<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoProductsSeeder extends Seeder
{
    public function run(): void
    {
        $shop = SiteSetting::query()->first()?->defaultShop
            ?? Shop::query()->orderBy('id')->first();

        if (! $shop) {
            $this->command?->warn('No shop found — skip DemoProductsSeeder.');

            return;
        }

        $settings = SiteSetting::query()->first();
        if ($settings && ! $settings->default_shop_id) {
            $settings->update(['default_shop_id' => $shop->id]);
        }

        $categories = $this->seedCategories($shop->id);
        $brands = $this->seedBrands($shop->id);
        $created = $this->seedProducts($shop->id, $categories, $brands);

        $this->command?->info("Demo catalog ready: {$created} products for shop #{$shop->id} ({$shop->name}).");
    }

    private function seedCategories(int $shopId): array
    {
        $defs = [
            ['name' => 'Smartphones', 'icon' => 'phone', 'featured' => true],
            ['name' => 'Chargers', 'icon' => 'plug', 'featured' => true],
            ['name' => 'Power Bank', 'icon' => 'battery', 'featured' => true],
            ['name' => 'Earbuds', 'icon' => 'earbuds', 'featured' => true],
            ['name' => 'Headphones', 'icon' => 'headphones', 'featured' => false],
            ['name' => 'Smartwatches', 'icon' => 'watch', 'featured' => true],
            ['name' => 'Cables', 'icon' => 'cable', 'featured' => false],
            ['name' => 'Accessories', 'icon' => 'mouse', 'featured' => false],
        ];

        $map = [];
        foreach ($defs as $def) {
            $map[$def['name']] = Category::updateOrCreate(
                ['shop_id' => $shopId, 'slug' => Str::slug($def['name'])],
                [
                    'name' => $def['name'],
                    'icon' => $def['icon'],
                    'description' => $def['name'].' for Maks Gadget',
                    'is_featured' => $def['featured'],
                ]
            );
        }

        return $map;
    }

    private function seedBrands(int $shopId): array
    {
        $names = [
            'Samsung', 'Apple', 'Xiaomi', 'Realme', 'OnePlus',
            'Somostel', 'Anker', 'Baseus', 'Oraimo', 'JBL',
        ];

        $map = [];
        foreach ($names as $i => $name) {
            $map[$name] = Brand::updateOrCreate(
                ['shop_id' => $shopId, 'name' => $name],
                ['sort_order' => $i + 1, 'is_active' => true]
            );
        }

        return $map;
    }

    private function seedProducts(int $shopId, array $categories, array $brands): int
    {
        $rows = $this->catalog();
        $count = 0;

        foreach ($rows as $i => $row) {
            $n = $i + 1;
            $barcode = 'DEMO-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT);
            $category = $categories[$row['category']] ?? null;
            $brand = $brands[$row['brand']] ?? null;

            $specBits = array_filter([
                $row['color'] ?? null,
                $row['ram'] ?? null,
                $row['storage'] ?? null,
            ]);
            $name = $row['name'];
            if (! empty($row['variant_group']) && $specBits) {
                $name .= ' — '.implode(' / ', $specBits);
            }

            Product::updateOrCreate(
                ['shop_id' => $shopId, 'barcode' => $barcode],
                [
                    'name' => $name,
                    'sku' => 'SKU-'.$barcode,
                    'category_id' => $category?->id,
                    'brand_id' => $brand?->id,
                    'brand_name' => $brand?->name ?? $row['brand'],
                    'variant_group' => $row['variant_group'] ?? null,
                    'color' => $row['color'] ?? null,
                    'color_hex' => $row['color_hex'] ?? null,
                    'storage' => $row['storage'] ?? null,
                    'ram' => $row['ram'] ?? null,
                    'cost_price' => $row['cost'],
                    'selling_price' => $row['price'],
                    'original_price' => $row['original'] ?? $row['price'],
                    'stock_quantity' => $row['stock'] ?? random_int(8, 40),
                    'alert_quantity' => 5,
                    'availability' => 'in_stock',
                    'short_description' => $row['description'],
                    'rating' => $row['rating'] ?? 4.5,
                    'review_count' => $row['reviews'] ?? random_int(12, 220),
                    'is_published' => true,
                    'is_new_arrival' => (bool) ($row['new'] ?? ($n <= 10)),
                    'is_best_seller' => (bool) ($row['best'] ?? false),
                    'is_featured' => (bool) ($row['featured'] ?? false),
                ]
            );

            $count++;
        }

        return $count;
    }

    /**
     * Exactly 30 demo SKUs (includes a Samsung S22 color + RAM/ROM family).
     *
     * @return list<array<string, mixed>>
     */
    private function catalog(): array
    {
        $s22 = [];
        foreach (
            [
                ['Green', '#16a34a', [
                    ['4GB', '64GB', 18500, 21999, 12],
                    ['8GB', '128GB', 24500, 28999, 10],
                    ['8GB', '512GB', 32000, 37999, 6],
                ]],
                ['Black', '#1e293b', [
                    ['4GB', '64GB', 18500, 21999, 9],
                    ['8GB', '128GB', 24500, 28999, 14],
                    ['8GB', '512GB', 32000, 37999, 5],
                ]],
                ['Blue', '#2563eb', [
                    ['4GB', '64GB', 18500, 21999, 7],
                    ['8GB', '128GB', 24500, 28999, 11],
                    ['8GB', '512GB', 32000, 37999, 4],
                ]],
            ] as [$color, $hex, $configs]
        ) {
            foreach ($configs as [$ram, $storage, $cost, $price, $stock]) {
                $s22[] = [
                    'name' => 'Samsung Galaxy S22',
                    'category' => 'Smartphones',
                    'brand' => 'Samsung',
                    'variant_group' => 'samsung-s22',
                    'color' => $color,
                    'color_hex' => $hex,
                    'ram' => $ram,
                    'storage' => $storage,
                    'cost' => $cost,
                    'price' => $price,
                    'original' => $price + 2000,
                    'stock' => $stock,
                    'description' => 'Demo Samsung Galaxy S22 family — pick color, then RAM / storage on the product page.',
                    'best' => true,
                    'featured' => true,
                    'new' => true,
                    'rating' => 4.7,
                    'reviews' => 186,
                ];
            }
        }

        $others = [
            [
                'name' => 'iPhone 13 128GB',
                'category' => 'Smartphones', 'brand' => 'Apple',
                'color' => 'Midnight', 'color_hex' => '#1e293b', 'ram' => '4GB', 'storage' => '128GB',
                'cost' => 52000, 'price' => 59999, 'stock' => 8,
                'description' => 'Apple iPhone 13 demo unit with A15 chip.',
                'best' => true, 'featured' => true,
            ],
            [
                'name' => 'Xiaomi Redmi Note 13',
                'category' => 'Smartphones', 'brand' => 'Xiaomi',
                'color' => 'Black', 'color_hex' => '#111827', 'ram' => '8GB', 'storage' => '256GB',
                'cost' => 18500, 'price' => 22999, 'stock' => 20,
                'description' => 'Affordable AMOLED phone with strong battery life.',
                'new' => true,
            ],
            [
                'name' => 'Realme 12 Pro',
                'category' => 'Smartphones', 'brand' => 'Realme',
                'color' => 'Beige', 'color_hex' => '#d4cfc8', 'ram' => '12GB', 'storage' => '256GB',
                'cost' => 28000, 'price' => 32999, 'stock' => 12,
                'description' => 'Stylish mid-range phone with portrait camera focus.',
            ],
            [
                'name' => 'OnePlus Nord CE 3',
                'category' => 'Smartphones', 'brand' => 'OnePlus',
                'color' => 'Aqua', 'color_hex' => '#0ea5e9', 'ram' => '8GB', 'storage' => '128GB',
                'cost' => 26000, 'price' => 30999, 'stock' => 10,
                'description' => 'Smooth OxygenOS experience with fast charging.',
            ],
            [
                'name' => 'Somostel 65W GaN Charger',
                'category' => 'Chargers', 'brand' => 'Somostel',
                'cost' => 900, 'price' => 1490, 'stock' => 40,
                'description' => 'Compact GaN fast charger for phone and laptop.',
                'best' => true, 'new' => true,
            ],
            [
                'name' => 'Anker 20W USB-C Charger',
                'category' => 'Chargers', 'brand' => 'Anker',
                'cost' => 1100, 'price' => 1690, 'stock' => 35,
                'description' => 'Reliable PowerIQ charger for everyday use.',
            ],
            [
                'name' => 'Baseus 33W Dual Port Charger',
                'category' => 'Chargers', 'brand' => 'Baseus',
                'cost' => 850, 'price' => 1290, 'stock' => 28,
                'description' => 'Dual USB ports with smart power allocation.',
            ],
            [
                'name' => 'Oraimo 18W Fast Charger',
                'category' => 'Chargers', 'brand' => 'Oraimo',
                'cost' => 450, 'price' => 790, 'stock' => 50,
                'description' => 'Budget fast charger with Type-C cable support.',
            ],
            [
                'name' => 'Somostel SMS-DY16 Power Bank 20000mAh',
                'category' => 'Power Bank', 'brand' => 'Somostel',
                'cost' => 1100, 'price' => 1600, 'stock' => 25,
                'description' => 'High-capacity power bank for travel and daily charge.',
                'best' => true, 'featured' => true,
            ],
            [
                'name' => 'Anker PowerCore 10000',
                'category' => 'Power Bank', 'brand' => 'Anker',
                'cost' => 1800, 'price' => 2490, 'stock' => 18,
                'description' => 'Pocket-size power bank with solid build quality.',
            ],
            [
                'name' => 'Baseus 30000mAh Power Bank',
                'category' => 'Power Bank', 'brand' => 'Baseus',
                'cost' => 2200, 'price' => 2990, 'stock' => 15,
                'description' => 'Large capacity bank with multiple output ports.',
            ],
            [
                'name' => 'Oraimo FreePods 4',
                'category' => 'Earbuds', 'brand' => 'Oraimo',
                'color' => 'Black', 'color_hex' => '#111827',
                'cost' => 1600, 'price' => 2290, 'stock' => 22,
                'description' => 'True wireless earbuds with deep bass.',
                'new' => true,
            ],
            [
                'name' => 'Xiaomi Redmi Buds 5',
                'category' => 'Earbuds', 'brand' => 'Xiaomi',
                'color' => 'White', 'color_hex' => '#f8fafc',
                'cost' => 2100, 'price' => 2790, 'stock' => 16,
                'description' => 'ANC earbuds with long battery life.',
            ],
            [
                'name' => 'Samsung Galaxy Buds FE',
                'category' => 'Earbuds', 'brand' => 'Samsung',
                'color' => 'Graphite', 'color_hex' => '#374151',
                'cost' => 6500, 'price' => 7990, 'stock' => 9,
                'description' => 'Comfortable Samsung buds with solid call quality.',
                'featured' => true,
            ],
            [
                'name' => 'JBL Tune 510BT',
                'category' => 'Headphones', 'brand' => 'JBL',
                'color' => 'Black', 'color_hex' => '#111827',
                'cost' => 3200, 'price' => 3990, 'stock' => 14,
                'description' => 'Wireless on-ear headphones with JBL Pure Bass.',
            ],
            [
                'name' => 'JBL Tune 760NC',
                'category' => 'Headphones', 'brand' => 'JBL',
                'color' => 'Blue', 'color_hex' => '#2563eb',
                'cost' => 4500, 'price' => 5490, 'stock' => 11,
                'description' => 'Noise-cancelling over-ear headphones with long battery.',
            ],
            [
                'name' => 'Samsung Galaxy Watch 6',
                'category' => 'Smartwatches', 'brand' => 'Samsung',
                'color' => 'Graphite', 'color_hex' => '#374151', 'storage' => '16GB',
                'cost' => 22000, 'price' => 26999, 'stock' => 7,
                'description' => 'Premium Galaxy Watch with health tracking.',
                'featured' => true,
            ],
            [
                'name' => 'Xiaomi Redmi Watch 4',
                'category' => 'Smartwatches', 'brand' => 'Xiaomi',
                'color' => 'Black', 'color_hex' => '#111827',
                'cost' => 4500, 'price' => 5490, 'stock' => 13,
                'description' => 'AMOLED smartwatch with long battery life.',
                'new' => true,
            ],
            [
                'name' => 'Baseus USB-C to Lightning Cable 1m',
                'category' => 'Cables', 'brand' => 'Baseus',
                'cost' => 350, 'price' => 590, 'stock' => 60,
                'description' => 'Durable braided cable for fast charge and sync.',
            ],
            [
                'name' => 'Anker USB-C Cable 2m',
                'category' => 'Cables', 'brand' => 'Anker',
                'cost' => 480, 'price' => 790, 'stock' => 45,
                'description' => 'Long USB-C cable for desk and travel use.',
            ],
            [
                'name' => 'Spigen Liquid Air Case (Universal Demo)',
                'category' => 'Accessories', 'brand' => 'Somostel',
                'color' => 'Matte Black', 'color_hex' => '#1f2937',
                'cost' => 650, 'price' => 990, 'stock' => 30,
                'description' => 'Slim protective case demo accessory.',
            ],
        ];

        return array_slice(array_merge($s22, $others), 0, 30);
    }
}
