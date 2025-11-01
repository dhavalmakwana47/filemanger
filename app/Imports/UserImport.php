<?php

namespace App\Imports;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\CompanyUserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserRegistrationMail;
use App\Models\CompanyRole;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Validators\Failure;
use Log;

class UserImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $requestData;

    public function __construct($requestData)
    {
        $this->requestData = $requestData;
    }

    public function model(array $row)
    {
        return DB::transaction(function () use ($row) {
            $user = User::where('email', $row['email'])->first();
            $activeCompanyId = get_active_company();
            $company = Company::find($activeCompanyId);
            $companyName = $company ? $company->name : 'Our Company';
            $isNewUser = false;
            $password = null;

            if (!$user) {
                $password = $this->generatePassword8();
                $user = User::create([
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'password' => bcrypt($password),
                    'is_active' => $row['status'],
                ]);
                $isNewUser = true;
            }

            CompanyUser::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'company_id' => $activeCompanyId
                ]
            );

            if (isset($this->requestData->role)) {
                foreach ($this->requestData->role as $role) {
                    CompanyUserRole::create([
                        'user_id' => $user->id,
                        'company_id' => $activeCompanyId,
                        'company_role_id' => $role,
                    ]);
                }
            }
            $roles = CompanyRole::whereIn('id', $request->role ?? [])->pluck('role_name')->toArray();
            $roleNames = !empty($roles) ? implode(', ', $roles) : 'No Roles Assigned';

            addUserAction([
                'user_id' => Auth::id(),
                'action' => $isNewUser
                    ? "User {$user->name} created and Login details Successfully sent to {$user->email} with Role Assign {$roleNames}"
                    : "User {$user->name} added to company"
            ]);


            try {
                Mail::to($user->email)->send(new UserRegistrationMail(
                    $user,
                    $companyName,
                    $isNewUser,
                    $isNewUser ? $password : null
                ));
            } catch (\Exception $e) {
                Log::error('Failed to send registration email: ' . $e->getMessage());
            }

            return $user;
        });
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->where(function ($query) {
                    $query->whereIn('id', function ($subQuery) {
                        $subQuery->select('user_id')
                            ->from('company_users')
                            ->where('company_id', get_active_company());
                    });
                }),
            ],
        ];
    }

    public function customValidationMessages()
    {
        return [
            'name.required' => 'The name field is required.',
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'The email :input has already been taken for this company.',
        ];
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $row = $failure->row();
            $errors = $failure->errors();
            $values = $failure->values();

            foreach ($errors as $error) {
                // Customize the error message to include the email
                if (strpos($error, 'The email') !== false && isset($values['email'])) {
                    $error = str_replace(':input', $values['email'], $error);
                }
                throw new \Exception("Error on row {$row}: {$error}");
            }
        }
    }

    private function generatePassword8(): string
    {
        do {
            $pwd = Str::random(8);
        } while (!preg_match('/[A-Za-z]/', $pwd) || !preg_match('/\d/', $pwd));

        return $pwd;
    }
}
