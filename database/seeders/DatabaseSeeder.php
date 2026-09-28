<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Always: roles, shop, admin, minimal settings (safe for production).
        $this->call(ProductionSeeder::class);

        // Demo catalog / CMS only when allowed (local by default).
        if ($this->shouldSeedDemo()) {
            $this->call(DemoSeeder::class);
            $this->command?->warn('Demo data loaded. Set SEED_DEMO=false (or APP_ENV=production) for a clean DB.');
        } else {
            $this->command?->info('Skipped demo seeders — database left clean for production.');
        }
    }

    /**
     * Demo data rules:
     * - SEED_DEMO=true|false overrides everything
     * - Otherwise: seed demo in local / development / testing only
     */
    protected function shouldSeedDemo(): bool
    {
        $override = env('SEED_DEMO');

        if ($override !== null && $override !== '') {
            return filter_var($override, FILTER_VALIDATE_BOOLEAN);
        }

        return app()->environment(['local', 'development', 'testing']);
    }
}
