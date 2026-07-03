<?php

namespace App\Services;

use App\Models\Tenant;

class TenantHostResolution
{
    public const TYPE_CENTRAL = 'central';
    public const TYPE_LOCAL_SUBDOMAIN = 'local_subdomain';
    public const TYPE_CUSTOM_DOMAIN = 'custom_domain';
    public const TYPE_UNKNOWN = 'unknown';
    public const TYPE_INVALID = 'invalid';

    public function __construct(
        public readonly string $host,
        public readonly string $type,
        public readonly ?Tenant $tenant = null,
        public readonly ?string $tenantKey = null,
        public readonly ?string $reason = null
    ) {
    }

    public function isTenant(): bool
    {
        return $this->tenant !== null;
    }

    public function isCentral(): bool
    {
        return $this->type === self::TYPE_CENTRAL;
    }

    public function isUnknownOrInvalid(): bool
    {
        return in_array($this->type, [self::TYPE_UNKNOWN, self::TYPE_INVALID], true);
    }
}
