<?php

namespace App\Services;

use Carbon\CarbonInterface;

class TenantAccessDecision
{
    public const STATE_ALLOWED = 'allowed';
    public const STATE_TRIAL = 'trial';
    public const STATE_EXPIRING_SOON = 'expiring_soon';
    public const STATE_GRACE = 'grace';
    public const STATE_INACTIVE = 'inactive';
    public const STATE_SUSPENDED = 'suspended';
    public const STATE_EXPIRED = 'expired';
    public const STATE_MISSING = 'missing';

    public function __construct(
        public readonly bool $allowed,
        public readonly string $state,
        public readonly string $message,
        public readonly ?string $severity = null,
        public readonly ?CarbonInterface $expiresAt = null,
        public readonly array $metadata = []
    ) {
    }

    public static function allow(string $message = 'School account access is available.', array $metadata = []): self
    {
        return new self(true, self::STATE_ALLOWED, $message, null, null, $metadata);
    }

    public static function warning(
        string $state,
        string $message,
        ?CarbonInterface $expiresAt = null,
        array $metadata = []
    ): self {
        return new self(true, $state, $message, 'warning', $expiresAt, $metadata);
    }

    public static function deny(
        string $state,
        string $message,
        ?CarbonInterface $expiresAt = null,
        array $metadata = []
    ): self {
        return new self(false, $state, $message, 'danger', $expiresAt, $metadata);
    }

    public function isDenied(): bool
    {
        return !$this->allowed;
    }

    public function isWarning(): bool
    {
        return $this->allowed && $this->severity === 'warning';
    }

    public function title(): string
    {
        return match ($this->state) {
            self::STATE_TRIAL => 'Trial account',
            self::STATE_EXPIRING_SOON => 'Subscription expiring soon',
            self::STATE_GRACE => 'Subscription grace period',
            self::STATE_SUSPENDED => 'School portal unavailable',
            self::STATE_EXPIRED => 'Subscription renewal required',
            self::STATE_INACTIVE, self::STATE_MISSING => 'School account unavailable',
            default => 'School account status',
        };
    }
}
