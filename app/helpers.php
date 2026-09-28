<?php

if (! function_exists('module_enabled')) {
    function module_enabled(string $module): bool
    {
        return (bool) config("modules.{$module}.enabled", false);
    }
}

if (! function_exists('retail_enabled')) {
    /** Legacy physical-retail module (POS, counters, BAKI, EMI, exchanges). */
    function retail_enabled(): bool
    {
        return module_enabled('retail');
    }
}

if (! function_exists('format_taka_number')) {
    /**
     * Round money to whole taka (no poysa / .00). Use for all currency display.
     * Percentages and other non-money figures should keep their own decimals.
     */
    function format_taka_number(mixed $amount): string
    {
        return number_format((int) round((float) ($amount ?? 0)), 0);
    }
}

if (! function_exists('format_taka')) {
    /**
     * Currency display in whole taka, e.g. ৳12,500
     */
    function format_taka(mixed $amount, ?string $symbol = '৳'): string
    {
        $symbol = $symbol !== null && $symbol !== '' ? $symbol : '৳';

        return $symbol.format_taka_number($amount);
    }
}

if (! function_exists('public_storage_path')) {
    /**
     * Absolute filesystem path for a public-disk relative path.
     * Honors PUBLIC_STORAGE_ROOT on hosts where public_html != Laravel public/.
     */
    function public_storage_path(?string $path = null): string
    {
        $root = \Illuminate\Support\Facades\Storage::disk('public')->path('');
        $root = rtrim(str_replace('\\', '/', $root), '/');

        if ($path === null || $path === '') {
            return $root;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return $root.'/'.$path;
    }
}

if (! function_exists('public_storage_url')) {
    /**
     * Public disk files live under storage/app/public and are served from {base}/storage/...
     * Uses the current request base path so subdirectory installs (XAMPP) work.
     */
    function public_storage_url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        try {
            $base = rtrim(request()->getBasePath(), '/');

            // Prefer path-only URLs so the browser always loads from the current host/port.
            return $base.'/storage/'.$path;
        } catch (\Throwable) {
            return '/storage/'.$path;
        }
    }
}

if (! function_exists('site_favicon_url')) {
    /**
     * Browser tab icon: custom favicon → store logo → branded default SVG.
     */
    function site_favicon_url(?object $settings = null): string
    {
        if ($settings === null) {
            try {
                $settings = \App\Models\SiteSetting::query()->first();
            } catch (\Throwable) {
                $settings = null;
            }
        }

        $favicon = data_get($settings, 'favicon_path');
        if (filled($favicon)) {
            return public_storage_url($favicon);
        }

        $logo = data_get($settings, 'logo_path');
        if (filled($logo)) {
            return public_storage_url($logo);
        }

        return asset('favicon.svg');
    }
}

if (! function_exists('app_timezone')) {
    function app_timezone(): string
    {
        return config('app.display_timezone', config('app.timezone', 'Asia/Dhaka'));
    }
}

if (! function_exists('asian_datetime')) {
    /**
     * Format a date/time in Asia/Dhaka (or APP_DISPLAY_TIMEZONE).
     */
    function asian_datetime($value = null, string $format = 'd M Y, h:i A'): string
    {
        if ($value === null || $value === '') {
            $value = now();
        }

        try {
            $dt = $value instanceof \Carbon\CarbonInterface
                ? $value->copy()
                : \Carbon\Carbon::parse($value);
        } catch (\Throwable $e) {
            return '';
        }

        return $dt->timezone(app_timezone())->format($format);
    }
}

if (! function_exists('asian_date')) {
    function asian_date($value = null, string $format = 'd M Y'): string
    {
        return asian_datetime($value, $format);
    }
}

if (! function_exists('normalize_memory_size')) {
    /**
     * Normalize RAM / storage labels: "8GB", "8gb", "8 GB" → "8 GB".
     */
    function normalize_memory_size(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^(\d+(?:\.\d+)?)\s*(tb|gb|mb|kb)$/i', $trimmed, $m)) {
            $number = $m[1];
            if (str_contains($number, '.')) {
                $number = rtrim(rtrim($number, '0'), '.');
            }

            return $number.' '.strtoupper($m[2]);
        }

        return $trimmed;
    }
}

if (! function_exists('memory_size_compact')) {
    /** Compact form for equality checks: "8 GB" → "8gb". */
    function memory_size_compact(?string $value): string
    {
        $normalized = normalize_memory_size($value);

        return $normalized ? strtolower(str_replace(' ', '', $normalized)) : '';
    }
}

if (! function_exists('memory_size_sort_key')) {
    /** Sort key in megabytes for numeric ordering. */
    function memory_size_sort_key(?string $value): float
    {
        $normalized = normalize_memory_size($value);
        if (! $normalized || ! preg_match('/^(\d+(?:\.\d+)?)\s*(TB|GB|MB|KB)$/', $normalized, $m)) {
            return PHP_FLOAT_MAX;
        }

        $n = (float) $m[1];

        return match ($m[2]) {
            'TB' => $n * 1024 * 1024,
            'GB' => $n * 1024,
            'MB' => $n,
            'KB' => $n / 1024,
            default => PHP_FLOAT_MAX,
        };
    }
}

if (! function_exists('unique_memory_sizes')) {
    /**
     * Deduplicate and sort memory labels (RAM / ROM).
     *
     * @param  iterable<int, mixed>  $values
     * @return \Illuminate\Support\Collection<int, string>
     */
    function unique_memory_sizes(iterable $values): \Illuminate\Support\Collection
    {
        return collect($values)
            ->map(fn ($v) => normalize_memory_size(is_string($v) ? $v : (string) $v))
            ->filter()
            ->unique(fn ($v) => memory_size_compact($v))
            ->sortBy(fn ($v) => memory_size_sort_key($v))
            ->values();
    }
}

if (! function_exists('normalize_map_embed_url')) {
    /**
     * Turn a Google Maps paste (full iframe HTML, share link, or embed src) into an iframe-ready URL.
     */
    function normalize_map_embed_url(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // Full <iframe ... src="..." ...></iframe> paste
        if (preg_match('/\bsrc\s*=\s*(["\'])(.*?)\1/i', $value, $m)) {
            $value = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } elseif (preg_match('/\bsrc\s*=\s*([^\s>]+)/i', $value, $m)) {
            $value = html_entity_decode(trim($m[1], "\"'"), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $value = trim($value, " \t\n\r\0\x0B\"'");
        if ($value === '') {
            return null;
        }

        // Protocol-relative URLs
        if (str_starts_with($value, '//')) {
            $value = 'https:'.$value;
        }

        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            return null;
        }

        $lower = strtolower($value);

        // Already a usable embed URL
        if (str_contains($lower, '/maps/embed') || str_contains($lower, 'output=embed')) {
            return $value;
        }

        $isGoogleMaps = str_contains($lower, 'google.') && str_contains($lower, '/maps')
            || str_contains($lower, 'maps.google.')
            || str_contains($lower, 'goo.gl/maps')
            || str_contains($lower, 'maps.app.goo.gl');

        if (! $isGoogleMaps) {
            // OpenStreetMap / other embed URLs — use as-is
            return $value;
        }

        // Coordinates from /@lat,lng,zoom
        if (preg_match('/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $value, $c)) {
            return 'https://maps.google.com/maps?q='.rawurlencode($c[1].','.$c[2]).'&z=15&output=embed';
        }

        // /place/Name/
        if (preg_match('#/place/([^/@]+)#', $value, $p)) {
            $place = urldecode(str_replace('+', ' ', $p[1]));
            $place = preg_replace('/\+/', ' ', $place) ?? $place;

            return 'https://maps.google.com/maps?q='.rawurlencode($place).'&output=embed';
        }

        // /search/Query/
        if (preg_match('#/search/([^/@?]+)#', $value, $s)) {
            $query = urldecode(str_replace('+', ' ', $s[1]));

            return 'https://maps.google.com/maps?q='.rawurlencode($query).'&output=embed';
        }

        $parts = parse_url($value);
        $query = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        if (! empty($query['q'])) {
            return 'https://maps.google.com/maps?q='.rawurlencode((string) $query['q']).'&output=embed';
        }
        if (! empty($query['query'])) {
            return 'https://maps.google.com/maps?q='.rawurlencode((string) $query['query']).'&output=embed';
        }

        // Last resort for google maps links
        $sep = str_contains($value, '?') ? '&' : '?';

        return $value.$sep.'output=embed';
    }
}

