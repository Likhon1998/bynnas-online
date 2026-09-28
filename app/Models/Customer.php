<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'user_id',
        'name',
        'email',
        'phone',
        'phone_country_code',
        'address',
        'date_of_birth',
        'gender',
        'reward_points',
        'baki_balance',
        'emi_balance',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'baki_balance' => 'decimal:2',
            'emi_balance' => 'decimal:2',
        ];
    }

    public function bakiEntries()
    {
        return $this->hasMany(CustomerBakiEntry::class)->latest();
    }

    public function emiPlans()
    {
        return $this->hasMany(CustomerEmiPlan::class)->latest();
    }

    public function emiEntries()
    {
        return $this->hasMany(CustomerEmiEntry::class)->latest();
    }

    // Relationship to the Shop
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Match phone allowing formatting differences (spaces, +, dashes). */
    public function scopeWherePhone($query, ?string $phone)
    {
        $raw = trim((string) $phone);
        if ($raw === '') {
            return $query->whereRaw('1 = 0');
        }

        $digits = preg_replace('/\D+/', '', $raw) ?: $raw;

        return $query->where(function ($q) use ($raw, $digits) {
            $q->where('phone', $raw)->orWhere('phone', $digits);

            if (strlen($digits) >= 10) {
                $tail = substr($digits, -10);
                $q->orWhere('phone', 'like', '%'.$tail);
            } elseif (strlen($digits) >= 9) {
                $q->orWhere('phone', 'like', '%'.$digits);
            }
        });
    }

    public static function normalizePhone(?string $phone): string
    {
        $raw = trim((string) $phone);

        return preg_replace('/\D+/', '', $raw) ?: $raw;
    }
}