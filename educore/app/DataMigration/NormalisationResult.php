<?php

namespace App\DataMigration;

final readonly class NormalisationResult
{
    public function __construct(public mixed $value, public string $rule, public ?string $warning = null) {}
}
