<?php

namespace App\DataMigration;

final readonly class SourceInspection
{
    /** @param list<SourceDatasetDefinition> $datasets */
    public function __construct(
        public string $format,
        public array $datasets,
        public array $metadata = [],
    ) {}
}
