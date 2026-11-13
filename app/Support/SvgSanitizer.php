<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use Throwable;

/**
 * Assainit un emblème SVG saisi dans l'admin : on ne garde que des formes simples
 * (path, circle, ellipse, rect, line, polyline, polygon, g) et leurs attributs géométriques
 * ou de présentation. Tout le reste disparaît : script, style, foreignObject, image, use, a,
 * attributs on*, href, url(…), entités, texte…
 *
 * Le résultat est reconstruit à la main (jamais recopié tel quel) :
 *   <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120">…formes…</svg>
 */
class SvgSanitizer
{
    public const DEFAULT_VIEWBOX = '0 0 120 120';

    public const ELEMENTS = ['path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon', 'g'];

    private const NUMBER = '/^[-+]?(\d+\.?\d*|\.\d+)([eE][-+]?\d+)?(%|px)?$/';

    private const NUMBER_LIST = '/^[-+0-9eE.,\s]+$/';

    private const PATH = '/^[MmLlHhVvCcSsQqTtAaZz0-9eE.,\s+\-]+$/';

    private const TRANSFORM = '/^((matrix|translate|scale|rotate|skewX|skewY)\s*\([-+0-9eE.,\s]*\)[\s,]*)+$/';

    private const PAINT = '/^(none|currentColor|transparent|#[0-9a-fA-F]{3,8}|[a-zA-Z]{3,20}|rgba?\(\s*[0-9.%\s,]+\))$/';

    private const KEYWORD = '/^[a-zA-Z\-]{1,20}$/';

    /**
     * Attribut autorisé => motif de validation de sa valeur.
     */
    private const ATTRIBUTES = [
        'd' => self::PATH,
        'cx' => self::NUMBER, 'cy' => self::NUMBER, 'r' => self::NUMBER,
        'rx' => self::NUMBER, 'ry' => self::NUMBER,
        'x' => self::NUMBER, 'y' => self::NUMBER,
        'x1' => self::NUMBER, 'y1' => self::NUMBER, 'x2' => self::NUMBER, 'y2' => self::NUMBER,
        'width' => self::NUMBER, 'height' => self::NUMBER,
        'points' => self::NUMBER_LIST,
        'pathLength' => self::NUMBER,
        'transform' => self::TRANSFORM,
        'fill' => self::PAINT,
        'stroke' => self::PAINT,
        'fill-rule' => self::KEYWORD,
        'clip-rule' => self::KEYWORD,
        'stroke-width' => self::NUMBER,
        'stroke-linecap' => self::KEYWORD,
        'stroke-linejoin' => self::KEYWORD,
        'stroke-miterlimit' => self::NUMBER,
        'stroke-dasharray' => self::NUMBER_LIST,
        'stroke-dashoffset' => self::NUMBER,
        'opacity' => self::NUMBER,
        'fill-opacity' => self::NUMBER,
        'stroke-opacity' => self::NUMBER,
        'vector-effect' => self::KEYWORD,
    ];

    // Présentation reprise de la balise <svg> d'origine (posée sur un <g> englobant).
    private const ROOT_PRESENTATION = ['fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'opacity', 'transform'];

    /**
     * SVG complet assaini, ou null s'il ne reste aucune forme.
     */
    public static function clean(?string $svg): ?string
    {
        $parts = self::parts($svg);

        if ($parts === null) {
            return null;
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="'.$parts['viewBox'].'">'.$parts['inner'].'</svg>';
    }

    /**
     * ['viewBox' => '0 0 120 120', 'inner' => '<path …/>…'] ou null s'il ne reste aucune forme.
     *
     * @return array{viewBox: string, inner: string}|null
     */
    public static function parts(?string $svg): ?array
    {
        $svg = trim((string) $svg);

        if ($svg === '' || strlen($svg) > 50_000) {
            return null;
        }

        // Ni déclaration, ni DOCTYPE / entités, ni commentaires, ni CDATA.
        $svg = preg_replace([
            '/<\?xml.*?\?>/is',
            '/<!DOCTYPE[^\[>]*(\[.*?\])?\s*>/is',
            '/<!ENTITY.*?>/is',
            '/<!--.*?-->/s',
            '/<!\[CDATA\[.*?\]\]>/s',
        ], '', $svg);

        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadXML('<root>'.$svg.'</root>', LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT);
        } catch (Throwable) {
            $loaded = false;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (! $loaded || ! $document->documentElement) {
            return null;
        }

        $viewBox = self::DEFAULT_VIEWBOX;
        $container = $document->documentElement;
        $wrapper = '';

        // Un <svg> complet : on garde sa viewBox (si elle est valide) et sa présentation.
        $roots = array_values(array_filter(iterator_to_array($container->childNodes), fn (DOMNode $node) => $node instanceof DOMElement));

        if (count($roots) === 1 && strtolower($roots[0]->localName) === 'svg') {
            $root = $roots[0];
            $candidate = trim(preg_replace('/[\s,]+/', ' ', $root->getAttribute('viewBox')));

            if (preg_match('/^-?\d+(\.\d+)? -?\d+(\.\d+)? \d+(\.\d+)? \d+(\.\d+)?$/', $candidate)) {
                $viewBox = $candidate;
            }

            $wrapper = self::attributes($root, self::ROOT_PRESENTATION);
            $container = $root;
        }

        $inner = self::children($container, 0);

        if ($inner === '') {
            return null;
        }

        return ['viewBox' => $viewBox, 'inner' => $wrapper !== '' ? '<g'.$wrapper.'>'.$inner.'</g>' : $inner];
    }

    private static function children(DOMNode $parent, int $depth): string
    {
        if ($depth > 12) {
            return '';
        }

        $html = '';

        foreach ($parent->childNodes as $node) {
            // Texte, commentaires, instructions : ignorés.
            if (! $node instanceof DOMElement) {
                continue;
            }

            $name = $node->localName;

            // Balise hors liste blanche (script, style, foreignObject, a, use, image, svg imbriqué…) :
            // supprimée avec tout son contenu.
            if (! in_array($name, self::ELEMENTS, true) || ($node->namespaceURI && $node->namespaceURI !== 'http://www.w3.org/2000/svg')) {
                continue;
            }

            $attributes = self::attributes($node, array_keys(self::ATTRIBUTES));

            if ($name === 'g') {
                $content = self::children($node, $depth + 1);

                if ($content !== '') {
                    $html .= '<g'.$attributes.'>'.$content.'</g>';
                }

                continue;
            }

            $html .= '<'.$name.$attributes.'/>';
        }

        return $html;
    }

    /**
     * Attributs autorisés et valides, sérialisés (« name="value" »), dans l'ordre d'origine.
     */
    private static function attributes(DOMElement $element, array $allowed): string
    {
        $html = '';

        foreach ($element->attributes as $attribute) {
            // Attributs préfixés (xlink:href, xml:base…) : refusés.
            if ($attribute->prefix !== '' && $attribute->prefix !== null) {
                continue;
            }

            $name = $attribute->localName;

            if (! in_array($name, $allowed, true)) {
                continue;
            }

            $value = trim(preg_replace('/\s+/', ' ', $attribute->value));

            if ($value === '' || strlen($value) > 10_000 || ! preg_match(self::ATTRIBUTES[$name], $value)) {
                continue;
            }

            $html .= ' '.$name.'="'.htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8').'"';
        }

        return $html;
    }
}
