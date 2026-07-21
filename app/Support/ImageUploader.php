<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploader
{
    public static function storeProductImage(?UploadedFile $file, ?string $url = null, ?string $oldPath = null): ?string
    {
        if ($file) {
            return self::storeResized($file, 'products', $oldPath);
        }

        if ($url !== null && trim($url) !== '') {
            if ($oldPath && ! str_starts_with($oldPath, 'http')) {
                Storage::disk('public')->delete($oldPath);
            }

            return trim($url);
        }

        return $oldPath;
    }

    public static function storeLogo(?UploadedFile $file, ?string $oldPath = null): ?string
    {
        if (! $file) {
            return $oldPath;
        }

        return self::storeResized($file, 'logos', $oldPath, 600);
    }

    public static function clear(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }

    private static function storeResized(UploadedFile $file, string $folder, ?string $oldPath = null, int $maxWidth = 500): string
    {
        if ($oldPath && ! str_starts_with($oldPath, 'http')) {
            Storage::disk('public')->delete($oldPath);
        }

        $resized = self::resizeToJpeg($file, $maxWidth);
        $filename = $folder.'/'.Str::uuid().'.jpg';
        Storage::disk('public')->makeDirectory($folder);
        Storage::disk('public')->put($filename, $resized);

        return $filename;
    }

    private static function resizeToJpeg(UploadedFile $file, int $maxWidth = 500, int $quality = 85): string
    {
        $contents = file_get_contents($file->getRealPath());
        $source = @imagecreatefromstring($contents);

        if (! $source) {
            return $contents;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $maxWidth / max(1, $width));
        $newW = (int) round($width * $scale);
        $newH = (int) round($height * $scale);

        $canvas = imagecreatetruecolor($newW, $newH);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $width, $height);

        ob_start();
        imagejpeg($canvas, null, $quality);
        $jpeg = ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        return $jpeg ?: $contents;
    }
}
