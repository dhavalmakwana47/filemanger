<?php

namespace App\Services;

use App\Models\File;
use App\Models\Folder;
use App\Services\FileStorageService;
use Illuminate\Support\Facades\Auth;

class FolderService
{
    public $fileStorage;
    public function __construct(FileStorageService $fileStorage)
    {
        $this->fileStorage = $fileStorage;
    }

    public function delete(Folder $folder)
    {
        foreach($folder->subfolders as $subfolder){
            $this->delete($subfolder);
        }
        $this->deleteFile($folder);
        $folder->forceDelete();
    }

    public function deleteFile(Folder $folder)
    {
        foreach($folder->files as $file){
            $path = "uploads/company_{$file->company_id}/{$file->name}";

            $this->fileStorage->delete($path, 's3');
            $file->forceDelete(); // Permanently delete
        }
    }
}