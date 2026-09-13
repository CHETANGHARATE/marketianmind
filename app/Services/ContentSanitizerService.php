<?php

namespace App\Services;

class ContentSanitizerService
{
    /**
     * Whitelisted HTML tags allowed in article content.
     */
    protected static array $allowedTags = [
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'p', 'br', 'hr',
        'b', 'strong', 'i', 'em', 'u', 's', 'strike', 'sub', 'sup', 'mark',
        'ul', 'ol', 'li',
        'blockquote', 'q', 'cite',
        'pre', 'code', 'kbd',
        'a',
        'img', 'figure', 'figcaption',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
        'div', 'span',
    ];

    /**
     * Whitelisted attributes allowed on elements.
     */
    protected static array $allowedAttributes = [
        'href', 'src', 'alt', 'title', 'target', 'rel', 'class', 'id',
        'width', 'height', 'align', 'valign', 'colspan', 'rowspan',
    ];

    /**
     * Dangerous protocols to strip or neutralize.
     */
    protected static array $dangerousProtocols = [
        'javascript:',
        'vbscript:',
        'data:text/html',
        'data:application',
    ];

    /**
     * Sanitize HTML content to prevent XSS while preserving legitimate formatting.
     */
    public function sanitize(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // 1. Remove dangerous blocks entirely (script, style, iframe, object, embed, etc.)
        $cleaned = preg_replace('/<(script|style|iframe|object|embed|applet|form|input|button|meta|link)[^>]*?>.*?<\/\\1>/is', '', $html);
        $cleaned = preg_replace('/<(script|style|iframe|object|embed|applet|form|input|button|meta|link)[^>]*?>/is', '', $cleaned);

        // 2. Remove inline event handlers (onload, onclick, onerror, onmouseover, etc.)
        $cleaned = preg_replace('/(\s+on[a-z]+)\s*=\s*(["\'][^"\']*["\']|[^\s>]+)/i', '', $cleaned);

        // 3. Remove javascript: / vbscript: / data: schemes in href, src, etc.
        $cleaned = preg_replace_callback('/(href|src)\s*=\s*(["\'])(.*?)\2/i', function ($matches) {
            $attr = $matches[1];
            $quote = $matches[2];
            $val = trim($matches[3]);

            foreach (self::$dangerousProtocols as $protocol) {
                if (stripos(str_replace([' ', "\t", "\n", "\r"], '', $val), $protocol) === 0) {
                    return "{$attr}={$quote}#{$quote}";
                }
            }

            // Ensure links with target="_blank" have rel="noopener noreferrer"
            return "{$attr}={$quote}{$val}{$quote}";
        }, $cleaned);

        // 4. Strip tags not in the allowed list
        $tagsList = '<' . implode('><', self::$allowedTags) . '>';
        $cleaned = strip_tags($cleaned, $tagsList);

        // 5. Ensure all <a> tags with target="_blank" have rel="noopener noreferrer"
        $cleaned = preg_replace_callback('/<a\b([^>]*)>/i', function ($matches) {
            $attrs = $matches[1];
            if (stripos($attrs, 'target="_blank"') !== false || stripos($attrs, "target='_blank'") !== false) {
                if (stripos($attrs, 'rel=') === false) {
                    $attrs .= ' rel="noopener noreferrer"';
                } elseif (stripos($attrs, 'noopener') === false) {
                    $attrs = preg_replace('/rel=["\']([^"\']*)["\']/i', 'rel="$1 noopener noreferrer"', $attrs);
                }
            }
            return "<a{$attrs}>";
        }, $cleaned);

        return trim($cleaned);
    }
}
