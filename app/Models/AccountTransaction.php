<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountTransaction extends Model
{
    protected $fillable = [
        'shop_id', 'user_id', 'transaction_no', 'type',
        'reference_type', 'reference_id', 'description', 'transaction_date',
    ];

    protected $casts = [
        'transaction_date' => 'date',
    ];

    public function entries()
    {
        return $this->hasMany(AccountEntry::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
