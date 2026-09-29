<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\HeroSlide;
use App\Models\NavigationLink;
use App\Models\PromoBanner;
use App\Models\Shop;
use App\Models\SiteFeature;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class WebsiteSeeder extends Seeder
{
    public function run(): void
    {
        $shop = Shop::first();
        if (! $shop) {
            return;
        }

        $shop->fill([
            'name' => 'Bynnas Social',
            'phone' => '+880 1712-345678',
            'address' => 'Gulshan 1, Dhaka 1212, Bangladesh',
            'email' => 'support@bynnas.com',
            'is_active' => true,
        ])->save();

        SiteSetting::query()->delete();
        NavigationLink::where('shop_id', $shop->id)->delete();
        HeroSlide::where('shop_id', $shop->id)->delete();
        SiteFeature::where('shop_id', $shop->id)->delete();
        PromoBanner::where('shop_id', $shop->id)->delete();

        SiteSetting::create([
            'default_shop_id' => $shop->id,
            'store_name' => 'Bynnas Social',
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
            'special_offer_text' => 'This Week’s Deals',
            'trusted_by_text' => 'Trusted by shoppers across Bangladesh',
            'footer_tagline' => 'Trending products from the brands you follow — delivered with care across Bangladesh.',
            'home_copy' => [
                'categories_eyebrow' => 'Browse the store',
                'categories_title' => 'Shop by',
                'categories_title_accent' => 'Category',
                'categories_subtitle' => 'From everyday essentials to viral favourites — find what you love fast.',
                'flash_eyebrow' => 'Limited time',
                'flash_title' => 'Flash',
                'flash_title_accent' => 'Sale',
                'flash_subtitle' => 'Hand-picked deals — genuine products at limited-time prices.',
                'new_eyebrow' => 'Just landed',
                'new_title' => 'New',
                'new_title_accent' => 'Arrivals',
                'new_subtitle' => 'Fresh products added to the store this week.',
                'trending_eyebrow' => 'Customer favorites',
                'trending_title' => "What's",
                'trending_title_accent' => 'Trending',
                'trending_subtitle' => 'Best-selling products shoppers are buying right now.',
                'brands_eyebrow' => 'Official brands',
                'brands_title' => 'Brands We',
                'brands_title_accent' => 'Carry',
                'brands_subtitle' => 'Shop the names you trust.',
                'reviews_title' => 'Loved by our customers',
                'reviews_subtitle' => 'Real feedback from customers who ordered with us.',
                'blog_eyebrow' => 'Journal',
                'blog_title' => 'Guides &',
                'blog_title_accent' => 'Reviews',
                'blog_subtitle' => 'Buying tips and product reviews from the Bynnas Social team.',
            ],
            'deals_kicker' => 'HOT DEALS',
            'deals_title' => "Deals You'll",
            'deals_title_accent' => 'Love',
            'deals_subtitle' => 'Save on trending products and everyday favourites.',
            'contact_email' => 'support@bynnas.com',
            'contact_phone' => '+880 1712-345678',
            'contact_address' => 'Gulshan 1, Dhaka 1212, Bangladesh',
            'contact_hours_weekday' => 'Sat – Thu: 10:00 AM – 8:00 PM (BDT)',
            'contact_hours_weekend' => 'Fri: 3:00 PM – 8:00 PM (BDT)',
            'contact_hours_title' => 'Store hours',
            'contact_hero_kicker' => 'WE ARE HERE',
            'contact_hero_title' => 'Talk to our team',
            'contact_hero_subtitle' => 'Questions about a product or your order? Message us — we reply in Bangla & English.',
            'contact_chat_title' => 'Live chat',
            'contact_chat_text' => 'Quick answers on stock, warranty, and delivery.',
            'contact_chat_status' => 'Usually replies within minutes',
            'contact_email_card_title' => 'Email support',
            'contact_email_card_text' => 'Orders, warranty claims, and wholesale inquiries.',
            'contact_phone_card_title' => 'Call / WhatsApp',
            'contact_phone_card_text' => 'Speak with our customer support team.',
            'contact_form_title' => 'Send us a message',
            'contact_form_subtitle' => 'Tell us what you are looking for — we will help you find it.',
            'contact_newsletter_title' => 'Get deal alerts',
            'contact_newsletter_text' => 'Flash sales, new arrivals, and exclusive drops.',
            'contact_website_url' => 'https://bynnas.com',
            'faq_hero_title' => 'Frequently asked questions',
            'faq_hero_subtitle' => 'Orders, COD, delivery, warranty, and returns — answered clearly.',
            'faq_help_title' => 'Still need help?',
            'faq_help_text' => 'Our support team can check stock, warranty, and delivery for your order.',
            'faq_help_button' => 'Contact support',
            'social_links' => [
                'facebook' => 'https://facebook.com/bynnas',
                'instagram' => 'https://instagram.com/bynnas',
                'youtube' => 'https://youtube.com/@bynnas',
                'tiktok' => 'https://tiktok.com/@bynnas',
                'whatsapp' => 'https://wa.me/8801712345678',
            ],
            'delivery_inside_dhaka' => 60,
            'delivery_outside_dhaka' => 120,
            'delivery_free_enabled' => true,
            'delivery_free_min_amount' => 10000,
            'delivery_cod_enabled' => true,
            'delivery_confirmation_enabled' => false,
            'delivery_confirmation_amount' => 0,
        ]);

        $this->call(HeroSlideSeeder::class);
        $this->call(SiteFeatureSeeder::class);

        $promos = [
            [
                'title' => 'Fashion Week Picks',
                'subtitle' => 'Tees, denim and jackets our community loves',
                'badge_text' => 'FASHION',
                'highlight_text' => 'Live deals',
                'discount_badge' => 'HOT',
                'price_from' => 650,
                'theme' => 'dark',
                'sort_order' => 1,
                'button_url' => '/category/fashion',
                'image' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=1200&q=80',
                'file' => 'promo-fashion.jpg',
            ],
            [
                'title' => 'Beauty Must-Haves',
                'subtitle' => 'Lipsticks, lotions and makeup kits',
                'badge_text' => 'BEAUTY',
                'highlight_text' => 'Best seller',
                'discount_badge' => 'NEW',
                'price_from' => 450,
                'theme' => 'light',
                'sort_order' => 2,
                'button_url' => '/category/cosmetics-beauty',
                'image' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=1200&q=80',
                'file' => 'promo-beauty.jpg',
            ],
            [
                'title' => 'Cosy Home Edit',
                'subtitle' => 'Bedsheets, mugs and cookware',
                'badge_text' => 'HOME',
                'highlight_text' => 'In stock',
                'discount_badge' => 'COSY',
                'price_from' => 390,
                'theme' => 'dark',
                'sort_order' => 3,
                'button_url' => '/category/home-kitchen',
                'image' => 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=1200&q=80',
                'file' => 'promo-home.jpg',
            ],
            [
                'title' => 'Little Ones Corner',
                'subtitle' => 'Toys, plush friends and baby care',
                'badge_text' => 'KIDS',
                'highlight_text' => 'Playtime',
                'discount_badge' => 'TREND',
                'price_from' => 690,
                'theme' => 'light',
                'sort_order' => 4,
                'button_url' => '/category/toys-kids',
                'image' => 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=1200&q=80',
                'file' => 'promo-kids.jpg',
            ],
        ];

        foreach ($promos as $p) {
            $imagePath = $this->storeRemoteImage($p['image'], 'cms/promos', $p['file']);
            $buttonUrl = $p['button_url'] ?? '/shop';
            unset($p['image'], $p['file'], $p['button_url']);

            PromoBanner::create(array_merge($p, [
                'shop_id' => $shop->id,
                'placement' => 'deals',
                'image_path' => $imagePath,
                'button_text' => 'Shop Now',
                'button_url' => $buttonUrl,
                'is_active' => true,
            ]));
        }

        $this->call(MidPromoBannerSeeder::class);

        foreach (['Bynnas Basics', 'Urban Thread', 'Glow Lab', 'Little Sprout', 'PlayNest', 'HomeCraft', 'Nova Tech', 'Carry Co.'] as $i => $name) {
            Brand::updateOrCreate(
                ['shop_id' => $shop->id, 'name' => $name],
                ['sort_order' => $i + 1, 'is_active' => true]
            );
        }

        $navLinks = [
            ['label' => 'Home', 'url' => '/', 'location' => 'main_nav', 'sort_order' => 1],
            ['label' => 'Shop', 'url' => '/shop', 'location' => 'main_nav', 'sort_order' => 2],
            ['label' => 'Categories', 'url' => '/shop', 'location' => 'main_nav', 'sort_order' => 3],
            ['label' => 'Deals', 'url' => '/shop?filter=deals', 'location' => 'main_nav', 'sort_order' => 4],
            ['label' => 'Brands', 'url' => '/#brands', 'location' => 'main_nav', 'sort_order' => 5],
            ['label' => 'Blog', 'url' => '/blog', 'location' => 'main_nav', 'sort_order' => 6],
            ['label' => 'Contact', 'url' => '/contact', 'location' => 'main_nav', 'sort_order' => 7],
            ['label' => 'Fashion', 'url' => '/category/fashion', 'location' => 'footer', 'sort_order' => 1],
            ['label' => 'Beauty', 'url' => '/category/cosmetics-beauty', 'location' => 'footer', 'sort_order' => 2],
            ['label' => 'Home & Kitchen', 'url' => '/category/home-kitchen', 'location' => 'footer', 'sort_order' => 3],
            ['label' => 'Toys & Kids', 'url' => '/category/toys-kids', 'location' => 'footer', 'sort_order' => 4],
            ['label' => 'Track Order', 'url' => '/track-order', 'location' => 'footer', 'sort_order' => 5],
            ['label' => 'FAQs', 'url' => '/faq', 'location' => 'footer', 'sort_order' => 6],
        ];

        foreach ($navLinks as $link) {
            NavigationLink::create(array_merge($link, ['shop_id' => $shop->id, 'is_active' => true]));
        }

        $this->command?->info('Website CMS ready: settings, heroes, promos, nav, features.');
    }

    private function storeRemoteImage(string $url, string $directory, string $filename): ?string
    {
        $relative = trim($directory, '/').'/'.$filename;

        try {
            if (Storage::disk('public')->exists($relative) && Storage::disk('public')->size($relative) > 1000) {
                return $relative;
            }

            $response = Http::timeout(25)
                ->withHeaders(['User-Agent' => 'BynnasSocialWebsiteSeeder/1.0'])
                ->get($url);

            if (! $response->successful() || strlen($response->body()) < 500) {
                return Storage::disk('public')->exists($relative) ? $relative : null;
            }

            Storage::disk('public')->put($relative, $response->body());

            return $relative;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
