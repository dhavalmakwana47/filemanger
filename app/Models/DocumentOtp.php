<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentOtp extends Model
{
    protected $fillable = ['document_id', 'email', 'otp', 'expires_at', 'verified_at'];

    protected $casts = ['expires_at' => 'datetime', 'verified_at' => 'datetime'];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
