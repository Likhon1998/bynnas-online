<?php

namespace App\Support;

/**
 * Online order lifecycle.
 *
 * Legacy values `pending` / `pending_fulfillment` are stored on older orders and
 * are treated as NEW everywhere (no data migration required).
 */
final class OrderStatus
{
    public const NEW = 'new';
    public const CONFIRMED = 'confirmed';
    public const PROCESSING = 'processing';
    public const PACKED = 'packed';
    public const SHIPPED = 'shipped';
    public const DELIVERED = 'delivered';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';
    public const RETURN_REQUESTED = 'return_requested';
    public const RETURNED = 'returned';
    public const REFUNDED = 'refunded';

    public const LEGACY_NEW = ['pending', 'pending_fulfillment'];

    /** Main happy path, in order. */
    public const FLOW = [
        self::NEW,
        self::CONFIRMED,
        self::PROCESSING,
        self::PACKED,
        self::SHIPPED,
        self::DELIVERED,
        self::COMPLETED,
    ];

    /** Terminal states that reverse the sale (stock released, ledger reversed). */
    public const VOID = [self::CANCELLED, self::RETURNED, self::REFUNDED];

    public const VERIFICATION_METHODS = [
        'phone_call' => 'Phone call',
        'whatsapp' => 'WhatsApp',
        'messenger' => 'Messenger',
        'sms' => 'SMS',
        'in_person' => 'In person',
        'other' => 'Other',
    ];

    private const TRANSITIONS = [
        self::NEW => [self::CONFIRMED, self::CANCELLED],
        self::CONFIRMED => [self::PROCESSING, self::CANCELLED],
        self::PROCESSING => [self::PACKED, self::CANCELLED],
        self::PACKED => [self::SHIPPED, self::CANCELLED],
        self::SHIPPED => [self::DELIVERED, self::RETURNED, self::CANCELLED],
        self::DELIVERED => [self::COMPLETED, self::RETURN_REQUESTED, self::RETURNED],
        self::COMPLETED => [self::RETURN_REQUESTED, self::REFUNDED],
        self::RETURN_REQUESTED => [self::RETURNED, self::DELIVERED, self::COMPLETED],
        self::RETURNED => [self::REFUNDED],
        self::CANCELLED => [],
        self::REFUNDED => [],
    ];

    public static function all(): array
    {
        return array_keys(self::TRANSITIONS);
    }

    /** Every value that may be stored in orders.status for online orders. */
    public static function storedValues(): array
    {
        return array_merge(self::all(), self::LEGACY_NEW);
    }

    public static function normalize(?string $status): string
    {
        $status = (string) $status;

        return in_array($status, self::LEGACY_NEW, true) ? self::NEW : $status;
    }

    /** DB values that mean "new" (canonical + legacy). */
    public static function newValues(): array
    {
        return array_merge([self::NEW], self::LEGACY_NEW);
    }

    /**
     * Expand canonical statuses to include legacy stored aliases (for whereIn queries).
     *
     * @param  list<string>  $statuses
     */
    public static function expand(array $statuses): array
    {
        $out = [];
        foreach ($statuses as $status) {
            $out = array_merge($out, $status === self::NEW ? self::newValues() : [$status]);
        }

        return array_values(array_unique($out));
    }

    public static function is(?string $status, string ...$candidates): bool
    {
        return in_array(self::normalize($status), $candidates, true);
    }

    public static function isVoid(?string $status): bool
    {
        return in_array((string) $status, self::VOID, true);
    }

    /** Placed but not yet shipped. */
    public static function preShipment(): array
    {
        return [self::NEW, self::CONFIRMED, self::PROCESSING, self::PACKED];
    }

    /** Still moving through fulfilment (not delivered, not void). */
    public static function open(): array
    {
        return [self::NEW, self::CONFIRMED, self::PROCESSING, self::PACKED, self::SHIPPED];
    }

    /** Statuses where physical stock has left (or must leave) the shelf. */
    public static function stockCommitted(): array
    {
        return [self::PACKED, self::SHIPPED, self::DELIVERED, self::COMPLETED, self::RETURN_REQUESTED];
    }

    /** @return list<string> */
    public static function nextFor(?string $status): array
    {
        return self::TRANSITIONS[self::normalize($status)] ?? [];
    }

    public static function canTransition(?string $from, string $to): bool
    {
        return in_array($to, self::nextFor($from), true);
    }

    public static function labels(): array
    {
        return [
            self::NEW => 'New',
            self::CONFIRMED => 'Confirmed',
            self::PROCESSING => 'Processing',
            self::PACKED => 'Packed',
            self::SHIPPED => 'Shipped',
            self::DELIVERED => 'Delivered',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::RETURN_REQUESTED => 'Return requested',
            self::RETURNED => 'Returned',
            self::REFUNDED => 'Refunded',
        ];
    }

    public static function label(?string $status): string
    {
        $normalized = self::normalize($status);

        return self::labels()[$normalized] ?? ucfirst(str_replace('_', ' ', $normalized));
    }

    /** Customer-facing wording. */
    public static function customerLabels(): array
    {
        return [
            self::NEW => 'Received',
            self::CONFIRMED => 'Confirmed',
            self::PROCESSING => 'Preparing',
            self::PACKED => 'Packed',
            self::SHIPPED => 'In transit',
            self::DELIVERED => 'Delivered',
            self::COMPLETED => 'Delivered',
            self::CANCELLED => 'Cancelled',
            self::RETURN_REQUESTED => 'Return requested',
            self::RETURNED => 'Returned',
            self::REFUNDED => 'Refunded',
        ];
    }

    public static function customerLabel(?string $status): string
    {
        $normalized = self::normalize($status);

        return self::customerLabels()[$normalized] ?? ucfirst(str_replace('_', ' ', $normalized));
    }

    /** Customer tracker steps (several internal statuses collapse into one step). */
    public const CUSTOMER_STEPS = ['new', 'confirmed', 'processing', 'shipped', 'delivered'];

    public static function customerStep(?string $status): string
    {
        return match (self::normalize($status)) {
            self::NEW => 'new',
            self::CONFIRMED => 'confirmed',
            self::PROCESSING, self::PACKED => 'processing',
            self::SHIPPED => 'shipped',
            self::DELIVERED, self::COMPLETED, self::RETURN_REQUESTED => 'delivered',
            default => self::normalize($status),
        };
    }

    public static function customerBadgeClass(?string $status): string
    {
        if (self::normalize($status) === self::RETURN_REQUESTED) {
            return 'bg-yellow-100 text-yellow-800';
        }

        return match (self::customerStep($status)) {
            'delivered' => 'bg-emerald-100 text-emerald-700',
            'shipped' => 'bg-sky-100 text-sky-800',
            'processing' => 'bg-amber-100 text-amber-800 ring-1 ring-amber-300',
            'confirmed' => 'bg-cyan-100 text-cyan-800',
            'new' => 'bg-orange-100 text-orange-800',
            self::CANCELLED, self::RETURNED, self::REFUNDED => 'bg-rose-100 text-rose-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    /** Tailwind badge classes for admin tables. */
    public static function badgeClasses(): array
    {
        return [
            self::NEW => 'bg-amber-100 text-amber-800 border-amber-200',
            self::CONFIRMED => 'bg-cyan-100 text-cyan-800 border-cyan-200',
            self::PROCESSING => 'bg-blue-100 text-blue-800 border-blue-200',
            self::PACKED => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            self::SHIPPED => 'bg-purple-100 text-purple-800 border-purple-200',
            self::DELIVERED => 'bg-teal-100 text-teal-800 border-teal-200',
            self::COMPLETED => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::RETURN_REQUESTED => 'bg-yellow-100 text-yellow-800 border-yellow-300',
            self::CANCELLED => 'bg-orange-100 text-orange-800 border-orange-300 line-through decoration-orange-600 decoration-2 font-black',
            self::RETURNED => 'bg-red-100 text-red-800 border-red-300 line-through decoration-red-600 decoration-2 font-black',
            self::REFUNDED => 'bg-rose-100 text-rose-800 border-rose-300 line-through decoration-rose-600 decoration-2 font-black',
        ];
    }
}
