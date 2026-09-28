<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'name',
        'is_active',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function sessions()
    {
        return $this->hasMany(CounterSession::class);
    }

    public function openSession()
    {
        return $this->hasOne(CounterSession::class)->where('status', 'open')->latestOfMany('opened_at');
    }

    /**
     * No staff member is assigned to this till (admin may use these).
     */
    public function scopeUnassigned($query)
    {
        return $query->whereDoesntHave('users');
    }

    public function isUnassigned(): bool
    {
        return ! $this->users()->exists();
    }
}
