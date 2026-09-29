<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\StaffAlert;
use App\Services\OrderCreationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    private User $admin;

    private User $manager;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->shop = Shop::create(['name' => 'Test Shop', 'is_active' => true]);
        $this->admin = $this->staff('Admin');
        $this->manager = $this->staff('Manager');
        $this->product = Product::create([
            'shop_id' => $this->shop->id, 'name' => 'Silk Saree', 'barcode' => 'SAREE-1',
            'cost_price' => 1500, 'selling_price' => 3200, 'stock_quantity' => 5, 'is_published' => true,
        ]);
    }

    private function staff(string $role, ?Shop $shop = null): User
    {
        $user = User::factory()->create();
        $user->forceFill(['shop_id' => ($shop ?? $this->shop)->id, 'role' => $role === 'Admin' ? 'admin' : strtolower($role)])->save();
        $user->assignRole($role);

        return $user;
    }

    private function createLead(array $data = [], ?User $as = null): Lead
    {
        $this->actingAs($as ?? $this->manager, 'admin')->post(route('leads.store'), array_merge([
            'name' => 'Nusrat',
            'phone' => '01812000000',
            'source' => 'facebook',
            'conversation_ref' => 'https://facebook.com/messages/t/123',
            'product_id' => $this->product->id,
        ], $data))->assertRedirect();

        return Lead::latest('id')->firstOrFail();
    }

    public function test_manager_creates_lead_and_admin_is_alerted(): void
    {
        $lead = $this->createLead();

        $this->assertSame('new', $lead->status);
        $this->assertSame($this->shop->id, $lead->shop_id);
        $this->assertSame($this->manager->id, $lead->created_by);
        $this->assertSame(1, $lead->activities()->count());

        $this->assertSame(1, $this->admin->notifications()->where('type', StaffAlert::class)->count());
        $this->assertSame(0, $this->manager->notifications()->count(), 'Creator is not alerted about their own lead.');

        $this->actingAs($this->manager, 'admin')->get(route('leads.index'))->assertOk()->assertSee('Nusrat');
        $this->actingAs($this->manager, 'admin')->get(route('leads.show', $lead))->assertOk()->assertSee('Create order');
    }

    public function test_lead_converts_to_order_through_shared_checkout(): void
    {
        $lead = $this->createLead();

        $response = $this->actingAs($this->manager, 'admin')->post(route('leads.convert-order', $lead), [
            'items' => [['id' => $this->product->id, 'qty' => 2]],
            'address' => 'House 5, Road 2, Uttara',
            'delivery_zone' => 'inside_dhaka',
            'payment_method' => 'cash_on_delivery',
        ]);

        $order = Order::latest('id')->firstOrFail();
        $response->assertRedirect(route('online-orders.show', $order));

        $this->assertSame('new', $order->status);
        $this->assertSame($lead->id, $order->lead_id);
        $this->assertStringStartsWith('WEB-', $order->invoice_no);
        $this->assertSame('facebook', $order->utm_source);
        $this->assertSame('message', $order->utm_medium);
        $this->assertSame(2, (int) $this->product->fresh()->reserved_stock);

        $lead->refresh();
        $this->assertSame('converted', $lead->status);
        $this->assertSame($order->id, $lead->order_id);
        $this->assertSame($order->customer_id, $lead->customer_id);
        $this->assertNotNull($lead->converted_at);

        // Converted leads cannot place a second order or be moved back by hand.
        $this->actingAs($this->manager, 'admin')->post(route('leads.convert-order', $lead), [
            'items' => [['id' => $this->product->id, 'qty' => 1]], 'address' => 'House 5, Road 2, Uttara',
        ])->assertSessionHas('error');
        $this->actingAs($this->manager, 'admin')->post(route('leads.status', $lead), ['status' => 'new'])->assertSessionHas('error');
        $this->assertSame(1, Order::count());
    }

    public function test_website_order_with_same_phone_links_open_lead(): void
    {
        $lead = $this->createLead(['phone' => '01812000000']);

        $order = app(OrderCreationService::class)->place(
            $this->shop->id,
            ['name' => 'Nusrat', 'phone' => '+8801812000000', 'address' => 'Mirpur 10, Dhaka'],
            [['id' => $this->product->id, 'qty' => 1]],
        )['order'];

        $this->assertSame($lead->id, $order->fresh()->lead_id);
        $this->assertSame('converted', $lead->fresh()->status);
        $this->assertSame($order->id, $lead->fresh()->order_id);
    }

    public function test_convert_to_customer_links_existing_customer_without_overwriting(): void
    {
        $existing = Customer::create(['shop_id' => $this->shop->id, 'name' => 'Nusrat Jahan', 'phone' => '01812000000', 'address' => 'Old address, Banani']);
        $lead = $this->createLead(['name' => 'Nusrat FB']);
        $this->assertSame($existing->id, $lead->customer_id, 'Existing customer is matched by phone on create.');

        $lead->update(['customer_id' => null]);
        $this->actingAs($this->manager, 'admin')->post(route('leads.convert-customer', $lead), ['address' => 'New address'])
            ->assertSessionHas('success');

        $this->assertSame($existing->id, $lead->fresh()->customer_id);
        $this->assertSame('Old address, Banani', $existing->fresh()->address);
        $this->assertSame('Nusrat Jahan', $existing->fresh()->name);
        $this->assertSame(1, Customer::count());
    }

    public function test_contact_logging_and_status_rules(): void
    {
        $lead = $this->createLead();

        $this->actingAs($this->manager, 'admin')->post(route('leads.activity', $lead), ['type' => 'call', 'body' => 'Wants red colour'])
            ->assertSessionHas('success');
        $lead->refresh();
        $this->assertSame('contacted', $lead->status);
        $this->assertNotNull($lead->last_contacted_at);

        $this->actingAs($this->manager, 'admin')->post(route('leads.activity', $lead), [
            'type' => 'message', 'body' => 'Sent price', 'follow_up_at' => now()->addDay()->format('Y-m-d H:i'),
        ])->assertSessionHas('success');
        $this->assertSame('follow_up', $lead->fresh()->status);

        // "converted" only comes from a real order.
        $this->actingAs($this->manager, 'admin')->post(route('leads.status', $lead), ['status' => 'converted'])
            ->assertSessionHasErrors('status');

        $this->actingAs($this->manager, 'admin')->post(route('leads.status', $lead), ['status' => 'lost', 'lost_reason' => 'Bought elsewhere'])
            ->assertSessionHas('success');
        $lead->refresh();
        $this->assertSame('lost', $lead->status);
        $this->assertSame('Bought elsewhere', $lead->lost_reason);
        $this->assertNull($lead->follow_up_at);
    }

    public function test_assignment_notifies_assignee_and_rejects_foreign_staff(): void
    {
        $lead = $this->createLead([], $this->admin);
        $agent = $this->staff('Manager');

        $this->actingAs($this->admin, 'admin')->post(route('leads.assign', $lead), ['assigned_to' => $agent->id])->assertSessionHas('success');
        $this->assertSame($agent->id, $lead->fresh()->assigned_to);
        $this->assertTrue($agent->notifications()->where('data', 'like', '%Lead assigned to you%')->exists());

        $other = Shop::withoutEvents(fn () => Shop::create(['name' => 'Other Shop', 'is_active' => true]));
        $outsider = $this->staff('Manager', $other);
        $this->actingAs($this->admin, 'admin')->post(route('leads.assign', $lead), ['assigned_to' => $outsider->id])
            ->assertSessionHasErrors('assigned_to');
        $this->assertSame($agent->id, $lead->fresh()->assigned_to);
    }

    public function test_leads_are_isolated_per_shop_and_need_permission(): void
    {
        $lead = $this->createLead();

        $other = Shop::withoutEvents(fn () => Shop::create(['name' => 'Other Shop', 'is_active' => true]));
        $otherAdmin = $this->staff('Admin', $other);
        $this->actingAs($otherAdmin, 'admin')->get(route('leads.show', $lead))->assertNotFound();
        $this->actingAs($otherAdmin, 'admin')->post(route('leads.status', $lead), ['status' => 'lost'])->assertNotFound();
        $this->actingAs($otherAdmin, 'admin')->get(route('leads.index'))->assertOk()->assertDontSee('Nusrat');

        $foreignProduct = Product::create(['shop_id' => $other->id, 'name' => 'Foreign', 'barcode' => 'F-1', 'cost_price' => 1, 'selling_price' => 2, 'stock_quantity' => 3]);
        $this->actingAs($this->manager, 'admin')->post(route('leads.store'), [
            'name' => 'X', 'source' => 'tiktok', 'product_id' => $foreignProduct->id,
        ])->assertSessionHasErrors('product_id');

        $cashier = $this->staff('Cashier');
        $this->actingAs($cashier, 'admin')->getJson(route('leads.index'))->assertForbidden();
        $this->actingAs($cashier, 'admin')->postJson(route('leads.store'), ['name' => 'Y', 'source' => 'other'])->assertForbidden();

        // Only shop owners delete leads.
        $this->actingAs($this->manager, 'admin')->deleteJson(route('leads.destroy', $lead))->assertForbidden();
        $this->actingAs($this->admin, 'admin')->delete(route('leads.destroy', $lead))->assertRedirect(route('leads.index'));
        $this->assertNull(Lead::find($lead->id));
    }
}
