<?php

namespace App\Jobs;

use App\Models\File;
use App\Services\ZipExtarctService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExtractZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;
    public $maxExceptions = 3;

    protected $fileId;
    protected $userId;
    protected $companyId;

    public function __construct($fileId, $userId, $companyId)
    {
        $this->fileId = $fileId;
        $this->userId = $userId;
        $this->companyId = $companyId;
    }

    public function handle(ZipExtarctService $zipService)
    {
        try {
            $file = File::find($this->fileId);
            
            if (!$file) {
                \Log::error("ExtractZipJob: File not found with ID {$this->fileId}");
                $this->fail("File not found with ID {$this->fileId}");
                return;
            }

            // session(['active_company' => $this->companyId]);
            // auth()->loginUsingId($this->userId);
            
            $zipService->extractUploadedZip($file);
        } catch (\Exception $e) {
            \Log::error("ExtractZipJob handle error: " . $e->getMessage());
            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception)
    {
        \Log::error("ExtractZipJob failed: " . $exception->getMessage());
    }
}