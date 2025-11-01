<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;
    public function getFormattedNameAttribute()
    {
        $name = str_replace('_', ' ', $this->name);
        return ucwords($name);
    }
    public function roles()
    {
        return $this->belongsToMany(CompanyRole::class, 'company_role_permissions'); 
    }
        // Check if a specific role has this permission
        public function hasRole($role_id)
        {
            return $this->roles()->where('company_role_id', $role_id)->exists();
        }
        
}
