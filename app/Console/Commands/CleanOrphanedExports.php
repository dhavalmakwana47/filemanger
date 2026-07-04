<?php

namespace App\Console\Commands;

use App\Models\LogExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanOrphanedExports extends Command
{
    protected $signature   = 'exports:clean';
    protected $description = 'Delete orphaned export files in storage/app/log_exports older than 24 hours';

    public function handle(): void
    {
        $dir      = storage_path('app/log_exports');
        $deleted  = 0;

        if (! is_dir($dir)) return;

        // Collect all file paths tracked in DB
        $tracked = LogExport::whereNotNull('file_path')
            ->pluck('file_path')
            ->flatMap(fn($v) => is_array($v) ? $v : [$v])
            ->map(fn($p) => storage_path('app/' . $p))
            ->flip()
            ->all();

        foreach (glob($dir . '/*') as $file) {
            if (! is_file($file)) continue;

            $isOld       = filemtime($file) < now()->subHours(24)->timestamp;
            $isUntracked = ! isset($tracked[$file]);

            if ($isOld && $isUntracked) {
                @unlink($file);
                $deleted++;
                Log::info('[CleanOrphanedExports] Deleted: ' . basename($file));
            }
        }

        $this->info("Deleted {$deleted} orphaned export file(s).");
    }
}
