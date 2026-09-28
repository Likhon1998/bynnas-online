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
                'title' => 'Flagship Phone Deals',
                'subtitle' => 'Samsung, iPhone & more — authentic & sealed',
                'badge_text' => 'PHONES',
                'highlight_text' => 'authentic',
                'discount_badge' => 'HOT',
                'price_from' => 24990,
                'theme' => 'dark',
                'button_text' => 'Shop phones',
                'button_url' => '/shop?search=phone',
                'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=1200&q=80',
                'file' => 'midpromo-phones.jpg',
            ],
            [
                'sort_order' => 2,
                'title' => 'Laptop Work Station',
                'subtitle' => 'MacBook, Dell & HP for study and office',
                'badge_text' => 'LAPTOPS',
                'highlight_text' => 'office',
                'discount_badge' => 'NEW',
                'price_from' => 54990,
                'theme' => 'light',
                'button_text' => 'Browse laptops',
                'button_url' => '/shop?search=laptop',
                'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=1200&q=80',
                'file' => 'midpromo-laptops.jpg',
            ],
            [
                'sort_order' => 3,
                'title' => 'Audio That Moves You',
                'subtitle' => 'Earbuds, headphones & speakers in stock',
                'badge_text' => 'AUDIO',
                'highlight_text' => 'in stock',
                'discount_badge' => 'SAVE',
                'price_from' => 1990,
                'theme' => 'dark',
                'button_text' => 'Shop audio',
                'button_url' => '/shop?search=headphone',
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
                ->withHeaders(['User-Agent' => 'MaksGadgetMidPromoSeeder/1.0'])
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
