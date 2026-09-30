<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\NavigationLink;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoBanner;
use App\Models\SiteFeature;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontCmsWiringTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->shopAdmin();
        $brand = Brand::create(['shop_id' => $this->admin->shop_id, 'name' => 'Tiny Toes', 'is_active' => true]);
        $this->product = Product::create([
            'shop_id' => $this->admin->shop_id, 'brand_id' => $brand->id, 'name' => 'Cotton Romper', 'barcode' => 'ROMP-1',
            'cost_price' => 200, 'selling_price' => 650, 'stock_quantity' => 10, 'is_published' => true,
        ]);
        SiteSetting::create(['default_shop_id' => $this->admin->shop_id, 'store_name' => 'Nest Store', 'currency_code' => 'BDT', 'currency_symbol' => '৳']);
    }

    public function test_homepage_shows_everything_managed_in_the_landing_page_editor(): void
    {
        SiteSetting::current()->update([
            'special_offer_text' => 'Free delivery this weekend',
            'trusted_by_text' => 'Loved by 5,000 families',
            'deals_kicker' => 'This week', 'deals_title' => 'Hot', 'deals_title_accent' => 'Deals',
            'home_copy' => ['hero_title' => 'Welcome to {store}', 'logo_tagline' => 'Baby Boutique', 'featured_title' => 'Staff Picks'],
        ]);
        PromoBanner::create(['shop_id' => $this->admin->shop_id, 'title' => 'Deal Card One', 'placement' => 'deals', 'price_from' => 450, 'is_active' => true]);
        PromoBanner::create(['shop_id' => $this->admin->shop_id, 'title' => 'Mid Strip Card', 'placement' => 'mid_promo', 'is_active' => true]);
        PromoBanner::create(['shop_id' => $this->admin->shop_id, 'title' => 'Hero Side Card', 'placement' => 'hero_side', 'is_active' => true]);
        NavigationLink::create(['shop_id' => $this->admin->shop_id, 'label' => 'Size Guide', 'url' => '/page/size-guide', 'location' => 'top_bar', 'is_active' => true]);
        SiteFeature::create(['shop_id' => $this->admin->shop_id, 'icon' => 'return', 'title' => '7-Day Returns', 'subtitle' => 'Unused items', 'is_active' => true]);
        $this->product->update(['is_featured' => true]);

        $this->get('/')->assertOk()
            ->assertSee('Free delivery this weekend')
            ->assertSee('Size Guide')
            ->assertSee('Welcome to Nest Store')
            ->assertSee('Baby Boutique')
            ->assertSee('Staff Picks')
            ->assertSee('Cotton Romper')
            ->assertSee('This week')
            ->assertSee('Deal Card One')
            ->assertSee('Mid Strip Card')
            ->assertSee('Hero Side Card')
            ->assertSee('Loved by 5,000 families')
            ->assertSee('id="brands"', false)
            ->assertSee('Tiny Toes');

        $this->get(route('website.product', $this->product))->assertOk()
            ->assertSee('7-Day Returns')
            ->assertDontSee('Guest or sign in');
        $this->get(route('website.faqs'))->assertOk()->assertSee('7-Day Returns')->assertDontSee('30-day easy returns');
    }

    public function test_admin_editors_render_the_new_fields(): void
    {
        $admin = $this->actingAs($this->admin, 'admin');
        $admin->get(route('cms.landing.edit'))->assertOk()
            ->assertSee('Homepage text')->assertSee('home_copy[hero_title]', false)->assertSee('home_copy[logo_tagline]', false)
            ->assertDontSee('home_copy[trending_title]', false)->assertSee('Store promises');
        $admin->get(route('cms.contact.index'))->assertOk()->assertSee('social[tiktok]', false)->assertSee('social[whatsapp]', false);
        $admin->get(route('cms.delivery.edit'))->assertOk()->assertSee('delivery_confirmation_instructions', false);
        $admin->get(route('products.index'))->assertOk()->assertSee("'is_featured'", false);
    }

    public function test_landing_editor_saves_only_customised_home_text(): void
    {
        $this->actingAs($this->admin, 'admin')->put(route('cms.landing.update'), [
            'store_name' => 'Nest Store', 'currency_code' => 'BDT', 'currency_symbol' => '৳',
            'home_copy' => ['hero_title' => 'Tiny joys', 'hero_subtitle' => '', 'not_a_field' => 'x'],
        ])->assertSessionHasNoErrors();

        $this->assertSame(['hero_title' => 'Tiny joys'], SiteSetting::current()->home_copy);
        $this->get('/')->assertSee('Tiny joys')->assertSee('Browse the collection and order online');
    }

    public function test_empty_offer_text_hides_the_strip_and_featured_flag_is_toggleable(): void
    {
        $this->get('/')->assertOk()->assertDontSee('bb-topbar', false);

        $this->actingAs($this->admin, 'admin')
            ->patchJson(route('products.homepage-flags', $this->product), ['flag' => 'is_featured', 'value' => 1])
            ->assertOk()->assertJson(['is_featured' => true]);
        $this->assertTrue($this->product->fresh()->is_featured);
    }

    public function test_contact_settings_save_tiktok_and_whatsapp_and_the_footer_shows_them(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('cms.contact.settings'), [
            'contact_email' => 'hello@nest.test',
            'social' => ['facebook' => 'https://facebook.com/nest', 'tiktok' => 'https://tiktok.com/@nest', 'whatsapp' => '01711-000000', 'bogus' => 'x'],
        ])->assertSessionHasNoErrors();

        $social = SiteSetting::current()->social_links;
        $this->assertSame('https://wa.me/8801711000000', $social['whatsapp']);
        $this->assertArrayNotHasKey('bogus', $social);

        $this->get('/')->assertOk()
            ->assertSee('https://tiktok.com/@nest', false)
            ->assertSee('https://wa.me/8801711000000', false);
        $this->get(route('website.contact'))->assertOk()->assertSee('aria-label="TikTok"', false)->assertDontSee('support@bynnas.com');
    }

    public function test_confirmation_charge_needs_instructions_and_a_transaction_id(): void
    {
        $zones = [['name' => 'Inside Dhaka', 'code' => 'inside_dhaka', 'fee' => 60, 'is_active' => 1]];
        $base = ['zones' => $zones, 'delivery_free_min_amount' => 100000, 'delivery_cod_enabled' => '1', 'delivery_confirmation_enabled' => '1', 'delivery_confirmation_amount' => 100];

        $this->actingAs($this->admin, 'admin')->put(route('cms.delivery.update'), $base)
            ->assertSessionHasErrors('delivery_confirmation_instructions');
        $this->actingAs($this->admin, 'admin')->put(route('cms.delivery.update'), $base + ['delivery_confirmation_instructions' => 'bKash 01700000000 (Personal)'])
            ->assertSessionHasNoErrors();

        $buyer = User::factory()->create();
        $buyer->assignRole('Customer');
        $payload = [
            'customer_name' => 'Rina', 'customer_phone' => '01811000000', 'customer_address' => 'Mirpur 10, Dhaka',
            'cart' => [['id' => $this->product->id, 'qty' => 1]], 'payment_method' => 'confirmation_charge',
        ];

        $this->actingAs($buyer, 'web')->postJson(route('website.checkout'), $payload)
            ->assertStatus(422)->assertJsonValidationErrors('payment_reference');
        $this->actingAs($buyer, 'web')->postJson(route('website.checkout'), $payload + ['payment_reference' => 'TRX9A7B6C'])
            ->assertOk()->assertJson(['success' => true, 'payment_method' => 'confirmation_charge']);

        $order = Order::latest('id')->first();
        $this->assertSame('TRX9A7B6C', $order->payment_reference);
        $this->actingAs($this->admin, 'admin')->get(route('online-orders.show', $order))->assertOk()->assertSee('TRX9A7B6C');

        $this->get('/')->assertSee('bKash 01700000000 (Personal)');
    }
}
