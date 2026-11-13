<?php

namespace App\Support;

use App\Models\Animation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Animations : règles « anim.* » (formats, taille, durée, quota) et conversion vidéo avec ffmpeg.
 *
 * - La source reste sur le disque privé (Animation::PRIVATE_DISK) sous animations/sources/… : jamais servie.
 * - Versions publiques (disque « public ») sous animations/{id}/… avec un hash dans le nom (cache long possible) :
 *   MP4 H.264 (largeur max 1280, yuv420p, faststart ; boucle sans piste son, clip en AAC 128 k),
 *   WebM VP9 facultatif (config media.webm, désactivé par défaut) et poster WebP (GD) extrait du MP4.
 * - Un GIF envoyé devient un MP4 (beaucoup plus léger) ; la source GIF reste privée.
 * - La conversion se fait dans le job ProcessAnimation, jamais pendant la requête d'envoi.
 */
class AnimationMedia
{
    public const MAX_WIDTH = 1280;
    public const POSTER_QUALITY = 82;
    /** Instant du poster par défaut (secondes). */
    public const POSTER_DEFAULT_SECOND = 1.0;

    /** Extensions acceptables → noms de formats renvoyés par ffprobe (format_name). */
    public const FORMATS = [
        'mp4' => ['mp4', 'mov'],
        'mov' => ['mov', 'mp4'],
        'webm' => ['webm', 'matroska'],
        'gif' => ['gif'],
    ];

    // ---------- Réglages ----------

    public static function enabled(): bool
    {
        return (bool) setting('anim.active', true);
    }

    /** Formats acceptés (réglage texte « mp4, mov, webm, gif »), limités à ceux que l'on sait convertir. */
    public static function formats(): array
    {
        $formats = collect(preg_split('/[\s,;]+/', mb_strtolower((string) setting('anim.formats_acceptes', 'mp4, mov, webm, gif'))))
            ->map(fn ($format) => ltrim(trim($format), '.'))
            ->map(fn ($format) => $format === 'm4v' ? 'mp4' : $format)
            ->filter(fn ($format) => isset(self::FORMATS[$format]))
            ->unique()->values()->all();

        return $formats ?: array_keys(self::FORMATS);
    }

    /** Extensions pour la règle « extensions: » (m4v avec mp4). */
    public static function extensions(): array
    {
        $formats = self::formats();

        return in_array('mp4', $formats, true) ? [...$formats, 'm4v'] : $formats;
    }

    /** Attribut « accept » du champ fichier. */
    public static function acceptAttribute(): string
    {
        $mimes = ['mp4' => 'video/mp4', 'mov' => 'video/quicktime', 'webm' => 'video/webm', 'gif' => 'image/gif'];

        return collect(self::formats())->flatMap(fn ($format) => ['.'.$format, $mimes[$format]])->implode(',');
    }

    /** « MP4, MOV, WEBM ou GIF ». */
    public static function formatsLabel(): string
    {
        $labels = array_map('strtoupper', self::formats());

        return count($labels) > 1 ? implode(', ', array_slice($labels, 0, -1)).' ou '.end($labels) : $labels[0];
    }

    public static function maxDuration(): int
    {
        return max(1, (int) setting('anim.duree_max_s', 120));
    }

    public static function maxFileMb(): int
    {
        return max(1, (int) setting('anim.taille_max_mo', 100));
    }

    public static function maxFileBytes(): int
    {
        return self::maxFileMb() * 1024 * 1024;
    }

    /** Quota d'animations par membre, séparé de ceux des illustrations et des récits dessinés. */
    public static function quotaBytes(): int
    {
        return max(1, (int) setting('anim.stockage_max_go', 2)) * 1024 * 1024 * 1024;
    }

    /** Stockage utilisé : source + versions de toutes ses animations (corbeille comprise). */
    public static function usedBytes(User $user): int
    {
        return (int) DB::table('animations')->where('user_id', $user->id)->sum('taille_octets');
    }

    /** @return array{used: int, limit: int, ratio: int, usedLabel: string, limitLabel: string} */
    public static function storage(User $user): array
    {
        $used = self::usedBytes($user);
        $limit = self::quotaBytes();

        return [
            'used' => $used,
            'limit' => $limit,
            'ratio' => min(100, (int) round($used / $limit * 100)),
            'usedLabel' => ComicImages::formatBytes($used),
            'limitLabel' => ComicImages::formatBytes($limit),
        ];
    }

    /** « 2 min », « 1 min 30 s », « 45 s ». */
    public static function durationLabel(float $seconds): string
    {
        $seconds = (int) ceil($seconds);
        $minutes = intdiv($seconds, 60);
        $rest = $seconds % 60;

        return match (true) {
            $minutes === 0 => $rest.' s',
            $rest === 0 => $minutes.' min',
            default => $minutes.' min '.$rest.' s',
        };
    }

    // ---------- Fichiers ----------

    /** Range la source sur le disque privé. */
    public static function storeSource(UploadedFile $file, User $user): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'video');
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'video';

        return $file->storeAs('animations/sources/'.$user->id, Str::random(40).'.'.$extension, Animation::PRIVATE_DISK);
    }

    /** Chemins publics d'une animation (MP4, WebM, poster). */
    public static function publicPaths(Animation $animation): array
    {
        return array_values(array_filter([$animation->mp4, $animation->webm, $animation->poster]));
    }

    /** Supprime tous les fichiers (versions publiques et source privée). */
    public static function deleteFiles(Animation $animation): void
    {
        Storage::disk('public')->delete(self::publicPaths($animation));

        if ($animation->source) {
            Storage::disk(Animation::PRIVATE_DISK)->delete($animation->source);
        }
    }

    // ---------- Conversion ----------

    /**
     * Vérifie et convertit la source. Renvoie true si l'animation est prête ; sinon elle passe en « erreur »
     * avec un message compréhensible en français.
     */
    public static function process(Animation $animation): bool
    {
        if (! Ffmpeg::available()) {
            return self::fail($animation, Ffmpeg::UNAVAILABLE_MESSAGE.' Relance la conversion plus tard.');
        }

        $private = Storage::disk(Animation::PRIVATE_DISK);

        if (! $animation->source || ! $private->exists($animation->source)) {
            return self::fail($animation, 'Fichier d’origine introuvable : envoie la vidéo à nouveau.');
        }

        $sourceSize = (int) $private->size($animation->source);

        if ($sourceSize > self::maxFileBytes()) {
            return self::fail($animation, 'Le fichier dépasse '.self::maxFileMb().' Mo.');
        }

        $animation->forceFill(['statut_traitement' => Animation::PROCESSING, 'erreur' => null])->save();

        $input = $private->path($animation->source);
        $probe = Ffmpeg::probe($input);

        if ($error = self::checkProbe($probe)) {
            return self::fail($animation, $error);
        }

        $withAudio = ! $animation->isLoop() && $probe['audio'];
        $created = [];
        $temp = [];

        try {
            $public = Storage::disk('public');
            $dir = 'animations/'.$animation->id;
            $hash = substr(sha1($animation->id.'|'.$animation->source.'|'.microtime(true).'|'.Str::random(8)), 0, 12);

            // MP4 H.264.
            $mp4Temp = $temp[] = self::tempPath('mp4');
            $result = Ffmpeg::run(Ffmpeg::mp4Arguments($input, $mp4Temp, $withAudio, self::MAX_WIDTH));

            if (! $result['ok'] || ! is_file($mp4Temp) || filesize($mp4Temp) === 0) {
                Log::warning('Animation conversion failed', ['animation' => $animation->id, 'error' => Str::limit($result['error'], 500)]);

                return self::cleanFail($animation, $created, $temp, 'La conversion a échoué : la vidéo est peut-être abîmée ou dans un codec non supporté. Essaie de l’exporter à nouveau en MP4 (H.264).');
            }

            $output = Ffmpeg::probe($mp4Temp);

            if (! $output || ! $output['video']) {
                return self::cleanFail($animation, $created, $temp, 'La conversion a produit un fichier illisible. Essaie de l’exporter à nouveau en MP4 (H.264).');
            }

            $mp4 = $dir.'/'.$hash.'.mp4';
            $public->put($mp4, fopen($mp4Temp, 'r'));
            $created[] = $mp4;
            $bytes = $sourceSize + (int) filesize($mp4Temp);
            $duration = $output['duration'] ?? $probe['duration'];

            // WebM VP9 facultatif (un échec n'empêche pas la publication : le MP4 suffit).
            $webm = null;

            if (config('media.webm')) {
                $webmTemp = $temp[] = self::tempPath('webm');
                $webmResult = Ffmpeg::run(Ffmpeg::webmArguments($input, $webmTemp, $withAudio, self::MAX_WIDTH));

                if ($webmResult['ok'] && is_file($webmTemp) && filesize($webmTemp) > 0) {
                    $webm = $dir.'/'.$hash.'.webm';
                    $public->put($webm, fopen($webmTemp, 'r'));
                    $created[] = $webm;
                    $bytes += (int) filesize($webmTemp);
                }
            }

            // Poster WebP.
            $poster = self::extractPoster($mp4Temp, self::posterSecond($animation->poster_seconde, $duration), $dir, $hash);

            if ($poster) {
                $created[] = $poster[0];
                $bytes += $poster[1];
            }

            $old = self::publicPaths($animation);

            $animation->forceFill([
                'mp4' => $mp4,
                'webm' => $webm,
                'poster' => $poster[0] ?? null,
                'duree_s' => $duration !== null ? round($duration, 2) : null,
                'largeur' => $output['video']['width'] ?: null,
                'hauteur' => $output['video']['height'] ?: null,
                'taille_octets' => $bytes,
                'statut_traitement' => Animation::READY,
                'erreur' => null,
                'traite_le' => now(),
            ])->save();

            $public->delete(array_diff($old, $created));
            self::cleanTemp($temp);

            return true;
        } catch (Throwable $exception) {
            Log::warning('Animation processing failed', ['animation' => $animation->id, 'error' => $exception->getMessage()]);

            return self::cleanFail($animation, $created, $temp, 'La conversion a échoué. Relance-la ou envoie la vidéo à nouveau.');
        }
    }

    /**
     * Vérifications ffprobe : format réel, piste vidéo, dimensions, durée. Renvoie le message d'erreur, ou null.
     */
    public static function checkProbe(?array $probe, ?int $maxDuration = null): ?string
    {
        $maxDuration ??= self::maxDuration();

        if ($probe === null) {
            return 'Ce fichier n’est pas une vidéo lisible : il est peut-être abîmé ou dans un format non supporté.';
        }

        $names = array_filter(explode(',', $probe['format']));
        $allowed = collect(self::formats())->flatMap(fn ($format) => self::FORMATS[$format])->unique()->all();
        $known = collect(self::FORMATS)->flatten()->unique()->all();

        if (! array_intersect($names, $known)) {
            return $probe['video'] && $probe['duration'] === null
                ? 'Ce fichier est une image fixe, pas une vidéo. Formats acceptés : '.self::formatsLabel().'.'
                : 'Format non supporté. Formats acceptés : '.self::formatsLabel().'.';
        }

        if (! array_intersect($names, $allowed)) {
            return 'Format non accepté sur le site. Formats acceptés : '.self::formatsLabel().'.';
        }

        if (! $probe['video']) {
            return 'Aucune image dans ce fichier : il ne contient pas de piste vidéo (fichier audio seul ?).';
        }

        if ($probe['video']['width'] < 16 || $probe['video']['height'] < 16) {
            return 'Dimensions de la vidéo illisibles ou trop petites : le fichier est peut-être abîmé.';
        }

        if ($probe['duration'] === null || $probe['duration'] <= 0) {
            return 'Impossible de lire la durée de la vidéo : le fichier est peut-être abîmé.';
        }

        if ($probe['duration'] > $maxDuration + 0.5) {
            return 'Vidéo trop longue ('.self::durationLabel($probe['duration']).') : la durée maximale est de '
                .self::durationLabel($maxDuration).'. Raccourcis-la avant de l’envoyer.';
        }

        return null;
    }

    /** Instant du poster : choix de l'artiste, sinon 1 s, borné à la durée (0 si la vidéo est plus courte). */
    public static function posterSecond(?float $chosen, ?float $duration): float
    {
        $second = $chosen ?? self::POSTER_DEFAULT_SECOND;

        if ($duration !== null && $second >= $duration) {
            $second = $duration > self::POSTER_DEFAULT_SECOND ? max(0, $duration - 0.1) : 0;
        }

        return max(0, round($second, 2));
    }

    /**
     * Change le poster d'une animation prête (instant choisi par l'artiste) : une seule image extraite du MP4,
     * rapide. Renvoie false si ffmpeg est absent ou si l'extraction échoue.
     */
    public static function regeneratePoster(Animation $animation, float $second): bool
    {
        if (! $animation->isReady() || ! Ffmpeg::available()) {
            return false;
        }

        $public = Storage::disk('public');

        if (! $public->exists($animation->mp4)) {
            return false;
        }

        $second = self::posterSecond($second, $animation->duree_s);
        $hash = substr(sha1($animation->id.'|poster|'.microtime(true).'|'.Str::random(8)), 0, 12);
        $poster = self::extractPoster($public->path($animation->mp4), $second, 'animations/'.$animation->id, $hash);

        if (! $poster) {
            return false;
        }

        $old = $animation->poster;
        $oldBytes = $old && $public->exists($old) ? (int) $public->size($old) : 0;

        $animation->forceFill([
            'poster' => $poster[0],
            'poster_seconde' => $second,
            'taille_octets' => max(0, (int) $animation->taille_octets - $oldBytes + $poster[1]),
        ])->save();

        if ($old && $old !== $poster[0]) {
            $public->delete($old);
        }

        return true;
    }

    /**
     * Extrait une image du MP4 (PNG via ffmpeg) et l'enregistre en WebP (GD) sur le disque public.
     *
     * @return array{0: string, 1: int}|null  [chemin, taille]
     */
    private static function extractPoster(string $video, float $second, string $dir, string $hash): ?array
    {
        $image = self::extractFrame($video, $second, self::MAX_WIDTH);

        if (! $image) {
            return null;
        }

        $path = $dir.'/'.$hash.'-poster.webp';
        $bytes = self::putWebp($image, $path);
        imagedestroy($image);

        return $bytes > 0 ? [$path, $bytes] : null;
    }

    /** Une image de la vidéo (ffmpeg → PNG temporaire → GD), ou null. */
    private static function extractFrame(string $video, float $second, int $maxWidth): ?\GdImage
    {
        if (! function_exists('imagewebp')) {
            return null;
        }

        $png = self::tempPath('png');

        try {
            $result = Ffmpeg::run(Ffmpeg::frameArguments($video, $png, $second, $maxWidth), 60);

            if ((! $result['ok'] || ! is_file($png) || filesize($png) === 0) && $second > 0) {
                // Instant hors de la vidéo (durée arrondie) : première image.
                $result = Ffmpeg::run(Ffmpeg::frameArguments($video, $png, 0, $maxWidth), 60);
            }

            if (! $result['ok'] || ! is_file($png) || filesize($png) === 0) {
                return null;
            }

            return @imagecreatefrompng($png) ?: null;
        } catch (Throwable $exception) {
            Log::warning('Animation frame extraction failed', ['error' => $exception->getMessage()]);

            return null;
        } finally {
            @unlink($png);
        }
    }

    /** Écrit l'image en WebP sur le disque public et renvoie sa taille (0 en cas d'échec). */
    private static function putWebp(\GdImage $image, string $path): int
    {
        ob_start();
        $ok = imagewebp($image, null, self::POSTER_QUALITY);
        $binary = (string) ob_get_clean();

        if (! $ok || $binary === '') {
            return 0;
        }

        Storage::disk('public')->put($path, $binary);

        return strlen($binary);
    }

    // ---------- Cases animées dans un chapitre de récit dessiné (§14) ----------

    /** Durée maximale d'une page animée (boucle muette). */
    public const COMIC_VIDEO_MAX_SECONDS = 15;

    /** Extensions d'un original de planche traité comme une vidéo (rangé par ComicImages::storeOriginal). */
    public const COMIC_VIDEO_EXTENSIONS = ['mp4', 'm4v', 'mov', 'qt', 'webm', 'gif'];

    /** Pages animées possibles : animations activées et ffmpeg présent. */
    public static function comicVideosEnabled(): bool
    {
        return self::enabled() && Ffmpeg::available();
    }

    /** Extensions vidéo acceptées en plus des images dans l'étape « planches » (vide si indisponible). */
    public static function comicVideoExtensions(): array
    {
        if (! self::comicVideosEnabled()) {
            return [];
        }

        $formats = self::formats();

        return array_values(array_unique([...$formats, ...(in_array('mp4', $formats, true) ? ['m4v'] : []), ...(in_array('mov', $formats, true) ? ['qt'] : [])]));
    }

    public static function isComicVideo(\App\Models\ComicPage $page): bool
    {
        return in_array(strtolower(pathinfo((string) $page->original, PATHINFO_EXTENSION)), self::COMIC_VIDEO_EXTENSIONS, true);
    }

    /**
     * Page animée d'un chapitre : boucle muette ≤ 15 s convertie en MP4 H.264 (sans piste son).
     * « lecture » reçoit le poster WebP (le lecteur affiche donc au moins l'image fixe), « miniature » la
     * miniature 300 px, « video » le MP4. Renvoie true si la page est prête.
     */
    public static function processComicPage(\App\Models\ComicPage $page): bool
    {
        $page->loadMissing('chapter.comic');
        $chapter = $page->chapter;
        $comic = $chapter?->comic;
        $fail = function (string $message) use ($page): bool {
            $page->forceFill(['statut_traitement' => \App\Models\ComicPage::FAILED, 'erreur' => Str::limit($message, 250)])->save();

            return false;
        };

        if (! $chapter || ! $comic) {
            return $fail('Chapitre introuvable.');
        }

        if (! self::comicVideosEnabled()) {
            return $fail(self::enabled()
                ? Ffmpeg::UNAVAILABLE_MESSAGE.' Remplace cette page animée par une image.'
                : 'Les pages animées ne sont pas disponibles. Remplace cette page par une image.');
        }

        $private = Storage::disk(\App\Models\ComicPage::PRIVATE_DISK);

        if (! $page->original || ! $private->exists($page->original)) {
            return $fail('Fichier d’origine introuvable : renvoie la page.');
        }

        $originalSize = (int) $private->size($page->original);

        if ($originalSize > ComicImages::maxPageBytes()) {
            return $fail('La page dépasse '.ComicImages::maxPageMb().' Mo.');
        }

        $input = $private->path($page->original);
        $probe = Ffmpeg::probe($input);

        if ($error = self::checkProbe($probe, self::COMIC_VIDEO_MAX_SECONDS)) {
            return $fail(str_replace('Vidéo trop longue', 'Page animée trop longue', $error));
        }

        $created = [];
        $temp = [];
        $width = min(self::MAX_WIDTH, ComicImages::readingWidth());

        try {
            $dir = 'comics/'.$comic->id.'/'.$chapter->id;
            $hash = substr(sha1($page->id.'|'.$page->original.'|'.microtime(true).'|'.Str::random(8)), 0, 12);

            $mp4Temp = $temp[] = self::tempPath('mp4');
            $result = Ffmpeg::run(Ffmpeg::mp4Arguments($input, $mp4Temp, false, $width));
            $output = $result['ok'] && is_file($mp4Temp) && filesize($mp4Temp) > 0 ? Ffmpeg::probe($mp4Temp) : null;

            if (! $output || ! $output['video']) {
                Log::warning('Comic video page conversion failed', ['page' => $page->id, 'error' => Str::limit($result['error'], 500)]);
                self::cleanTemp($temp);

                return $fail('La conversion de la page animée a échoué : exporte-la à nouveau en MP4 (H.264) ou en GIF.');
            }

            $public = Storage::disk('public');
            $video = $dir.'/'.$page->id.'-'.$hash.'.mp4';
            $public->put($video, fopen($mp4Temp, 'r'));
            $created[] = $video;
            $bytes = $originalSize + (int) filesize($mp4Temp);
            $duration = $output['duration'] ?? $probe['duration'];

            // Poster (image de lecture) et miniature 300 px (au plus 2:3, haut de l'image).
            $frame = self::extractFrame($mp4Temp, self::posterSecond(null, $duration), $width);

            if (! $frame) {
                Storage::disk('public')->delete($created);
                self::cleanTemp($temp);

                return $fail('Impossible d’extraire une image de la page animée.');
            }

            $reading = $dir.'/'.$page->id.'-'.$hash.'.webp';
            $bytes += self::putWebp($frame, $reading);
            $created[] = $reading;

            $frameWidth = imagesx($frame);
            $frameHeight = imagesy($frame);
            $thumbWidth = min(ComicImages::THUMB_WIDTH, $frameWidth);
            $scale = $thumbWidth / $frameWidth;
            $sourceCrop = min($frameHeight, (int) round(ComicImages::THUMB_MAX_HEIGHT / $scale));
            $thumbHeight = max(1, (int) round($sourceCrop * $scale));
            $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
            imagecopyresampled($thumb, $frame, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $frameWidth, $sourceCrop);
            imagedestroy($frame);
            $thumbPath = $dir.'/'.$page->id.'-'.$hash.'-mini.webp';
            $bytes += self::putWebp($thumb, $thumbPath);
            $created[] = $thumbPath;
            imagedestroy($thumb);

            $old = ComicImages::publicPaths($page);

            $page->forceFill([
                'video' => $video,
                'duree_s' => $duration !== null ? round($duration, 2) : null,
                'lecture' => $reading,
                'miniature' => $thumbPath,
                'tranches' => null,
                'largeur' => $output['video']['width'] ?: $frameWidth,
                'hauteur' => $output['video']['height'] ?: $frameHeight,
                'taille_octets' => $bytes,
                'statut_traitement' => \App\Models\ComicPage::READY,
                'erreur' => null,
            ])->save();

            $public->delete(array_diff($old, $created));
            self::cleanTemp($temp);

            return true;
        } catch (Throwable $exception) {
            Log::warning('Comic video page processing failed', ['page' => $page->id, 'error' => $exception->getMessage()]);
            Storage::disk('public')->delete($created);
            self::cleanTemp($temp);

            return $fail('La préparation de la page animée a échoué. Remplace-la ou renvoie-la.');
        }
    }

    private static function tempPath(string $extension): string
    {
        $dir = storage_path('app/tmp-animations');

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir.DIRECTORY_SEPARATOR.Str::random(24).'.'.$extension;
    }

    private static function cleanTemp(array $temp): void
    {
        foreach ($temp as $file) {
            @unlink($file);
        }
    }

    private static function cleanFail(Animation $animation, array $created, array $temp, string $message): bool
    {
        Storage::disk('public')->delete($created);
        self::cleanTemp($temp);

        return self::fail($animation, $message);
    }

    public static function fail(Animation $animation, string $message): bool
    {
        $animation->forceFill([
            'statut_traitement' => Animation::FAILED,
            'erreur' => Str::limit($message, 250),
            'traite_le' => now(),
        ])->save();

        return false;
    }
}
