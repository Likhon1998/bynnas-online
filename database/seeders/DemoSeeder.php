<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Local / staging demo content: CMS media, general product catalog, blogs, reviews, legal pages.
 * Never run on a clean production database unless you intentionally want sample data.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            WebsiteSeeder::class,
            GeneralCatalogSeeder::class,
            BlogSeeder::class,
            DemoStorefrontSeeder::class,
        ]);

        $this->command?->info('Demo storefront + catalog seeded for local development.');
    }
}
