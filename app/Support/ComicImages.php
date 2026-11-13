<?php

namespace App\Support;

use App\Models\Comic;
use App\Models\ComicPage;
use App\Models\User;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Traitement des planches de récits dessinés (GD, sans bibliothèque externe) et règles « bd.* ».
 *
 * - L'original reste sur le disque privé (ComicPage::PRIVATE_DISK) sous comics/originaux/… : jamais servi.
 * - Versions publiques (disque « public ») sous comics/{comic}/{chapitre}/… avec un hash dans le nom
 *   (cache long possible) : « lecture » WebP (largeur max bd.largeur_lecture_px, qualité 82) et
 *   « miniature » WebP 300 px.
 * - Image très haute (webtoon) : au-delà de 5 000 px de haut après redimensionnement, découpe en tranches
 *   d'environ 2 000 px ([{chemin, largeur, hauteur}] dans « tranches ») ; « lecture » pointe alors sur la
 *   première tranche, « largeur »/« hauteur » décrivent l'image entière.
 * - Ré-encoder l'image retire toutes les métadonnées (GPS, appareil) ; l'orientation EXIF est appliquée avant.
 */
class ComicImages
{
    public const QUALITY = 82;
    public const THUMB_WIDTH = 300;
    /** Miniature limitée au format 2:3 (le haut de la planche pour un webtoon). */
    public const THUMB_MAX_HEIGHT = 450;
    public const SLICE_THRESHOLD = 5000;
    public const SLICE_HEIGHT = 2000;
    /** Garde-fou mémoire : au-delà, l'image décodée dépasserait plusieurs centaines de Mo. */
    public const MAX_PIXELS = 80_000_000;

    public const MIMES = [
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    // ---------- Réglages ----------

    public static function enabled(): bool
    {
        return (bool) setting('bd.active', true);
    }

    /** Formats acceptés (réglage texte « jpg, png, webp »), limités à ceux que GD sait lire. */
    public static function formats(): array
    {
        $formats = collect(preg_split('/[\s,;]+/', mb_strtolower((string) setting('bd.formats_acceptes', 'jpg, png, webp'))))
            ->map(fn ($format) => ltrim(trim($format), '.'))
            ->map(fn ($format) => $format === 'jpeg' ? 'jpg' : $format)
            ->filter(fn ($format) => isset(self::MIMES[$format]))
            ->unique()->values()->all();

        return $formats ?: array_keys(self::MIMES);
    }

    /** Extensions pour la règle « mimes: » (jpeg en plus de jpg). */
    public static function extensions(): array
    {
        $formats = self::formats();

        return in_array('jpg', $formats, true) ? [...$formats, 'jpeg'] : $formats;
    }

    public static function acceptedMimes(): array
    {
        return array_values(array_map(fn ($format) => self::MIMES[$format], self::formats()));
    }

    /** « JPG, PNG ou WEBP ». */
    public static function formatsLabel(): string
    {
        $labels = array_map('strtoupper', self::formats());

        return count($labels) > 1 ? implode(', ', array_slice($labels, 0, -1)).' ou '.end($labels) : $labels[0];
    }

    public static function maxPageMb(): int
    {
        return max(1, (int) setting('bd.taille_max_page_mo', 15));
    }

    public static function maxPageBytes(): int
    {
        return self::maxPageMb() * 1024 * 1024;
    }

    public static function readingWidth(): int
    {
        return min(2400, max(800, (int) setting('bd.largeur_lecture_px', 1600)));
    }

    public static function maxPagesPerChapter(): int
    {
        return max(1, (int) setting('bd.pages_max_par_chapitre', 80));
    }

    /** Quota BD par membre, séparé de celui des illustrations. */
    public static function quotaBytes(): int
    {
        return max(1, (int) setting('bd.stockage_max_go', 3)) * 1024 * 1024 * 1024;
    }

    /** Stockage BD utilisé : planches (original + versions) et couvertures de tous ses récits. */
    public static function usedBytes(User $user): int
    {
        $pages = (int) DB::table('comic_pages')
            ->join('comic_chapters', 'comic_chapters.id', '=', 'comic_pages.comic_chapter_id')
            ->join('comics', 'comics.id', '=', 'comic_chapters.comic_id')
            ->where('comics.user_id', $user->id)
            ->sum('comic_pages.taille_octets');

        $covers = (int) DB::table('comics')->where('user_id', $user->id)->sum('couverture_octets');

        return $pages + $covers;
    }

    /** « 12,4 Mo », « 1,25 Go ». */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 ** 3) {
            return number_format($bytes / 1024 ** 3, 2, ',', ' ').' Go';
        }

        return number_format($bytes / 1024 ** 2, 1, ',', ' ').' Mo';
    }

    // ---------- Fichiers ----------

    /**
     * Tri naturel par nom de fichier, insensible à la casse : « 2.jpg » avant « 10.jpg ».
     *
     * @param  array<int|string, string>  $names
     * @return array<int|string, string>  mêmes clés, dans l'ordre de lecture
     */
    public static function naturalSort(array $names): array
    {
        uasort($names, fn ($a, $b) => strnatcasecmp((string) $a, (string) $b));

        return $names;
    }

    /** Range l'original sur le disque privé. */
    public static function storeOriginal(UploadedFile $file, Comic $comic, int $chapterId): string
    {
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'img');
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;

        return $file->storeAs('comics/originaux/'.$comic->id.'/'.$chapterId, Str::random(40).'.'.$extension, ComicPage::PRIVATE_DISK);
    }

    /**
     * Couverture : convertie en WebP (900 px de large au plus, métadonnées retirées) sur le disque public.
     *
     * @return array{0: string, 1: int}|null  [chemin, taille] ou null si l'image est illisible
     */
    public static function storeCover(UploadedFile $file, Comic $comic): ?array
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            return null;
        }

        $path = $file->getRealPath();
        $info = @getimagesize($path);

        if (! $info || ! in_array($info['mime'], array_values(self::MIMES), true) || $info[0] * $info[1] > self::MAX_PIXELS) {
            return null;
        }

        $previousLimit = ini_get('memory_limit');
        self::raiseMemory();

        try {
            $image = @imagecreatefromstring((string) file_get_contents($path));

            if (! $image) {
                return null;
            }

            if (! imageistruecolor($image)) {
                imagepalettetotruecolor($image);
            }

            $image = self::orient($image, $path, $info['mime']);
            $width = min(900, imagesx($image));
            $height = max(1, (int) round(imagesy($image) * $width / imagesx($image)));
            $cover = self::canvas($width, $height);
            imagecopyresampled($cover, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
            imagedestroy($image);

            $target = 'comics/'.$comic->id.'/couverture-'.substr(sha1(Str::random(16).microtime(true)), 0, 12).'.webp';
            $bytes = self::putWebp($cover, $target);
            imagedestroy($cover);

            return [$target, $bytes];
        } catch (Throwable $exception) {
            Log::warning('Comic cover processing failed', ['comic' => $comic->id, 'error' => $exception->getMessage()]);

            return null;
        } finally {
            ini_set('memory_limit', $previousLimit);
        }
    }

    /** Chemins publics d'une planche (lecture, miniature, tranches). */
    public static function publicPaths(ComicPage $page): array
    {
        return collect([$page->lecture, $page->miniature])
            ->concat(collect($page->tranches ?? [])->pluck('chemin'))
            ->filter()->unique()->values()->all();
    }

    public static function deleteVersions(ComicPage $page): void
    {
        Storage::disk('public')->delete(self::publicPaths($page));
    }

    /** Supprime tous les fichiers d'une planche (versions publiques et original privé). */
    public static function deleteFiles(ComicPage $page): void
    {
        self::deleteVersions($page);

        if ($page->original) {
            Storage::disk(ComicPage::PRIVATE_DISK)->delete($page->original);
        }
    }

    // ---------- Traitement ----------

    /**
     * Prépare une planche : vérifications, orientation, versions WebP, découpe, filigrane.
     * Renvoie true si la planche est prête ; sinon elle passe en « erreur » avec un message en français.
     */
    public static function process(ComicPage $page): bool
    {
        $page->loadMissing('chapter.comic.user');
        $chapter = $page->chapter;
        $comic = $chapter?->comic;

        if (! $chapter || ! $comic) {
            return self::fail($page, 'Chapitre introuvable.');
        }

        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            return self::fail($page, 'Le serveur ne sait pas convertir les images (extension GD avec WebP absente).');
        }

        $private = Storage::disk(ComicPage::PRIVATE_DISK);

        if (! $page->original || ! $private->exists($page->original)) {
            return self::fail($page, 'Fichier d’origine introuvable : renvoie la planche.');
        }

        $originalSize = (int) $private->size($page->original);

        if ($originalSize > self::maxPageBytes()) {
            return self::fail($page, 'La planche dépasse '.self::maxPageMb().' Mo.');
        }

        $path = $private->path($page->original);
        // Type réel du fichier (contenu), pas l'extension.
        $info = @getimagesize($path);

        if (! $info) {
            return self::fail($page, 'Ce fichier n’est pas une image lisible.');
        }

        if (! in_array($info['mime'], self::acceptedMimes(), true)) {
            return self::fail($page, 'Format non accepté : seules les images '.self::formatsLabel().' sont acceptées.');
        }

        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            return self::fail($page, 'Image trop grande ('.$info[0].' × '.$info[1].' px) : réduis-la avant de l’envoyer.');
        }

        $previousLimit = ini_get('memory_limit');
        self::raiseMemory();
        $created = [];

        try {
            $image = @imagecreatefromstring($private->get($page->original));

            if (! $image) {
                return self::fail($page, 'Impossible de lire cette image : elle est peut-être abîmée.');
            }

            if (! imageistruecolor($image)) {
                imagepalettetotruecolor($image);
            }

            $image = self::orient($image, $path, $info['mime']);

            // Version de lecture : largeur max, jamais agrandie.
            $sourceWidth = imagesx($image);
            $sourceHeight = imagesy($image);
            $width = min(self::readingWidth(), $sourceWidth);
            $height = max(1, (int) round($sourceHeight * $width / $sourceWidth));

            if ($width !== $sourceWidth) {
                $resized = self::canvas($width, $height);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
                imagedestroy($image);
                $image = $resized;
            }

            if (setting('bd.filigrane', false)) {
                self::watermark($image, self::watermarkText($comic));
            }

            $public = Storage::disk('public');
            $dir = 'comics/'.$comic->id.'/'.$chapter->id;
            $hash = substr(sha1($page->id.'|'.$page->original.'|'.microtime(true).'|'.Str::random(8)), 0, 12);
            $bytes = $originalSize;
            $tranches = null;

            if ($height > self::SLICE_THRESHOLD) {
                $count = (int) ceil($height / self::SLICE_HEIGHT);
                $base = intdiv($height, $count);
                $rest = $height % $count;
                $y = 0;
                $tranches = [];

                for ($i = 0; $i < $count; $i++) {
                    $sliceHeight = $base + ($i < $rest ? 1 : 0);
                    $slice = self::canvas($width, $sliceHeight);
                    imagecopy($slice, $image, 0, 0, 0, $y, $width, $sliceHeight);
                    $slicePath = $dir.'/'.$page->id.'-'.$hash.'-'.($i + 1).'.webp';
                    $bytes += self::putWebp($slice, $slicePath);
                    $created[] = $slicePath;
                    imagedestroy($slice);
                    $tranches[] = ['chemin' => $slicePath, 'largeur' => $width, 'hauteur' => $sliceHeight];
                    $y += $sliceHeight;
                }

                $reading = $tranches[0]['chemin'];
            } else {
                $reading = $dir.'/'.$page->id.'-'.$hash.'.webp';
                $bytes += self::putWebp($image, $reading);
                $created[] = $reading;
            }

            // Miniature 300 px, au plus 2:3 (haut de la planche).
            $thumbWidth = min(self::THUMB_WIDTH, $width);
            $scale = $thumbWidth / $width;
            $sourceCrop = min($height, (int) round(self::THUMB_MAX_HEIGHT / $scale));
            $thumbHeight = max(1, (int) round($sourceCrop * $scale));
            $thumb = self::canvas($thumbWidth, $thumbHeight);
            imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $sourceCrop);
            imagedestroy($image);
            $thumbPath = $dir.'/'.$page->id.'-'.$hash.'-mini.webp';
            $bytes += self::putWebp($thumb, $thumbPath);
            $created[] = $thumbPath;
            imagedestroy($thumb);

            $old = self::publicPaths($page);

            $page->forceFill([
                'lecture' => $reading,
                'miniature' => $thumbPath,
                'tranches' => $tranches,
                'largeur' => $width,
                'hauteur' => $height,
                'taille_octets' => $bytes,
                'statut_traitement' => ComicPage::READY,
                'erreur' => null,
            ])->save();

            $public->delete(array_diff($old, $created));

            return true;
        } catch (Throwable $exception) {
            Log::warning('Comic page processing failed', ['page' => $page->id, 'error' => $exception->getMessage()]);
            Storage::disk('public')->delete($created);

            return self::fail($page, 'La préparation de la planche a échoué. Remplace-la ou renvoie-la.');
        } finally {
            ini_set('memory_limit', $previousLimit);
        }
    }

    private static function fail(ComicPage $page, string $message): bool
    {
        $page->forceFill(['statut_traitement' => ComicPage::FAILED, 'erreur' => $message])->save();

        return false;
    }

    private static function raiseMemory(): void
    {
        $current = ini_get('memory_limit');
        $bytes = $current === '-1' ? PHP_INT_MAX : self::iniBytes((string) $current);

        if ($bytes < 1024 * 1024 * 1024) {
            // Une planche de 4961 × 7016 px demande environ 200 Mo une fois décodée (cf. ImageThumbnail).
            ini_set('memory_limit', '1024M');
        }
    }

    private static function iniBytes(string $value): int
    {
        $number = (int) $value;

        return match (strtolower(substr(trim($value), -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private static function canvas(int $width, int $height): GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        return $canvas;
    }

    /** Écrit l'image en WebP sur le disque public et renvoie sa taille. */
    private static function putWebp(GdImage $image, string $path): int
    {
        ob_start();
        imagewebp($image, null, self::QUALITY);
        $binary = ob_get_clean();
        Storage::disk('public')->put($path, $binary);

        return strlen($binary);
    }

    /** Applique l'orientation EXIF (JPEG) si l'extension exif est disponible ; sinon, ne fait rien. */
    private static function orient(GdImage $image, string $path, string $mime): GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        $rotate = function (GdImage $source, int $angle): GdImage {
            $rotated = imagerotate($source, $angle, 0);
            imagedestroy($source);

            return $rotated;
        };

        switch ($orientation) {
            case 2:
                imageflip($image, IMG_FLIP_HORIZONTAL);
                break;
            case 3:
                $image = $rotate($image, 180);
                break;
            case 4:
                imageflip($image, IMG_FLIP_VERTICAL);
                break;
            case 5:
                $image = $rotate($image, -90);
                imageflip($image, IMG_FLIP_HORIZONTAL);
                break;
            case 6:
                $image = $rotate($image, -90);
                break;
            case 7:
                $image = $rotate($image, 90);
                imageflip($image, IMG_FLIP_HORIZONTAL);
                break;
            case 8:
                $image = $rotate($image, 90);
                break;
        }

        return $image;
    }

    /** « @pseudo · Afrik'art Digital ». */
    public static function watermarkText(Comic $comic): string
    {
        $pseudo = Str::of((string) ($comic->user?->name ?? 'auteur'))->squish()->replace(' ', '')->value();
        $collectif = (string) (setting('identite.nom') ?: config('site.name', config('app.name')));

        return '@'.$pseudo.' · '.$collectif;
    }

    /** Petit texte discret en bas à droite, sur un fond semi-transparent. */
    private static function watermark(GdImage $image, string $text): void
    {
        $width = imagesx($image);
        $height = imagesy($image);
        imagealphablending($image, true);

        $white = imagecolorallocatealpha($image, 255, 255, 255, 30);
        $shade = imagecolorallocatealpha($image, 0, 0, 0, 80);
        $font = self::fontPath();
        $margin = max(8, (int) round($width / 100));

        if ($font && function_exists('imagettftext')) {
            $size = max(9, (int) round($width / 90));
            $box = imagettfbbox($size, 0, $font, $text);

            if ($box !== false) {
                $textWidth = abs($box[2] - $box[0]);
                $textHeight = abs($box[7] - $box[1]);
                $x = max(0, $width - $textWidth - $margin * 2);
                $y = max($textHeight, $height - $margin);
                $pad = intdiv($margin, 2);
                imagefilledrectangle($image, (int) $x - $pad, (int) $y - $textHeight - $pad, (int) $x + $textWidth + $pad, (int) $y + $pad, $shade);
                imagettftext($image, $size, 0, (int) $x, (int) $y, $white, $font, $text);
                imagealphablending($image, false);

                return;
            }
        }

        // Police bitmap intégrée : ASCII uniquement.
        $ascii = Str::ascii(str_replace('·', '-', $text));
        $fontId = $width >= 900 ? 5 : 3;
        $textWidth = imagefontwidth($fontId) * strlen($ascii);
        $textHeight = imagefontheight($fontId);
        $x = max(0, $width - $textWidth - $margin * 2);
        $y = max(0, $height - $textHeight - $margin);
        imagefilledrectangle($image, $x - 4, $y - 3, $x + $textWidth + 4, $y + $textHeight + 3, $shade);
        imagestring($image, $fontId, $x, $y, $ascii, $white);
        imagealphablending($image, false);
    }

    private static function fontPath(): ?string
    {
        $candidates = [
            resource_path('fonts/watermark.ttf'),
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans.ttf',
            'C:/Windows/Fonts/arial.ttf',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
