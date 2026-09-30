<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

class StaffPermissions
{
    /** Legacy physical-retail permissions; hidden from role editors while the retail module is off. */
    public const RETAIL = [
        'process pos sales',
        'view sales ledger',
        'manage counters',
    ];

    public const LABELS = [
        'view dashboard' => 'View dashboard',
        'manage orders' => 'Manage online orders',
        'manage customers' => 'Manage customers',
        'manage leads' => 'Manage leads & abandoned carts',
        'manage campaigns' => 'Manage campaigns & landing pages',
        'manage inventory' => 'Manage products & inventory',
        'manage website' => 'Manage website (CMS, delivery, couriers)',
        'view reports' => 'View reports & analytics',
        'manage accounts' => 'Manage accounts',
        'manage staff' => 'Manage staff',
        'manage roles' => 'Manage roles',
        'process pos sales' => 'POS: process sales',
        'view sales ledger' => 'POS: sales ledger, credit & EMI',
        'manage counters' => 'POS: manage counters',
    ];

    /** Permissions that can be assigned from the role editor right now. */
    public static function assignable(): Collection
    {
        $hidden = retail_enabled() ? [] : self::RETAIL;
        $order = array_flip(array_keys(self::LABELS));

        return Permission::where('guard_name', 'web')
            ->whereNotIn('name', $hidden)
            ->get()
            ->sortBy(fn (Permission $p) => $order[$p->name] ?? PHP_INT_MAX)
            ->values();
    }

    public static function label(string $name): string
    {
        return self::LABELS[$name] ?? ucfirst(str_replace('_', ' ', $name));
    }

    public static function isRetail(string $name): bool
    {
        return in_array($name, self::RETAIL, true);
    }
}
