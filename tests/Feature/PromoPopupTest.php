<?php

namespace Tests\Feature;

use App\Models\PromoBanner;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PromoPopupTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_popup_appears_on_storefront_and_inactive_does_not(): void
    {
        $admin = $this->shopAdmin();
        SiteSetting::create([
            'default_shop_id' => $admin->shop_id,
            'store_name' => 'Nest Store',
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
        ]);

        $this->get('/')->assertOk()->assertDontSee('bb-promo-popup', false);

        PromoBanner::create([
            'shop_id' => $admin->shop_id,
            'title' => 'Spring Sale',
            'placement' => 'popup',
            'image_path' => 'cms/promo-popups/spring.jpg',
            'button_text' => 'Shop deals',
            'button_url' => '/shop?filter=deals',
            'is_active' => true,
        ]);

        $this->get('/')->assertOk()
            ->assertSee('bb-promo-popup', false)
            ->assertSee('Spring Sale')
            ->assertSee('Shop deals')
            ->assertSee('cms/promo-popups/spring.jpg', false)
            ->assertSee('bb-promo-popup__close', false);

        PromoBanner::where('placement', 'popup')->update(['is_active' => false]);

        $this->get('/')->assertOk()->assertDontSee('bb-promo-popup', false);
    }

    public function test_admin_can_upload_toggle_and_delete_popup_and_landing_save_keeps_it(): void
    {
        Storage::fake('public');
        $admin = $this->shopAdmin();
        SiteSetting::create([
            'default_shop_id' => $admin->shop_id,
            'store_name' => 'Nest Store',
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('cms.promo-popups.index'))
            ->assertOk()
            ->assertSee('Offer Popup');

        $this->actingAs($admin, 'admin')
            ->post(route('cms.promo-popups.store'), [
                'title' => 'Flash Offer',
                'button_text' => 'Grab it',
                'button_url' => '/shop',
                'sort_order' => 1,
                'is_active' => '1',
                'image' => UploadedFile::fake()->image('offer.jpg', 900, 1100),
            ])
            ->assertRedirect(route('cms.promo-popups.index'));

        $banner = PromoBanner::where('placement', 'popup')->first();
        $this->assertNotNull($banner);
        $this->assertTrue($banner->is_active);
        Storage::disk('public')->assertExists($banner->image_path);

        $other = PromoBanner::create([
            'shop_id' => $admin->shop_id,
            'title' => 'Older Offer',
            'placement' => 'popup',
            'image_path' => 'cms/promo-popups/old.jpg',
            'is_active' => false,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('cms.promo-popups.toggle', $other))
            ->assertRedirect();

        $this->assertTrue($other->fresh()->is_active);
        $this->assertFalse($banner->fresh()->is_active);

        // Landing page save must not wipe popup banners.
        $this->actingAs($admin, 'admin')->put(route('cms.landing.update'), [
            'store_name' => 'Nest Store',
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('promo_banners', [
            'id' => $banner->id,
            'placement' => 'popup',
        ]);

        $this->actingAs($admin, 'admin')
            ->delete(route('cms.promo-popups.destroy', $banner))
            ->assertRedirect();

        $this->assertDatabaseMissing('promo_banners', ['id' => $banner->id]);
    }

    public function test_popup_without_image_never_renders(): void
    {
        $admin = $this->shopAdmin();
        SiteSetting::create([
            'default_shop_id' => $admin->shop_id,
            'store_name' => 'Nest Store',
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
        ]);

        PromoBanner::create([
            'shop_id' => $admin->shop_id,
            'title' => 'Broken Offer',
            'placement' => 'popup',
            'image_path' => null,
            'is_active' => true,
        ]);

        $this->get('/')->assertOk()->assertDontSee('bb-promo-popup', false);
    }
}
