<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\Shop;
use App\Models\SiteSetting;
use App\Services\ProductVariantService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo catalog for a general social-commerce store: fashion, beauty, toys, baby, home,
 * electronics and accessories, with real variant families (Color × Size, shades, pack sizes).
 * Idempotent: products are keyed by barcode BSD-0001…
 */
class GeneralCatalogSeeder extends Seeder
{
    private const PHOTO = 'https://images.unsplash.com/%s?w=900&h=900&fit=crop&q=80';

    public function run(): void
    {
        $shop = SiteSetting::query()->first()?->defaultShop
            ?? Shop::query()->orderBy('id')->first();

        if (! $shop) {
            $this->command?->warn('No shop found — skip GeneralCatalogSeeder.');

            return;
        }

        $settings = SiteSetting::query()->first();
        if ($settings) {
            $settings->fill([
                'default_shop_id' => $settings->default_shop_id ?: $shop->id,
                'store_name' => $settings->store_name ?: 'Bynnas Social',
                'trusted_by_text' => 'Trusted by shoppers across Bangladesh',
            ])->save();
        }

        ProductAttribute::ensureDefaults($shop->id);
        $attributes = ProductAttribute::where('shop_id', $shop->id)->get()->keyBy('slug');

        $categories = $this->seedCategories($shop->id);
        $brands = $this->seedBrands($shop->id);
        $count = $this->seedProducts($shop->id, $categories, $brands, $attributes);

        $this->command?->info("General catalog ready: {$count} sellable variants.");
    }

    /** @return array<string, Category> */
    private function seedCategories(int $shopId): array
    {
        $defs = [
            ['name' => 'Fashion', 'icon' => 'shirt', 'description' => 'Everyday tees, denim and jackets', 'featured' => true, 'photo' => 'photo-1445205170230-053b83016050'],
            ['name' => 'Cosmetics & Beauty', 'icon' => 'sparkles', 'description' => 'Makeup, skincare and body care', 'featured' => true, 'photo' => 'photo-1596462502278-27bfdc403348'],
            ['name' => 'Toys & Kids', 'icon' => 'toy', 'description' => 'Building sets and playtime favourites', 'featured' => true, 'photo' => 'photo-1558060370-d644479cb6f7'],
            ['name' => 'Baby Care', 'icon' => 'baby', 'description' => 'Soft, safe picks for little ones', 'featured' => true, 'photo' => 'photo-1515488042361-ee00e0ddd4e4'],
            ['name' => 'Home & Kitchen', 'icon' => 'home', 'description' => 'Cookware, mugs and bedding', 'featured' => true, 'photo' => 'photo-1556909114-f6e7ad7d3136'],
            ['name' => 'Electronics', 'icon' => 'headphones', 'description' => 'Audio and smart wearables', 'featured' => true, 'photo' => 'photo-1505740420928-5e560c06d30e'],
            ['name' => 'Accessories', 'icon' => 'bag', 'description' => 'Bags, wallets and sunglasses', 'featured' => true, 'photo' => 'photo-1553062407-98eeb64c6a62'],
        ];

        $map = [];
        foreach ($defs as $def) {
            $slug = Str::slug($def['name']);
            $image = $this->storeRemoteImage(sprintf(self::PHOTO, $def['photo']), 'categories', $slug.'.jpg');

            $map[$def['name']] = Category::updateOrCreate(
                ['shop_id' => $shopId, 'slug' => $slug],
                [
                    'name' => $def['name'],
                    'icon' => $def['icon'],
                    'description' => $def['description'],
                    'is_featured' => $def['featured'],
                    'image_path' => $image,
                    'product_count_label' => null,
                ]
            );
        }

        return $map;
    }

    /** @return array<string, Brand> */
    private function seedBrands(int $shopId): array
    {
        $defs = [
            'Bynnas Basics' => '#0f172a',
            'Urban Thread' => '#1e3a8a',
            'Glow Lab' => '#be185d',
            'PlayNest' => '#ea580c',
            'Little Sprout' => '#0891b2',
            'HomeCraft' => '#15803d',
            'Nova Tech' => '#4338ca',
            'Carry Co.' => '#92400e',
        ];

        $map = [];
        $i = 0;
        foreach ($defs as $name => $color) {
            $brand = Brand::updateOrCreate(
                ['shop_id' => $shopId, 'name' => $name],
                ['sort_order' => ++$i, 'is_active' => true]
            );

            $path = 'brands/'.Str::slug($name).'.svg';
            if (! Storage::disk('public')->exists($path)) {
                $initials = collect(preg_split('/\s+/', trim(str_replace('.', '', $name))))
                    ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
                Storage::disk('public')->put($path, <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="160" height="64" viewBox="0 0 160 64">
  <rect width="160" height="64" rx="12" fill="{$color}"/>
  <text x="80" y="41" text-anchor="middle" font-family="Inter, Arial, Helvetica, sans-serif" font-size="26" font-weight="800" letter-spacing="2" fill="#ffffff">{$initials}</text>
</svg>
SVG);
            }
            if ($brand->logo_path !== $path) {
                $brand->update(['logo_path' => $path]);
            }

            $map[$name] = $brand;
        }

        return $map;
    }

    private function seedProducts(int $shopId, array $categories, array $brands, $attributes): int
    {
        $variantService = app(ProductVariantService::class);
        $n = 0;

        foreach ($this->catalog() as $family) {
            $group = Str::slug($family['name']);
            $axes = $family['axes'] ?? [];
            $combos = $this->combinations($axes);
            $isFamily = count($combos) > 1;

            $gallery = [];
            foreach ($family['photos'] as $k => $photoId) {
                $path = $this->storeRemoteImage(sprintf(self::PHOTO, $photoId), 'products/demo', $group.'-'.($k + 1).'.jpg');
                if ($path) {
                    $gallery[] = $path;
                }
            }

            foreach ($combos as $combo) {
                $n++;
                $barcode = 'BSD-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
                $labels = array_values(array_map(fn ($opt) => $opt['value'], $combo));
                $name = $family['name'].($labels !== [] ? ' - '.implode(' / ', $labels) : '');

                $price = (float) $family['price'];
                foreach ($combo as $slug => $opt) {
                    $price += (float) ($family['price_add'][$slug][$opt['value']] ?? 0);
                }
                $ratio = $family['price'] > 0 ? $price / $family['price'] : 1;
                $cost = round($family['cost'] * $ratio);
                $original = isset($family['original']) ? round($family['original'] * $ratio) : $price;

                $images = $gallery;
                if (($family['art'] ?? null) && isset($combo['color'])) {
                    $art = $this->storeArt($family['art'], $combo['color']['hex'], $group.'-'.Str::slug($combo['color']['value']));
                    array_unshift($images, $art);
                }

                $seed = crc32($barcode);
                $stock = ($family['sold_out'] ?? null) === $labels ? 0 : 6 + ($seed % 40);

                $product = Product::updateOrCreate(
                    ['shop_id' => $shopId, 'barcode' => $barcode],
                    [
                        'name' => $name,
                        'sku' => strtoupper(Str::limit(Str::slug($family['name'], ''), 8, '')).'-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT),
                        'category_id' => $categories[$family['category']]->id ?? null,
                        'brand_id' => $brands[$family['brand']]->id ?? null,
                        'brand_name' => $family['brand'],
                        'variant_group' => $isFamily ? $group : null,
                        'cost_price' => $cost,
                        'selling_price' => $price,
                        'original_price' => max($original, $price),
                        'stock_quantity' => $stock,
                        'reserved_stock' => 0,
                        'alert_quantity' => 5,
                        'availability' => 'in_stock',
                        'short_description' => $family['summary'],
                        'description' => $family['description'],
                        'seo_title' => $family['name'].' | Bynnas Social',
                        'meta_description' => Str::limit($family['summary'].' Cash on delivery across Bangladesh.', 155, ''),
                        'image' => $images[0] ?? null,
                        'image_2' => $images[1] ?? null,
                        'image_3' => $images[2] ?? null,
                        'rating' => $family['rating'] ?? round(4.2 + ($seed % 8) / 10, 1),
                        'review_count' => $family['reviews'] ?? 12 + ($seed % 180),
                        'is_published' => true,
                        'is_new_arrival' => (bool) ($family['new'] ?? false),
                        'is_best_seller' => (bool) ($family['best'] ?? false),
                        'is_featured' => (bool) ($family['featured'] ?? false),
                    ]
                );

                $product->galleryImages()->delete();
                foreach ($images as $sort => $path) {
                    ProductImage::create(['product_id' => $product->id, 'path' => $path, 'sort_order' => $sort]);
                }

                $values = [];
                foreach (array_merge($family['fixed'] ?? [], $combo) as $slug => $opt) {
                    $attribute = $attributes->get($slug);
                    if (! $attribute) {
                        continue;
                    }
                    $values[$attribute->id] = is_array($opt) ? $opt : ['value' => $opt];
                }
                $variantService->syncValues($product, $values);
            }
        }

        return $n;
    }

    /**
     * Cartesian product of variant axes.
     *
     * @param  array<string, array<int|string, string>>  $axes  slug => [label, …] or [label => hex]
     * @return list<array<string, array{value: string, hex: ?string}>>
     */
    private function combinations(array $axes): array
    {
        $combos = [[]];
        foreach ($axes as $slug => $values) {
            $next = [];
            foreach ($combos as $combo) {
                foreach ($values as $key => $val) {
                    $option = is_string($key) ? ['value' => $key, 'hex' => $val] : ['value' => $val, 'hex' => null];
                    $next[] = $combo + [$slug => $option];
                }
            }
            $combos = $next;
        }

        return $combos;
    }

    /** Colour-accurate product art for families that sell many colours of one design. */
    private function storeArt(string $type, string $hex, string $name): string
    {
        $path = 'products/demo/'.$name.'.svg';
        $stroke = $this->shade($hex, -0.28);
        $light = strtolower($hex) === '#f8fafc' || strtolower($hex) === '#ffffff';
        $stroke = $light ? '#cbd5e1' : $stroke;

        $shape = match ($type) {
            'tee' => <<<SVG
  <path d="M300 170 L210 205 L135 300 L205 350 L245 322 L245 640 L555 640 L555 322 L595 350 L665 300 L590 205 L500 170 Q400 245 300 170 Z" fill="{$hex}" stroke="{$stroke}" stroke-width="6" stroke-linejoin="round"/>
  <path d="M300 170 Q400 245 500 170" fill="none" stroke="{$stroke}" stroke-width="10" stroke-linecap="round"/>
  <path d="M245 322 L245 300 M555 322 L555 300" stroke="{$stroke}" stroke-width="4" opacity=".5"/>
SVG,
            default => <<<SVG
  <rect x="330" y="430" width="140" height="230" rx="14" fill="#1f2937"/>
  <rect x="330" y="430" width="140" height="26" fill="#d4a373"/>
  <rect x="345" y="330" width="110" height="112" rx="8" fill="#e5e7eb" stroke="#9ca3af" stroke-width="3"/>
  <path d="M355 335 L355 225 Q400 150 445 205 L445 335 Z" fill="{$hex}" stroke="{$stroke}" stroke-width="5" stroke-linejoin="round"/>
  <circle cx="560" cy="300" r="70" fill="{$hex}" stroke="{$stroke}" stroke-width="5"/>
SVG,
        };

        Storage::disk('public')->put($path, <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" viewBox="0 0 800 800">
  <defs><linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f8fafc"/><stop offset="1" stop-color="#e2e8f0"/></linearGradient></defs>
  <rect width="800" height="800" fill="url(#bg)"/>
  <ellipse cx="400" cy="690" rx="230" ry="22" fill="#0f172a" opacity=".08"/>
{$shape}
</svg>
SVG);

        return $path;
    }

    private function shade(string $hex, float $amount): string
    {
        $hex = ltrim($hex, '#');
        $rgb = array_map('hexdec', str_split(strlen($hex) === 3 ? preg_replace('/(.)/', '$1$1', $hex) : $hex, 2));
        $rgb = array_map(fn ($c) => (int) max(0, min(255, $c + $c * $amount)), $rgb);

        return sprintf('#%02x%02x%02x', ...$rgb);
    }

    private function storeRemoteImage(string $url, string $directory, string $filename): ?string
    {
        $relative = trim($directory, '/').'/'.$filename;

        try {
            if (Storage::disk('public')->exists($relative) && Storage::disk('public')->size($relative) > 1000) {
                return $relative;
            }

            $response = Http::timeout(25)
                ->withHeaders(['User-Agent' => 'BynnasSocialCatalogSeeder/1.0'])
                ->get($url);

            if (! $response->successful() || strlen($response->body()) < 500) {
                $this->command?->warn("Image skip: {$filename}");

                return null;
            }

            Storage::disk('public')->put($relative, $response->body());

            return $relative;
        } catch (\Throwable $e) {
            $this->command?->warn('Image error: '.$e->getMessage());

            return null;
        }
    }

    /** @return list<array<string, mixed>> */
    private function catalog(): array
    {
        return [
            [
                'name' => 'Classic Cotton T-Shirt', 'category' => 'Fashion', 'brand' => 'Bynnas Basics',
                'cost' => 320, 'price' => 650, 'original' => 790,
                'axes' => ['color' => ['Black' => '#111827', 'White' => '#f8fafc', 'Navy' => '#1e3a8a'], 'size' => ['M', 'L', 'XL']],
                'price_add' => ['size' => ['XL' => 50]],
                'fixed' => ['material' => 'Cotton'],
                'art' => 'tee', 'photos' => ['photo-1521572163474-6864f9cf17ab', 'photo-1586790170083-2f9ceadc732d'],
                'sold_out' => ['Navy', 'XL'],
                'summary' => 'Soft 180 GSM combed cotton tee with a relaxed everyday fit.',
                'description' => "Our best-selling everyday tee in breathable 180 GSM combed cotton.\n\n• Relaxed regular fit\n• Pre-shrunk, colour-fast fabric\n• Reinforced neck rib that keeps its shape\n\nSize guide: M fits chest 38–40\", L fits 40–42\", XL fits 42–44\".",
                'best' => true, 'featured' => true, 'new' => true, 'rating' => 4.7, 'reviews' => 186,
            ],
            [
                'name' => 'Slim Fit Denim Jeans', 'category' => 'Fashion', 'brand' => 'Urban Thread',
                'cost' => 980, 'price' => 1890, 'original' => 2290,
                'axes' => ['size' => ['30', '32', '34', '36']],
                'fixed' => ['color' => ['value' => 'Blue', 'hex' => '#1d4ed8'], 'material' => 'Denim'],
                'photos' => ['photo-1604176354204-9268737828e4', 'photo-1576995853123-5a10305d93c0'],
                'summary' => 'Stretch denim jeans with a clean slim fit and mid-rise waist.',
                'description' => "Comfort stretch denim that moves with you.\n\n• Slim fit, mid-rise\n• 98% cotton, 2% elastane\n• Five-pocket styling\n\nSizes are waist in inches.",
                'best' => true, 'new' => true,
            ],
            [
                'name' => 'Faux Leather Biker Jacket', 'category' => 'Fashion', 'brand' => 'Urban Thread',
                'cost' => 1900, 'price' => 3450, 'original' => 3990,
                'axes' => ['size' => ['M', 'L', 'XL']],
                'fixed' => ['color' => ['value' => 'Black', 'hex' => '#111827'], 'material' => 'Faux Leather'],
                'photos' => ['photo-1551028719-00167b16eac5'],
                'summary' => 'Classic biker jacket in soft vegan leather with silver zips.',
                'description' => "A wardrobe staple with an asymmetric zip, notch collar and quilted lining.\n\n• Soft faux leather shell\n• Three zip pockets\n• Wipe-clean finish",
                'featured' => true,
            ],
            [
                'name' => 'Matte Lipstick', 'category' => 'Cosmetics & Beauty', 'brand' => 'Glow Lab',
                'cost' => 180, 'price' => 450, 'original' => 550,
                'axes' => ['color' => ['Ruby Red' => '#b91c1c', 'Coral' => '#f87171', 'Berry' => '#831843', 'Nude Rose' => '#c08081']],
                'art' => 'lipstick', 'photos' => ['photo-1586495777744-4413f21062fa'],
                'summary' => 'Long-lasting matte lipstick with a creamy, non-drying formula.',
                'description' => "Rich colour payoff in one swipe with up to 8 hours of wear.\n\n• Enriched with vitamin E\n• Transfer-resistant matte finish\n• 3.5 g bullet",
                'best' => true, 'new' => true, 'rating' => 4.8, 'reviews' => 241,
            ],
            [
                'name' => 'Hydrating Body Lotion', 'category' => 'Cosmetics & Beauty', 'brand' => 'Glow Lab',
                'cost' => 260, 'price' => 590, 'original' => 690,
                'axes' => ['size' => ['100 ml', '200 ml']],
                'price_add' => ['size' => ['200 ml' => 300]],
                'photos' => ['photo-1620916566398-39f1143ab7be'],
                'summary' => 'Fragrance-free daily body lotion for soft, hydrated skin.',
                'description' => "Lightweight lotion with shea butter and glycerin that absorbs quickly.\n\n• Fragrance free\n• Suitable for sensitive skin\n• Dermatologically tested",
            ],
            [
                'name' => 'Everyday Makeup Brush Kit', 'category' => 'Cosmetics & Beauty', 'brand' => 'Glow Lab',
                'cost' => 520, 'price' => 1290, 'original' => 1590,
                'photos' => ['photo-1596462502278-27bfdc403348', 'photo-1522335789203-aabd1fc54bc9'],
                'summary' => 'Soft synthetic brushes for face and eyes in a travel pouch.',
                'description' => "Everything you need for a flawless everyday look.\n\n• Powder, blush, contour and eye brushes\n• Cruelty-free synthetic bristles\n• Travel pouch included",
                'new' => true,
            ],
            [
                'name' => 'Kids Building Blocks Set', 'category' => 'Toys & Kids', 'brand' => 'PlayNest',
                'cost' => 560, 'price' => 1290, 'original' => 1490,
                'axes' => ['size' => ['250 pcs', '500 pcs']],
                'price_add' => ['size' => ['500 pcs' => 900]],
                'fixed' => ['material' => 'ABS Plastic'],
                'photos' => ['photo-1587654780291-39c9404d746b'],
                'summary' => 'Colourful interlocking blocks that spark creativity. Ages 4+.',
                'description' => "Build anything you imagine with bright, durable blocks.\n\n• Non-toxic ABS plastic\n• Compatible with major block brands\n• Storage box included",
                'best' => true, 'featured' => true,
            ],
            [
                'name' => 'Classic Die-cast Toy Car', 'category' => 'Toys & Kids', 'brand' => 'PlayNest',
                'cost' => 280, 'price' => 690,
                'fixed' => ['color' => ['value' => 'White', 'hex' => '#f8fafc'], 'material' => 'Metal'],
                'photos' => ['photo-1581235720704-06d3acfcb36f'],
                'summary' => 'Vintage-style die-cast car with pull-back action.',
                'description' => "A collectible retro car with opening doors and pull-back motor.\n\n• Die-cast metal body\n• Rubber tyres\n• Ages 3+",
            ],
            [
                'name' => 'Soft Plush Teddy Bear', 'category' => 'Baby Care', 'brand' => 'Little Sprout',
                'cost' => 380, 'price' => 890, 'original' => 1090,
                'axes' => ['size' => ['30 cm', '50 cm']],
                'price_add' => ['size' => ['50 cm' => 600]],
                'fixed' => ['color' => ['value' => 'Cream', 'hex' => '#f5e6c8']],
                'photos' => ['photo-1559454403-b8fb88521f11'],
                'summary' => 'Huggable teddy bear with baby-safe stitched eyes.',
                'description' => "Super-soft plush that is safe from day one.\n\n• Embroidered safety eyes\n• Hypoallergenic filling\n• Machine washable",
                'best' => true, 'new' => true,
            ],
            [
                'name' => 'Baby Play & Learn Set', 'category' => 'Baby Care', 'brand' => 'Little Sprout',
                'cost' => 700, 'price' => 1590,
                'photos' => ['photo-1515488042361-ee00e0ddd4e4'],
                'summary' => 'Soft toys, picture book and sensory shapes for early learning.',
                'description' => "A gift-ready bundle that supports touch, sight and early words.\n\n• Plush animal friends\n• Board picture book\n• Sensory shapes and rattles",
            ],
            [
                'name' => 'Ceramic Coffee Mug', 'category' => 'Home & Kitchen', 'brand' => 'HomeCraft',
                'cost' => 150, 'price' => 390,
                'axes' => ['size' => ['350 ml', '450 ml']],
                'price_add' => ['size' => ['450 ml' => 60]],
                'fixed' => ['color' => ['value' => 'White', 'hex' => '#f8fafc'], 'material' => 'Ceramic'],
                'photos' => ['photo-1514228742587-6b1558fcca3d'],
                'summary' => 'Minimal glazed ceramic mug for coffee and tea.',
                'description' => "A clean, rounded mug that feels great in the hand.\n\n• Microwave and dishwasher safe\n• Lead-free glaze",
                'new' => true,
            ],
            [
                'name' => 'Cotton Bedsheet Set', 'category' => 'Home & Kitchen', 'brand' => 'HomeCraft',
                'cost' => 780, 'price' => 1490, 'original' => 1790,
                'axes' => ['size' => ['Single', 'Double', 'King']],
                'price_add' => ['size' => ['Double' => 500, 'King' => 1000]],
                'fixed' => ['material' => 'Cotton'],
                'photos' => ['photo-1522771739844-6a9f6d5f14af'],
                'summary' => 'Breathable cotton bedsheet with two matching pillow covers.',
                'description' => "Soft, breathable cotton for a better night's sleep.\n\n• 200 thread count\n• Includes 2 pillow covers\n• Fade-resistant",
                'featured' => true,
            ],
            [
                'name' => 'Enamel Cookware Pot', 'category' => 'Home & Kitchen', 'brand' => 'HomeCraft',
                'cost' => 1150, 'price' => 2290, 'original' => 2690,
                'axes' => ['size' => ['20 cm', '24 cm']],
                'price_add' => ['size' => ['24 cm' => 500]],
                'fixed' => ['color' => ['value' => 'Red', 'hex' => '#dc2626']],
                'photos' => ['photo-1556909114-f6e7ad7d3136'],
                'summary' => 'Enamel-coated pot for slow cooking, curries and stews.',
                'description' => "Even heat distribution with a tight-fitting lid.\n\n• Works on gas, electric and induction\n• Easy-clean enamel",
            ],
            [
                'name' => 'Wireless Earbuds Pro', 'category' => 'Electronics', 'brand' => 'Nova Tech',
                'cost' => 900, 'price' => 1890, 'original' => 2490,
                'axes' => ['color' => ['Black' => '#111827', 'White' => '#f8fafc']],
                'photos' => ['photo-1590658268037-6bf12165a8df'],
                'summary' => 'Bluetooth 5.3 earbuds with 30-hour battery and quick charge.',
                'description' => "Clear sound and deep bass in a pocket-size case.\n\n• Bluetooth 5.3, low latency\n• 30 hours with case\n• IPX4 sweat resistant",
                'best' => true, 'rating' => 4.6, 'reviews' => 312,
            ],
            [
                'name' => 'Over-Ear Wireless Headphones', 'category' => 'Electronics', 'brand' => 'Nova Tech',
                'cost' => 1500, 'price' => 2990, 'original' => 3490,
                'fixed' => ['color' => ['value' => 'Black', 'hex' => '#111827']],
                'photos' => ['photo-1505740420928-5e560c06d30e'],
                'summary' => 'Foldable over-ear headphones with 40-hour battery.',
                'description' => "Cushioned ear cups and rich sound for music and calls.\n\n• 40-hour battery\n• Built-in microphone\n• Foldable design",
            ],
            [
                'name' => 'Minimal Smart Watch', 'category' => 'Electronics', 'brand' => 'Nova Tech',
                'cost' => 1800, 'price' => 3490, 'original' => 3990,
                'axes' => ['size' => ['Regular', 'Large']],
                'price_add' => ['size' => ['Large' => 300]],
                'fixed' => ['color' => ['value' => 'White', 'hex' => '#f8fafc']],
                'photos' => ['photo-1523275335684-37898b6baf30'],
                'summary' => 'Fitness tracking, notifications and 7-day battery.',
                'description' => "Track steps, sleep and heart rate with a clean round display.\n\n• 7-day battery\n• Call and app notifications\n• Water resistant",
                'new' => true,
            ],
            [
                'name' => 'Leather Bifold Wallet', 'category' => 'Accessories', 'brand' => 'Carry Co.',
                'cost' => 420, 'price' => 990,
                'fixed' => ['color' => ['value' => 'Brown', 'hex' => '#7c4a2d'], 'material' => 'Leather'],
                'photos' => ['photo-1627123424574-724758594e93'],
                'summary' => 'Slim genuine leather wallet with 6 card slots.',
                'description' => "Ages beautifully with everyday use.\n\n• Genuine leather\n• 6 card slots, 2 note compartments\n• Slim profile",
            ],
            [
                'name' => 'Everyday Backpack', 'category' => 'Accessories', 'brand' => 'Carry Co.',
                'cost' => 850, 'price' => 1790, 'original' => 2190,
                'fixed' => ['color' => ['value' => 'Navy', 'hex' => '#1e3a8a'], 'material' => 'Polyester'],
                'photos' => ['photo-1553062407-98eeb64c6a62'],
                'summary' => 'Water-resistant backpack with padded laptop sleeve.',
                'description' => "Built for campus, office and weekend trips.\n\n• Fits 15.6\" laptop\n• Water-resistant fabric\n• Hidden back pocket",
                'best' => true,
            ],
            [
                'name' => 'Classic Sunglasses', 'category' => 'Accessories', 'brand' => 'Carry Co.',
                'cost' => 450, 'price' => 1190,
                'fixed' => ['color' => ['value' => 'Black', 'hex' => '#111827']],
                'photos' => ['photo-1572635196237-14b3f281503f'],
                'summary' => 'UV400 polarised sunglasses in a timeless frame.',
                'description' => "Everyday eye protection with style.\n\n• UV400 polarised lenses\n• Lightweight frame\n• Case and cloth included",
            ],
            [
                'name' => 'Woven Top-Handle Handbag', 'category' => 'Accessories', 'brand' => 'Carry Co.',
                'cost' => 1100, 'price' => 2490, 'original' => 2890,
                'fixed' => ['color' => ['value' => 'Tan', 'hex' => '#c2703d'], 'material' => 'Vegan Leather'],
                'photos' => ['photo-1590874103328-eac38a683ce7'],
                'summary' => 'Structured woven handbag with detachable shoulder strap.',
                'description' => "A statement bag for work and weekends.\n\n• Vegan leather flap\n• Detachable strap\n• Inner zip pocket",
                'new' => true, 'featured' => true,
            ],
        ];
    }
}
