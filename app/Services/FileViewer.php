<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class FileViewer
{
    public static function viewFile($value)
    {
        $storageType = config('filesystems.default') == 's3' ? 's3' : 'public';
        if (Storage::disk($storageType)->exists($value)) {
            $fileContent = Storage::disk($storageType)->get($value);
            $mimeType = Storage::disk($storageType)->mimeType($value);
            $fileName = basename($value);
            // Add explicit MIME type for EPUB files
            if (pathinfo($fileName, PATHINFO_EXTENSION) === 'epub') {
                $mimeType = 'application/epub+zip';
            }

            // Convert .doc to PDF if needed (requires server-side tool)
            if (str_contains($mimeType, 'msword') && !str_contains($mimeType, 'wordprocessingml')) {

                // Example: Save to temp file, convert, then stream
                // 1. Store uploaded file content in storage/app/temp/filename
                Storage::put('temp/' . $fileName, $fileContent);

                // 2. Get full absolute paths for input and output files for exec()
                $inputPath = storage_path('app/temp/' . $fileName);
                $outputPdfPath = storage_path('app/temp/' . pathinfo($fileName, PATHINFO_FILENAME) . '.pdf');

                // 3. Convert to PDF using unoconv (make sure unoconv is installed and in PATH)
                exec("unoconv -f pdf -o " . escapeshellarg($outputPdfPath) . " " . escapeshellarg($inputPath));
                // 4. Read the generated PDF content
                $fileContent = file_get_contents($outputPdfPath);

                $mimeType = 'application/pdf';

                // 5. Delete the original and pdf files from storage/app/temp
                Storage::delete(['temp/' . $fileName, 'temp/' . pathinfo($fileName, PATHINFO_FILENAME) . '.pdf']);
            }

            // Add explicit MIME types for audio files
            if (in_array(pathinfo($fileName, PATHINFO_EXTENSION), ['mp3', 'wav', 'ogg'])) {
                $mimeType = match (pathinfo($fileName, PATHINFO_EXTENSION)) {
                    'mp3' => 'audio/mpeg',
                    'wav' => 'audio/wav',
                    'ogg' => 'audio/ogg',
                    default => $mimeType,
                };
            }
            return response($fileContent, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline; filename="' . $fileName . '"')
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }
    }
}
