<?php

namespace App\Services;

use App\Models\Student;

class StudentIdGenerator
{
    /**
     * Generate the next platform-wide incremental student identifier.
     *
     * Examples: STU1001, STU1002, STU1003 ...
     *
     * Legacy tenant-scoped/year-based admission numbers remain untouched, but
     * every newly generated identifier follows this single incremental format.
     */
    public function generate(): string
    {
        $highest = 1000;

        foreach (
            Student::withoutTenantScope()
                ->withTrashed()
                ->whereNotNull('admission_number')
                ->pluck('admission_number') as $studentId
        ) {
            $highest = max($highest, $this->numericPart($studentId));
        }

        do {
            $highest++;
            $candidate = 'STU'.str_pad((string) $highest, 4, '0', STR_PAD_LEFT);
        } while (
            Student::withoutTenantScope()
                ->withTrashed()
                ->where('admission_number', $candidate)
                ->exists()
        );

        return $candidate;
    }

    private function numericPart(?string $studentId): int
    {
        if (!$studentId) {
            return 0;
        }

        $normalized = strtoupper(trim($studentId));

        // Canonical format.
        if (preg_match('/^STU(\d+)$/', $normalized, $matches)) {
            return (int) $matches[1];
        }

        // Read the trailing sequence from legacy values such as
        // STU-2026-0008 without preserving that old format for new records.
        if (preg_match('/^STU(?:-\d{4})?-(\d+)$/', $normalized, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }
}
