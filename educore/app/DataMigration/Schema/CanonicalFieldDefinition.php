<?php

namespace App\DataMigration\Schema;

final readonly class CanonicalFieldDefinition
{
    public function __construct(
        public string $name,
        public string $type,
        public array $aliases = [],
        public bool $required = false,
        public bool $tenantUnique = false,
        public bool $nullable = true,
        public array $canonicalValues = [],
        public ?string $relationshipEntity = null,
    ) {}
}
