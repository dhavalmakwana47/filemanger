<?php

namespace App\Http\Controllers;

use App\Exports\UsersExport;
use App\Http\Requests\User\UserRequest;
use App\Imports\UserImport;
use App\Mail\UserRegistrationMail;
use App\Models\Company;
use App\Models\CompanyRole;
use App\Models\CompanyUser;
use App\Models\CompanyUserRole;
use App\Models\File;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller implements HasMiddleware
{

    public static function middleware(): array
    {
        return [
            new Middleware('permission_check:Users,view', only: ['index', 'show']),
            new Middleware('permission_check:Users,create', only: ['create', 'store']),
            new Middleware('permission_check:Users,update', only: ['edit', 'update']),
            new Middleware('permission_check:Users,delete', only: ['edit', 'destroy']),
        ];
    }

    public function index(Request $request)
    {
        $currentUser = current_user();
        if (request()->ajax()) {

            // If the user is not a master admin, select users associated with the active company
            $users = User::with('companies', 'companyRoles', 'companyUser')
                ->select(['id', 'name', 'email', 'created_at', 'is_active'])
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


            return DataTables::of($users)
                ->addColumn('select', function ($user) {
                    return '<input type="checkbox" class="user-checkbox" value="' . $user->id . '">';
                })
                ->addColumn('action', function ($user) use ($currentUser) {
                    $actionButtons = '';
                    $resendPasswordUrl = route('users.resend_password', $user->id);

                    $actionButtons .= ' <a href="' . $resendPasswordUrl . '" class="btn btn-sm btn-warning">Resend Password</a> ';

                    if ($currentUser->hasPermission('Users', 'update')) {
                        $editUrl = route('users.edit', $user->id);

                        $actionButtons .= '<a href="' . $editUrl . '" class="btn btn-sm btn-primary">Edit</a>';
                    }

                    if ($currentUser->hasPermission('Users', 'delete')) {
                        $deleteUrl = route('users.destroy', $user->id);
                        $actionButtons .= '<form action="' . $deleteUrl . '" method="POST" style="display:inline;">
                                        ' . csrf_field() . '
                                        ' . method_field('DELETE') . '
                                        <button type="submit" class="btn btn-sm btn-danger" 
                                            onclick="return confirm(\'Are you sure you want to delete this user?\');">Delete</button>
                                       </form>';
                    }

                    return $actionButtons;
                })
                ->editColumn('created_at', function ($item) {
                    return $item->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status', function ($user) use ($currentUser) {
                    return view('app.users.status', ['user' => $user, 'currentUser' => $currentUser])->render();
                })
                ->rawColumns(['select', 'status', 'action'])
                ->make(true);
        }
        $data['roleArr'] = CompanyRole::whereNot('role_name', 'Super Admin')->where('company_id', get_active_company())->get();

        return view('app.users.index', $data);
    }

    public function create()
    {

        $data['roleArr'] = CompanyRole::whereNot('role_name', 'Super Admin')->where('company_id', get_active_company())->get();
        return view('app.users.add-update', $data);
    }

    private function generatePassword8(): string
    {
        do {
            $pwd = Str::random(8); // cryptographically secure, letters+numbers only
        } while (!preg_match('/[A-Za-z]/', $pwd) || !preg_match('/\d/', $pwd));

        return $pwd;
    }

    public function store(UserRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::where('email', $request->input('user_email'))->first();
            $activeCompanyId = get_active_company();
            $company = Company::find($activeCompanyId);
            $companyName = $company ? $company->name : 'Our Company';
            $isNewUser = false;
            $password = null;

            if (!$user) {
                $password = $this->generatePassword8();
                $user = User::create([
                    'name' => $request->input('user_name'),
                    'email' => $request->input('user_email'),
                    'password' => bcrypt($password)
                ]);

                $isNewUser = true;
            }

            CompanyUser::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'company_id' => $activeCompanyId,
                    'is_active' => $request->input('is_active') ?? 0
                ]
            );

            // Clear existing roles for the user in the active company
        if ($request->filled('role')) {

                // Fix: Use $role instead of $request->role inside the loop
                foreach ($request->role as $role) {
                    CompanyUserRole::create([
                        'user_id' => $user->id,
                        'company_id' => $activeCompanyId,
                        'company_role_id' => $role,
                    ]);
                }
            }

            $roles = CompanyRole::whereIn('id', $request->role ?? [])->pluck('role_name')->toArray();
            $roleNames = implode(', ', $roles);

            addUserAction([
                'user_id' => Auth::id(),
                'action' => $isNewUser
                    ? "User {$user->name} created and Login details Successfully sent to {$user->email} with Role Assign {$roleNames}"
                    : "User {$user->name} added to company"
            ]);


            DB::commit();

            // Send registration email AFTER transaction commits
            try {
                Mail::to($user->email)->send(new UserRegistrationMail(
                    $user,
                    $companyName,
                    $isNewUser,
                    $isNewUser ? $password : null
                ));
            } catch (\Exception $e) {
                \Log::error('Failed to send registration email: ' . $e->getMessage());
            }

            return redirect()->route('users.index')->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create user: ' . $e->getMessage());
            return redirect()->back()->withErrors('Failed to create user. Please try again.');
        }
    }


    public function edit(User $user)
    {
        if ($this->admin_check($user)) {
            return redirect()->route('login');
        }

        $data['user'] = $user;
        $data['roleArr'] = CompanyRole::whereNot('role_name', 'Super Admin')->where('company_id', get_active_company())->get();

        return view('app.users.add-update', $data);
    }

    public function update(UserRequest $request, $id)
    {
        $user = User::findOrFail($id);

        // Prepare update data
        $updateData = [
            'name' => $request->input('user_name'),
            'email' => $request->input('user_email')
        ];

        // Update password only if provided
        if ($request->filled('password')) {
            $updateData['password'] = bcrypt($request->input('password'));
        }
        CompanyUser::updateOrCreate(
            [
                'user_id' => $user->id,
                'company_id' => get_active_company(),
            ],
            [
                'is_active' => $request->input('is_active') ?? 0
            ]
        );

        // Update user information
        $user->update($updateData);

        // Get the active company ID
        $activeCompanyId = get_active_company();

        // Delete existing roles for the active company
        CompanyUserRole::where('user_id', $id)
            ->where('company_id', $activeCompanyId)
            ->delete();

        // Assign new roles if provided
        if ($request->filled('role')) {
            foreach ($request->role as $role) {
                CompanyUserRole::create([
                    'user_id' => $id,
                    'company_id' => $activeCompanyId,
                    'company_role_id' => $role,
                ]);
            }
        }
        $roles = CompanyRole::whereIn('id', $request->role ?? [])->pluck('role_name')->toArray();
        $roleNames = implode(', ', $roles);

        addUserAction([
            'user_id' => Auth::id(),
            'action'  => "User {$user->name} Updated with Role Assign {$roleNames} ({$user->email})"
        ]);


        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if ($this->admin_check($user)) {
            return redirect()->route('login');
        }

        CompanyUser::where('user_id', $id)->where('company_id', get_active_company())->delete();
        CompanyUserRole::where('user_id', $id)->where('company_id', get_active_company())->delete();
        // Log the user deletion action
        addUserAction([
            'user_id' => Auth::id(),
            'action' => "User {$user->name} deleted ({$user->email})"
        ]);

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    public function change_company(Request $request)
    {

        session(['active_company' => $request->company_id]);
        session()->forget('nda_agreement');
        return redirect()->route('dashboard');
    }


    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ]);

        try {
            // Get uploaded file instance
            $uploadedFile = $request->file('file');
            $fileName = $uploadedFile->getClientOriginalName();

            // Import the CSV file using Laravel Excel
            Excel::import(new UserImport($request), $uploadedFile);

            // Fetch role names
            $roles = CompanyRole::whereIn('id', $request->role ?? [])->pluck('role_name')->toArray();
            $roleNames = !empty($roles) ? implode(', ', $roles) : 'No Roles Assigned';

            // Log user action
            addUserAction([
                'user_id' => Auth::id(),
                'action'  => "Import file '{$fileName}' successfully uploaded. Users assigned with roles: {$roleNames}"
            ]);

            return redirect()->back()->with('success', 'Users imported successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error importing users: ' . $e->getMessage());
        }
    }


    public function resendPassword($id)
    {


        $user = User::findOrFail($id);

        $activeCompanyId = get_active_company();
        $company = Company::find($activeCompanyId);
        $companyName = $company ? $company->name : 'Our Company';
        $password = $this->generatePassword8(); // Shuffle to mix letters/numbers
        $user->password = bcrypt($password);
        $user->save();
        // Send registration email AFTER transaction commits
        try {
            Mail::to($user->email)->send(new UserRegistrationMail(
                $user,
                $companyName,
                false,
                $password
            ));
        } catch (\Exception $e) {
            \Log::error('Failed to send registration email: ' . $e->getMessage());
        }
        addUserAction([
            'user_id' => Auth::id(),
            'action' => "Password resent to user {$user->name} ({$user->email})"
        ]);

        return redirect()->back()->with('success', 'Password resent successfully.');
    }

    public function profile()
    {
        $data['user'] = Auth::user();
        $company = Company::find(get_active_company());
        if (isset($company) && (auth()->user()->is_master_admin() || auth()->user()->is_super_admin())) {
            # code...
            $remainingSpace = getTotalUsedSpace();
            $usedSpaceMb = round((File::where('company_id', $company->id)->sum('size_kb') / 1024)/1024, 2);

            // Initialize spaceDetails as an array
            $spaceDetails = [
                'total_space' => $company->storage_size_mb ?? 100, // Default to 100 GB if not set
                'used_space' => $usedSpaceMb ? $usedSpaceMb : 0, // Fetch used space from function
                'available_space' => $remainingSpace ? round($remainingSpace / 1024, 2) : 0, // Calculate available space
                'start_date' => $company->start_date ?? null,
                'end_date' => $company->end_date ?? null,
            ];

            $data['spaceDetails'] = (object) $spaceDetails; // Convert to object for Blade template consistency
        }

        return view('app.users.profile', $data);
    }
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // Validation rules
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8|confirmed',
        ];

        // Validate the request
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Update user details
        $user->name = $request->input('name');
        $user->email = $request->input('email');

        // Update password if provided
        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        // Save user
        $user->save();

        return redirect()->route('users.profile')
            ->with('success', 'Profile updated successfully.');
    }

    public function exportUsers()
    {
        return Excel::download(new UsersExport, 'users_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx');
    }

    public function changeStatus(Request $request)
    {
        $user = User::find($request->user_id);
        if ($this->admin_check($user)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }
        CompanyUser::where('user_id', $request->user_id)
            ->where('company_id', get_active_company())
            ->update(['is_active' => $request->is_active]);

        return response()->json(['status' => 'success', 'message' => 'User status updated successfully.']);
    }

    public function bulkStatusUpdate(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'is_active' => 'required|boolean'
        ]);

        $userIds = $request->user_ids;
        $isActive = $request->is_active;
        $activeCompanyId = get_active_company();

        // Check if any of the users are admin
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($this->admin_check($user)) {
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Cannot update status for admin users.'
                ], 403);
            }
        }

        // Update status for all selected users
        CompanyUser::whereIn('user_id', $userIds)
            ->where('company_id', $activeCompanyId)
            ->update(['is_active' => $isActive]);

        $statusText = $isActive ? 'enabled' : 'disabled';
        $count = count($userIds);

        addUserAction([
            'user_id' => Auth::id(),
            'action' => "Bulk status update: {$count} user(s) {$statusText}"
        ]);

        return response()->json([
            'status' => 'success', 
            'message' => "{$count} user(s) status updated successfully."
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $activeCompanyId = get_active_company();
        $userIds = $request->user_ids;

        // Prevent deleting admin users
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($this->admin_check($user)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete admin users.'
                ], 403);
            }
        }

        // Delete relations in active company only (match destroy behavior)
        CompanyUser::whereIn('user_id', $userIds)
            ->where('company_id', $activeCompanyId)
            ->delete();

        CompanyUserRole::whereIn('user_id', $userIds)
            ->where('company_id', $activeCompanyId)
            ->delete();

        addUserAction([
            'user_id' => Auth::id(),
            'action' => 'Bulk delete: ' . count($userIds) . ' user(s) detached from company'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => count($userIds) . ' user(s) deleted from this company successfully.'
        ]);
    }
}
