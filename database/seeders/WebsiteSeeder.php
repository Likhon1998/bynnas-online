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
            'name' => 'Maks Gadget',
            'phone' => '+880 1712-345678',
            'address' => 'Gulshan 1, Dhaka 1212, Bangladesh',
            'email' => 'support@maksgadget.com',
            'is_active' => true,
        ])->save();

        SiteSetting::query()->delete();
        NavigationLink::where('shop_id', $shop->id)->delete();
        HeroSlide::where('shop_id', $shop->id)->delete();
        SiteFeature::where('shop_id', $shop->id)->delete();
        PromoBanner::where('shop_id', $shop->id)->delete();

        SiteSetting::create([
            'default_shop_id' => $shop->id,
            'store_name' => 'Maks Gadget',
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
            'special_offer_text' => 'Gadget Week Deals',
            'trusted_by_text' => 'Trusted by gadget lovers across Bangladesh',
            'footer_tagline' => 'Authentic phones, laptops, audio & accessories — delivered with care across Bangladesh.',
            'home_copy' => [
                'categories_eyebrow' => 'Browse the store',
                'categories_title' => 'Shop by',
                'categories_title_accent' => 'Category',
                'categories_subtitle' => 'From flagship phones to everyday chargers — find the right tech fast.',
                'flash_eyebrow' => 'Limited time',
                'flash_title' => 'Flash',
                'flash_title_accent' => 'Sale',
                'flash_subtitle' => 'Hand-picked gadget deals — genuine products with clear warranty.',
                'new_eyebrow' => 'Just landed',
                'new_title' => 'New',
                'new_title_accent' => 'Arrivals',
                'new_subtitle' => 'Fresh smartphones, wearables, and accessories added this week.',
                'trending_eyebrow' => 'Customer favorites',
                'trending_title' => "What's",
                'trending_title_accent' => 'Trending',
                'trending_subtitle' => 'Best-selling gadgets shoppers are buying right now.',
                'brands_eyebrow' => 'Official brands',
                'brands_title' => 'Brands We',
                'brands_title_accent' => 'Carry',
                'brands_subtitle' => 'Apple, Samsung, Sony, and more — shop names you trust.',
                'reviews_title' => 'Loved by gadget buyers',
                'reviews_subtitle' => 'Real feedback from customers who ordered phones, laptops, and audio gear.',
                'blog_eyebrow' => 'Tech journal',
                'blog_title' => 'Guides &',
                'blog_title_accent' => 'Reviews',
                'blog_subtitle' => 'Buying tips and product reviews from the Maks Gadget team.',
            ],
            'deals_kicker' => 'HOT DEALS',
            'deals_title' => "Deals You'll",
            'deals_title_accent' => 'Love',
            'deals_subtitle' => 'Save on phones, laptops, headphones, and everyday tech accessories.',
            'contact_email' => 'support@maksgadget.com',
            'contact_phone' => '+880 1712-345678',
            'contact_address' => 'Gulshan 1, Dhaka 1212, Bangladesh',
            'contact_hours_weekday' => 'Sat – Thu: 10:00 AM – 8:00 PM (BDT)',
            'contact_hours_weekend' => 'Fri: 3:00 PM – 8:00 PM (BDT)',
            'contact_hours_title' => 'Store hours',
            'contact_hero_kicker' => 'WE ARE HERE',
            'contact_hero_title' => 'Talk to a gadget expert',
            'contact_hero_subtitle' => 'Need help picking a phone, laptop, or accessory? Message us — we reply in Bangla & English.',
            'contact_chat_title' => 'Live chat',
            'contact_chat_text' => 'Quick answers on stock, warranty, and delivery.',
            'contact_chat_status' => 'Usually replies within minutes',
            'contact_email_card_title' => 'Email support',
            'contact_email_card_text' => 'Orders, warranty claims, and wholesale inquiries.',
            'contact_phone_card_title' => 'Call / WhatsApp',
            'contact_phone_card_text' => 'Speak with our Dhaka showroom team.',
            'contact_form_title' => 'Send us a message',
            'contact_form_subtitle' => 'Tell us what gadget you need — we will recommend the right model.',
            'contact_newsletter_title' => 'Get deal alerts',
            'contact_newsletter_text' => 'Flash sales, new arrivals, and exclusive gadget drops.',
            'contact_website_url' => 'https://maksgadget.com',
            'faq_hero_title' => 'Frequently asked questions',
            'faq_hero_subtitle' => 'Orders, COD, delivery, warranty, and returns — answered clearly.',
            'faq_help_title' => 'Still need help?',
            'faq_help_text' => 'Our support team can check stock, warranty, and delivery for your order.',
            'faq_help_button' => 'Contact support',
            'social_links' => [
                'facebook' => 'https://facebook.com/maksgadget',
                'instagram' => 'https://instagram.com/maksgadget',
                'youtube' => 'https://youtube.com/@maksgadget',
                'tiktok' => 'https://tiktok.com/@maksgadget',
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
                'title' => 'Wireless Audio Fest',
                'subtitle' => 'Headphones & earbuds from Sony, Bose, JBL',
                'badge_text' => 'AUDIO',
                'highlight_text' => 'Live deals',
                'discount_badge' => 'HOT',
                'price_from' => 5900,
                'theme' => 'dark',
                'sort_order' => 1,
                'button_url' => '/shop?search=headphone',
                'image' => 'https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?w=1200&q=80',
                'file' => 'promo-audio.jpg',
            ],
            [
                'title' => 'MacBook Air M3',
                'subtitle' => 'Ultra-thin power for work & campus',
                'badge_text' => 'LAPTOPS',
                'highlight_text' => 'Best seller',
                'discount_badge' => 'NEW',
                'price_from' => 119900,
                'theme' => 'light',
                'sort_order' => 2,
                'button_url' => '/shop?search=macbook',
                'image' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=1200&q=80',
                'file' => 'promo-macbook.jpg',
            ],
            [
                'title' => 'Gaming Gear Up',
                'subtitle' => 'PS5 Slim, monitors & keyboards',
                'badge_text' => 'GAMING',
                'highlight_text' => 'In stock',
                'discount_badge' => 'PLAY',
                'price_from' => 14900,
                'theme' => 'dark',
                'sort_order' => 3,
                'button_url' => '/shop?search=gaming',
                'image' => 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=1200&q=80',
                'file' => 'promo-gaming.jpg',
            ],
            [
                'title' => 'Smartwatch Drop',
                'subtitle' => 'Apple Watch & Galaxy Watch models',
                'badge_text' => 'WEARABLES',
                'highlight_text' => 'Health + style',
                'discount_badge' => 'TREND',
                'price_from' => 12900,
                'theme' => 'light',
                'sort_order' => 4,
                'button_url' => '/shop?search=watch',
                'image' => 'https://images.unsplash.com/photo-1434493789847-2f02dc6ca35d?w=1200&q=80',
                'file' => 'promo-watch.jpg',
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

        foreach (['Apple', 'Samsung', 'Sony', 'Bose', 'Canon', 'Dell', 'Xiaomi', 'Nothing', 'Anker', 'JBL'] as $i => $name) {
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
            ['label' => 'Smartphones', 'url' => '/shop?search=phone', 'location' => 'footer', 'sort_order' => 1],
            ['label' => 'Laptops', 'url' => '/shop?search=laptop', 'location' => 'footer', 'sort_order' => 2],
            ['label' => 'Audio', 'url' => '/shop?search=headphone', 'location' => 'footer', 'sort_order' => 3],
            ['label' => 'Gaming', 'url' => '/shop?search=gaming', 'location' => 'footer', 'sort_order' => 4],
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
                ->withHeaders(['User-Agent' => 'MaksGadgetWebsiteSeeder/1.0'])
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
