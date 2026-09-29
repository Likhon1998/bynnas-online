<?php

namespace Tests\Feature;

use App\Models\AbandonedCart;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\StaffAlert;
use App\Services\AbandonedCartService;
use App\Services\CommerceAlertService;
use App\Services\OrderCreationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbandonedCartTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/120 Mobile Safari/537.36';

    private Shop $shop;

    private User $admin;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->shop = Shop::create(['name' => 'Test Shop', 'is_active' => true]);
        $this->admin = User::factory()->create();
        $this->admin->forceFill(['shop_id' => $this->shop->id, 'role' => 'admin'])->save();
        $this->admin->assignRole('Admin');
        $this->product = Product::create([
            'shop_id' => $this->shop->id, 'name' => 'Leather Bag', 'barcode' => 'BAG-1',
            'cost_price' => 800, 'selling_price' => 1500, 'stock_quantity' => 10, 'is_published' => true,
        ]);
    }

    private function sync(array $payload, ?string $token = null, ?User $as = null)
    {
        $request = $this->withHeader('User-Agent', self::BROWSER)->withCredentials();
        if ($token) {
            $request = $request->withCookie(AbandonedCartService::COOKIE, $token);
        }
        if ($as) {
            $request = $request->actingAs($as, 'web');
        }

        return $request->postJson(route('website.cart.sync'), $payload)->assertOk();
    }

    private function buyer(): User
    {
        $buyer = User::factory()->create(['email' => 'buyer@example.com']);
        $buyer->forceFill(['shop_id' => $this->shop->id])->save();
        $buyer->assignRole('Customer');
        Customer::create(['shop_id' => $this->shop->id, 'user_id' => $buyer->id, 'name' => 'Rima', 'phone' => '01711000000', 'address' => 'Dhanmondi']);

        return $buyer;
    }

    public function test_cart_sync_records_items_contact_and_attribution(): void
    {
        $this->withHeader('User-Agent', self::BROWSER)->get('/shop?utm_source=instagram&utm_medium=story&utm_campaign=winter')->assertOk();

        $response = $this->withHeader('User-Agent', self::BROWSER)
            ->postJson(route('website.cart.sync'), ['items' => [['id' => $this->product->id, 'qty' => 2]]])
            ->assertOk()
            ->assertJsonPath('items.0.qty', 2);

        $token = $response->getCookie(AbandonedCartService::COOKIE, false)?->getValue();
        $this->assertNotEmpty($token);

        $cart = AbandonedCart::firstOrFail();
        $this->assertSame($this->shop->id, $cart->shop_id);
        $this->assertSame(2, $cart->item_count);
        $this->assertEqualsWithDelta(3000, (float) $cart->subtotal, 0.01);
        $this->assertSame('Leather Bag', $cart->items[0]['name']);
        $this->assertSame('instagram', $cart->utm_source);
        $this->assertSame('active', $cart->status);
        $this->assertNull($cart->phone);

        // Same visitor enters a phone at checkout: same cart row, contact captured.
        $this->sync(['items' => [['id' => $this->product->id, 'qty' => 1]], 'contact' => ['name' => 'Sadia', 'phone' => '01911222333']], $cart->token);
        $cart->refresh();
        $this->assertSame(1, AbandonedCart::count());
        $this->assertSame('01911222333', $cart->phone);
        $this->assertSame('Sadia', $cart->name);
        $this->assertSame(1, $cart->item_count);

        // Emptying the cart clears it.
        $this->sync(['items' => []], $cart->token);
        $this->assertSame(0, $cart->fresh()->item_count);

        // Crawlers are ignored.
        $this->withHeader('User-Agent', 'facebookexternalhit/1.1')
            ->postJson(route('website.cart.sync'), ['items' => [['id' => $this->product->id, 'qty' => 1]]])->assertOk();
        $this->assertSame(1, AbandonedCart::count());
    }

    public function test_abandoned_cart_digest_and_recovery_on_checkout(): void
    {
        $buyer = $this->buyer();
        $this->sync(['items' => [['id' => $this->product->id, 'qty' => 1]]], null, $buyer);
        $cart = AbandonedCart::firstOrFail();
        $this->assertSame('01711000000', $cart->phone, 'Signed-in shopper phone comes from their profile.');
        $this->assertNotNull($cart->customer_id);

        $alerts = app(CommerceAlertService::class);
        $this->assertSame(0, $alerts->scanAbandonedCarts(), 'Not abandoned yet.');

        $this->travel(2)->hours();
        $this->assertTrue($cart->fresh()->isAbandoned());
        $this->assertSame(1, $alerts->scanAbandonedCarts());
        $this->assertSame(0, $alerts->scanAbandonedCarts(), 'Same carts are not alerted twice.');
        $this->assertNotNull($cart->fresh()->notified_at);
        $this->assertSame(1, $this->admin->notifications()->where('type', StaffAlert::class)->where('data', 'like', '%abandoned_cart%')->count());

        $this->actingAs($this->admin, 'admin')->get(route('abandoned-carts.index'))->assertOk()->assertSee('Rima');

        $this->withHeader('User-Agent', self::BROWSER)->withCredentials()->withCookie(AbandonedCartService::COOKIE, $cart->token)
            ->actingAs($buyer, 'web')->postJson(route('website.checkout'), [
                'customer_name' => 'Rima', 'customer_phone' => '01711000000', 'customer_address' => 'Dhanmondi, Dhaka',
                'delivery_zone' => 'inside_dhaka', 'payment_method' => 'cash_on_delivery',
                'cart' => [['id' => $this->product->id, 'qty' => 1]],
            ])->assertOk()->assertJson(['success' => true]);

        $order = Order::latest('id')->firstOrFail();
        $cart->refresh();
        $this->assertSame('recovered', $cart->status);
        $this->assertSame($order->id, $cart->order_id);
        $this->assertNotNull($cart->recovered_at);

        // A new cart after ordering starts a fresh row instead of reopening the recovered one.
        $this->sync(['items' => [['id' => $this->product->id, 'qty' => 1]]], $cart->token, $buyer);
        $this->assertSame(2, AbandonedCart::count());
        $this->assertSame('recovered', $cart->fresh()->status);
    }

    public function test_same_visit_order_is_converted_not_recovered(): void
    {
        $buyer = $this->buyer();
        $this->sync(['items' => [['id' => $this->product->id, 'qty' => 1]]], null, $buyer);
        $cart = AbandonedCart::firstOrFail();

        $this->withCredentials()->withCookie(AbandonedCartService::COOKIE, $cart->token)->actingAs($buyer, 'web')->postJson(route('website.checkout'), [
            'customer_name' => 'Rima', 'customer_phone' => '01711000000', 'customer_address' => 'Dhanmondi, Dhaka',
            'cart' => [['id' => $this->product->id, 'qty' => 1]],
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame('converted', $cart->fresh()->status);
    }

    public function test_phone_match_recovers_cart_and_staff_can_follow_up(): void
    {
        $this->sync(['items' => [['id' => $this->product->id, 'qty' => 3]], 'contact' => ['name' => 'Tania', 'phone' => '01555666777']]);
        $cart = AbandonedCart::firstOrFail();
        $this->travel(3)->hours();

        $this->actingAs($this->admin, 'admin')->post(route('abandoned-carts.status', $cart), ['status' => 'contacted', 'notes' => 'Called, will order tonight'])
            ->assertSessionHas('success');
        $cart->refresh();
        $this->assertSame('contacted', $cart->status);
        $this->assertSame($this->admin->id, $cart->contacted_by);
        $this->assertStringContainsString('will order tonight', $cart->notes);

        $this->actingAs($this->admin, 'admin')->post(route('abandoned-carts.lead', $cart))->assertRedirect();
        $lead = Lead::firstOrFail();
        $this->assertSame('01555666777', $lead->phone);
        $this->assertSame('website', $lead->source);
        $this->assertSame($this->product->id, $lead->product_id);
        $this->actingAs($this->admin, 'admin')->post(route('abandoned-carts.lead', $cart))->assertRedirect(route('leads.show', $lead));
        $this->assertSame(1, Lead::count(), 'An open lead for the phone is reused.');

        // Order placed by phone (e.g. landing page or staff) recovers the cart and converts the lead.
        $order = app(OrderCreationService::class)->place($this->shop->id,
            ['name' => 'Tania', 'phone' => '+8801555666777', 'address' => 'Khulna'],
            [['id' => $this->product->id, 'qty' => 3]],
        )['order'];

        $this->assertSame('recovered', $cart->fresh()->status);
        $this->assertSame($order->id, $cart->fresh()->order_id);
        $this->assertSame('converted', $lead->fresh()->status);

        $this->actingAs($this->admin, 'admin')->post(route('abandoned-carts.status', $cart), ['status' => 'lost'])->assertSessionHas('error');
    }

    public function test_abandoned_carts_are_shop_scoped_and_permissioned(): void
    {
        $this->sync(['items' => [['id' => $this->product->id, 'qty' => 1]], 'contact' => ['phone' => '01555666777']]);
        $cart = AbandonedCart::firstOrFail();

        $other = Shop::withoutEvents(fn () => Shop::create(['name' => 'Other', 'is_active' => true]));
        $otherAdmin = User::factory()->create();
        $otherAdmin->forceFill(['shop_id' => $other->id, 'role' => 'admin'])->save();
        $otherAdmin->assignRole('Admin');
        $this->actingAs($otherAdmin, 'admin')->get(route('abandoned-carts.show', $cart))->assertNotFound();
        $this->actingAs($otherAdmin, 'admin')->post(route('abandoned-carts.status', $cart), ['status' => 'lost'])->assertNotFound();

        $cashier = User::factory()->create();
        $cashier->forceFill(['shop_id' => $this->shop->id, 'role' => 'cashier'])->save();
        $cashier->assignRole('Cashier');
        $this->actingAs($cashier, 'admin')->getJson(route('abandoned-carts.index'))->assertForbidden();

        $this->actingAs($this->admin, 'admin')->get(route('abandoned-carts.show', $cart))->assertOk()->assertSee('01555666777');
    }
}
