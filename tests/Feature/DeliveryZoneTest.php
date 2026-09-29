<?php

namespace Tests\Feature;

use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\DeliveryChargeService;
use App\Services\PaymentMethodRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryZoneTest extends TestCase
{
    use RefreshDatabase;

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
            'shop_id' => $this->shop->id, 'name' => 'Ceramic Mug', 'barcode' => 'MUG-1',
            'cost_price' => 100, 'selling_price' => 400, 'stock_quantity' => 10, 'is_published' => true,
        ]);
    }

    private function saveZones(array $zones, array $extra = [])
    {
        return $this->actingAs($this->admin, 'admin')->put(route('cms.delivery.update'), array_merge([
            'zones' => $zones,
            'delivery_free_min_amount' => 100000,
            'delivery_cod_enabled' => '1',
        ], $extra));
    }

    public function test_falls_back_to_legacy_zones_when_shop_has_none(): void
    {
        $quote = app(DeliveryChargeService::class)->quote(500, 'outside_dhaka', null, null, $this->shop->id);
        $this->assertSame('outside_dhaka', $quote['zone']);
        $this->assertSame('Outside Dhaka', $quote['zone_label']);
    }

    public function test_admin_configures_zones_and_checkout_uses_them(): void
    {
        $this->actingAs($this->admin, 'admin')->get(route('cms.delivery.edit'))->assertOk()->assertSee('Delivery zones')->assertSee('Not active');
        $this->assertSame(2, DeliveryZone::forShop($this->shop->id)->count());
        $inside = DeliveryZone::forShop($this->shop->id)->where('code', 'inside_dhaka')->first();
        $outside = DeliveryZone::forShop($this->shop->id)->where('code', 'outside_dhaka')->first();

        $this->saveZones([
            ['id' => $inside->id, 'name' => 'Inside Dhaka', 'code' => 'inside_dhaka', 'fee' => 70, 'is_active' => 1],
            ['id' => $outside->id, 'name' => 'Outside Dhaka', 'code' => 'outside_dhaka', 'fee' => 130, 'delete' => 1],
            ['name' => 'Chattogram City', 'fee' => 90, 'note' => '2 days', 'is_active' => 1],
        ], ['default_zone' => 'inside_dhaka'])->assertRedirect(route('cms.delivery.edit'))->assertSessionHasNoErrors();

        $codes = DeliveryZone::forShop($this->shop->id)->ordered()->pluck('fee', 'code')->map(fn ($f) => (float) $f)->all();
        $this->assertSame(['inside_dhaka' => 70.0, 'chattogram_city' => 90.0], $codes);

        $buyer = User::factory()->create();
        $buyer->assignRole('Customer');
        $payload = [
            'customer_name' => 'Nabil', 'customer_phone' => '01811000000', 'customer_address' => 'Agrabad, Chattogram',
            'cart' => [['id' => $this->product->id, 'qty' => 1]],
        ];

        $this->actingAs($buyer, 'web')->postJson(route('website.checkout'), $payload + ['delivery_zone' => 'chattogram_city'])
            ->assertOk()->assertJson(['success' => true, 'delivery_fee' => 90]);
        $order = Order::latest('id')->first();
        $this->assertSame('chattogram_city', $order->delivery_zone);
        $this->assertEquals(490, (float) $order->total_amount);

        // Removed zone codes and unknown payment methods are rejected server-side.
        $this->actingAs($buyer, 'web')->postJson(route('website.checkout'), $payload + ['delivery_zone' => 'outside_dhaka'])->assertStatus(422);
        $this->actingAs($buyer, 'web')->postJson(route('website.checkout'), $payload + ['payment_method' => 'bkash'])->assertStatus(422);

        $this->get('/')->assertOk()->assertSee('Chattogram City');
    }

    public function test_at_least_one_active_zone_and_unique_codes_are_required(): void
    {
        $this->saveZones([['name' => 'Only', 'code' => 'only', 'fee' => 50]])->assertSessionHasErrors('zones');
        $this->saveZones([
            ['name' => 'A', 'code' => 'same', 'fee' => 50, 'is_active' => 1],
            ['name' => 'B', 'code' => 'same', 'fee' => 60, 'is_active' => 1],
        ])->assertSessionHasErrors('zones');
    }

    public function test_zones_of_another_shop_cannot_be_edited_and_cashiers_are_denied(): void
    {
        $other = Shop::withoutEvents(fn () => Shop::create(['name' => 'Other', 'is_active' => true]));
        $foreign = DeliveryZone::create(['shop_id' => $other->id, 'name' => 'Foreign', 'code' => 'foreign', 'fee' => 10, 'is_active' => true]);

        $this->saveZones([['id' => $foreign->id, 'name' => 'Hijacked', 'code' => 'foreign', 'fee' => 1, 'is_active' => 1]])->assertSessionHasNoErrors();
        $this->assertSame('Foreign', $foreign->fresh()->name);
        $this->assertSame(1, DeliveryZone::forShop($this->shop->id)->where('name', 'Hijacked')->count());

        $cashier = User::factory()->create();
        $cashier->forceFill(['shop_id' => $this->shop->id, 'role' => 'cashier'])->save();
        $cashier->assignRole('Cashier');
        $this->actingAs($cashier, 'admin')->getJson(route('cms.delivery.edit'))->assertForbidden();
    }

    public function test_gateways_stay_inactive_without_credentials_and_driver(): void
    {
        $registry = app(PaymentMethodRegistry::class);
        $bkash = collect($registry->all())->firstWhere('key', 'bkash');
        $this->assertFalse($bkash['available']);
        $this->assertStringContainsString('Missing credentials', $bkash['reason']);

        config(['payments.methods.bkash.credentials' => ['app_key' => 'k', 'app_secret' => 's', 'username' => 'u', 'password' => 'p']]);
        $bkash = collect($registry->all())->firstWhere('key', 'bkash');
        $this->assertFalse($bkash['available']);
        $this->assertSame('No payment driver installed', $bkash['reason']);

        $this->assertNotContains('bkash', $registry->checkoutKeys());
        $this->assertContains('cash_on_delivery', $registry->checkoutKeys());
    }
}
