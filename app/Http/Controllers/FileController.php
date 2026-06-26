<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyRole;
use App\Models\File;
use App\Models\Folder;
use App\Models\RoleFilePermission;
use App\Models\Setting;
use App\Services\FileStorageService;
use App\Services\FileViewer;
use App\Services\IndexNumberingService;
use App\Services\WatermarkService;
use App\Services\ZipExtarctService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    protected $fileStorage;
    private $zipExtarctService;

    public function __construct(
        FileStorageService $fileStorage,
        ZipExtarctService $zipExtarctService,
        private readonly WatermarkService $watermarkService,
    ) {
        $this->fileStorage = $fileStorage;
        $this->zipExtarctService = $zipExtarctService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $company_id = get_active_company();
        if (!$company_id) {
            return $this->errorResponse('Active company not found.', 400);
        }

        try {
            // Log invalid files on first batch only
            if ((int) $request->input('batch_index', 0) === 0) {
                $invalidFiles = json_decode($request->input('invalid_files', '[]'), true) ?? [];
                if (!empty($invalidFiles)) {
                    addUserAction([
                        'user_id' => Auth::id(),
                        'action' => 'File Upload - Skipped invalid file types (' . count($invalidFiles) . '): ' . implode(', ', $invalidFiles)
                    ]);
                }
            }

            $filesData = [];
            $fileNames = [];

            // Check if files are uploaded
            if ($request->hasFile('file')) {
                $files = $request->file('file');

                // Ensure files is an array
                $files = is_array($files) ? $files : [$files];

                // Calculate total size in bytes
                $totalSizeBytes = 0;
                foreach ($files as $file) {
                    $totalSizeBytes += $file->getSize();  // getSize() returns size in bytes
                }

                if ($totalSizeBytes / 1024 > getTotalUsedSpace()) {
                    return $this->errorResponse('Not enough storage space available.', 200);
                }

                foreach ($files as $file) {
                    // Get original file name without extension
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                    // Get file extension
                    $extension = $file->getClientOriginalExtension();

                    // Generate filename: originalName_timestamp.extension
                    $fileName = $originalName . '_' . time() . '.' . $extension;

                    $filePath = 'uploads/company_' . $company_id;
                    $sizeKb = $file->getSize();

                    // Store the file in the storage/app/uploads/company_{$company_id} directory
                    // $file->storeAs($filePath, $fileName, 'public');
                    $this->fileStorage->store($file, $filePath, $fileName);

                    // Save file info in the database with custom or auto-generated index
                    $folderId = $request->folder_id ?? null;
                    $customIndex = $request->input('item_index');
                    
                    $itemIndex = $customIndex 
                        ? IndexNumberingService::normalizeIndex($customIndex)
                        : IndexNumberingService::generateNextIndex($folderId, 'file');
                    
                    $folder = File::create([
                        'name' => $fileName,
                        'file_name' => $originalName . '.' . $extension,
                        'folder_id' => $folderId,
                        'company_id' => $company_id,
                        'file_path' => $filePath . '/' . $fileName,
                        'item_index' => $itemIndex,
                        'created_by' => current_user()->id,
                        'size_kb' => $sizeKb,
                    ]);

                    $filesData[] = $folder;
                    $fileNames[] = $folder->file_name;

                    $roles = CompanyRole::whereIn('id', $request->input('roles', []))->pluck('role_name')->toArray();
                    // Log the file upload action
                    addUserAction([
                        'user_id' => Auth::id(),
                        'action' => "File {$folder->file_name} Uploaded Successfully with Role Assigned: " . (count($roles) ? implode(', ', $roles) : "'-'")
                    ]);

                    // Sync permissions for each file
                    $selectedRoles = array_filter($request->input('roles', []), function ($value) {
                        return !empty($value);
                    });

                    if (isset($selectedRoles) && count($selectedRoles) > 0) {
                        $request->merge(['permissions' => $selectedRoles]);
                        $this->syncPermissions($folder->id, $request->input('permissions', []));
                    }
                }

                // Send emails if roles were assigned and email toggle is enabled
                if (!empty($selectedRoles) && isset($request->send_email)) {
                    $this->sendPermissionEmails([], $fileNames, $selectedRoles, $company_id);
                }

                return $this->successResponse('Files created successfully!', $filesData);
            } else {
                return $this->errorResponse('No files uploaded.', 400);
            }
        } catch (\Exception $e) {
            // Log the full error message and stack trace
            addUserAction([
                'user_id' => Auth::id(),
                'action' => "Error creating files: " . $e->getMessage()
            ]);

            return $this->errorResponse('There was an error creating the files.', 500, $e);
        }
    }
    // Method to stream the file content
    public function viewFile(Request $request, $id)
    {
        $file = File::find($id);

        if (!$file || !$file->checkAccess('Folder', 'file_view')) {
            return redirect()->route('dashboard')->with('error', 'File not found.');
        }

        $value =  "uploads/company_" . get_active_company() . "/" . $file->name;
        // Log the file viewing action
        addUserAction([
            'user_id' => Auth::id(),
            'action' => "File {$file->file_name} viewed"
        ]);
        return  FileViewer::viewFile($value);
    }
    // Method to download the file
    public function downloadFile(Request $request)
    {

        // 1. Find file or 404
        $file = File::findOrFail($request->id);

        // 2. Access check
        if (!$file || !$file->checkAccess('Folder', 'download')) {
            abort(404, 'File not found.');
        }

        // 3. Build storage path
        $path = "uploads/company_" . get_active_company() . "/" . $file->name;
        $disk = $this->fileStorage->disk;

        // 4. Check file exists
        if (! Storage::disk($disk)->exists($path)) {
            abort(404, 'File not found.');
        }

        // 5. Get settings & user
        $setting = Setting::where('company_id', get_active_company())->first();
        if (!$setting) {
            $setting = Setting::create([
                'company_id' => get_active_company(),
                'watermark_image' => null,
                'ip_restriction' => null,
                'enable_watermark' => false,
            ]);
        }

        $user = auth()->user();

        // If watermark disabled or admin → direct download
        if (! $this->watermarkService->shouldApply($setting, $user)) {
            addUserAction([
                'user_id' => Auth::id(),
                'action'  => "File {$file->file_name} downloaded"
            ]);

            return Storage::disk($disk)->download($path, $file->name);
        }

        // Load file info
        $contents = Storage::disk($disk)->get($path);
        if ($contents === null) {
            abort(404, 'File not found.');
        }

        $mime     = Storage::disk($disk)->mimeType($path);
        $ext      = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));
        $textWatermark = $this->watermarkService->buildWatermarkText($user);

        try {
            $watermarked = $this->watermarkService->applyToContent($contents, $file->name, $textWatermark);

            if ($watermarked !== $contents) {
                $contents = $watermarked;
                $mime = match ($ext) {
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'pdf' => 'application/pdf',
                    default => $mime,
                };
            }
        } catch (\Exception $e) {
            \Log::error('Watermark application failed: ' . $e->getMessage());

            // On error, serve original file
            addUserAction([
                'user_id' => Auth::id(),
                'action'  => "File {$file->file_name} downloaded (watermark failed)"
            ]);

            return Storage::disk($disk)->download($path, $file->name);
        }
        addUserAction([
            'user_id' => Auth::id(),
            'action'  => "File {$file->file_name} downloaded with text watermark"
        ]);

        // Return watermarked file
        return response($contents, 200)
            ->header('Content-Type', $mime)
            ->header('Content-Disposition', 'attachment; filename="' . $file->name . '"');
    }
    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $routeName = 'file.view';
        $file = File::find($request->id);
        if (!$file || !$file->checkAccess('Folder', 'file_view')) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'The requested file is not available. Please try again later.');
        }

        if (isset($file)) {
            $fileName = $file->name;
            $value =  "uploads/company_" . get_active_company() . "/" . $file->name;

            $storageType = config('filesystems.default') == 's3' ? 's3' : 'public';
            $mimeType = Storage::disk($storageType)->mimeType($value);
            $id = $file->id;
            return view('fileviewer.index', compact('id', 'fileName', 'mimeType', 'routeName', 'value'));
        }
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(File $file)
    {
        return view('app.folder.updatefile', [
            'title' => "Edit File",
            'assignedRoles' => $this->getFileRoles($file->id),
            'roleArr' => CompanyRole::whereNot('role_name', 'Super Admin')->where('company_id', get_active_company())->get(),
            'file' => $file
        ]);
    }

    private function getFileRoles($fileId)
    {
        return RoleFilePermission::where('file_id', $fileId)
            ->get()
            ->pluck('company_role_id')
            ->toArray();
    }
    /**
     * Get permissions for the file.
     *
     * @param int $fileId
     * @return array
     */
    private function getFilePermissions($fileId)
    {
        return RoleFilePermission::where('file_id', $fileId)
            ->get()
            ->groupBy('company_role_id')
            ->map(fn($permissions) => [
                'can_download' => $permissions->contains('can_download', true),
                'can_view' => $permissions->contains('can_view', true),
                'can_update' => $permissions->contains('can_update', true),
                'can_delete' => $permissions->contains('can_delete', true),
            ])
            ->toArray();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $company_id = get_active_company();
        if (!$company_id) {
            return $this->errorResponse('Active company not found.', 400);
        }

        try {
            $file = File::findOrFail($id);
            $file->update([
                'file_name' => $request->name,
                'updated_by' => current_user()->id,
            ]);

            $selectedRoles = $request->input('roles', []);
            $request->merge(['permissions' => $selectedRoles]);
            $roles = CompanyRole::whereIn('id', $request->input('roles', []))->pluck('role_name')->toArray();

            // Sync permissions
            $this->syncPermissions($id, $request->input('permissions', []));

            // Send emails if roles were assigned and email toggle is enabled
            if (!empty($selectedRoles) && isset($request->send_email)) {
                $this->sendPermissionEmails([], [$file->file_name], $selectedRoles, $company_id);
            }

            addUserAction([
                'user_id' => Auth::id(),
                'action' => "File {$file->file_name} updated with Role Assigned: " . implode(', ', $roles)
            ]);

            return $this->successResponse('File updated successfully!', $file);
        } catch (\Exception $e) {
            addUserAction([
                'user_id' => Auth::id(),
                'action' => "Error updating file: " . $e->getMessage()
            ]);
            return $this->errorResponse('There was an error updating the file.', 500, $e);
        }
    }

    private function syncPermissions($fileId, array $permissions)
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(File $file)
    {
        //
    }

    public function extractUploadedZip(Request $request)
    {
        $file = File::find($request->id);

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }

        $company_id = get_active_company();
        if (!$company_id) {
            return response()->json(['error' => 'Active company not found'], 400);
        }

        try {
            \App\Jobs\ExtractZipJob::dispatch($file->id, auth()->id(), $company_id);
            return redirect()->back()->with('success', 'Zip extraction job queued successfully.');
        } catch (\Throwable $th) {
            Log::error("ExtractZipJob failed: " . $th->getMessage());
            return redirect()->back()->with('error', 'Error queuing zip extraction job: ' . $th->getMessage());
        }
    }
}
