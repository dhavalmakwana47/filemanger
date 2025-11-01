<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\AddUpdateRequest;
use App\Mail\CompanyCreatedMail;
use App\Mail\PlanUpdatedMail;
use App\Models\Company;
use App\Models\CompanyRole;
use App\Models\CompanyRolePermission;
use App\Models\CompanyUser;
use App\Models\CompanyUserRole;
use App\Models\Permission;
use App\Models\RoleFilePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Mail;

class CompanyController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission_check:Company,view', only: ['index', 'show']),
            new Middleware('permission_check:Company,create', only: ['create', 'store']),
            new Middleware('permission_check:Company,update', only: ['edit', 'update']),
            new Middleware('permission_check:Company,delete', only: ['edit', 'destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (request()->ajax()) {
            $companyArr = Company::select(['id', 'name', 'created_at']);
            return DataTables::of($companyArr)
                ->addColumn('admin', function ($company) {
                    $emails = $company
                        ->companyRoles()
                        ->where('role_name', 'Super Admin')
                        ->with('users')
                        ->get()
                        ->pluck('users') // Extract users collection
                        ->flatten() // Flatten nested collections
                        ->map(function ($user) {
                            return "<li><b>{$user->name}</b> (<i>{$user->email}</i>)</li>"; // Format name and email as a list item
                        })
                        ->implode(''); // Join list items without separators

                    return "<ul style='padding-left: 15px;'>{$emails}</ul>"; // Wrap in an unordered list
                })
                ->editColumn('created_at', function ($item) {
                    return $item->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('action', function ($company) {
                    $editUrl = route('company.edit', $company->id);
                    $deleteUrl = route('company.destroy', $company->id); // Assuming 'company.destroy' is the delete route

                    return '
                    <a href="' . $editUrl . '" class="btn btn-sm btn-primary">Edit</a>
                    
                    <form action="' . $deleteUrl . '" method="POST" style="display:inline;">
                        ' . csrf_field() . '
                        ' . method_field('DELETE') . '
                        <button type="submit" class="btn btn-sm btn-danger" 
                            onclick="return confirm(\'Are you sure you want to delete this company?\');">Delete</button>
                    </form>
                ';
                })
                ->rawColumns(['admin', 'action']) // Allow HTML in these columns

                ->make(true);
        }

        return view('app.company.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('app.company.addupdate');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AddUpdateRequest $request)
    {
        // Start a database transaction
        DB::beginTransaction();

        try {
            $user = User::where('email', $request->input('user_email'))->first();
            if (!isset($user)) {
                // Create the admin user for the company
                $user = User::create([
                    'name' => $request->input('user_name'),
                    'email' => $request->input('user_email'),
                    'password' => bcrypt($request->input('password'))
                ]);
            }
            // Create the company
            $company = Company::create([
                'name' => $request->input('company_name'),
                'admin_id' => $user->id,
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'storage_size_mb' => $request->input('storage_size_mb'),
            ]);

            CompanyUser::create([
                'user_id' => $user->id,
                'company_id' => $company->id
            ]);

            $companyRole = CompanyRole::create([
                'company_id' => $company->id,
                'role_name' => 'Super Admin'
            ]);

            CompanyUserRole::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'company_role_id' => $companyRole->id
            ]);

            $permissions = Permission::all();

            foreach ($permissions as $permission) {
                CompanyRolePermission::create([
                    'company_role_id' => $companyRole->id,
                    'permission_id' => $permission->id
                ]);
            }

            // Attempt to send email, but continue if it fails
            try {
                Mail::to($user->email)->send(new CompanyCreatedMail($company, $user, $request->input('password')));
            } catch (\Exception $e) {
                // Log the email failure (optional, depending on your logging setup)
                \Log::error('Failed to send company creation email: ' . $e->getMessage());
            }

            addUserAction([
                'user_id' => current_user()->id,
                'action' => "Company {$company->name} created with Admin: {$user->email}"
            ]);
            // Commit the transaction
            DB::commit();

            return redirect()->route('company.index')->with('success', 'Company Created successfully.');
        } catch (\Exception $e) {
            // Rollback the transaction if an error occurs
            DB::rollBack();

            return redirect()->route('company.index')->with('error', 'Error creating company: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Company $company)
    {
        $data['company'] = $company;
        return view('app.company.addupdate', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AddUpdateRequest $request, Company $company)
    {
        $company->update([
            'name' => $request->input('company_name'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'storage_size_mb' => $request->input('storage_size_mb'),
        ]);

        $adminRole = CompanyRole::where('company_id', $company->id)
            ->where('role_name', 'Super Admin')
            ->first();
            
        // Find user linked with Super Admin
        $adminUserRole = CompanyUserRole::with('user')
            ->where('company_role_id', $adminRole->id)
            ->first();

        $company->admin = $adminUserRole ? $adminUserRole->user : null;
        // Send email notification to the admin
        Mail::to($company->admin->email)->send(new PlanUpdatedMail($company));
        addUserAction([
            'user_id' => current_user()->id,
            'action' => "Company {$company->name} updated Plan Period to {$company->start_date} to {$company->end_date} and Storage to {$company->storage_size_mb} MB"
        ]);

        return redirect()->route('company.index')->with('success', 'Company updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        $user = current_user();
        if (!$user->is_master_admin()) {
            return redirect()->route('login');
        }
        $companyRoles = CompanyRole::where('company_id', $company->id)->pluck('id')->toArray();

        RoleFilePermission::whereIn('company_role_id', $companyRoles)->delete();
        CompanyUserRole::whereIn('company_role_id', $companyRoles)->delete();
        CompanyRolePermission::whereIn('company_role_id', $companyRoles)->delete();
        CompanyRole::where('company_id', $company->id)->delete();

        User::whereIn(
            'id',
            CompanyUser::where('company_id', $company->id)->pluck('user_id')->toArray()
        )->delete();

        $company->delete();

        return redirect()->route('company.index')->with('success', 'Company deleted successfully.');
    }
}
