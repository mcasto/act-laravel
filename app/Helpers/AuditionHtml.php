<?php

namespace App\Helpers;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Cleans audition-notice HTML submitted from the director page before it's
 * saved. That HTML is rendered with v-html on the public audition page (and
 * in the admin), and production-team members are a much larger, short-lived
 * group than admins — so anything that could run code is stripped, while
 * the layout and CSS (including a <style> block) that the AI-generated
 * notices rely on are kept.
 *
 * Removed: <script>, frames/embeds/objects, forms and form controls, <link>,
 * <meta>, <base>, <svg>/<math>, every on* event attribute, javascript:/
 * vbscript: URLs (and data: URLs except images), and CSS that can load or run
 * code (@import, expression(), javascript:/behavior/-moz-binding).
 */
class AuditionHtml
{
    private const REMOVE_ELEMENTS = [
        'script', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet',
        'form', 'input', 'button', 'textarea', 'select', 'option',
        'link', 'meta', 'base', 'svg', 'math', 'noscript', 'template',
    ];

    private const URL_ATTRIBUTES = ['href', 'src', 'action', 'formaction', 'xlink:href', 'background', 'poster', 'srcset'];

    public static function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        $previous = libxml_use_internal_errors(true); // HTML5 tags (section, footer…) warn
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML(
            '<?xml encoding="utf-8"?><div id="__audition_root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($doc);

        foreach (self::REMOVE_ELEMENTS as $tag) {
            foreach (iterator_to_array($doc->getElementsByTagName($tag)) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        foreach (iterator_to_array($xpath->query('//*')) as $element) {
            /** @var DOMElement $element */
            foreach (iterator_to_array($element->attributes) as $attribute) {
                $name = strtolower($attribute->nodeName);
                $value = $attribute->nodeValue ?? '';

                if (str_starts_with($name, 'on')) {
                    $element->removeAttribute($attribute->nodeName);
                } elseif (in_array($name, self::URL_ATTRIBUTES, true) && self::isDangerousUrl($value, $element->nodeName)) {
                    $element->removeAttribute($attribute->nodeName);
                } elseif ($name === 'style') {
                    $element->setAttribute('style', self::cleanCss($value));
                }
            }

            if ($element->nodeName === 'style') {
                $element->textContent = self::cleanCss($element->textContent);
            }
        }

        $root = $doc->getElementById('__audition_root');
        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    private static function isDangerousUrl(string $value, string $tag): bool
    {
        // Browsers ignore whitespace/control characters inside the scheme.
        $normalized = strtolower(preg_replace('/[\x00-\x20]+/', '', html_entity_decode($value)));

        if (str_starts_with($normalized, 'javascript:') || str_starts_with($normalized, 'vbscript:')) {
            return true;
        }

        return str_starts_with($normalized, 'data:')
            && ! ($tag === 'img' && str_starts_with($normalized, 'data:image/') && ! str_starts_with($normalized, 'data:image/svg'));
    }

    private static function cleanCss(string $css): string
    {
        $css = preg_replace('/@import[^;]*;?/i', '', $css);
        $css = preg_replace('/expression\s*\(/i', 'blocked(', $css);
        $css = preg_replace('/(javascript|vbscript)\s*:/i', 'blocked:', $css);
        $css = preg_replace('/(-moz-binding|behavior)\s*:/i', 'blocked:', $css);

        return str_replace('</', '', $css);
    }
}
