<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CmsReview;
use App\Models\Customer;
use App\Models\LandingPage;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageOrderTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/120 Mobile Safari/537.36';

    private Shop $shop;

    private User $admin;

    private Product $tee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->shop = Shop::create(['name' => 'Test Shop', 'is_active' => true]);
        $this->admin = $this->staff('Admin');
        $this->tee = Product::create([
            'shop_id' => $this->shop->id, 'name' => 'Classic Tee', 'barcode' => 'TEE-1',
            'cost_price' => 200, 'selling_price' => 500, 'stock_quantity' => 5, 'is_published' => true,
        ]);
    }

    private function staff(string $role, ?Shop $shop = null): User
    {
        $user = User::factory()->create();
        $user->forceFill(['shop_id' => ($shop ?? $this->shop)->id, 'role' => 'admin'])->save();
        $user->assignRole($role);

        return $user;
    }

    private function page(array $attributes = []): LandingPage
    {
        return LandingPage::create(array_merge([
            'shop_id' => $this->shop->id, 'title' => 'Tee offer', 'status' => 'published',
            'headline' => 'The softest tee you will own', 'product_ids' => [$this->tee->id],
        ], $attributes));
    }

    private function orderPayload(array $overrides = []): array
    {
        return array_merge([
            'product_id' => $this->tee->id, 'qty' => 2,
            'customer_name' => 'Rima Akter', 'customer_phone' => '01711-000000',
            'customer_address' => 'House 5, Road 2, Dhanmondi, Dhaka',
            'delivery_zone' => 'inside_dhaka', 'payment_method' => 'cash_on_delivery',
        ], $overrides);
    }

    public function test_admin_manages_landing_pages(): void
    {
        $this->actingAs($this->admin, 'admin')->get(route('landing-pages.index'))->assertOk()->assertSee('No landing pages yet');
        $this->actingAs($this->admin, 'admin')->get(route('landing-pages.create'))->assertOk()->assertSee('Classic Tee');

        $this->actingAs($this->admin, 'admin')->post(route('landing-pages.store'), [
            'title' => 'Eid Tee Offer', 'status' => 'published', 'headline' => 'Eid tees',
        ])->assertSessionHasErrors('product_ids');

        $this->actingAs($this->admin, 'admin')->post(route('landing-pages.store'), [
            'title' => 'Eid Tee Offer', 'status' => 'published', 'headline' => 'Eid tees', 'product_ids' => [$this->tee->id],
            'benefits' => [['title' => 'Cash on delivery', 'text' => 'Pay later'], ['title' => '', 'text' => 'ignored']],
            'faqs' => [['q' => 'Sizes?', 'a' => 'S to XXL'], ['q' => 'No answer', 'a' => '']],
            'countdown_ends_at' => now()->addDay()->format('Y-m-d\TH:i'), 'accent_color' => '#e11d48', 'show_reviews' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $page = LandingPage::firstOrFail();
        $this->assertSame('eid-tee-offer', $page->slug);
        $this->assertCount(1, $page->benefits);
        $this->assertCount(1, $page->faqs);
        $this->assertSame([$this->tee->id], $page->product_ids);

        $this->actingAs($this->admin, 'admin')->post(route('landing-pages.store'), [
            'title' => 'Eid Tee Offer', 'status' => 'draft', 'headline' => 'Copy',
        ])->assertSessionHasNoErrors();
        $this->assertSame('eid-tee-offer-2', LandingPage::latest('id')->value('slug'));

        $this->actingAs($this->admin, 'admin')->get(route('landing-pages.edit', $page))->assertOk()->assertSee('/campaign/eid-tee-offer');
        $this->actingAs($this->admin, 'admin')->put(route('landing-pages.update', $page), [
            'title' => 'Eid Tee Offer', 'slug' => 'eid-tees', 'status' => 'draft', 'headline' => 'Eid tees', 'product_ids' => [$this->tee->id],
        ])->assertRedirect(route('landing-pages.edit', $page));
        $this->assertSame('eid-tees', $page->fresh()->slug);

        $this->actingAs($this->admin, 'admin')->delete(route('landing-pages.destroy', $page))->assertRedirect(route('landing-pages.index'));
        $this->assertNull(LandingPage::find($page->id));
    }

    public function test_cashier_is_denied_and_other_shop_pages_are_hidden(): void
    {
        $this->actingAs($this->staff('Cashier'), 'admin')->getJson(route('landing-pages.index'))->assertForbidden();

        $other = Shop::withoutEvents(fn () => Shop::create(['name' => 'Other', 'is_active' => true]));
        $foreign = $this->page(['shop_id' => $other->id, 'slug' => 'foreign']);
        $this->actingAs($this->admin, 'admin')->get(route('landing-pages.edit', $foreign))->assertNotFound();
    }

    public function test_public_page_renders_sections_and_counts_real_views(): void
    {
        CmsReview::create(['shop_id' => $this->shop->id, 'product_id' => $this->tee->id, 'customer_name' => 'Nabila', 'rating' => 5, 'body' => 'Fits perfectly', 'is_published' => true]);
        $page = $this->page([
            'offer_badge' => '30% OFF', 'offer_text' => 'Free delivery today', 'countdown_ends_at' => now()->addHours(5),
            'benefits' => [['title' => 'Cash on delivery', 'text' => 'Pay at your door']],
            'faqs' => [['q' => 'Can I exchange?', 'a' => 'Yes within 3 days']],
        ]);

        $this->withHeader('User-Agent', self::BROWSER)->get('/campaign/'.$page->slug)->assertOk()
            ->assertSee('The softest tee you will own')->assertSee('30% OFF')->assertSee('Offer ends in')
            ->assertSee('Classic Tee')->assertSee('Pay at your door')->assertSee('Can I exchange?')
            ->assertSee('Fits perfectly')->assertSee('Place your order');
        $this->withHeader('User-Agent', self::BROWSER)->get('/campaign/'.$page->slug)->assertOk();
        $this->assertSame(1, $page->fresh()->views);

        $this->flushSession();
        $this->withHeader('User-Agent', 'facebookexternalhit/1.1')->get('/campaign/'.$page->slug)->assertOk();
        $this->assertSame(1, $page->fresh()->views);
    }

    public function test_drafts_are_hidden_from_visitors_but_previewable_by_staff(): void
    {
        $page = $this->page(['status' => 'draft']);

        $this->get('/campaign/'.$page->slug)->assertNotFound();
        $this->post('/campaign/'.$page->slug.'/order', $this->orderPayload())->assertNotFound();
        $this->actingAs($this->admin, 'admin')->get('/campaign/'.$page->slug)->assertOk()->assertSee('Draft preview');
        $this->assertSame(0, $page->fresh()->views);
    }

    public function test_guest_orders_without_an_account_and_is_matched_by_phone(): void
    {
        $page = $this->page();

        $this->post('/campaign/'.$page->slug.'/order', $this->orderPayload(['note' => 'Size L please']))
            ->assertRedirect(route('website.landing', $page->slug).'#order')
            ->assertSessionHas('landing_order');

        $order = Order::latest('id')->firstOrFail();
        $this->assertStringStartsWith('WEB-', $order->invoice_no);
        $this->assertSame('new', $order->status);
        $this->assertSame($page->id, $order->landing_page_id);
        $this->assertSame('Rima Akter', $order->delivery_name);
        $this->assertSame('01711-000000', $order->delivery_phone);
        $this->assertSame('House 5, Road 2, Dhanmondi, Dhaka', $order->delivery_address);
        $this->assertSame('Size L please', $order->customer_note);
        $this->assertEquals(1060, (float) $order->total_amount);
        $this->assertSame(2, (int) $order->items()->sum('quantity'));
        $this->assertSame(2, (int) $this->tee->fresh()->reserved_stock);
        $this->assertNull($order->customer->user_id);

        $this->post('/campaign/'.$page->slug.'/order', $this->orderPayload(['qty' => 1, 'customer_phone' => '+8801711000000', 'customer_address' => 'New flat, Mirpur']));
        $this->assertSame(1, Customer::count());
        $this->assertSame('New flat, Mirpur', Customer::first()->address);
        $this->assertSame(2, Order::count());

        $this->get(route('website.landing', $page->slug))->assertOk();
        $this->post(route('website.track.lookup'), ['invoice_no' => $order->invoice_no, 'phone' => '8801711000000'])->assertOk()->assertSee($order->invoice_no);
    }

    public function test_guest_order_does_not_overwrite_a_registered_customer_profile(): void
    {
        $page = $this->page();
        $member = User::factory()->create();
        $member->assignRole('Customer');
        $customer = Customer::create(['shop_id' => $this->shop->id, 'user_id' => $member->id, 'name' => 'Real Owner', 'phone' => '01711000000', 'address' => 'Owner address']);

        $this->post('/campaign/'.$page->slug.'/order', $this->orderPayload(['customer_name' => 'Someone Else']))->assertSessionHas('landing_order');

        $order = Order::latest('id')->firstOrFail();
        $this->assertSame($customer->id, $order->customer_id);
        $this->assertSame('Someone Else', $order->delivery_name);
        $this->assertSame('Real Owner', $customer->fresh()->name);
        $this->assertSame('Owner address', $customer->fresh()->address);
    }

    public function test_invalid_orders_are_rejected(): void
    {
        $page = $this->page();
        $otherProduct = Product::create([
            'shop_id' => $this->shop->id, 'name' => 'Not On Page', 'barcode' => 'X-9',
            'cost_price' => 1, 'selling_price' => 2, 'stock_quantity' => 9, 'is_published' => true,
        ]);

        $this->post('/campaign/'.$page->slug.'/order', $this->orderPayload(['product_id' => $otherProduct->id]))->assertSessionHasErrors('order');
        $this->post('/campaign/'.$page->slug.'/order', $this->orderPayload(['qty' => 6]))->assertSessionHasErrors('order');
        $this->post('/campaign/'.$page->slug.'/order', $this->orderPayload(['customer_phone' => 'call me']))->assertSessionHasErrors('customer_phone');
        $this->post('/campaign/'.$page->slug.'/order', $this->orderPayload(['website' => 'http://spam']))->assertSessionHasErrors('website');
        $this->postJson('/campaign/'.$page->slug.'/order', $this->orderPayload(['qty' => 6]))->assertStatus(422)->assertJson(['success' => false]);

        $this->assertSame(0, Order::count());
        $this->assertSame(0, (int) $this->tee->fresh()->reserved_stock);
    }

    public function test_landing_orders_are_attributed_to_the_linked_campaign_unless_another_source_is_tracked(): void
    {
        $campaign = Campaign::create([
            'shop_id' => $this->shop->id, 'name' => 'Tee Reels', 'source' => 'instagram', 'medium' => 'story',
            'utm_campaign' => 'tee_reels', 'landing_type' => 'landing', 'status' => 'active',
        ]);
        $page = $this->page(['campaign_id' => $campaign->id]);

        $this->assertSame('/campaign/'.$page->slug, $campaign->landingPath());

        $this->post('/campaign/'.$page->slug.'/order', $this->orderPayload(['qty' => 1]));
        $order = Order::latest('id')->firstOrFail();
        $this->assertSame($campaign->id, $order->campaign_id);
        $this->assertSame('instagram', $order->utm_source);

        $fb = Campaign::create([
            'shop_id' => $this->shop->id, 'name' => 'FB Ads', 'source' => 'facebook', 'medium' => 'paid_social',
            'utm_campaign' => 'fb_ads', 'landing_type' => 'landing', 'status' => 'active',
        ]);
        $this->withHeader('User-Agent', self::BROWSER)->get('/go/'.$fb->code)->assertRedirect();
        $this->post('/campaign/'.$page->slug.'/order', $this->orderPayload(['qty' => 1, 'customer_phone' => '01822000000']));
        $second = Order::latest('id')->firstOrFail();
        $this->assertSame($fb->id, $second->campaign_id);
        $this->assertSame('facebook', $second->utm_source);

        $this->actingAs($this->admin, 'admin')->get(route('online-orders.show', $second))->assertOk()
            ->assertSee('Ordered from landing page')->assertSee('Tee offer')->assertSee('FB Ads');
    }

    public function test_storefront_checkout_and_buy_now_use_the_shared_order_service(): void
    {
        $this->get('/product/'.$this->tee->slug)->assertOk()->assertSee('Buy Now');

        $buyer = User::factory()->create();
        $buyer->assignRole('Customer');
        $guestRecord = Customer::create(['shop_id' => $this->shop->id, 'name' => 'Old guest', 'phone' => '01933000000']);

        $this->actingAs($buyer, 'web')->postJson(route('website.checkout'), [
            'customer_name' => 'Tanvir', 'customer_phone' => '01933-000000', 'customer_address' => 'Uttara  Sector 7',
            'delivery_zone' => 'outside_dhaka', 'cart' => [['id' => $this->tee->id, 'qty' => 1], ['id' => $this->tee->id, 'qty' => 1]],
        ])->assertOk()->assertJson(['success' => true]);

        $order = Order::latest('id')->firstOrFail();
        $this->assertSame($guestRecord->id, $order->customer_id);
        $this->assertSame($buyer->id, $guestRecord->fresh()->user_id);
        $this->assertSame('Uttara Sector 7', $order->delivery_address);
        $this->assertSame(1, $order->items()->count());
        $this->assertSame(2, (int) $order->items()->value('quantity'));
        $this->assertNull($order->landing_page_id);

        $this->actingAs($buyer, 'web')->postJson(route('website.checkout'), [
            'customer_name' => 'Tanvir', 'customer_phone' => '01933000000', 'customer_address' => 'Uttara',
            'cart' => [['id' => $this->tee->id, 'qty' => 99]],
        ])->assertOk()->assertJson(['success' => false]);
    }
}
