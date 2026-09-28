<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Counter;
use App\Models\Customer;
use App\Models\Shop;
use App\Models\User;
use App\Services\AccountService;
use App\Services\BakiService;
use App\Services\CounterSessionService;
use App\Services\EmiService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use InvalidArgumentException;

class PosController extends Controller
{
    public function __construct(
        protected AccountService $accounts,
        protected StockService $stock,
        protected BakiService $baki,
        protected EmiService $emi,
    ) {}
    /**
     * Load the POS Terminal
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->canAccessPos()) {
            return redirect()->route('dashboard')->with('error', 'Access Denied: You must be assigned to a specific Counter before you can access the POS terminal. Please contact your Admin.');
        }

        // Cashiers assigned to a counter must enter today's opening cash first
        if ($user->requiresDailyOpeningBalance() && ! $user->hasTodayOpenSession()) {
            return redirect()
                ->route('counters.sessions.open-today')
                ->with('error', 'Enter your opening cash for today before using the POS.');
        }

        $shopId = $user->shop_id;
        $shop = Shop::query()->find($shopId);
        $posEmiEnabled = (bool) ($shop?->pos_emi_enabled ?? true);
        $posBakiEnabled = (bool) ($shop?->pos_baki_enabled ?? true);
        $posSaleEnabled = (bool) ($shop?->pos_sale_enabled ?? true);

        $categories = Category::where('shop_id', $shopId)->orderBy('name')->get(['id', 'name', 'icon']);
        $brands = Brand::where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $brandById = $brands->keyBy('id');
        $brandsSortedByNameLength = $brands->sortByDesc(fn (Brand $b) => mb_strlen($b->name))->values();

        $website = app(\App\Services\WebsiteService::class);

        $products = Product::where('shop_id', $shopId)
            ->with(['category:id,name', 'brand:id,name', 'galleryImages', 'availableImeis'])
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($brandById, $brandsSortedByNameLength, $website, $posSaleEnabled) {
                [$brandId, $brandName] = $this->resolvePosBrand($product, $brandById, $brandsSortedByNameLength);

                $imagePath = $product->image ?: ($product->imagePaths()[0] ?? null);
                $imageUrl = $website->productImageUrl($product);

                // Prefer a verified local file URL; otherwise keep WebsiteService fallback (CDN).
                if ($imagePath) {
                    $diskPath = public_storage_path(ltrim(str_replace('\\', '/', $imagePath), '/'));
                    if (is_file($diskPath)) {
                        $imageUrl = public_storage_url($imagePath);
                    }
                }

                $listPrice = (float) $product->selling_price;
                $onSale = $posSaleEnabled && $product->isOnSale();
                $chargePrice = $onSale ? $product->currentPrice() : $listPrice;
                $availableImeis = $product->requires_imei
                    ? $product->availableImeis->pluck('imei')->values()->all()
                    : [];

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'sku' => $product->sku,
                    'color' => $product->color,
                    'ram' => $product->ram,
                    'storage' => $product->storage,
                    'selling_price' => $chargePrice,
                    'list_price' => $listPrice,
                    'on_sale' => $onSale,
                    'sale_percent' => $onSale ? $product->discountPercent() : 0,
                    'stock_quantity' => $product->availableStock(),
                    'physical_stock' => $product->physicalStock(),
                    'reserved_stock' => $product->reservedStock(),
                    'requires_imei' => (bool) $product->requires_imei,
                    'available_imeis' => $availableImeis,
                    'image' => $imagePath,
                    'image_url' => $imageUrl,
                    'category_id' => $product->category_id,
                    'brand_id' => $brandId,
                    'category_name' => $product->category?->name,
                    'brand_name' => $brandName,
                ];
            })
            ->values();

        $openSession = null;
        $posCounters = collect();
        $defaultPosCounterId = null;

        if ($user->counter_id) {
            $counter = Counter::find($user->counter_id);
            $openSession = $counter
                ? app(CounterSessionService::class)->currentOpen($counter)
                : null;
        } elseif ($user->isAdminUser()) {
            // Admin may only sell on a free till they opened themselves (with opening balance).
            $openSession = $user->adminOpenPosSession();

            if (! $openSession) {
                return redirect()
                    ->route('counters.sessions.open-today')
                    ->with('error', 'Select an unassigned counter and enter opening cash before using POS.');
            }

            $openSession->loadMissing('counter');
            $defaultPosCounterId = (int) $openSession->counter_id;
            $posCounters = collect([[
                'id' => (int) $openSession->counter_id,
                'name' => $openSession->counter?->name ?? ('Counter #'.$openSession->counter_id),
                'has_open_session' => true,
                'opened_by_admin' => true,
            ]]);
        }

        // 🚀 CATCH EXCHANGE PARAMETERS (If redirected from Sales Ledger)
        $exchangeOrder = $request->query('exchange_order');
        $returnProduct = $request->query('return_product');
        $returnQty = $request->query('return_qty');
        $credit = $request->query('credit', 0);

        return view('pos.index', compact(
            'categories',
            'brands',
            'products',
            'exchangeOrder',
            'returnProduct',
            'returnQty',
            'credit',
            'openSession',
            'posCounters',
            'defaultPosCounterId',
            'posEmiEnabled',
            'posBakiEnabled',
            'posSaleEnabled',
        ));
    }

    /**
     * Process the sale, save customer, and record stock movements
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->canAccessPos()) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction Blocked: No Counter assigned to your account.'
            ], 403);
        }

        try {
            $counterId = $this->resolvePosCounterId($user, $request->input('counter_id'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }

        if ($user->isAdminUser() && ! $user->adminOpenPosSession()) {
            return response()->json([
                'success' => false,
                'message' => 'Open an unassigned counter with opening cash before making sales.',
                'redirect' => route('counters.sessions.open-today'),
            ], 403);
        }

        if ($user->requiresDailyOpeningBalance() && ! $user->hasOpenCounterSession()) {
            return response()->json([
                'success' => false,
                'message' => 'Open your cash session before making sales.',
                'redirect' => route('counters.sessions.open-today'),
            ], 403);
        }

        $request->validate([
            'cart' => 'required|array',
            'cart.*.id' => 'required|exists:products,id',
            'cart.*.qty' => 'required|integer|min:1',
            'cart.*.imeis' => 'nullable|array',
            'cart.*.imeis.*' => 'nullable|string|max:32',
            'payment_method' => 'required|string',
            'paid_amount' => 'required|numeric|min:0',
            'cash_paid' => 'nullable|numeric|min:0',
            'card_paid' => 'nullable|numeric|min:0',
            'mobile_paid' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'is_baki' => 'nullable|boolean',
            'is_emi' => 'nullable|boolean',
            'emi_months' => 'nullable|integer|min:1|max:36',
            'emi_down_payment' => 'nullable|numeric|min:0',
            'customer_phone' => 'nullable|string',
            'customer_name' => 'nullable|string',
            'counter_id' => 'nullable|integer',
        ]);

        $isBaki = $request->boolean('is_baki');
        $isEmi = $request->boolean('is_emi');
        $shop = Shop::query()->find($user->shop_id);
        $posEmiEnabled = (bool) ($shop?->pos_emi_enabled ?? true);
        $posBakiEnabled = (bool) ($shop?->pos_baki_enabled ?? true);
        $posSaleEnabled = (bool) ($shop?->pos_sale_enabled ?? true);

        if ($isEmi && ! $posEmiEnabled) {
            return response()->json([
                'success' => false,
                'message' => 'EMI checkout is turned off for this store.',
            ], 422);
        }
        if ($isBaki && ! $posBakiEnabled) {
            return response()->json([
                'success' => false,
                'message' => 'Baki checkout is turned off for this store.',
            ], 422);
        }
        if ($isBaki && $isEmi) {
            return response()->json([
                'success' => false,
                'message' => 'BAKI and EMI cannot be used on the same sale.',
            ], 422);
        }
        if ($isBaki) {
            $request->validate([
                'customer_phone' => 'required|string|min:5',
                'customer_name' => 'required|string|min:2',
            ], [
                'customer_phone.required' => 'Mobile number is required for BAKI sales.',
                'customer_name.required' => 'Customer name is required for BAKI sales.',
            ]);
        }
        if ($isEmi) {
            $request->validate([
                'customer_phone' => 'required|string|min:5',
                'customer_name' => 'required|string|min:2',
                'emi_months' => 'required|integer|min:1|max:36',
                'emi_down_payment' => 'nullable|numeric|min:0',
            ], [
                'customer_phone.required' => 'Mobile number is required for EMI sales.',
                'customer_name.required' => 'Customer name is required for EMI sales.',
                'emi_months.required' => 'Enter EMI months (1–36).',
            ]);
        }

        $shopId = $user->shop_id;

        try {
            DB::beginTransaction();

            $totalAmount = 0;
            $chargeTotal = 0;
            $includesSale = false;

            // 1. Subtotal at list price; charge uses product discount / sale when enabled
            foreach ($request->cart as $item) {
                $product = Product::where('shop_id', $shopId)->findOrFail($item['id']);
                if ($product->availableStock() < $item['qty']) {
                    throw new \Exception("Not enough stock for {$product->name}");
                }
                if ($product->requires_imei) {
                    $imeis = collect($item['imeis'] ?? [])
                        ->map(fn ($v) => ProductImei::normalize((string) $v))
                        ->filter()
                        ->unique()
                        ->values();
                    if ($imeis->count() !== (int) $item['qty']) {
                        throw new \Exception("Select {$item['qty']} IMEI(s) for {$product->name}.");
                    }
                }
                $qty = (int) $item['qty'];
                $list = (float) $product->selling_price;
                $onSale = $posSaleEnabled && $product->isOnSale();
                $charge = $onSale ? $product->currentPrice() : $list;
                if ($onSale) {
                    $includesSale = true;
                }
                $totalAmount += $list * $qty;
                $chargeTotal += $charge * $qty;
            }
            $totalAmount = round($totalAmount, 2);
            $chargeTotal = round($chargeTotal, 2);
            $autoSaleDiscount = max(0, round($totalAmount - $chargeTotal, 2));

            // Sale / product discounts may be combined with Baki or EMI (financed on net bill).

            // 🚀 EXCHANGE MATH & SECURITY
            $isExchange = $request->is_exchange ?? false;
            $exchangeCredit = (float) ($request->exchange_credit ?? 0);

            if ($isExchange && $chargeTotal < $exchangeCredit) {
                throw new \Exception("Exchange Blocked: Cart total must equal or exceed the return credit. No cash refunds allowed.");
            }

            $paidAmountInput = max(0, (float) $request->paid_amount);
            $requestedDiscount = max(0, (float) ($request->discount_amount ?? 0));
            // Keep timed sale savings in the discount even if client under-reports.
            if ($autoSaleDiscount > 0) {
                $requestedDiscount = max($requestedDiscount, $autoSaleDiscount);
            }
            $exchangeForMath = $isExchange ? $exchangeCredit : 0.0;

            $creditAmount = 0.0;
            $towardPrevious = 0.0;
            $previousBalance = 0.0;
            $bakiPool = null;
            $emiDownPayment = 0.0;
            $emiMonths = 0;
            $emiPrincipal = 0.0;

            if ($isBaki) {
                // Explicit discount only — shortfall becomes baki, not "Less" discount.
                $afterCredit = max(0, round((float) $totalAmount - $exchangeForMath, 2));
                $discountAmount = min($afterCredit, $requestedDiscount);
                $billPayable = round($afterCredit - $discountAmount, 2);
                // Customer resolved below before pool; placeholder until then.
                $payableAmount = $billPayable;
                $changeAmount = 0.0;
                $paidAmount = $paidAmountInput;
            } elseif ($isEmi) {
                if ($isExchange) {
                    throw new \Exception('EMI cannot be combined with exchange sales.');
                }
                $afterCredit = max(0, round((float) $totalAmount - $exchangeForMath, 2));
                $discountAmount = min($afterCredit, $requestedDiscount);
                $billPayable = round($afterCredit - $discountAmount, 2);
                $emiMonths = (int) $request->input('emi_months', 3);
                $emiDownPayment = min($billPayable, max(0, round((float) $request->input('emi_down_payment', 0), 2)));
                $emiPrincipal = round($billPayable - $emiDownPayment, 2);
                if ($emiPrincipal <= 0) {
                    throw new \Exception('EMI financed amount must be greater than zero. Lower the down payment.');
                }
                if ($paidAmountInput + 0.009 < $emiDownPayment) {
                    throw new \Exception('Pay at least the EMI down payment (৳'.format_taka_number($emiDownPayment).').');
                }
                $creditAmount = $emiPrincipal;
                $paidAmount = $emiDownPayment;
                $changeAmount = max(0, round($paidAmountInput - $emiDownPayment, 2));
                $payableAmount = $billPayable;
            } else {
                $settlement = Order::resolvePosSettlement(
                    (float) $totalAmount,
                    $exchangeForMath,
                    $requestedDiscount,
                    $paidAmountInput,
                );
                $discountAmount = $settlement['discount'];
                $payableAmount = $settlement['payable'];
                $changeAmount = $settlement['change'];
                $paidAmount = $paidAmountInput;
            }

            // 2. Customer Handling (same CRM table as website shoppers — match by phone)
            $customerId = null;
            $customer = null;
            if (! empty($request->customer_phone)) {
                $phone = Customer::normalizePhone($request->customer_phone);
                $customer = Customer::where('shop_id', $shopId)->wherePhone($phone)->lockForUpdate()->first();

                if ($customer) {
                    $updates = [];
                    if (! empty($request->customer_name) && $customer->name !== $request->customer_name) {
                        $updates['name'] = $request->customer_name;
                    }
                    if ($customer->phone !== $phone) {
                        $updates['phone'] = $phone;
                    }
                    if ($updates !== []) {
                        $customer->update($updates);
                    }
                    $customerId = $customer->id;
                } else {
                    $customer = Customer::create([
                        'shop_id' => $shopId,
                        'phone' => $phone,
                        'name' => $request->customer_name ?? 'Guest User',
                        'baki_balance' => 0,
                    ]);
                    $customerId = $customer->id;
                }
            }

            if ($isBaki) {
                if (! $customer) {
                    throw new \Exception('Customer name and mobile are required for BAKI.');
                }
                $previousBalance = round((float) $customer->baki_balance, 2);
                $bakiPool = $this->baki->resolvePool($previousBalance, $payableAmount, $paidAmountInput);
                $creditAmount = $bakiPool['credit'];
                $towardPrevious = $bakiPool['toward_previous'];
                $paidAmount = $bakiPool['toward_bill']; // amount applied to this invoice
                $changeAmount = $bakiPool['change'];
                $payableAmount = $bakiPool['bill'];
            }

            if ($isEmi && ! $customer) {
                throw new \Exception('Customer name and mobile are required for EMI.');
            }

            // 3. Unique invoice inside the open transaction (locked)
            $invoiceNo = Order::nextPosInvoiceNo($shopId, 'INV');

            $rawCash = $request->has('cash_paid') ? max(0, (float) $request->cash_paid) : 0.0;
            $rawCard = $request->has('card_paid') ? max(0, (float) $request->card_paid) : 0.0;
            $rawMobile = $request->has('mobile_paid') ? max(0, (float) $request->mobile_paid) : 0.0;
            $hasTenderBreakdown = $request->has('cash_paid') || $request->has('card_paid') || $request->has('mobile_paid');

            $cashPaid = null;
            $cardPaid = null;
            $mobilePaid = null;
            $prevCash = $prevCard = $prevMobile = 0.0;

            if ($isBaki && $hasTenderBreakdown) {
                $billSplit = $this->baki->splitTenders($paidAmount, $rawCash, $rawCard, $rawMobile);
                $cashPaid = $billSplit['cash'];
                $cardPaid = $billSplit['card'];
                $mobilePaid = $billSplit['mobile'];
                $prevSplit = $this->baki->splitTenders(
                    $towardPrevious,
                    $billSplit['remaining_cash'],
                    $billSplit['remaining_card'],
                    $billSplit['remaining_mobile'],
                );
                $prevCash = $prevSplit['cash'];
                $prevCard = $prevSplit['card'];
                $prevMobile = $prevSplit['mobile'];
            } elseif ($isEmi && $hasTenderBreakdown) {
                // Only the down payment is collected on this invoice.
                $billSplit = $this->baki->splitTenders($paidAmount, $rawCash, $rawCard, $rawMobile);
                $cashPaid = $billSplit['cash'];
                $cardPaid = $billSplit['card'];
                $mobilePaid = $billSplit['mobile'];
            } elseif ($hasTenderBreakdown) {
                $cashPaid = $rawCash;
                $cardPaid = $rawCard;
                $mobilePaid = $rawMobile;
            }

            $clientUuid = trim((string) $request->input('client_uuid', ''));
            if ($clientUuid !== '') {
                $existing = Order::where('shop_id', $shopId)
                    ->where('offline_client_uuid', $clientUuid)
                    ->first();
                if ($existing) {
                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'order_id' => $existing->id,
                        'invoice_no' => $existing->invoice_no,
                        'change' => (float) $existing->change_amount,
                        'total_amount' => (float) $existing->total_amount,
                        'duplicate' => true,
                    ]);
                }
            }

            // 4. Create the Main Order
            $order = Order::create([
                'shop_id' => $shopId,
                'user_id' => $user->id,
                'counter_id' => $counterId,
                'customer_id' => $customerId,
                'invoice_no' => $invoiceNo,
                'offline_client_uuid' => $clientUuid !== '' ? $clientUuid : null,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'credit_amount' => $creditAmount,
                'is_baki' => $isBaki && ($creditAmount > 0 || $towardPrevious > 0 || $previousBalance > 0),
                'is_emi' => $isEmi && $emiPrincipal > 0,
                'emi_down_payment' => $isEmi ? $emiDownPayment : 0,
                'emi_months' => $isEmi ? $emiMonths : null,
                'paid_amount' => $paidAmount,
                'cash_paid' => $cashPaid,
                'card_paid' => $cardPaid,
                'mobile_paid' => $mobilePaid,
                'change_amount' => $changeAmount,
                'payment_method' => $request->payment_method,
                'status' => 'completed',

                'is_exchange_receipt' => $isExchange,
                'exchange_for_order_id' => $request->exchange_for_order_id,

                'return_product_id' => $isExchange ? $request->return_product_id : null,
                'return_qty' => $isExchange ? $request->return_qty : null,
                'exchange_credit' => $isExchange ? $exchangeCredit : null,
                'includes_sale' => $includesSale,
            ]);

            // 5. Save Items & Decrease Inventory
            // 5. Create Order Items + reduce stock
            $touchedProductIds = [];
            foreach ($request->cart as $item) {
                $product = Product::where('shop_id', $shopId)->findOrFail($item['id']);
                $list = (float) $product->selling_price;
                $subtotal = $list * $item['qty'];

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['qty'],
                    'unit_price' => $list,
                    'subtotal' => $subtotal,
                ]);

                if ($product->requires_imei) {
                    $imeis = collect($item['imeis'] ?? [])
                        ->map(fn ($v) => ProductImei::normalize((string) $v))
                        ->filter()
                        ->unique()
                        ->values();
                    if ($imeis->count() !== (int) $item['qty']) {
                        throw new \Exception("Select {$item['qty']} IMEI(s) for {$product->name}.");
                    }
                    foreach ($imeis as $imei) {
                        $row = ProductImei::query()
                            ->where('product_id', $product->id)
                            ->where('imei', $imei)
                            ->available()
                            ->lockForUpdate()
                            ->first();
                        if (! $row) {
                            throw new \Exception("IMEI {$imei} is not available for {$product->name}.");
                        }
                        $row->update([
                            'status' => ProductImei::STATUS_SOLD,
                            'order_id' => $order->id,
                            'order_item_id' => $orderItem->id,
                        ]);
                    }
                }

                $this->stock->recordSale(
                    $product,
                    (int) $item['qty'],
                    'Sale - ' . $invoiceNo,
                    $user->id,
                    'order',
                    $order->id,
                );
                $touchedProductIds[] = (int) $product->id;
            }

            // 6. Restock returned item on exchange
            if ($isExchange && $request->return_product_id) {
                $returnProduct = Product::where('shop_id', $shopId)->find($request->return_product_id);
                if ($returnProduct && (int) $request->return_qty > 0) {
                    $this->stock->restockForDocument(
                        $returnProduct,
                        (int) $request->return_qty,
                        'Exchange return for ' . $invoiceNo,
                        'exchange_return',
                        $order->id,
                        'exchange_return',
                        $user->id,
                    );
                    $touchedProductIds[] = (int) $returnProduct->id;
                }
            }

            $order->load('items.product', 'counter', 'customer');
            $this->accounts->postOrderSale($order);

            if ($isBaki && $customer) {
                if ($creditAmount > 0) {
                    $this->baki->addSaleCredit($customer, $order, $creditAmount, $user->id);
                }
                if ($towardPrevious > 0) {
                    $method = $request->payment_method ?: 'cash';
                    $this->baki->collectPayment(
                        customer: $customer,
                        amount: $towardPrevious,
                        method: $method,
                        note: 'Paid with sale '.$invoiceNo,
                        order: $order,
                        userId: $user->id,
                        counterId: $counterId,
                        cash: $hasTenderBreakdown ? $prevCash : null,
                        card: $hasTenderBreakdown ? $prevCard : null,
                        mobile: $hasTenderBreakdown ? $prevMobile : null,
                    );
                }
            }

            $emiPlan = null;
            if ($isEmi && $customer && $emiPrincipal > 0) {
                $emiPlan = $this->emi->createPlanFromSale(
                    customer: $customer,
                    order: $order,
                    principal: $emiPrincipal,
                    downPayment: $emiDownPayment,
                    months: $emiMonths,
                    userId: $user->id,
                );
            }

            DB::commit();

            $customer?->refresh();

            $stockUpdates = Product::where('shop_id', $shopId)
                ->whereIn('id', array_values(array_unique($touchedProductIds)))
                ->with('availableImeis')
                ->get()
                ->map(fn (Product $p) => [
                    'id' => (int) $p->id,
                    'stock_quantity' => (int) $p->availableStock(),
                    'physical_stock' => (int) $p->physicalStock(),
                    'reserved_stock' => (int) $p->reservedStock(),
                    'available_imeis' => $p->requires_imei
                        ? $p->availableImeis->pluck('imei')->values()->all()
                        : [],
                ])
                ->values()
                ->all();

            return response()->json([
                'success' => true,
                'message' => 'Payment completed successfully!',
                'order_id' => $order->id,
                'invoice_no' => $order->invoice_no,
                'change' => $order->change_amount,
                'paid_amount' => $order->paid_amount,
                'total_amount' => $order->total_amount,
                'discount_amount' => $order->discount_amount,
                'credit_amount' => (float) $order->credit_amount,
                'is_emi' => (bool) $order->is_emi,
                'emi_plan_id' => $emiPlan?->id,
                'emi_months' => $order->emi_months,
                'emi_down_payment' => (float) ($order->emi_down_payment ?? 0),
                'emi_balance' => $customer ? (float) $customer->emi_balance : 0,
                'baki_balance' => $customer ? (float) $customer->baki_balance : 0,
                'receipt_url' => route('pos.receipt', $order),
                'stock_updates' => $stockUpdates,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Look up existing customer by phone number
     */
    public function lookupCustomer(Request $request)
    {
        $shopId = Auth::user()->shop_id;
        $phone = $request->phone;

        if (!$phone) {
            return response()->json(['found' => false]);
        }

        $customer = Customer::where('shop_id', $shopId)
            ->wherePhone($phone)
            ->first();

        if ($customer) {
            return response()->json([
                'found' => true,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'baki_balance' => (float) ($customer->baki_balance ?? 0),
                'emi_balance' => (float) ($customer->emi_balance ?? 0),
            ]);
        }

        return response()->json(['found' => false]);
    }

    /**
     * --- PWA FEATURE: Bulk Sync Offline Orders ---
     */
    public function syncOffline(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->canAccessPos()) {
            return response()->json(['success' => false, 'message' => 'POS access denied. Sync blocked.'], 403);
        }

        try {
            $fallbackCounterId = $this->resolvePosCounterId($user, $request->input('counter_id'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }

        if ($user->isAdminUser() && ! $user->adminOpenPosSession()) {
            return response()->json(['success' => false, 'message' => 'Open an unassigned counter with opening cash before syncing sales.', 'redirect' => route('counters.sessions.open-today')], 403);
        }

        if ($user->requiresDailyOpeningBalance() && ! $user->hasOpenCounterSession()) {
            return response()->json(['success' => false, 'message' => 'Open your cash session before syncing sales.'], 403);
        }

        $shopId = $user->shop_id;
        $userId = $user->id;
        
        $orders = $request->input('orders', []);
        $syncedCount = 0;

        try {
            DB::beginTransaction();

            foreach ($orders as $offlineOrder) {
                // BAKI requires live balance locking — never sync offline credit sales.
                if (! empty($offlineOrder['is_baki'])) {
                    continue;
                }

                $clientUuid = isset($offlineOrder['client_uuid'])
                    ? trim((string) $offlineOrder['client_uuid'])
                    : '';

                if ($clientUuid !== '') {
                    $existing = Order::where('shop_id', $shopId)
                        ->where('offline_client_uuid', $clientUuid)
                        ->first();
                    if ($existing) {
                        $syncedCount++;
                        continue;
                    }
                }

                // 1. Customer Handling (shared with website shoppers)
                $customerId = null;
                if (!empty($offlineOrder['customer_phone'])) {
                    $phone = Customer::normalizePhone($offlineOrder['customer_phone']);
                    $customer = Customer::where('shop_id', $shopId)->wherePhone($phone)->first();

                    if ($customer) {
                        if (!empty($offlineOrder['customer_name']) && $customer->name !== $offlineOrder['customer_name']) {
                            $customer->update(['name' => $offlineOrder['customer_name'], 'phone' => $phone]);
                        } elseif ($customer->phone !== $phone) {
                            $customer->update(['phone' => $phone]);
                        }
                        $customerId = $customer->id;
                    } else {
                        $newCustomer = Customer::create([
                            'shop_id' => $shopId,
                            'phone' => $phone,
                            'name' => $offlineOrder['customer_name'] ?? 'Guest User',
                        ]);
                        $customerId = $newCustomer->id;
                    }
                }

                $counterId = $fallbackCounterId;
                if (! empty($offlineOrder['counter_id'])) {
                    try {
                        $counterId = $this->resolvePosCounterId($user, $offlineOrder['counter_id']);
                    } catch (InvalidArgumentException $e) {
                        // Keep request-level counter.
                    }
                }

                // 2. Unique offline invoice (locked inside outer transaction)
                $invoiceNo = Order::nextPosInvoiceNo($shopId, 'OFF');

                // 3. Create Order — list price subtotal + sale discount (same as online POS)
                $lineGross = 0.0;
                $chargeGross = 0.0;
                $includesSale = false;
                $posSaleEnabled = (bool) (Shop::query()->find($shopId)?->pos_sale_enabled ?? true);
                $resolvedItems = [];
                foreach (($offlineOrder['items'] ?? []) as $item) {
                    $product = Product::where('shop_id', $shopId)->find($item['id'] ?? 0);
                    if (! $product) {
                        throw new \Exception('Product not found for offline sync item.');
                    }
                    $qty = max(1, (int) ($item['qty'] ?? 0));
                    if ($product->availableStock() < $qty) {
                        throw new \Exception("Insufficient stock for {$product->name} during offline sync.");
                    }
                    $list = (float) $product->selling_price;
                    $onSale = $posSaleEnabled && $product->isOnSale();
                    $charge = $onSale ? $product->currentPrice() : $list;
                    if ($onSale) {
                        $includesSale = true;
                    }
                    $subtotal = $list * $qty;
                    $lineGross += $subtotal;
                    $chargeGross += $charge * $qty;
                    $resolvedItems[] = [
                        'product' => $product,
                        'qty' => $qty,
                        'subtotal' => $subtotal,
                        'unit_price' => $list,
                    ];
                }

                if ($resolvedItems === []) {
                    throw new \Exception('Offline order has no items.');
                }

                $lineGross = round($lineGross, 2);
                $autoSaleDiscount = max(0, round($lineGross - $chargeGross, 2));
                $paid = max(0, (float) ($offlineOrder['paid_amount'] ?? ($lineGross - $autoSaleDiscount)));
                $exchangeCredit = max(0, (float) ($offlineOrder['exchange_credit'] ?? 0));
                $requestedDiscount = max(0, (float) ($offlineOrder['discount_amount'] ?? 0));
                if ($autoSaleDiscount > 0) {
                    $requestedDiscount = max($requestedDiscount, $autoSaleDiscount);
                }
                $settlement = Order::resolvePosSettlement(
                    $lineGross,
                    $exchangeCredit,
                    $requestedDiscount,
                    $paid,
                );
                $discount = $settlement['discount'];
                $payable = $settlement['payable'];
                $changeAmount = $settlement['change'];

                $cashPaid = array_key_exists('cash_paid', $offlineOrder) ? max(0, (float) $offlineOrder['cash_paid']) : null;
                $cardPaid = array_key_exists('card_paid', $offlineOrder) ? max(0, (float) $offlineOrder['card_paid']) : null;
                $mobilePaid = array_key_exists('mobile_paid', $offlineOrder) ? max(0, (float) $offlineOrder['mobile_paid']) : null;

                $order = Order::create([
                    'shop_id' => $shopId,
                    'user_id' => $userId,
                    'counter_id' => $counterId,
                    'customer_id' => $customerId,
                    'invoice_no' => $invoiceNo,
                    'offline_client_uuid' => $clientUuid !== '' ? $clientUuid : null,
                    'total_amount' => $lineGross,
                    'discount_amount' => $discount,
                    'paid_amount' => $paid,
                    'cash_paid' => $cashPaid,
                    'card_paid' => $cardPaid,
                    'mobile_paid' => $mobilePaid,
                    'change_amount' => $changeAmount,
                    'payment_method' => $offlineOrder['payment_method'] ?? 'cash',
                    'status' => 'completed',
                    'exchange_credit' => $exchangeCredit > 0 ? $exchangeCredit : null,
                    'includes_sale' => $includesSale,
                    'created_at' => Carbon::parse($offlineOrder['created_at'] ?? now()),
                    'updated_at' => Carbon::parse($offlineOrder['created_at'] ?? now()),
                ]);

                // 4. Save Items and Deduct Stock
                foreach ($resolvedItems as $line) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $line['product']->id,
                        'quantity' => $line['qty'],
                        'unit_price' => $line['unit_price'],
                        'subtotal' => $line['subtotal'],
                    ]);

                    $this->stock->recordSale(
                        $line['product'],
                        (int) $line['qty'],
                        'Offline sync - '.$invoiceNo,
                        $userId,
                        'order',
                        $order->id,
                    );
                }
                $syncedCount++;

                $order->load('items.product', 'counter');
                $this->accounts->postOrderSale($order);
            }

            DB::commit();
            return response()->json(['success' => true, 'synced' => $syncedCount]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Staff use their assigned till. Admins only sell on a free till they opened themselves.
     */
    protected function resolvePosCounterId(User $user, mixed $requestedCounterId = null): int
    {
        if ($user->counter_id) {
            return (int) $user->counter_id;
        }

        if (! $user->isAdminUser()) {
            throw new InvalidArgumentException('No counter assigned to your account.');
        }

        $adminSession = $user->adminOpenPosSession();
        if (! $adminSession) {
            throw new InvalidArgumentException(
                'Select an unassigned counter and enter opening cash before selling as admin.'
            );
        }

        $counterId = (int) ($requestedCounterId ?: $adminSession->counter_id);
        if ($counterId > 0 && $counterId !== (int) $adminSession->counter_id) {
            throw new InvalidArgumentException(
                'You can only sell on the unassigned counter you opened. Close it first to switch tills.'
            );
        }

        $session = app(CounterSessionService::class)->currentOpen(
            Counter::where('shop_id', $user->shop_id)->findOrFail($adminSession->counter_id)
        );

        if (! $session || (int) $session->opened_by !== (int) $user->id) {
            throw new InvalidArgumentException(
                'You can only sell on an unassigned counter that you opened with your own opening cash.'
            );
        }

        $counter = Counter::where('shop_id', $user->shop_id)->find($adminSession->counter_id);
        if ($counter && ! $counter->isUnassigned()) {
            throw new InvalidArgumentException(
                'That counter is now assigned to staff. Close this session and use an unassigned counter.'
            );
        }

        return (int) $adminSession->counter_id;
    }

    /**
     * Resolve brand for POS filters: linked brand, brand_name text, or brand name in product title.
     *
     * @param  \Illuminate\Support\Collection<int, Brand>  $brandById
     * @param  \Illuminate\Support\Collection<int, Brand>  $brandsSortedByNameLength
     * @return array{0: int|null, 1: string|null}
     */
    protected function resolvePosBrand(Product $product, $brandById, $brandsSortedByNameLength): array
    {
        if ($product->brand_id && $brandById->has($product->brand_id)) {
            $brand = $brandById->get($product->brand_id);

            return [(int) $brand->id, $brand->name];
        }

        if ($product->brand && $product->brand->name) {
            return [(int) $product->brand->id, $product->brand->name];
        }

        $explicitName = trim((string) ($product->brand_name ?? ''));
        if ($explicitName !== '') {
            $match = $brandsSortedByNameLength->first(
                fn (Brand $b) => strcasecmp($b->name, $explicitName) === 0
            );
            if ($match) {
                return [(int) $match->id, $match->name];
            }

            return [null, $explicitName];
        }

        $productName = trim((string) $product->name);
        if ($productName !== '') {
            foreach ($brandsSortedByNameLength as $brand) {
                $brandName = trim($brand->name);
                if ($brandName === '') {
                    continue;
                }
                if (preg_match('/^'.preg_quote($brandName, '/').'(?:\b|[\s\-_])/iu', $productName)) {
                    return [(int) $brand->id, $brand->name];
                }
            }
        }

        return [null, null];
    }
}