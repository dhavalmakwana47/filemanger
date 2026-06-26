<?php

namespace App\Services\Pdf;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;

class PdfNormalizer
{
    public function normalize(string $sourcePath): string
    {
        $outputPath = $this->createOutputPath();

        $process = new Process([
            config('pdf.ghostscript_binary'),
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-dNOPAUSE',
            '-dBATCH',
            '-dQUIET',
            '-sOutputFile='.$outputPath,
            $sourcePath,
        ]);

        $process->setTimeout((int) config('pdf.normalizer_timeout'));

        try {
            $process->mustRun();
        } catch (\Throwable $exception) {
            @unlink($outputPath);

            Log::error('PDF normalization failed', [
                'source_path' => $sourcePath,
                'error' => $exception->getMessage(),
            ]);

            throw new RuntimeException(
                'This PDF uses features that require normalization before watermarking, and normalization failed.'
            );
        }

        if (! is_readable($outputPath) || filesize($outputPath) === 0) {
            @unlink($outputPath);

            throw new RuntimeException('PDF normalization produced an empty file.');
        }

        return $outputPath;
    }

    private function createOutputPath(): string
    {
        $path = sys_get_temp_dir().'/pdf_normalized_'.bin2hex(random_bytes(8)).'.pdf';

        if (file_exists($path)) {
            @unlink($path);
        }

        return $path;
    }
}
