<?php

namespace Database\Seeders;

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

/**
 * Assigns unique, category-correct product images (no shared laptop fallbacks).
 */
class ProductImageRepairSeeder extends Seeder
{
    public function run(): void
    {
        $shop = SiteSetting::query()->first()?->defaultShop
            ?? Shop::query()->orderBy('id')->first();

        if (! $shop) {
            $this->command?->warn('No shop — skip ProductImageRepairSeeder.');

            return;
        }

        $map = $this->imageMap();
        $updated = 0;
        $n = 0;
        $pathCache = [];

        Product::query()
            ->where('shop_id', $shop->id)
            ->with('category')
            ->orderBy('id')
            ->chunkById(20, function ($products) use ($map, &$updated, &$n, &$pathCache) {
                DB::reconnect();

                foreach ($products as $product) {
                    $n++;
                    $url = $this->resolveUrl($product, $map);
                    if (! $url) {
                        $this->command?->warn('No image map for: '.$product->name);

                        continue;
                    }

                    $cacheKey = $product->variant_group ?: ('id-'.$product->id);
                    if (! isset($pathCache[$cacheKey])) {
                        $filename = 'fix-'.Str::slug((string) $cacheKey).'.jpg';
                        $pathCache[$cacheKey] = $this->storeRemoteImage($url, 'products', $filename);
                    }

                    $path = $pathCache[$cacheKey];
                    if (! $path) {
                        continue;
                    }

                    $attempts = 0;
                    while (true) {
                        try {
                            $product->forceFill(['image' => $path])->saveQuietly();
                            ProductImage::updateOrCreate(
                                ['product_id' => $product->id, 'path' => $path],
                                ['sort_order' => 0]
                            );
                            ProductImage::where('product_id', $product->id)->where('path', '!=', $path)->delete();
                            $updated++;
                            break;
                        } catch (\Throwable $e) {
                            $attempts++;
                            DB::reconnect();
                            if ($attempts >= 3) {
                                $this->command?->warn('DB error for '.$product->name.': '.$e->getMessage());
                                break;
                            }
                            usleep(250000);
                        }
                    }
                }
            });

        $this->command?->info("Product images repaired: {$updated}/{$n}");
    }

    private function resolveUrl(Product $product, array $map): ?string
    {
        $group = (string) ($product->variant_group ?? '');
        if ($group !== '' && isset($map['groups'][$group])) {
            return $map['groups'][$group];
        }

        $barcode = (string) $product->barcode;
        if ($barcode !== '' && isset($map['barcodes'][$barcode])) {
            return $map['barcodes'][$barcode];
        }

        $name = strtolower($product->name);
        foreach ($map['keywords'] as $keyword => $url) {
            if (str_contains($name, $keyword)) {
                return $url;
            }
        }

        $cat = strtolower((string) ($product->category?->name ?? ''));

        return $map['categories'][$cat] ?? $map['fallback'];
    }

    /** @return array{groups: array<string,string>, barcodes: array<string,string>, keywords: array<string,string>, categories: array<string,string>, fallback: string} */
    private function imageMap(): array
    {
        return [
            'groups' => [
                // Phones
                'iphone-13-pro' => 'https://images.unsplash.com/photo-1510557880182-3d4d3cba35a5?w=800&q=80&auto=format&fit=crop',
                'iphone-16-pro-max' => 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=800&q=80&auto=format&fit=crop',
                'iphone-16' => 'https://images.unsplash.com/photo-1591337676887-a217a6970a8a?w=800&q=80&auto=format&fit=crop',
                'galaxy-s23' => 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=800&q=80&auto=format&fit=crop',
                'galaxy-s25-ultra' => 'https://images.unsplash.com/photo-1610945415295-d9bbf067e59c?w=800&q=80&auto=format&fit=crop',
                'galaxy-a56' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800&q=80&auto=format&fit=crop',
                'pixel-9-pro' => 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=800&q=80&auto=format&fit=crop',
                'xiaomi-14t-pro' => 'https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?w=800&q=80&auto=format&fit=crop',
                'oneplus-13' => 'https://images.unsplash.com/photo-1585060544812-6b45742d762f?w=800&q=80&auto=format&fit=crop',
                'nothing-2a' => 'https://images.unsplash.com/photo-1601784551446-20c9e07cdbdb?w=800&q=80&auto=format&fit=crop',

                // Laptops
                'macbook-air-m2-13' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800&q=80&auto=format&fit=crop',
                'macbook-air-m3-13' => 'https://images.unsplash.com/photo-1484788984921-03950022c9ef?w=800&q=80&auto=format&fit=crop',
                'macbook-pro-14-m3' => 'https://images.unsplash.com/photo-1511385348-a52b4a160dc2?w=800&q=80&auto=format&fit=crop',
                'dell-xps-15' => 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=800&q=80&auto=format&fit=crop',
                'dell-xps-13' => 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=800&q=80&auto=format&fit=crop',
                'rog-g14' => 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=800&q=80&auto=format&fit=crop',
                'hp-pavilion-15' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800&q=80&auto=format&fit=crop',
                'thinkpad-e14' => 'https://images.unsplash.com/photo-1525547719571-a2d4ac8945e2?w=800&q=80&auto=format&fit=crop',

                // Tablets
                'ipad-pro-11' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=800&q=80&auto=format&fit=crop',
                'ipad-air-m2' => 'https://images.unsplash.com/photo-1561154464-82e9adf32764?w=800&q=80&auto=format&fit=crop',
                'ipad-10' => 'https://images.unsplash.com/photo-1585790050230-5dd28404ccb9?w=800&q=80&auto=format&fit=crop',
                'tab-s9-fe' => 'https://images.unsplash.com/photo-1561154464-82e9adf32764?w=800&q=80&auto=format&fit=crop',
                'xiaomi-pad-6' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=800&q=80&auto=format&fit=crop',

                // Audio
                'sony-wh-1000xm4' => 'https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?w=800&q=80&auto=format&fit=crop',
                'sony-xm5' => 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=800&q=80&auto=format&fit=crop',
                'bose-qc-ultra' => 'https://images.unsplash.com/photo-1484704849700-f032a568e944?w=800&q=80&auto=format&fit=crop',
                'airpods-max' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&q=80&auto=format&fit=crop',
                'jbl-770nc' => 'https://images.unsplash.com/photo-1484704849700-f032a568e944?w=800&q=80&auto=format&fit=crop',
                'razer-blackshark' => 'https://images.unsplash.com/photo-1599669454699-248893623440?w=800&q=80&auto=format&fit=crop',
                'galaxy-buds2-pro' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=800&q=80&auto=format&fit=crop',
                'airpods-pro-2' => 'https://images.unsplash.com/photo-1600294037681-c80b4cb5b434?w=800&q=80&auto=format&fit=crop',
                'buds3-pro' => 'https://images.unsplash.com/photo-1631867675167-90a456a90863?w=800&q=80&auto=format&fit=crop',
                'sony-wf-xm5' => 'https://images.unsplash.com/photo-1606220588913-b3aacb4d2f46?w=800&q=80&auto=format&fit=crop',
                'nothing-ear-a' => 'https://images.unsplash.com/photo-1598331668826-20cecc596b86?w=800&q=80&auto=format&fit=crop',
                'jbl-wave-beam' => 'https://images.unsplash.com/photo-1572569511254-d8f925fe2cbb?w=800&q=80&auto=format&fit=crop',

                // Watches
                'apple-watch-s9' => 'https://images.unsplash.com/photo-1434493789847-2f02dc6ca35d?w=800&q=80&auto=format&fit=crop',
                'watch-s10' => 'https://images.unsplash.com/photo-1579586337278-3befd40fd17a?w=800&q=80&auto=format&fit=crop',
                'watch-se-2' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&q=80&auto=format&fit=crop',
                'galaxy-watch-ultra' => 'https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?w=800&q=80&auto=format&fit=crop',
                'xiaomi-watch-s3' => 'https://images.unsplash.com/photo-1617043786394-f977fa12eddf?w=800&q=80&auto=format&fit=crop',
                'oneplus-watch-2' => 'https://images.unsplash.com/photo-1551816230-ef5deaed4a26?w=800&q=80&auto=format&fit=crop',

                // Cameras
                'canon-eos-r10' => 'https://images.unsplash.com/photo-1502920917128-1aa500764cbd?w=800&q=80&auto=format&fit=crop',
                'sony-zv-e10' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=800&q=80&auto=format&fit=crop',
                'gopro-13' => 'https://images.unsplash.com/photo-1552168324-d612d77725e3?w=800&q=80&auto=format&fit=crop',
                'canon-g7x' => 'https://images.unsplash.com/photo-1606983340126-99ab4feaa64a?w=800&q=80&auto=format&fit=crop',
                'canon-r50' => 'https://images.unsplash.com/photo-1495707902641-75cac588d2e9?w=800&q=80&auto=format&fit=crop',

                // Gaming
                'ps5-dualsense' => 'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?w=800&q=80&auto=format&fit=crop',
                'ps5-slim' => 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&q=80&auto=format&fit=crop',
                'xbox-series-s' => 'https://images.unsplash.com/photo-1621259182978-fbf93132d53d?w=800&q=80&auto=format&fit=crop',
                'gpro-superlight' => 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=800&q=80&auto=format&fit=crop',
                'blackwidow-v4' => 'https://images.unsplash.com/photo-1541140532154-b024d705b90a?w=800&q=80&auto=format&fit=crop',
                'tuf-vg27aq' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=800&q=80&auto=format&fit=crop',

                // Speakers / chargers / accessories
                'jbl-flip-6' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=800&q=80&auto=format&fit=crop',
                'jbl-charge-5' => 'https://images.unsplash.com/photo-1545454675-3531b543be5d?w=800&q=80&auto=format&fit=crop',
                'sony-xg300' => 'https://images.unsplash.com/photo-1589003077984-894e133dabab?w=800&q=80&auto=format&fit=crop',
                'bose-flex' => 'https://images.unsplash.com/photo-1558089687-f282ffcbc126?w=800&q=80&auto=format&fit=crop',
                'homepod-mini' => 'https://images.unsplash.com/photo-1543512214-318c7553f230?w=800&q=80&auto=format&fit=crop',
                'anker-737' => 'https://images.unsplash.com/photo-1609091839311-d5365f9ff1c5?w=800&q=80&auto=format&fit=crop',
                'anker-20000' => 'https://images.unsplash.com/photo-1625948515291-69613efd103f?w=800&q=80&auto=format&fit=crop',
                'apple-20w' => 'https://images.unsplash.com/photo-1615526675159-e248c3021d3f?w=800&q=80&auto=format&fit=crop',
                'anker-cable-cl' => 'https://images.unsplash.com/photo-1583863788434-e58a36338a0a?w=800&q=80&auto=format&fit=crop',
                'spigen-magsafe-13pro' => 'https://images.unsplash.com/photo-1605236453806-6ff36851218e?w=800&q=80&auto=format&fit=crop',
            ],
            'barcodes' => [],
            'keywords' => [
                'iphone 13' => 'https://images.unsplash.com/photo-1510557880182-3d4d3cba35a5?w=800&q=80&auto=format&fit=crop',
                'iphone 16 pro' => 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=800&q=80&auto=format&fit=crop',
                'iphone 16' => 'https://images.unsplash.com/photo-1510557880182-3d4d3cba35a5?w=800&q=80&auto=format&fit=crop',
                'iphone' => 'https://images.unsplash.com/photo-1510557880182-3d4d3cba35a5?w=800&q=80&auto=format&fit=crop',
                'galaxy s23' => 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=800&q=80&auto=format&fit=crop',
                'galaxy s25' => 'https://images.unsplash.com/photo-1610945415295-d9bbf067e59c?w=800&q=80&auto=format&fit=crop',
                'macbook air' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800&q=80&auto=format&fit=crop',
                'macbook pro' => 'https://images.unsplash.com/photo-1511385348-a52b4a160dc2?w=800&q=80&auto=format&fit=crop',
                'macbook' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800&q=80&auto=format&fit=crop',
                'ipad pro' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=800&q=80&auto=format&fit=crop',
                'ipad' => 'https://images.unsplash.com/photo-1561154464-82e9adf32764?w=800&q=80&auto=format&fit=crop',
                'dualsense' => 'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?w=800&q=80&auto=format&fit=crop',
                'playstation' => 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&q=80&auto=format&fit=crop',
                'airpods' => 'https://images.unsplash.com/photo-1600294037681-c80b4cb5b434?w=800&q=80&auto=format&fit=crop',
                'watch' => 'https://images.unsplash.com/photo-1434493789847-2f02dc6ca35d?w=800&q=80&auto=format&fit=crop',
                'camera' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=800&q=80&auto=format&fit=crop',
                'canon' => 'https://images.unsplash.com/photo-1502920917128-1aa500764cbd?w=800&q=80&auto=format&fit=crop',
                'gopro' => 'https://images.unsplash.com/photo-1564466809058-bf4114d54340?w=800&q=80&auto=format&fit=crop',
                'speaker' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=800&q=80&auto=format&fit=crop',
                'flip 6' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=800&q=80&auto=format&fit=crop',
                'charger' => 'https://images.unsplash.com/photo-1583863788434-e58a36338a0a?w=800&q=80&auto=format&fit=crop',
                'case' => 'https://images.unsplash.com/photo-1605236453806-6ff36851218e?w=800&q=80&auto=format&fit=crop',
                'magsafe' => 'https://images.unsplash.com/photo-1605236453806-6ff36851218e?w=800&q=80&auto=format&fit=crop',
                'laptop' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800&q=80&auto=format&fit=crop',
                'headphone' => 'https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?w=800&q=80&auto=format&fit=crop',
                'earbuds' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=800&q=80&auto=format&fit=crop',
                'buds' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=800&q=80&auto=format&fit=crop',
                'monitor' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=800&q=80&auto=format&fit=crop',
                'mouse' => 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=800&q=80&auto=format&fit=crop',
                'keyboard' => 'https://images.unsplash.com/photo-1541140532154-b024d705b90a?w=800&q=80&auto=format&fit=crop',
            ],
            'categories' => [
                'smartphones' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800&q=80&auto=format&fit=crop',
                'laptops' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800&q=80&auto=format&fit=crop',
                'tablets' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=800&q=80&auto=format&fit=crop',
                'headphones' => 'https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?w=800&q=80&auto=format&fit=crop',
                'earbuds' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=800&q=80&auto=format&fit=crop',
                'smartwatches' => 'https://images.unsplash.com/photo-1434493789847-2f02dc6ca35d?w=800&q=80&auto=format&fit=crop',
                'cameras' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=800&q=80&auto=format&fit=crop',
                'gaming' => 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&q=80&auto=format&fit=crop',
                'speakers' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=800&q=80&auto=format&fit=crop',
                'chargers & cables' => 'https://images.unsplash.com/photo-1583863788434-e58a36338a0a?w=800&q=80&auto=format&fit=crop',
                'accessories' => 'https://images.unsplash.com/photo-1605236453806-6ff36851218e?w=800&q=80&auto=format&fit=crop',
                'monitors' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=800&q=80&auto=format&fit=crop',
            ],
            'fallback' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800&q=80&auto=format&fit=crop',
        ];
    }

    private function storeRemoteImage(string $url, string $directory, string $filename): ?string
    {
        $relative = trim($directory, '/').'/'.$filename;

        try {
            // Always re-download when repairing so we don't keep a wrong cached file under the same name.
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'ProductImageRepairSeeder/1.0'])
                ->get($url);

            if (! $response->successful() || strlen($response->body()) < 800) {
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
