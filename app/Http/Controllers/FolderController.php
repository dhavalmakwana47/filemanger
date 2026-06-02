<?php

namespace App\Http\Controllers;

use App\Http\Requests\Folder\FolderRequest;
use App\Models\Company;
use App\Models\CompanyRole;
use App\Models\File;
use App\Models\Folder;
use App\Models\RoleFilePermission;
use App\Models\RoleFolderPermission;
use App\Services\FileStorageService;
use App\Services\FolderService;
use App\Services\IndexNumberingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;
use ZipArchive;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\HtmlString;
use App\Models\Setting;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class FolderController extends Controller implements HasMiddleware
{
    protected $fileStorage;
    protected $folderService;

    public static function middleware(): array
    {
        return [
            new Middleware('permission_check:Folder,view', only: ['index', 'show']),
            new Middleware('permission_check:Folder,create', only: ['create', 'store']),
            new Middleware('permission_check:Folder,update', only: ['edit', 'update', 'moveItems']),
            new Middleware('permission_check:Folder,delete', only: ['destroy', 'deleteFolder']),
            new Middleware('permission_check:Company Role,create', only: ['trashData']),

        ];
    }

    public function __construct(FileStorageService $fileStorage, FolderService $folderService)
    {
        $this->fileStorage = $fileStorage;
        $this->folderService = $folderService;
    }

    /**
     * Display a listing of folders.
     */
    public function index()
    {
        $company = Company::find(get_active_company());
        $totalSpace = $company->storage_size_mb ?? 100;
        $usedSpaceMb = round((File::where('company_id', $company->id)->sum('size_kb') / 1024) / 1024, 2);

        return view('app.folder.index', [
            'title' => "Add Folder",
            'assignedPermissions' => [],
            'multiSelect' => auth()->user()->is_master_admin() || auth()->user()->is_super_admin(),
            'folderArr' => Folder::where('company_id', get_active_company())->whereNull('parent_id')->get(),
            'allFolderArr' => Folder::where('company_id', get_active_company())->get(),
            'roleArr' => CompanyRole::whereNot('role_name', 'Super Admin')->where('company_id', get_active_company())->get(),
            'totalSpace' => $totalSpace,
            'usedSpace' => $usedSpaceMb,
            'totalFolders' => Folder::where('company_id', $company->id)->get()->filter(fn($f) => $f->has_access())->count(),
            'totalFiles' => File::where('company_id', $company->id)->get()->filter(fn($f) => $f->hasAccess())->count(),
        ]);
    }

    /**
     * Store a new folder.
     */
    public function store(FolderRequest $request)
    {
        $company_id = get_active_company();
        if (!$company_id) {
            return $this->errorResponse('Active company not found.', 400);
        }

        try {
            // Create folder with custom or auto-generated index
            $customIndex = $request->input('item_index');
            $parentId = $request['parent_id'] ?? null;
            
            // If custom index provided, normalize and use it; otherwise auto-generate
            $itemIndex = $customIndex 
                ? IndexNumberingService::normalizeIndex($customIndex)
                : IndexNumberingService::generateNextIndex($parentId, 'folder');
            
            $folder = Folder::create([
                'name' => $request['name'],
                'parent_id' => $parentId,
                'company_id' => $company_id,
                'item_index' => $itemIndex,
                'created_by' => current_user()->id
            ]);

            // Optional: Handle a single file name if provided
            $fileNamesCreated = [];
            if ($request->has('file_name') && !empty($request->input('file_name'))) {
                $file = File::create([
                    'name' => $request->input('file_name'),
                    'file_name' => $request->input('file_name'),
                    'parent_id' => $parentId,
                    'company_id' => $company_id,
                    'item_index' => IndexNumberingService::generateNextIndex($parentId, 'file', $itemIndex),
                    'created_by' => current_user()->id
                ]);
                $fileNamesCreated[] = $file->file_name;
            }

            $selectedRoles = $request->input('roles', []);
            $request->merge(['permissions' => $selectedRoles]);
            $roles = CompanyRole::whereIn('id', $selectedRoles)->pluck('role_name')->toArray();

            // Sync permissions for folder
            $this->syncPermissions($folder->id, $request->input('permissions', []), Folder::class);

            // Sync permissions for file if created
            if (!empty($fileNamesCreated)) {
                $this->syncPermissions($file->id, $request->input('permissions', []), File::class);
            }

            // Send emails if toggle is enabled
            if (isset($request->send_email)) {
                $this->sendPermissionEmails([$folder->name], $fileNamesCreated, $selectedRoles, $company_id);
            }

            // Log user action
            $resourceNames = array_merge([$folder->name], $fileNamesCreated);
            addUserAction([
                'user_id' => Auth::id(),
                'action' => "Folder " . implode(', ', $resourceNames) . " created with Role Assigned: " . (count($roles) ? implode(', ', $roles) : "'-'")
            ]);

            return $this->successResponse('Folder created successfully!', $folder);
        } catch (\Exception $e) {
            addUserAction([
                'user_id' => Auth::id(),
                'action' => "Error creating resource(s): " . $e->getMessage()
            ]);
            return $this->errorResponse('There was an error creating the folder.', 500, $e);
        }
    }

    /**
     * Show edit form for a folder.
     */
    public function edit(Folder $folder)
    {
        return view('app.folder.update', [
            'title' => "Edit Folder",
            'assignedRoles' => $this->getFolderRoles($folder->id),
            'roleArr' => CompanyRole::whereNot('role_name', 'Super Admin')->where('company_id', get_active_company())->get(),
            'folder' => $folder
        ]);
    }

    /**
     * Update a folder.
     */
    public function update(FolderRequest $request, $id)
    {
        $company_id = get_active_company();
        if (!$company_id) {
            return $this->errorResponse('Active company not found.', 400);
        }

        try {
            $folder = Folder::findOrFail($id);
            $oldIndex = $folder->item_index;
            $newIndex = $request->input('item_index', $oldIndex);
            
            // Normalize the index to remove leading zeros
            $newIndex = IndexNumberingService::normalizeIndex($newIndex);
            
            $folder->update([
                'name' => $request->input('name'),
                'item_index' => $newIndex,
                'updated_by' => current_user()->id
            ]);
            
            // Always update children indexes to ensure proper hierarchy
            // This handles cases where children might have incorrect indexes
            $this->recursivelyUpdateAllChildren($folder->id, $newIndex);

            $selectedRoles = $request->input('roles', []);
            $request->merge(['permissions' => $selectedRoles]);
            $roles = CompanyRole::whereIn('id', $selectedRoles)->pluck('role_name')->toArray();

            // Sync permissions for folder
            $this->syncPermissions($id, $request->input('permissions', []), Folder::class);
            $this->updatePermissions($request, $id); // Assuming this is a custom method

            // Sync permissions for associated files
            foreach ($folder->files as $file) {
                $this->syncFilePermissions($file->id, $request->input('permissions', []));
            }

            // Send emails with folder and file names if toggle is enabled
            if (isset($request->send_email)) {
                $this->sendPermissionEmails([$folder->name], [], $selectedRoles, $company_id);
            }

            addUserAction([
                'user_id' => Auth::id(),
                'action' => "Folder {$folder->name} updated"
            ]);

            return $this->successResponse('Folder updated successfully!', $folder);
        } catch (\Exception $e) {
            addUserAction([
                'user_id' => Auth::id(),
                'action' => "Error updating folder: " . $e->getMessage()
            ]);
            return $this->errorResponse('There was an error updating the folder.', 500, $e);
        }
    }

    public function assignRoles(Request $request)
    {
        $company_id = get_active_company();
        if (!$company_id) {
            return $this->errorResponse('Active company not found.', 400);
        }

        try {
            $fileIds = $request->input('file_ids', []);
            $folderIds = $request->input('folder_ids', []);
            $roles = $request->input('roles', []);
            $userId = Auth::id();

            $folderNames = [];
            $fileNames = [];

            // Assign roles to selected files
            if (!empty($fileIds)) {
                foreach ($fileIds as $fileId) {
                    $file = File::find($fileId);
                    if ($file) {
                        // Sync file permissions/roles
                        $this->syncFilePermissions($file->id, $roles);
                        $fileNames[] = $file->file_name;
                        $roleNames = CompanyRole::whereIn('id', $roles)->pluck('role_name')->toArray();

                        // Log action
                        addUserAction([
                            'user_id' => $userId,
                            'action' => "Roles [" . implode(', ', $roleNames) . "] assigned to File: {$file->file_name}"
                        ]);
                    }
                }
            }

            // Assign roles to selected folders
            if (!empty($folderIds)) {
                foreach ($folderIds as $folderId) {
                    $folder = Folder::find($folderId);
                    if ($folder) {
                        // Sync folder permissions/roles
                        $this->syncPermissions($folder->id, $roles, Folder::class);
                        $request->merge(['permissions' => $roles]);
                        $this->updatePermissions($request, $folder->id);

                        $folderNames[] = $folder->name;

                        // Sync roles to all files inside folder
                        foreach ($folder->files as $file) {
                            $this->syncFilePermissions($file->id, $roles);
                        }
                        $roleNames = CompanyRole::whereIn('id', $roles)->pluck('role_name')->toArray();

                        // Log action
                        addUserAction([
                            'user_id' => $userId,
                            'action' => "Roles [" . implode(', ', $roleNames) . "] assigned to Folder: {$folder->name}"
                        ]);
                    }
                }
            }
            Log::info('Send email: ' . $request->send_email);

            // Send emails if roles were assigned and email toggle is enabled
            if (!empty($roles) && (!empty($folderNames) || !empty($fileNames)) && (isset($request->send_email) && $request->send_email === 1)) {
                $this->sendPermissionEmails($folderNames, $fileNames, $roles, $company_id);
            }

            return response()->json([
                'status' => true,
                'message' => 'Roles assigned successfully!',
            ]);
        } catch (\Exception $e) {
            addUserAction([
                'user_id' => Auth::id(),
                'action' => "Error assigning roles: " . $e->getMessage()
            ]);
            return $this->errorResponse('There was an error assigning roles.', 500, $e);
        }
    }

    /**
     * Update children indexes when parent index changes
     */
    private function updateChildrenIndexes($folderId, $newParentIndex)
    {
        $company_id = get_active_company();
        
        // Normalize the parent index
        $newParentIndex = IndexNumberingService::normalizeIndex($newParentIndex);
        
        // Get all child folders sorted by their current index
        $childFolders = Folder::where('company_id', $company_id)
            ->where('parent_id', $folderId)
            ->orderBy('item_index')
            ->get();
        
        // Get all child files sorted by their current index
        $childFiles = File::where('company_id', $company_id)
            ->where('folder_id', $folderId)
            ->orderBy('item_index')
            ->get();
        
        $childIndex = 1;
        
        // Update child folders first and recursively update their children
        foreach ($childFolders as $childFolder) {
            $newChildIndex = $newParentIndex . '.' . $childIndex;
            $childFolder->update(['item_index' => $newChildIndex]);
            
            // Recursively update all descendants of this folder
            $this->recursivelyUpdateAllChildren($childFolder->id, $newChildIndex);
            $childIndex++;
        }
        
        // Update child files
        foreach ($childFiles as $childFile) {
            $newChildIndex = $newParentIndex . '.' . $childIndex;
            $childFile->update(['item_index' => $newChildIndex]);
            $childIndex++;
        }
    }
    
    /**
     * Recursively update all children at all levels
     */
    private function recursivelyUpdateAllChildren($folderId, $parentIndex)
    {
        $company_id = get_active_company();
        
        // Get all child folders
        $childFolders = Folder::where('company_id', $company_id)
            ->where('parent_id', $folderId)
            ->orderBy('item_index')
            ->get();
        
        // Get all child files
        $childFiles = File::where('company_id', $company_id)
            ->where('folder_id', $folderId)
            ->orderBy('item_index')
            ->get();
        
        $index = 1;
        
        // Process folders first
        foreach ($childFolders as $folder) {
            $newIndex = $parentIndex . '.' . $index;
            $folder->update(['item_index' => $newIndex]);
            
            // Recursively update this folder's children
            $this->recursivelyUpdateAllChildren($folder->id, $newIndex);
            $index++;
        }
        
        // Process files
        foreach ($childFiles as $file) {
            $newIndex = $parentIndex . '.' . $index;
            $file->update(['item_index' => $newIndex]);
            $index++;
        }
    }

    private function getFolderRoles($folderId)
    {
        return RoleFolderPermission::where('folder_id', $folderId)
            ->pluck('company_role_id')
            ->toArray();
    }
    public function updatePermissions($request, $id)
    {
        try {
            $folders = Folder::where('parent_id', $id)->get();
            $permissions = $request->input('permissions', []);

            // Validate permissions if necessary
            if (!is_array($permissions)) {
                throw new \InvalidArgumentException('Permissions must be an array.');
            }

            foreach ($folders as $folder) {
                $this->syncPermissions($folder->id, $permissions);
                $this->updatePermissions($request, $folder->id); // Recursive call
                foreach ($folder->files as $file) {
                    $this->syncFilePermissions($file->id, $permissions);
                }
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to update permissions for folder ID ' . $id . ': ' . $e->getMessage());
            return false; // Or throw the exception depending on requirements
        }
    }
    /**
     * Delete one or multiple folders.
     */
    public function deleteFolder(Request $request)
    {
        try {
            $folderIds = (array) $request->folder_ids;
            $fileIds = (array) $request->file_ids;
            $folders = Folder::whereIn('id', $folderIds)->get(['id', 'name']);
            $files = File::whereIn('id', $fileIds)->get(['id', 'name','file_name']);
            $folderNames = $folders->pluck('name')->toArray();
            $fileNames = $files->pluck('file_name')->toArray();

            $deletedNames = array_merge($folderNames, $fileNames);
            // RoleFolderPermission::whereIn('folder_id', $request->folder_ids)->delete();
            File::whereIn('id', (array) $fileIds)->delete();

            Folder::whereIn('id', (array) $request->folder_ids)->delete();
            // Log the folder deletion action
            $action = count($deletedNames) === 1
                ? "File/Folder {$deletedNames[0]} deleted"
                : "File/Folder " . implode(', ', $deletedNames) . " deleted";

            addUserAction([
                'user_id' => Auth::id(),
                'action' => $action
            ]);
            return $this->successResponse('Folder deleted successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('There was an error deleting the folder.', 500, $e);
        }
    }

    /**
     * Get the folder structure for file manager.
     */
    public function fileManager(Request $request)
    {
        $defaultAccess = current_user()->is_master_admin() || current_user()->is_super_admin();
        $query = $request->query('query', '');
        $fileTree = [];
        $isDownload    = $request->boolean('is_download', false);

        if ($query) {
            // Search mode: Find all files and folders that match the query across the entire structure
            $matchingFolders = Folder::with('access_to_role.companyRole')
                ->where('company_id', get_active_company())
                ->where('name', 'LIKE', '%' . $query . '%')
                ->get();

            $matchingFiles = File::with('access_to_role.companyRole')
                ->where('company_id', get_active_company())
                ->where('file_name', 'LIKE', '%' . $query . '%')
                ->get();

            // Add matching folders to results
            foreach ($matchingFolders as $folder) {
                if ($folder->has_access()) {
                    $fileTree[] = [
                        'id' => $folder->id,
                        'key' => 'folder_' . $folder->id,
                        'parentId' => $folder->parent_id,
                        'name' => $folder->name,
                        'isDirectory' => true,
                        'dateModified' => $folder->created_at,
                        'owner' => $folder->access_to_role->map(function ($rolePermission) {
                            return $rolePermission->companyRole->role_name ?? null;
                        })->filter()->join(', '),
                        'permissions' => $this->formatPermissions($folder, $defaultAccess),
                        'items' => [],
                        'index' => $folder->item_index,
                        'isBookmarked' => $folder->isBookmarkedByCurrentUser(),

                    ];
                }
            }

            // Add matching files to results
            foreach ($matchingFiles as $file) {
                if ($file->hasAccess()) {
                    $fileTree[] = [
                        'id' => $file->id,
                        'parentId' => $file->folder_id,
                        'name' => $file->file_name,
                        'file_name' => $file->name,
                        'isDirectory' => false,
                        'size' => $file->size_kb,
                        'dateModified' => $file->created_at,
                        'owner' => $file->access_to_role->map(function ($rolePermission) {
                            return $rolePermission->companyRole->role_name ?? null;
                        })->filter()->join(', '),
                        'permissions' => $this->formatPermissions($file, $defaultAccess, false),
                        'index' => $file->item_index,
                        'isBookmarked' => $file->isBookmarkedByCurrentUser(),
                    ];
                }
            }
        } else {
            // Normal mode: Show root folders and files
            $folders = Folder::with('files', 'subfolders', 'access_to_role.companyRole')
                ->where('company_id', get_active_company())
                ->whereNull('parent_id')
                ->with(['subfolders', 'files'])
                ->get();

            $files = File::with('access_to_role.companyRole')
                ->where('company_id', get_active_company())
                ->whereNull('folder_id')
                ->get();

            // Build hierarchical structure
            $fileTree = $this->buildFileTree($folders, $defaultAccess);

            // Add root-level files
            foreach ($files as $file) {
                if ($file->hasAccess()) {
                    $fileTree[] = [
                        'id' => $file->id,
                        'parentId' => null,
                        'name' => $file->file_name,
                        'file_name' => $file->name,
                        'isDirectory' => false,
                        'size' => $file->size_kb,
                        'dateModified' => $file->created_at,
                        'owner' => $file->access_to_role->map(function ($rolePermission) {
                            return $rolePermission->companyRole->role_name ?? null;
                        })->filter()->join(', '),
                        'permissions' => $this->formatPermissions($file, $defaultAccess, false),
                        'index' => $file->item_index,
                        'isBookmarked' => $file->isBookmarkedByCurrentUser(),
                    ];
                }
            }
        }

        // === PDF DOWNLOAD ===
        if ($isDownload) {
            $flatTree = $this->flattenTreeForPdf($fileTree);

            $html = view('pdf_tree', compact('flatTree'))->render();

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            // Add page numbers & footer text
            $canvas = $dompdf->getCanvas();
            $canvas->page_text(50, 570, "Generated on " . now()->format('M d, Y \a\t h:i A'), null, 9, [0.5, 0.5, 0.5]);
            $canvas->page_text(720, 570, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, [0.3, 0.3, 0.3]);

            addUserAction([
                'user_id' => Auth::id(),
                'action' => "User (" . auth()->user()->email . ") Directory-Structure Successfully Downloaded"
            ]);

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="Directory-Structure-' . now()->format('Y-m-d') . '.pdf"',
            ]);
        }

        return response()->json($fileTree);
    }

    private function flattenTreeForPdf(array $tree, int $depth = 0): array
    {
        $flat = [];

        foreach ($tree as $node) {
            // --- FORCE EVERYTHING TO STRING ---
            $name = $node['name'] ?? '';
            if (is_array($name)) {
                $name = implode(', ', $name); // fallback
            } elseif (is_object($name) && method_exists($name, '__toString')) {
                $name = (string) $name;
            } else {
                $name = (string) $name;
            }

            $flat[] = [
                'depth'       => $depth,
                'name'        => $name,
                'isDirectory' => !empty($node['isDirectory']),
            ];

            // Recurse into children
            if (!empty($node['items']) && is_array($node['items'])) {
                $flat = array_merge($flat, $this->flattenTreeForPdf($node['items'], $depth + 1));
            }
        }

        return $flat;
    }

    /**
     * Recursively build folder structure with permissions, applying search filter if provided.
     */
    private function countAllFiles($folder): int
    {
        $count = $folder->files->count();
        foreach ($folder->subfolders as $sub) {
            $count += $this->countAllFiles($sub);
        }
        return $count;
    }

    /**
     * Skip OS / app lock & temp files from folder uploads (e.g. Office ~$file.docx).
     */
    private function shouldSkipFolderUploadFile(string $fileName, string $relativePath = ''): bool
    {
        $baseName = basename($fileName);
        $lowerBase = strtolower($baseName);
        $lowerPath = strtolower($relativePath !== '' ? $relativePath : $fileName);

        if (str_starts_with($baseName, '~$') || str_starts_with($baseName, '~')) {
            return true;
        }

        if (str_contains($lowerPath, '.~lock.') || str_starts_with($lowerBase, '.~lock.')) {
            return true;
        }

        if ($lowerBase === '.ds_store' || str_starts_with($lowerBase, '._')) {
            return true;
        }

        $skipNames = ['thumbs.db', 'desktop.ini', '.gitignore'];
        if (in_array($lowerBase, $skipNames, true)) {
            return true;
        }

        $skipExtensions = ['.tmp', '.temp', '.log', '.cache', '.swp', '.swo'];
        foreach ($skipExtensions as $ext) {
            if (str_ends_with($lowerBase, $ext)) {
                return true;
            }
        }

        return false;
    }

    private function buildFileTree($folders, $defaultAccess, $query = '')
    {
        return $folders->filter(function ($folder) use ($query) {
            $matchesQuery = !$query || stripos($folder->name, $query) !== false;
            return ($folder->has_access() || $this->hasAccessibleChild($folder)) && ($matchesQuery || $this->hasChildMatch($folder, $query));
        })
            ->map(function ($folder) use ($defaultAccess, $query) {
                $items = array_merge(
                    $this->buildFileTree($folder->subfolders, $defaultAccess, $query),
                    $this->getPermittedFiles($folder, $defaultAccess, $query)
                );
                $totalFiles = $this->countAllFiles($folder);
                return [
                    'id' => $folder->id,
                    'key' => 'folder_' . $folder->id,
                    'parentId' => $folder->parent_id,
                    'name' => $folder->name . ' (' . $totalFiles . ')',
                    'isDirectory' => true,
                    'dateModified' => $folder->created_at,
                    'owner' => $folder->access_to_role->map(function ($rolePermission) {
                        return $rolePermission->companyRole->role_name ?? null;
                    })->filter()->join(', '),
                    'permissions' => $this->formatPermissions($folder, $defaultAccess),
                    'items' => $items,
                    'index' => $folder->item_index,
                    'isBookmarked' => $folder->isBookmarkedByCurrentUser(),
                ];
            })
            ->values()
            ->all();
    }

    private function hasAccessibleChild($folder, $query = '')
    {
        // Check subfolders
        foreach ($folder->subfolders as $subfolder) {
            $matchesQuery = !$query || stripos($subfolder->name, $query) !== false;
            if ($subfolder->has_access() && $matchesQuery) {
                return true;
            }
            if ($this->hasAccessibleChild($subfolder, $query)) {
                return true;
            }
        }

        // Check files
        foreach ($folder->files as $file) {
            $matchesQuery = !$query || stripos($file->file_name, $query) !== false;
            if ($file->hasAccess() && $matchesQuery) { // Assuming File model has a has_access method
                return true;
            }
        }

        return false;
    }

    /**
     * Check if folder or its children (subfolders or files) match the query or have access.
     */
    private function hasChildMatch($folder, $query = '')
    {
        $hasMatchingSubfolder = $folder->subfolders->contains(function ($sub) use ($query) {
            $matchesQuery = !$query || stripos($sub->name, $query) !== false;
            return $sub->has_access() && ($matchesQuery || $this->hasChildMatch($sub, $query));
        });

        $hasMatchingFile = $folder->files->some(function ($file) use ($query) {
            return $file->hasAccess() && (!$query || stripos($file->file_name, $query) !== false);
        });

        return $hasMatchingSubfolder || $hasMatchingFile;
    }

    /**
     * Get files with access in a given folder, applying search filter if provided.
     */
    private function getPermittedFiles($folder, $defaultAccess, $query = '')
    {
        return $folder->files->filter(function ($file) use ($query) {
            return $file->hasAccess() && (!$query || stripos($file->file_name, $query) !== false);
        })
            ->map(function ($file) use ($defaultAccess) {
                return [
                    'id' => $file->id,
                    'name' => $file->file_name,
                    'file_name' => $file->name,
                    "size" => $file->size_kb,
                    "dateModified" => $file->created_at,
                    'isDirectory' => false,
                    'owner' => $file->access_to_role->map(function ($rolePermission) {
                        return $rolePermission->companyRole->role_name ?? null;
                    })->filter()->join(', '),
                    'permissions' => $this->formatPermissions($file, $defaultAccess, false),
                    'index' => $file->item_index,
                    'isBookmarked' => $file->isBookmarkedByCurrentUser(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Format permissions with default values.
     */
    private function formatPermissions($model, $defaultAccess, $isFolder = true)
    {
        $roleIds = optional($model->access_to_role)->pluck('company_role_id') ?? collect();

        $rawPermissions = auth()->user()
            ->companyRoles()
            ->whereIn('company_role_id', $roleIds) // restrict to these roles
            ->with(['permissions' => function ($query) {
                $query->where('module_name', 'folder');
            }])
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('slug')
            ->unique()
            ->map(function ($name) {
                return strtolower($name); // normalize
            });


        $permissionsMap = collect([
            'can_create' => $rawPermissions->contains('create') ? true : null,
            'can_update' => $rawPermissions->contains('update') ? true : null,
            'can_delete' => $rawPermissions->contains('delete') ? true : null,
            'can_download' => $rawPermissions->contains('download') ? true : null,
            'file_view' => $rawPermissions->contains('file_view') ? true : null,
        ])->mapWithKeys(fn($value, $key) => [$key => $value]);

        $permissions = (object) $permissionsMap->toArray();

        // $permissions = $model->getPermissions();
        if ($isFolder) {
            return [
                'download' => $permissions->can_download ?? $defaultAccess,
                'create' => $permissions->can_create ?? $defaultAccess,
                'update' => $permissions->can_update ?? $defaultAccess,
                'delete' => $permissions->can_delete ?? $defaultAccess,
            ];
        } else {
            return [
                'download' => $permissions->can_download ?? $defaultAccess,
                'update' => $permissions->can_update ?? $defaultAccess,
                'delete' => $permissions->can_delete ?? $defaultAccess,
                'file_view' => $permissions->file_view ?? $defaultAccess, // Using can_create for file view as per original logic
            ];
        }
    }


    /**
     * Sync folder permissions.
     */
    private function syncPermissions($folderId, array $permissions)
    {
        RoleFolderPermission::where('folder_id', $folderId)->delete();

        $rolePermissions = collect($permissions)
            ->map(fn($roleId) => [
                'company_role_id' => $roleId,
                'folder_id' => $folderId,
                // 'can_view' => in_array('can_view', $permissionArray),
                // 'can_create' => in_array('can_create', $permissionArray),
                // 'can_update' => in_array('can_update', $permissionArray),
                // 'can_delete' => in_array('can_delete', $permissionArray),
                'created_at' => now(),
                'updated_at' => now()
            ])
            ->values()
            ->all();

        if (!empty($rolePermissions)) {
            RoleFolderPermission::insert($rolePermissions);
        }
    }

    private function syncFilePermissions($fileId, array $permissions)
    {
        RoleFilePermission::where('file_id', $fileId)->delete();

        $rolePermissions = collect($permissions)
            ->map(fn($roleId) => [
                'company_role_id' => $roleId,
                'file_id' => $fileId,
                // 'can_view' => in_array('can_view', $permissionArray),
                // 'can_download' => in_array('can_download', $permissionArray),
                // 'can_update' => in_array('can_update', $permissionArray),
                // 'can_delete' => in_array('can_delete', $permissionArray),
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        if (!empty($rolePermissions)) {
            RoleFilePermission::insert($rolePermissions);
        }
    }

    public function folderZip(Request $request)
    {
        $dataItem = json_decode($request->input('dataItem'), true);
        $company_id = get_active_company();

        if (!$dataItem || empty($dataItem['name']) || !$company_id) {
            return response()->json(['error' => 'Invalid data item or company ID'], 400);
        }

        // Create zip download record
        $zipDownload = \App\Models\ZipDownload::create([
            'user_id' => auth()->id(),
            'company_id' => $company_id,
            'folder_name' => $dataItem['name'],
            'folder_data' => $dataItem,
            'status' => 'pending'
        ]);

        // Dispatch background job
        \App\Jobs\ProcessZipDownloadJob::dispatch($zipDownload);

        addUserAction([
            'user_id' => auth()->id(),
            'action' => "Folder/File {$dataItem['name']} zip processing started"
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Zip processing started. Redirecting to downloads page...',
            'zip_id' => $zipDownload->id,
            'redirect_url' => route('downloads.index')
        ]);
    }

    public function checkZipStatus($id)
    {
        $zipDownload = \App\Models\ZipDownload::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$zipDownload) {
            return response()->json(['error' => 'Zip download not found'], 404);
        }

        return response()->json([
            'status' => $zipDownload->status,
            'error_message' => $zipDownload->error_message,
            'download_url' => $zipDownload->status === 'completed' ? route('folder.download-zip', $zipDownload->id) : null
        ]);
    }

    public function downloadZip($id)
    {
        $zipDownload = \App\Models\ZipDownload::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->first();

        if (!$zipDownload || !$zipDownload->zip_path) {
            return redirect()->route('downloads.index')->with('error', 'Zip file not found or not ready');
        }

        if (!Storage::disk('s3')->exists($zipDownload->zip_path)) {
            return redirect()->route('downloads.index')->with('error', 'Zip file not found in storage');
        }

        addUserAction([
            'user_id' => auth()->id(),
            'action' => "Folder/File {$zipDownload->folder_name} Successfully Downloaded"
        ]);

        $url = Storage::disk('s3')->temporaryUrl(
            $zipDownload->zip_path,
            now()->addMinutes(15),
            ['ResponseContentDisposition' => 'attachment; filename="' . $zipDownload->folder_name . '.zip"']
        );

        return redirect()->away($url)->with('download_started', true)->with('redirect_to', route('downloads.index'));
    }

    private function addToZip(array $item, string $relativePath, ZipArchive $zip, string $company_id): void
    {
        $name = $item['name'] ?? 'unknown';
        $entryName = $relativePath . $name;
        $isDir = !empty($item['isDirectory']);

        \Log::debug('Processing item', [
            'name' => $name,
            'entryName' => $entryName,
            'isDir' => $isDir,
        ]);

        if ($isDir) {
            if ($entryName !== '') {
                $zip->addEmptyDir($entryName . '/');
                \Log::debug('Added directory to zip', ['entryName' => $entryName]);
            }

            // Process sub-items
            $subItems = $item['items'] ?? [];
            if (empty($subItems)) {
                \Log::warning('Directory has no sub-items', ['entryName' => $entryName]);
            }
            foreach ($subItems as $subItem) {
                $this->addToZip($subItem, $entryName . '/', $zip, $company_id);
            }
        } else {
            $file_name = $item['file_name'] ?? null;
            if (empty($file_name)) {
                \Log::warning('Missing file_name for item', ['item' => $item]);
                return;
            }

            // Construct S3 key
            $s3Key = "uploads/company_{$company_id}/{$file_name}";

            \Log::debug('Checking S3 file', ['s3Key' => $s3Key]);

            // Check if file exists on S3
            try {
                if (Storage::disk('s3')->exists($s3Key)) {
                    $content = Storage::disk('s3')->get($s3Key);
                    if ($content === null) {
                        \Log::warning('S3 file content is null', ['s3Key' => $s3Key]);
                        return;
                    }

                    // Apply watermark if needed
                    $content = $this->applyWatermarkToContent($content, $file_name, $company_id);

                    $zip->addFromString($entryName, $content);
                    \Log::info('Added file to zip', ['s3Key' => $s3Key, 'entryName' => $entryName]);
                } else {
                    \Log::warning('S3 file does not exist', ['s3Key' => $s3Key]);
                }
            } catch (\Exception $e) {
                \Log::error('Error accessing S3 file', [
                    's3Key' => $s3Key,
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }

    // Restore a folder
    public function restoreFolder($id)
    {
        $folder = Folder::onlyTrashed()->findOrFail($id);
        addUserAction([
            'user_id' => Auth::id(),
            'action' => "Folder {$folder->name} restored"
        ]);
        $folder->restore(); // Restore from trash
        return redirect()->route('filemanager.trash.data')->with('success', 'Folder restored successfully.');
    }

    // Restore a file
    public function restoreFile($id)
    {
        $file = File::onlyTrashed()->findOrFail($id);
        $file->restore(); // Restore from trash
        addUserAction([
            'user_id' => Auth::id(),
            'action' => "File {$file->file_name} restored"
        ]);
        return redirect()->route('filemanager.trash.data')->with('success', 'File restored successfully.');
    }

    // Permanently delete a folder
    public function forceDeleteFolder($id)
    {
        $folder = Folder::onlyTrashed()->findOrFail($id);
        addUserAction([
            'user_id' => Auth::id(),
            'action' => "Folder {$folder->name} permanently deleted"
        ]);
        $this->folderService->delete($folder);
        return redirect()->route('filemanager.trash.data')->with('success', 'Folder permanently deleted.');
    }

    // Permanently delete a file
    public function forceDeleteFile($id)
    {
        try {
            DB::beginTransaction();
            $file = File::onlyTrashed()->findOrFail($id);
            $path = "uploads/company_{$file->company_id}/{$file->name}";

            $this->fileStorage->delete($path, 's3');
            addUserAction([
                'user_id' => Auth::id(),
                'action' => "File {$file->file_name} permanently deleted"
            ]);
            $file->forceDelete(); // Permanently delete
            DB::commit();
            return redirect()->route('filemanager.trash.data')->with('success', 'File permanently deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('filemanager.trash.data')->with('error', 'File permanently deleted.');
        }
    }

    public function trashData(Request $request)
    {
        if ($request->ajax()) {
            try {
                $trashedFolders = Folder::where('company_id', get_active_company())->onlyTrashed()->select(['id', 'name', 'created_at']);
                $trashedFiles = File::where('company_id', get_active_company())->onlyTrashed()->select(['id', 'name', 'file_name', 'created_at']);

                // Combine the collections without mapping to arrays
                $data = $trashedFolders->get()->map(function ($folder) {
                    $folder->type = 'Folder';
                    return $folder;
                })->merge($trashedFiles->get()->map(function ($file) {
                    $file->type = 'File';
                    $file->name = $file->file_name;
                    return $file;
                }));

                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('type', function ($item) {
                        return $item->type;
                    })
                    ->editColumn('created_at', function ($item) {
                        return $item->created_at->format('Y-m-d H:i:s');
                    })
                    ->addColumn('action', function ($item) {
                        $restoreRoute = $item->type === 'Folder'
                            ? route('filemanager.folder.restore', $item->id)
                            : route('filemanager.file.restore', $item->id);
                        $deleteRoute = $item->type === 'Folder'
                            ? route('filemanager.folder.forceDelete', $item->id)
                            : route('filemanager.file.forceDelete', $item->id);

                        return '
                        <form action="' . $restoreRoute . '" method="POST" style="display:inline;" onsubmit="return confirmRestore(event, this)">
                            ' . csrf_field() . '
                            <button type="submit" class="btn btn-sm btn-success">Restore</button>
                        </form>
                        <form action="' . $deleteRoute . '" method="POST" style="display:inline;"  onsubmit="return confirmDelete(event, this)">
                            ' . csrf_field() . '
                            ' . method_field('DELETE') . '
                            <button type="submit" class="btn btn-sm btn-danger">Permanently Delete</button>
                        </form>';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            } catch (\Exception $e) {
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }
        }

        return view('app.folder.trash', [
            'title' => 'Trash',
        ]);
    }

    public function getProperties(Request $request)
    {
        if (isset($request->file_id)) {
            $file = File::find($request->file_id);
            $sizeInMB = ($file->size_kb / 1024) / 1024; // Convert KB to MB

            if ($sizeInMB < 1) {
                // Show in KB if less than 1 MB
                $data['size'] = $file->size_kb . ' KB';
            } else {
                // Show in MB if 1 MB or more
                $data['size'] = round($sizeInMB, 2) . ' MB';
            }


            $data['name'] = $file->filname_name;
            $data['dateModified'] = $file->created_at->format('Y-m-d H:i:s');
            $data['owner'] = $file->access_to_role->map(function ($rolePermission) {
                return $rolePermission->companyRole->role_name ?? null;
            })->filter()->join(', ');
            $data['created_by'] = $file->createdBy->name ?? 'N/A';
            return view('app.folder.partials.file_properties', $data)->with('success', true);
        }
        if (isset($request->folder_id)) {
            $folder = Folder::find($request->folder_id);
            $data['name'] = $folder->name;
            $data['itemCount'] = $folder->files->count() + $folder->subfolders->count();
            $data['dateModified'] = $folder->created_at->format('Y-m-d H:i:s');
            $data['owner'] = $folder->access_to_role->map(function ($rolePermission) {
                return $rolePermission->companyRole->role_name ?? null;
            })->filter()->join(', ');
            $data['created_by'] = $folder->createdBy->name ?? 'N/A';
            return view('app.folder.partials.file_properties', $data)->with('success', true);
        }
        return response()->json(['success' => false, 'message' => 'Invalid request.'], 400);
    }

    public function bulkDelete(Request $request)
    {
        try {
            $items = $request->input('items', []);

            foreach ($items as $item) {
                if ($item['type'] === 'Folder') {
                    $folder = Folder::onlyTrashed()->where('id', $item['id'])->first();
                    $this->folderService->delete($folder);
                } else {
                    $file = File::onlyTrashed()->where('id', $item['id'])->first();
                    if ($file) {
                        $path = "uploads/company_{$file->company_id}/{$file->name}";
                        $this->fileStorage->delete($path, 's3');
                        $file->forceDelete();
                    }
                }
            }

            return response()->json(['message' => 'Selected items deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
    public function uploadFolderStructure(Request $request)
    {
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

            // Archives
            'application/zip',
            'application/x-zip-compressed',
            'application/x-rar-compressed',
            'application/vnd.rar',
            'application/x-7z-compressed'
        ];

        if ($request->has('item_index')) {
            $request->merge([
                'item_index' => trim((string) $request->input('item_index', '')),
            ]);
        }

        $request->validate([
            'file_paths.*' => 'string',
            'item_index' => ['nullable', 'string', 'max:40', 'regex:/^$|^[0-9]+(\.[0-9]+)*$/'],
            'folder_id' => 'nullable|exists:folders,id',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:company_roles,id',
        ]);

        $company_id = get_active_company();
        if (!$company_id) {
            return $this->errorResponse('Active company not found.', 400);
        }

        try {
            // Log skipped/invalid files on first batch only (outside transaction)
            if ((int) $request->input('batch_index', 0) === 0) {
                $skippedFiles = json_decode($request->input('skipped_files', '[]'), true) ?? [];
                $invalidFiles = json_decode($request->input('invalid_files', '[]'), true) ?? [];

                if (!empty($skippedFiles)) {
                    addUserAction([
                        'user_id' => Auth::id(),
                        'action' => 'Folder Upload - Skipped system files (' . count($skippedFiles) . '): ' . implode(', ', $skippedFiles)
                    ]);
                }

                if (!empty($invalidFiles)) {
                    addUserAction([
                        'user_id' => Auth::id(),
                        'action' => 'Folder Upload - Skipped invalid file types (' . count($invalidFiles) . '): ' . implode(', ', $invalidFiles)
                    ]);
                }
            }

        $rawItemIndex = $request->input('item_index');
        $customParentIndex = ($rawItemIndex !== null && $rawItemIndex !== '')
            ? IndexNumberingService::normalizeIndex(trim((string) $rawItemIndex))
            : null;
        if ($customParentIndex === '') {
            $customParentIndex = null;
        }

        return DB::transaction(function () use ($request, $allowedMimeTypes, $company_id, $customParentIndex) {
            $files = $request->file('files');
            $file_paths = $request->input('file_paths');
            $root_folder_id = $request->input('folder_id') ?: null;
            $created_by = current_user()->id;
            $selectedRoles = array_filter($request->input('roles', []), fn($value) => !empty($value));

            if (!$files || !$file_paths || count($files) !== count($file_paths)) {
                Log::error('Invalid or mismatched files and paths', [
                    'files_count' => count($files),
                    'file_paths_count' => count($file_paths),
                ]);
                return $this->errorResponse('Invalid or mismatched files and paths.', 400);
            }

            // Check storage space
            $totalSizeBytes = 0;
            foreach ($files as $file) {
                $totalSizeBytes += $file->getSize();
            }
            if ($totalSizeBytes / 1024 > getTotalUsedSpace()) {
                return $this->errorResponse('Not enough storage space available.', 400);
            }

            $folderMap = [];
            $fileNamesCreated = [];
            $folderNamesCreated = [];
            $validFilesProcessed = false;

            foreach ($file_paths as $index => $relativePath) {
                if (!isset($files[$index]) || !$files[$index]->isValid()) {
                    continue;
                }

                $pathParts = explode('/', trim($relativePath, '/'));
                $fileName = array_pop($pathParts); // Last part is the file

                if ($this->shouldSkipFolderUploadFile($fileName, $relativePath)) {
                    continue;
                }

                // Skip files with invalid MIME types
                $file = $files[$index];
                if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                    continue;
                }
                $currentParentId = $root_folder_id; // Start with the provided folder_id
                $hasNestedFolders = count($pathParts) > 0;

                // Create folder hierarchy
                $currentPath = '';
                foreach ($pathParts as $folderIndex => $folderName) {
                    $currentPath .= $folderName . '/';

                    // Unique key for folderMap to avoid conflicts
                    $mapKey = $currentParentId . '|' . $currentPath;

                    if (!isset($folderMap[$mapKey])) {
                        // Check if folder exists
                        $folder = Folder::where('name', $folderName)
                            ->where('parent_id', $currentParentId)
                            ->where('company_id', $company_id)
                            ->first();

                        if (!$folder) {
                            // Only the first path segment under the upload target may use the user's base index;
                            // deeper segments must use the real parent's item_index (child-of-child: 1.1.1, not 1.2).
                            $indexCustomForFolder = ($folderIndex === 0) ? $customParentIndex : null;
                            $folder = Folder::create([
                                'name' => $folderName,
                                'parent_id' => $currentParentId,
                                'company_id' => $company_id,
                                'item_index' => IndexNumberingService::generateNextIndex($currentParentId, 'folder', $indexCustomForFolder),
                                'created_by' => $created_by,
                                'updated_by' => $created_by,
                            ]);
                            $folderNamesCreated[] = $folder->name;

                            // Sync permissions for folder
                            if (!empty($selectedRoles)) {
                                $request->merge(['permissions' => $selectedRoles]);
                                $this->syncPermissions($folder->id, $selectedRoles, Folder::class);
                            }
                        }

                        $folderMap[$mapKey] = $folder->id;
                        $currentParentId = $folder->id;
                    } else {
                        $currentParentId = $folderMap[$mapKey];
                    }
                }

                // Store file
                $file = $files[$index];
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $uniqueFileName = $originalName . '_' . time() . '_' . $index . '.' . $extension; // Ensure unique filename
                $filePath = "uploads/company_{$company_id}";
                $this->fileStorage->store($file, $filePath, $uniqueFileName);
                $sizeKb = $file->getSize();

                // File directly in the target folder: optional user base index; file inside uploaded subfolders: use that folder's index chain only.
                $indexCustomForFile = $hasNestedFolders ? null : $customParentIndex;

                $fileRecord = File::create([
                    'name' => $uniqueFileName,
                    'file_name' => $originalName . '.' . $extension,
                    'folder_id' => $currentParentId,
                    'company_id' => $company_id,
                    'file_path' => $filePath . '/' . $uniqueFileName,
                    'item_index' => IndexNumberingService::generateNextIndex($currentParentId, 'file', $indexCustomForFile),
                    'created_by' => $created_by,
                    'updated_by' => $created_by,
                    'size_kb' => $sizeKb,
                ]);

                $fileNamesCreated[] = $fileRecord->file_name;
                $validFilesProcessed = true;

                // Sync permissions for file
                if (!empty($selectedRoles)) {
                    $request->merge(['permissions' => $selectedRoles]);
                    $this->syncFilePermissions($fileRecord->id, $selectedRoles, File::class);
                }
            }

            if (!$validFilesProcessed) {
                return $this->errorResponse('No valid files were uploaded. Only PNG, JPEG, GIF, PDF, Word, ZIP, CSV, and Excel files are allowed.', 400);
            }

            // Send emails if toggle is enabled
            if (!empty($selectedRoles) && (isset($request->send_email) && (int) $request->send_email === 1)) {
                $this->sendPermissionEmails($folderNamesCreated, $fileNamesCreated, $selectedRoles, $company_id);
            }

            // Log action
            $resourceNames = array_merge($folderNamesCreated, $fileNamesCreated);
            $roles = CompanyRole::whereIn('id', $selectedRoles)->pluck('role_name')->toArray();

            addUserAction([
                'user_id' => Auth::id(),
                'action' => "Folders " . implode(', ', $resourceNames) . " Uploaded Successfully with Role Assigned: " . (count($roles) ? implode(', ', $roles) : "'-'")
            ]);

            return $this->successResponse('Folder structure uploaded successfully!', []);
        });
        } catch (\Exception $e) {
            Log::error('Error creating folder structure: ' . $e->getMessage());
            // Log the error
            addUserAction([
                'user_id' => Auth::id(),
                'action' => "Error creating folder structure: " . $e->getMessage()
            ]);
            return $this->errorResponse('There was an error uploading the folder structure.', 500, $e);
        }
    }

    public function moveItems(Request $request)
    {
        try {
            $fileIds = $request->input('file_ids', []);
            $folderIds = $request->input('folder_ids', []);
            $destinationFolderId = $request->input('destination_folder_id');

            $movedItems = [];

            // Move files
            if (!empty($fileIds)) {
                File::whereIn('id', $fileIds)->update(['folder_id' => $destinationFolderId]);
                $movedItems = array_merge($movedItems, File::whereIn('id', $fileIds)->pluck('file_name')->toArray());
            }

            // Move folders
            if (!empty($folderIds)) {
                Folder::whereIn('id', $folderIds)->update(['parent_id' => $destinationFolderId]);
                $movedItems = array_merge($movedItems, Folder::whereIn('id', $folderIds)->pluck('name')->toArray());
            }

            addUserAction([
                'user_id' => Auth::id(),
                'action' => 'Items moved: ' . implode(', ', $movedItems)
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Items moved successfully!',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error moving items.', 500, $e);
        }
    }

    private function applyWatermarkToContent($content, $fileName, $company_id)
    {
        // Get watermark settings
        $setting = Setting::where('company_id', $company_id)->first();
        if (!$setting || !$setting->enable_watermark) {
            return $content; // No watermark needed
        }

        $user = auth()->user();
        if ($user->is_master_admin() || $user->is_super_admin()) {
            return $content; // No watermark for admins
        }

        $userEmail = $user?->email ?? 'unknown@domain.com';
        $downloadDate = now()->format('Y-m-d H:i');
        $textWatermark = "$userEmail | $downloadDate";
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        try {
            // Apply watermark to images
            if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
                $manager = new ImageManager(new Driver());
                $image = $manager->read($content);

                $width = $image->width();
                $height = $image->height();
                $fontSize = min($width, $height) / 3;

                // Cross diagonal watermarks
                $image->text($textWatermark, $width / 2, $height / 2, function ($font) use ($fontSize) {
                    $font->size($fontSize);
                    $font->color('#CCCCCC80');
                    $font->align('center');
                    $font->valign('middle');
                    $font->angle(45);
                });

                $image->text($textWatermark, $width / 2, $height / 2, function ($font) use ($fontSize) {
                    $font->size($fontSize);
                    $font->color('#CCCCCC80');
                    $font->align('center');
                    $font->valign('middle');
                    $font->angle(-45);
                });

                return $ext === 'png' ? $image->toPng()->toString() : $image->toJpeg(90)->toString();
            }
            // Apply watermark to PDFs
            elseif ($ext === 'pdf') {
                $pdf = new \setasign\Fpdi\Fpdi();
                $pdf->SetAutoPageBreak(false);

                $pageCount = $pdf->setSourceFile(
                    \setasign\Fpdi\PdfParser\StreamReader::createByString($content)
                );

                for ($i = 1; $i <= $pageCount; $i++) {
                    $templateId = $pdf->importPage($i);
                    $size = $pdf->getTemplateSize($templateId);

                    if ($size['width'] > 0 && $size['height'] > 0) {
                        $pdf->AddPage(
                            $size['orientation'] === 'L' ? 'L' : 'P',
                            [$size['width'], $size['height']]
                        );

                        $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height'], true);
                        $pdf->SetFont('Helvetica', '', 24);
                        $pdf->SetTextColor(150, 150, 150);

                        $diagonal = sqrt($size['width'] * $size['width'] + $size['height'] * $size['height']);
                        $angle = atan2($size['height'], $size['width']);

                        $pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm', cos($angle), sin($angle), -sin($angle), cos($angle), 0, $size['height']));
                        $pdf->SetXY(0, -8);
                        $pdf->Cell($diagonal, 16, $textWatermark, 0, 0, 'C');
                        $pdf->_out('Q');
                    }
                }

                return $pdf->Output('S');
            }
        } catch (\Exception $e) {
            \Log::error('Watermark application failed in zip: ' . $e->getMessage());
            // Return original content if watermark fails
        }

        return $content; // Return original for other file types or on error
    }
}
