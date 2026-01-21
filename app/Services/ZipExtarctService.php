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
use Illuminate\Support\Facades\Process;

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
        $fileExtension = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));
        Log::info("Starting extraction for file: {$file->name} with extension: {$fileExtension}");
        if ($fileExtension === 'rar') {
            return $this->extractRarFile($file);
        } else {
            return $this->extractZipFile($file);
        }
    }

    private function extractZipFile(File $file)
    {
        try {
            $disk = Storage::disk($this->disk);
            $company_id = get_active_company();
            $parentId = $file->folder_id ?? null;

            $zipFileName = $file->name;
            $zipPath = "uploads/company_{$company_id}/{$zipFileName}";

            if (!$disk->exists($zipPath)) {
                throw new \Exception("Archive file not found at path: {$zipPath}");
            }

            if ($this->disk === 's3') {
                $zipContent = $disk->get($zipPath);
                $tempZipPath = tempnam(sys_get_temp_dir(), 'zip_') . '.zip';
                file_put_contents($tempZipPath, $zipContent);
                $zipFullPath = $tempZipPath;
            } else {
                $zipFullPath = $disk->path($zipPath);
            }

            $zip = new ZipArchive();
            if ($zip->open($zipFullPath) !== true) {
                if ($this->disk === 's3' && isset($tempZipPath)) unlink($tempZipPath);
                throw new \Exception('Could not open zip file');
            }

            $this->processZipEntries($zip, $company_id, $parentId);
            $zip->close();

            if ($this->disk === 's3' && isset($tempZipPath)) {
                usleep(100000);
                if (file_exists($tempZipPath)) unlink($tempZipPath);
            }

            $disk->delete($zipPath);
            $file->forceDelete();
            $this->deleteRootFolder();

            addUserAction([
                'user_id' => Auth::id(),
                'action' => "Archive file {$zipFileName} extracted and RAR/Zip File deleted Successfully"
            ]);
        } catch (\Exception $e) {
            if ($this->disk === 's3' && isset($tempZipPath) && file_exists($tempZipPath)) {
                unlink($tempZipPath);
            }
            throw $e;
        }
    }

    private function extractRarFile(File $file)
    {
        try {
            $disk = Storage::disk($this->disk);
            $company_id = get_active_company();
            $parentId = $file->folder_id ?? null;

            $rarFileName = $file->name;
            $rarPath = "uploads/company_{$company_id}/{$rarFileName}";

            if (!$disk->exists($rarPath)) {
                throw new \Exception("RAR file not found at path: {$rarPath}");
            }

            // Create temp directory for extraction
            $tempDir = sys_get_temp_dir() . '/rar_extract_' . uniqid();
            mkdir($tempDir, 0755, true);

            if ($this->disk === 's3') {
                $rarContent = $disk->get($rarPath);
                $tempRarPath = $tempDir . '/' . $rarFileName;
                file_put_contents($tempRarPath, $rarContent);
            } else {
                $tempRarPath = $disk->path($rarPath);
            }

            // Extract using unrar command
            $command = "unrar x -o+ \"$tempRarPath\" \"$tempDir/\"";
            $result = shell_exec($command . ' 2>&1');
            
            if ($result === null || strpos($result, 'All OK') === false) {
                throw new \Exception('Could not extract RAR file: ' . $result);
            }

            $this->processExtractedFiles($tempDir, $company_id, $parentId);

            // Clean up
            $this->deleteDirectory($tempDir);
            $disk->delete($rarPath);
            $file->forceDelete();
            $this->deleteRootFolder();

            addUserAction([
                'user_id' => Auth::id(),
                'action' => "RAR file {$rarFileName} extracted and RAR/Zip File deleted Successfully"
            ]);
        } catch (\Exception $e) {
            if (isset($tempDir) && is_dir($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
            throw $e;
        }
    }

    private function processZipEntries(ZipArchive $zip, string $company_id, ?int $parent_id = null): void
    {
        $folderCache = [];
        $disk = Storage::disk($this->disk);
        $basePath = "uploads/company_{$company_id}/";

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);

            if (str_starts_with($entry, '__MACOSX/') || str_ends_with($entry, '.DS_Store')) {
                continue;
            }

            if (substr($entry, -1) === '/') {
                $this->createFolderFromZip($entry, $company_id, $parent_id, $folderCache);
            } else {
                $stream = $zip->getStream($entry);
                if ($stream) {
                    $fullS3Path = $basePath . $entry;
                    $disk->put($fullS3Path, $stream);
                    $this->createFileFromZip($entry, $zip, $company_id, $parent_id, $folderCache);
                }
            }
        }
    }

    private function processExtractedFiles(string $tempDir, string $company_id, ?int $parent_id = null): void
    {
        $folderCache = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tempDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $relativePath = str_replace($tempDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $relativePath = str_replace('\\', '/', $relativePath);

            if ($file->isDir()) {
                $this->createFolderFromZip($relativePath . '/', $company_id, $parent_id, $folderCache);
            } else {
                // Upload file to storage
                $disk = Storage::disk($this->disk);
                $storagePath = "uploads/company_{$company_id}/{$relativePath}";
                $disk->put($storagePath, file_get_contents($file->getPathname()));
                
                $this->createFileFromZip($relativePath, null, $company_id, $parent_id, $folderCache);
            }
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
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

    private function createFileFromZip(string $entry, ?ZipArchive $zip, string $company_id, ?int $parent_id, array &$folderCache): void
    {
        // Allowed MIME types
        $allowedMimeTypes = [
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
        $fileName = array_pop($pathSegments);
        $parentPath = implode('/', $pathSegments);

        $parentFolderId = $parent_id;
        if (!empty($parentPath)) {
            if (isset($folderCache[$parentPath])) {
                $parentFolderId = $folderCache[$parentPath];
            } else {
                $this->createFolderFromZip($parentPath . '/', $company_id, $parent_id, $folderCache);
                $parentFolderId = $folderCache[$parentPath] ?? $parent_id;
            }
        }

        // Get file info for validation
        $disk = Storage::disk($this->disk);
        $filePath = "uploads/company_{$company_id}/{$entry}";
        
        if (!$disk->exists($filePath)) {
            Log::warning("File not found in storage: {$entry}");
            return;
        }
        
        $mimeType = $disk->mimeType($filePath) ?? 'application/octet-stream';
        
        if (!in_array($mimeType, $allowedMimeTypes)) {
            Log::info("Skipped file from archive (not allowed MIME): {$entry} ({$mimeType})");
            $disk->delete($filePath);
            return;
        }
        
        // Generate new filename with timestamp
        $originalName = pathinfo($fileName, PATHINFO_FILENAME);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = $originalName . '_' . time() . '.' . $extension;
        $newFilePath = "uploads/company_{$company_id}/{$newFileName}";
        
        // Move file to new location with timestamp
        $disk->move($filePath, $newFilePath);
        $sizeKb = $disk->size($newFilePath);

        // Save file metadata in database
        $file = File::create([
            'name' => $newFileName,
            'file_name' => $fileName,
            'folder_id' => $parentFolderId,
            'company_id' => $company_id,
            'file_path' => $newFilePath,
            'size_kb' => $sizeKb,
            'created_by' => Auth::id(),
        ]);

        // Assign default permissions
        $this->syncPermissions($file->id, request()->input('permissions', []));

        // Log file creation
        addUserAction([
            'user_id' => Auth::id(),
            'action' => "File {$file->name} Uploaded Successfully"
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