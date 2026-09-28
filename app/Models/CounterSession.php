<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CounterSession extends Model
{
    protected $fillable = [
        'shop_id',
        'counter_id',
        'opened_by',
        'closed_by',
        'opened_at',
        'closed_at',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'variance',
        'order_count',
        'total_sales',
        'cash_sales',
        'card_sales',
        'mobile_sales',
        'cash_refunds',
        'status',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'variance' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'cash_sales' => 'decimal:2',
        'card_sales' => 'decimal:2',
        'mobile_sales' => 'decimal:2',
        'cash_refunds' => 'decimal:2',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }

    public function opener()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}
