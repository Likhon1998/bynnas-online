<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The single way online orders are created — storefront checkout, Buy Now, campaign landing pages
 * and (later) lead conversion all go through here, so pricing, stock holds, accounting and tracking stay identical.
 */
class OrderCreationService
{
    public function __construct(
        private StockService $stock,
        private AccountService $accounts,
        private OnlineOrderTrackingService $tracking,
        private DeliveryChargeService $delivery,
    ) {}

    /**
     * @param  array{name: string, phone: string, address: string, email?: ?string}  $contact
     * @param  list<array{id: int|string, qty: int|string}>  $items
     * @param  array{zone?: ?string, payment_method?: ?string, attribution?: array, landing_page_id?: ?int, note?: ?string}  $options
     * @return array{order: Order, quote: array, message: string}
     *
     * @throws OrderCreationException
     */
    public function place(int $shopId, array $contact, array $items, ?User $user = null, array $options = []): array
    {
        $lines = $this->resolveLines($shopId, $items);
        $subtotal = array_sum(array_column($lines, 'subtotal'));
        $quote = $this->delivery->quote($subtotal, $options['zone'] ?? null, $options['payment_method'] ?? null);
        $address = trim(preg_replace('/\s+/u', ' ', (string) $contact['address']) ?? '');
        $contact['address'] = $address;

        $staffId = User::where('shop_id', $shopId)->whereIn('role', ['admin', 'shop_owner', 'Shop Owner'])->value('id');

        $order = DB::transaction(function () use ($shopId, $contact, $user, $options, $lines, $quote, $staffId) {
            $customer = $this->resolveCustomer($shopId, $contact, $user);
            $placedBy = $staffId ?? $user?->id ?? User::where('shop_id', $shopId)->value('id');

            $invoiceNo = Order::nextWebInvoiceNo($shopId);
            if ($invoiceNo === '') {
                throw new OrderCreationException('Could not generate an order ID. Please try again.');
            }

            $order = Order::create(array_merge([
                'shop_id' => $shopId,
                'user_id' => $placedBy,
                'invoice_no' => $invoiceNo,
                'customer_id' => $customer->id,
                'total_amount' => $quote['grand_total'],
                'delivery_charge' => $quote['delivery_fee'],
                'delivery_zone' => $quote['zone'],
                'delivery_name' => $contact['name'],
                'delivery_phone' => $contact['phone'],
                'delivery_address' => $contact['address'],
                'customer_note' => filled($options['note'] ?? null) ? mb_substr(trim((string) $options['note']), 0, 500) : null,
                'confirmation_charge' => $quote['confirmation_amount'],
                'paid_amount' => $quote['amount_paid_now'],
                'payment_method' => $quote['payment_method'],
                'status' => 'pending_fulfillment',
                'counter_id' => null,
                'landing_page_id' => $options['landing_page_id'] ?? null,
            ], $options['attribution'] ?? []));

            foreach ($lines as $line) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $line['product']->id,
                    'quantity' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'subtotal' => $line['subtotal'],
                ]);
            }

            $order->load('items.product');
            // Hold inventory immediately so POS/website cannot oversell COD units.
            $this->stock->reserveWebOrderStock($order, $placedBy);
            $this->accounts->postWebSale($order);
            $this->tracking->logInitialPlacement($order);

            return $order;
        });

        return ['order' => $order, 'quote' => $quote, 'message' => $this->confirmationMessage($order, $quote)];
    }

    /**
     * Price and stock-check requested items against the live catalog (client prices are never trusted).
     *
     * @return list<array{product: Product, qty: int, unit_price: float, subtotal: float}>
     *
     * @throws OrderCreationException
     */
    public function resolveLines(int $shopId, array $items): array
    {
        $quantities = [];
        foreach ($items as $item) {
            $productId = (int) ($item['id'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);
            if ($productId < 1 || $qty < 1) {
                throw new OrderCreationException('Invalid cart item.');
            }
            $quantities[$productId] = ($quantities[$productId] ?? 0) + $qty;
        }
        if ($quantities === []) {
            throw new OrderCreationException('Cart is empty or store unavailable.');
        }

        $products = Product::where('shop_id', $shopId)->whereIn('id', array_keys($quantities))->get()->keyBy('id');
        $lines = [];
        foreach ($quantities as $productId => $qty) {
            $product = $products[$productId] ?? null;
            if (! $product || $product->is_published === false) {
                throw new OrderCreationException('A product in your cart is no longer available.');
            }
            if ($product->availableStock() < $qty) {
                throw new OrderCreationException("Not enough stock for {$product->name}. Only {$product->availableStock()} left.");
            }

            $unitPrice = $product->currentPrice();
            $lines[] = ['product' => $product, 'qty' => $qty, 'unit_price' => $unitPrice, 'subtotal' => $unitPrice * $qty];
        }

        return $lines;
    }

    /**
     * Signed-in shoppers use their own customer record; guests are matched by phone number.
     *
     * @param  array{name: string, phone: string, address: string, email?: ?string}  $contact
     */
    public function resolveCustomer(int $shopId, array $contact, ?User $user = null): Customer
    {
        if ($user) {
            $customer = Customer::where('shop_id', $shopId)->where('user_id', $user->id)->first()
                ?? $this->findByPhone($shopId, $contact['phone'], guestOnly: true);
            $details = [
                'user_id' => $user->id,
                'name' => $contact['name'],
                'phone' => $contact['phone'],
                'address' => $contact['address'],
                'email' => $user->email,
            ];
            if ($customer) {
                $customer->update($details);
            } else {
                $customer = Customer::create(['shop_id' => $shopId] + $details);
            }
            $user->update(['name' => $contact['name']]);

            return $customer;
        }

        $customer = $this->findByPhone($shopId, $contact['phone']);
        if (! $customer) {
            return Customer::create([
                'shop_id' => $shopId,
                'name' => $contact['name'],
                'phone' => $contact['phone'],
                'address' => $contact['address'],
                'email' => $contact['email'] ?? null,
            ]);
        }

        // Guest details never overwrite a registered customer's profile; the order keeps its own delivery snapshot.
        if (! $customer->user_id) {
            $customer->update(['name' => $contact['name'], 'address' => $contact['address']]);
        }

        return $customer;
    }

    public function findByPhone(int $shopId, string $phone, bool $guestOnly = false): ?Customer
    {
        $normalized = Customer::normalizePhone($phone);
        if (strlen($normalized) < 8) {
            return null;
        }

        return Customer::where('shop_id', $shopId)
            ->when($guestOnly, fn ($q) => $q->whereNull('user_id'))
            ->where('phone', 'like', '%'.substr($normalized, -6))
            ->orderBy('id')
            ->get()
            ->first(fn (Customer $c) => Customer::normalizePhone($c->phone) === $normalized);
    }

    public function confirmationMessage(Order $order, array $quote): string
    {
        $payNote = $quote['payment_method'] === DeliveryChargeService::PAY_CONFIRMATION
            ? 'Confirmation charge ৳'.format_taka_number($quote['amount_paid_now']).' · balance due on delivery ৳'.format_taka_number($quote['amount_due_later'])
            : 'Cash on delivery · total due ৳'.format_taka_number($quote['grand_total']);

        return 'Order placed successfully. Your Order ID is '.$order->invoice_no.'. '.$payNote;
    }
}
