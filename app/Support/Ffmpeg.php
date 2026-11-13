<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Accès à ffmpeg / ffprobe (chemins dans config/media.php, .env FFMPEG_PATH / FFPROBE_PATH).
 *
 * Les processus sont toujours lancés avec un tableau d'arguments (jamais de chaîne shell) et un délai maximal.
 * Sur un hébergement sans ffmpeg, available() renvoie false : l'envoi d'animations est suspendu, les animations
 * déjà converties restent lisibles et rien ne plante.
 */
class Ffmpeg
{
    public const UNAVAILABLE_MESSAGE = 'La conversion des vidéos n’est pas disponible sur ce serveur pour le moment.';

    /** Durée de mise en cache du test de présence (secondes). */
    public const CACHE_SECONDS = 600;

    public static function ffmpegPath(): string
    {
        return (string) (config('media.ffmpeg') ?: 'ffmpeg');
    }

    public static function ffprobePath(): string
    {
        return (string) (config('media.ffprobe') ?: 'ffprobe');
    }

    public static function timeout(): int
    {
        return max(30, (int) config('media.timeout', 600));
    }

    /**
     * ffmpeg ET ffprobe répondent-ils ? Résultat mis en cache 10 minutes (clé liée aux chemins configurés).
     */
    public static function available(): bool
    {
        $key = 'media.ffmpeg.available.'.md5(self::ffmpegPath().'|'.self::ffprobePath());

        return (bool) Cache::remember($key, self::CACHE_SECONDS, fn () => self::check() ? 1 : 0);
    }

    /** Oublie le résultat mis en cache (après un changement de configuration). */
    public static function forget(): void
    {
        Cache::forget('media.ffmpeg.available.'.md5(self::ffmpegPath().'|'.self::ffprobePath()));
    }

    /** Test réel, sans cache : « -version » sur les deux outils. */
    public static function check(): bool
    {
        foreach ([self::ffmpegPath(), self::ffprobePath()] as $binary) {
            try {
                $process = new Process([$binary, '-version']);
                $process->setTimeout(15);
                $process->run();

                if (! $process->isSuccessful() || ! str_contains($process->getOutput(), 'version')) {
                    return false;
                }
            } catch (Throwable) {
                return false;
            }
        }

        return true;
    }

    /** Première ligne de « ffmpeg -version » (affichée dans l'admin), ou null. */
    public static function version(): ?string
    {
        try {
            $process = new Process([self::ffmpegPath(), '-version']);
            $process->setTimeout(15);
            $process->run();

            return $process->isSuccessful() ? trim(strtok($process->getOutput(), "\n")) : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Analyse un fichier avec ffprobe.
     *
     * @return array{format: string, duration: ?float, video: ?array{codec: string, width: int, height: int, frames: ?int}, audio: bool, size: int}|null
     *         null si ffprobe ne sait pas lire le fichier
     */
    public static function probe(string $path): ?array
    {
        $process = new Process([
            self::ffprobePath(), '-v', 'error', '-print_format', 'json', '-show_format', '-show_streams', $path,
        ]);
        $process->setTimeout(60);

        try {
            $process->run();
        } catch (Throwable) {
            return null;
        }

        if (! $process->isSuccessful()) {
            return null;
        }

        $data = json_decode($process->getOutput(), true);

        if (! is_array($data) || ! isset($data['format'])) {
            return null;
        }

        $streams = collect($data['streams'] ?? []);
        // Piste vidéo réelle (pas une pochette « attached_pic » d'un fichier audio).
        $video = $streams->first(fn ($s) => ($s['codec_type'] ?? null) === 'video' && empty($s['disposition']['attached_pic']));
        $duration = $data['format']['duration'] ?? ($video['duration'] ?? null);

        return [
            'format' => (string) ($data['format']['format_name'] ?? ''),
            'duration' => is_numeric($duration) ? (float) $duration : null,
            'video' => $video ? [
                'codec' => (string) ($video['codec_name'] ?? ''),
                'width' => (int) ($video['width'] ?? 0),
                'height' => (int) ($video['height'] ?? 0),
                'frames' => isset($video['nb_frames']) && is_numeric($video['nb_frames']) ? (int) $video['nb_frames'] : null,
            ] : null,
            'audio' => $streams->contains(fn ($s) => ($s['codec_type'] ?? null) === 'audio'),
            'size' => (int) ($data['format']['size'] ?? (is_file($path) ? filesize($path) : 0)),
        ];
    }

    /**
     * Lance ffmpeg avec les arguments donnés (sans le binaire).
     *
     * @return array{ok: bool, error: string}
     */
    public static function run(array $arguments, ?int $timeout = null): array
    {
        $process = new Process([self::ffmpegPath(), '-hide_banner', '-nostdin', '-loglevel', 'error', '-y', ...$arguments]);
        $process->setTimeout($timeout ?? self::timeout());

        try {
            $process->run();
        } catch (Throwable $exception) {
            return ['ok' => false, 'error' => $exception->getMessage()];
        }

        return ['ok' => $process->isSuccessful(), 'error' => trim($process->getErrorOutput())];
    }

    // ---------- Commandes ----------

    /** Filtre de redimensionnement : largeur ≤ $maxWidth, jamais agrandie, dimensions paires (H.264 / yuv420p). */
    public static function scaleFilter(int $maxWidth = 1280): string
    {
        return "scale='trunc(min({$maxWidth},iw)/2)*2':-2";
    }

    /**
     * MP4 H.264 lisible partout (faststart pour démarrer avant la fin du téléchargement).
     * Boucle : sans piste son. Clip : AAC 128 k (si la source a du son).
     */
    public static function mp4Arguments(string $input, string $output, bool $withAudio, int $maxWidth = 1280): array
    {
        return [
            '-i', $input,
            '-map', '0:v:0', ...($withAudio ? ['-map', '0:a:0?'] : []),
            '-map_metadata', '-1', '-sn', '-dn', ...($withAudio ? [] : ['-an']),
            '-vf', self::scaleFilter($maxWidth).',format=yuv420p',
            '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '26', '-pix_fmt', 'yuv420p',
            ...($withAudio ? ['-c:a', 'aac', '-b:a', '128k', '-ac', '2'] : []),
            '-movflags', '+faststart',
            $output,
        ];
    }

    /** WebM VP9 (facultatif, désactivé par défaut : lent sur un petit serveur). */
    public static function webmArguments(string $input, string $output, bool $withAudio, int $maxWidth = 1280): array
    {
        return [
            '-i', $input,
            '-map', '0:v:0', ...($withAudio ? ['-map', '0:a:0?'] : []),
            '-map_metadata', '-1', '-sn', '-dn', ...($withAudio ? [] : ['-an']),
            '-vf', self::scaleFilter($maxWidth).',format=yuv420p',
            '-c:v', 'libvpx-vp9', '-b:v', '0', '-crf', '34', '-deadline', 'good', '-cpu-used', '4', '-row-mt', '1',
            ...($withAudio ? ['-c:a', 'libopus', '-b:a', '96k'] : []),
            $output,
        ];
    }

    /** Une image (PNG) à l'instant $second, largeur ≤ $maxWidth. */
    public static function frameArguments(string $input, string $output, float $second, int $maxWidth = 1280): array
    {
        return [
            '-ss', number_format(max(0, $second), 2, '.', ''),
            '-i', $input,
            '-frames:v', '1',
            '-vf', "scale='min({$maxWidth},iw)':-2",
            $output,
        ];
    }
}
