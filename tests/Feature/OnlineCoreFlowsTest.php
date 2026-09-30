<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Campaign;
use App\Models\CmsReview;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\SiteSetting;
use App\Models\User;
use App\Notifications\CustomerOrderUpdate;
use App\Services\AccountService;
use App\Services\CustomerSegmentService;
use App\Services\LeadService;
use App\Services\OrderCreationException;
use App\Services\OrderCreationService;
use App\Services\OrderWorkflowException;
use App\Services\OrderWorkflowService;
use App\Support\OrderStatus;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnlineCoreFlowsTest extends TestCase
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
        $this->admin = User::factory()->create();
        $this->admin->forceFill(['shop_id' => $this->shop->id, 'role' => 'admin'])->save();
        $this->admin->assignRole('Admin');
        $this->product = Product::create([
            'shop_id' => $this->shop->id, 'name' => 'Denim Jeans', 'slug' => 'denim-jeans', 'barcode' => 'JEANS-1',
            'cost_price' => 600, 'selling_price' => 1500, 'stock_quantity' => 10, 'is_published' => true,
        ]);
    }

    private function placeOrder(array $options = [], string $phone = '01711000000'): Order
    {
        return app(OrderCreationService::class)->place(
            $this->shop->id,
            ['name' => 'Rima', 'phone' => $phone, 'address' => 'Dhanmondi, Dhaka'],
            [['id' => $this->product->id, 'qty' => 1]],
            null,
            array_merge(['zone' => 'inside_dhaka', 'payment_method' => 'cash_on_delivery'], $options),
        )['order'];
    }

    public function test_each_transition_is_logged_with_previous_status_and_stale_updates_are_rejected(): void
    {
        $workflow = app(OrderWorkflowService::class);
        $order = $this->placeOrder();

        $workflow->verify($order, 'phone_call', null, $this->admin->id);
        $workflow->transition($order->fresh(), OrderStatus::PROCESSING, [], $this->admin->id);

        $logs = $order->statusLogs()->orderBy('id')->get();
        $this->assertSame([null, 'new', 'confirmed'], $logs->pluck('from_status')->all());
        $this->assertSame(['new', 'confirmed', 'processing'], $logs->pluck('status')->all());
        $this->assertSame($this->admin->id, $logs->last()->changed_by);

        // A second staff member still holding the old "processing" copy cannot act after a cancel.
        $stale = Order::find($order->id);
        $workflow->transition(Order::find($order->id), OrderStatus::CANCELLED, [], $this->admin->id);
        $this->assertSame(0, (int) $this->product->fresh()->reserved_stock);

        try {
            $workflow->transition($stale, OrderStatus::PACKED, [], $this->admin->id);
            $this->fail('A stale order copy must not be able to change status.');
        } catch (OrderWorkflowException) {
        }
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(10, (int) $this->product->fresh()->stock_quantity);
    }

    public function test_refund_before_settlement_reverses_the_advance_from_cash(): void
    {
        SiteSetting::current()->forceFill([
            'delivery_confirmation_enabled' => true,
            'delivery_confirmation_amount' => 200,
            'delivery_confirmation_instructions' => 'bKash 01700000000',
        ])->save();

        $order = $this->placeOrder(['payment_method' => 'confirmation_charge', 'payment_reference' => 'TRX1']);
        $this->assertEqualsWithDelta(200, (float) $order->confirmation_charge, 0.01);

        app(OrderWorkflowService::class)->transition($order, OrderStatus::CANCELLED, [], $this->admin->id);

        $accounts = app(AccountService::class);
        foreach (['WEB-COD', 'WEB-CASH', 'REVENUE'] as $code) {
            $account = Account::where('shop_id', $this->shop->id)->where('code', $code)->firstOrFail();
            $this->assertEqualsWithDelta(0, $accounts->accountBalance($account), 0.01, "{$code} should net to zero after the refund.");
        }
    }

    public function test_lead_cannot_be_converted_twice_or_while_lost(): void
    {
        $leads = app(LeadService::class);
        $lead = $leads->create($this->shop->id, ['name' => 'Nusrat', 'phone' => '01812000000', 'source' => 'facebook'], $this->admin);
        $options = ['address' => 'Uttara, Dhaka', 'zone' => 'inside_dhaka', 'payment_method' => 'cash_on_delivery'];

        $order = $leads->convertToOrder($lead, [['id' => $this->product->id, 'qty' => 1]], $options, $this->admin);

        try {
            $leads->convertToOrder($lead->fresh(), [['id' => $this->product->id, 'qty' => 1]], $options, $this->admin);
            $this->fail('A lead with an open order must not get a second one.');
        } catch (OrderCreationException $e) {
            $this->assertStringContainsString($order->invoice_no, $e->getMessage());
        }

        // Once that order is cancelled, the lead can be ordered again.
        app(OrderWorkflowService::class)->transition($order, OrderStatus::CANCELLED, [], $this->admin->id);
        $leads->convertToOrder($lead->fresh(), [['id' => $this->product->id, 'qty' => 1]], $options, $this->admin);
        $this->assertSame(2, Order::where('lead_id', $lead->id)->count());

        $lost = $leads->create($this->shop->id, ['name' => 'Karim', 'phone' => '01912000000', 'source' => 'facebook', 'status' => 'lost'], $this->admin);
        $this->expectException(OrderCreationException::class);
        $leads->convertToOrder($lost, [['id' => $this->product->id, 'qty' => 1]], $options, $this->admin);
    }

    public function test_customer_can_read_and_mark_order_notifications(): void
    {
        $buyer = User::factory()->create();
        $buyer->assignRole('Customer');
        $order = $this->placeOrder();
        $buyer->notify(new CustomerOrderUpdate($order, 'placed'));
        $buyer->notify(new CustomerOrderUpdate($order, OrderStatus::CONFIRMED));

        $this->actingAs($buyer, 'web')->get(route('website.account'))
            ->assertOk()->assertSee('Notifications')->assertSee('Order confirmed')->assertSee('Mark all read');

        $first = $buyer->notifications()->latest()->first();
        $this->actingAs($buyer, 'web')->post(route('website.account.notifications.read', $first->id))
            ->assertRedirect(route('website.track', ['invoice' => $order->invoice_no]));
        $this->assertSame(1, $buyer->unreadNotifications()->count());

        $this->actingAs($buyer, 'web')->post(route('website.account.notifications.read-all'))->assertRedirect();
        $this->assertSame(0, $buyer->unreadNotifications()->count());

        // Another user's notification id is not reachable.
        $other = User::factory()->create();
        $other->assignRole('Customer');
        $other->notify(new CustomerOrderUpdate($order, 'placed'));
        $this->actingAs($buyer, 'web')->post(route('website.account.notifications.read', $other->notifications()->first()->id))->assertNotFound();
    }

    public function test_product_share_links_attribute_to_a_tracked_campaign(): void
    {
        $this->get(route('website.product', $this->product).'?utm_source=whatsapp&utm_medium=social_share&utm_campaign=product_share&utm_content=denim-jeans')
            ->assertOk();

        $campaign = Campaign::where('shop_id', $this->shop->id)->where('utm_campaign', 'product_share')->firstOrFail();
        $this->assertTrue($campaign->isTracking());
        $this->assertSame($campaign->id, session('bs_attribution.campaign_id'));
    }

    public function test_delivery_settings_require_a_real_payment_option(): void
    {
        $zones = [['name' => 'Inside Dhaka', 'code' => 'inside_dhaka', 'fee' => 60, 'is_active' => 1]];
        $base = ['zones' => $zones, 'delivery_free_min_amount' => 0];

        $this->actingAs($this->admin, 'admin')->put(route('cms.delivery.update'), $base)
            ->assertSessionHasErrors('delivery_cod_enabled');
        $this->actingAs($this->admin, 'admin')->put(route('cms.delivery.update'), $base + [
            'delivery_confirmation_enabled' => '1', 'delivery_confirmation_amount' => 0, 'delivery_confirmation_instructions' => 'bKash 01700000000',
        ])->assertSessionHasErrors('delivery_confirmation_amount');
        $this->actingAs($this->admin, 'admin')->put(route('cms.delivery.update'), $base + ['delivery_cod_enabled' => '1'])
            ->assertSessionHasNoErrors();
    }

    public function test_ratings_come_only_from_published_product_reviews(): void
    {
        $this->product->forceFill(['rating' => 4.8, 'review_count' => 241])->save();
        CmsReview::syncProductRating($this->product->id);
        $this->assertSame(0, (int) $this->product->fresh()->review_count);

        CmsReview::create(['shop_id' => $this->shop->id, 'product_id' => $this->product->id, 'customer_name' => 'A', 'rating' => 4, 'body' => 'Good', 'is_published' => true]);
        CmsReview::create(['shop_id' => $this->shop->id, 'product_id' => $this->product->id, 'customer_name' => 'B', 'rating' => 5, 'body' => 'Great', 'is_published' => false]);

        $fresh = $this->product->fresh();
        $this->assertSame(1, (int) $fresh->review_count);
        $this->assertEqualsWithDelta(4.0, (float) $fresh->rating, 0.01);
    }

    public function test_repeat_returns_flag_the_customer_as_cod_risk(): void
    {
        $workflow = app(OrderWorkflowService::class);
        foreach ([1, 2] as $i) {
            $order = $this->placeOrder([], '01655000000');
            $order->forceFill(['status' => OrderStatus::SHIPPED, 'courier_service_id' => null])->save();
            $workflow->transition($order->fresh(), OrderStatus::RETURNED, ['note' => 'Refused at door'], $this->admin->id);
        }

        $customer = Customer::where('shop_id', $this->shop->id)->firstOrFail();
        $segments = app(CustomerSegmentService::class)->segmentsFor($customer);
        $this->assertContains('cod_risk', $segments);

        $this->actingAs($this->admin, 'admin')->get(route('customers.show', $customer))
            ->assertOk()->assertSee('COD risk')->assertSee('2 returned / refused');
    }
}
