<?php

namespace App\Services;

use App\Models\Brand;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class BrandLogoService
{
    /** @var array<string, string> */
    protected array $domains = [
        'apple' => 'apple.com',
        'samsung' => 'samsung.com',
        'sony' => 'sony.com',
        'xiaomi' => 'mi.com',
        'oneplus' => 'oneplus.com',
        'google' => 'google.com',
        'dell' => 'dell.com',
        'hp' => 'hp.com',
        'asus' => 'asus.com',
        'lenovo' => 'lenovo.com',
        'acer' => 'acer.com',
        'bose' => 'bose.com',
        'jbl' => 'jbl.com',
        'anker' => 'anker.com',
        'canon' => 'canon.com',
        'gopro' => 'gopro.com',
        'logitech' => 'logitech.com',
        'razer' => 'razer.com',
        'nothing' => 'nothing.tech',
        'microsoft' => 'microsoft.com',
        'spigen' => 'spigen.com',
    ];

    public function domainFor(string $name): string
    {
        $slug = Str::slug($name);

        return $this->domains[$slug] ?? ($slug.'.com');
    }

    /**
     * Store an uploaded brand logo: crop empty padding and fit for the Gadget Lovers strip.
     */
    public function storeUploaded(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        $mime = strtolower((string) $file->getMimeType());

        // Keep SVG as-is (vector stays crisp).
        if ($ext === 'svg' || str_contains($mime, 'svg')) {
            return $file->store('brands', 'public');
        }

        $tmp = $file->getRealPath();
        if (! $tmp || ! is_file($tmp)) {
            throw new RuntimeException('Uploaded brand logo could not be read.');
        }

        $filename = Str::uuid()->toString().'.png';
        $relative = 'brands/'.$filename;
        $absolute = Storage::disk('public')->path($relative);
        $dir = dirname($absolute);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        app(SiteLogoNormalizer::class)->normalizeToPng(
            $tmp,
            $absolute,
            square: false,
            padding: 10,
            threshold: 42,
            maxSize: 640,
        );

        return $relative;
    }

    public function resolveUrl(?Brand $brand): ?string
    {
        if (! $brand) {
            return null;
        }

        // Prefer the logo staff uploaded — never replace it with a generated mark.
        if ($brand->logo_path && $this->isUserLogo($brand->logo_path) && Storage::disk('public')->exists($brand->logo_path)) {
            return public_storage_url($brand->logo_path);
        }

        if ($brand->logo_path && Storage::disk('public')->exists($brand->logo_path)) {
            return public_storage_url($brand->logo_path);
        }

        $path = $this->writePremiumSvg($brand->name);
        if ($path && empty($brand->logo_path)) {
            try {
                $brand->forceFill(['logo_path' => $path])->saveQuietly();
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return $path ? public_storage_url($path) : null;
    }

    public function ensureStored(Brand $brand): ?string
    {
        if ($brand->logo_path && Storage::disk('public')->exists($brand->logo_path)) {
            return $brand->logo_path;
        }

        $path = $this->writePremiumSvg($brand->name);
        if ($path) {
            $brand->update(['logo_path' => $path]);
        }

        return $path;
    }

    public function download(string $name): ?string
    {
        return $this->writePremiumSvg($name);
    }

    public function writePremiumSvg(string $name): ?string
    {
        $slug = Str::slug($name) ?: 'brand';
        $path = 'brands/'.$slug.'.svg';

        if (Storage::disk('public')->exists($path) && Storage::disk('public')->size($path) > 80) {
            return $path;
        }

        $svg = $this->svgFor($slug, $name);
        Storage::disk('public')->put($path, $svg);

        return $path;
    }

    /** Uploaded files use UUID PNG / random store names — not slug.svg wordmarks. */
    protected function isUserLogo(string $path): bool
    {
        $base = basename($path);

        return ! str_ends_with(strtolower($base), '.svg')
            || preg_match('/^[0-9a-f-]{36}\./i', $base);
    }

    protected function svgFor(string $slug, string $name): string
    {
        $marks = $this->premiumMarks();
        if (isset($marks[$slug])) {
            return $marks[$slug];
        }

        $safe = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 180 40" role="img" aria-label="{$safe}">
  <text x="90" y="27" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="16" font-weight="800" fill="#0f172a">{$safe}</text>
</svg>
SVG;
    }

    /** @return array<string, string> */
    protected function premiumMarks(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $cache = [
            'apple' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 40" role="img"><path fill="#111111" d="M28.6 12.3c1.5-1.9 2.5-4.5 2.2-7.1-2.2.1-4.8 1.5-6.3 3.4-1.4 1.6-2.6 4.3-2.3 6.8 2.5.2 4.9-1.2 6.4-3.1zm5.9 1.5c-3.8-.2-7 2.2-8.8 2.2-1.8 0-4.6-2.1-7.6-2-3.9.1-7.5 2.3-9.5 5.8-4.1 7.1-1 17.6 2.9 23.4 1.9 2.8 4.2 6 7.2 5.9 2.9-.1 4-1.9 7.5-1.9s4.5 1.9 7.6 1.8c3.1-.1 5.1-2.9 7-5.7 2.2-3.2 3.1-6.3 3.1-6.5-.1 0-6-2.3-6.1-9.2-.1-5.7 4.7-8.5 4.9-8.6-2.7-4-6.9-4.4-8.2-4.5z"/></svg>',
            'samsung' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 40"><text x="100" y="27" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="20" font-weight="700" letter-spacing="2.2" fill="#1428A0">SAMSUNG</text></svg>',
            'sony' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 140 40"><text x="70" y="27" text-anchor="middle" font-family="Georgia,serif" font-size="22" font-weight="700" letter-spacing="6" fill="#111">SONY</text></svg>',
            'dell' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 90 40"><circle cx="45" cy="20" r="15" fill="none" stroke="#0076CE" stroke-width="2.5"/><text x="45" y="25.5" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="13" font-weight="800" font-style="italic" fill="#0076CE">Dell</text></svg>',
            'jbl' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 40"><text x="50" y="29" text-anchor="middle" font-family="Arial Black,Arial,sans-serif" font-size="26" font-weight="900" fill="#FF6600">JBL</text></svg>',
            'oneplus' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 180 40"><rect x="8" y="8" width="24" height="24" rx="4" fill="#EB0029"/><text x="20" y="26" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="14" font-weight="800" fill="#fff">1+</text><text x="110" y="27" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="16" font-weight="700" fill="#EB0029">OnePlus</text></svg>',
            'anker' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 140 40"><text x="70" y="28" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="22" font-weight="800" fill="#00A9E0">Anker</text></svg>',
            'canon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 150 40"><text x="75" y="28" text-anchor="middle" font-family="Georgia,serif" font-size="24" font-weight="700" font-style="italic" fill="#C8102E">Canon</text></svg>',
            'hp' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 40"><circle cx="40" cy="20" r="16" fill="#0096D6"/><text x="40" y="26" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="16" font-weight="700" fill="#fff">hp</text></svg>',
            'spigen' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 170 40"><g fill="#111"><rect x="8" y="10" width="14" height="2.2" rx="1"/><rect x="8" y="16" width="14" height="2.2" rx="1"/><rect x="8" y="22" width="14" height="2.2" rx="1"/><rect x="8" y="28" width="14" height="2.2" rx="1"/></g><text x="100" y="27" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="18" font-weight="700" letter-spacing="2" fill="#111">SPIGEN</text></svg>',
            'asus' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 140 40"><text x="70" y="28" text-anchor="middle" font-family="Arial Black,Arial,sans-serif" font-size="24" font-weight="900" letter-spacing="3" fill="#00539B">ASUS</text></svg>',
            'google' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 40"><text x="18" y="28" font-family="Arial,Helvetica,sans-serif" font-size="24" font-weight="700" fill="#4285F4">G</text><text x="38" y="28" font-family="Arial,Helvetica,sans-serif" font-size="24" font-weight="700" fill="#EA4335">o</text><text x="56" y="28" font-family="Arial,Helvetica,sans-serif" font-size="24" font-weight="700" fill="#FBBC05">o</text><text x="74" y="28" font-family="Arial,Helvetica,sans-serif" font-size="24" font-weight="700" fill="#4285F4">g</text><text x="92" y="28" font-family="Arial,Helvetica,sans-serif" font-size="24" font-weight="700" fill="#34A853">l</text><text x="104" y="28" font-family="Arial,Helvetica,sans-serif" font-size="24" font-weight="700" fill="#EA4335">e</text></svg>',
            'lenovo' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 150 40"><rect x="10" y="6" width="130" height="28" rx="3" fill="#E2231A"/><text x="75" y="26" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="16" font-weight="800" fill="#fff">Lenovo</text></svg>',
            'xiaomi' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 40"><rect x="20" y="4" width="40" height="32" rx="8" fill="#FF6900"/><text x="40" y="26" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="16" font-weight="800" fill="#fff">mi</text></svg>',
            'bose' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 140 40"><text x="70" y="28" text-anchor="middle" font-family="Arial Black,Arial,sans-serif" font-size="22" font-weight="900" font-style="italic" fill="#111">BOSE</text></svg>',
            'gopro' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 170 40"><text x="8" y="27" font-family="Arial,Helvetica,sans-serif" font-size="18" font-weight="800" fill="#111">GoPro</text><rect x="90" y="12" width="12" height="12" rx="1.5" fill="#00BBEF"/><rect x="106" y="12" width="12" height="12" rx="1.5" fill="#0088C8"/><rect x="122" y="12" width="12" height="12" rx="1.5" fill="#005A8C"/><rect x="138" y="12" width="12" height="12" rx="1.5" fill="#003F66"/></svg>',
            'logitech' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 170 40"><text x="85" y="27" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="18" font-weight="700" fill="#111">logitech</text></svg>',
            'razer' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 40"><path fill="#44D62C" d="M14 8l8 12-8 12h6l8-12-8-12h-6zm12 0l8 12-8 12h6l8-12-8-12h-6z"/><text x="105" y="27" text-anchor="middle" font-family="Arial Black,Arial,sans-serif" font-size="18" font-weight="900" letter-spacing="2" fill="#44D62C">RAZER</text></svg>',
            'nothing' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 180 40"><text x="90" y="27" text-anchor="middle" font-family="Courier New,monospace" font-size="16" font-weight="500" letter-spacing="3" fill="#111">nothing</text></svg>',
            'microsoft' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 190 40"><rect x="8" y="8" width="10" height="10" fill="#F25022"/><rect x="20" y="8" width="10" height="10" fill="#7FBA00"/><rect x="8" y="20" width="10" height="10" fill="#00A4EF"/><rect x="20" y="20" width="10" height="10" fill="#FFB900"/><text x="115" y="26" text-anchor="middle" font-family="Segoe UI,Arial,sans-serif" font-size="16" font-weight="600" fill="#737373">Microsoft</text></svg>',
            'acer' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 40"><text x="60" y="28" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="22" font-weight="800" fill="#83B81A">acer</text></svg>',
        ];

        return $cache;
    }
}
