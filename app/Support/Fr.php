<?php

namespace App\Support;

use Illuminate\Support\Str;

class Fr
{
    /**
     * "0 œuvre", "1 œuvre", "5 œuvres" (règle française : pluriel à partir de 2).
     */
    public static function count(int $count, string $singular, ?string $plural = null): string
    {
        return $count.' '.($count >= 2 ? ($plural ?? $singular.'s') : $singular);
    }

    /**
     * Élision de « de » devant une voyelle : "de Kassi", "d’Awa", "d’Émile".
     */
    public static function de(string $name): string
    {
        return (self::startsWithVowel($name) ? 'd’' : 'de ').$name;
    }

    /**
     * Seulement l'article (« de » ou « d’ »), quand le nom est affiché dans un autre élément.
     */
    public static function deArticle(string $name): string
    {
        return self::startsWithVowel($name) ? 'd’' : 'de';
    }

    private static function startsWithVowel(string $name): bool
    {
        // Le « h » est ignoré : muet ou aspiré, on ne peut pas trancher pour un nom propre.
        $first = Str::lower(Str::ascii(mb_substr(ltrim($name), 0, 1)));

        return in_array($first, ['a', 'e', 'i', 'o', 'u', 'y'], true)
            || mb_substr(Str::lower(ltrim($name)), 0, 1) === 'œ';
    }
}
