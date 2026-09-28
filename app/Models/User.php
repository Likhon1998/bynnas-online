<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Counter;
use App\Models\Shop;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * Spatie roles/permissions are stored under the "web" guard.
     * Staff authenticate via the "admin" session guard but keep the same permissions.
     */
    public function getDefaultGuardName(): string
    {
        return 'web';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'avatar_path',
        'password',
        'shop_id', // Added for Nexa POS: Links the user to a shop
        'role',
        'counter_id',
        'is_suspended',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_suspended' => 'boolean',
        ];
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }

    public function customerProfile()
    {
        return $this->hasOne(Customer::class, 'user_id');
    }

    public function isStorefrontCustomer(): bool
    {
        if (in_array($this->role, ['customer', 'Customer'], true)) {
            return true;
        }

        return $this->hasRole('Customer');
    }

    /** Shop employees only — excludes website customer accounts. */
    public function scopeStaffMembers($query)
    {
        return $query
            ->where(function ($q) {
                $q->whereNull('role')
                    ->orWhereNotIn('role', ['customer', 'Customer']);
            })
            ->whereDoesntHave('roles', fn ($r) => $r->where('name', 'Customer'));
    }

    public function avatarUrl(): ?string
    {
        return public_storage_url($this->avatar_path);
    }

    public function avatarInitials(): string
    {
        $name = trim($this->name ?: 'CU');

        return strtoupper(substr($name, 0, 2));
    }

    public function isShopOwner(): bool
    {
        if (in_array($this->role, ['admin', 'shop_owner', 'Shop Owner', 'superadmin', 'Admin'], true)) {
            return true;
        }

        return $this->hasRole(['Shop Owner', 'Admin']);
    }

    /**
     * Shop Owner / Admin never work a fixed till — they oversee all counters.
     */
    public function isAdminUser(): bool
    {
        return $this->isShopOwner();
    }

    /**
     * Floor staff who sell on POS need a counter assignment.
     * Website customers never need a counter.
     */
    public function requiresCounter(): bool
    {
        if (! retail_enabled() || $this->isStorefrontCustomer()) {
            return false;
        }

        return ! $this->isAdminUser();
    }

    public function canAccessPos(): bool
    {
        if (! retail_enabled() || $this->isStorefrontCustomer()) {
            return false;
        }

        if ($this->isAdminUser()) {
            return true;
        }

        return $this->counter_id !== null;
    }

    /**
     * Floor staff with a till must open cash each calendar day.
     */
    public function requiresDailyOpeningBalance(): bool
    {
        return $this->requiresCounter() && $this->counter_id !== null;
    }

    /**
     * Floor staff may keep selling on an open till even past midnight
     * until they close it. Calendar "open today" is separate for morning reopen.
     */
    public function hasTodayOpenSession(): bool
    {
        return $this->hasOpenCounterSession();
    }

    public function hasOpenCounterSession(): bool
    {
        if ($this->isAdminUser()) {
            return $this->adminOpenPosSession() !== null;
        }

        if (! $this->counter_id) {
            return true;
        }

        return \App\Models\CounterSession::where('counter_id', $this->counter_id)
            ->where('status', 'open')
            ->exists();
    }

    /** True when this counter has an open session started on today's date. */
    public function hasCalendarDayOpenSession(): bool
    {
        if ($this->isAdminUser()) {
            $session = $this->adminOpenPosSession();

            return $session && $session->opened_at && $session->opened_at->isToday();
        }

        if (! $this->counter_id) {
            return true;
        }

        return \App\Models\CounterSession::where('counter_id', $this->counter_id)
            ->where('status', 'open')
            ->whereDate('opened_at', now()->toDateString())
            ->exists();
    }

    /**
     * Admin POS till: the open cash session this admin started (not a cashier's till).
     */
    public function adminOpenPosSession(): ?\App\Models\CounterSession
    {
        if (! $this->isAdminUser()) {
            return null;
        }

        return \App\Models\CounterSession::query()
            ->where('shop_id', $this->shop_id)
            ->where('opened_by', $this->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    /**
     * Keep admin accounts forever unassigned to any counter.
     */
    public function clearCounterIfAdmin(): void
    {
        if ($this->isAdminUser() && $this->counter_id !== null) {
            $this->forceFill(['counter_id' => null])->saveQuietly();
        }
    }
}