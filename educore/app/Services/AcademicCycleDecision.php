<?php

namespace App\Services;

class AcademicCycleDecision
{
    public function __construct(
        public bool $allowed = true,
        public array $blocking = [],
        public array $warnings = [],
        public array $information = [],
        public array $context = [],
    ) {
    }

    public static function allow(array $context = [], array $warnings = [], array $information = []): self
    {
        return new self(true, [], $warnings, $information, $context);
    }

    public static function deny(array $blocking, array $context = [], array $warnings = [], array $information = []): self
    {
        return new self(false, $blocking, $warnings, $information, $context);
    }

    public function hasBlockingItems(): bool
    {
        return $this->blocking !== [];
    }

    public function allItems(): array
    {
        return [
            'blocking' => $this->blocking,
            'warnings' => $this->warnings,
            'information' => $this->information,
        ];
    }
}
