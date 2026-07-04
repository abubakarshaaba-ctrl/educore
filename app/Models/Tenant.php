<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class Tenant extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_SUBSCRIPTION_EXPIRED = 'subscription_expired';
    public const STATUS_PENDING = 'pending';

    public const RESERVED_SLUGS = [
        'login',
        'logout',
        'register',
        'password',
        'super',
        'admin',
        'administrator',
        'portal',
        'agent',
        'apply',
        'school',
        'schools',
        'student',
        'students',
        'parent',
        'parents',
        'staff',
        'settings',
        'api',
        'storage',
        'assets',
        'vendor',
        'dashboard',
        'home',
        'public',
    ];

    protected $fillable = [
        'name',
        'slug',
        'subdomain',
        'logo_path',
        'motto',
        'address',
        'phone',
        'email',
        'status',
        'subscription_expires_at',
        'theme_primary',
        'theme_accent',
        'theme_sidebar',
        'custom_domain',
        'domain_verified',
        'primary_color',
        'secondary_color',
    ];

    protected function casts(): array
    {
        return [
            'subscription_expires_at' => 'date',
            'domain_verified' => 'boolean',
        ];
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function academicSessions(): HasMany
    {
        return $this->hasMany(AcademicSession::class);
    }

    public function activeSubscription()
    {
        // Only return subscriptions that actually have a plan assigned.
        // Admin-created subscriptions without a plan_id are treated as "no plan"
        // and should not prevent a paid plan from showing as current.
        return $this->hasOne(TenantSubscription::class)
            ->where('status', 'active')
            ->whereNotNull('plan_id')
            ->latest();
    }

    public function subscriptions()
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_SUBSCRIPTION_EXPIRED
            || ($this->subscription_expires_at && $this->subscription_expires_at->isPast());
    }

    /**
     * True only when the subscription is still active but falls due within $days.
     * Uses an explicit future-window comparison rather than diffInDays(), whose
     * sign convention changed in Carbon 3 (a future date yields a negative diff,
     * which made "< 14" match every active subscription).
     */
    public function isExpiringSoon(int $days = 14): bool
    {
        if (!$this->subscription_expires_at || $this->isExpired()) {
            return false;
        }

        return $this->subscription_expires_at->isFuture()
            && $this->subscription_expires_at->lessThanOrEqualTo(now()->addDays($days));
    }

    public function isPublicPortalAvailable(): bool
    {
        return $this->isActive() && !$this->isExpired();
    }

    public static function normalizeSlug(?string $slug): string
    {
        return Str::slug(Str::lower(trim((string) $slug)));
    }

    public static function isReservedSlug(?string $slug): bool
    {
        return in_array(static::normalizeSlug($slug), self::RESERVED_SLUGS, true);
    }

    public static function slugRules(?int $ignoreTenantId = null): array
    {
        $unique = Rule::unique('tenants', 'slug');

        if ($ignoreTenantId) {
            $unique = $unique->ignore($ignoreTenantId);
        }

        return [
            'required',
            'string',
            'max:80',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::notIn(self::RESERVED_SLUGS),
            $unique,
        ];
    }

    public function scopePubliclyAccessible(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where(function (Builder $expiry) {
                $expiry->whereNull('subscription_expires_at')
                    ->orWhereDate('subscription_expires_at', '>=', now()->toDateString());
            });
    }

    public function getCurrentSessionAttribute()
    {
        return $this->academicSessions()->where('is_current', true)->first();
    }
}
