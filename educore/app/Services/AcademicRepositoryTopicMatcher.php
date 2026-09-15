<?php

namespace App\Services;

use Illuminate\Support\Str;

class AcademicRepositoryTopicMatcher
{
    private const STOP_WORDS = [
        'the', 'a', 'an', 'of', 'in', 'on', 'to', 'for', 'and', 'or', 'with',
        'introduction', 'introductory', 'lesson', 'topic', 'study', 'studies',
    ];

    public function canonical(?string $value): string
    {
        $value = Str::of((string) $value)
            ->lower()
            ->replaceMatches('/[^\pL\pN\s]+/u', ' ')
            ->squish()
            ->value();

        $tokens = collect(preg_split('/\s+/u', $value) ?: [])
            ->map(fn ($token) => trim((string) $token))
            ->filter(fn ($token) => $token !== '' && ! in_array($token, self::STOP_WORDS, true))
            ->map(fn ($token) => $this->lightStem($token))
            ->unique()
            ->sort()
            ->values();

        return $tokens->implode(' ');
    }

    public function similarity(?string $left, ?string $right): float
    {
        $a = $this->tokens($left);
        $b = $this->tokens($right);

        if (! $a || ! $b) {
            return 0.0;
        }

        if ($a === $b) {
            return 1.0;
        }

        $intersection = count(array_intersect($a, $b));
        $union = count(array_unique(array_merge($a, $b)));
        $jaccard = $union > 0 ? $intersection / $union : 0.0;
        $containment = min(count($a), count($b)) > 0
            ? $intersection / min(count($a), count($b))
            : 0.0;

        return max($jaccard, $containment * 0.9);
    }

    public function isNearDuplicate(?string $left, ?string $right): bool
    {
        $a = $this->canonical($left);
        $b = $this->canonical($right);

        if ($a === '' || $b === '') {
            return false;
        }

        if ($a === $b) {
            return true;
        }

        if (mb_strlen($a) >= 8 && mb_strlen($b) >= 8 && (str_contains($a, $b) || str_contains($b, $a))) {
            return true;
        }

        return $this->similarity($left, $right) >= 0.68;
    }

    private function tokens(?string $value): array
    {
        $canonical = $this->canonical($value);

        return $canonical === ''
            ? []
            : (preg_split('/\s+/u', $canonical) ?: []);
    }

    private function lightStem(string $token): string
    {
        foreach (['ies' => 'y', 'sses' => 'ss', 'ing' => '', 'ed' => '', 'es' => '', 's' => ''] as $suffix => $replacement) {
            if (mb_strlen($token) > mb_strlen($suffix) + 3 && str_ends_with($token, $suffix)) {
                return mb_substr($token, 0, mb_strlen($token) - mb_strlen($suffix)).$replacement;
            }
        }

        return $token;
    }
}
