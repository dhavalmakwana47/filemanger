<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Folder extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'name',
        'parent_id',
        'company_id',
        'created_by',
        'updated_by',
        'item_index'
    ];

    protected static function booted()
    {
        static::addGlobalScope('item_index', function ($query) {
            $query->orderBy('item_index', 'asc');
        });
    }


    // Subfolders relationship
    public function subfolders()
    {
        return $this->hasMany(Folder::class, 'parent_id')->orderBy('name');
    }

    public function  access_to_role()
    {
        return $this->hasMany(RoleFolderPermission::class, 'folder_id');
    }

    public function has_access()
    {
        if (current_user()->is_master_admin() || current_user()->id == $this->created_by || current_user()->is_super_admin()) {
            return true;
        }
        return $this->access_to_role()->whereIn('company_role_id', current_user()->companyRoles->pluck('id')->toArray())->exists();
    }

    public function files()
    {
        return $this->hasMany(File::class, 'folder_id');
    }

    public function rolePermissions()
    {
        return $this->hasMany(RoleFolderPermission::class, 'folder_id');
    }

    public function getPermissions()
    {
        return $this->rolePermissions()
            ->whereIn('company_role_id', current_user()->companyRoles->pluck('id'))
            ->get(['can_create', 'can_update', 'can_delete']);
    }
      public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
