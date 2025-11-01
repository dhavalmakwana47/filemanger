<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleFilePermission extends Model
{
    use HasFactory;
    public function companyRole()
    {
        return $this->belongsTo(CompanyRole::class, 'company_role_id');
    }
}
