<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErrorLog extends Model
{
    public const SEV_CRITICAL = 'CRITICAL';
    public const SEV_WARNING = 'WARNING';
    public const SEV_INFO = 'INFO';

    protected $fillable = [
        'severity', 'message', 'file', 'line', 'trace',
        'user_id', 'url', 'method', 'is_resolved',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'line' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
