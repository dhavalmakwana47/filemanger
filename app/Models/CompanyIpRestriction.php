<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyIpRestriction extends Model
{
    use HasFactory;
    protected $table = 'company_ip_restrictions';

    protected $fillable = ['setting_id', 'ip_address', 'label'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
