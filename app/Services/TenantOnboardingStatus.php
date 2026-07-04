<?php

namespace App\Services;

class TenantOnboardingStatus
{
    public function __construct(
        public readonly bool $complete,
        public readonly int $progress_percentage,
        public readonly ?string $current_step,
        public readonly ?string $next_step,
        public readonly array $blocking_items,
        public readonly array $warning_items,
        public readonly array $completed_items,
        public readonly bool $can_activate,
        public readonly bool $can_access_operations,
        public readonly array $steps,
        public readonly array $urls = [],
        public readonly array $environment = [],
    ) {
    }

    public function hasBlockingItems(): bool
    {
        return $this->blocking_items !== [];
    }

    public function stepStatus(string $step): string
    {
        return $this->steps[$step]['status'] ?? 'pending';
    }

    public function stepRoute(string $step): string
    {
        return $this->steps[$step]['route'] ?? 'tenant.onboarding.index';
    }
}
