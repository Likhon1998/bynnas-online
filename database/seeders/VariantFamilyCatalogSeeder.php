<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Shop;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VariantFamilyCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $shop = SiteSetting::query()->first()?->defaultShop
            ?? Shop::query()->orderBy('id')->first();

        if (! $shop) {
            $this->command?->warn('No shop found — skip VariantFamilyCatalogSeeder.');

            return;
        }

        $categories = $this->resolveCategories($shop->id);
        $brands = $this->resolveBrands($shop->id);
        $this->seedFamilies($shop->id, $categories, $brands);

        $this->command?->info('Variant families ready: '.Product::where('shop_id', $shop->id)->where('barcode', 'like', 'FAMILY-%')->count().' family SKUs.');
    }

    private function resolveCategories(int $shopId): array
    {
        $names = [
            'Smartphones', 'Laptops', 'Tablets', 'Headphones', 'Earbuds',
            'Smartwatches', 'Cameras', 'Gaming', 'Speakers', 'Chargers & Cables',
            'Accessories', 'Monitors',
        ];

        $map = [];
        foreach ($names as $i => $name) {
            $map[$name] = Category::updateOrCreate(
                ['shop_id' => $shopId, 'slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'is_featured' => $i < 6,
                    'description' => $name.' for everyday and pro use',
                ]
            );
        }

        return $map;
    }

    private function resolveBrands(int $shopId): array
    {
        $defs = [
            'Apple' => ['#111111', 'apple.com'],
            'Samsung' => ['#1428A0', 'samsung.com'],
            'Sony' => ['#000000', 'sony.com'],
            'Dell' => ['#007DB8', 'dell.com'],
            'JBL' => ['#FF6600', 'jbl.com'],
            'Anker' => ['#00A9E0', 'anker.com'],
            'Canon' => ['#C8102E', 'canon.com'],
            'Spigen' => ['#1F2937', 'spigen.com'],
            'Google' => ['#4285F4', 'google.com'],
            'Xiaomi' => ['#FF6900', 'mi.com'],
        ];

        $map = [];
        $i = 0;
        foreach ($defs as $name => [$color, $domain]) {
            $brand = Brand::updateOrCreate(
                ['shop_id' => $shopId, 'name' => $name],
                ['sort_order' => ++$i, 'is_active' => true]
            );
            $logo = $this->storeBrandLogo($name, $color, $domain);
            if ($logo) {
                $brand->update(['logo_path' => $logo]);
            }
            $map[$name] = $brand->fresh();
        }

        return $map;
    }

    private function seedFamilies(int $shopId, array $categories, array $brands): void
    {
        $index = 0;
        foreach ($this->families() as $family) {
            $category = $categories[$family['category']] ?? null;
            $brand = $brands[$family['brand']] ?? null;
            $desc = $family['description'];
            $image = $family['image'];

            foreach ($family['variants'] as $variant) {
                $index++;
                if ($index % 5 === 1) {
                    DB::reconnect();
                }

                $barcode = 'FAMILY-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT);
                $imagePath = $this->storeRemoteImage(
                    $variant['image'] ?? $image,
                    'products',
                    'family-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT).'.jpg'
                );

                $colorLabel = $variant['color'] ?? '';
                $storageLabel = $variant['storage'] ?? '';
                $ramLabel = $variant['ram'] ?? '';
                $specBits = array_filter([$colorLabel, $storageLabel, $ramLabel]);
                $name = $family['name'].(count($specBits) ? ' — '.implode(' / ', $specBits) : '');

                $payload = [
                    'name' => $name,
                    'sku' => 'SKU-'.$barcode,
                    'category_id' => $category?->id,
                    'brand_id' => $brand?->id,
                    'brand_name' => $brand?->name ?? $family['brand'],
                    'variant_group' => $family['variant_group'],
                    'color' => $variant['color'] ?? null,
                    'color_hex' => $variant['color_hex'] ?? null,
                    'storage' => $variant['storage'] ?? null,
                    'ram' => $variant['ram'] ?? null,
                    'cost_price' => $variant['cost'],
                    'selling_price' => $variant['price'],
                    'original_price' => $variant['original'] ?? $variant['price'],
                    'stock_quantity' => $variant['stock'] ?? random_int(10, 45),
                    'alert_quantity' => 5,
                    'availability' => 'in_stock',
                    'short_description' => $desc,
                    'image' => $imagePath,
                    'rating' => $family['rating'] ?? 4.7,
                    'review_count' => $family['reviews'] ?? random_int(40, 380),
                    'is_published' => true,
                    'is_new_arrival' => (bool) ($family['new'] ?? ($index <= 15)),
                    'is_best_seller' => (bool) ($family['best'] ?? false),
                    'is_featured' => (bool) ($family['featured'] ?? false),
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
                        DB::reconnect();
                        if ($attempts >= 3) {
                            throw $e;
                        }
                        usleep(200000);
                    }
                }
            }
        }
    }

    /** @return list<array<string, mixed>> */
    private function families(): array
    {
        return [
            [
                'name' => 'iPhone 13 Pro',
                'variant_group' => 'iphone-13-pro',
                'brand' => 'Apple',
                'category' => 'Smartphones',
                'best' => true,
                'featured' => true,
                'image' => 'https://images.unsplash.com/photo-1510557880182-3d4d3cba35a5?w=800&q=80&auto=format&fit=crop',
                'description' => "Apple iPhone 13 Pro brings a ProMotion 120Hz Super Retina XDR display, the A15 Bionic chip, and a versatile triple-camera system with cinematic mode.\n\nThis listing is a variant family: choose your color, then pick the matching storage and RAM configuration. Base configs ship with 64 GB storage / 4 GB RAM, while higher capacity options unlock 128 GB storage with 6 GB RAM for smoother multitasking and more photos, 4K video, and apps.\n\nIncludes Face ID, Ceramic Shield, MagSafe accessories support, and all-day battery life. Ideal if you want a proven Apple flagship with clear color and storage choices on one product page.",
                'variants' => [
                    ['color' => 'Sierra Blue', 'color_hex' => '#A7C1D9', 'storage' => '64 GB', 'ram' => '4 GB', 'cost' => 62000, 'price' => 74900, 'original' => 89900],
                    ['color' => 'Gold', 'color_hex' => '#F5D0A9', 'storage' => '64 GB', 'ram' => '4 GB', 'cost' => 62000, 'price' => 74900, 'original' => 89900],
                    ['color' => 'Graphite', 'color_hex' => '#53524F', 'storage' => '64 GB', 'ram' => '4 GB', 'cost' => 62000, 'price' => 74900, 'original' => 89900],
                    ['color' => 'Silver', 'color_hex' => '#F1F2ED', 'storage' => '128 GB', 'ram' => '6 GB', 'cost' => 68000, 'price' => 82900, 'original' => 96900],
                    ['color' => 'Alpine Green', 'color_hex' => '#576856', 'storage' => '128 GB', 'ram' => '6 GB', 'cost' => 68000, 'price' => 82900, 'original' => 96900],
                ],
            ],
            [
                'name' => 'Samsung Galaxy S23',
                'variant_group' => 'galaxy-s23',
                'brand' => 'Samsung',
                'category' => 'Smartphones',
                'featured' => true,
                'image' => 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=800&q=80&auto=format&fit=crop',
                'description' => "Galaxy S23 packs Snapdragon flagship performance, a bright Dynamic AMOLED display, and a refined camera tuned for night shots and detail.\n\nOpen any S23 variant to switch between Phantom colors and storage/RAM sets: everyday 128 GB / 8 GB configs, plus 256 GB and 512 GB options with higher RAM for creators and power users.\n\nIP68 durability, long software support, and seamless Galaxy ecosystem features (buds, watch, SmartSwitch) make this a strong Android pick across multiple configurations.",
                'variants' => [
                    ['color' => 'Phantom Black', 'color_hex' => '#2B2B2B', 'storage' => '128 GB', 'ram' => '8 GB', 'cost' => 52000, 'price' => 64900],
                    ['color' => 'Cream', 'color_hex' => '#F5E6C8', 'storage' => '128 GB', 'ram' => '8 GB', 'cost' => 52000, 'price' => 64900],
                    ['color' => 'Green', 'color_hex' => '#5F7A61', 'storage' => '256 GB', 'ram' => '8 GB', 'cost' => 56000, 'price' => 69900],
                    ['color' => 'Lavender', 'color_hex' => '#C5B4D6', 'storage' => '256 GB', 'ram' => '12 GB', 'cost' => 59000, 'price' => 73900],
                    ['color' => 'Graphite', 'color_hex' => '#5A5A5A', 'storage' => '512 GB', 'ram' => '12 GB', 'cost' => 65000, 'price' => 79900],
                ],
            ],
            [
                'name' => 'MacBook Air 13" M2',
                'variant_group' => 'macbook-air-m2-13',
                'brand' => 'Apple',
                'category' => 'Laptops',
                'best' => true,
                'featured' => true,
                'image' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800&q=80&auto=format&fit=crop',
                'description' => "MacBook Air with M2 delivers silent fanless performance, a Liquid Retina display, and MagSafe charging in a thin aluminum chassis.\n\nPick finish color first, then storage and memory: start at 256 GB / 8 GB for light work, or step up to 512 GB and 1 TB with 16 GB unified memory for photo libraries, multitasking, and creative apps.\n\nPerfect everyday laptop for students and professionals who want Apple reliability with clear upgrade paths on one product page.",
                'variants' => [
                    ['color' => 'Midnight', 'color_hex' => '#1E293B', 'storage' => '256 GB', 'ram' => '8 GB', 'cost' => 98000, 'price' => 119900],
                    ['color' => 'Starlight', 'color_hex' => '#F5F0E6', 'storage' => '256 GB', 'ram' => '8 GB', 'cost' => 98000, 'price' => 119900],
                    ['color' => 'Silver', 'color_hex' => '#E8E8ED', 'storage' => '512 GB', 'ram' => '8 GB', 'cost' => 112000, 'price' => 139900],
                    ['color' => 'Space Gray', 'color_hex' => '#7D7E80', 'storage' => '512 GB', 'ram' => '16 GB', 'cost' => 128000, 'price' => 159900],
                    ['color' => 'Midnight', 'color_hex' => '#1E293B', 'storage' => '1 TB', 'ram' => '16 GB', 'cost' => 145000, 'price' => 179900],
                ],
            ],
            [
                'name' => 'Dell XPS 15',
                'variant_group' => 'dell-xps-15',
                'brand' => 'Dell',
                'category' => 'Laptops',
                'image' => 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=800&q=80&auto=format&fit=crop',
                'description' => "Dell XPS 15 is a premium Windows creator laptop with a near-borderless display, solid build, and strong CPU/GPU options for editing and development.\n\nVariants cover Platinum and Graphite finishes paired with 512 GB to 2 TB SSD storage and 16 GB or 32 GB RAM. Choose the config that matches your workload—office and study, or heavy creative projects.\n\nIncludes modern ports, comfortable keyboard, and Dell ProSupport-ready reliability for long-term use.",
                'variants' => [
                    ['color' => 'Platinum Silver', 'color_hex' => '#D6D6D6', 'storage' => '512 GB', 'ram' => '16 GB', 'cost' => 118000, 'price' => 144900],
                    ['color' => 'Platinum Silver', 'color_hex' => '#D6D6D6', 'storage' => '1 TB', 'ram' => '16 GB', 'cost' => 132000, 'price' => 159900],
                    ['color' => 'Graphite', 'color_hex' => '#4B5563', 'storage' => '1 TB', 'ram' => '32 GB', 'cost' => 148000, 'price' => 179900],
                    ['color' => 'Graphite', 'color_hex' => '#4B5563', 'storage' => '2 TB', 'ram' => '32 GB', 'cost' => 168000, 'price' => 204900],
                ],
            ],
            [
                'name' => 'iPad Pro 11"',
                'variant_group' => 'ipad-pro-11',
                'brand' => 'Apple',
                'category' => 'Tablets',
                'featured' => true,
                'new' => true,
                'image' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=800&q=80&auto=format&fit=crop',
                'description' => "iPad Pro 11\" combines a Liquid Retina display, Apple Pencil Pro support, and desktop-class performance for drawing, note-taking, and entertainment.\n\nSelect Space Gray or Silver, then storage/RAM: 128 GB / 8 GB for everyday use, or 256 GB and 512 GB with up to 16 GB RAM for large creative libraries and pro apps.\n\nPairs with Magic Keyboard and Stage Manager for a near-laptop workflow when you need it.",
                'variants' => [
                    ['color' => 'Space Gray', 'color_hex' => '#6E6E73', 'storage' => '128 GB', 'ram' => '8 GB', 'cost' => 72000, 'price' => 89900],
                    ['color' => 'Silver', 'color_hex' => '#E3E4E5', 'storage' => '128 GB', 'ram' => '8 GB', 'cost' => 72000, 'price' => 89900],
                    ['color' => 'Space Gray', 'color_hex' => '#6E6E73', 'storage' => '256 GB', 'ram' => '8 GB', 'cost' => 82000, 'price' => 99900],
                    ['color' => 'Silver', 'color_hex' => '#E3E4E5', 'storage' => '256 GB', 'ram' => '16 GB', 'cost' => 92000, 'price' => 114900],
                    ['color' => 'Space Gray', 'color_hex' => '#6E6E73', 'storage' => '512 GB', 'ram' => '16 GB', 'cost' => 105000, 'price' => 129900],
                ],
            ],
            [
                'name' => 'Sony WH-1000XM4',
                'variant_group' => 'sony-wh-1000xm4',
                'brand' => 'Sony',
                'category' => 'Headphones',
                'best' => true,
                'image' => 'https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?w=800&q=80&auto=format&fit=crop',
                'description' => "Sony WH-1000XM4 remains a benchmark for wireless noise cancelling, comfort on long flights, and rich LDAC-capable audio.\n\nThis family focuses on color finishes—Black, Silver, and Blue—so you can match your style while keeping the same acclaimed ANC and multipoint Bluetooth experience.\n\nSpeak-to-Chat, adaptive sound control, and up to 30 hours of battery make these everyday travel companions.",
                'variants' => [
                    ['color' => 'Black', 'color_hex' => '#0F172A', 'cost' => 22000, 'price' => 27900, 'original' => 34900],
                    ['color' => 'Silver', 'color_hex' => '#C0C0C0', 'cost' => 22000, 'price' => 27900, 'original' => 34900],
                    ['color' => 'Blue', 'color_hex' => '#1D4ED8', 'cost' => 22000, 'price' => 27900, 'original' => 34900],
                ],
            ],
            [
                'name' => 'Samsung Galaxy Buds2 Pro',
                'variant_group' => 'galaxy-buds2-pro',
                'brand' => 'Samsung',
                'category' => 'Earbuds',
                'image' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=800&q=80&auto=format&fit=crop',
                'description' => "Galaxy Buds2 Pro deliver 24-bit Hi-Fi audio, intelligent ANC, and a compact charging case that syncs tightly with Samsung phones.\n\nPick from Graphite, White, Purple, or Bora Purple finishes. All share the same audio tuning—use the color swatches on the product page to jump between looks.\n\nIPX7 resistance, seamless Auto Switch, and comfortable sealing tips for workouts and commuting.",
                'variants' => [
                    ['color' => 'Graphite', 'color_hex' => '#4B5563', 'cost' => 12000, 'price' => 15900],
                    ['color' => 'White', 'color_hex' => '#F8FAFC', 'cost' => 12000, 'price' => 15900],
                    ['color' => 'Purple', 'color_hex' => '#7C3AED', 'cost' => 12000, 'price' => 15900],
                    ['color' => 'Bora Purple', 'color_hex' => '#A78BFA', 'cost' => 12000, 'price' => 15900],
                ],
            ],
            [
                'name' => 'Apple Watch Series 9',
                'variant_group' => 'apple-watch-s9',
                'brand' => 'Apple',
                'category' => 'Smartwatches',
                'featured' => true,
                'new' => true,
                'image' => 'https://images.unsplash.com/photo-1434493789847-2f02dc6ca35d?w=800&q=80&auto=format&fit=crop',
                'description' => "Apple Watch Series 9 adds a brighter display, the S9 SiP, and Double Tap gesture control for quick interactions on the go.\n\nVariants map case color and size: 41 mm everyday fits and larger 45 mm options. Storage on watch configs is shown as internal capacity so you can compare models the same way as phones.\n\nTrack sleep, workouts, heart health, and stay connected with iPhone notifications and Apple Pay.",
                'variants' => [
                    ['color' => 'Midnight', 'color_hex' => '#1C1C1E', 'storage' => '32 GB', 'ram' => '1 GB', 'cost' => 38000, 'price' => 45900],
                    ['color' => 'Starlight', 'color_hex' => '#F5F2EB', 'storage' => '32 GB', 'ram' => '1 GB', 'cost' => 38000, 'price' => 45900],
                    ['color' => 'Pink', 'color_hex' => '#F2C4CE', 'storage' => '64 GB', 'ram' => '1 GB', 'cost' => 42000, 'price' => 49900],
                    ['color' => 'Product Red', 'color_hex' => '#A50011', 'storage' => '64 GB', 'ram' => '1 GB', 'cost' => 42000, 'price' => 49900],
                    ['color' => 'Silver', 'color_hex' => '#E3E4E5', 'storage' => '64 GB', 'ram' => '1 GB', 'cost' => 42000, 'price' => 49900],
                ],
            ],
            [
                'name' => 'Canon EOS R10',
                'variant_group' => 'canon-eos-r10',
                'brand' => 'Canon',
                'category' => 'Cameras',
                'image' => 'https://images.unsplash.com/photo-1502920917128-1aa500764cbd?w=800&q=80&auto=format&fit=crop',
                'description' => "Canon EOS R10 is a compact APS-C mirrorless body with Dual Pixel CMOS AF, fast burst shooting, and 4K video for creators stepping up from phones.\n\nChoose Black, White, or Silver body finishes. Specs on each variant note buffer-friendly storage labels so kit bundles stay organized alongside color.\n\nRF mount opens a growing lens system—great starter for travel, vlogs, and events.",
                'variants' => [
                    ['color' => 'Black', 'color_hex' => '#111827', 'storage' => '64 GB', 'cost' => 72000, 'price' => 88900],
                    ['color' => 'White', 'color_hex' => '#F8FAFC', 'storage' => '64 GB', 'cost' => 74000, 'price' => 90900],
                    ['color' => 'Silver', 'color_hex' => '#CBD5E1', 'storage' => '128 GB', 'cost' => 76000, 'price' => 92900],
                ],
            ],
            [
                'name' => 'PlayStation DualSense',
                'variant_group' => 'ps5-dualsense',
                'brand' => 'Sony',
                'category' => 'Gaming',
                'best' => true,
                'image' => 'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?w=800&q=80&auto=format&fit=crop',
                'description' => "The DualSense wireless controller brings haptic feedback, adaptive triggers, and a built-in mic to PlayStation 5 and compatible PC titles.\n\nSwap between White, Midnight Black, Cosmic Red, and Starlight Blue finishes from the color picker on any DualSense product page.\n\nErgonomic grip and Create button workflows keep gameplay comfortable for long sessions.",
                'variants' => [
                    ['color' => 'White', 'color_hex' => '#F8FAFC', 'cost' => 4800, 'price' => 6490],
                    ['color' => 'Midnight Black', 'color_hex' => '#0F172A', 'cost' => 4800, 'price' => 6490],
                    ['color' => 'Cosmic Red', 'color_hex' => '#B91C1C', 'cost' => 5200, 'price' => 6990],
                    ['color' => 'Starlight Blue', 'color_hex' => '#38BDF8', 'cost' => 5200, 'price' => 6990],
                ],
            ],
            [
                'name' => 'JBL Flip 6',
                'variant_group' => 'jbl-flip-6',
                'brand' => 'JBL',
                'category' => 'Speakers',
                'image' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=800&q=80&auto=format&fit=crop',
                'description' => "JBL Flip 6 is a portable Bluetooth speaker with bold JBL Pro Sound, IP67 waterproofing, and PartyBoost pairing for multi-speaker setups.\n\nAvailable in Black, Blue, Red, and Teal—open one color to see the full finish set and pick your vibe.\n\nRoughly 12 hours of playtime and a rugged fabric body for beach days, rooms, and outdoor hangs.",
                'variants' => [
                    ['color' => 'Black', 'color_hex' => '#0F172A', 'cost' => 8500, 'price' => 11900],
                    ['color' => 'Blue', 'color_hex' => '#2563EB', 'cost' => 8500, 'price' => 11900],
                    ['color' => 'Red', 'color_hex' => '#DC2626', 'cost' => 8500, 'price' => 11900],
                    ['color' => 'Teal', 'color_hex' => '#0D9488', 'cost' => 8500, 'price' => 11900],
                ],
            ],
            [
                'name' => 'Spigen MagSafe Case (iPhone 13 Pro)',
                'variant_group' => 'spigen-magsafe-13pro',
                'brand' => 'Spigen',
                'category' => 'Accessories',
                'image' => 'https://images.unsplash.com/photo-1605236453806-6ff36851218e?w=800&q=80&auto=format&fit=crop',
                'description' => "Spigen MagSafe Case for iPhone 13 Pro adds drop protection with precise MagSafe alignment for chargers and wallets.\n\nChoose Clear, Black, or Navy finishes. Designed to show off your phone color while keeping buttons and cameras accessible.\n\nSlim everyday protection that fits the 13 Pro variant family you already stock.",
                'variants' => [
                    ['color' => 'Clear', 'color_hex' => '#E2E8F0', 'cost' => 1200, 'price' => 1990],
                    ['color' => 'Black', 'color_hex' => '#111827', 'cost' => 1200, 'price' => 1990],
                    ['color' => 'Navy', 'color_hex' => '#1E3A8A', 'cost' => 1200, 'price' => 1990],
                ],
            ],
        ];
    }

    private function storeBrandLogo(string $name, string $hex, string $domain): ?string
    {
        $slug = Str::slug($name);
        $relative = 'brands/'.$slug.'.svg';

        if (Storage::disk('public')->exists($relative) && Storage::disk('public')->size($relative) > 50) {
            return $relative;
        }

        // Prefer a remote logo when available; fall back to a simple SVG mark.
        try {
            $response = Http::timeout(12)
                ->withHeaders(['User-Agent' => 'GadgetStoreLogoSeeder/1.0'])
                ->get('https://www.google.com/s2/favicons?domain='.$domain.'&sz=128');

            if ($response->successful() && strlen($response->body()) > 200) {
                $png = 'brands/'.$slug.'.png';
                Storage::disk('public')->put($png, $response->body());

                return $png;
            }
        } catch (\Throwable $e) {
            // continue to SVG fallback
        }

        $initial = strtoupper(substr($name, 0, 1));
        $safeHex = ltrim($hex, '#');
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="160" height="64" viewBox="0 0 160 64">
  <rect width="160" height="64" rx="10" fill="#{$safeHex}"/>
  <text x="80" y="40" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="28" font-weight="700" fill="#ffffff">{$initial}</text>
</svg>
SVG;
        Storage::disk('public')->put($relative, $svg);

        return $relative;
    }

    private function storeRemoteImage(string $url, string $directory, string $filename): ?string
    {
        $relative = trim($directory, '/').'/'.$filename;

        try {
            if (Storage::disk('public')->exists($relative) && Storage::disk('public')->size($relative) > 1000) {
                return $relative;
            }

            $response = Http::timeout(25)
                ->withHeaders(['User-Agent' => 'VariantFamilyCatalogSeeder/1.0'])
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
