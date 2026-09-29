<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\CustomerSegmentService;
use App\Services\OrderCreationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SegmentAndShareTest extends TestCase
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
            'shop_id' => $this->shop->id, 'name' => 'Jamdani Saree', 'slug' => 'jamdani-saree', 'barcode' => 'JS-1',
            'cost_price' => 4000, 'selling_price' => 8000, 'stock_quantity' => 30, 'is_published' => true,
        ]);
    }

    private function order(string $phone, int $qty = 1, array $after = []): Order
    {
        $order = app(OrderCreationService::class)->place(
            $this->shop->id,
            ['name' => 'Buyer '.$phone, 'phone' => $phone, 'address' => 'Dhaka'],
            [['id' => $this->product->id, 'qty' => $qty]],
            null,
            ['zone' => 'inside_dhaka', 'payment_method' => 'cash_on_delivery'],
        )['order'];
        if ($after) {
            $order->forceFill($after)->save();
        }

        return $order;
    }

    public function test_segments_follow_real_order_history(): void
    {
        $segments = app(CustomerSegmentService::class);

        $this->order('01711000001');
        $second = $this->order('01711000001');
        $this->order('01711000001', 3, ['status' => 'cancelled']);
        $loyal = $second->customer;

        $stats = $segments->stats($loyal->fresh());
        $this->assertSame(2, $stats['orders'], 'Cancelled orders are excluded.');
        $this->assertEqualsWithDelta(16000, $stats['spent'], 0.01, 'Spend excludes delivery fees.');
        $this->assertSame(['returning', 'high_value'], $segments->segmentsFor($loyal->fresh()));

        $old = $this->order('01711000002', 1, ['created_at' => now()->subDays(120)]);
        $this->assertSame(['inactive'], $segments->segmentsFor($old->customer->fresh()));

        $fresh = $this->order('01711000003');
        $this->assertSame(['new'], $segments->segmentsFor($fresh->customer->fresh()));

        $signup = Customer::create(['shop_id' => $this->shop->id, 'name' => 'Just joined', 'phone' => '01711000004']);
        $this->assertSame(['new'], $segments->segmentsFor($signup));

        config(['commerce.segments.vip_spend' => 15000]);
        $this->assertContains('vip', $segments->segmentsFor($loyal->fresh()));
        $this->assertNotContains('high_value', $segments->segmentsFor($loyal->fresh()));

        $this->actingAs($this->admin, 'admin')->get(route('customers.index'))->assertOk()->assertSee('All segments');
        $this->actingAs($this->admin, 'admin')->get(route('customers.show', $loyal))
            ->assertOk()->assertSee('Returning')->assertSee('16,000')->assertSee($second->invoice_no);
    }

    public function test_customer_profile_is_shop_scoped(): void
    {
        $customer = $this->order('01711000009')->customer;

        $other = Shop::withoutEvents(fn () => Shop::create(['name' => 'Other', 'is_active' => true]));
        $otherAdmin = User::factory()->create();
        $otherAdmin->forceFill(['shop_id' => $other->id, 'role' => 'admin'])->save();
        $otherAdmin->assignRole('Admin');

        $this->actingAs($otherAdmin, 'admin')->get(route('customers.show', $customer))->assertNotFound();
    }

    public function test_product_share_links_are_tracked_and_attribute_orders(): void
    {
        $this->withHeader('User-Agent', self::BROWSER)->get(route('website.product', $this->product))
            ->assertOk()
            ->assertSee('facebook.com/sharer/sharer.php', false)
            ->assertSee('wa.me/?text=', false)
            ->assertSee('utm_medium%3Dsocial_share', false)
            ->assertSee('Copy link');

        // A friend opens the WhatsApp share link and orders.
        $this->withHeader('User-Agent', self::BROWSER)
            ->get(route('website.product', $this->product).'?utm_source=whatsapp&utm_medium=social_share&utm_campaign=product_share&utm_content=jamdani-saree')
            ->assertOk();

        $buyer = User::factory()->create();
        $buyer->forceFill(['shop_id' => $this->shop->id])->save();
        $buyer->assignRole('Customer');
        $this->actingAs($buyer, 'web')->postJson(route('website.checkout'), [
            'customer_name' => 'Friend', 'customer_phone' => '01722000000', 'customer_address' => 'Sylhet',
            'delivery_zone' => 'outside_dhaka', 'payment_method' => 'cash_on_delivery',
            'cart' => [['id' => $this->product->id, 'qty' => 1]],
        ])->assertOk()->assertJson(['success' => true]);

        $order = Order::latest('id')->firstOrFail();
        $this->assertSame('whatsapp', $order->utm_source);
        $this->assertSame('social_share', $order->utm_medium);
        $this->assertSame('product_share', $order->utm_campaign);
    }

    public function test_crm_screens_work_with_retail_module_on_and_off(): void
    {
        foreach ([false, true] as $retail) {
            config(['modules.retail.enabled' => $retail]);
            $customer = $this->order('0171100010'.(int) $retail)->customer;

            $this->actingAs($this->admin, 'admin')->get(route('leads.index'))->assertOk();
            $this->actingAs($this->admin, 'admin')->get(route('abandoned-carts.index'))->assertOk();
            $this->actingAs($this->admin, 'admin')->get(route('notifications.index'))->assertOk();
            $this->actingAs($this->admin, 'admin')->get(route('customers.show', $customer))->assertOk();
            $this->actingAs($this->admin, 'admin')->get(route('online-orders.index'))->assertOk()->assertSee('Leads')->assertSee('Abandoned carts');
        }

        // Retail dashboard widgets use MySQL GREATEST(); the online dashboard runs on any driver.
        config(['modules.retail.enabled' => false]);
        $this->actingAs($this->admin, 'admin')->get(route('dashboard'))->assertOk()->assertSee('Leads');

        config(['modules.retail.enabled' => false]);
        $this->actingAs($this->admin, 'admin')->get(route('pos.index'))->assertNotFound();
    }
}
