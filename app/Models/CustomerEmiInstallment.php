<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerEmiInstallment extends Model
{
    protected $fillable = [
        'shop_id',
        'emi_plan_id',
        'customer_id',
        'sequence',
        'due_date',
        'amount',
        'paid_amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(CustomerEmiPlan::class, 'emi_plan_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function remaining(): float
    {
        return max(0, round((float) $this->amount - (float) $this->paid_amount, 2));
    }
}
