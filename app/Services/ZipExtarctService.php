<?php

namespace App\Services;

use App\Models\File;
use App\Models\Folder;
use App\Models\RoleFilePermission;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use ZipArchive;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ZipExtarctService
{
    public $disk;
    public $rootFolder;
    public function __construct()
    {
        $this->disk = config('filesystems.default') == 's3' ? 's3' : 'public';
        $this->rootFolder = null;
    }

    public function extractUploadedZip(File $file)
    {
        try {
            $disk = Storage::disk($this->disk);
            $company_id = get_active_company();
            $parentId = $file->folder_id ?? null;

            $zipFileName = $file->name;
            $zipPath = "uploads/company_{$company_id}/{$zipFileName}";

            // Check if zip exists with better error handling
            try {
                if (!$disk->exists($zipPath)) {
                    throw new \Exception("Zip file not found at path: {$zipPath}");
                }
            } catch (\Exception $e) {
                Log::error("Error checking zip file existence: " . $e->getMessage());
                throw new \Exception("Unable to access zip file: " . $e->getMessage());
            }

            // For S3, download to temp file; for public, use direct path
            if ($this->disk === 's3') {
                try {
                    $zipContent = $disk->get($zipPath);
                    $tempZipPath = tempnam(sys_get_temp_dir(), 'zip_') . '.zip';
                    file_put_contents($tempZipPath, $zipContent);
                    $zipFullPath = $tempZipPath;
                } catch (\Exception $e) {
                    Log::error("Error downloading zip from S3: " . $e->getMessage());
                    throw new \Exception("Failed to download zip file: " . $e->getMessage());
                }
            } else {
                $zipFullPath = $disk->path($zipPath);
                if (!file_exists($zipFullPath)) {
                    throw new \Exception("Zip file does not exist at local path: {$zipFullPath}");
                }
            }

            // Open the zip file
            $zip = new ZipArchive();
            if ($zip->open($zipFullPath) !== true) {
                if ($this->disk === 's3' && isset($tempZipPath)) unlink($tempZipPath);
                return response()->json(['error' => 'Could not open zip file'], 500);
            }

            // Process entries
            $this->processZipEntries($zip, $company_id, $parentId);

            // Close and clean up
            $zip->close();
            if ($this->disk === 's3' && isset($tempZipPath)) {
                // Add small delay to ensure file handle is released
                usleep(100000); // 0.1 second
                if (file_exists($tempZipPath)) {
                    try {
                        unlink($tempZipPath);
                    } catch (\Exception $e) {
                        Log::warning("Could not delete temp zip file: " . $e->getMessage());
                    }
                }
            }

            // Delete original zip and database record
            try {
                $disk->delete($zipPath);
            } catch (\Exception $e) {
                Log::warning("Could not delete original zip file: " . $e->getMessage());
            }
            $file->forceDelete();
            $this->deleteRootFolder();


            // Log the action
            addUserAction([
                'user_id' => Auth::id(),
                'action' => "Zip file {$zipFileName} extracted and deleted for company {$company_id}"
            ]);
        } catch (\Exception $e) {
            if ($this->disk === 's3' && isset($tempZipPath) && file_exists($tempZipPath)) {
                unlink($tempZipPath);
            }

            // Log error action
            addUserAction([
                'user_id' => Auth::id(),
                'action' => "Error extracting zip file {$zipFileName}: " . $e->getMessage()
            ]);

            throw $e;
        }
    }

    private function processZipEntries(ZipArchive $zip, string $company_id, ?int $parent_id = null): void
    {
        // Track created folders to avoid duplicates
        $folderCache = [];
        $disk = Storage::disk($this->disk);
        $basePath = "uploads/company_{$company_id}/";

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);

            // Skip Mac junk
            if (str_starts_with($entry, '__MACOSX/') || str_ends_with($entry, '.DS_Store')) {
                continue;
            }

            if (substr($entry, -1) === '/') {
                // Directory → create in database only (your existing method)
                $this->createFolderFromZip($entry, $company_id, $parent_id, $folderCache);
            } else {
                // File → extract content and save to S3, then create DB record
                $stream = $zip->getStream($entry);

                if ($stream) {
                    // Upload directly to S3 from stream (efficient)
                    $fullS3Path = $basePath . $entry; // preserves folder structure

                    $disk->put($fullS3Path, $stream);

                    // DO NOT fclose($stream) — ZipArchive closes it automatically

                    // Now create the File record in DB using your existing method
                    // It should use the same $entry path and $folderCache to set correct folder_id
                    $this->createFileFromZip($entry, $zip, $company_id, $parent_id, $folderCache);
                }
                // If stream fails, skip silently (corrupted entry)
            }
        }
    }

    private function createFolderFromZip(string $entry, string $company_id, ?int $parent_id, array &$folderCache): void
    {
        // Remove trailing slash and normalize path
        $entry = rtrim($entry, '/');
        if (empty($entry)) {
            return;
        }

        // Split path into segments
        $pathSegments = explode('/', $entry);
        $currentParentId = $parent_id;
        $currentPath = '';

        // Build folder hierarchy
        foreach ($pathSegments as $index => $segment) {
            $currentPath .= $segment;
            $cacheKey = $currentPath;

            // Check if folder already processed
            if (isset($folderCache[$cacheKey])) {
                $currentParentId = $folderCache[$cacheKey];
                $currentPath .= '/';
                continue;
            }
            if (!isset($this->rootFolder)) {
                $this->rootFolder = $segment;
            }

            // Create folder in database
            $folder = Folder::create([
                'name' => $segment,
                'parent_id' => $currentParentId,
                'company_id' => $company_id,
                'created_by' => Auth::id(),
            ]);

            // Store folder ID in cache
            $folderCache[$cacheKey] = $folder->id;
            $currentParentId = $folder->id;

            // Assign default permissions
            $this->syncPermissions($folder->id, request()->input('permissions', []));

            // Log folder creation
            addUserAction([
                'user_id' => Auth::id(),
                'action' => "Folder {$folder->name} created"
            ]);

            $currentPath .= '/';
        }
    }

    private function createFileFromZip(string $entry, ZipArchive $zip, string $company_id, ?int $parent_id, array &$folderCache): void
    {
        // Allowed MIME types
        $allowedMimeTypes = [
            // Original MIME types
            'image/png',
            'image/jpeg',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/x-zip-compressed',
            'text/csv',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'application/x-rar-compressed',
            'application/vnd.rar',

            // Additional MIME types
            'image/tiff',
            'image/tif',
            'application/rtf',
            'application/vnd.ms-excel',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'video/mp4',
            'video/quicktime',
            'video/x-ms-wmv',
            'video/x-matroska',
            'video/mpeg',
            'audio/mpeg',
            'audio/wav',
            'audio/aac',
            'audio/mp4',
            'audio/x-m4a',
            'application/acad',
            'application/x-acad',
            'application/autocad_dwg',
            'application/dwg',
            'application/x-dwg',
            'application/x-autocad',
            'drawing/dwg',
            'image/vnd.dwg',
            'image/x-dwg',
            'application/x-7z-compressed'
        ];

        // Get parent folder ID for the file
        $pathSegments = explode('/', $entry);
        $fileName = array_pop($pathSegments); // Last segment is the file name
        $parentPath = implode('/', $pathSegments);

        $parentFolderId = $parent_id;
        if (!empty($parentPath)) {
            // Check if parent folder is in cache
            if (isset($folderCache[$parentPath])) {
                $parentFolderId = $folderCache[$parentPath];
            } else {
                // Create parent folders if they don’t exist
                $this->createFolderFromZip($parentPath . '/', $company_id, $parent_id, $folderCache);
                $parentFolderId = $folderCache[$parentPath] ?? $parent_id;
            }
        }

        // Extract file from zip
        $fileStream = $zip->getStream($entry);
        if ($fileStream === false) {
            Log::warning("Could not read file from zip: {$entry}");
            return;
        }
        // Check MIME type
        $tmpPath = storage_path('app/tmp_' . uniqid());
        $tmpHandle = fopen($tmpPath, 'w');
        stream_copy_to_stream($fileStream, $tmpHandle);
        fclose($fileStream);
        fclose($tmpHandle);
        $mimeType = \Illuminate\Support\Facades\File::mimeType($tmpPath);
        if (!in_array($mimeType, $allowedMimeTypes)) {
            Log::info("Skipped file from zip (not allowed MIME): {$entry} ({$mimeType})");
            unlink($tmpPath);
            return;
        }
        // Generate new filename with timestamp
        $originalName = pathinfo($fileName, PATHINFO_FILENAME);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = $originalName . '_' . time() . '.' . $extension;
        $filePath = "uploads/company_{$company_id}/{$newFileName}";

        // Store file in storage
        $disk = Storage::disk($this->disk);
        $disk->put($filePath, fopen($tmpPath, 'r'));
        unlink($tmpPath);

        // Check if file exists and get size, default to 0 if not found
        $sizeKb = 0;
        if ($disk->exists($filePath)) {
            $sizeKb = $disk->size($filePath);
        }
        // Save file metadata in database
        $file = File::create([
            'name' => $newFileName,
            'file_name' => $fileName,
            'folder_id' => $parentFolderId,
            'company_id' => $company_id,
            'file_path' => $filePath,
            'size_kb' => $sizeKb,
            'created_by' => Auth::id(),
        ]);

        // Assign default permissions
        $this->syncPermissions($file->id, request()->input('permissions', []));

        // Log file creation
        addUserAction([
            'user_id' => Auth::id(),
            'action' => "File {$file->name} created"
        ]);
    }

    private function syncPermissions($fileId, array $permissions)
    {
        RoleFilePermission::where('file_id', $fileId)->delete();

        $rolePermissions = collect($permissions)
            ->map(fn($roleId) => [
                'company_role_id' => $roleId,
                'file_id' => $fileId,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        if (!empty($rolePermissions)) {
            RoleFilePermission::insert($rolePermissions);
        }
    }

    public function deleteRootFolder()
    {
        $company_id = get_active_company();

        if (isset($this->rootFolder)) {
                    $filePath = "uploads/company_{$company_id}/{$this->rootFolder}";
            $disk = Storage::disk($this->disk);
            $disk->deleteDirectory($filePath);
        }
    }
}
