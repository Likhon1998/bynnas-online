<?php

namespace Tests\Feature;

use App\Models\CourierService;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\CustomerOrderUpdate;
use App\Notifications\StaffAlert;
use App\Services\CommerceAlertService;
use App\Services\OrderCreationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CommerceNotificationTest extends TestCase
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
            'shop_id' => $this->shop->id, 'name' => 'Linen Shirt', 'barcode' => 'SHIRT-1',
            'cost_price' => 500, 'selling_price' => 1200, 'stock_quantity' => 6, 'alert_quantity' => 5, 'is_published' => true,
        ]);
        $this->courier = CourierService::create(['shop_id' => $this->shop->id, 'name' => 'Steadfast', 'is_active' => true]);
    }

    private function staff(string $role, ?Shop $shop = null): User
    {
        $user = User::factory()->create();
        $user->forceFill(['shop_id' => ($shop ?? $this->shop)->id, 'role' => $role === 'Admin' ? 'admin' : strtolower($role)])->save();
        $user->assignRole($role);

        return $user;
    }

    private function place(int $qty = 1, ?User $buyer = null, string $phone = '01711000000'): Order
    {
        return app(OrderCreationService::class)->place(
            $this->shop->id,
            ['name' => 'Rima', 'phone' => $phone, 'address' => 'Dhanmondi, Dhaka'],
            [['id' => $this->product->id, 'qty' => $qty]],
            $buyer,
            ['zone' => 'inside_dhaka', 'payment_method' => 'cash_on_delivery'],
        )['order'];
    }

    private function alerts(User $user, string $kind)
    {
        return $user->notifications()->where('type', StaffAlert::class)->where('data', 'like', '%"kind":"'.$kind.'"%');
    }

    public function test_low_stock_alert_is_sent_once_per_day(): void
    {
        $inventory = $this->staff('Manager');

        $this->place(1);
        $this->assertSame(1, $this->alerts($this->admin, 'low_stock')->count());
        $this->assertSame(1, $this->alerts($inventory, 'low_stock')->count(), 'Staff with inventory access are alerted.');
        $this->assertStringContainsString('5 available', $this->alerts($this->admin, 'low_stock')->first()->data['body']);

        $this->place(1);
        $this->assertSame(1, $this->alerts($this->admin, 'low_stock')->count(), 'No repeat alert within 24h.');

        $this->travel(25)->hours();
        $this->place(4);
        $this->assertSame(2, $this->alerts($this->admin, 'low_stock')->count());
        $this->assertStringContainsString('Out of stock', $this->alerts($this->admin, 'low_stock')->latest()->first()->data['title']);
    }

    public function test_customer_gets_order_updates_through_the_workflow(): void
    {
        Notification::fake();

        $buyer = User::factory()->create(['email' => 'rima@example.com']);
        $buyer->forceFill(['shop_id' => $this->shop->id])->save();
        $buyer->assignRole('Customer');

        $order = $this->place(1, $buyer);
        Notification::assertSentTo($buyer, CustomerOrderUpdate::class, fn ($n, $channels) => $n->event === 'placed'
            && in_array('database', $channels, true) && in_array('mail', $channels, true));

        $this->actingAs($this->admin, 'admin')->post(route('online-orders.verify', $order), ['verification_method' => 'whatsapp'])->assertSessionHas('success');
        Notification::assertSentTo($buyer, CustomerOrderUpdate::class, fn ($n) => $n->event === 'confirmed');

        foreach (['processing', 'packed'] as $status) {
            $this->actingAs($this->admin, 'admin')->post(route('online-orders.update-status', $order), ['status' => $status])->assertSessionHas('success');
        }
        $this->actingAs($this->admin, 'admin')->post(route('online-orders.update-status', $order), [
            'status' => 'shipped', 'courier_service_id' => $this->courier->id, 'tracking_number' => 'SF-99',
        ])->assertSessionHas('success');
        Notification::assertSentTo($buyer, CustomerOrderUpdate::class, function ($n) {
            return $n->event === 'shipped' && str_contains($n->toMail($n->order->customer->user)->render(), 'SF-99');
        });

        $this->actingAs($this->admin, 'admin')->post(route('online-orders.update-status', $order), ['status' => 'delivered'])->assertSessionHas('success');
        Notification::assertSentTo($buyer, CustomerOrderUpdate::class, fn ($n) => $n->event === 'delivered');

        // processing / packed are internal steps: no customer message.
        Notification::assertSentToTimes($buyer, CustomerOrderUpdate::class, 4);
    }

    public function test_guest_with_email_gets_mail_and_guest_without_email_gets_nothing(): void
    {
        Notification::fake();

        Customer::create(['shop_id' => $this->shop->id, 'name' => 'Guest', 'phone' => '01999888777', 'email' => 'guest@example.com']);
        $this->place(1, null, '01999888777');
        Notification::assertSentOnDemand(CustomerOrderUpdate::class, fn ($n, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'guest@example.com' && $channels === ['mail']);

        Notification::fake();
        $order = $this->place(1, null, '01666555444');
        $order->update(['status' => 'confirmed']);
        Notification::assertNothingSentTo(new AnonymousNotifiable);
        Notification::assertNotSentTo($order->customer, CustomerOrderUpdate::class);
    }

    public function test_delivery_delay_and_return_request_alert_admins(): void
    {
        $order = $this->place(1);
        $order->forceFill(['status' => 'shipped', 'shipped_at' => now(), 'courier_service_id' => $this->courier->id, 'shipping_tracking_no' => 'SF-1'])->save();

        $alerts = app(CommerceAlertService::class);
        $this->assertSame(0, $alerts->scanDelayedShipments());

        $this->travel(6)->days();
        $this->assertSame(1, $alerts->scanDelayedShipments());
        $this->assertSame(0, $alerts->scanDelayedShipments(), 'One alert per delayed order.');
        $this->assertStringContainsString('SF-1', $this->alerts($this->admin, 'delivery_issue')->first()->data['body']);

        $this->actingAs($this->admin, 'admin')->post(route('online-orders.update-status', $order), ['status' => 'delivered'])->assertSessionHas('success');
        $this->actingAs($this->admin, 'admin')->post(route('online-orders.update-status', $order), [
            'status' => 'return_requested', 'return_reason' => 'Wrong size',
        ])->assertSessionHas('success');

        $issue = $this->alerts($this->admin, 'delivery_issue')->where('data', 'like', '%Return requested%')->first();
        $this->assertNotNull($issue);
        $this->assertSame('Wrong size', $issue->data['body']);

        $this->artisan('commerce:scan-alerts')->assertSuccessful();
    }

    public function test_alert_feed_is_private_per_user(): void
    {
        $this->place(1);
        $alert = $this->alerts($this->admin, 'low_stock')->firstOrFail();

        $this->actingAs($this->admin, 'admin')->getJson(route('notifications.feed'))
            ->assertOk()->assertJsonPath('unread', 1)->assertJsonPath('items.0.kind', 'low_stock');

        $other = Shop::withoutEvents(fn () => Shop::create(['name' => 'Other', 'is_active' => true]));
        $otherAdmin = $this->staff('Admin', $other);
        $this->actingAs($otherAdmin, 'admin')->getJson(route('notifications.feed'))->assertOk()->assertJsonPath('unread', 0);
        $this->actingAs($otherAdmin, 'admin')->postJson(route('notifications.read', $alert->id))->assertNotFound();
        $this->assertNull($alert->fresh()->read_at);

        $this->actingAs($this->admin, 'admin')->postJson(route('notifications.read', $alert->id))->assertOk();
        $this->assertNotNull($alert->fresh()->read_at);
        $this->actingAs($this->admin, 'admin')->get(route('notifications.index'))->assertOk()->assertSee('Low stock');
    }

    public function test_pos_orders_do_not_trigger_customer_messages(): void
    {
        Notification::fake();
        $customer = Customer::create(['shop_id' => $this->shop->id, 'name' => 'Walk-in', 'phone' => '01700000001', 'email' => 'walkin@example.com']);
        $order = Order::create([
            'shop_id' => $this->shop->id, 'user_id' => $this->admin->id, 'customer_id' => $customer->id,
            'invoice_no' => 'INV-1', 'total_amount' => 500, 'paid_amount' => 500, 'status' => 'completed', 'counter_id' => null,
        ]);

        $this->assertFalse(app(\App\Services\CustomerNotifier::class)->orderUpdate($order, 'confirmed'));
        Notification::assertNothingSent();
    }
}
