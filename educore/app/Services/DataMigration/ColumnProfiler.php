<?php

namespace App\Services\DataMigration;

class ColumnProfiler
{
    public function profile(array $values): array
    {
        $values = array_values(array_filter($values, fn ($value) => $value !== null && trim((string) $value) !== ''));
        $types = array_count_values(array_map(fn ($value) => $this->detectType($value), $values));
        arsort($types);

        return [
            'sample_count' => count($values),
            'detected_type' => array_key_first($types) ?? 'unknown',
            'type_distribution' => $types,
            'samples' => array_slice(array_map(fn ($value) => mb_substr((string) $value, 0, 120), $values), 0, 5),
        ];
    }

    private function detectType(mixed $value): string
    {
        if (is_bool($value) || in_array(strtolower((string) $value), ['true', 'false', 'yes', 'no'], true)) {
            return 'boolean';
        }
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }
        if (is_numeric($value)) {
            return str_contains((string) $value, '.') ? 'decimal' : 'integer';
        }
        if (preg_match('/^\d{4}[-\/]\d{1,2}[-\/]\d{1,2}$/', (string) $value)) {
            return 'date';
        }

        return 'string';
    }
}
