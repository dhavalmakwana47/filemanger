<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyRoleController;
use App\Http\Controllers\CompanyRolePermissionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserLogController;
use App\Models\Company;
use App\Models\CompanyRole;
use App\Models\CompanyUserRole;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;



Route::get('/', function () {
    return view('welcome');
})->name('home');


Route::get('/access-denied', function () {
    return view('accessdenied');
})->name('accessdenied');

Route::middleware('auth')->group(function () {
    //dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/download-sample-csv', function () {
        $filePath = public_path('sample.csv');

        if (file_exists($filePath)) {
            return response()->download($filePath, 'sample.csv', [
                'Content-Type' => 'text/csv',
            ]);
        }

        abort(404, 'File not found');
    })->name('download.sample.csv');

    //profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    //users
    Route::resource('users', UserController::class);
    Route::get('my-profile', [UserController::class, 'profile'])->name('users.profile');
    Route::post('update-profile', [UserController::class, 'updateProfile'])->name('users.updateprofile');
    Route::get('/export-users', [UserController::class, 'exportUsers'])->name('users.export');

    Route::post('/change_company', [UserController::class, 'change_company'])->name('change_company');
    Route::post('users-upload', [UserController::class, 'upload'])->name('users.upload');
    Route::get('users-resendpassword/{id}', [UserController::class, 'resendPassword'])->name('users.resend_password');
    Route::post('chnage-status', [UserController::class, 'changeStatus'])->name('users.change_status');
    Route::post('users-bulk-status', [UserController::class, 'bulkStatusUpdate'])->name('users.bulk_status');
    Route::post('users-bulk-delete', [UserController::class, 'bulkDelete'])->name('users.bulk_delete');

    //company
    Route::resource('company', CompanyController::class);

    //company role
    Route::resource('companyrole', CompanyRoleController::class);
    Route::get('companyrole-users-assign/{id}', [CompanyRoleController::class, 'userAssign'])->name('companyrole.usersassign');
    Route::post('companyrole-users-assign', [CompanyRoleController::class, 'userAssignStore'])->name('companyrole.usersassignstore');

    //company role permission
    Route::resource('permission', CompanyRolePermissionController::class);
    Route::post('change-permission', [CompanyRolePermissionController::class, 'change_permission'])->name('role_permission.change_permission');

    //folder
    Route::resource('folder', FolderController::class);
    Route::resource('file', FileController::class);
    Route::get('file/view-file/{id}', [FileController::class, 'viewFile'])->name('file.view');
    Route::post('file/download', [FileController::class, 'downloadFile'])->name('file.download');
    Route::post('folder-zip', [FolderController::class, 'folderZip'])->name('folder.zip');
    Route::post('extract-zip', [FileController::class, 'extractUploadedZip'])->name('folder.extractzip');
    Route::post('assign-roles', [FolderController::class, 'assignRoles'])->name('folder.multiassignroles');
    Route::post('get-properties', [FolderController::class, 'getProperties'])->name('folder.getproperties');
    
    Route::post('file/view', [FileController::class, 'show'])->name('file.getview');
    Route::get('/getfiledata', [FolderController::class, 'fileManager'])->name('filemanger.data');
    Route::post('/delete/folders', [FolderController::class, 'deleteFolder'])->name('folders.delete');
    Route::get('/filemanager/trash/data', [FolderController::class, 'trashData'])->name('filemanager.trash.data');
    Route::post('filemanager/trash/bulk-delete', [FolderController::class, 'bulkDelete'])->name('filemanager.trash.bulkDelete');
    Route::delete('/filemanager/folder/{id}/force', [FolderController::class, 'forceDeleteFolder'])->name('filemanager.folder.forceDelete');
    Route::delete('/filemanager/file/{id}/force', [FolderController::class, 'forceDeleteFile'])->name('filemanager.file.forceDelete');
    Route::post('/filemanager/folder/{id}/restore', [FolderController::class, 'restoreFolder'])->name('filemanager.folder.restore');
    Route::post('/filemanager/file/{id}/restore', [FolderController::class, 'restoreFile'])->name('filemanager.file.restore');
    Route::post('folder/upload', [FolderController::class, 'uploadFolderStructure'])->name('folder.uploadstructure');
    
    //logs
    Route::get('userlog/', [UserLogController::class, "index"])->name('userlog.index');
    Route::post('userlog/download', [UserLogController::class, "userlog_downlaod"])->name('userlog.download');
    Route::post('userlog/getusers', [UserLogController::class, "getusers"])->name('userlog.users');
});

require __DIR__ . '/auth.php';
