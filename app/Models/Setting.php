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
    ];
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
