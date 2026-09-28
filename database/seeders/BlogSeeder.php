<?php

namespace Database\Seeders;

use App\Models\CmsBlog;
use App\Models\CmsBlogCategory;
use App\Models\Shop;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $shop = Shop::query()->orderBy('id')->first();
        if (! $shop) {
            return;
        }

        $settings = SiteSetting::query()->first();
        if (! $settings) {
            $settings = SiteSetting::create([
                'default_shop_id' => $shop->id,
                'store_name' => 'Maks Gadget',
                'currency_code' => 'BDT',
                'currency_symbol' => '৳',
            ]);
        }

        $heroPath = $this->storeRemoteImage(
            'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=1600&q=80',
            'cms/blog-hero',
            'hero-gadgets.jpg'
        );

        $settings->fill([
            'default_shop_id' => $settings->default_shop_id ?: $shop->id,
            'blog_hero_kicker' => 'OUR BLOG',
            'blog_hero_title' => 'News & Articles',
            'blog_hero_subtitle' => 'Stay updated with the latest tech news, product reviews, and buying guides from Maks Gadget.',
            'blog_articles_title' => 'Latest Articles',
            'blog_newsletter_title' => 'Subscribe to Our Newsletter',
            'blog_newsletter_text' => 'Get the latest deals and tech news delivered to your inbox.',
            'blog_feature_1_title' => 'Expert Reviews',
            'blog_feature_1_text' => 'In-depth & honest',
            'blog_feature_2_title' => 'Buying Guides',
            'blog_feature_2_text' => 'Smart picks for you',
            'blog_feature_3_title' => 'Latest Updates',
            'blog_feature_3_text' => 'Tech news, trends & more',
        ]);
        if ($heroPath) {
            $settings->blog_hero_image = $heroPath;
        }
        $settings->save();

        $categoryDefs = [
            'product-reviews' => ['name' => 'Product Reviews', 'color' => 'blue', 'sort_order' => 1],
            'tech-news' => ['name' => 'Tech News', 'color' => 'emerald', 'sort_order' => 2],
            'buying-guides' => ['name' => 'Buying Guides', 'color' => 'violet', 'sort_order' => 3],
            'how-to' => ['name' => 'How-To', 'color' => 'amber', 'sort_order' => 4],
        ];

        $categories = [];
        foreach ($categoryDefs as $slug => $def) {
            $categories[$slug] = CmsBlogCategory::updateOrCreate(
                ['shop_id' => $shop->id, 'slug' => $slug],
                [
                    'name' => $def['name'],
                    'color' => $def['color'],
                    'sort_order' => $def['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        $posts = [
            [
                'title' => 'Sony WH-1000XM5 Review: Still the King of Noise Cancellation?',
                'slug' => 'sony-wh-1000xm5-review',
                'category' => 'product-reviews',
                'excerpt' => "We put Sony's flagship headphones through daily commuting, flights, and long work sessions to see if they still lead the ANC pack.",
                'body' => "Sony's WH-1000XM5 continues to set the bar for wireless noise cancelling headphones. Comfort is excellent for long sessions, call quality is clear, and the app gives fine control over EQ and ambient modes.\n\nBattery life easily covers a full work week of listening, and multipoint pairing makes switching between laptop and phone painless. If you want class-leading ANC without stepping into ultra-premium pricing, these still deserve the crown.",
                'author_name' => 'Alex Rivera',
                'reading_time' => 5,
                'views_count' => 1840,
                'is_featured' => true,
                'published_at' => '2025-05-24 10:00:00',
                'image' => 'https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?w=1200&q=80',
                'file' => 'sony-headphones.jpg',
            ],
            [
                'title' => 'Best Smartwatches in 2025: Which One Should You Buy?',
                'slug' => 'best-smartwatches-2025',
                'category' => 'buying-guides',
                'excerpt' => 'From Apple Watch to Galaxy Watch and budget fitness bands — here is a clear buying guide for every lifestyle and budget.',
                'body' => "Choosing a smartwatch in 2025 depends less on specs sheets and more on the phone you already own, the sports you track, and how much battery you need between charges.\n\nApple Watch Ultra remains the premium pick for iPhone users. Galaxy Watch Ultra leads on Android with deep Samsung Health insights. If you mainly want sleep and workout tracking without notifications overload, a focused fitness watch will serve you better than a mini smartphone on your wrist.",
                'author_name' => 'Maya Chen',
                'reading_time' => 7,
                'views_count' => 2210,
                'is_featured' => false,
                'published_at' => '2025-05-20 09:00:00',
                'image' => 'https://images.unsplash.com/photo-1579586337278-3befd40fd17a?w=1200&q=80',
                'file' => 'smartwatches.jpg',
            ],
            [
                'title' => 'iPhone 16 Pro Max vs Galaxy S25 Ultra: Flagship Showdown',
                'slug' => 'iphone-16-pro-max-vs-galaxy-s25-ultra',
                'category' => 'tech-news',
                'excerpt' => 'Camera systems, battery stamina, and everyday performance — we compare the two phones everyone is debating this year.',
                'body' => "Both flagships are outstanding, but they feel different day to day. The iPhone 16 Pro Max prioritizes video, polish, and long software support. The Galaxy S25 Ultra leans into versatility with its S Pen, zoom camera, and deeper customization.\n\nIf you live in the Apple ecosystem, the Pro Max is the safer buy. Android power users who edit photos, multitask heavily, and want stylus precision will likely prefer the Ultra.",
                'author_name' => 'Jordan Lee',
                'reading_time' => 8,
                'views_count' => 3120,
                'is_featured' => true,
                'published_at' => '2025-05-18 11:30:00',
                'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=1200&q=80',
                'file' => 'flagship-phones.jpg',
            ],
            [
                'title' => 'How to Clean Wireless Earbuds Without Damaging Them',
                'slug' => 'how-to-clean-wireless-earbuds',
                'category' => 'how-to',
                'excerpt' => 'A simple routine that keeps tips, meshes, and charging contacts free of wax and dust — without voiding your warranty.',
                'body' => "Turn earbuds off and remove silicone tips. Wipe the housing with a dry microfiber cloth, then gently brush speaker meshes with a soft, dry brush. Never rinse earbuds under water unless they are rated for it and the manufacturer allows it.\n\nFor the case, clear lint from the charging pins with a wooden toothpick and wipe the interior lightly. Clean tips with mild soapy water, dry completely, then reattach. Do this every few weeks and sound quality stays crisp.",
                'author_name' => 'Sam Okonkwo',
                'reading_time' => 4,
                'views_count' => 980,
                'is_featured' => false,
                'published_at' => '2025-05-15 14:00:00',
                'image' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=1200&q=80',
                'file' => 'earbuds.jpg',
            ],
            [
                'title' => 'Build a Mid-Range Gaming PC That Still Looks Premium',
                'slug' => 'mid-range-gaming-pc-setup',
                'category' => 'tech-news',
                'excerpt' => 'Balanced parts, clean cable management, and RGB that does not overwhelm — a setup guide for 1440p gaming without overspending.',
                'body' => "Start with a modern mid-tier GPU and a CPU that will not bottleneck it. Pair 32GB of fast RAM with a 1TB NVMe SSD for games and a secondary drive for media.\n\nSpend on a case with strong airflow and a quality PSU — those parts age better than chasing every new GPU launch. Keep cable routes tidy, leave room for intake filters, and your rig will stay cool, quiet, and photo-ready.",
                'author_name' => 'Chris Patel',
                'reading_time' => 6,
                'views_count' => 1560,
                'is_featured' => false,
                'published_at' => '2025-05-12 16:00:00',
                'image' => 'https://images.unsplash.com/photo-1587202372775-e229f172b9d7?w=1200&q=80',
                'file' => 'gaming-pc.jpg',
            ],
            [
                'title' => 'Ultrabooks vs Gaming Laptops: Which Travel Machine Wins?',
                'slug' => 'ultrabooks-vs-gaming-laptops',
                'category' => 'buying-guides',
                'excerpt' => 'Weight, battery, thermals, and real-world performance — how to pick the right laptop for work trips and weekend creative work.',
                'body' => "Ultrabooks win on battery life, thin chassis, and quiet fan profiles for cafes and flights. Gaming laptops win when you need GPU horsepower for editing, 3D, or AAA titles — at the cost of bulk and shorter unplugged time.\n\nIf you mostly browse, write, and join video calls, an ultrabook is the better daily companion. Creators who export video or run local AI tools should look at thin gaming or creator laptops with discrete graphics and a strong charger.",
                'author_name' => 'Elena Brooks',
                'reading_time' => 6,
                'views_count' => 1340,
                'is_featured' => false,
                'published_at' => '2025-05-10 08:30:00',
                'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=1200&q=80',
                'file' => 'laptop-travel.jpg',
            ],
        ];

        foreach ($posts as $post) {
            $cover = $this->storeRemoteImage($post['image'], 'cms/blogs', $post['file']);

            CmsBlog::updateOrCreate(
                ['shop_id' => $shop->id, 'slug' => $post['slug']],
                [
                    'category_id' => $categories[$post['category']]->id,
                    'title' => $post['title'],
                    'excerpt' => $post['excerpt'],
                    'body' => $post['body'],
                    'cover_image' => $cover,
                    'author_name' => $post['author_name'],
                    'is_published' => true,
                    'is_featured' => $post['is_featured'],
                    'views_count' => $post['views_count'],
                    'reading_time' => $post['reading_time'],
                    'published_at' => $post['published_at'],
                ]
            );
        }
    }

    private function storeRemoteImage(string $url, string $directory, string $filename): ?string
    {
        $relative = trim($directory, '/').'/'.$filename;

        try {
            if (Storage::disk('public')->exists($relative)) {
                return $relative;
            }

            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'IntegratedCommerceBlogSeeder/1.0'])
                ->get($url);

            if (! $response->successful()) {
                $this->command?->warn("Failed to download image: {$url}");

                return null;
            }

            Storage::disk('public')->put($relative, $response->body());

            return $relative;
        } catch (\Throwable $e) {
            $this->command?->warn('Image download error: '.$e->getMessage());

            return null;
        }
    }
}
