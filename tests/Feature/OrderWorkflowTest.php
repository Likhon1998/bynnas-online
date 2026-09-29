<?php

namespace Tests\Feature;

use App\Models\AccountTransaction;
use App\Models\CourierService;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\OrderCreationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    private User $admin;

    private Product $product;

    private CourierService $courier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->shop = Shop::create(['name' => 'Test Shop', 'is_active' => true]);
        $this->admin = $this->staff('Admin');
        $this->product = Product::create([
            'shop_id' => $this->shop->id, 'name' => 'Cotton Kurti', 'barcode' => 'KURTI-1',
            'cost_price' => 300, 'selling_price' => 1000, 'stock_quantity' => 10, 'is_published' => true,
        ]);
        $this->courier = CourierService::create(['shop_id' => $this->shop->id, 'name' => 'Pathao', 'is_active' => true]);
    }

    private function staff(string $role, ?Shop $shop = null): User
    {
        $user = User::factory()->create();
        $user->forceFill(['shop_id' => ($shop ?? $this->shop)->id, 'role' => $role === 'Admin' ? 'admin' : strtolower($role)])->save();
        $user->assignRole($role);

        return $user;
    }

    private function placeOrder(int $qty = 2, array $options = []): Order
    {
        return app(OrderCreationService::class)->place(
            $this->shop->id,
            ['name' => 'Rima', 'phone' => '01711000000', 'address' => 'Dhanmondi, Dhaka'],
            [['id' => $this->product->id, 'qty' => $qty]],
            null,
            array_merge(['zone' => 'inside_dhaka', 'payment_method' => 'cash_on_delivery'], $options),
        )['order'];
    }

    private function moveTo(Order $order, string $status, array $extra = [])
    {
        return $this->actingAs($this->admin, 'admin')
            ->post(route('online-orders.update-status', $order), array_merge(['status' => $status], $extra));
    }

    private function txCount(Order $order, string $type): int
    {
        return AccountTransaction::where('reference_type', Order::class)->where('reference_id', $order->id)->where('type', $type)->count();
    }

    public function test_full_chain_from_new_to_completed_moves_stock_and_ledger_once(): void
    {
        $order = $this->placeOrder(2);
        $this->assertSame('new', $order->status);
        $this->assertSame(2, (int) $this->product->fresh()->reserved_stock);
        $this->assertSame(10, (int) $this->product->fresh()->stock_quantity);
        $this->assertSame(1, $this->txCount($order, 'web_sale'));

        // Processing requires a verified, confirmed order.
        $this->moveTo($order, 'processing')->assertSessionHas('error');
        $this->moveTo($order, 'confirmed')->assertSessionHas('error');
        $this->assertSame('new', $order->fresh()->status);

        $this->actingAs($this->admin, 'admin')->post(route('online-orders.verify', $order), [
            'verification_method' => 'phone_call', 'verification_notes' => 'Address confirmed',
        ])->assertSessionHas('success');
        $order->refresh();
        $this->assertSame('confirmed', $order->status);
        $this->assertSame($this->admin->id, $order->verified_by);
        $this->assertSame('phone_call', $order->verification_method);
        $this->assertNotNull($order->verified_at);

        $this->moveTo($order, 'processing')->assertSessionHas('success');
        $this->assertSame(10, (int) $this->product->fresh()->stock_quantity);

        $this->moveTo($order, 'packed')->assertSessionHas('success');
        $this->assertSame(8, (int) $this->product->fresh()->stock_quantity);
        $this->assertSame(0, (int) $this->product->fresh()->reserved_stock);

        $this->moveTo($order, 'shipped')->assertSessionHas('error');
        $this->moveTo($order, 'shipped', ['courier_service_id' => $this->courier->id, 'tracking_number' => 'PX-1'])->assertSessionHas('success');
        $order->refresh();
        $this->assertSame('Pathao', $order->shipping_courier);
        $this->assertNotNull($order->shipped_at);

        $this->moveTo($order, 'delivered')->assertSessionHas('success');
        $order->refresh();
        $this->assertEqualsWithDelta(2000, $order->amountDueFromCourier(), 0.01);

        $this->actingAs($this->admin, 'admin')->post(route('online-orders.collect-from-courier', $order))->assertSessionHas('success');
        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->courier_collected_at);
        $this->assertEqualsWithDelta(2000, (float) $order->courier_collected_amount, 0.01);
        $this->assertSame(1, $this->txCount($order, 'web_settlement'));
        $this->assertSame(8, (int) $this->product->fresh()->stock_quantity);

        // Customer tracker sees the collapsed customer steps.
        $this->actingAs($this->admin, 'admin')->get(route('online-orders.show', $order))->assertOk()->assertSee('Verified');
        $this->post(route('website.track.lookup'), ['invoice_no' => $order->invoice_no, 'phone' => '01711000000'])
            ->assertOk()->assertSee('Delivered');
    }

    public function test_courier_collection_from_shipped_passes_through_delivered(): void
    {
        $order = $this->placeOrder(1);
        $order->forceFill(['status' => 'packed', 'verified_at' => now()])->save();
        $this->moveTo($order, 'shipped', ['courier_service_id' => $this->courier->id])->assertSessionHas('success');

        $this->actingAs($this->admin, 'admin')->post(route('online-orders.collect-from-courier', $order))->assertSessionHas('success');
        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->delivered_at);
        $this->assertTrue($order->statusLogs()->where('status', 'delivered')->exists());
        $this->assertSame(9, (int) $this->product->fresh()->stock_quantity);
    }

    public function test_cancel_before_packing_releases_reservation_without_phantom_stock(): void
    {
        $order = $this->placeOrder(3);
        $this->actingAs($this->admin, 'admin')->post(route('online-orders.cancel', $order))->assertSessionHas('success');

        $product = $this->product->fresh();
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(10, (int) $product->stock_quantity);
        $this->assertSame(0, (int) $product->reserved_stock);
        $this->assertSame(1, $this->txCount($order, 'web_refund'));

        // Terminal: cannot reopen.
        $this->moveTo($order, 'confirmed', ['verification_method' => 'sms'])->assertSessionHas('error');
    }

    public function test_return_after_shipping_restocks_and_refund_needs_collected_money(): void
    {
        $order = $this->placeOrder(2);
        $order->forceFill(['status' => 'packed', 'verified_at' => now()])->save();
        $this->moveTo($order, 'shipped', ['courier_service_id' => $this->courier->id]);
        $this->assertSame(8, (int) $this->product->fresh()->stock_quantity);

        $this->moveTo($order, 'returned')->assertSessionHas('success');
        $this->assertSame(10, (int) $this->product->fresh()->stock_quantity);
        $this->assertSame(0, (int) $this->product->fresh()->reserved_stock);
        $this->assertSame(1, $this->txCount($order, 'web_refund'));

        // COD never collected → nothing to refund.
        $this->moveTo($order, 'refunded')->assertSessionHas('error');
        $this->assertSame('returned', $order->fresh()->status);
    }

    public function test_return_request_can_be_declined_or_refunded_after_completion(): void
    {
        $order = $this->placeOrder(1);
        $order->forceFill(['status' => 'packed', 'verified_at' => now()])->save();
        $this->moveTo($order, 'shipped', ['courier_service_id' => $this->courier->id]);
        $this->moveTo($order, 'delivered');
        $this->moveTo($order, 'completed')->assertSessionHas('success');

        $this->moveTo($order, 'return_requested', ['return_reason' => 'Wrong size'])->assertSessionHas('success');
        $order->refresh();
        $this->assertSame('Wrong size', $order->return_reason);
        $this->assertNotNull($order->return_requested_at);

        // Settled orders cannot go back to "delivered" — decline returns them to completed.
        $this->moveTo($order, 'delivered')->assertSessionHas('error');
        $this->moveTo($order, 'completed')->assertSessionHas('success');

        $this->moveTo($order, 'refunded')->assertSessionHas('success');
        $this->assertSame(10, (int) $this->product->fresh()->stock_quantity);
        $this->assertSame(1, $this->txCount($order, 'web_refund'));
        $this->assertSame(1, $this->txCount($order, 'web_settlement'));
    }

    public function test_legacy_pending_orders_follow_the_new_workflow(): void
    {
        $order = $this->placeOrder(1);
        $order->forceFill(['status' => 'pending_fulfillment'])->save();

        $this->actingAs($this->admin, 'admin')->get(route('online-orders.index'))->assertOk();
        $this->actingAs($this->admin, 'admin')->get(route('online-orders.show', $order))->assertOk()->assertSee('Verify this order');

        $this->moveTo($order, 'confirmed', ['verification_method' => 'whatsapp'])->assertSessionHas('success');
        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_only_shop_admins_of_the_same_shop_can_change_status(): void
    {
        $order = $this->placeOrder(1);

        $cashier = $this->staff('Cashier');
        $this->actingAs($cashier, 'admin')->postJson(route('online-orders.verify', $order), ['verification_method' => 'sms'])->assertForbidden();

        $other = Shop::withoutEvents(fn () => Shop::create(['name' => 'Other', 'is_active' => true]));
        $foreignAdmin = $this->staff('Admin', $other);
        $this->actingAs($foreignAdmin, 'admin')->postJson(route('online-orders.verify', $order), ['verification_method' => 'sms'])->assertForbidden();
        $this->actingAs($foreignAdmin, 'admin')->getJson(route('online-orders.show', $order))->assertForbidden();

        $this->assertSame('new', $order->fresh()->status);
    }

    public function test_retail_ledger_return_on_unshipped_order_cancels_without_phantom_stock(): void
    {
        config(['modules.retail.enabled' => true]);
        $order = $this->placeOrder(2);

        $this->actingAs($this->admin, 'admin')->post(route('sales.return', $order))->assertRedirect();

        $product = $this->product->fresh();
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(10, (int) $product->stock_quantity);
        $this->assertSame(0, (int) $product->reserved_stock);
    }

    public function test_retail_ledger_routes_are_hidden_when_pos_is_off(): void
    {
        config(['modules.retail.enabled' => false]);
        $order = $this->placeOrder(1);

        $this->actingAs($this->admin, 'admin')->post(route('sales.return', $order))->assertNotFound();
        $this->assertSame('new', $order->fresh()->status);
        $this->assertSame(1, Customer::count());
    }
}
