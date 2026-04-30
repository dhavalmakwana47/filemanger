<?php

namespace App\Console\Commands;

use App\Models\ZipDownload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupZipDownloads extends Command
{
    protected $signature = 'zip-downloads:cleanup';

    protected $description = 'Delete ZipDownload files and records older than 24 hours';

    public function handle(): int
    {
        $cutoff = now()->subHours(24);
        $deletedRecords = 0;
        $deletedFiles = 0;
        $missingFiles = 0;

        ZipDownload::where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(200, function ($downloads) use (&$deletedRecords, &$deletedFiles, &$missingFiles) {
                foreach ($downloads as $download) {
                    if (!empty($download->zip_path)) {
                        try {
                            if (Storage::disk('s3')->exists($download->zip_path)) {
                                Storage::disk('s3')->delete($download->zip_path);
                                $deletedFiles++;
                            } else {
                                $missingFiles++;
                            }
                        } catch (\Throwable $e) {
                            Log::warning('Failed deleting zip file from S3 during cleanup.', [
                                'zip_download_id' => $download->id,
                                'zip_path' => $download->zip_path,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $download->delete();
                    $deletedRecords++;
                }
            });

        $this->info("Zip cleanup completed. Deleted records: {$deletedRecords}, deleted files: {$deletedFiles}, missing files: {$missingFiles}");

        return self::SUCCESS;
    }
}
