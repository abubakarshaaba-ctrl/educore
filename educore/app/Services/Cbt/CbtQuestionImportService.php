<?php

namespace App\Services\Cbt;

use App\Models\CbtExam;
use App\Models\CbtImportBatch;
use App\Models\CbtQuestion;
use App\Models\CbtQuestionBank;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CbtQuestionImportService
{
    public const HEADERS = [
        'section_code', 'section_name', 'question_no', 'parent_question_no', 'question_level',
        'question_type', 'question_text', 'option_a', 'option_b', 'option_c', 'option_d',
        'correct_option', 'marks', 'scoring_method', 'answer_mode', 'display_order',
        'instructions', 'is_required', 'model_answer',
    ];

    public function preview(CbtQuestionBank $bank, UploadedFile $file, int $userId, ?CbtExam $exam = null): CbtImportBatch
    {
        abort_if($exam && ((int) $exam->question_bank_id !== (int) $bank->id || $exam->status !== 'draft'), 422, 'Select a draft exam that uses this question bank.');
        $rows = $this->read($file);
        $errors = $this->validateRows($rows, $exam);
        return CbtImportBatch::create([
            'tenant_id' => $bank->tenant_id, 'question_bank_id' => $bank->id, 'cbt_exam_id' => $exam?->id,
            'uploaded_by' => $userId, 'original_name' => $file->getClientOriginalName(),
            'status' => $errors ? 'invalid' : 'preview', 'rows' => $rows,
            'validation_errors' => $errors, 'row_count' => count($rows),
        ]);
    }

    public function import(CbtImportBatch $batch): int
    {
        abort_if($batch->status !== 'preview' || $batch->validation_errors, 422, 'This batch has validation errors and cannot be imported.');
        return DB::transaction(function () use ($batch) {
            $created = [];
            $exam = $batch->cbt_exam_id ? CbtExam::findOrFail($batch->cbt_exam_id) : null;
            $sections = $exam?->sections()->get()->keyBy(fn ($section) => strtoupper($section->code)) ?? collect();
            $remaining = collect($batch->rows)->keyBy(fn (array $row) => $this->rowKey($row));
            while ($remaining->isNotEmpty()) {
                $progress = false;
                foreach ($remaining as $key => $row) {
                    $reference = trim((string) $row['reference']);
                    $parentRef = trim((string) $row['parent_reference']);
                    $parentKey = $this->rowKey($row, $parentRef);
                    if ($parentRef !== '' && ! isset($created[$parentKey])) continue;
                    $parent = $parentRef !== '' ? $created[$parentKey] : null;
                    $instruction = $this->bool($row['is_instruction_only']);
                    $question = CbtQuestion::create([
                        'tenant_id' => $batch->tenant_id, 'question_bank_id' => $batch->question_bank_id,
                        'parent_question_id' => $parent?->id, 'level' => $parent ? $parent->level + 1 : 0,
                        'sequence' => (int) $row['display_order'], 'reference_code' => $reference,
                        'type' => $row['type'], 'question_text' => $row['question_text'],
                        'option_a' => $row['option_a'] ?: ($row['type'] === 'true_false' ? 'True' : null),
                        'option_b' => $row['option_b'] ?: ($row['type'] === 'true_false' ? 'False' : null),
                        'option_c' => $row['option_c'] ?: null, 'option_d' => $row['option_d'] ?: null,
                        'correct_answer_letter' => in_array($row['type'], ['mcq', 'true_false'], true) ? ($row['correct_answer'] ?: null) : null,
                        'marks' => $instruction ? 0 : (float) $row['marks'], 'scoring_method' => $row['scoring_method'] ?: null,
                        'numbering_style' => 'auto', 'is_instruction_only' => $instruction,
                        'requires_answer' => ! $instruction,
                        'model_answer' => $row['model_answer'] ?: ($row['type'] === 'fill_blank' ? ($row['correct_answer'] ?: null) : null),
                    ]);
                    $created[$key] = $question;
                    if ($exam) {
                        $section = $sections->get(strtoupper($row['section_code']));
                        $section->questions()->attach($question->id, [
                            'tenant_id' => $batch->tenant_id, 'cbt_exam_id' => $exam->id,
                            'display_order' => (int) $row['display_order'], 'marks_override' => (float) $row['marks'],
                        ]);
                    }
                    $remaining->forget($key);
                    $progress = true;
                }
                abort_unless($progress, 422, 'Question hierarchy contains a circular or unresolved parent reference.');
            }
            if ($exam) app(CbtExamConfigurationService::class)->recalculateExamTotals($exam);
            $batch->update(['status' => 'imported', 'imported_count' => count($created)]);
            return count($created);
        });
    }

    private function read(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, ['csv', 'txt'], true)) {
            $raw = [];
            $handle = fopen($file->getRealPath(), 'r');
            while (($row = fgetcsv($handle)) !== false) $raw[] = $row;
            fclose($handle);
        } else {
            $raw = IOFactory::load($file->getRealPath())->getActiveSheet()->toArray('', true, true, false);
        }
        if (count($raw) < 2) return [];
        $headers = array_map(fn ($value) => strtolower(trim((string) $value)), array_shift($raw));
        return collect($raw)->filter(fn ($row) => collect($row)->contains(fn ($value) => trim((string) $value) !== ''))->values()->map(function ($row, $index) use ($headers) {
            $source = [];
            foreach ($headers as $column => $header) $source[$header] = trim((string) ($row[$column] ?? ''));
            $value = fn (array $keys, string $default = '') => collect($keys)->map(fn ($key) => $source[$key] ?? null)->first(fn ($item) => $item !== null && $item !== '') ?? $default;
            $rawType = strtolower($value(['question_type', 'type'], 'mcq'));
            $type = match ($rawType) {
                'single_choice', 'multiple_choice', 'objective' => 'mcq',
                'theory', 'theory_group' => 'essay',
                'short', 'shortanswer' => 'short_answer',
                'fill_in_blank' => 'fill_blank',
                default => $rawType,
            };
            $marks = $value(['marks'], '1');
            $group = $rawType === 'theory_group' || (is_numeric($marks) && (float) $marks === 0.0 && $value(['parent_question_no', 'parent_reference']) === '');
            $sectionCode = strtoupper($value(['section_code']));
            $importScope = $sectionCode !== ''
                ? $sectionCode
                : (in_array($type, ['mcq', 'true_false', 'fill_blank'], true) ? 'AUTO' : 'MANUAL');
            return [
                'section_code' => $sectionCode, 'section_name' => $value(['section_name']),
                'import_scope' => $importScope,
                'scope_label' => $sectionCode !== ''
                    ? ($value(['section_name']) ?: $sectionCode)
                    : ($importScope === 'AUTO' ? 'Automatic' : 'Manual / theory'),
                'reference' => $value(['question_no', 'reference'], 'Q'.($index + 1)),
                'parent_reference' => $value(['parent_question_no', 'parent_reference']),
                'question_level' => $value(['question_level', 'level'], '1'), 'type' => $type,
                'question_text' => $value(['question_text', 'question']),
                'option_a' => $value(['option_a']), 'option_b' => $value(['option_b']),
                'option_c' => $value(['option_c']), 'option_d' => $value(['option_d']),
                'correct_answer' => strtolower($value(['correct_option', 'correct_answer'])),
                'marks' => $marks, 'scoring_method' => strtolower($value(['scoring_method'])),
                'answer_mode' => strtolower($value(['answer_mode'], 'online')),
                'display_order' => $value(['display_order', 'sequence'], (string) ($index + 1)),
                'instructions' => $value(['instructions']), 'is_required' => strtolower($value(['is_required'], 'yes')),
                'is_instruction_only' => $group || $this->bool($value(['is_instruction_only'])),
                'model_answer' => $value(['model_answer']),
            ];
        })->all();
    }

    private function validateRows(array $rows, ?CbtExam $exam): array
    {
        $errors = [];
        $keys = collect($rows)->map(fn (array $row) => $this->rowKey($row));
        if ($rows === []) $errors[] = 'File: no data rows were found.';
        foreach ($keys->duplicates() as $duplicate) {
            [$sectionCode, $reference] = array_pad(explode('|', $duplicate, 2), 2, '');
            $scope = $sectionCode !== '' ? " in section {$sectionCode}" : '';
            $errors[] = "question_no: duplicate identifier {$reference}{$scope}.";
        }
        $sections = $exam?->sections()->get()->keyBy(fn ($section) => strtoupper($section->code)) ?? collect();
        $rowsByKey = collect($rows)->keyBy(fn (array $row) => $this->rowKey($row));

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $key = $this->rowKey($row);
            $parentKey = $this->rowKey($row, $row['parent_reference']);
            if ($row['reference'] === '') $errors[] = "Row {$line} · question_no: required.";
            if ($row['question_text'] === '') $errors[] = "Row {$line} · question_text: required.";
            if (! in_array($row['type'], ['mcq', 'essay', 'short_answer', 'fill_blank', 'true_false'], true)) $errors[] = "Row {$line} · question_type: invalid value '{$row['type']}'.";
            if (! is_numeric($row['marks']) || (float) $row['marks'] < 0) $errors[] = "Row {$line} · marks: must be zero or greater.";
            if (! ctype_digit((string) $row['question_level']) || (int) $row['question_level'] < 1) $errors[] = "Row {$line} · question_level: must be a positive integer.";
            if (! ctype_digit((string) $row['display_order']) || (int) $row['display_order'] < 1) $errors[] = "Row {$line} · display_order: must be a positive integer.";
            if ($row['parent_reference'] !== '' && ! $keys->contains($parentKey)) $errors[] = "Row {$line} · parent_question_no: '{$row['parent_reference']}' does not exist in section {$row['section_code']}.";
            if ($row['parent_reference'] !== '' && $parentKey === $key) $errors[] = "Row {$line} · parent_question_no: a question cannot be its own parent.";
            if (in_array($row['type'], ['mcq', 'true_false'], true) && ! in_array($row['correct_answer'], ['a', 'b', 'c', 'd'], true)) $errors[] = "Row {$line} · correct_option: use A, B, C or D.";
            if ($row['type'] === 'mcq' && ($row['option_a'] === '' || $row['option_b'] === '')) $errors[] = "Row {$line} · options: objective questions require option_a and option_b.";
            if ($row['type'] === 'fill_blank' && $row['model_answer'] === '' && $row['correct_answer'] === '') $errors[] = "Row {$line} · model_answer: required for fill_blank.";
            if ($row['scoring_method'] !== '' && ! in_array($row['scoring_method'], ['automatic', 'manual'], true)) $errors[] = "Row {$line} · scoring_method: use automatic or manual.";
            if (! in_array($row['answer_mode'], ['online', 'paper', 'hybrid'], true)) $errors[] = "Row {$line} · answer_mode: use online, paper or hybrid.";
            if ($row['scoring_method'] === 'automatic' && ! in_array($row['type'], ['mcq', 'true_false', 'fill_blank'], true)) $errors[] = "Row {$line} · scoring_method: {$row['type']} questions require manual scoring.";
            if ($row['answer_mode'] === 'paper' && $row['scoring_method'] === 'automatic') $errors[] = "Row {$line} · answer_mode: paper answers cannot use automatic scoring.";
            if ($this->bool($row['is_instruction_only']) && (float) $row['marks'] !== 0.0) $errors[] = "Row {$line} · marks: instruction/group rows must have zero marks.";
            if ($exam) {
                if ($row['section_code'] === '' || ! $sections->has($row['section_code'])) $errors[] = "Row {$line} · section_code: '{$row['section_code']}' is not a section in the selected exam.";
                elseif ($sections->get($row['section_code'])->answer_mode !== $row['answer_mode']) $errors[] = "Row {$line} · answer_mode: does not match section {$row['section_code']}.";
            }
        }

        $parents = collect($rows)->mapWithKeys(fn (array $row) => [$this->rowKey($row) => $row['parent_reference'] === '' ? '' : $this->rowKey($row, $row['parent_reference'])])->all();
        foreach (array_keys($parents) as $reference) {
            $seen = [];
            $cursor = $reference;
            $depth = 1;
            while (($parents[$cursor] ?? '') !== '') {
                if (isset($seen[$cursor])) { $errors[] = "parent_question_no: hierarchy cycle detected at {$reference}."; break; }
                $seen[$cursor] = true;
                $cursor = $parents[$cursor];
                $depth++;
            }
            if (isset($rowsByKey[$reference]) && ctype_digit((string) $rowsByKey[$reference]['question_level']) && (int) $rowsByKey[$reference]['question_level'] !== $depth) {
                $row = $rowsByKey[$reference];
                $errors[] = "question_level: {$row['reference']} in section {$row['section_code']} must be level {$depth}.";
            }
        }

        if ($exam) {
            foreach (collect($rows)->groupBy('section_code') as $code => $sectionRows) {
                $section = $sections->get($code);
                if (! $section) continue;
                $parentKeys = $sectionRows->filter(fn ($row) => $row['parent_reference'] !== '')->map(fn ($row) => $this->rowKey($row, $row['parent_reference']))->all();
                $leafTotal = round((float) $sectionRows->reject(fn ($row) => in_array($this->rowKey($row), $parentKeys, true) || $this->bool($row['is_instruction_only']))->sum(fn ($row) => (float) $row['marks']), 2);
                $projected = round($section->assignedMarks() + $leafTotal, 2);
                if ($projected > (float) $section->max_marks) $errors[] = "Section {$code} · marks: projected total {$projected} exceeds the {$section->max_marks} maximum.";
                if ($exam->strict_marks_validation && abs($projected - (float) $section->max_marks) > 0.009) $errors[] = "Section {$code} · marks: projected leaf total {$projected} must equal {$section->max_marks}.";
            }
        }
        return array_values(array_unique($errors));
    }

    private function bool(mixed $value): bool
    {
        if (is_bool($value)) return $value;
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y'], true);
    }

    private function rowKey(array $row, ?string $reference = null): string
    {
        $scope = trim((string) ($row['section_code'] ?? ''));
        if ($scope === '') {
            $scope = trim((string) ($row['import_scope'] ?? ''));
        }
        if ($scope === '') {
            $scope = in_array(($row['type'] ?? ''), ['mcq', 'true_false', 'fill_blank'], true) ? 'AUTO' : 'MANUAL';
        }

        return strtoupper($scope).'|'.trim((string) ($reference ?? $row['reference'] ?? ''));
    }
}
