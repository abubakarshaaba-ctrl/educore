<?php

namespace App\DataMigration;

use App\Enums\MigrationMappingDecision;

final readonly class ColumnMappingSuggestion
{
    public function __construct(
        public string $sourceColumn,
        public ?string $destinationField,
        public MigrationMappingDecision $decision,
        public float $confidence,
        public array $profile,
        public array $candidates,
        public string $basis,
    ) {}
}
