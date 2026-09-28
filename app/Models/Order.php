<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    // Allowed mass assignable attributes
    protected $fillable = [
        'shop_id', 
        'user_id', 
        'customer_id', 
        'counter_id', 
        'invoice_no',
        'offline_client_uuid',
        'total_amount',
        'discount_amount',
        'credit_amount',
        'is_baki',
        'is_emi',
        'emi_down_payment',
        'emi_months',
        'paid_amount',
        'cash_paid',
        'card_paid',
        'mobile_paid',
        'change_amount',
        'payment_method',
        'status',
        'delivery_charge',
        'delivery_zone',
        'confirmation_charge',
        'shipping_courier',
        'courier_service_id',
        'shipping_tracking_no',
        'courier_collected_at',
        'courier_collected_amount',

        // Exchange & Return tracking fields
        'is_exchange_receipt',
        'exchange_for_order_id',
        'return_product_id',
        'return_qty',
        'exchange_credit',
        'includes_sale',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'is_baki' => 'boolean',
        'is_emi' => 'boolean',
        'emi_down_payment' => 'decimal:2',
        'includes_sale' => 'boolean',
        'paid_amount' => 'decimal:2',
        'cash_paid' => 'decimal:2',
        'card_paid' => 'decimal:2',
        'mobile_paid' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'confirmation_charge' => 'decimal:2',
        'courier_collected_at' => 'datetime',
        'courier_collected_amount' => 'decimal:2',
        'exchange_credit' => 'decimal:2',
        'is_exchange_receipt' => 'boolean',
    ];

    /** Gross − discount − exchange credit (what the customer owes). */
    public function netPayable(): float
    {
        return max(0, (float) $this->total_amount - (float) ($this->discount_amount ?? 0) - (float) ($this->exchange_credit ?? 0));
    }

    /** Delivery fee charged to the customer (paid to courier — not shop revenue). */
    public function deliveryFeeAmount(): float
    {
        return max(0, round((float) ($this->delivery_charge ?? 0), 2));
    }

    /**
     * Shop merchandise portion of an online order (excludes courier delivery fee).
     * Used for ledger postings so delivery is never counted as store income.
     */
    public function shopCollectableAmount(): float
    {
        return max(0, round($this->netPayable() - $this->deliveryFeeAmount(), 2));
    }

    /** Advance already in the shop before courier remittance (confirmation charge only). */
    public function shopAdvancePaid(): float
    {
        $shop = $this->shopCollectableAmount();

        return min($shop, max(0, round((float) ($this->confirmation_charge ?? 0), 2)));
    }

    /**
     * Cash the courier collected for the shop and still owes you
     * (products only — delivery fee stays with the courier).
     */
    public function amountDueFromCourier(): float
    {
        if (! $this->isOnlineOrder()) {
            return 0.0;
        }

        if ($this->courier_collected_at) {
            return 0.0;
        }

        if (! in_array((string) $this->status, ['shipped'], true)) {
            return 0.0;
        }

        return max(0, round($this->shopCollectableAmount() - $this->shopAdvancePaid(), 2));
    }

    public function courierService()
    {
        return $this->belongsTo(CourierService::class);
    }

    /**
     * Resolve POS discount / payable / change from gross cart, exchange credit, requested discount, and cash tendered.
     * Discount applies after exchange credit. Paying under the due amount increases discount ("Less").
     *
     * @return array{discount: float, payable: float, change: float}
     */
    public static function resolvePosSettlement(
        float $grossTotal,
        float $exchangeCredit,
        float $requestedDiscount,
        float $paidAmount,
    ): array {
        $afterCredit = max(0, round($grossTotal, 2) - max(0, round($exchangeCredit, 2)));
        $paidAmount = max(0, round($paidAmount, 2));
        $discount = min($afterCredit, max(0, round($requestedDiscount, 2)));
        $payable = max(0, round($afterCredit - $discount, 2));

        if ($paidAmount < $payable) {
            $discount = min($afterCredit, round($afterCredit - $paidAmount, 2));
            $payable = $paidAmount;
        }

        return [
            'discount' => round($discount, 2),
            'payable' => round($payable, 2),
            'change' => round(max(0, $paidAmount - $payable), 2),
        ];
    }

    /**
     * Cash/card/mobile amounts that actually settled the net sale (change removed from cash).
     * Matches what was posted to accounts / kept in the till.
     *
     * @return array{cash: float, card: float, mobile: float, has_breakdown: bool, net: float}
     */
    public function settledTenderBreakdown(): array
    {
        $net = round($this->netPayable(), 2);
        $credit = max(0, round((float) ($this->credit_amount ?? 0), 2));
        // Cash actually collected for this invoice (excludes baki receivable).
        $collected = max(0, round($net - $credit, 2));

        $cash = max(0, (float) ($this->cash_paid ?? 0));
        $card = max(0, (float) ($this->card_paid ?? 0));
        $mobile = max(0, (float) ($this->mobile_paid ?? 0));
        $change = max(0, (float) ($this->change_amount ?? 0));
        $hasBreakdown = $this->hasTenderBreakdown();

        if (! $hasBreakdown) {
            return [
                'cash' => 0.0,
                'card' => 0.0,
                'mobile' => 0.0,
                'has_breakdown' => false,
                'net' => $net,
                'collected' => $collected,
                'credit' => $credit,
            ];
        }

        // Change was returned from cash only when tender amounts still include the overpay.
        // BAKI stores invoice cash only (toward bill) — do not subtract change again.
        $tenderGross = round($cash + $card + $mobile, 2);
        if ($change > 0.009 && $tenderGross + 0.05 >= $collected + $change) {
            $cash = max(0, round($cash - $change, 2));
        }
        $allocated = round($cash + $card + $mobile, 2);

        if ($allocated <= 0 && $collected > 0) {
            $cash = $collected;
        } elseif (abs($allocated - $collected) > 0.05) {
            $cash = max(0, round($collected - $card - $mobile, 2));
        }

        return [
            'cash' => round($cash, 2),
            'card' => round($card, 2),
            'mobile' => round($mobile, 2),
            'has_breakdown' => true,
            'net' => $net,
            'collected' => $collected,
            'credit' => $credit,
        ];
    }

    // The items on the receipt
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLog::class)->orderBy('created_at');
    }

    public function isOnlineOrder(): bool
    {
        return $this->counter_id === null && str_starts_with((string) $this->invoice_no, 'WEB-');
    }

    public function isCashOnDelivery(): bool
    {
        $method = strtolower(trim((string) ($this->payment_method ?? '')));

        return in_array($method, ['cash_on_delivery', 'cod', 'cash on delivery'], true)
            || str_contains($method, 'cash_on_delivery')
            || str_contains($method, 'cash on delivery');
    }

    /**
     * Amount still owed on this invoice (baki/emi credit, or unpaid online COD).
     */
    public function amountDue(): float
    {
        $credit = max(0, round((float) ($this->credit_amount ?? 0), 2));
        if ($credit > 0.009) {
            return $credit;
        }

        // Online COD / unpaid web orders: paid_amount stays 0 until delivery/collection.
        if ($this->isOnlineOrder()) {
            $net = round($this->netPayable(), 2);
            $paid = max(0, round((float) ($this->paid_amount ?? 0), 2));

            return max(0, round($net - $paid, 2));
        }

        return 0.0;
    }

    /**
     * Receipt / UI payment badge state.
     *
     * @return array{is_void: bool, is_paid: bool, is_due: bool, due_label: string, amount_due: float, amount_paid: float}
     */
    public function receiptPaymentState(): array
    {
        $isVoid = in_array((string) $this->status, ['refunded', 'cancelled', 'returned'], true);
        $amountDue = $this->amountDue();
        $amountPaid = max(0, round((float) ($this->paid_amount ?? 0), 2));

        if ($isVoid) {
            return [
                'is_void' => true,
                'is_paid' => false,
                'is_due' => false,
                'due_label' => '',
                'amount_due' => 0.0,
                'amount_paid' => 0.0,
            ];
        }

        $isDue = $amountDue > 0.009;
        $dueLabel = 'DUE';
        if ($isDue) {
            if ($this->is_emi) {
                $dueLabel = 'EMI DUE';
            } elseif ($this->is_baki) {
                $dueLabel = 'BAKI DUE';
            } elseif ($this->isOnlineOrder() && $this->isCashOnDelivery()) {
                $dueLabel = 'COD DUE';
            } elseif ($this->isOnlineOrder() && (float) ($this->confirmation_charge ?? 0) > 0.009) {
                $dueLabel = 'BALANCE DUE';
            } elseif ($this->isOnlineOrder()) {
                $dueLabel = 'PAYMENT DUE';
            }
        }

        return [
            'is_void' => false,
            'is_paid' => ! $isDue,
            'is_due' => $isDue,
            'due_label' => $dueLabel,
            'amount_due' => $amountDue,
            'amount_paid' => $amountPaid,
        ];
    }

    /** Admin / reports: online storefront orders only. */
    public function scopeOnlineOrders($query)
    {
        return $query->whereNull('counter_id')->where('invoice_no', 'like', 'WEB-%');
    }

    /**
     * Unique customer-facing online order ID (stored in invoice_no).
     * Format: WEB-{shopId}-{year}-{#####}
     */
    public static function nextWebInvoiceNo(int $shopId): string
    {
        return static::nextInvoiceNo($shopId, 'WEB');
    }

    /**
     * Unique POS invoice number. Call inside a DB transaction (uses lockForUpdate).
     * Format: INV-{shopId}-{year}-{#####} or OFF-... for offline sync.
     */
    public static function nextPosInvoiceNo(int $shopId, string $prefix = 'INV'): string
    {
        return static::nextInvoiceNo($shopId, $prefix);
    }

    protected static function nextInvoiceNo(int $shopId, string $kind): string
    {
        $year = date('Y');
        $prefix = strtoupper($kind) . '-' . $shopId . '-' . $year . '-';

        $latest = static::query()
            ->where('shop_id', $shopId)
            ->where('invoice_no', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('invoice_no');

        $seq = 1;
        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        for ($i = 0; $i < 50; $i++) {
            $candidate = $prefix . str_pad((string) ($seq + $i), 5, '0', STR_PAD_LEFT);
            if (! static::where('invoice_no', $candidate)->exists()) {
                return $candidate;
            }
        }

        return $prefix . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT) . '-' . substr(uniqid(), -4);
    }

    /** Cash portion that actually hit the till (split-tender aware). */
    public function cashTenderAmount(): float
    {
        if ($this->hasTenderBreakdown()) {
            return max(0, (float) ($this->cash_paid ?? 0));
        }

        $method = strtolower(trim((string) $this->payment_method));
        $net = $this->netPayable();

        if ($method === 'cash' || $method === '') {
            return $net;
        }

        // Legacy mixed strings without tender breakdown — do not assume all cash
        if (str_contains($method, 'cash') && (str_contains($method, 'card') || str_contains($method, 'bkash') || str_contains($method, 'nagad') || str_contains($method, 'mobile') || str_contains($method, 'bank'))) {
            return 0.0;
        }

        if (str_contains($method, 'cash')) {
            return $net;
        }

        return 0.0;
    }

    public function hasTenderBreakdown(): bool
    {
        return $this->cash_paid !== null || $this->card_paid !== null || $this->mobile_paid !== null;
    }

    public function cardTenderAmount(): float
    {
        if ($this->hasTenderBreakdown()) {
            return max(0, (float) ($this->card_paid ?? 0));
        }

        $method = strtolower(trim((string) $this->payment_method));
        if ($method === 'card' || $method === 'bank' || (
            (str_contains($method, 'card') || str_contains($method, 'bank'))
            && ! str_contains($method, '+')
            && ! str_contains($method, 'cash')
            && ! str_contains($method, 'bkash')
        )) {
            return $this->netPayable();
        }

        return 0.0;
    }

    public function mobileTenderAmount(): float
    {
        if ($this->hasTenderBreakdown()) {
            return max(0, (float) ($this->mobile_paid ?? 0));
        }

        $method = strtolower(trim((string) $this->payment_method));
        if (in_array($method, ['bkash', 'nagad', 'mobile'], true) || (
            (str_contains($method, 'bkash') || str_contains($method, 'nagad') || str_contains($method, 'mobile'))
            && ! str_contains($method, '+')
            && ! str_contains($method, 'cash')
            && ! str_contains($method, 'card')
        )) {
            return $this->netPayable();
        }

        return 0.0;
    }

    /**
     * Lines for invoice/receipt: Cash, Card/Bank, bKash.
     * Only returns methods with amount > 0.
     *
     * @return list<array{key: string, label: string, amount: float}>
     */
    public function tenderLines(): array
    {
        $lines = [];
        $cash = $this->cashTenderAmount();
        $card = $this->cardTenderAmount();
        $mobile = $this->mobileTenderAmount();

        if ($cash > 0) {
            $lines[] = ['key' => 'cash', 'label' => 'Cash', 'amount' => round($cash, 2)];
        }
        if ($card > 0) {
            $lines[] = ['key' => 'card', 'label' => 'Card / Bank', 'amount' => round($card, 2)];
        }
        if ($mobile > 0) {
            $lines[] = ['key' => 'bkash', 'label' => 'bKash / Mobile', 'amount' => round($mobile, 2)];
        }

        return $lines;
    }

    // Link the order to the Cashier (User)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Link the order to the Shop
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    // Link the order to the Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Link the order to the Counter (Hardware Terminal)
    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }

    // 🚀 NEW: Link to the specific product they returned
    public function returnProduct()
    {
        return $this->belongsTo(Product::class, 'return_product_id');
    }

    // 🚀 NEW: Link this new receipt back to the original old receipt
    public function originalOrder()
    {
        return $this->belongsTo(Order::class, 'exchange_for_order_id');
    }
}