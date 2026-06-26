<?php

namespace App\Jobs;

use App\Models\Setting;
use App\Models\User;
use App\Models\ZipDownload;
use App\Services\WatermarkService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ProcessZipDownloadJob implements ShouldQueue
{
    use Queueable;

    /** @var list<string> */
    private array $tempFiles = [];

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(protected ZipDownload $zipDownload)
    {
        $this->onQueue(env('ZIP_DOWNLOAD_QUEUE', 'default'));
    }

    public function handle(): void
    {
        $tempZipPath = null;
        $zip = null;

        try {
            // Set temp directory BEFORE any operations
            $customTempDir = storage_path('app/temp');
            if (! is_dir($customTempDir)) {
                @mkdir($customTempDir, 0777, true);
            }
            putenv('TMPDIR=' . $customTempDir);
            putenv('TEMP=' . $customTempDir);
            putenv('TMP=' . $customTempDir);

            $stats = $this->collectFolderStats($this->zipDownload->folder_data);
            $maxFiles = (int) env('ZIP_DOWNLOAD_MAX_FILES', 500);
            $maxBytes = (int) env('ZIP_DOWNLOAD_MAX_BYTES', 2 * 1024 * 1024 * 1024);

            if ($stats['files'] > $maxFiles) {
                throw new \RuntimeException("Too many files ({$stats['files']}). Maximum: {$maxFiles}.");
            }

            if ($stats['bytes'] > $maxBytes) {
                $sizeMB = round($stats['bytes'] / 1024 / 1024, 2);
                $maxMB = round($maxBytes / 1024 / 1024, 2);
                throw new \RuntimeException("Folder is too large ({$sizeMB}MB). Maximum: {$maxMB}MB. Download smaller folders.");
            }

            $this->zipDownload->update(['status' => 'processing']);

            // Use storage/app/temp for zip file creation
            $tempZipPath = $customTempDir . '/zip_' . $this->zipDownload->id . '_' . time() . '.zip';

            $zip = new ZipArchive();
            $openResult = $zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            if ($openResult !== true) {
                throw new \RuntimeException("Could not create zip file. Error code: {$openResult}");
            }

            $this->addToZip(
                $this->zipDownload->folder_data,
                '',
                $zip,
                (string) $this->zipDownload->company_id,
                (int) $this->zipDownload->user_id
            );

            if ($zip !== null) {
                $zip->close();
                $zip = null;
            }
            $this->cleanupTempFiles();

            $storagePath = "zip_downloads/company_{$this->zipDownload->company_id}/{$this->zipDownload->folder_name}_{$this->zipDownload->id}.zip";

            $stream = fopen($tempZipPath, 'rb');
            if ($stream === false) {
                throw new \RuntimeException('Could not read zip for upload');
            }

            Storage::disk('s3')->writeStream($storagePath, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            @unlink($tempZipPath);
            $tempZipPath = null;

            $this->zipDownload->update([
                'status' => 'completed',
                'zip_path' => $storagePath,
            ]);
        } catch (\Throwable $e) {
            Log::error('Zip processing failed', [
                'zip_download_id' => $this->zipDownload->id,
                'error' => $e->getMessage(),
            ]);

            $this->zipDownload->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        } finally {
            if ($zip !== null) {
                @$zip->close();
            }
            $this->cleanupTempFiles();

            if ($tempZipPath && is_file($tempZipPath)) {
                @unlink($tempZipPath);
            }
        }
    }

    /**
     * @return array{files: int, bytes: int}
     */
    private function collectFolderStats(array $item): array
    {
        if (! empty($item['isDirectory'])) {
            $files = 0;
            $bytes = 0;

            foreach ($item['items'] ?? [] as $subItem) {
                $sub = $this->collectFolderStats($subItem);
                $files += $sub['files'];
                $bytes += $sub['bytes'];
            }

            return ['files' => $files, 'bytes' => $bytes];
        }

        $fileName = $item['file_name'] ?? null;
        if (empty($fileName)) {
            return ['files' => 0, 'bytes' => 0];
        }

        $s3Key = 'uploads/company_' . $this->zipDownload->company_id . '/' . $fileName;

        try {
            if (Storage::disk('s3')->exists($s3Key)) {
                return ['files' => 1, 'bytes' => (int) Storage::disk('s3')->size($s3Key)];
            }
        } catch (\Throwable $e) {
            Log::warning('Zip stat failed', ['key' => $s3Key, 'error' => $e->getMessage()]);
        }

        return ['files' => 0, 'bytes' => 0];
    }

    private function addToZip(array $item, string $relativePath, ZipArchive $zip, string $companyId, int $userId): void
    {
        $name = $item['name'] ?? 'unknown';
        $entryName = $relativePath . $name;

        if (! empty($item['isDirectory'])) {
            if ($entryName !== '') {
                $zip->addEmptyDir($entryName . '/');
            }

            foreach ($item['items'] ?? [] as $subItem) {
                $this->addToZip($subItem, $entryName . '/', $zip, $companyId, $userId);
            }

            return;
        }

        $fileName = $item['file_name'] ?? null;
        if (empty($fileName)) {
            return;
        }

        $s3Key = "uploads/company_{$companyId}/{$fileName}";
        $maxFileBytes = (int) env('ZIP_DOWNLOAD_MAX_FILE_BYTES', 100 * 1024 * 1024);

        try {
            if (! Storage::disk('s3')->exists($s3Key)) {
                return;
            }

            if ((int) Storage::disk('s3')->size($s3Key) > $maxFileBytes) {
                Log::warning('Skipped large file in zip', ['key' => $s3Key]);

                return;
            }

            $localPath = $this->streamS3ToTemp($s3Key);
            if ($localPath === null) {
                return;
            }

            $zipPath = $this->watermarkFileIfNeeded($localPath, $fileName, $companyId, $userId);
            if ($zipPath !== $localPath) {
                $this->tempFiles[] = $zipPath;
            }

            $zip->addFile($zipPath, $entryName);
            gc_collect_cycles();
        } catch (\Throwable $e) {
            Log::error('Error adding file to zip', ['key' => $s3Key, 'error' => $e->getMessage()]);
        }
    }

    private function streamS3ToTemp(string $s3Key): ?string
    {
        $in = Storage::disk('s3')->readStream($s3Key);
        if ($in === null) {
            return null;
        }

        $customTempDir = storage_path('app/temp');
        $tempPath = tempnam($customTempDir, 'zipf_');
        if ($tempPath === false) {
            if (is_resource($in)) {
                fclose($in);
            }

            return null;
        }

        $out = fopen($tempPath, 'wb');
        if ($out === false) {
            @unlink($tempPath);
            if (is_resource($in)) {
                fclose($in);
            }

            return null;
        }

        stream_copy_to_stream($in, $out);
        fclose($out);

        if (is_resource($in)) {
            fclose($in);
        }

        $this->tempFiles[] = $tempPath;

        return $tempPath;
    }

    private function watermarkFileIfNeeded(string $path, string $fileName, string $companyId, int $userId): string
    {
        $watermarkService = app(WatermarkService::class);

        $setting = Setting::where('company_id', $companyId)->first();
        $user = User::find($userId);

        if (! $watermarkService->shouldApply($setting, $user)) {
            return $path;
        }

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (! in_array($ext, ['png', 'jpg', 'jpeg', 'pdf'], true)) {
            return $path;
        }

        $maxBytes = (int) env('ZIP_DOWNLOAD_MAX_WATERMARK_BYTES', 15 * 1024 * 1024);
        if (filesize($path) > $maxBytes) {
            return $path;
        }

        $text = $watermarkService->buildWatermarkText($user);
        $outPath = $watermarkService->applyToFile($path, $fileName, $text);

        if ($outPath !== $path) {
            $this->tempFiles[] = $outPath;
        }

        return $outPath;
    }

    private function cleanupTempFiles(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->tempFiles = [];
    }
}
