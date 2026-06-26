<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use App\Services\Pdf\PdfWatermarkService;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class WatermarkService
{
    public function __construct(
        private readonly PdfWatermarkService $pdfWatermarkService,
    ) {}

    public function shouldApply(?Setting $setting, ?User $user): bool
    {
        if (! $setting || ! $setting->enable_watermark) {
            return false;
        }

        return ! ($user && ($user->is_master_admin() || $user->is_super_admin()));
    }

    public function buildWatermarkText(?User $user): string
    {
        $userEmail = $user?->email ?? 'unknown@domain.com';

        return $userEmail.' | '.now()->format('Y-m-d H:i');
    }

    public function applyToContent(string $content, string $fileName, string $watermarkText): string
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        try {
            if (in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
                return $this->applyImageWatermark($content, $ext, $watermarkText);
            }

            if ($ext === 'pdf') {
                return $this->pdfWatermarkService->applyFromContent($content, $watermarkText);
            }
        } catch (\Throwable $e) {
            Log::error('Watermark application failed', [
                'file' => $fileName,
                'error' => $e->getMessage(),
            ]);
        }

        return $content;
    }

    /**
     * @return string Path to the watermarked file (may be a new temp file)
     */
    public function applyToFile(string $path, string $fileName, string $watermarkText): string
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        try {
            if (in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
                $content = file_get_contents($path);

                if ($content === false) {
                    return $path;
                }

                $watermarked = $this->applyImageWatermark($content, $ext, $watermarkText);
                $outPath = $path.'.wm.'.$ext;
                file_put_contents($outPath, $watermarked);

                return $outPath;
            }

            if ($ext === 'pdf') {
                return $this->pdfWatermarkService->apply($path, $watermarkText);
            }
        } catch (\Throwable $e) {
            Log::error('Watermark application failed', [
                'file' => $fileName,
                'error' => $e->getMessage(),
            ]);
        }

        return $path;
    }

    private function applyImageWatermark(string $content, string $ext, string $watermarkText): string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($content);

        $width = $image->width();
        $height = $image->height();
        $fontSize = min($width, $height) / 3;

        $image->text($watermarkText, $width / 2, $height / 2, function ($font) use ($fontSize) {
            $font->size($fontSize);
            $font->color('#CCCCCC80');
            $font->align('center');
            $font->valign('middle');
            $font->angle(45);
        });

        $image->text($watermarkText, $width / 2, $height / 2, function ($font) use ($fontSize) {
            $font->size($fontSize);
            $font->color('#CCCCCC80');
            $font->align('center');
            $font->valign('middle');
            $font->angle(-45);
        });

        return $ext === 'png'
            ? $image->toPng()->toString()
            : $image->toJpeg(90)->toString();
    }
}
