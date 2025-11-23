<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserOtp extends Model
{
    protected $fillable = ['user_id', 'code', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public static function generateFor(User $user)
    {
        // Delete old ones
        static::where('user_id', $user->id)->delete();

        return static::create([
            'user_id' => $user->id,
            'code' => str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT),
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }
}
