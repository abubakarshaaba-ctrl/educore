<?php

namespace App\Contracts\DataMigration;

use App\DataMigration\SourceDatasetDefinition;
use App\DataMigration\SourceInspection;
use App\Models\MigrationFile;
use Generator;

interface SourceAdapter
{
    public function supports(MigrationFile $file, string $detectedFormat): bool;

    public function inspect(MigrationFile $file): SourceInspection;

    /** @return Generator<int, array<string, mixed>> */
    public function streamRows(MigrationFile $file, SourceDatasetDefinition $dataset, int $startRow = 1): Generator;
}
