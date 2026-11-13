<?php

namespace App\Support;

use Illuminate\Support\Str;
use ZipArchive;

/**
 * Transforme le texte d'une œuvre en chapitres, paragraphes et séparateurs pour la page de lecture.
 *
 * Convention d'écriture (aussi expliquée dans l'éditeur) :
 *  - une ligne vide sépare deux paragraphes ;
 *  - « # Titre » (ou une ligne seule « Chapitre 3 », « Partie II », « Prologue », « Épilogue »…) ouvre un chapitre ;
 *  - « * * * », « *** », « --- » ou « ✦ ✦ ✦ » sur une ligne seule marque une pause dans le chapitre.
 */
class ReaderText
{
    private const SEPARATOR = '/^(?:[✦*\-–—•·]\s*){3,}$/u';

    private const NAMED_HEADING = '/^(?:chapitre|partie|livre|acte|prologue|épilogue|epilogue|interlude)\b.{0,80}$/iu';

    /**
     * @return array{chapters: array<int, array{id: string, title: ?string, blocks: array}>, words: int}
     */
    public static function parse(?string $text): array
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", (string) $text));

        if ($text === '') {
            return ['chapters' => [], 'words' => 0];
        }

        $chapters = [];
        $current = ['title' => null, 'blocks' => []];
        $buffer = [];

        $flush = function () use (&$buffer, &$current) {
            if ($buffer) {
                $current['blocks'][] = ['type' => 'p', 'lines' => $buffer];
                $buffer = [];
            }
        };
        $openChapter = function (string $title) use (&$chapters, &$current, $flush) {
            $flush();
            if ($current['title'] !== null || $current['blocks']) {
                $chapters[] = $current;
            }
            $current = ['title' => $title, 'blocks' => []];
        };

        $lines = explode("\n", $text);
        foreach ($lines as $index => $rawLine) {
            $line = trim($rawLine);
            $previousBlank = $index === 0 || trim($lines[$index - 1]) === '';
            $nextBlank = ! isset($lines[$index + 1]) || trim($lines[$index + 1]) === '';

            if (preg_match('/^#{1,3}\s+(.+)$/u', $line, $match)) {
                $openChapter(trim($match[1]));

                continue;
            }

            // « Chapitre 3 » seul sur sa ligne, entouré de lignes vides.
            // (une phrase qui se termine par un point n'est pas un titre : « Partie de pêche au lever du jour. »)
            if ($previousBlank && $nextBlank && preg_match(self::NAMED_HEADING, $line) && ! preg_match('/[.!?…]$/u', $line)) {
                $openChapter($line);

                continue;
            }

            if ($line === '' || preg_match(self::SEPARATOR, $line)) {
                $flush();
                $blocks = $current['blocks'];
                if ($line !== '' && $blocks && end($blocks)['type'] !== 'sep') {
                    $current['blocks'][] = ['type' => 'sep'];
                }

                continue;
            }

            $buffer[] = $line;
        }

        $flush();
        $chapters[] = $current;

        $chapters = collect($chapters)
            ->map(function (array $chapter) {
                // Pas de séparateur en fin de chapitre.
                while ($chapter['blocks'] && end($chapter['blocks'])['type'] === 'sep') {
                    array_pop($chapter['blocks']);
                }

                return $chapter;
            })
            ->filter(fn (array $chapter) => $chapter['title'] !== null || $chapter['blocks'])
            ->values()
            ->map(fn (array $chapter, int $i) => $chapter + ['id' => 'chapitre-'.($i + 1)])
            ->all();

        // Temps de lecture : seuls les mots des paragraphes comptent (ni « # », ni « * * * », ni les titres).
        $words = 0;
        foreach ($chapters as $chapter) {
            foreach ($chapter['blocks'] as $block) {
                foreach ($block['lines'] ?? [] as $line) {
                    $words += count(array_filter(
                        preg_split('/\s+/u', $line, -1, PREG_SPLIT_NO_EMPTY),
                        fn (string $token) => (bool) preg_match('/[\pL\pN]/u', $token),
                    ));
                }
            }
        }

        return ['chapters' => $chapters, 'words' => $words];
    }

    /**
     * Extrait le texte d'un fichier DOCX ; les titres Word (Titre 1, Heading 2…) deviennent des chapitres « # ».
     */
    public static function fromDocx(string $path): ?string
    {
        if (! class_exists(ZipArchive::class)) {
            return null;
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return null;
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! $xml || ! preg_match_all('/<w:p[ >].*?<\/w:p>|<w:p\/>/s', $xml, $paragraphs)) {
            return null;
        }

        $out = [];
        foreach ($paragraphs[0] as $paragraph) {
            $paragraph = str_replace(['<w:br/>', '<w:br />', '<w:cr/>'], "\n", $paragraph);
            $paragraph = str_replace('<w:tab/>', "\t", $paragraph);
            preg_match_all('/<w:t(?:\s[^>]*)?>(.*?)<\/w:t>|\n|\t/s', $paragraph, $runs, PREG_SET_ORDER);
            $text = '';
            foreach ($runs as $run) {
                $text .= isset($run[1]) ? $run[1] : $run[0];
            }
            $text = trim(html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8'));

            if ($text === '') {
                continue;
            }

            $isHeading = preg_match('/<w:pStyle w:val="(?:Heading|Titre|Title|Titolo|berschrift)\s?\d?"/i', $paragraph)
                || preg_match('/<w:outlineLvl w:val="[0-2]"/', $paragraph);

            $out[] = $isHeading ? '# '.Str::squish($text) : $text;
        }

        return $out ? implode("\n\n", $out) : null;
    }
}
