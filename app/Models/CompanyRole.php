<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyRole extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'role_name'
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'company_role_permissions', 'company_role_id', 'permission_id');
    }
    
    public function users()
    {
        return $this->belongsToMany(User::class, 'company_user_roles', 'company_role_id', 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function getFormattedNameAttribute()
    {
        $name = str_replace('_', ' ', $this->role_name);
        return ucwords($name);
    }

}
