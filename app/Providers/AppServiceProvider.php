<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Never use Vite HMR on deployed hosts. A leftover public/hot file
        // (from local `npm run dev`) points at [::1]:5173 and breaks all CSS/JS.
        if (! $this->app->environment('local')) {
            Vite::useHotFile(storage_path('framework/vite-hot-disabled'));
        }
    }
}
