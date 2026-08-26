<?php

namespace App\Services\LessonPlanning;

class StructuredNoteRenderer
{
    public function toHtml(array $note): string
    {
        $week = trim((string) ($note['week'] ?? ''));
        $lesson = trim((string) ($note['lesson'] ?? '1'));
        $topic = (string) ($note['topic'] ?? 'Lesson Note');
        $subtopics = $note['sub_topics'] ?? $note['subtopics'] ?? [];

        $html = '<header class="lesson-note-heading">'
            .'<div class="lesson-note-week">WEEK '.e($week !== '' ? $week : '—').' | LESSON '.e($lesson).'</div>'
            .'<h1><span>TOPIC:</span> '.e($topic).'</h1>';

        if ($subtopics) {
            $html .= '<h2>SUB-TOPICS:</h2><ul class="lesson-note-subtopics">'.$this->items($subtopics).'</ul>';
        }
        $html .= '</header>';

        foreach ($note['sections'] ?? [] as $section) {
            $html .= '<section class="lesson-note-section"><h2>'.e($section['heading'] ?? '').'</h2>';
            if (! empty($section['subheading'])) {
                $html .= '<h3>'.e($section['subheading']).'</h3>';
            }
            foreach ($section['content_blocks'] ?? [] as $block) {
                $html .= $this->block($block);
            }
            $html .= '</section>';
        }

        $html .= '<section class="lesson-note-closing"><h2>EVALUATION</h2><ul>'.$this->items($note['evaluation'] ?? []).'</ul>'
            .'<h2>ASSIGNMENT</h2><p>'.nl2br(e($note['assignment'] ?? '')).'</p>'
            .'<div class="lesson-note-reading"><h2>READING ASSIGNMENT</h2><p>'.nl2br(e($note['reading_assignment'] ?? '')).'</p></div></section>';

        return $html;
    }

    private function block(array $block): string
    {
        return match ($block['type'] ?? '') {
            'paragraph' => '<p>'.nl2br(e($block['content'] ?? $block['text'] ?? $block['description'] ?? '')).'</p>',
            'bullets' => '<ul>'.$this->items($block['items'] ?? []).'</ul>',
            'table' => $this->table($block),
            'worked_example' => '<div class="worked-example"><strong>'.e($block['title'] ?? 'Worked Example').'</strong><p>'.e($block['problem'] ?? '').'</p><p><em>Solution:</em> '.nl2br(e($block['solution'] ?? '')).'</p></div>',
            'diagram' => $this->diagram($block),
            default => '',
        };
    }

    private function diagram(array $block): string
    {
        $labels = collect($block['labels'] ?? [])->filter(fn ($label) => is_scalar($label) && trim((string) $label) !== '')->take(8)->values();
        $title = (string) ($block['title'] ?? 'Study diagram');
        $caption = (string) ($block['caption'] ?? $title);
        $description = trim((string) ($block['description'] ?? ''));
        $height = max(180, 80 + ($labels->count() * 68));
        $nodes = '';

        foreach ($labels as $index => $label) {
            $y = 34 + ($index * 68);
            if ($index > 0) {
                $lineStart = $y - 24;
                $nodes .= '<line x1="350" y1="'.($lineStart - 18).'" x2="350" y2="'.$lineStart.'" stroke="#1d4ed8" stroke-width="3" marker-end="url(#noteArrow)"/>';
            }
            $nodes .= '<rect x="145" y="'.$y.'" width="410" height="46" rx="12" fill="'.($index % 2 === 0 ? '#eff6ff' : '#f8fafc').'" stroke="#1d4ed8" stroke-width="2"/>'
                .'<circle cx="172" cy="'.($y + 23).'" r="14" fill="#0f2f61"/>'
                .'<text x="172" y="'.($y + 28).'" text-anchor="middle" font-family="Arial,sans-serif" font-size="13" font-weight="700" fill="#fff">'.($index + 1).'</text>'
                .'<text x="198" y="'.($y + 29).'" font-family="Arial,sans-serif" font-size="16" font-weight="600" fill="#0f172a">'.e((string) $label).'</text>';
        }

        return '<figure class="lesson-note-diagram"><svg viewBox="0 0 700 '.$height.'" role="img" aria-label="'.e($title).'" xmlns="http://www.w3.org/2000/svg">'
            .'<title>'.e($title).'</title><defs><marker id="noteArrow" markerWidth="8" markerHeight="8" refX="4" refY="4" orient="auto"><path d="M0,0 L8,4 L0,8 z" fill="#1d4ed8"/></marker></defs>'
            .$nodes.'</svg><figcaption>'.e($caption).'</figcaption>'
            .($description !== '' ? '<p class="lesson-note-diagram-description">'.nl2br(e($description)).'</p>' : '').'</figure>';
    }

    private function items(array $items): string
    {
        return collect($items)->map(fn ($item) => '<li>'.e(is_array($item) ? json_encode($item, JSON_UNESCAPED_UNICODE) : $item).'</li>')->implode('');
    }

    private function table(array $block): string
    {
        $head = collect($block['headers'] ?? [])->map(fn ($value) => '<th>'.e($value).'</th>')->implode('');
        $rows = collect($block['rows'] ?? [])->map(fn ($row) => '<tr>'.collect($row)->map(fn ($value) => '<td>'.e($value).'</td>')->implode('').'</tr>')->implode('');
        return '<div class="lesson-note-table-wrap"><table><thead><tr>'.$head.'</tr></thead><tbody>'.$rows.'</tbody></table></div>';
    }
}
