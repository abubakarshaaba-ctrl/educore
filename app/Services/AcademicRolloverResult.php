<?php

namespace App\Services;

class AcademicRolloverResult
{
    public array $rows = [];

    public array $counts = [
        'inspected' => 0,
        'ready' => 0,
        'blocked' => 0,
        'skipped' => 0,
        'promoted' => 0,
        'repeated' => 0,
        'graduated' => 0,
        'failed' => 0,
    ];

    public function __construct(
        public int $tenantId,
        public int $sourceSessionId,
        public int $targetSessionId,
        public bool $committed = false,
    ) {
    }

    public function addRow(array $row): void
    {
        $this->rows[] = $row;
        $status = $row['status'] ?? null;

        if ($status && array_key_exists($status, $this->counts)) {
            $this->counts[$status]++;
        }
    }
}
