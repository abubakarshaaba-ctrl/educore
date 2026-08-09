<?php

namespace App\Contracts\DataMigration;

use Generator;

interface ReadOnlySourceConnector
{
    public function identifier(): string;

    public function inspect(): array;

    public function stream(string $dataset, int $offset = 0): Generator;

    public function isReadOnly(): bool;
}
