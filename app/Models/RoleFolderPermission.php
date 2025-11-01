<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleFolderPermission extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_role_id',
        'folder_id',
        'can_create'
    ];
        public function companyRole()
    {
        return $this->belongsTo(CompanyRole::class, 'company_role_id');
    }
}
