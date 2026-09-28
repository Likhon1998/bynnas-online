<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SiteLogoNormalizer
{
    /**
     * Store an uploaded logo/favicon as a cropped transparent PNG.
     *
     * @param  'logo'|'favicon'  $kind
     */
    public function storeProcessed(UploadedFile $file, string $kind = 'logo'): string
    {
        $folder = $kind === 'favicon' ? 'cms/favicon' : 'cms/logo';
        $tmp = $file->getRealPath();
        if (! $tmp || ! is_file($tmp)) {
            throw new RuntimeException('Uploaded image could not be read.');
        }

        $filename = Str::uuid()->toString().'.png';
        $relative = $folder.'/'.$filename;
        $absolute = Storage::disk('public')->path($relative);

        $dir = dirname($absolute);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $this->normalizeToPng(
            $tmp,
            $absolute,
            square: $kind === 'favicon',
            padding: $kind === 'favicon' ? 6 : 12,
            threshold: 42,
            maxSize: $kind === 'favicon' ? 512 : 1000,
        );

        return $relative;
    }

    public function normalizeToPng(
        string $sourcePath,
        string $destPath,
        bool $square = false,
        int $padding = 12,
        int $threshold = 42,
        int $maxSize = 512,
    ): void {
        if (! function_exists('imagecreatetruecolor')) {
            if (! @copy($sourcePath, $destPath)) {
                throw new RuntimeException('GD is not available and logo copy failed.');
            }

            return;
        }

        $data = @file_get_contents($sourcePath);
        if ($data === false) {
            throw new RuntimeException('Unable to read uploaded logo.');
        }

        $im = @imagecreatefromstring($data);
        if (! $im instanceof \GdImage) {
            throw new RuntimeException('Unsupported logo image format.');
        }

        try {
            $w = imagesx($im);
            $h = imagesy($im);
            $minX = $w;
            $minY = $h;
            $maxX = -1;
            $maxY = -1;

            for ($y = 0; $y < $h; $y++) {
                for ($x = 0; $x < $w; $x++) {
                    $rgb = imagecolorat($im, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    // Ignore near-black/near-white empty canvas when finding content.
                    if ($this->isCanvasPixel($r, $g, $b, $threshold)) {
                        continue;
                    }
                    $minX = min($minX, $x);
                    $minY = min($minY, $y);
                    $maxX = max($maxX, $x);
                    $maxY = max($maxY, $y);
                }
            }

            if ($maxX < 0) {
                throw new RuntimeException('No logo content found in the image.');
            }

            $minX = max(0, $minX - $padding);
            $minY = max(0, $minY - $padding);
            $maxX = min($w - 1, $maxX + $padding);
            $maxY = min($h - 1, $maxY + $padding);
            $cw = $maxX - $minX + 1;
            $ch = $maxY - $minY + 1;

            $outW = $square ? max($cw, $ch) : $cw;
            $outH = $square ? $outW : $ch;
            $ox = (int) (($outW - $cw) / 2);
            $oy = (int) (($outH - $ch) / 2);

            $out = imagecreatetruecolor($outW, $outH);
            imagealphablending($out, false);
            imagesavealpha($out, true);
            $transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
            imagefilledrectangle($out, 0, 0, $outW, $outH, $transparent);

            for ($y = 0; $y < $ch; $y++) {
                for ($x = 0; $x < $cw; $x++) {
                    $rgb = imagecolorat($im, $minX + $x, $minY + $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;

                    if ($this->isCanvasPixel($r, $g, $b, $threshold)) {
                        // Fully transparent for empty canvas.
                        imagesetpixel($out, $ox + $x, $oy + $y, $transparent);
                        continue;
                    }

                    $col = imagecolorallocatealpha($out, $r, $g, $b, 0);
                    imagesetpixel($out, $ox + $x, $oy + $y, $col);
                }
            }

            if ($outW > $maxSize || $outH > $maxSize) {
                $scale = min($maxSize / $outW, $maxSize / $outH);
                $nw = max(1, (int) round($outW * $scale));
                $nh = max(1, (int) round($outH * $scale));
                $scaled = imagecreatetruecolor($nw, $nh);
                imagealphablending($scaled, false);
                imagesavealpha($scaled, true);
                $t = imagecolorallocatealpha($scaled, 0, 0, 0, 127);
                imagefilledrectangle($scaled, 0, 0, $nw, $nh, $t);
                imagealphablending($scaled, true);
                imagecopyresampled($scaled, $out, 0, 0, 0, 0, $nw, $nh, $outW, $outH);
                imagedestroy($out);
                $out = $scaled;
            }

            imagealphablending($out, false);
            imagesavealpha($out, true);

            if (! imagepng($out, $destPath, 6)) {
                throw new RuntimeException('Failed to save processed logo.');
            }
            imagedestroy($out);
        } catch (Throwable $e) {
            imagedestroy($im);
            throw $e;
        }

        imagedestroy($im);
    }

    private function isCanvasPixel(int $r, int $g, int $b, int $threshold): bool
    {
        // Solid black / near-black backgrounds from AI exports.
        if ($r <= $threshold && $g <= $threshold && $b <= $threshold) {
            return true;
        }

        // Near-white empty padding some exports include.
        if ($r >= 250 && $g >= 250 && $b >= 250) {
            return true;
        }

        return false;
    }
}
