<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Assainit le « texte riche » saisi dans l'administration (réglages, pages éditables…).
 *
 *   RichText::clean('<p onclick="x()">Bonjour <script>…</script><b>toi</b></p>')
 *   // → '<p>Bonjour <strong>toi</strong></p>'
 *
 * Liste blanche stricte, sans dépendance externe (DOMDocument) :
 * - balises gardées : p, br, strong, em, a, ul, ol, li, h2, h3, blockquote
 *   (b → strong et i → em sont convertis) ;
 * - attributs : aucun, sauf href sur <a>, limité à http://, https:// et mailto: ;
 *   un lien sans adresse valide est remplacé par son texte ;
 * - balises dangereuses (script, style, iframe, svg, formulaires…) : supprimées avec leur contenu ;
 * - autres balises inconnues (div, span, font…) : retirées mais leur texte est gardé ;
 * - commentaires HTML : supprimés.
 *
 * Un texte sans aucune balise est converti en paragraphes (ligne vide = nouveau paragraphe,
 * retour à la ligne simple = <br>), pour qu'un administrateur puisse écrire sans HTML.
 *
 * Le résultat est sûr à afficher avec {!! !!}. Appeler clean() à l'enregistrement ET à l'affichage
 * ne coûte presque rien et protège les valeurs écrites avant l'assainissement.
 */
class RichText
{
    /** Balises autorisées telles quelles. */
    public const ALLOWED_TAGS = ['p', 'br', 'strong', 'em', 'a', 'ul', 'ol', 'li', 'h2', 'h3', 'blockquote'];

    /** Balises converties vers leur équivalent autorisé. */
    private const RENAMED_TAGS = ['b' => 'strong', 'i' => 'em'];

    /** Balises supprimées AVEC leur contenu. */
    private const DROPPED_TAGS = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet', 'template', 'noscript',
        'svg', 'math', 'head', 'title', 'meta', 'link', 'base', 'form', 'input', 'button', 'select', 'option',
        'textarea', 'img', 'video', 'audio', 'source', 'track', 'canvas', 'picture',
    ];

    /** Schémas d'URL autorisés pour href. */
    private const ALLOWED_SCHEMES = ['http', 'https', 'mailto'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        if (strip_tags($html) === $html) {
            return self::fromPlainText($html);
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        // L'en-tête XML force la lecture en UTF-8 ; la racine <div> regroupe les nœuds de premier niveau.
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="richtext-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('richtext-root');

        if (! $root) {
            return '';
        }

        self::cleanChildren($root);

        $output = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    /**
     * Texte brut (sans balise) d'un contenu riche, pour une meta description ou un extrait.
     */
    public static function toPlainText(?string $html): string
    {
        $text = html_entity_decode(strip_tags(preg_replace('#<(br|/p|/li|/h[23]|/blockquote)\b[^>]*>#i', ' ', (string) $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    private static function fromPlainText(string $text): string
    {
        $paragraphs = preg_split('/\R{2,}/u', str_replace("\r\n", "\n", $text));

        return collect($paragraphs)
            ->map(fn ($paragraph) => trim($paragraph))
            ->filter(fn ($paragraph) => $paragraph !== '')
            ->map(fn ($paragraph) => '<p>'.nl2br(e($paragraph), false).'</p>')
            ->implode("\n");
    }

    private static function cleanChildren(DOMNode $parent): void
    {
        // Copie de la liste : on modifie l'arbre pendant le parcours.
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node->nodeType === XML_TEXT_NODE) {
                continue;
            }

            if (! $node instanceof DOMElement) {
                // Commentaires, CDATA, instructions de traitement…
                $parent->removeChild($node);

                continue;
            }

            $tag = strtolower($node->nodeName);

            if (in_array($tag, self::DROPPED_TAGS, true)) {
                $parent->removeChild($node);

                continue;
            }

            if (isset(self::RENAMED_TAGS[$tag])) {
                $node = self::rename($node, self::RENAMED_TAGS[$tag]);
                $tag = self::RENAMED_TAGS[$tag];
            }

            // On nettoie d'abord les enfants (le contenu d'une balise retirée est remonté déjà propre).
            self::cleanChildren($node);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                self::unwrap($node);

                continue;
            }

            $href = $tag === 'a' ? self::safeHref($node->getAttribute('href')) : null;

            foreach (iterator_to_array($node->attributes) as $attribute) {
                $node->removeAttribute($attribute->nodeName);
            }

            if ($tag === 'a') {
                if ($href === null) {
                    self::unwrap($node);

                    continue;
                }

                $node->setAttribute('href', $href);
            }
        }
    }

    private static function safeHref(string $href): ?string
    {
        // Retire les caractères invisibles et de contrôle utilisés pour masquer « javascript: ».
        $href = trim(preg_replace('/[\x00-\x20\x7F]+/u', '', html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        if ($href === '' || ! preg_match('/^([a-z][a-z0-9+.-]*):/i', $href, $matches)) {
            return null;
        }

        if (! in_array(strtolower($matches[1]), self::ALLOWED_SCHEMES, true)) {
            return null;
        }

        return strtolower($matches[1]) === 'mailto' || filter_var($href, FILTER_VALIDATE_URL) ? $href : null;
    }

    private static function rename(DOMElement $node, string $tag): DOMElement
    {
        $replacement = $node->ownerDocument->createElement($tag);

        while ($node->firstChild) {
            $replacement->appendChild($node->firstChild);
        }

        $node->parentNode->replaceChild($replacement, $node);

        return $replacement;
    }

    private static function unwrap(DOMElement $node): void
    {
        $parent = $node->parentNode;

        while ($node->firstChild) {
            $parent->insertBefore($node->firstChild, $node);
        }

        $parent->removeChild($node);
    }
}
