<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Fast CMS uploads: resize large photos and store as JPEG so shared hosting stays snappy.
 */
class CmsImageStore
{
    public function store(
        UploadedFile $file,
        string $directory,
        int $maxWidth = 1920,
        int $quality = 82,
    ): string {
        $mime = strtolower((string) $file->getMimeType());
        $ext = strtolower((string) $file->getClientOriginalExtension());

        // Keep animated GIF / SVG as-is.
        if (str_contains($mime, 'gif') || $ext === 'gif' || str_contains($mime, 'svg') || $ext === 'svg') {
            return $file->store($directory, 'public');
        }

        if (! function_exists('imagecreatetruecolor')) {
            return $file->store($directory, 'public');
        }

        $tmp = $file->getRealPath();
        if (! $tmp || ! is_file($tmp)) {
            throw new RuntimeException('Uploaded image could not be read.');
        }

        $binary = @file_get_contents($tmp);
        if ($binary === false) {
            throw new RuntimeException('Unable to read uploaded image.');
        }

        $source = @imagecreatefromstring($binary);
        if (! $source instanceof \GdImage) {
            return $file->store($directory, 'public');
        }

        try {
            $width = imagesx($source);
            $height = imagesy($source);

            if ($width < 1 || $height < 1) {
                return $file->store($directory, 'public');
            }

            $targetWidth = $width;
            $targetHeight = $height;

            if ($width > $maxWidth) {
                $targetWidth = $maxWidth;
                $targetHeight = (int) max(1, round($height * ($maxWidth / $width)));
            }

            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
            if (! $canvas instanceof \GdImage) {
                return $file->store($directory, 'public');
            }

            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $white);
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

            $relative = trim($directory, '/').'/'.Str::uuid()->toString().'.jpg';
            $absolute = Storage::disk('public')->path($relative);
            $dir = dirname($absolute);
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            if (! imagejpeg($canvas, $absolute, $quality)) {
                imagedestroy($canvas);

                return $file->store($directory, 'public');
            }

            imagedestroy($canvas);
            @chmod($absolute, 0644);

            return $relative;
        } finally {
            imagedestroy($source);
        }
    }
}
