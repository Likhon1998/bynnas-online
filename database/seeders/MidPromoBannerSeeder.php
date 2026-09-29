<?php

namespace Database\Seeders;

use App\Models\PromoBanner;
use App\Models\Shop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Seeds 3 continuous mid-promo banners (Flash Sale → New Arrivals).
 * Safe to re-run: updates the 3 default slots without wiping other banners.
 */
class MidPromoBannerSeeder extends Seeder
{
    public function run(): void
    {
        $shop = Shop::query()->orderBy('id')->first();
        if (! $shop) {
            $this->command?->warn('MidPromoBannerSeeder: no shop found — skipped.');

            return;
        }

        $banners = [
            [
                'sort_order' => 1,
                'title' => 'Denim Days',
                'subtitle' => 'Slim fit jeans and jackets for every day',
                'badge_text' => 'FASHION',
                'highlight_text' => 'every day',
                'discount_badge' => 'HOT',
                'price_from' => 1890,
                'theme' => 'dark',
                'button_text' => 'Shop fashion',
                'button_url' => '/category/fashion',
                'image' => 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=1200&q=80',
                'file' => 'midpromo-denim.jpg',
            ],
            [
                'sort_order' => 2,
                'title' => 'Glow Kit Deals',
                'subtitle' => 'Lipsticks, lotions and makeup kits',
                'badge_text' => 'BEAUTY',
                'highlight_text' => 'makeup',
                'discount_badge' => 'NEW',
                'price_from' => 450,
                'theme' => 'light',
                'button_text' => 'Shop beauty',
                'button_url' => '/category/cosmetics-beauty',
                'image' => 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=1200&q=80',
                'file' => 'midpromo-beauty.jpg',
            ],
            [
                'sort_order' => 3,
                'title' => 'Sound On',
                'subtitle' => 'Wireless earbuds and headphones in stock',
                'badge_text' => 'AUDIO',
                'highlight_text' => 'in stock',
                'discount_badge' => 'SAVE',
                'price_from' => 1490,
                'theme' => 'dark',
                'button_text' => 'Shop electronics',
                'button_url' => '/category/electronics',
                'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=1200&q=80',
                'file' => 'midpromo-audio.jpg',
            ],
        ];

        foreach ($banners as $row) {
            $imagePath = $this->storeRemoteImage($row['image'], 'cms/promos', $row['file']);
            unset($row['image'], $row['file']);

            PromoBanner::updateOrCreate(
                [
                    'shop_id' => $shop->id,
                    'placement' => 'mid_promo',
                    'sort_order' => $row['sort_order'],
                ],
                array_merge($row, [
                    'image_path' => $imagePath,
                    'is_active' => true,
                ])
            );
        }

        $this->command?->info('Mid promo: 3 continuous banners ready (placement=mid_promo).');
    }

    private function storeRemoteImage(string $url, string $directory, string $filename): ?string
    {
        $relative = trim($directory, '/').'/'.$filename;

        try {
            if (Storage::disk('public')->exists($relative) && Storage::disk('public')->size($relative) > 1000) {
                return $relative;
            }

            $response = Http::timeout(25)
                ->withHeaders(['User-Agent' => 'BynnasSocialMidPromoSeeder/1.0'])
                ->get($url);

            if (! $response->successful() || strlen($response->body()) < 500) {
                return Storage::disk('public')->exists($relative) ? $relative : null;
            }

            Storage::disk('public')->put($relative, $response->body());

            return $relative;
        } catch (\Throwable $e) {
            return Storage::disk('public')->exists($relative) ? $relative : null;
        }
    }
}
