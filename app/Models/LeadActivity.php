<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    public const TYPES = [
        'note' => 'Note',
        'call' => 'Phone call',
        'message' => 'Message sent',
        'status' => 'Status change',
        'follow_up' => 'Follow-up scheduled',
        'conversion' => 'Converted',
        'system' => 'System',
    ];

    protected $fillable = ['lead_id', 'user_id', 'type', 'body', 'meta'];

    protected $casts = ['meta' => 'array'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }
}
