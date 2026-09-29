<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    public const STATUSES = [
        'new' => 'New',
        'contacted' => 'Contacted',
        'interested' => 'Interested',
        'follow_up' => 'Follow-up',
        'converted' => 'Converted',
        'lost' => 'Lost',
    ];

    /** Where the inquiry came from. Conversations happen on the platform itself; we only keep a reference. */
    public const SOURCES = [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'whatsapp' => 'WhatsApp',
        'messenger' => 'Messenger',
        'website' => 'Website',
        'other' => 'Other',
    ];

    public const CLOSED_STATUSES = ['converted', 'lost'];

    protected $fillable = [
        'shop_id',
        'name',
        'phone',
        'email',
        'source',
        'conversation_ref',
        'campaign_id',
        'product_id',
        'landing_page_id',
        'status',
        'notes',
        'assigned_to',
        'follow_up_at',
        'last_contacted_at',
        'customer_id',
        'order_id',
        'converted_at',
        'lost_reason',
        'created_by',
    ];

    protected $casts = [
        'follow_up_at' => 'datetime',
        'last_contacted_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest('id');
    }

    public function scopeForShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', self::CLOSED_STATUSES);
    }

    public function scopeFollowUpDue($query)
    {
        return $query->open()->whereNotNull('follow_up_at')->where('follow_up_at', '<=', now());
    }

    public function isClosed(): bool
    {
        return in_array($this->status, self::CLOSED_STATUSES, true);
    }

    public function isFollowUpDue(): bool
    {
        return ! $this->isClosed() && $this->follow_up_at && $this->follow_up_at->lte(now());
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function sourceLabel(): string
    {
        return self::SOURCES[$this->source] ?? ucfirst((string) $this->source);
    }

    public static function statusBadge(string $status): string
    {
        return match ($status) {
            'new' => 'bg-amber-100 text-amber-800',
            'contacted' => 'bg-sky-100 text-sky-800',
            'interested' => 'bg-indigo-100 text-indigo-800',
            'follow_up' => 'bg-violet-100 text-violet-800',
            'converted' => 'bg-emerald-100 text-emerald-800',
            'lost' => 'bg-slate-200 text-slate-600',
            default => 'bg-slate-100 text-slate-700',
        };
    }
}
