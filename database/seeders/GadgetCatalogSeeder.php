<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Shop;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GadgetCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $shop = SiteSetting::query()->first()?->defaultShop
            ?? Shop::query()->orderBy('id')->first();

        if (! $shop) {
            $this->command?->warn('No shop found — skip GadgetCatalogSeeder.');

            return;
        }

        $settings = SiteSetting::query()->first();
        if ($settings) {
            $settings->fill([
                'default_shop_id' => $settings->default_shop_id ?: $shop->id,
                'store_name' => $settings->store_name ?: 'Maks Gadget',
                'trusted_by_text' => $settings->trusted_by_text ?: 'Trusted by gadget lovers across Bangladesh',
            ])->save();
        }

        $categories = $this->seedCategories($shop->id);
        $brands = $this->seedBrands($shop->id);
        $this->seedProducts($shop->id, $categories, $brands);

        $this->command?->info('Gadget catalog ready: '.Product::where('shop_id', $shop->id)->count().' products.');
    }

    private function seedCategories(int $shopId): array
    {
        $defs = [
            ['name' => 'Smartphones', 'icon' => 'phone', 'description' => 'Latest Android and iPhone mobiles', 'featured' => true],
            ['name' => 'Laptops', 'icon' => 'laptop', 'description' => 'Ultrabooks, MacBooks, and gaming laptops', 'featured' => true],
            ['name' => 'Tablets', 'icon' => 'tablet', 'description' => 'iPad and Android tablets', 'featured' => true],
            ['name' => 'Headphones', 'icon' => 'headphones', 'description' => 'Over-ear and wireless headphones', 'featured' => true],
            ['name' => 'Earbuds', 'icon' => 'earbuds', 'description' => 'True wireless earbuds', 'featured' => false],
            ['name' => 'Smartwatches', 'icon' => 'watch', 'description' => 'Fitness and premium smartwatches', 'featured' => true],
            ['name' => 'Cameras', 'icon' => 'camera', 'description' => 'Mirrorless, DSLR, and action cams', 'featured' => false],
            ['name' => 'Gaming', 'icon' => 'game', 'description' => 'Consoles, controllers, and gear', 'featured' => true],
            ['name' => 'Speakers', 'icon' => 'speaker', 'description' => 'Portable and smart speakers', 'featured' => false],
            ['name' => 'Chargers & Cables', 'icon' => 'plug', 'description' => 'Fast chargers, power banks, and cables', 'featured' => false],
            ['name' => 'Accessories', 'icon' => 'mouse', 'description' => 'Cases, stands, and everyday gadget extras', 'featured' => false],
            ['name' => 'Monitors', 'icon' => 'monitor', 'description' => 'Gaming and productivity displays', 'featured' => false],
        ];

        $map = [];
        foreach ($defs as $i => $def) {
            $cat = Category::updateOrCreate(
                ['shop_id' => $shopId, 'slug' => Str::slug($def['name'])],
                [
                    'name' => $def['name'],
                    'icon' => $def['icon'],
                    'description' => $def['description'],
                    'is_featured' => $def['featured'],
                    'product_count_label' => null,
                ]
            );
            $map[$def['name']] = $cat;
        }

        // Merge any old manual Smartphone category into Smartphones.
        $smartphones = $map['Smartphones'] ?? null;
        if ($smartphones) {
            Category::where('shop_id', $shopId)
                ->where('id', '!=', $smartphones->id)
                ->where(function ($q) {
                    $q->where('slug', 'smartphone')
                        ->orWhere('slug', 'smartphones-legacy')
                        ->orWhere('name', 'Smartphone');
                })
                ->get()
                ->each(function (Category $legacy) use ($smartphones) {
                    Product::where('category_id', $legacy->id)->update(['category_id' => $smartphones->id]);
                    $legacy->delete();
                });
        }

        return $map;
    }

    private function seedBrands(int $shopId): array
    {
        $names = [
            'Apple', 'Samsung', 'Sony', 'Xiaomi', 'OnePlus', 'Google',
            'Dell', 'HP', 'Asus', 'Lenovo', 'Acer',
            'Bose', 'JBL', 'Anker', 'Canon', 'GoPro', 'Logitech', 'Razer', 'Nothing', 'Microsoft',
        ];

        $domains = [
            'Apple' => 'apple.com', 'Samsung' => 'samsung.com', 'Sony' => 'sony.com',
            'Xiaomi' => 'mi.com', 'OnePlus' => 'oneplus.com', 'Google' => 'google.com',
            'Dell' => 'dell.com', 'HP' => 'hp.com', 'Asus' => 'asus.com',
            'Lenovo' => 'lenovo.com', 'Acer' => 'acer.com', 'Bose' => 'bose.com',
            'JBL' => 'jbl.com', 'Anker' => 'anker.com', 'Canon' => 'canon.com',
            'GoPro' => 'gopro.com', 'Logitech' => 'logitech.com', 'Razer' => 'razer.com',
            'Nothing' => 'nothing.tech', 'Microsoft' => 'microsoft.com',
        ];

        $map = [];
        foreach ($names as $i => $name) {
            $brand = Brand::updateOrCreate(
                ['shop_id' => $shopId, 'name' => $name],
                [
                    'sort_order' => $i + 1,
                    'is_active' => true,
                ]
            );

            if (! $brand->logo_path) {
                $logo = $this->storeBrandLogoMark($name, $domains[$name] ?? Str::slug($name).'.com');
                if ($logo) {
                    $brand->update(['logo_path' => $logo]);
                }
            }

            $map[$name] = $brand->fresh();
        }

        return $map;
    }

    private function storeBrandLogoMark(string $name, string $domain): ?string
    {
        $slug = Str::slug($name);
        $png = 'brands/'.$slug.'.png';
        $svg = 'brands/'.$slug.'.svg';

        if (Storage::disk('public')->exists($png) && Storage::disk('public')->size($png) > 200) {
            return $png;
        }
        if (Storage::disk('public')->exists($svg) && Storage::disk('public')->size($svg) > 50) {
            return $svg;
        }

        try {
            $response = Http::timeout(12)
                ->withHeaders(['User-Agent' => 'GadgetStoreCatalogSeeder/1.0'])
                ->get('https://www.google.com/s2/favicons?domain='.$domain.'&sz=128');

            if ($response->successful() && strlen($response->body()) > 200) {
                Storage::disk('public')->put($png, $response->body());

                return $png;
            }
        } catch (\Throwable $e) {
            // fall through
        }

        $initial = strtoupper(substr($name, 0, 1));
        $mark = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="160" height="64" viewBox="0 0 160 64">
  <rect width="160" height="64" rx="10" fill="#1e293b"/>
  <text x="80" y="40" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="28" font-weight="700" fill="#ffffff">{$initial}</text>
</svg>
SVG;
        Storage::disk('public')->put($svg, $mark);

        return $svg;
    }

    private function seedProducts(int $shopId, array $categories, array $brands): void
    {
        $catalog = $this->catalog();

        foreach ($catalog as $i => $item) {
            $n = $i + 1;
            $barcode = 'GADGET-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
            $category = $categories[$item['category']] ?? null;
            $brand = $brands[$item['brand']] ?? null;

            // PgBouncer/Supabase can drop prepared statements on long seed runs.
            if ($n % 5 === 1) {
                \Illuminate\Support\Facades\DB::reconnect();
            }

            $imagePath = $this->storeRemoteImage(
                $item['image'],
                'products',
                'gadget-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT).'.jpg'
            );

            $payload = [
                'name' => $item['name'],
                'sku' => 'SKU-'.$barcode,
                'category_id' => $category?->id,
                'brand_id' => $brand?->id,
                'brand_name' => $brand?->name ?? $item['brand'],
                'variant_group' => $item['variant_group'] ?? null,
                'color' => $item['color'] ?? null,
                'color_hex' => $item['color_hex'] ?? null,
                'storage' => $item['storage'] ?? null,
                'ram' => $item['ram'] ?? null,
                'cost_price' => $item['cost'],
                'selling_price' => $item['price'],
                'original_price' => $item['original'] ?? $item['price'],
                'stock_quantity' => $item['stock'] ?? random_int(8, 60),
                'alert_quantity' => 5,
                'availability' => 'in_stock',
                'short_description' => $item['desc'],
                'image' => $imagePath,
                'rating' => $item['rating'] ?? round(random_int(40, 50) / 10, 1),
                'review_count' => $item['reviews'] ?? random_int(12, 420),
                'is_published' => true,
                'is_new_arrival' => (bool) ($item['new'] ?? ($n <= 12)),
                'is_best_seller' => (bool) ($item['best'] ?? false),
                'is_featured' => (bool) ($item['featured'] ?? false),
            ];

            $attempts = 0;
            while (true) {
                try {
                    $product = Product::updateOrCreate(
                        ['shop_id' => $shopId, 'barcode' => $barcode],
                        $payload
                    );

                    if ($imagePath) {
                        ProductImage::updateOrCreate(
                            ['product_id' => $product->id, 'path' => $imagePath],
                            ['sort_order' => 0]
                        );
                    }
                    break;
                } catch (\Throwable $e) {
                    $attempts++;
                    \Illuminate\Support\Facades\DB::reconnect();
                    if ($attempts >= 3) {
                        throw $e;
                    }
                    usleep(200000);
                }
            }
        }
    }

    /** @return list<array<string, mixed>> */
    private function catalog(): array
    {
        return [
            // Smartphones (8)
            ['name' => 'iPhone 16 Pro Max 256GB', 'brand' => 'Apple', 'category' => 'Smartphones', 'variant_group' => 'iphone-16-pro-max', 'color' => 'Natural Titanium', 'color_hex' => '#d4cfc8', 'storage' => '256GB', 'ram' => '8GB', 'cost' => 118000, 'price' => 139900, 'original' => 149900, 'desc' => 'Apple A18 Pro, ProMotion display, and pro camera system.', 'image' => 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=800&q=80', 'best' => true, 'featured' => true, 'new' => true],
            ['name' => 'iPhone 16 128GB', 'brand' => 'Apple', 'category' => 'Smartphones', 'variant_group' => 'iphone-16', 'color' => 'Black', 'color_hex' => '#1e293b', 'storage' => '128GB', 'ram' => '8GB', 'cost' => 78000, 'price' => 94900, 'desc' => 'A18 chip, Camera Control, and all-day battery life.', 'image' => 'https://images.unsplash.com/photo-1591337676887-a217a6970a8a?w=800&q=80', 'new' => true],
            ['name' => 'Samsung Galaxy S25 Ultra 512GB', 'brand' => 'Samsung', 'category' => 'Smartphones', 'variant_group' => 'galaxy-s25-ultra', 'color' => 'Titanium Black', 'color_hex' => '#3a3a3a', 'storage' => '512GB', 'ram' => '12GB', 'cost' => 112000, 'price' => 134900, 'desc' => '200MP camera, S Pen, and Snapdragon flagship performance.', 'image' => 'https://images.unsplash.com/photo-1610945415295-d9bbf067e59c?w=800&q=80', 'best' => true, 'featured' => true],
            ['name' => 'Samsung Galaxy A56 256GB', 'brand' => 'Samsung', 'category' => 'Smartphones', 'variant_group' => 'galaxy-a56', 'color' => 'Awesome Violet', 'color_hex' => '#7c3aed', 'storage' => '256GB', 'ram' => '8GB', 'cost' => 32000, 'price' => 39900, 'desc' => 'Bright AMOLED, long battery, and reliable mid-range power.', 'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800&q=80'],
            ['name' => 'Google Pixel 9 Pro 256GB', 'brand' => 'Google', 'category' => 'Smartphones', 'variant_group' => 'pixel-9-pro', 'color' => 'Porcelain', 'color_hex' => '#f8fafc', 'storage' => '256GB', 'ram' => '16GB', 'cost' => 82000, 'price' => 99900, 'desc' => 'Best-in-class computational photography and clean Android.', 'image' => 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=800&q=80', 'featured' => true],
            ['name' => 'Xiaomi 14T Pro 512GB', 'brand' => 'Xiaomi', 'category' => 'Smartphones', 'variant_group' => 'xiaomi-14t-pro', 'color' => 'Blue', 'color_hex' => '#2563eb', 'storage' => '512GB', 'ram' => '12GB', 'cost' => 48000, 'price' => 58900, 'desc' => 'Leica-tuned cameras and blazing Dimensity performance.', 'image' => 'https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?w=800&q=80'],
            ['name' => 'OnePlus 13 256GB', 'brand' => 'OnePlus', 'category' => 'Smartphones', 'variant_group' => 'oneplus-13', 'color' => 'Black', 'color_hex' => '#0f172a', 'storage' => '256GB', 'ram' => '12GB', 'cost' => 62000, 'price' => 74900, 'desc' => 'Hasselblad camera, 120Hz display, and ultra-fast charging.', 'image' => 'https://images.unsplash.com/photo-1585060544812-6b45742d762f?w=800&q=80'],
            ['name' => 'Nothing Phone (2a) 128GB', 'brand' => 'Nothing', 'category' => 'Smartphones', 'variant_group' => 'nothing-2a', 'color' => 'White', 'color_hex' => '#f8fafc', 'storage' => '128GB', 'ram' => '8GB', 'cost' => 24000, 'price' => 29900, 'desc' => 'Glyph interface design with smooth Nothing OS experience.', 'image' => 'https://images.unsplash.com/photo-1601784551446-20c9e07cdbdb?w=800&q=80', 'new' => true],

            // Laptops (6)
            ['name' => 'MacBook Air 13" M3 8/256', 'brand' => 'Apple', 'category' => 'Laptops', 'variant_group' => 'macbook-air-m3-13', 'color' => 'Midnight', 'color_hex' => '#1e293b', 'storage' => '256GB', 'ram' => '8GB', 'cost' => 98000, 'price' => 119900, 'desc' => 'Fanless M3 power in an impossibly thin aluminum body.', 'image' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800&q=80', 'best' => true, 'featured' => true],
            ['name' => 'MacBook Pro 14" M3 Pro 18/512', 'brand' => 'Apple', 'category' => 'Laptops', 'variant_group' => 'macbook-pro-14-m3', 'color' => 'Space Black', 'color_hex' => '#111827', 'storage' => '512GB', 'ram' => '18GB', 'cost' => 165000, 'price' => 199900, 'desc' => 'Pro display, long battery, and desktop-class performance.', 'image' => 'https://images.unsplash.com/photo-1511385348-a52b4a160dc2?w=800&q=80', 'featured' => true],
            ['name' => 'Dell XPS 13 Plus OLED', 'brand' => 'Dell', 'category' => 'Laptops', 'variant_group' => 'dell-xps-13', 'color' => 'Platinum', 'color_hex' => '#e2e8f0', 'storage' => '512GB', 'ram' => '16GB', 'cost' => 98000, 'price' => 124900, 'desc' => 'Edge-to-edge OLED and premium Windows ultraportable design.', 'image' => 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=800&q=80'],
            ['name' => 'ASUS ROG Zephyrus G14', 'brand' => 'Asus', 'category' => 'Laptops', 'variant_group' => 'rog-g14', 'color' => 'Eclipse Gray', 'color_hex' => '#334155', 'storage' => '1TB', 'ram' => '16GB', 'cost' => 118000, 'price' => 145900, 'desc' => 'Compact AMD Ryzen gaming laptop with strong thermals.', 'image' => 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=800&q=80', 'best' => true],
            ['name' => 'HP Pavilion 15 i7', 'brand' => 'HP', 'category' => 'Laptops', 'variant_group' => 'hp-pavilion-15', 'color' => 'Silver', 'color_hex' => '#94a3b8', 'storage' => '512GB', 'ram' => '16GB', 'cost' => 62000, 'price' => 78900, 'desc' => 'Everyday productivity laptop for work and study.', 'image' => 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=800&q=80'],
            ['name' => 'Lenovo ThinkPad E14 Gen 5', 'brand' => 'Lenovo', 'category' => 'Laptops', 'variant_group' => 'thinkpad-e14', 'color' => 'Black', 'color_hex' => '#0f172a', 'storage' => '512GB', 'ram' => '16GB', 'cost' => 68000, 'price' => 84900, 'desc' => 'Business-ready keyboard and durable ThinkPad reliability.', 'image' => 'https://images.unsplash.com/photo-1525547719571-a2d4ac8945e2?w=800&q=80'],

            // Tablets (4)
            ['name' => 'iPad Air M2 128GB Wi-Fi', 'brand' => 'Apple', 'category' => 'Tablets', 'variant_group' => 'ipad-air-m2', 'color' => 'Blue', 'color_hex' => '#3b82f6', 'storage' => '128GB', 'ram' => '8GB', 'cost' => 62000, 'price' => 74900, 'desc' => 'Liquid Retina display with Apple Pencil Pro support.', 'image' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=800&q=80', 'featured' => true],
            ['name' => 'iPad 10th Gen 64GB', 'brand' => 'Apple', 'category' => 'Tablets', 'variant_group' => 'ipad-10', 'color' => 'Silver', 'color_hex' => '#e2e8f0', 'storage' => '64GB', 'ram' => '4GB', 'cost' => 38000, 'price' => 45900, 'desc' => 'Colorful all-screen design for streaming and note-taking.', 'image' => 'https://images.unsplash.com/photo-1561154464-82e9adf32764?w=800&q=80'],
            ['name' => 'Samsung Galaxy Tab S9 FE', 'brand' => 'Samsung', 'category' => 'Tablets', 'variant_group' => 'tab-s9-fe', 'color' => 'Mint', 'color_hex' => '#6ee7b7', 'storage' => '128GB', 'ram' => '6GB', 'cost' => 28000, 'price' => 34900, 'desc' => 'S Pen included with vibrant entertainment display.', 'image' => 'https://images.unsplash.com/photo-1585790050230-5dd28404ccb9?w=800&q=80'],
            ['name' => 'Xiaomi Pad 6 256GB', 'brand' => 'Xiaomi', 'category' => 'Tablets', 'variant_group' => 'xiaomi-pad-6', 'color' => 'Gold', 'color_hex' => '#ca8a04', 'storage' => '256GB', 'ram' => '8GB', 'cost' => 26000, 'price' => 32900, 'desc' => '144Hz display and strong multimedia performance.', 'image' => 'https://images.unsplash.com/photo-1623129011814-8754678e3f3b?w=800&q=80'],

            // Headphones (5)
            ['name' => 'Sony WH-1000XM5', 'brand' => 'Sony', 'category' => 'Headphones', 'variant_group' => 'sony-xm5', 'color' => 'Black', 'color_hex' => '#0f172a', 'cost' => 28000, 'price' => 34900, 'original' => 39900, 'desc' => 'Industry-leading noise cancellation and multipoint Bluetooth.', 'image' => 'https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?w=800&q=80', 'best' => true, 'featured' => true],
            ['name' => 'Bose QuietComfort Ultra', 'brand' => 'Bose', 'category' => 'Headphones', 'variant_group' => 'bose-qc-ultra', 'color' => 'White Smoke', 'color_hex' => '#f1f5f9', 'cost' => 32000, 'price' => 39900, 'desc' => 'Immersive audio with legendary Bose comfort.', 'image' => 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=800&q=80', 'featured' => true],
            ['name' => 'Apple AirPods Max', 'brand' => 'Apple', 'category' => 'Headphones', 'variant_group' => 'airpods-max', 'color' => 'Space Gray', 'color_hex' => '#64748b', 'cost' => 48000, 'price' => 59900, 'desc' => 'Computational audio with Adaptive EQ and Spatial Audio.', 'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&q=80'],
            ['name' => 'JBL Tune 770NC', 'brand' => 'JBL', 'category' => 'Headphones', 'variant_group' => 'jbl-770nc', 'color' => 'Blue', 'color_hex' => '#2563eb', 'cost' => 8500, 'price' => 11900, 'desc' => 'Active noise cancelling with JBL Pure Bass sound.', 'image' => 'https://images.unsplash.com/photo-1484704849700-f032a568e944?w=800&q=80'],
            ['name' => 'Razer BlackShark V2 Pro', 'brand' => 'Razer', 'category' => 'Headphones', 'variant_group' => 'razer-blackshark', 'color' => 'Black', 'color_hex' => '#111827', 'cost' => 14000, 'price' => 18900, 'desc' => 'Esports wireless headset with THX Spatial Audio.', 'image' => 'https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?w=800&q=80'],

            // Earbuds (5)
            ['name' => 'Apple AirPods Pro 2 USB-C', 'brand' => 'Apple', 'category' => 'Earbuds', 'variant_group' => 'airpods-pro-2', 'color' => 'White', 'color_hex' => '#ffffff', 'cost' => 22000, 'price' => 27900, 'desc' => 'Adaptive Audio, ANC, and MagSafe charging case.', 'image' => 'https://images.unsplash.com/photo-1600294037681-c80b4cb5b434?w=800&q=80', 'best' => true, 'new' => true],
            ['name' => 'Samsung Galaxy Buds3 Pro', 'brand' => 'Samsung', 'category' => 'Earbuds', 'variant_group' => 'buds3-pro', 'color' => 'Silver', 'color_hex' => '#cbd5e1', 'cost' => 18000, 'price' => 22900, 'desc' => 'Blade lights design with rich 24-bit Hi-Fi audio.', 'image' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=800&q=80'],
            ['name' => 'Sony WF-1000XM5', 'brand' => 'Sony', 'category' => 'Earbuds', 'variant_group' => 'sony-wf-xm5', 'color' => 'Black', 'color_hex' => '#0f172a', 'cost' => 21000, 'price' => 26900, 'desc' => 'Best-in-class ANC earbuds with LDAC support.', 'image' => 'https://images.unsplash.com/photo-1631867675167-90a456a90863?w=800&q=80', 'featured' => true],
            ['name' => 'Nothing Ear (a)', 'brand' => 'Nothing', 'category' => 'Earbuds', 'variant_group' => 'nothing-ear-a', 'color' => 'Yellow', 'color_hex' => '#facc15', 'cost' => 6500, 'price' => 8900, 'desc' => 'Transparent case design with strong ANC for the price.', 'image' => 'https://images.unsplash.com/photo-1598331668826-20cecc596b86?w=800&q=80', 'new' => true],
            ['name' => 'JBL Wave Beam 2', 'brand' => 'JBL', 'category' => 'Earbuds', 'variant_group' => 'jbl-wave-beam', 'color' => 'Black', 'color_hex' => '#1e293b', 'cost' => 4200, 'price' => 5900, 'desc' => 'Everyday wireless buds with deep JBL bass.', 'image' => 'https://images.unsplash.com/photo-1572569511254-d8f925fe2cbb?w=800&q=80'],

            // Smartwatches (5)
            ['name' => 'Apple Watch Series 10 GPS 46mm', 'brand' => 'Apple', 'category' => 'Smartwatches', 'variant_group' => 'watch-s10', 'color' => 'Jet Black', 'color_hex' => '#0f172a', 'cost' => 42000, 'price' => 51900, 'desc' => 'Thinner design with brighter display and health insights.', 'image' => 'https://images.unsplash.com/photo-1434493789847-2f02dc6ca35d?w=800&q=80', 'best' => true, 'featured' => true, 'new' => true],
            ['name' => 'Apple Watch SE 2nd Gen 44mm', 'brand' => 'Apple', 'category' => 'Smartwatches', 'variant_group' => 'watch-se-2', 'color' => 'Starlight', 'color_hex' => '#f8fafc', 'cost' => 24000, 'price' => 29900, 'desc' => 'Essential Apple Watch features at a smarter price.', 'image' => 'https://images.unsplash.com/photo-1579586337278-3befd40fd17a?w=800&q=80'],
            ['name' => 'Samsung Galaxy Watch Ultra', 'brand' => 'Samsung', 'category' => 'Smartwatches', 'variant_group' => 'galaxy-watch-ultra', 'color' => 'Titanium Gray', 'color_hex' => '#64748b', 'cost' => 48000, 'price' => 59900, 'desc' => 'Adventure-ready titanium watch with advanced tracking.', 'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&q=80', 'featured' => true],
            ['name' => 'Xiaomi Watch S3', 'brand' => 'Xiaomi', 'category' => 'Smartwatches', 'variant_group' => 'xiaomi-watch-s3', 'color' => 'Silver', 'color_hex' => '#94a3b8', 'cost' => 9500, 'price' => 12900, 'desc' => 'Swapable bezels with accurate fitness and sleep tracking.', 'image' => 'https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?w=800&q=80'],
            ['name' => 'OnePlus Watch 2', 'brand' => 'OnePlus', 'category' => 'Smartwatches', 'variant_group' => 'oneplus-watch-2', 'color' => 'Black', 'color_hex' => '#111827', 'cost' => 18000, 'price' => 22900, 'desc' => 'Dual-chip architecture with week-long battery modes.', 'image' => 'https://images.unsplash.com/photo-1617043786394-f977fa12eddf?w=800&q=80'],

            // Cameras (4)
            ['name' => 'Sony ZV-E10 II Vlog Camera', 'brand' => 'Sony', 'category' => 'Cameras', 'variant_group' => 'sony-zv-e10', 'color' => 'Black', 'color_hex' => '#0f172a', 'cost' => 78000, 'price' => 94900, 'desc' => 'Content-creator APS-C camera with flip screen and mic input.', 'image' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=800&q=80', 'featured' => true],
            ['name' => 'Canon EOS R50 Kit 18-45mm', 'brand' => 'Canon', 'category' => 'Cameras', 'variant_group' => 'canon-r50', 'color' => 'Black', 'color_hex' => '#1e293b', 'cost' => 72000, 'price' => 88900, 'desc' => 'Compact mirrorless starter kit with Dual Pixel AF.', 'image' => 'https://images.unsplash.com/photo-1502920917128-1aa500764cbd?w=800&q=80'],
            ['name' => 'GoPro HERO13 Black', 'brand' => 'GoPro', 'category' => 'Cameras', 'variant_group' => 'gopro-13', 'color' => 'Black', 'color_hex' => '#0f172a', 'cost' => 38000, 'price' => 45900, 'desc' => '5.3K action cam with HyperSmooth and modular lenses.', 'image' => 'https://images.unsplash.com/photo-1564466809058-bf4114d54340?w=800&q=80', 'new' => true, 'best' => true],
            ['name' => 'Canon PowerShot G7 X Mark III', 'brand' => 'Canon', 'category' => 'Cameras', 'variant_group' => 'canon-g7x', 'color' => 'Black', 'color_hex' => '#111827', 'cost' => 52000, 'price' => 64900, 'desc' => 'Pocket vlogging camera with 4K and vertical video support.', 'image' => 'https://images.unsplash.com/photo-1606983340126-99ab4feaa64a?w=800&q=80'],

            // Gaming (5)
            ['name' => 'Sony PlayStation 5 Slim', 'brand' => 'Sony', 'category' => 'Gaming', 'variant_group' => 'ps5-slim', 'color' => 'White', 'color_hex' => '#f8fafc', 'storage' => '1TB', 'cost' => 48000, 'price' => 56900, 'desc' => 'Next-gen console with DualSense and ultra-fast SSD.', 'image' => 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&q=80', 'best' => true, 'featured' => true],
            ['name' => 'Xbox Series S 512GB', 'brand' => 'Microsoft', 'category' => 'Gaming', 'variant_group' => 'xbox-series-s', 'color' => 'White', 'color_hex' => '#f1f5f9', 'storage' => '512GB', 'cost' => 28000, 'price' => 34900, 'desc' => 'Compact digital Xbox for 1440p gaming and Game Pass.', 'image' => 'https://images.unsplash.com/photo-1621259182978-fbf93132d53d?w=800&q=80'],
            ['name' => 'Logitech G Pro X Superlight 2', 'brand' => 'Logitech', 'category' => 'Gaming', 'variant_group' => 'gpro-superlight', 'color' => 'Black', 'color_hex' => '#0f172a', 'cost' => 11000, 'price' => 14900, 'desc' => 'Ultra-light wireless esports mouse with HERO sensor.', 'image' => 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=800&q=80'],
            ['name' => 'Razer BlackWidow V4 Pro', 'brand' => 'Razer', 'category' => 'Gaming', 'variant_group' => 'blackwidow-v4', 'color' => 'Black', 'color_hex' => '#111827', 'cost' => 16000, 'price' => 20900, 'desc' => 'Hot-swappable mechanical keyboard with Command Dial.', 'image' => 'https://images.unsplash.com/photo-1541140532154-b024d705b90a?w=800&q=80', 'new' => true],
            ['name' => 'ASUS TUF Gaming VG27AQ', 'brand' => 'Asus', 'category' => 'Monitors', 'variant_group' => 'tuf-vg27aq', 'color' => 'Black', 'color_hex' => '#0f172a', 'cost' => 28000, 'price' => 34900, 'desc' => '27-inch 165Hz IPS gaming monitor with Adaptive-Sync.', 'image' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=800&q=80', 'featured' => true],

            // Speakers (4)
            ['name' => 'JBL Charge 5 Portable', 'brand' => 'JBL', 'category' => 'Speakers', 'variant_group' => 'jbl-charge-5', 'color' => 'Blue', 'color_hex' => '#2563eb', 'cost' => 12000, 'price' => 15900, 'desc' => 'Powerful portable speaker with powerbank charging.', 'image' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=800&q=80', 'best' => true],
            ['name' => 'Sony SRS-XG300', 'brand' => 'Sony', 'category' => 'Speakers', 'variant_group' => 'sony-xg300', 'color' => 'Black', 'color_hex' => '#0f172a', 'cost' => 22000, 'price' => 27900, 'desc' => 'Party-ready X-Balanced speaker with long battery life.', 'image' => 'https://images.unsplash.com/photo-1545454675-3531b543be5d?w=800&q=80'],
            ['name' => 'Bose SoundLink Flex', 'brand' => 'Bose', 'category' => 'Speakers', 'variant_group' => 'bose-flex', 'color' => 'Stone Blue', 'color_hex' => '#64748b', 'cost' => 14000, 'price' => 17900, 'desc' => 'Rugged waterproof speaker with PositionIQ audio.', 'image' => 'https://images.unsplash.com/photo-1589003077984-894e133dabab?w=800&q=80'],
            ['name' => 'Apple HomePod mini', 'brand' => 'Apple', 'category' => 'Speakers', 'variant_group' => 'homepod-mini', 'color' => 'White', 'color_hex' => '#f8fafc', 'cost' => 9000, 'price' => 11900, 'desc' => 'Compact smart speaker with Siri and room-filling sound.', 'image' => 'https://images.unsplash.com/photo-1589492477829-5e409ceb0288?w=800&q=80'],

            // Chargers & Cables (4)
            ['name' => 'Anker 737 GaNPrime 120W Charger', 'brand' => 'Anker', 'category' => 'Chargers & Cables', 'variant_group' => 'anker-737', 'color' => 'Black', 'color_hex' => '#0f172a', 'cost' => 6500, 'price' => 8900, 'desc' => 'Triple-port GaN charger for laptop and phone fast charging.', 'image' => 'https://images.unsplash.com/photo-1583863788434-e58a36338a0a?w=800&q=80', 'best' => true],
            ['name' => 'Anker PowerCore 20000mAh', 'brand' => 'Anker', 'category' => 'Chargers & Cables', 'variant_group' => 'anker-20000', 'color' => 'Black', 'color_hex' => '#1e293b', 'cost' => 3800, 'price' => 5200, 'desc' => 'High-capacity power bank with dual USB output.', 'image' => 'https://images.unsplash.com/photo-1609091839311-d5365f9ff1c5?w=800&q=80'],
            ['name' => 'Apple 20W USB-C Power Adapter', 'brand' => 'Apple', 'category' => 'Chargers & Cables', 'variant_group' => 'apple-20w', 'color' => 'White', 'color_hex' => '#ffffff', 'cost' => 1800, 'price' => 2490, 'desc' => 'Official fast charger for iPhone and AirPods.', 'image' => 'https://images.unsplash.com/photo-1615526675159-e248c3021d3f?w=800&q=80'],
            ['name' => 'Anker USB-C to Lightning Cable 1.8m', 'brand' => 'Anker', 'category' => 'Chargers & Cables', 'variant_group' => 'anker-cable-cl', 'color' => 'White', 'color_hex' => '#f8fafc', 'cost' => 900, 'price' => 1490, 'desc' => 'MFi-certified durable braided charging cable.', 'image' => 'https://images.unsplash.com/photo-1583863788434-e58a36338a0a?w=800&q=80'],

            // Accessories (0 extras — catalog totals 50 above with chargers)
        ];
    }

    private function storeRemoteImage(string $url, string $directory, string $filename): ?string
    {
        $relative = trim($directory, '/').'/'.$filename;

        try {
            if (Storage::disk('public')->exists($relative) && Storage::disk('public')->size($relative) > 1000) {
                return $relative;
            }

            $response = Http::timeout(25)
                ->withHeaders(['User-Agent' => 'GadgetStoreCatalogSeeder/1.0'])
                ->get($url);

            if (! $response->successful() || strlen($response->body()) < 500) {
                $this->command?->warn("Image skip: {$filename}");

                return Storage::disk('public')->exists($relative) ? $relative : null;
            }

            Storage::disk('public')->put($relative, $response->body());

            return $relative;
        } catch (\Throwable $e) {
            $this->command?->warn('Image error: '.$e->getMessage());

            return null;
        }
    }
}
