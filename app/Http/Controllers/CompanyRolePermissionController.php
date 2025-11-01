<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyRole;
use App\Models\CompanyRolePermission;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CompanyRolePermissionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission_check:Company Permission,view', only: ['index']),
            new Middleware('permission_check:Company Permission,update', only: ['change_permission']),
        ];
    }

    public function index()
    {
        $data = [];
        $data['roles'] = CompanyRole::whereNot('role_name', env('COMPANY_ADMIN_NAME'))->where('company_id', get_active_company())->orderBy('role_name')->get();
        $data['permissionsArr'] = Permission::orderBy('module_name')->orderBy('name')->get()->groupBy('module_name');
        return view('app.permission.index', $data);
    }

    public function change_permission(Request $request)
    {
        $data = [
            "company_role_id" => $request->role_id,
            "permission_id" => $request->permission_id
        ];

        if ($request->status) {
            // Create or updated the RolePermission
            CompanyRolePermission::updateOrCreate($data);
        } else {
            // Delete the RolePermission if it exists
            CompanyRolePermission::where($data)->delete();
        }

        return response()->json(['message' => 'Permission updated successfully.']);
    }
}
