<?php

namespace App\Services\Cbt;

use App\Models\CbtQuestion;
use Illuminate\Support\Collection;

class CbtQuestionNumberingService
{
    public function number(Collection $questions): Collection
    {
        $byParent = $questions->groupBy(fn (CbtQuestion $q) => $q->parent_question_id ?: 0);
        $rows = collect();
        $walk = function (int $parentId, array $prefix = [], int $depth = 0) use (&$walk, $byParent, $rows) {
            foreach (($byParent->get($parentId) ?? collect())->sortBy('sequence')->values() as $index => $question) {
                $position = $index + 1;
                $style = $question->numbering_style === 'auto' || ! $question->numbering_style
                    ? $this->defaultStyle($depth)
                    : $question->numbering_style;
                $token = $this->token($position, $style);
                $path = [...$prefix, $token];
                $question->setAttribute('display_number', $token);
                $question->setAttribute('display_path', $this->formatPath($path));
                $question->setAttribute('display_level', $depth);
                $rows->push($question);
                $walk((int) $question->id, $path, $depth + 1);
            }
        };
        $walk(0);

        return $rows;
    }

    private function defaultStyle(int $depth): string
    {
        return match ($depth % 4) {
            0 => 'decimal',
            1 => 'lower_alpha',
            2 => 'lower_roman',
            default => 'upper_alpha',
        };
    }

    private function token(int $value, string $style): string
    {
        return match ($style) {
            'lower_alpha' => strtolower($this->alpha($value)),
            'upper_alpha' => $this->alpha($value),
            'lower_roman' => strtolower($this->roman($value)),
            'upper_roman' => $this->roman($value),
            default => (string) $value,
        };
    }

    private function formatPath(array $path): string
    {
        return implode('.', $path);
    }

    private function alpha(int $value): string
    {
        $result = '';
        while ($value > 0) {
            $value--;
            $result = chr(65 + ($value % 26)).$result;
            $value = intdiv($value, 26);
        }
        return $result;
    }

    private function roman(int $value): string
    {
        $map = [1000 => 'M', 900 => 'CM', 500 => 'D', 400 => 'CD', 100 => 'C', 90 => 'XC', 50 => 'L', 40 => 'XL', 10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I'];
        $result = '';
        foreach ($map as $number => $symbol) {
            while ($value >= $number) {
                $result .= $symbol;
                $value -= $number;
            }
        }
        return $result;
    }
}
