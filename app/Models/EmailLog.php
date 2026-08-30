<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EmailLog extends Model
{
    protected $fillable = [
        'user_id', 'type', 'subject', 'recipient', 'bcc', 'status',
        'related_type', 'related_id', 'queued_at', 'sent_at', 'error',
    ];

    protected function casts(): array
    {
        return ['queued_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
