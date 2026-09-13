<?php

namespace App\Support;

class AcademicContentFormatter
{
    private const ALLOWED_TAGS = '<p><br><h2><h3><h4><h5><h6><ul><ol><li><strong><b><em><i><u><blockquote><table><thead><tbody><tfoot><tr><th><td><hr><sup><sub><code><pre>';

    public function render(?string $content, bool $preserveLineBreaks = false): string
    {
        $content = trim((string) $content);

        if ($content === '') {
            return '<p class="reader-empty-paragraph">No readable content is available for this section.</p>';
        }

        if ($this->containsSupportedHtml($content)) {
            return $this->sanitizeHtml($content);
        }

        return $this->formatPlainText($content, $preserveLineBreaks);
    }

    private function containsSupportedHtml(string $content): bool
    {
        return preg_match('/<\s*(?:p|br|h[1-6]|ul|ol|li|strong|b|em|i|u|blockquote|table|thead|tbody|tfoot|tr|th|td|hr|sup|sub|code|pre)\b/i', $content) === 1;
    }

    private function sanitizeHtml(string $html): string
    {
        $html = preg_replace('~<(script|style|iframe|object|embed|form)[^>]*>.*?</\\1>~is', '', $html) ?? '';
        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? '';
        $html = strip_tags($html, self::ALLOWED_TAGS);

        $html = preg_replace_callback(
            '/<\s*(\/?)\s*([a-z0-9]+)(?:\s[^>]*)?>/i',
            static function (array $matches): string {
                $closing = $matches[1] === '/' ? '/' : '';
                $tag = strtolower($matches[2]);

                return '<'.$closing.$tag.'>';
            },
            $html
        ) ?? '';

        return trim($html);
    }

    private function formatPlainText(string $content, bool $preserveLineBreaks): string
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", $content);
        $html = [];
        $paragraph = [];
        $listType = null;
        $listItems = [];

        $flushParagraph = function () use (&$paragraph, &$html): void {
            if ($paragraph === []) {
                return;
            }

            $text = trim(implode(' ', array_map('trim', $paragraph)));
            if ($text !== '') {
                $html[] = '<p>'.$this->formatInline($text).'</p>';
            }
            $paragraph = [];
        };

        $flushList = function () use (&$listType, &$listItems, &$html): void {
            if ($listType === null || $listItems === []) {
                $listType = null;
                $listItems = [];
                return;
            }

            $items = array_map(fn (string $item): string => '<li>'.$this->formatInline($item).'</li>', $listItems);
            $html[] = '<'.$listType.'>'.implode('', $items).'</'.$listType.'>';
            $listType = null;
            $listItems = [];
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                $flushParagraph();
                $flushList();
                continue;
            }

            if (preg_match('/^(#{1,3})\s+(.+)$/u', $trimmed, $matches) === 1) {
                $flushParagraph();
                $flushList();
                $level = min(4, strlen($matches[1]) + 1);
                $html[] = '<h'.$level.'>'.$this->formatInline($matches[2]).'</h'.$level.'>';
                continue;
            }

            if ($this->looksLikeHeading($trimmed)) {
                $flushParagraph();
                $flushList();
                $html[] = '<h3>'.$this->formatInline(rtrim($trimmed, ':')).'</h3>';
                continue;
            }

            if (preg_match('/^(?:[-*•–—])\s+(.+)$/u', $trimmed, $matches) === 1) {
                $flushParagraph();
                if ($listType !== 'ul') {
                    $flushList();
                    $listType = 'ul';
                }
                $listItems[] = $matches[1];
                continue;
            }

            if (preg_match('/^(?:\d+[.)]|[a-zA-Z][.)])\s+(.+)$/u', $trimmed, $matches) === 1) {
                $flushParagraph();
                if ($listType !== 'ol') {
                    $flushList();
                    $listType = 'ol';
                }
                $listItems[] = $matches[1];
                continue;
            }

            if (preg_match('/^(?:NOTE|NB|N\.B\.|KEY POINT|IMPORTANT)\s*:\s*(.+)$/iu', $trimmed, $matches) === 1) {
                $flushParagraph();
                $flushList();
                $html[] = '<blockquote><strong>Note:</strong> '.$this->formatInline($matches[1]).'</blockquote>';
                continue;
            }

            $flushList();
            $paragraph[] = $trimmed;

            if ($preserveLineBreaks) {
                $flushParagraph();
            }
        }

        $flushParagraph();
        $flushList();

        return implode("\n", $html);
    }

    private function looksLikeHeading(string $line): bool
    {
        $length = mb_strlen($line);
        if ($length < 3 || $length > 90) {
            return false;
        }

        if (str_ends_with($line, ':') && substr_count($line, ' ') <= 10) {
            return true;
        }

        $letters = preg_replace('/[^\pL]+/u', '', $line) ?? '';
        if ($letters === '') {
            return false;
        }

        return mb_strtoupper($letters) === $letters && substr_count($line, ' ') <= 10;
    }

    private function formatInline(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/(?<!\*)\*([^*\n]+)\*(?!\*)/u', '<em>$1</em>', $text) ?? $text;

        return $text;
    }
}
