<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    public const TYPE_STOCK_ALERT = 'STOCK_ALERT';
    public const TYPE_TRANSFER = 'TRANSFER';
    public const TYPE_IMPORT = 'IMPORT';
    public const TYPE_REPORT = 'REPORT';
    public const TYPE_SYSTEM = 'SYSTEM';

    public const SEV_CRITICAL = 'CRITICAL';
    public const SEV_WARNING = 'WARNING';
    public const SEV_INFO = 'INFO';

    protected $fillable = [
        'user_id', 'type', 'severity', 'title', 'message', 'data', 'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markRead(): void
    {
        if (! $this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }
}
