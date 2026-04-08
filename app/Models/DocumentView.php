<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentView extends Model
{
    protected $fillable = ['document_id', 'email', 'verified_at', 'viewed_at', 'ip_address', 'user_agent'];

    protected $casts = ['verified_at' => 'datetime', 'viewed_at' => 'datetime'];
}
