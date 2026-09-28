<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use App\Models\Shop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class HeroSlideSeeder extends Seeder
{
    public function run(): void
    {
        $shop = Shop::query()->orderBy('id')->first();
        if (! $shop) {
            return;
        }

        $banners = [
            [
                'title' => 'Upgrade Your Digital Life',
                'badge_text' => 'MAKS GADGET',
                'description' => 'Phones, laptops, audio & more — sealed and ready to ship.',
                'price_from' => null,
                'button_text' => 'Shop Gadgets',
                'button_url' => '/shop',
                'learn_more_text' => 'Learn More',
                'learn_more_url' => '/shop',
                'sort_order' => 1,
                'file' => 'banner-smarthome.jpg',
                'remote' => 'https://images.unsplash.com/photo-1558089687-f282ffcbc126?w=1920&h=640&fit=crop&q=80',
            ],
            [
                'title' => 'iPhone 16 Pro Max',
                'badge_text' => 'FLAGSHIP',
                'description' => 'Titanium. Pro camera. All-day battery.',
                'price_from' => 139900,
                'button_text' => 'Shop iPhone',
                'button_url' => '/shop?search=iphone',
                'learn_more_text' => 'Learn More',
                'learn_more_url' => '/shop?filter=new',
                'sort_order' => 2,
                'file' => 'banner-iphone.jpg',
                'remote' => 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=1920&h=640&fit=crop&q=80',
            ],
            [
                'title' => 'MacBook Air M3',
                'badge_text' => 'BEST SELLER',
                'description' => 'Impressively thin. Supercharged for work & play.',
                'price_from' => 119900,
                'button_text' => 'Shop Laptops',
                'button_url' => '/shop?search=macbook',
                'learn_more_text' => 'Learn More',
                'learn_more_url' => '/shop',
                'sort_order' => 3,
                'file' => 'banner-macbook.jpg',
                'remote' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=1920&h=640&fit=crop&q=80',
            ],
            [
                'title' => 'Galaxy Watch Ultra',
                'badge_text' => 'WEARABLES',
                'description' => 'Adventure-ready tracking with premium battery.',
                'price_from' => 59900,
                'button_text' => 'Shop Watches',
                'button_url' => '/shop?search=watch',
                'learn_more_text' => 'Learn More',
                'learn_more_url' => '/shop?filter=deals',
                'sort_order' => 4,
                'file' => 'banner-watch.jpg',
                'remote' => 'https://images.unsplash.com/photo-1434493789847-2f02dc6ca35d?w=1920&h=640&fit=crop&q=80',
            ],
            [
                'title' => 'Premium Audio Sale',
                'badge_text' => 'UP TO 22% OFF',
                'description' => 'Sony, Bose & JBL headphones — live deal prices in shop.',
                'price_from' => 5900,
                'button_text' => 'Shop Audio Deals',
                'button_url' => '/shop?filter=deals',
                'learn_more_text' => 'Learn More',
                'learn_more_url' => '/shop',
                'sort_order' => 5,
                'file' => 'banner-audio.jpg',
                'remote' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=1920&h=640&fit=crop&q=80',
            ],
        ];

        $keepTitles = [];

        foreach ($banners as $banner) {
            $keepTitles[] = $banner['title'];
            $imagePath = $this->storeSeedImage($banner['file'], $banner['remote'] ?? null);

            HeroSlide::updateOrCreate(
                [
                    'shop_id' => $shop->id,
                    'title' => $banner['title'],
                ],
                [
                    'badge_text' => $banner['badge_text'],
                    'description' => $banner['description'],
                    'price_from' => $banner['price_from'],
                    'image_path' => $imagePath,
                    'button_text' => $banner['button_text'],
                    'button_url' => $banner['button_url'],
                    'learn_more_text' => $banner['learn_more_text'],
                    'learn_more_url' => $banner['learn_more_url'],
                    'sort_order' => $banner['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        // Keep carousel at these 5 designed posters.
        HeroSlide::where('shop_id', $shop->id)
            ->whereNotIn('title', $keepTitles)
            ->delete();
    }

    /** Prefer local 1920×640 posters; fall back to a remote image for demos. */
    private function storeSeedImage(string $filename, ?string $remoteUrl = null): ?string
    {
        $relative = 'cms/slides/'.$filename;
        $source = database_path('seeders/assets/slides/'.$filename);

        if (File::exists($source)) {
            Storage::disk('public')->put($relative, File::get($source));

            return $relative;
        }

        if (Storage::disk('public')->exists($relative) && Storage::disk('public')->size($relative) > 1000) {
            return $relative;
        }

        if ($remoteUrl) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders(['User-Agent' => 'MaksGadgetHeroSlideSeeder/1.0'])
                    ->get($remoteUrl);

                if ($response->successful() && strlen($response->body()) > 500) {
                    Storage::disk('public')->put($relative, $response->body());

                    return $relative;
                }
            } catch (\Throwable $e) {
                $this->command?->warn('Hero image download failed: '.$e->getMessage());
            }
        }

        $this->command?->warn("Missing banner asset: {$source}");

        return Storage::disk('public')->exists($relative) ? $relative : null;
    }
}
