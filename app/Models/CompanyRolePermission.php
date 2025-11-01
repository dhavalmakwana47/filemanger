<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyRolePermission extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_role_id',
        'permission_id'
    ];
}
