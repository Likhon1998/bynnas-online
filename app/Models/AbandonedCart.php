<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbandonedCart extends Model
{
    /**
     * active = still shopping (shown as abandoned once idle past the threshold).
     * recovered = ordered after being abandoned or contacted; converted = ordered in the same visit.
     */
    public const STATUSES = [
        'active' => 'Active',
        'contacted' => 'Contacted',
        'recovered' => 'Recovered',
        'converted' => 'Ordered',
        'lost' => 'Lost',
    ];

    public const CLOSED_STATUSES = ['recovered', 'converted', 'lost'];

    protected $fillable = [
        'shop_id',
        'token',
        'user_id',
        'customer_id',
        'name',
        'phone',
        'items',
        'item_count',
        'subtotal',
        'campaign_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'landing_page',
        'status',
        'last_activity_at',
        'contacted_at',
        'contacted_by',
        'notes',
        'order_id',
        'recovered_at',
        'notified_at',
    ];

    protected $casts = [
        'items' => 'array',
        'subtotal' => 'decimal:2',
        'last_activity_at' => 'datetime',
        'contacted_at' => 'datetime',
        'recovered_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public static function idleMinutes(): int
    {
        return max(5, (int) config('commerce.abandoned_cart_minutes', 60));
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function contactedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contacted_by');
    }

    public function scopeForShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /** Idle carts still worth recovering (not converted, not written off). */
    public function scopeAbandoned($query)
    {
        return $query->whereIn('status', ['active', 'contacted'])
            ->where('item_count', '>', 0)
            ->where('last_activity_at', '<=', now()->subMinutes(self::idleMinutes()));
    }

    public function isAbandoned(): bool
    {
        return in_array($this->status, ['active', 'contacted'], true)
            && $this->item_count > 0
            && $this->last_activity_at?->lte(now()->subMinutes(self::idleMinutes()));
    }

    public function displayStatus(): string
    {
        if ($this->status === 'active') {
            return $this->isAbandoned() ? 'Abandoned' : 'Shopping now';
        }

        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function hasContact(): bool
    {
        return filled($this->phone) || $this->customer_id !== null;
    }
}
