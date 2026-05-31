<?php

namespace App\Imports;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\CompanyUserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserRegistrationMail;
use App\Models\CompanyRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as FacadesLog;
use Maatwebsite\Excel\Validators\Failure;
use Log;

class UserImport implements ToCollection, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
{
    protected $requestData;
    protected $skippedEmails = [];

    public function __construct($requestData)
    {
        $this->requestData = $requestData;
    }

    public function collection(Collection $rows)
    {
        $activeCompanyId = get_active_company();
        $company = Company::find($activeCompanyId);
        $companyName = $company ? $company->name : 'Our Company';
        
        // Get existing emails in this company (single query)
        $existingEmails = User::whereHas('companies', function($q) use ($activeCompanyId) {
            $q->where('company_id', $activeCompanyId);
        })->pluck('email')->toArray();

        foreach ($rows as $row) {
            $email = $row['email'];
            
            // Skip if already exists in company
            if (in_array($email, $existingEmails)) {
                $this->skippedEmails[] = $email;
                continue;
            }

            // Check if user exists globally
            $user = User::where('email', $email)->first();
            $isNewUser = false;
            $password = null;

            if (!$user) {
                $password = $this->generatePassword8();
                $user = User::create([
                    'name' => $row['name'],
                    'email' => $email,
                    'password' => bcrypt($password),
                    'is_active' => 1
                ]);
                $isNewUser = true;
            }

            // Add to company
            CompanyUser::firstOrCreate([
                'user_id' => $user->id,
                'company_id' => $activeCompanyId
            ]);

            // Add roles
            if (isset($this->requestData->role)) {
                foreach ($this->requestData->role as $role) {
                    CompanyUserRole::firstOrCreate([
                        'user_id' => $user->id,
                        'company_id' => $activeCompanyId,
                        'company_role_id' => $role,
                    ]);
                }
            }

            // Queue email only for new users
            if ($isNewUser && $password) {
                try {
                    Mail::to($user->email)->queue(new UserRegistrationMail(
                        $user,
                        $companyName,
                        true,
                        $password
                    ));
                } catch (\Exception $e) {
                    // Silent fail for emails
                }
            }

            $existingEmails[] = $email;
        }

        // Log action once for entire batch
        if ($rows->count() > 0) {
            $roles = CompanyRole::whereIn('id', $this->requestData->role ?? [])->pluck('role_name')->toArray();
            $roleNames = !empty($roles) ? implode(', ', $roles) : 'No Roles Assigned';
            
            addUserAction([
                'user_id' => Auth::id(),
                'action' => "Bulk import: " . ($rows->count() - count($this->skippedEmails)) . " users imported with roles: {$roleNames}"
            ]);
        }
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getSkippedEmails()
    {
        return $this->skippedEmails;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
        ];
    }

    private function generatePassword8(): string
    {
        do {
            $pwd = Str::random(8);
        } while (!preg_match('/[A-Za-z]/', $pwd) || !preg_match('/\d/', $pwd));

        return $pwd;
    }
}