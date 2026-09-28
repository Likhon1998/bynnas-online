<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountEntry extends Model
{
    protected $fillable = [
        'account_transaction_id', 'account_id', 'counter_id', 'entry_type', 'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function transaction()
    {
        return $this->belongsTo(AccountTransaction::class, 'account_transaction_id');
    }

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }
}
