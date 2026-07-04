<?php

namespace App\Models\Scopes;

/**
 * TenantContext
 *
 * A simple static store that lets CLI commands, seeders, and background
 * jobs declare which tenant they are operating on — without requiring
 * an authenticated HTTP user.
 *
 * Usage:
 *   TenantContext::set($tenantId);   // set before any queries
 *   TenantContext::clear();          // clear after the operation
 */
class TenantContext
{
    private static ?int $tenantId = null;

    public static function set(int $tenantId): void
    {
        static::$tenantId = $tenantId;
    }

    public static function get(): ?int
    {
        return static::$tenantId;
    }

    public static function has(): bool
    {
        return static::$tenantId !== null;
    }

    public static function clear(): void
    {
        static::$tenantId = null;
    }
}
