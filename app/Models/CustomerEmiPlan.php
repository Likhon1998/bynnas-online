<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerEmiPlan extends Model
{
    protected $fillable = [
        'shop_id',
        'customer_id',
        'order_id',
        'user_id',
        'principal',
        'down_payment',
        'months',
        'installment_amount',
        'total_payable',
        'paid_amount',
        'remaining_amount',
        'status',
        'started_at',
    ];

    protected $casts = [
        'principal' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'total_payable' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'started_at' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function installments()
    {
        return $this->hasMany(CustomerEmiInstallment::class, 'emi_plan_id')->orderBy('sequence');
    }

    public function entries()
    {
        return $this->hasMany(CustomerEmiEntry::class, 'emi_plan_id')->latest();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && (float) $this->remaining_amount > 0.009;
    }
}
