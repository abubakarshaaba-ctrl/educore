<?php

namespace App\DataMigration;

final readonly class SourceDatasetDefinition
{
    public function __construct(
        public string $name,
        public array $headers,
        public ?int $estimatedRows = null,
        public ?string $locator = null,
        public array $metadata = [],
    ) {}
}
