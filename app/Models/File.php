<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'name',
        'folder_id',
        'company_id',
        'created_by',
        'updated_by',
        'size_kb',
        'item_index',
        'file_name'
    ];

    public function  access_to_role()
    {
        return $this->hasMany(RoleFilePermission::class, 'file_id');
    }

    protected static function booted()
    {
        static::addGlobalScope('item_index', function ($query) {
            $query->orderBy('item_index', 'desc');
        });
    }

    public function rolePermissions()
    {
        return $this->hasMany(RoleFilePermission::class, 'file_id');
    }

    public function hasAccess()
    {
        if (current_user()->is_master_admin() || current_user()->id == $this->created_by || current_user()->is_super_admin()) {
            return true;
        }
        return $this->rolePermissions()
            ->whereIn('company_role_id', current_user()->companyRoles->pluck('id'))
            ->exists();
    }

    public function checkAccess($moduleName, $permision)
    {
        if (current_user()->is_master_admin() || current_user()->id == $this->created_by || current_user()->is_super_admin()) {
            return true;
        }

        $roles = $this->rolePermissions()
            ->whereIn('company_role_id', current_user()->companyRoles->pluck('id'))
            ->pluck('company_role_id');

        if ($roles->isEmpty()) {
            return false;
        }

        $permisions = Permission::where('module_name', $moduleName)
            ->where('slug', $permision)
            ->pluck('id');

        return CompanyRolePermission::whereIn('permission_id', $permisions)
            ->whereIn('company_role_id', $roles)->exists();
    }

    public function getPermissions()
    {
        return $this->rolePermissions()
            ->whereIn('company_role_id', current_user()->companyRoles->pluck('id'))
            ->first(['can_download', 'can_update', 'can_delete']);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function bookmarks()
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }

    /**
     * Check if the current user has bookmarked this file
     */
    public function bookmarkedByCurrentUser()
    {
        if (!auth()->check()) {
            return null;
        }
        
        return $this->morphOne(Bookmark::class, 'bookmarkable')
            ->where('user_id', auth()->id())
            ->where('company_id', current_user()->company_id);
    }

    /**
     * Check if the file is bookmarked by the current user
     * @return bool
     */
    public function isBookmarkedByCurrentUser(): bool
    {
           if (!auth()->check()) {
            return false;
        }
        return $this->bookmarks()
            ->where('user_id', auth()->user()->id)
            ->where('company_id',get_active_company())
            ->exists();
    }
}
