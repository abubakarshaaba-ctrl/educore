<?php

namespace App\Services\DataMigration;

use App\DataMigration\Schema\CanonicalEntityDefinition;
use App\DataMigration\Schema\CanonicalFieldDefinition;
use InvalidArgumentException;

class CanonicalSchemaRegistry
{
    public function entity(string $name): CanonicalEntityDefinition
    {
        $definition = config("data_migration_schema.entities.{$name}");
        if (! is_array($definition)) {
            throw new InvalidArgumentException("Unknown canonical entity [{$name}].");
        }

        $fields = [];
        foreach ($definition['fields'] as $field => $options) {
            $fields[$field] = new CanonicalFieldDefinition(
                $field,
                $options['type'] ?? 'string',
                $options['aliases'] ?? [],
                $options['required'] ?? false,
                $options['tenant_unique'] ?? false,
                $options['nullable'] ?? true,
                $options['values'] ?? [],
                $options['relationship'] ?? null,
            );
        }

        return new CanonicalEntityDefinition($name, config('data_migration_schema.version', '1.0'), $fields);
    }

    public function has(string $entity, string $field): bool
    {
        return $this->entity($entity)->field($field) !== null;
    }

    public function entities(): array
    {
        return array_keys(config('data_migration_schema.entities', []));
    }
}
