<?php

namespace App\Support;

final class EduCoreRichText
{
    /**
     * Render EduCore's intentionally small Markdown subset.
     *
     * Supported block syntax:
     * # H1, ## H2, ### H3, - bullets, 1. numbered lists.
     * Supported inline syntax:
     * **bold**, *italic*, [label](https://example.com).
     *
     * Raw HTML is always escaped before formatting is applied.
     */
    public static function render(?string $markdown): string
    {
        $lines = preg_split('/\R/u', (string) $markdown) ?: [];
        $html = [];
        $list = null;

        $closeList = static function () use (&$html, &$list): void {
            if ($list !== null) {
                $html[] = "</{$list}>";
                $list = null;
            }
        };

        foreach ($lines as $line) {
            if (preg_match('/^\s*###\s+(.+)$/u', $line, $match)) {
                $closeList();
                $html[] = '<h3>'.self::inline($match[1]).'</h3>';
                continue;
            }

            if (preg_match('/^\s*##\s+(.+)$/u', $line, $match)) {
                $closeList();
                $html[] = '<h2>'.self::inline($match[1]).'</h2>';
                continue;
            }

            if (preg_match('/^\s*#\s+(.+)$/u', $line, $match)) {
                $closeList();
                $html[] = '<h1>'.self::inline($match[1]).'</h1>';
                continue;
            }

            if (preg_match('/^\s*[-*]\s+(.+)$/u', $line, $match)) {
                if ($list !== 'ul') {
                    $closeList();
                    $list = 'ul';
                    $html[] = '<ul>';
                }
                $html[] = '<li>'.self::inline($match[1]).'</li>';
                continue;
            }

            if (preg_match('/^\s*\d+[.)]\s+(.+)$/u', $line, $match)) {
                if ($list !== 'ol') {
                    $closeList();
                    $list = 'ol';
                    $html[] = '<ol>';
                }
                $html[] = '<li>'.self::inline($match[1]).'</li>';
                continue;
            }

            $closeList();

            if (trim($line) === '') {
                $html[] = '<div class="edu-rich-spacer" aria-hidden="true"></div>';
                continue;
            }

            $html[] = '<p>'.self::inline($line).'</p>';
        }

        $closeList();

        return implode("\n", $html);
    }

    public static function plainText(?string $markdown): string
    {
        $text = (string) $markdown;
        $text = preg_replace('/^\\s*#{1,3}\\s+/mu', '', $text) ?? $text;
        $text = preg_replace('/^\\s*[-*]\\s+/mu', '• ', $text) ?? $text;
        $text = preg_replace('/^\\s*\\d+[.)]\\s+/mu', '', $text) ?? $text;
        $text = preg_replace('/\\[([^]\\n]+)]\\(([^\\s)]+)\\)/u', '$1', $text) ?? $text;
        $text = preg_replace('/\\*\\*([^*\\n]+)\\*\\*/u', '$1', $text) ?? $text;
        $text = preg_replace('/(?<!\\*)\\*([^*\\n]+)\\*(?!\\*)/u', '$1', $text) ?? $text;

        return trim($text);
    }

    private static function inline(string $text): string
    {
        $escaped = e($text);

        $escaped = preg_replace_callback(
            '/\[([^\]\n]+)]\(([^\s)]+)\)/u',
            static function (array $match): string {
                $label = $match[1];
                $url = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

                if (! in_array($scheme, ['http', 'https', 'mailto'], true)) {
                    return $label.' ('.e($url).')';
                }

                return '<a href="'.e($url).'" target="_blank" rel="noopener noreferrer">'.$label.'</a>';
            },
            $escaped,
        ) ?? $escaped;

        $escaped = preg_replace('/\*\*([^*\n]+)\*\*/u', '<strong>$1</strong>', $escaped) ?? $escaped;
        $escaped = preg_replace('/(?<!\*)\*([^*\n]+)\*(?!\*)/u', '<em>$1</em>', $escaped) ?? $escaped;

        return $escaped;
    }
}
