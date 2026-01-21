<?php

namespace App\Jobs;

use App\Models\ZipDownload;
use App\Models\Setting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProcessZipDownloadJob implements ShouldQueue
{
    use Queueable;

    protected $zipDownload;

    public function __construct(ZipDownload $zipDownload)
    {
        $this->zipDownload = $zipDownload;
    }

    public function handle(): void
    {
        try {
            $this->zipDownload->update(['status' => 'processing']);

            $zip = new ZipArchive();
            $zipFileName = 'zip_downloads/' . $this->zipDownload->id . '_' . time() . '.zip';
            $tempZipPath = storage_path('app/' . $zipFileName);

            if (!is_dir(dirname($tempZipPath))) {
                mkdir(dirname($tempZipPath), 0755, true);
            }

            if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Could not create zip file');
            }

            $this->addToZip($this->zipDownload->folder_data, '', $zip, $this->zipDownload->company_id, $this->zipDownload->user_id);
            $zip->close();

            $storagePath = "zip_downloads/company_{$this->zipDownload->company_id}/{$this->zipDownload->folder_name}_{$this->zipDownload->id}.zip";
            Storage::disk('s3')->put($storagePath, file_get_contents($tempZipPath));

            unlink($tempZipPath);

            $this->zipDownload->update([
                'status' => 'completed',
                'zip_path' => $storagePath
            ]);

        } catch (\Exception $e) {
            Log::error('Zip processing failed: ' . $e->getMessage());
            $this->zipDownload->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
        }
    }

    private function addToZip(array $item, string $relativePath, ZipArchive $zip, string $company_id, int $user_id): void
    {
        $name = $item['name'] ?? 'unknown';
        $entryName = $relativePath . $name;
        $isDir = !empty($item['isDirectory']);

        if ($isDir) {
            if ($entryName !== '') {
                $zip->addEmptyDir($entryName . '/');
            }

            $subItems = $item['items'] ?? [];
            foreach ($subItems as $subItem) {
                $this->addToZip($subItem, $entryName . '/', $zip, $company_id, $user_id);
            }
        } else {
            $file_name = $item['file_name'] ?? null;
            if (empty($file_name)) {
                return;
            }

            $s3Key = "uploads/company_{$company_id}/{$file_name}";

            try {
                if (Storage::disk('s3')->exists($s3Key)) {
                    $content = Storage::disk('s3')->get($s3Key);
                    if ($content === null) {
                        return;
                    }

                    $content = $this->applyWatermarkToContent($content, $file_name, $company_id, $user_id);
                    $zip->addFromString($entryName, $content);
                }
            } catch (\Exception $e) {
                Log::error('Error accessing S3 file: ' . $e->getMessage());
            }
        }
    }

    private function applyWatermarkToContent($content, $fileName, $company_id, $user_id)
    {
        $setting = Setting::where('company_id', $company_id)->first();
        Log::info('Watermark check', ['company_id' => $company_id, 'setting_exists' => !!$setting, 'enable_watermark' => $setting?->enable_watermark]);
        
        if (!$setting || !$setting->enable_watermark) {
            return $content;
        }

        $user = \App\Models\User::find($user_id);
        if ($user && ($user->is_master_admin() || $user->is_super_admin())) {
            Log::info('Skipping watermark for admin user', ['user_id' => $user_id]);
            return $content;
        }

        $userEmail = $user?->email ?? 'unknown@domain.com';
        $downloadDate = now()->format('Y-m-d H:i');
        $textWatermark = "$userEmail | $downloadDate";
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        Log::info('Applying watermark', ['file' => $fileName, 'ext' => $ext, 'watermark' => $textWatermark]);

        try {
            if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
                $manager = new ImageManager(new Driver());
                $image = $manager->read($content);

                $width = $image->width();
                $height = $image->height();
                $fontSize = min($width, $height) / 3;

                $image->text($textWatermark, $width / 2, $height / 2, function ($font) use ($fontSize) {
                    $font->size($fontSize);
                    $font->color('#CCCCCC80');
                    $font->align('center');
                    $font->valign('middle');
                    $font->angle(45);
                });

                Log::info('Image watermark applied', ['file' => $fileName]);
                return $ext === 'png' ? $image->toPng()->toString() : $image->toJpeg(90)->toString();
            }
            // Skip PDF watermarking due to FPDI limitations
        } catch (\Exception $e) {
            Log::error('Watermark application failed', ['file' => $fileName, 'error' => $e->getMessage()]);
        }

        return $content;
    }
}
