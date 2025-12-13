<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileStorageService
{

    public $disk;
    public function __construct()
    {
        $this->disk =config('filesystems.default') == 's3' ? 's3' : 'public';
    }
    /**
     * Store a new file in the specified disk and folder.
     *
     * @param UploadedFile $file
     * @param string $folder       // e.g., 'avatars', 'documents/products'
     * @param string $disk         // e.g., 'public', 's3'
     * @param string|null $filename // Optional custom filename (without extension)
     * @return string              // Returns the stored file path (relative to disk root)
     */
    public function store(UploadedFile $file, string $folder = '',  ?string $filename = null): string
    {
        $folder = ltrim($folder, '/');
        $path = $file->storeAs($folder, $filename, $this->disk);

        return $path;
    }

    /**
     * Delete a file from storage.
     *
     * @param string $path
     * @param string $disk
     * @return bool
     */
    public function delete(string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        return Storage::disk($this->disk)->delete($path);
    }

    /**
     * Replace an existing file with a new one (delete old, store new).
     *
     * @param UploadedFile $newFile
     * @param string $oldPath
     * @param string $folder
     * @param string $disk
     * @param string|null $filename
     * @return string              // New file path
     */
    public function update(UploadedFile $newFile, string $oldPath, string $folder = '', ?string $filename = null): string
    {
        // Delete old file if exists
        if ($oldPath && $this->delete($oldPath, $this->disk)) {
            // Old file deleted successfully
        }

        // Store new file
        return $this->store($newFile, $folder, $this->disk, $filename);
    }

    /**
     * Get the public URL of a file (works for 'public' and 's3' disks).
     *
     * @param string $path
     * @param string $disk
     * @return string
     */
    public function url(string $path): string
    {
        return Storage::disk($this->disk)->url($path);
    }

    /**
     * Get temporary URL for private files (S3 only, with expiration).
     *
     * @param string $path
     * @param int $expiresInMinutes
     * @param string $disk
     * @return string
     */
    public function temporaryUrl(string $path, int $expiresInMinutes = 60): string
    {
        return Storage::disk($this->disk)->temporaryUrl($path, now()->addMinutes($expiresInMinutes));
    }

    /**
     * Check if file exists.
     */
    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }
}