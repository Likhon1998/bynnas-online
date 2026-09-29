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

        $u = fn (string $id) => "https://images.unsplash.com/{$id}?w=1920&h=640&fit=crop&q=80";
        $banners = [
            [
                'title' => 'Style That Gets Shared',
                'badge_text' => 'BYNNAS SOCIAL',
                'description' => 'Trending fashion, beauty and home picks. Order in one tap, pay on delivery.',
                'price_from' => null,
                'button_text' => 'Shop Now',
                'button_url' => '/shop',
                'learn_more_text' => 'New Arrivals',
                'learn_more_url' => '/shop?filter=new',
                'sort_order' => 1,
                'file' => 'banner-social-store.jpg',
                'remote' => $u('photo-1441986300917-64674bd600d8'),
            ],
            [
                'title' => 'New Season Fashion',
                'badge_text' => 'NEW ARRIVALS',
                'description' => 'Everyday tees, denim and jackets in every size.',
                'price_from' => 650,
                'button_text' => 'Shop Fashion',
                'button_url' => '/category/fashion',
                'learn_more_text' => 'View Deals',
                'learn_more_url' => '/shop?filter=deals',
                'sort_order' => 2,
                'file' => 'banner-fashion.jpg',
                'remote' => $u('photo-1445205170230-053b83016050'),
            ],
            [
                'title' => 'Beauty Essentials',
                'badge_text' => 'GLOW UP',
                'description' => 'Lipsticks, lotions and makeup kits loved by our community.',
                'price_from' => 450,
                'button_text' => 'Shop Beauty',
                'button_url' => '/category/cosmetics-beauty',
                'learn_more_text' => 'Learn More',
                'learn_more_url' => '/shop?filter=bestsellers',
                'sort_order' => 3,
                'file' => 'banner-beauty.jpg',
                'remote' => $u('photo-1596462502278-27bfdc403348'),
            ],
            [
                'title' => 'Home & Kitchen Refresh',
                'badge_text' => 'HOME',
                'description' => 'Cookware, mugs and bedding that make a house feel like home.',
                'price_from' => 390,
                'button_text' => 'Shop Home',
                'button_url' => '/category/home-kitchen',
                'learn_more_text' => 'Learn More',
                'learn_more_url' => '/shop',
                'sort_order' => 4,
                'file' => 'banner-home.jpg',
                'remote' => $u('photo-1556909114-f6e7ad7d3136'),
            ],
            [
                'title' => 'Toys Kids Love',
                'badge_text' => 'KIDS',
                'description' => 'Building blocks, plush friends and playtime favourites.',
                'price_from' => 690,
                'button_text' => 'Shop Toys',
                'button_url' => '/category/toys-kids',
                'learn_more_text' => 'Learn More',
                'learn_more_url' => '/shop?filter=deals',
                'sort_order' => 5,
                'file' => 'banner-toys.jpg',
                'remote' => $u('photo-1558060370-d644479cb6f7'),
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
                    ->withHeaders(['User-Agent' => 'BynnasSocialHeroSlideSeeder/1.0'])
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
