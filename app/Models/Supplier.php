<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'shop_id',
        'name',
        'company',
        'contact_person',
        'phone',
        'phone_dial_code',
        'alt_phone',
        'alt_phone_dial_code',
        'email',
        'website',
        'tax_number',
        'business_type',
        'address',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'opening_balance',
        'credit_limit',
        'payment_terms',
        'currency',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'opening_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
    ];

    public const BUSINESS_TYPES = [
        'manufacturer' => 'Manufacturer',
        'distributor' => 'Distributor',
        'wholesaler' => 'Wholesaler',
        'retailer' => 'Retailer',
        'importer' => 'Importer',
        'service' => 'Service Provider',
        'other' => 'Other',
    ];

    public const PAYMENT_TERMS = [
        'due_on_receipt' => 'Due on Receipt',
        'net_7' => 'Net 7',
        'net_15' => 'Net 15',
        'net_30' => 'Net 30',
        'net_45' => 'Net 45',
        'net_60' => 'Net 60',
    ];

    public const CURRENCIES = [
        'BDT' => 'Bangladeshi Taka (BDT)',
        'USD' => 'US Dollar (USD)',
        'EUR' => 'Euro (EUR)',
        'INR' => 'Indian Rupee (INR)',
    ];

    public const DIAL_CODES = [
        '+880' => ['label' => 'Bangladesh', 'flag' => '🇧🇩', 'iso' => 'BD'],
        '+91' => ['label' => 'India', 'flag' => '🇮🇳', 'iso' => 'IN'],
        '+1' => ['label' => 'USA / Canada', 'flag' => '🇺🇸', 'iso' => 'US'],
        '+44' => ['label' => 'United Kingdom', 'flag' => '🇬🇧', 'iso' => 'GB'],
        '+971' => ['label' => 'United Arab Emirates', 'flag' => '🇦🇪', 'iso' => 'AE'],
        '+966' => ['label' => 'Saudi Arabia', 'flag' => '🇸🇦', 'iso' => 'SA'],
        '+65' => ['label' => 'Singapore', 'flag' => '🇸🇬', 'iso' => 'SG'],
        '+86' => ['label' => 'China', 'flag' => '🇨🇳', 'iso' => 'CN'],
        '+60' => ['label' => 'Malaysia', 'flag' => '🇲🇾', 'iso' => 'MY'],
    ];

    public static function dialCodeKeys(): array
    {
        return array_keys(self::DIAL_CODES);
    }

    public const COUNTRIES = [
        'Bangladesh' => ['label' => 'Bangladesh', 'iso' => 'BD'],
        'India' => ['label' => 'India', 'iso' => 'IN'],
        'China' => ['label' => 'China', 'iso' => 'CN'],
        'United States' => ['label' => 'United States', 'iso' => 'US'],
        'United Kingdom' => ['label' => 'United Kingdom', 'iso' => 'GB'],
        'United Arab Emirates' => ['label' => 'United Arab Emirates', 'iso' => 'AE'],
        'Saudi Arabia' => ['label' => 'Saudi Arabia', 'iso' => 'SA'],
        'Singapore' => ['label' => 'Singapore', 'iso' => 'SG'],
        'Malaysia' => ['label' => 'Malaysia', 'iso' => 'MY'],
        'Other' => ['label' => 'Other', 'iso' => 'un'],
    ];

    public const CITIES_BY_COUNTRY = [
        'Bangladesh' => [
            'Dhaka', 'Chattogram', 'Khulna', 'Rajshahi', 'Sylhet', 'Barishal',
            'Rangpur', 'Mymensingh', 'Gazipur', 'Narayanganj', 'Comilla', 'Bogura',
        ],
        'India' => [
            'Mumbai', 'Delhi', 'Bengaluru', 'Hyderabad', 'Chennai', 'Kolkata',
            'Pune', 'Ahmedabad', 'Jaipur', 'Surat', 'Lucknow', 'Kanpur',
        ],
        'China' => [
            'Shanghai', 'Beijing', 'Guangzhou', 'Shenzhen', 'Chengdu', 'Hangzhou',
            'Wuhan', 'Xi\'an', 'Nanjing', 'Tianjin', 'Suzhou', 'Chongqing',
        ],
        'United States' => [
            'New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia',
            'San Antonio', 'San Diego', 'Dallas', 'San Jose', 'Austin', 'Seattle',
        ],
        'United Kingdom' => [
            'London', 'Birmingham', 'Manchester', 'Glasgow', 'Liverpool', 'Leeds',
            'Sheffield', 'Edinburgh', 'Bristol', 'Cardiff', 'Belfast', 'Newcastle',
        ],
        'United Arab Emirates' => [
            'Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman', 'Ras Al Khaimah', 'Fujairah', 'Umm Al Quwain',
        ],
        'Saudi Arabia' => [
            'Riyadh', 'Jeddah', 'Mecca', 'Medina', 'Dammam', 'Khobar', 'Tabuk', 'Abha',
        ],
        'Singapore' => [
            'Singapore',
        ],
        'Malaysia' => [
            'Kuala Lumpur', 'George Town', 'Johor Bahru', 'Ipoh', 'Shah Alam',
            'Petaling Jaya', 'Malacca City', 'Kota Kinabalu', 'Kuching',
        ],
        'Other' => [],
    ];

    public static function countryKeys(): array
    {
        return array_keys(self::COUNTRIES);
    }

    public static function citiesFor(string $country): array
    {
        return self::CITIES_BY_COUNTRY[$country] ?? [];
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function formattedPhone(): ?string
    {
        if (! $this->phone) {
            return null;
        }

        $dial = $this->phone_dial_code ?: '+880';

        return trim($dial.' '.$this->phone);
    }

    public function syncLegacyAddress(): void
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        $this->address = $parts ? implode(', ', $parts) : $this->address;
    }
}
