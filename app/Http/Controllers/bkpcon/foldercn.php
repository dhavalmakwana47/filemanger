<?php

namespace App\Http\Controllers;

use App\Http\Requests\Folder\FolderRequest;
use App\Models\Company;
use App\Models\CompanyRole;
use App\Models\File;
use App\Models\Folder;
use App\Models\RoleFilePermission;
use App\Models\RoleFolderPermission;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;
use ZipArchive;

class FolderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission_check:Folder,view', only: ['index', 'show']),
            new Middleware('permission_check:Folder,create', only: ['create', 'store']),
            new Middleware('permission_check:Folder,update', only: ['edit', 'update']),
            new Middleware('permission_check:Folder,delete', only: ['destroy', 'deleteFolder']),
            new Middleware('permission_check:Company Role,create', only: ['trashData']),

        ];
    }

    /**
     * Display a listing of folders.
     */
    public function index()
    {
        return view('app.folder.index', [
            'title' => "Add Folder",
            'assignedPermissions' => [],
            'folderArr' => Folder::where('company_id', get_active_company())->whereNull('parent_id')->get(),
            'allFolderArr' => Folder::where('company_id', get_active_company())->get(),
            'roleArr' => CompanyRole::whereNot('role_name', 'Super Admin')->where('company_id', get_active_company())->get(),
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
            // Create folder
            $folder = Folder::create([
                'name' => $request['name'],
                'parent_id' => $request['parent_id'] ?? null,
                'company_id' => $company_id,
                'item_index' => $request->item_index ?? 0,
                'created_by' => current_user()->id
            ]);

            // Optional: Handle a single file name if provided
            $fileNamesCreated = [];
            if ($request->has('file_name') && !empty($request->input('file_name'))) {
                $file = File::create([
                    'name' => $request->input('file_name'),
                    'parent_id' => $request['parent_id'] ?? null,
                    'company_id' => $company_id,
                    'item_index' => $request->item_index ?? 0,
                    'created_by' => current_user()->id
                ]);
                $fileNamesCreated[] = $file->name;
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

            // Send emails
            $this->sendPermissionEmails([$folder->name], $fileNamesCreated, $selectedRoles, $company_id);

            // Log user action
            $resourceNames = array_merge([$folder->name], $fileNamesCreated);
            addUserAction([
                'user_id' => Auth::id(),
                'action' => "Resource(s) " . implode(', ', $resourceNames) . " created with Role Assigned: " . implode(', ', $roles)
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
            $folder->update([
                'name' => $request->input('name'),
                'item_index' => $request->item_index ?? 0,
                'updated_by' => current_user()->id
            ]);

            $selectedRoles = $request->input('roles', []);
            $request->merge(['permissions' => $selectedRoles]);
            $roles = CompanyRole::whereIn('id', $selectedRoles)->pluck('role_name')->toArray();

            // Sync permissions for folder
            $this->syncPermissions($id, $request->input('permissions', []), Folder::class);
            $this->updatePermissions($request, $id); // Assuming this is a custom method

            // Sync permissions for associated files
            $fileNames = $folder->files->pluck('name')->toArray();
            foreach ($folder->files as $file) {
                $this->syncFilePermissions($file->id, $request->input('permissions', []));
            }

            // Send emails with folder and file names
            $this->sendPermissionEmails([$folder->name], $fileNames, $selectedRoles, $company_id);

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
                        $fileNames[] = $file->name;

                        // Log action
                        addUserAction([
                            'user_id' => $userId,
                            'action' => "Roles [" . implode(', ', $roles) . "] assigned to File: {$file->name}"
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
                        $folderNames[] = $folder->name;

                        // Sync roles to all files inside folder
                        foreach ($folder->files as $file) {
                            $this->syncFilePermissions($file->id, $roles);
                            if (!in_array($file->name, $fileNames)) {
                                $fileNames[] = $file->name;
                            }
                        }

                        // Log action
                        addUserAction([
                            'user_id' => $userId,
                            'action' => "Roles [" . implode(', ', $roles) . "] assigned to Folder: {$folder->name}"
                        ]);
                    }
                }
            }

            // Send emails if roles were assigned
            if (!empty($roles) && (!empty($folderNames) || !empty($fileNames))) {
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
            $files = File::whereIn('id', $fileIds)->get(['id', 'name']);
            $folderNames = $folders->pluck('name')->toArray();
            $fileNames = $files->pluck('name')->toArray();

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

        // Fetch all root-level folders to check for matching subfolders or files
        $folders = Folder::with('files', 'subfolders', 'access_to_role.companyRole')
            ->where('company_id', get_active_company())
            ->whereNull('parent_id')
            ->with(['subfolders', 'files'])
            ->get();

        // Fetch root-level files (files without a folder), applying search filter if query exists
        $filesQuery = File::with('access_to_role.companyRole')
            ->where('company_id', get_active_company())
            ->whereNull('folder_id');

        if ($query) {
            $filesQuery->where('name', 'LIKE', '%' . $query . '%');
        }

        $files = $filesQuery->get();

        // Build hierarchical structure with search query
        $fileTree = $this->buildFileTree($folders, $defaultAccess, $query);

        // Merge root-level files into the structured tree
        foreach ($files as $file) {
            $ownerRoles = $file->access_to_role->map(function ($rolePermission) {
                return $rolePermission->companyRole->role_name ?? null;
            })->filter()->join(', ');
            if ($file->hasAccess()) {
                $fileTree[] = [
                    'id' => $file->id,
                    'parentId' => null,
                    'name' => $file->name,
                    'isDirectory' => false,
                    "size" => $file->size_kb,
                    "dateModified" => $file->created_at,
                    'owner' => $ownerRoles,
                    'permissions' => $this->formatPermissions($file, $defaultAccess, false),
                ];
            }
        }

        return response()->json($fileTree);
    }

    /**
     * Recursively build folder structure with permissions, applying search filter if provided.
     */
    private function buildFileTree($folders, $defaultAccess, $query = '')
    {
        return $folders->filter(function ($folder) use ($query) {
            // Include folder if it has access and either matches the query or has matching children
            $matchesQuery = !$query || stripos($folder->name, $query) !== false;
            return $folder->has_access() && ($matchesQuery || $this->hasChildMatch($folder, $query));
        })
            ->map(function ($folder) use ($defaultAccess, $query) {
                return [
                    'id' => $folder->id,
                    'parentId' => $folder->parent_id,
                    'name' => $folder->name,
                    'isDirectory' => true,
                    "dateModified" => $folder->created_at,
                    'owner' => $folder->access_to_role->map(function ($rolePermission) {
                        return $rolePermission->companyRole->role_name ?? null;
                    })->filter()->join(', '),
                    'permissions' => $this->formatPermissions($folder, $defaultAccess),
                    'items' => array_merge(
                        $this->buildFileTree($folder->subfolders, $defaultAccess, $query),
                        $this->getPermittedFiles($folder, $defaultAccess, $query)
                    ),
                ];
            })
            ->values()
            ->all();
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
            return $file->hasAccess() && (!$query || stripos($file->name, $query) !== false);
        });

        return $hasMatchingSubfolder || $hasMatchingFile;
    }

    /**
     * Get files with access in a given folder, applying search filter if provided.
     */
    private function getPermittedFiles($folder, $defaultAccess, $query = '')
    {
        return $folder->files->filter(function ($file) use ($query) {
            return $file->hasAccess() && (!$query || stripos($file->name, $query) !== false);
        })
            ->map(function ($file) use ($defaultAccess) {
                return [
                    'id' => $file->id,
                    'name' => $file->name,
                    "size" => $file->size_kb,
                    "dateModified" => $file->created_at,
                    'isDirectory' => false,
                    'owner' => $file->access_to_role->map(function ($rolePermission) {
                        return $rolePermission->companyRole->role_name ?? null;
                    })->filter()->join(', '),
                    'permissions' => $this->formatPermissions($file, $defaultAccess, false),
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
        // Validate and decode the dataItem
        $dataItem = json_decode($request->input('dataItem'), true);
        $company_id = get_active_company(); // Assuming company_id is passed in the request

        if (!$dataItem || empty($dataItem['name']) || !$company_id) {
            return response()->json(['error' => 'Invalid data item or company ID'], 400);
        }

        // Create a temporary zip file
        $zip = new ZipArchive();
        $zipFileName = tempnam(sys_get_temp_dir(), 'zip_');

        if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response()->json(['error' => 'Could not create zip file'], 500);
        }

        // Add files and folders to the zip
        $this->addToZip($dataItem, '', $zip, $company_id);
        addUserAction([
            'user_id' => Auth::id(),
            'action' => "Folder/File {$dataItem['name']} downloaded as ZIP"
        ]);

        // Close the zip file
        $zip->close();

        // Return the zip file as a download response
        return response()->download($zipFileName, $dataItem['name'] . '.zip', ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    private function addToZip(array $item, string $relativePath, ZipArchive $zip, string $company_id): void
    {
        $name = $item['name'] ?? '';
        $entryName = $relativePath . $name;
        $isDir = !empty($item['isDirectory']);

        if ($isDir) {
            // Add directory to zip (skip empty root directory name if applicable)
            if ($entryName !== '') {
                $zip->addEmptyDir($entryName);
            }
            // Recursively add sub-items
            foreach ($item['items'] ?? [] as $subItem) {
                $this->addToZip($subItem, $entryName . '/', $zip, $company_id);
            }
        } else {
            // Construct the physical file path based on your storage logic
            $filePath = "uploads/company_{$company_id}/{$name}";
            $physicalPath = Storage::path($filePath);

            // Check if the file exists and add it to the zip
            if (Storage::exists($filePath)) {
                $zip->addFile($physicalPath, $entryName);
            } else {
                // Optionally log missing files or handle the error
                \Log::warning("File not found: {$filePath}");
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
            'action' => "File {$file->name} restored"
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
        $folder->forceDelete(); // Permanently delete
        return redirect()->route('filemanager.trash.data')->with('success', 'Folder permanently deleted.');
    }

    // Permanently delete a file
    public function forceDeleteFile($id)
    {
        $file = File::onlyTrashed()->findOrFail($id);
        addUserAction([
            'user_id' => Auth::id(),
            'action' => "File {$file->name} permanently deleted"
        ]);
        $file->forceDelete(); // Permanently delete
        return redirect()->route('filemanager.trash.data')->with('success', 'File permanently deleted.');
    }

    public function trashData(Request $request)
    {
        if ($request->ajax()) {
            try {
                $trashedFolders = Folder::where('company_id', get_active_company())->onlyTrashed()->select(['id', 'name', 'created_at']);
                $trashedFiles = File::where('company_id', get_active_company())->onlyTrashed()->select(['id', 'name', 'created_at']);

                // Combine the collections without mapping to arrays
                $data = $trashedFolders->get()->map(function ($folder) {
                    $folder->type = 'Folder';
                    return $folder;
                })->merge($trashedFiles->get()->map(function ($file) {
                    $file->type = 'File';
                    return $file;
                }));

                return DataTables::of($data)
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
}
