<?php

namespace App\Support;

use App\Models\Account;

class AccountUi
{
    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'asset' => 'Asset',
            'liability' => 'Liability',
            'equity' => 'Equity',
            'income' => 'Revenue',
            'expense' => 'Expense',
            default => ucfirst($type),
        };
    }

    /** @return array{bg: string, text: string, ring: string} */
    public static function typeBadge(string $type): array
    {
        return match ($type) {
            'asset' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'ring' => 'ring-blue-100'],
            'liability' => ['bg' => 'bg-violet-50', 'text' => 'text-violet-700', 'ring' => 'ring-violet-100'],
            'equity' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'ring' => 'ring-emerald-100'],
            'income' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'ring' => 'ring-amber-100'],
            'expense' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'ring' => 'ring-rose-100'],
            default => ['bg' => 'bg-gray-50', 'text' => 'text-gray-700', 'ring' => 'ring-gray-100'],
        };
    }

    public static function groupLabel(Account $account): string
    {
        return match ($account->type) {
            'asset' => $account->counter_id ? 'Counter Cash' : 'Current Assets',
            'liability' => 'Current Liabilities',
            'equity' => "Owner's Equity",
            'income' => 'Operating Revenue',
            'expense' => 'Operating Expenses',
            default => 'General',
        };
    }

    public static function balanceTone(string $type, float $balance): string
    {
        if ($balance == 0.0) {
            return 'text-gray-900';
        }

        return match ($type) {
            'asset', 'equity', 'income' => 'text-emerald-600',
            'liability', 'expense' => 'text-rose-600',
            default => 'text-gray-900',
        };
    }
}
