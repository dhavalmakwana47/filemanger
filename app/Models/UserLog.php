<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ipaddress',
        'action',
        'company_id'
    ];
    protected $table = 'user_logs';
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
