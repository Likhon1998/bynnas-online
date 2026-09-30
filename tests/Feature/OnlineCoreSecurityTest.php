<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\OrderCreationService;
use App\Services\PaymentGatewayService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OnlineCoreSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    private User $admin;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        config(['modules.retail.enabled' => false]);
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->shop = Shop::create(['name' => 'Test Shop', 'is_active' => true]);
        $this->admin = $this->staff('Admin');
        $this->product = Product::create([
            'shop_id' => $this->shop->id, 'name' => 'Cotton Kurti', 'slug' => 'cotton-kurti', 'barcode' => 'KURTI-1',
            'cost_price' => 300, 'selling_price' => 1000, 'stock_quantity' => 10, 'is_published' => true,
        ]);
    }

    private function staff(string $role, ?Shop $shop = null): User
    {
        $user = User::factory()->create();
        $user->forceFill(['shop_id' => ($shop ?? $this->shop)->id, 'role' => $role === 'Admin' ? 'admin' : strtolower($role)])->save();
        $user->assignRole($role);

        return $user;
    }

    private function placeOrder(?Shop $shop = null, ?Product $product = null): Order
    {
        return app(OrderCreationService::class)->place(
            ($shop ?? $this->shop)->id,
            ['name' => 'Rima', 'phone' => '01711000000', 'address' => 'Dhanmondi, Dhaka'],
            [['id' => ($product ?? $this->product)->id, 'qty' => 1]],
            null,
            ['zone' => 'inside_dhaka', 'payment_method' => 'cash_on_delivery'],
        )['order'];
    }

    public function test_online_core_areas_follow_role_permissions(): void
    {
        $cashier = $this->staff('Cashier');
        $manager = $this->staff('Manager');

        foreach (['online-orders.index', 'accounts.chart', 'analytics.overview', 'reports.daily'] as $route) {
            $this->actingAs($cashier, 'admin')->getJson(route($route))->assertForbidden();
        }
        $this->actingAs($cashier, 'admin')->get(route('customers.index'))->assertOk();

        $this->actingAs($manager, 'admin')->get(route('online-orders.index'))->assertOk();
        $this->actingAs($manager, 'admin')->get(route('customers.index'))->assertOk();
        $this->actingAs($manager, 'admin')->getJson(route('accounts.chart'))->assertForbidden();
        $this->actingAs($manager, 'admin')->getJson(route('analytics.overview'))->assertForbidden();

        foreach (['online-orders.index', 'accounts.chart', 'customers.index'] as $route) {
            $this->actingAs($this->admin, 'admin')->get(route($route))->assertOk();
        }
        // Report pages use MySQL-only SQL (GREATEST), so only the permission is asserted on SQLite.
        $this->assertTrue($this->admin->can('view reports'));
    }

    public function test_orders_and_invoices_from_another_shop_are_blocked(): void
    {
        $other = Shop::withoutEvents(fn () => Shop::create(['name' => 'Other Shop', 'is_active' => true]));
        $this->staff('Admin', $other);
        $foreignProduct = Product::create([
            'shop_id' => $other->id, 'name' => 'Other Tee', 'barcode' => 'OT-1',
            'cost_price' => 100, 'selling_price' => 300, 'stock_quantity' => 5, 'is_published' => true,
        ]);
        $foreign = $this->placeOrder($other, $foreignProduct);

        $this->actingAs($this->admin, 'admin')->getJson(route('online-orders.show', $foreign))->assertForbidden();
        $this->actingAs($this->admin, 'admin')->getJson(route('orders.invoice', $foreign))->assertForbidden();
        $this->actingAs($this->admin, 'admin')->postJson(route('online-orders.cancel', $foreign))->assertForbidden();
        $this->assertSame('new', $foreign->fresh()->status);

        $own = $this->placeOrder();
        $this->actingAs($this->admin, 'admin')->get(route('orders.invoice', $own))->assertOk();
        $this->actingAs($this->staff('Cashier'), 'admin')->getJson(route('orders.invoice', $own))->assertForbidden();
    }

    public function test_role_editor_hides_retail_permissions_and_keeps_them_on_save(): void
    {
        $manager = Role::findByName('Manager', 'web');
        $this->assertTrue($manager->hasPermissionTo('process pos sales'));

        $this->actingAs($this->admin, 'admin')->get(route('roles.edit', $manager))
            ->assertOk()
            ->assertSee('Manage online orders')
            ->assertDontSee('POS: process sales');

        $this->actingAs($this->admin, 'admin')->put(route('roles.update', $manager), [
            'name' => 'Renamed',
            'permissions' => ['view dashboard', 'manage orders'],
        ])->assertRedirect(route('roles.index'));

        $manager->refresh();
        $this->assertSame('Manager', $manager->name);
        $this->assertTrue($manager->hasPermissionTo('manage orders'));
        $this->assertTrue($manager->hasPermissionTo('process pos sales'));
        $this->assertFalse($manager->hasPermissionTo('manage leads'));

        $this->actingAs($this->admin, 'admin')->post(route('roles.store'), [
            'name' => 'Sneaky', 'permissions' => ['process pos sales'],
        ])->assertSessionHasErrors('permissions.0');
    }

    public function test_customers_cannot_be_duplicated_or_deleted_with_history(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('customers.store'), ['name' => 'Rima', 'phone' => '01711000000'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->admin, 'admin')->post(route('customers.store'), ['name' => 'Rima 2', 'phone' => '+8801711000000'])
            ->assertSessionHasErrors('phone');

        $order = $this->placeOrder();
        $customer = Customer::find($order->customer_id);
        $this->actingAs($this->admin, 'admin')->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.show', $customer));
        $this->assertNotNull($customer->fresh());

        $blank = Customer::create(['shop_id' => $this->shop->id, 'name' => 'Walk away', 'phone' => '01899000000']);
        $this->actingAs($this->admin, 'admin')->delete(route('customers.destroy', $blank))->assertRedirect(route('customers.index'));
        $this->assertNull($blank->fresh());
    }

    public function test_robots_and_sitemap_are_generated(): void
    {
        $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->assertSee('User-agent: *');

        $this->get('/sitemap.xml')->assertOk()
            ->assertSee('<urlset', false)
            ->assertSee(route('website.product', $this->product), false)
            ->assertSee(route('website.shop'), false);
    }

    public function test_gateway_callback_is_inert_until_configured(): void
    {
        $order = $this->placeOrder();
        $transaction = PaymentTransaction::create([
            'shop_id' => $this->shop->id, 'order_id' => $order->id, 'method' => 'bkash', 'amount' => 100, 'status' => 'pending',
        ]);

        $this->get(route('payment.callback', $transaction))->assertNotFound();
        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertFalse(app(PaymentGatewayService::class)->isAvailable('bkash'));
    }

    public function test_configured_gateway_verifies_amount_before_recording_payment(): void
    {
        config([
            'payments.methods.bkash.driver' => FakeGateway::class,
            'payments.methods.bkash.credentials' => ['app_key' => 'k', 'app_secret' => 's'],
        ]);
        $order = $this->placeOrder();

        $url = app(PaymentGatewayService::class)->start($order, 'bkash', 200);
        $transaction = PaymentTransaction::where('order_id', $order->id)->firstOrFail();
        $this->assertStringContainsString($transaction->uuid, $url);

        $this->post(route('payment.callback', $transaction), ['amount' => 150, 'trx' => 'BK1'])->assertRedirect();
        $this->assertSame('failed', $transaction->fresh()->status);
        $this->assertEquals(0, (float) $order->fresh()->paid_amount);

        $retry = PaymentTransaction::create([
            'shop_id' => $this->shop->id, 'order_id' => $order->id, 'method' => 'bkash', 'amount' => 200, 'status' => 'pending',
        ]);
        $this->post(route('payment.callback', $retry), ['amount' => 200, 'trx' => 'BK2'])->assertRedirect();
        $this->post(route('payment.callback', $retry), ['amount' => 200, 'trx' => 'BK2'])->assertRedirect();

        $this->assertSame('paid', $retry->fresh()->status);
        $this->assertEquals(200, (float) $order->fresh()->paid_amount);
        $this->assertSame('BK2', $order->fresh()->payment_reference);
    }
}

class FakeGateway implements PaymentGateway
{
    public function __construct(array $credentials, bool $sandbox) {}

    public function initiate(PaymentTransaction $transaction, string $callbackUrl): string
    {
        return 'https://gateway.test/pay?return='.urlencode($callbackUrl);
    }

    public function verify(PaymentTransaction $transaction, Request $request): array
    {
        return ['paid' => true, 'amount' => (float) $request->input('amount'), 'reference' => $request->input('trx')];
    }
}
