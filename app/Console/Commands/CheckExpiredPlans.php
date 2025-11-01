<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use Illuminate\Support\Facades\Mail;
use App\Mail\PlanExpiredMail;
use App\Models\CompanyRole;
use App\Models\CompanyUserRole;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckExpiredPlans extends Command
{
    protected $signature = 'plans:check-expired';
    protected $description = 'Check for expired company plans and notify admins';

    public function handle()
    {
        $today = Carbon::now();

        $expiredCompanies = Company::where('end_date', '<', $today)->get();

        foreach ($expiredCompanies as $company) {
            try {
                // Find Super Admin role
                $adminRole = CompanyRole::where('company_id', $company->id)
                    ->where('role_name', 'Super Admin')
                    ->first();

                if (!$adminRole) {
                    Log::warning("No Super Admin role found for company: {$company->name} (ID: {$company->id})");
                    continue;
                }

                // Find user linked with Super Admin
                $adminUserRole = CompanyUserRole::with('user')
                    ->where('company_role_id', $adminRole->id)
                    ->first();
                $company->admin = $adminUserRole ? $adminUserRole->user : null;

                if ($adminUserRole && $adminUserRole->user) {
                    Mail::to($adminUserRole->user->email)->send(new PlanExpiredMail($company));
                    $this->info("Notified company: {$company->name}");
                } else {
                    Log::warning("No admin user found for company: {$company->name} (ID: {$company->id})");
                }
            } catch (\Exception $e) {
                Log::error("Error processing company: {$company->name} (ID: {$company->id}) - " . $e->getMessage());
            }
        }

        $this->info('Expired plans check completed.');
    }
}
