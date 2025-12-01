<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyRole;
use App\Models\File;
use App\Models\Folder;
use App\Models\RoleFilePermission;
use App\Models\Setting;
use App\Services\FileViewer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Image;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver; // or Imagick\Driver
use PhpOffice\PhpWord\IOFactory as WordIO;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIO;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpWord\SimpleType\Jc;

class FileController extends Controller
{
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
                    $file->storeAs($filePath, $fileName, 'public');

                    // Save file info in the database
                    $folder = File::create([
                        'name' => $fileName,
                        'file_name' => $originalName . '.' . $extension,
                        'folder_id' => $request->folder_id ?? null,
                        'company_id' => $company_id,
                        'file_path' => $filePath . '/' . $fileName,
                        'item_index' => $request->item_index ?? 0,
                        'created_by' => current_user()->id,
                        'size_kb' => $sizeKb,
                    ]);

                    $filesData[] = $folder;
                    $fileNames[] = $folder->name;

                    $roles = CompanyRole::whereIn('id', $request->input('roles', []))->pluck('role_name')->toArray();
                    // Log the file upload action
                    addUserAction([
                        'user_id' => Auth::id(),
                        'action' => "File {$folder->name} created with Role Assigned: " . implode(', ', $roles)
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
            'action' => "File {$file->name} viewed"
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

        // 4. Check file exists
        if (!Storage::exists($path)) {
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
        $userEmail = $user?->email ?? 'unknown@domain.com';
        $downloadDate = now()->format('Y-m-d H:i');
        $textWatermark = "$userEmail | $downloadDate";

        // If watermark disabled or admin → direct download
        if (!$setting->enable_watermark || $user->is_master_admin() || $user->is_super_admin()) {
            addUserAction([
                'user_id' => Auth::id(),
                'action'  => "File {$file->name} downloaded"
            ]);

            return Storage::download($path, $file->name);
        }

        // Load file info
        $contents = Storage::get($path);
        $mime     = Storage::mimeType($path);
        $ext      = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));

        try {
            // === IMAGES: PNG, JPG, JPEG ===
            if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
                $manager = new ImageManager(new Driver());
                $image   = $manager->read($contents);

                // Cross diagonal watermarks with bigger text
                $width = $image->width();
                $height = $image->height();
                $fontSize = min($width, $height) / 3; // Much bigger font size
                
                // First diagonal (top-left to bottom-right)
                $image->text($textWatermark, $width / 2, $height / 2, function ($font) use ($fontSize) {
                    $font->size($fontSize);
                    $font->color('#CCCCCC80'); // More visible
                    $font->align('center');
                    $font->valign('middle');
                    $font->angle(45);
                });
                
                // Second diagonal (top-right to bottom-left)
                $image->text($textWatermark, $width / 2, $height / 2, function ($font) use ($fontSize) {
                    $font->size($fontSize);
                    $font->color('#CCCCCC80'); // More visible
                    $font->align('center');
                    $font->valign('middle');
                    $font->angle(-45);
                });

                $contents = $ext === 'png'
                    ? $image->toPng()->toString()
                    : $image->toJpeg(90)->toString();

                $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
            } elseif ($ext === 'pdf') {
                // Create new PDF instance
                $pdf = new \setasign\Fpdi\Fpdi();

                // Set auto page break to false
                $pdf->SetAutoPageBreak(false);

                // Get the number of pages in the source PDF
                $pageCount = $pdf->setSourceFile(
                    \setasign\Fpdi\PdfParser\StreamReader::createByString($contents)
                );

                // Process each page
                for ($i = 1; $i <= $pageCount; $i++) {
                    // Import the page
                    $templateId = $pdf->importPage($i);
                    $size = $pdf->getTemplateSize($templateId);

                    // Only proceed if we have valid page dimensions
                    if ($size['width'] > 0 && $size['height'] > 0) {
                        // Add a page with the same dimensions and orientation as the original
                        $pdf->AddPage(
                            $size['orientation'] === 'L' ? 'L' : 'P',
                            [$size['width'], $size['height']]
                        );

                        // Use the imported page
                        $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height'], true);

                        // Set smaller font for cross watermarks
                        $pdf->SetFont('Helvetica', '', 40); // Smaller font size
                        $pdf->SetTextColor(220, 220, 220); // More visible
                        
                        $centerX = $size['width'] / 2;
                        $centerY = $size['height'] / 2;
                        
                        // First diagonal (45 degrees)
                        $angle1 = 45 * pi() / 180;
                        $cos1 = cos($angle1);
                        $sin1 = sin($angle1);
                        $pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm', $cos1, $sin1, -$sin1, $cos1, $centerX, $centerY));
                        $pdf->SetXY(-80, -8);
                        $pdf->Cell(160, 16, $textWatermark, 0, 0, 'C');
                        $pdf->_out('Q');
                        
                        // Second diagonal (-45 degrees)
                        $angle2 = -45 * pi() / 180;
                        $cos2 = cos($angle2);
                        $sin2 = sin($angle2);
                        $pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm', $cos2, $sin2, -$sin2, $cos2, $centerX, $centerY));
                        $pdf->SetXY(-80, -8);
                        $pdf->Cell(160, 16, $textWatermark, 0, 0, 'C');
                        $pdf->_out('Q');
                    }
                }

                // Get the watermarked PDF as string
                $contents = $pdf->Output('S');
                $mime = 'application/pdf';
            }

            // For all other file types (docx, xlsx, etc.) → no watermark possible → download original
        } catch (\Exception $e) {
            \Log::error('Watermark application failed: ' . $e->getMessage());

            // On error, serve original file
            addUserAction([
                'user_id' => Auth::id(),
                'action'  => "File {$file->name} downloaded (watermark failed)"
            ]);

            return Storage::download($path, $file->name);
        }

        // Log successful download with watermark
        addUserAction([
            'user_id' => Auth::id(),
            'action'  => "File {$file->name} downloaded with text watermark"
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
                'item_index' => $request->item_index ?? 0,
            ]);

            $selectedRoles = $request->input('roles', []);
            $request->merge(['permissions' => $selectedRoles]);
            $roles = CompanyRole::whereIn('id', $request->input('roles', []))->pluck('role_name')->toArray();

            // Sync permissions
            $this->syncPermissions($id, $request->input('permissions', []));

            // Send emails if roles were assigned and email toggle is enabled
            if (!empty($selectedRoles) && isset($request->send_email)) {
                $this->sendPermissionEmails([], [$file->name], $selectedRoles, $company_id);
            }

            addUserAction([
                'user_id' => Auth::id(),
                'action' => "File {$file->name} updated with Role Assigned: " . implode(', ', $roles)
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
        // Validate the request
        // $request->validate([
        //     'zip_file_name' => 'required|string', // Name of the uploaded zip file
        //     'parent_id' => 'nullable|exists:folders,id', // Optional parent folder ID
        // ]);
        $file = File::find($request->id);

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }
        $parentId = $file->folder_id ?? null;

        // Get active company ID
        $company_id = get_active_company();
        if (!$company_id) {
            return response()->json(['error' => 'Active company not found'], 400);
        }

        try {
            // Construct the zip file path
            $zipFileName = $file->name;
            $zipPath = "uploads/company_{$company_id}/{$zipFileName}";
            $zipFullPath = Storage::disk('public')->path($zipPath);

            // Check if the zip file exists
            if (!Storage::disk('public')->exists($zipPath)) {
                return response()->json(['error' => 'Zip file not found'], 404);
            }

            // Open the zip file
            $zip = new ZipArchive();
            if ($zip->open($zipFullPath) !== true) {
                return response()->json(['error' => 'Could not open zip file'], 500);
            }

            // Process each entry in the zip
            $this->processZipEntries($zip, $company_id, $parentId);

            // Close the zip file
            $zip->close();

            // Delete the original zip file
            Storage::disk('public')->delete($zipPath);
            $file->forceDelete();

            // Log the action
            addUserAction([
                'user_id' => Auth::id(),
                'action' => "Zip file {$zipFileName} extracted and deleted for company {$company_id}"
            ]);

            return redirect()->back()->with('success', 'Zip file extracted and deleted successfully.');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error extracting zip file', 'details' => $e->getMessage()], 500);
        }
    }

    private function processZipEntries(ZipArchive $zip, string $company_id, ?int $parent_id = null): void
    {

        // Track created folders to avoid duplicates
        $folderCache = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (substr($entry, -1) === '/') {
                // Handle directory
                $this->createFolderFromZip($entry, $company_id, $parent_id, $folderCache);
            } else {
                // Handle file
                $this->createFileFromZip($entry, $zip, $company_id, $parent_id, $folderCache);
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
            \Log::warning("Could not read file from zip: {$entry}");
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
            \Log::info("Skipped file from zip (not allowed MIME): {$entry} ({$mimeType})");
            unlink($tmpPath);
            return;
        }
        // Generate new filename with timestamp
        $originalName = pathinfo($fileName, PATHINFO_FILENAME);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = $originalName . '_' . time() . '.' . $extension;
        $filePath = "uploads/company_{$company_id}/{$newFileName}";

        // Store file in storage
        Storage::disk('public')->put($filePath, fopen($tmpPath, 'r'));
        unlink($tmpPath);

        // Check if file exists and get size, default to 0 if not found
        $sizeKb = 0;
        if (Storage::disk('public')->exists($filePath)) {
            $sizeKb = Storage::disk('public')->size($filePath); // Convert bytes to KB
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
}
