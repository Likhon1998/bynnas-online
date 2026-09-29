<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignVisit;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\CampaignAttributionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignAttributionTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile Safari/604.1';

    private Shop $shop;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->shop = Shop::create(['name' => 'Test Shop', 'is_active' => true]);
        $this->admin = $this->staff('Admin');
    }

    private function staff(string $role, ?Shop $shop = null): User
    {
        $user = User::factory()->create();
        $user->forceFill(['shop_id' => ($shop ?? $this->shop)->id])->save();
        $user->assignRole($role);

        return $user;
    }

    private function campaign(array $attributes = []): Campaign
    {
        return Campaign::create(array_merge([
            'shop_id' => $this->shop->id, 'name' => 'Eid Sale', 'source' => 'facebook', 'medium' => 'paid_social',
            'utm_campaign' => 'eid_sale', 'landing_type' => 'shop', 'status' => 'active',
        ], $attributes));
    }

    private function browser(): static
    {
        return $this->withHeader('User-Agent', self::BROWSER);
    }

    public function test_admin_can_create_update_and_delete_campaigns(): void
    {
        $product = Product::create([
            'shop_id' => $this->shop->id, 'name' => 'Linen Shirt', 'barcode' => 'LS-1',
            'cost_price' => 100, 'selling_price' => 200, 'stock_quantity' => 5, 'is_published' => true,
        ]);

        $this->actingAs($this->admin, 'admin')->get(route('campaigns.index'))->assertOk()->assertSee('No campaigns yet');
        $this->actingAs($this->admin, 'admin')->get(route('campaigns.create'))->assertOk()->assertSee('Linen Shirt');

        $this->actingAs($this->admin, 'admin')->post(route('campaigns.store'), [
            'name' => 'Summer Linen Reel', 'source' => 'instagram', 'medium' => 'story',
            'landing_type' => 'product', 'product_id' => $product->id, 'status' => 'active', 'spend' => 1500,
        ])->assertSessionHasNoErrors();

        $campaign = Campaign::firstOrFail();
        $this->assertSame('summer_linen_reel', $campaign->utm_campaign);
        $this->assertSame($this->admin->id, $campaign->created_by);
        $this->assertMatchesRegularExpression('/^[a-z0-9]{7}$/', $campaign->code);

        $this->actingAs($this->admin, 'admin')->get(route('campaigns.show', $campaign))
            ->assertOk()->assertSee($campaign->trackingUrl())->assertSee('Product: Linen Shirt');
        $this->actingAs($this->admin, 'admin')->get(route('campaigns.index'))->assertOk()->assertSee('Summer Linen Reel');

        $this->actingAs($this->admin, 'admin')->put(route('campaigns.update', $campaign), [
            'name' => 'Summer Linen Reel', 'source' => 'instagram', 'medium' => 'story', 'utm_campaign' => 'summer_linen_reel',
            'landing_type' => 'home', 'product_id' => $product->id, 'status' => 'paused',
        ])->assertRedirect(route('campaigns.show', $campaign));
        $campaign->refresh();
        $this->assertSame('paused', $campaign->status);
        $this->assertNull($campaign->product_id);

        $this->actingAs($this->admin, 'admin')->post(route('campaigns.store'), [
            'name' => 'Duplicate', 'utm_campaign' => 'summer_linen_reel', 'source' => 'facebook', 'medium' => 'paid_social',
            'landing_type' => 'home', 'status' => 'active',
        ])->assertSessionHasErrors('utm_campaign');

        $this->actingAs($this->admin, 'admin')->delete(route('campaigns.destroy', $campaign))->assertRedirect(route('campaigns.index'));
        $this->assertSame(0, Campaign::count());
    }

    public function test_external_landing_urls_are_rejected(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('campaigns.store'), [
            'name' => 'Bad', 'source' => 'facebook', 'medium' => 'paid_social',
            'landing_type' => 'url', 'landing_url' => 'https://evil.example.com/phish', 'status' => 'active',
        ])->assertSessionHasErrors('landing_url');

        $this->actingAs($this->admin, 'admin')->post(route('campaigns.store'), [
            'name' => 'Deals', 'source' => 'facebook', 'medium' => 'paid_social',
            'landing_type' => 'url', 'landing_url' => 'shop?filter=deals', 'status' => 'active',
        ])->assertSessionHasNoErrors();
        $this->assertSame('/shop?filter=deals', Campaign::where('name', 'Deals')->value('landing_url'));

        $this->assertSame('/', Campaign::normalizePath('//evil.example.com'));
        $this->assertSame('/', Campaign::normalizePath('https://evil.example.com/x'));
        $this->assertStringStartsWith('/', Campaign::normalizePath('javascript:alert(1)'));
    }

    public function test_cashier_is_denied_and_other_shop_campaigns_are_hidden(): void
    {
        $cashier = $this->staff('Cashier');
        $this->actingAs($cashier, 'admin')->getJson(route('campaigns.index'))->assertForbidden();

        $otherShop = Shop::withoutEvents(fn () => Shop::create(['name' => 'Other', 'is_active' => true]));
        $foreign = $this->campaign(['shop_id' => $otherShop->id]);

        $this->actingAs($this->admin, 'admin')->get(route('campaigns.show', $foreign))->assertNotFound();
        $this->actingAs($this->admin, 'admin')->delete(route('campaigns.destroy', $foreign))->assertNotFound();
        $this->assertTrue(Campaign::whereKey($foreign->id)->exists());
    }

    public function test_tracking_link_records_a_click_and_redirects_with_utm(): void
    {
        $campaign = $this->campaign();

        $response = $this->browser()->withHeader('Referer', 'https://m.facebook.com/')->get('/go/'.$campaign->code.'?c=Reel 1');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('/shop?', $location);
        $this->assertStringContainsString('utm_source=facebook', $location);
        $this->assertStringContainsString('utm_campaign=eid_sale', $location);
        $this->assertStringContainsString('utm_content=reel-1', $location);

        $visit = CampaignVisit::firstOrFail();
        $this->assertSame($campaign->id, $visit->campaign_id);
        $this->assertSame('reel-1', $visit->utm_content);
        $this->assertSame('m.facebook.com', $visit->referrer_host);
        $this->assertSame(64, strlen($visit->visitor_hash));
        $this->assertSame($campaign->id, session(CampaignAttributionService::SESSION_KEY)['campaign_id']);

        $this->get('/go/zzzzzzz')->assertRedirect(route('home'));
    }

    public function test_bots_repeat_clicks_and_paused_campaigns_are_not_counted(): void
    {
        $campaign = $this->campaign();

        $this->withHeader('User-Agent', 'facebookexternalhit/1.1')->get('/go/'.$campaign->code)->assertRedirect();
        $this->assertSame(0, CampaignVisit::count());

        $this->browser()->get('/go/'.$campaign->code);
        $this->browser()->get('/go/'.$campaign->code);
        $this->assertSame(1, CampaignVisit::count());

        $paused = $this->campaign(['name' => 'Old', 'utm_campaign' => 'old', 'status' => 'paused']);
        $this->browser()->get('/go/'.$paused->code)->assertRedirect();
        $ended = $this->campaign(['name' => 'Past', 'utm_campaign' => 'past', 'ends_on' => now()->subDay()->toDateString()]);
        $this->browser()->get('/go/'.$ended->code)->assertRedirect();

        $this->assertSame(0, CampaignVisit::whereIn('campaign_id', [$paused->id, $ended->id])->count());
    }

    public function test_utm_landing_and_social_referrer_are_captured(): void
    {
        $campaign = $this->campaign(['utm_campaign' => 'ads_manager']);

        $this->browser()->get('/shop?utm_source=facebook&utm_medium=paid_social&utm_campaign=ads_manager&utm_content=carousel')->assertOk();
        $touch = session(CampaignAttributionService::SESSION_KEY);
        $this->assertSame($campaign->id, $touch['campaign_id']);
        $this->assertSame('carousel', $touch['utm_content']);
        $this->assertSame('/shop', $touch['landing_page']);
        $this->assertSame(1, CampaignVisit::where('campaign_id', $campaign->id)->count());

        $this->flushSession();
        $this->browser()->withHeader('Referer', 'https://l.instagram.com/')->get('/shop')->assertOk();
        $touch = session(CampaignAttributionService::SESSION_KEY);
        $this->assertNull($touch['campaign_id']);
        $this->assertSame('instagram', $touch['utm_source']);
        $this->assertSame('referral', $touch['utm_medium']);
        $this->assertSame('l.instagram.com', $touch['referrer_host']);
    }

    public function test_checkout_saves_campaign_attribution_on_the_order(): void
    {
        $campaign = $this->campaign();
        $product = Product::create([
            'shop_id' => $this->shop->id, 'name' => 'Canvas Tote', 'barcode' => 'CT-1',
            'cost_price' => 200, 'selling_price' => 450, 'stock_quantity' => 10, 'is_published' => true,
        ]);
        $buyer = User::factory()->create();
        $buyer->forceFill(['shop_id' => $this->shop->id])->save();
        $buyer->assignRole('Customer');

        $this->browser()->get('/go/'.$campaign->code.'?c=story');

        $this->actingAs($buyer, 'web')->postJson(route('website.checkout'), [
            'customer_name' => 'Rima Akter', 'customer_phone' => '01711000000', 'customer_address' => 'Dhanmondi, Dhaka',
            'delivery_zone' => 'inside_dhaka', 'payment_method' => 'cash_on_delivery',
            'cart' => [['id' => $product->id, 'qty' => 1]],
        ])->assertOk()->assertJson(['success' => true]);

        $order = Order::latest('id')->firstOrFail();
        $this->assertSame($campaign->id, $order->campaign_id);
        $this->assertSame('facebook', $order->utm_source);
        $this->assertSame('paid_social', $order->utm_medium);
        $this->assertSame('eid_sale', $order->utm_campaign);
        $this->assertSame('story', $order->utm_content);
        $this->assertSame('/shop', $order->landing_page);

        $this->actingAs($this->admin, 'admin')->get(route('online-orders.show', $order))
            ->assertOk()->assertSee('Order source')->assertSee('Eid Sale');
    }

    public function test_stats_count_real_orders_and_exclude_cancelled_revenue(): void
    {
        $campaign = $this->campaign();
        $customer = Customer::create(['shop_id' => $this->shop->id, 'name' => 'Buyer', 'phone' => '01700000000']);
        foreach ([['completed', 1000], ['pending_fulfillment', 500], ['cancelled', 700]] as $i => [$status, $total]) {
            Order::create([
                'shop_id' => $this->shop->id, 'user_id' => $this->admin->id, 'customer_id' => $customer->id,
                'invoice_no' => 'WEB-T'.$i, 'total_amount' => $total, 'paid_amount' => 0, 'status' => $status,
                'campaign_id' => $campaign->id, 'utm_source' => 'facebook', 'utm_medium' => 'paid_social',
            ]);
        }
        foreach (['a', 'b', 'c', 'd'] as $visitor) {
            CampaignVisit::create(['campaign_id' => $campaign->id, 'visitor_hash' => str_repeat($visitor, 64)]);
        }

        $stats = app(CampaignAttributionService::class)->stats([$campaign->id])[$campaign->id];
        $this->assertSame(4, $stats['clicks']);
        $this->assertSame(4, $stats['visitors']);
        $this->assertSame(3, $stats['orders']);
        $this->assertSame(1, $stats['voided']);
        $this->assertEquals(1500, $stats['revenue']);
        $this->assertEquals(75.0, $stats['conversion']);

        $breakdown = app(CampaignAttributionService::class)->sourceBreakdown($this->shop->id, now()->subDays(30));
        $this->assertSame('facebook', $breakdown->first()->source);
        $this->assertEquals(2, $breakdown->first()->orders);

        $this->actingAs($this->admin, 'admin')->get(route('campaigns.index'))->assertOk()->assertSee('1,500');
    }
}
