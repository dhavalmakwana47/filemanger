<?php

namespace App\Http\Controllers;

use App\Mail\SendResourcePermissionEmail;
use App\Models\CompanyUserRole;
use Illuminate\Support\Facades\Mail;

abstract class Controller
{
    public function admin_check($user)
    {
        return ($user->is_master_admin() || $user->is_super_admin());
    }

    /**
     * Standardized success response.
     */
    public function successResponse($message, $data = null)
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data]);
    }

    /**
     * Standardized error response.
     */
    public function errorResponse($message, $code = 200, $exception = null)
    {
        \Log::error($message . ($exception ? ' - ' . $exception->getMessage() : ''));
        return response()->json(['success' => false, 'message' => $message], $code);
    }

    protected function sendPermissionEmails(array $folderNames, array $fileNames, array $roleIds, int $companyId)
    {
        // Fetch unique users with the selected roles
        $users = CompanyUserRole::whereIn('company_role_id', $roleIds)
            ->where('company_id', $companyId)
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter(function ($user) {
                return !is_null($user) && !is_null($user->email);
            })
            ->unique('id'); // Ensure unique users by ID

            $companyName = auth()->user()->company->name ?? 'the company';

        // Send email to each user
        foreach ($users as $user) {
            Mail::to($user->email)->queue(new SendResourcePermissionEmail($folderNames, $fileNames,$companyName));
        }
    }
}
