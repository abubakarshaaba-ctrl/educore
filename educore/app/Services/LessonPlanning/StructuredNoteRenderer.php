<?php

namespace App\Services\LessonPlanning;

class StructuredNoteRenderer
{
    public function toHtml(array $note): string
    {
        $html = '<h2>'.e($note['title'] ?? 'Lesson Note').'</h2><p>'.nl2br(e($note['overview'] ?? '')).'</p>';
        foreach ($note['sections'] ?? [] as $section) {
            $html .= '<h2>'.e($section['heading'] ?? '').'</h2>';
            if (! empty($section['subheading'])) $html .= '<h3>'.e($section['subheading']).'</h3>';
            foreach ($section['content_blocks'] ?? [] as $block) $html .= $this->block($block);
        }
        if ($points = ($note['key_examination_points'] ?? [])) $html .= '<div class="key-points"><h2>Key Examination Points</h2><ul>'.$this->items($points).'</ul></div>';
        foreach (['objective'=>'Objective Questions','structured'=>'Structured Questions','application'=>'Application Questions'] as $key => $heading) {
            $questions = $note['review_questions'][$key] ?? [];
            if ($questions) $html .= '<h2>'.e($heading).'</h2><ol>'.$this->items($questions).'</ol>';
        }
        return $html.'<h2>Summary</h2><p>'.nl2br(e($note['summary'] ?? '')).'</p>';
    }

    private function block(array $block): string
    {
        return match ($block['type'] ?? '') {
            'paragraph' => '<p>'.nl2br(e($block['content'] ?? '')).'</p>',
            'bullets' => '<ul>'.$this->items($block['items'] ?? []).'</ul>',
            'table' => $this->table($block),
            'worked_example' => '<div class="exam-question"><strong>'.e($block['title'] ?? 'Worked Example').'</strong><p>'.e($block['problem'] ?? '').'</p><p><em>Solution:</em> '.nl2br(e($block['solution'] ?? '')).'</p></div>',
            'diagram' => '<figure style="border:1px dashed #94a3b8;padding:24px"><strong>Diagram placeholder: '.e($block['title'] ?? '').'</strong><p>Labels: '.e(implode(', ', $block['labels'] ?? [])).'</p></figure>',
            default => '',
        };
    }

    private function items(array $items): string { return collect($items)->map(fn ($item) => '<li>'.e(is_array($item) ? json_encode($item, JSON_UNESCAPED_UNICODE) : $item).'</li>')->implode(''); }
    private function table(array $block): string
    {
        $head = collect($block['headers'] ?? [])->map(fn ($v) => '<th>'.e($v).'</th>')->implode('');
        $rows = collect($block['rows'] ?? [])->map(fn ($row) => '<tr>'.collect($row)->map(fn ($v) => '<td>'.e($v).'</td>')->implode('').'</tr>')->implode('');
        return '<table><thead><tr>'.$head.'</tr></thead><tbody>'.$rows.'</tbody></table>';
    }
}
