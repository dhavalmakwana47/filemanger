<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Document extends Model
{
    protected $fillable = ['company_id', 'title', 'content', 'share_token'];

    public function generateShareToken(): string
    {
        $token = hash('sha256', Str::random(40) . $this->id . time());
        $this->update(['share_token' => $token]);
        return $token;
    }

    public function otps()
    {
        return $this->hasMany(DocumentOtp::class);
    }

    public function views()
    {
        return $this->hasMany(DocumentView::class);
    }
}
