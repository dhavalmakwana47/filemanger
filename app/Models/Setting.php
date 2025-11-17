<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'company_id',
        'watermark_image',
        'ip_restriction',
        'enable_watermark',
        'nda_content',
        'nda_content_enable',
    ];
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function ipRestrictions()
    {
        return $this->hasMany(CompanyIpRestriction::class);
    }
}
