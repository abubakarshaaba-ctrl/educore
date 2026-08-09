<?php

namespace App\DataMigration\Schema;

final readonly class CanonicalEntityDefinition
{
    /** @param array<string, CanonicalFieldDefinition> $fields */
    public function __construct(public string $name, public string $version, public array $fields) {}

    public function field(string $name): ?CanonicalFieldDefinition
    {
        return $this->fields[$name] ?? null;
    }
}
