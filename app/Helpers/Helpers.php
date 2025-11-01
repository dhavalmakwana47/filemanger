<?php

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\File;
use App\Models\UserLog;
use Illuminate\Support\Facades\Request;

function get_active_company()
{
    // $company = session('active_company');
    // if (!$company) {
    //     return null;
    // }
    // if (auth()->user()->is_master_admin() || auth()->user()->is_super_admin()) {
    //     return $company;
    // }
    
    // $isCompanyMemeber = CompanyUser::where('user_id', auth()->id())
    //     ->where('company_id', $company)->where('is_active', 1)
    //     ->exists();

    // if (!$isCompanyMemeber) {
    //     return null;
    // }
    return session('active_company');
}

function current_user()
{
    return auth()->user();
}
function fetch_company()
{
    if (current_user()->is_master_admin()) {
        return Company::all();
    }
    return current_user()->companies;
}

function addUserAction($data)
{
    if (get_active_company()) {
        UserLog::create([
            'user_id' => isset($data['user_id']) ? $data['user_id'] : null,
            'ipaddress' => Request::ip(),
            'action' => $data['action'],
            'company_id' => get_active_company(),
        ]);
    }
}

function getTotalUsedSpace()
{
    $company = Company::find(get_active_company());

    if (!$company || $company->end_date < date('Y-m-d')) {
        return 0;
    }

    $availableSize = $company->storage_size_mb * 1024;
    $usedSpaceMb = round(
        File::withTrashed()
            ->where('company_id', $company->id)
            ->sum('size_kb') / 1024,
        2
    );

    $remainingSpace = $availableSize - $usedSpaceMb;

    return ($remainingSpace < 0) ? 0 : $remainingSpace;
}
