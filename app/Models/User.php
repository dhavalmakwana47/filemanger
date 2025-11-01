<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withPivot('created_at');
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_users', 'user_id', 'company_id');
    }

    public function companyRoles()
    {
        $activeCompanyId = get_active_company();

        return $this->belongsToMany(CompanyRole::class, 'company_user_roles', 'user_id', 'company_role_id')
            ->withPivot('company_id') // Ensure you include this if you're storing company ID in the pivot table
            ->where('company_user_roles.company_id', $activeCompanyId)

            ->withTimestamps();
    }


    public function activeCompanyRoles()
    {
        $activeCompanyId = get_active_company();
        return $this->companyRoles()->where('company_id', $activeCompanyId);
    }

    public function is_master_admin()
    {
        return $this->roles()->where('name',  env('MASTER_ADMIN_NAME'))->exists();
    }

    public function is_super_admin()
    {
        return $this->companyRoles()->where('role_name',  env('COMPANY_ADMIN_NAME'))->exists();
    }

    public function hasPermission($module, $permission)
    {
        if ($this->is_master_admin()) {
            return true;
        }

        foreach ($this->companyRoles as $role) {
            if ($role->permissions()
                ->where('slug', $permission)
                ->where('module_name', $module)
                ->exists()
            ) {
                return true;
            }
        }

        return false;
    }

    public function companyUser()
    {
        $activeCompanyId = get_active_company();

        return $this->hasOne(CompanyUser::class, 'user_id', 'id')
            ->where('company_id', $activeCompanyId);
    }
}
