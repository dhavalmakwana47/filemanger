<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZipDownload extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'folder_name',
        'folder_data',
        'zip_path',
        'status',
        'error_message',
    ];

    protected $casts = [
        'folder_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
