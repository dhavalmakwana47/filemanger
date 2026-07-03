<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogExport extends Model
{
    protected $fillable = ['user_id', 'format', 'status', 'file_path', 'error_message'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
