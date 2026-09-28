<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\SiteFeature;
use Illuminate\Database\Seeder;

class SiteFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $shop = Shop::query()->orderBy('id')->first();
        if (! $shop) {
            return;
        }

        $features = [
            [
                'icon' => 'truck',
                'title' => 'Free Delivery',
                'subtitle' => 'Orders over ৳10,000',
                'sort_order' => 1,
            ],
            [
                'icon' => 'return',
                'title' => 'Easy Returns',
                'subtitle' => '30-day unused policy',
                'sort_order' => 2,
            ],
            [
                'icon' => 'lock',
                'title' => 'Cash on Delivery',
                'subtitle' => 'Pay when you unbox',
                'sort_order' => 3,
            ],
            [
                'icon' => 'shield',
                'title' => 'Genuine Warranty',
                'subtitle' => 'Sealed gadgets only',
                'sort_order' => 4,
            ],
        ];

        $keepTitles = [];

        foreach ($features as $feature) {
            $keepTitles[] = $feature['title'];

            SiteFeature::updateOrCreate(
                [
                    'shop_id' => $shop->id,
                    'title' => $feature['title'],
                ],
                [
                    'icon' => $feature['icon'],
                    'subtitle' => $feature['subtitle'],
                    'sort_order' => $feature['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        // Keep exactly these 4 homepage features active by default.
        SiteFeature::where('shop_id', $shop->id)
            ->whereNotIn('title', $keepTitles)
            ->update(['is_active' => false]);
    }
}
