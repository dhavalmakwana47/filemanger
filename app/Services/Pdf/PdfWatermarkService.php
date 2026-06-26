<?php

namespace App\Services\Pdf;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfReader\PdfReaderException;

class PdfWatermarkService
{
    public function __construct(
        private readonly PdfNormalizer $pdfNormalizer,
    ) {}

    public function apply(string $sourcePath, string $watermarkText): string
    {
        if (! is_readable($sourcePath)) {
            throw new RuntimeException('Source PDF is not readable.');
        }

        try {
            return $this->renderWatermark($sourcePath, $watermarkText);
        } catch (\Throwable $exception) {
            if (! $this->requiresNormalization($exception)) {
                $this->handleRenderException($sourcePath, $exception);
            }
        }

        Log::info('Normalizing PDF before watermarking', [
            'source_path' => $sourcePath,
        ]);

        $normalizedPath = $this->pdfNormalizer->normalize($sourcePath);

        try {
            return $this->renderWatermark($normalizedPath, $watermarkText);
        } catch (\Throwable $exception) {
            $this->handleRenderException($sourcePath, $exception);

            throw $exception;
        } finally {
            @unlink($normalizedPath);
        }
    }

    public function applyFromContent(string $content, string $watermarkText): string
    {
        $sourcePath = tempnam(sys_get_temp_dir(), 'pdf_src_');

        if ($sourcePath === false) {
            throw new RuntimeException('Unable to create temporary PDF source file.');
        }

        file_put_contents($sourcePath, $content);

        try {
            $watermarkedPath = $this->apply($sourcePath, $watermarkText);
            $result = file_get_contents($watermarkedPath);
            @unlink($watermarkedPath);

            if ($result === false) {
                throw new RuntimeException('Unable to read watermarked PDF.');
            }

            return $result;
        } finally {
            @unlink($sourcePath);
        }
    }

    private function renderWatermark(string $sourcePath, string $watermarkText): string
    {
        $pdf = new WatermarkFpdi;
        $pageCount = $pdf->setSourceFile($sourcePath);

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $templateId = $pdf->importPage($pageNumber);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            $this->drawWatermark($pdf, $watermarkText, $size['width'], $size['height']);
        }

        $tempPath = $this->createTempPath();
        $pdf->Output('F', $tempPath);

        return $tempPath;
    }

    private function requiresNormalization(\Throwable $exception): bool
    {
        return str_contains(
            $exception->getMessage(),
            'compression technique which is not supported'
        );
    }

    private function handleRenderException(string $sourcePath, \Throwable $exception): void
    {
        if ($exception instanceof CrossReferenceException
            || $exception instanceof PdfParserException
            || $exception instanceof PdfReaderException) {
            Log::error('Watermark generation failed: invalid PDF', [
                'source_path' => $sourcePath,
                'error' => $exception->getMessage(),
            ]);

            throw new RuntimeException('The PDF file could not be parsed for watermarking.', 0, $exception);
        }

        Log::error('Watermark generation failed', [
            'source_path' => $sourcePath,
            'error' => $exception->getMessage(),
        ]);

        throw new RuntimeException('Failed to generate watermarked PDF.', 0, $exception);
    }

    private function drawWatermark(WatermarkFpdi $pdf, string $text, float $pageWidth, float $pageHeight): void
    {
        [$red, $green, $blue] = config('pdf.font_color');
        $fontSize = config('pdf.font_size');
        $angle = config('pdf.rotation_angle');

        $pdf->SetFont('Helvetica', 'B', $fontSize);
        $pdf->SetTextColor($red, $green, $blue);

        $centerX = $pageWidth / 2;
        $centerY = $pageHeight / 2;
        $textWidth = $pdf->GetStringWidth($text);

        $pdf->rotate($angle, $centerX, $centerY);
        $pdf->Text($centerX - ($textWidth / 2), $centerY, $text);
        $pdf->rotate(0);
    }

    private function createTempPath(): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'pdf_watermark_');

        if ($tempPath === false) {
            throw new RuntimeException('Unable to create temporary file for watermarked PDF.');
        }

        return $tempPath;
    }
}
