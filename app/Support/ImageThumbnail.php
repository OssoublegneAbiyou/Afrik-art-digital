<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Miniatures légères (≈ 600 px de large, WebP) pour les grilles.
 *
 * Le chemin de la miniature est déduit du chemin de l'image d'origine : aucune colonne
 * en base n'est nécessaire. Si la miniature n'existe pas encore, l'URL renvoyée pointe
 * vers une route qui la génère à la première demande (voir ThumbnailController).
 */
class ImageThumbnail
{
    public const WIDTH = 600;

    public static function pathFor(string $source, int $width = self::WIDTH): string
    {
        $extension = function_exists('imagewebp') ? 'webp' : 'jpg';

        return 'thumbnails/'.substr(sha1($source), 0, 24).'-'.$width.'.'.$extension;
    }

    public static function exists(string $source, int $width = self::WIDTH): bool
    {
        return Storage::disk('public')->exists(self::pathFor($source, $width));
    }

    /**
     * URL publique de la miniature si elle existe, sinon l'URL de génération à la demande.
     */
    public static function url(string $source, string $fallbackUrl, int $width = self::WIDTH): string
    {
        return self::exists($source, $width)
            ? asset('storage/'.self::pathFor($source, $width))
            : $fallbackUrl;
    }

    /**
     * Crée la miniature (sans agrandir une image plus petite). Renvoie son chemin, ou null en cas d'échec.
     */
    public static function generate(string $source, int $width = self::WIDTH): ?string
    {
        $disk = Storage::disk('public');
        $target = self::pathFor($source, $width);

        if ($disk->exists($target)) {
            return $target;
        }

        if (! extension_loaded('gd') || ! $disk->exists($source)) {
            return null;
        }

        $previousLimit = ini_get('memory_limit');
        // Une image de 4961 × 7016 px demande environ 200 Mo une fois décodée.
        ini_set('memory_limit', '1024M');

        try {
            $image = @imagecreatefromstring($disk->get($source));

            if (! $image) {
                return null;
            }

            $sourceWidth = imagesx($image);
            $sourceHeight = imagesy($image);
            $targetWidth = min($width, $sourceWidth);
            $targetHeight = max(1, (int) round($sourceHeight * ($targetWidth / $sourceWidth)));

            $thumb = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            imagecopyresampled($thumb, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
            imagedestroy($image);

            ob_start();
            function_exists('imagewebp') ? imagewebp($thumb, null, 80) : imagejpeg($thumb, null, 82);
            $binary = ob_get_clean();
            imagedestroy($thumb);

            $disk->put($target, $binary);

            return $target;
        } catch (Throwable $exception) {
            Log::warning('Thumbnail generation failed', ['path' => $source, 'error' => $exception->getMessage()]);

            return null;
        } finally {
            ini_set('memory_limit', $previousLimit);
        }
    }

    public static function delete(string $source, int $width = self::WIDTH): void
    {
        Storage::disk('public')->delete(self::pathFor($source, $width));
    }
}
