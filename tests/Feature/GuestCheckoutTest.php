<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->shop = Shop::create(['name' => 'Test Shop', 'is_active' => true]);
        $admin = User::factory()->create();
        $admin->forceFill(['shop_id' => $this->shop->id, 'role' => 'admin'])->save();
        $admin->assignRole('Admin');
        $this->product = Product::create([
            'shop_id' => $this->shop->id, 'name' => 'Ceramic Mug', 'barcode' => 'MUG-1',
            'cost_price' => 100, 'selling_price' => 400, 'stock_quantity' => 10, 'is_published' => true,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Guest Buyer',
            'customer_phone' => '01711000000',
            'customer_address' => 'House 1, Road 2, Dhanmondi',
            'cart' => [['id' => $this->product->id, 'qty' => 2]],
        ], $overrides);
    }

    public function test_guest_can_checkout_without_an_account(): void
    {
        $usersBefore = User::count();

        $response = $this->postJson(route('website.checkout'), $this->payload())
            ->assertOk()
            ->assertJson(['success' => true, 'guest' => true]);

        $order = Order::latest('id')->firstOrFail();
        $response->assertJson([
            'invoice' => $order->invoice_no,
            'track_url' => route('website.track', ['invoice' => $order->invoice_no]),
        ]);

        $this->assertSame('Guest Buyer', $order->delivery_name);
        $this->assertSame(2, (int) $order->items()->sum('quantity'));
        $this->assertNull($order->customer->user_id);
        $this->assertSame($usersBefore, User::count());
    }

    public function test_guest_sees_tracking_straight_after_checkout_in_same_session(): void
    {
        $this->postJson(route('website.checkout'), $this->payload())->assertOk();
        $order = Order::latest('id')->firstOrFail();

        $this->get(route('website.track', ['invoice' => $order->invoice_no]))
            ->assertOk()
            ->assertSee('bb-track-result', false)
            ->assertSee($order->invoice_no)
            ->assertSee('Ceramic Mug');
    }

    public function test_tracking_link_alone_does_not_reveal_someone_elses_order(): void
    {
        $this->postJson(route('website.checkout'), $this->payload())->assertOk();
        $order = Order::latest('id')->firstOrFail();

        $this->flushSession();

        $this->get(route('website.track', ['invoice' => $order->invoice_no]))
            ->assertOk()
            ->assertDontSee('bb-track-result', false)
            ->assertDontSee('Ceramic Mug');
    }

    public function test_guest_tracks_order_later_with_order_id_and_phone(): void
    {
        $this->postJson(route('website.checkout'), $this->payload())->assertOk();
        $order = Order::latest('id')->firstOrFail();
        $this->flushSession();

        $this->post(route('website.track.lookup'), ['invoice_no' => $order->invoice_no, 'phone' => '01799999999'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->post(route('website.track.lookup'), ['invoice_no' => $order->invoice_no, 'phone' => '+8801711000000'])
            ->assertRedirect(route('website.track', ['invoice' => $order->invoice_no]));

        $this->get(route('website.track', ['invoice' => $order->invoice_no]))
            ->assertOk()
            ->assertSee('bb-track-result', false)
            ->assertSee('Ceramic Mug');
    }

    public function test_buyer_sees_full_delivery_details_right_after_checkout(): void
    {
        $this->postJson(route('website.checkout'), $this->payload())->assertOk();
        $order = Order::latest('id')->firstOrFail();

        $this->get(route('website.track', ['invoice' => $order->invoice_no]))
            ->assertSee('Guest Buyer')
            ->assertSee('House 1, Road 2, Dhanmondi')
            ->assertDontSee('Partly hidden for privacy');
    }

    public function test_forgot_order_id_finds_all_orders_by_phone_only(): void
    {
        $this->postJson(route('website.checkout'), $this->payload())->assertOk();
        $this->postJson(route('website.checkout'), $this->payload(['customer_phone' => '+880 1711-000000']))->assertOk();
        $this->postJson(route('website.checkout'), $this->payload(['customer_phone' => '01822000000', 'customer_name' => 'Someone Else']))->assertOk();
        [$first, $second, $other] = Order::orderBy('id')->get()->all();
        $this->flushSession();

        $this->followingRedirects()
            ->post(route('website.track.lookup'), ['lookup_mode' => 'phone', 'phone' => '01711000000'])
            ->assertOk()
            ->assertSee('2 orders found')
            ->assertSee($first->invoice_no)
            ->assertSee($second->invoice_no)
            ->assertDontSee($other->invoice_no)
            ->assertDontSee('House 1, Road 2')
            ->assertDontSee('Guest Buyer');

        // Opening one from the list shows its status, with name and address masked.
        $this->get(route('website.track', ['invoice' => $first->invoice_no]))
            ->assertOk()
            ->assertSee('bb-track-result', false)
            ->assertSee('Partly hidden for privacy')
            ->assertDontSee('House 1, Road 2')
            ->assertDontSee('Guest Buyer');

        // An order for a different phone is still not reachable.
        $this->get(route('website.track', ['invoice' => $other->invoice_no]))
            ->assertDontSee('bb-track-result', false);
    }

    public function test_phone_only_lookup_with_unknown_number_finds_nothing(): void
    {
        $this->postJson(route('website.checkout'), $this->payload())->assertOk();
        $this->flushSession();

        $this->post(route('website.track.lookup'), ['lookup_mode' => 'phone', 'phone' => '01999999999'])
            ->assertRedirect()
            ->assertSessionHas('error', 'No orders found for that phone number.');

        $this->post(route('website.track.lookup'), ['lookup_mode' => 'phone', 'phone' => '123'])
            ->assertSessionHas('error');
    }

    public function test_signed_in_customer_order_still_attaches_to_their_account(): void
    {
        $buyer = User::factory()->create();
        $buyer->assignRole('Customer');

        $this->actingAs($buyer, 'web')->postJson(route('website.checkout'), $this->payload())
            ->assertOk()
            ->assertJson(['success' => true, 'guest' => false]);

        $order = Order::latest('id')->firstOrFail();
        $this->assertSame($buyer->id, (int) $order->customer->user_id);

        $this->flushSession();
        $this->actingAs($buyer, 'web')->get(route('website.track', ['invoice' => $order->invoice_no]))
            ->assertOk()
            ->assertSee('bb-track-result', false);
    }

    public function test_guest_order_reuses_customer_record_by_phone(): void
    {
        $this->postJson(route('website.checkout'), $this->payload())->assertOk();
        $this->postJson(route('website.checkout'), $this->payload(['customer_phone' => '+8801711000000']))->assertOk();

        $this->assertSame(2, Order::count());
        $this->assertSame(1, Customer::count());
    }
}
