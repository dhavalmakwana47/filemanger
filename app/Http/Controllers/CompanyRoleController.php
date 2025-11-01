<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\CompanyRoleRequest;
use App\Models\Company;
use App\Models\CompanyRole;
use App\Models\CompanyRolePermission;
use App\Models\CompanyUserRole;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class CompanyRoleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission_check:Company Role,view', only: ['index', 'show']),
            new Middleware('permission_check:Company Role,create', only: ['create', 'store']),
            new Middleware('permission_check:Company Role,update', only: ['edit', 'update']),
            new Middleware('permission_check:Company Role,delete', only: ['destroy']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $currentUser = current_user();

        if (request()->ajax()) {

            // If the user is not a master admin, select users associated with the active company
            $roles = CompanyRole::where('company_id', get_active_company())->whereNot('role_name', 'Super Admin')->get();

            return DataTables::of($roles)
                ->editColumn('created_at', function ($item) {
                    return $item->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('action', function ($role) use ($currentUser) {
                    $actionButtons = '';

                    if ($currentUser->hasPermission('Company Role', 'update')) {
                        $editUrl = route('companyrole.edit', $role->id);
                        $actionButtons .= '<a href="' . $editUrl . '" class="btn btn-sm btn-primary">Edit</a> ';
                        $assignUserUrl = route('companyrole.usersassign', $role->id);
                        $actionButtons .= '<a href="' . $assignUserUrl . '" class="btn btn-sm btn-secondary">Assign Users</a> ';
                    }

                    if ($currentUser->hasPermission('Company Role', 'delete')) {
                        $deleteUrl = route('companyrole.destroy', $role->id);
                        $actionButtons .= '<form action="' . $deleteUrl . '" method="POST" style="display:inline;">
                                            ' . csrf_field() . '
                                            ' . method_field('DELETE') . '
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                onclick="return confirm(\'Are you sure you want to delete this role?\');">Delete</button>
                                       </form>';
                    }

                    return $actionButtons;
                })

                ->make(true);
        }

        return view('app.role.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data = [];
        $data['permissionsArr'] = Permission::where('module_name', 'folder')->orderBy('module_name')->orderBy('name')->get()->groupBy('module_name');
        return view('app.role.add-update', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CompanyRoleRequest $request)
    {
        // Create the company role
        $companyrole = CompanyRole::create([
            'role_name' => $request->role_name,
            'company_id' => get_active_company()
        ]);

        // Ensure permissions is an array; default to empty array if null
        $permissions = $request->permissions ?? [];

        // Insert permissions if any are provided
        if (!empty($permissions)) {
            CompanyRolePermission::insert(
                collect($permissions)->map(function ($permissionId) use ($companyrole) {
                    return [
                        'company_role_id' => $companyrole->id,
                        'permission_id' => $permissionId
                    ];
                })->toArray()
            );
        }

        // Fetch permission names for logging (only if permissions exist)
        $permissionNames = !empty($permissions)
            ? Permission::whereIn('id', $permissions)->pluck('name')->toArray()
            : [];

        // Log the role creation action
        addUserAction([
            'user_id' => Auth::id(),
            'action' => "Role {$companyrole->role_name} created with Assign Permission " . implode(', ', $permissionNames)
        ]);

        return redirect()->route('companyrole.index')->with('success', 'Role created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CompanyRole $companyRole)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CompanyRole $companyrole)
    {
        if ($companyrole->role_name == "Super Admin") {
            return redirect()->route('login');
        }

        $data['role'] = $companyrole;
        $data['permissionsArr'] = Permission::where('module_name', 'folder')->orderBy('module_name')->orderBy('name')->get()->groupBy('module_name');
        return view('app.role.add-update', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CompanyRoleRequest $request, CompanyRole $companyrole)
    {
        // Delete existing permissions for the role
        CompanyRolePermission::where('company_role_id', $companyrole->id)->delete();

        // Ensure permissions is an array; default to empty array if null
        $permissions = $request->permissions ?? [];

        // Insert new permissions if any are provided
        if (!empty($permissions)) {
            CompanyRolePermission::insert(
                collect($permissions)->map(function ($permissionId) use ($companyrole) {
                    return [
                        'company_role_id' => $companyrole->id,
                        'permission_id' => $permissionId
                    ];
                })->toArray()
            );
        }

        // Fetch permission names for logging (only if permissions exist)
        $permissionNames = !empty($permissions)
            ? Permission::whereIn('id', $permissions)->pluck('name')->toArray()
            : [];

        // Log the action
        addUserAction([
            'user_id' => Auth::id(),
            'action' => "Role {$companyrole->role_name} updated with Assign Permission " . implode(', ', $permissionNames)
        ]);

        // Update the role name
        $companyrole->update([
            'role_name' => $request->role_name
        ]);

        return redirect()->route('companyrole.index')->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CompanyRole $companyrole)
    {
        $usersrole =  CompanyUserRole::where('company_role_id', $companyrole->id)->count();
        if ($usersrole) {
            return redirect()->route('companyrole.index')->with('error', 'This role cannot be deleted because it is currently assigned to one or more users.');
        }

        addUserAction([
            'user_id' => Auth::id(),
            'action' => "Role {$companyrole->role_name} deleted"
        ]);

        $companyrole->delete();
        return redirect()->route('companyrole.index')->with('success', 'Role deleted successfully.');
    }

    public function userAssign($id)
    {
        $role = CompanyRole::findOrFail($id);
        if ($role->role_name == "Super Admin") {
            return redirect()->route('login');
        }

        $assignedUserIds = CompanyUserRole::where('company_role_id', $id)->pluck('user_id')->toArray();
        $users = User::with('companies', 'companyRoles')
            ->select(['id', 'name', 'email', 'created_at'])
            ->whereHas('companies', function ($query) {
                $query->where('company_id', get_active_company());
            })
            ->where(function ($query) {
                $query->whereDoesntHave('companyRoles')
                    ->orWhereHas('companyRoles', function ($q) {
                        $q->where('role_name', '!=', 'Super Admin');
                    });
            })
            ->get();

        return view('app.role.user-assign', [
            'role' => $role,
            'users' => $users,
            'assignedUserIds' => $assignedUserIds,
        ]);
    }

    public function userAssignStore(Request $request)
    {

        $request->validate([
            'role_id' => 'required|exists:company_roles,id',
            'users' => 'nullable|array',
            'users.*' => 'exists:users,id',
        ]);


        $roleId = $request->input('role_id');
        $userIds = $request->input('users');

        // Remove existing role assignments for the specified users
        CompanyUserRole::where('company_role_id', $roleId)->delete();

        // Assign the new role to the specified users
        if (empty($userIds)) {
            return redirect()->route('companyrole.index')->with('success', 'Users unassigned from role successfully.');
        }
        $assignments = [];
        foreach ($userIds as $userId) {
            $assignments[] = [
                'user_id' => $userId,
                'company_role_id' => $roleId,
                'company_id' => get_active_company(),
            ];
        }
        CompanyUserRole::insert($assignments);

        return redirect()->route('companyrole.index')->with('success', 'Users assigned to role successfully.');
    }
}
