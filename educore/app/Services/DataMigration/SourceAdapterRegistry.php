<?php

namespace App\Services\DataMigration;

use App\Contracts\DataMigration\SourceAdapter;
use App\Exceptions\UnsupportedMigrationSource;
use App\Models\MigrationFile;

class SourceAdapterRegistry
{
    /** @var list<SourceAdapter> */
    private array $adapters;

    public function __construct(iterable $adapters, private readonly FileSignatureInspector $signatures)
    {
        $this->adapters = [...$adapters];
    }

    public function resolve(MigrationFile $file): SourceAdapter
    {
        $format = $this->signatures->detect($file);
        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($file, $format)) {
                return $adapter;
            }
        }

        throw new UnsupportedMigrationSource("Unsupported or unrecognised source format: {$format}.");
    }

    public function detectedFormat(MigrationFile $file): string
    {
        return $this->signatures->detect($file);
    }
}
