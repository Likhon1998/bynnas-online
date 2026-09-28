<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerEmiEntry extends Model
{
    protected $fillable = [
        'shop_id',
        'customer_id',
        'emi_plan_id',
        'installment_id',
        'order_id',
        'user_id',
        'type',
        'amount',
        'method',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan()
    {
        return $this->belongsTo(CustomerEmiPlan::class, 'emi_plan_id');
    }

    public function installment()
    {
        return $this->belongsTo(CustomerEmiInstallment::class, 'installment_id');
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
}
