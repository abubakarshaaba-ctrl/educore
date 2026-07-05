<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Lightweight bearer token for the mobile API.
 *
 * Not tenant-scoped: tokens belong to users; tenant isolation comes from
 * the authenticated user's tenant_id (BaseTenantModel global scope).
 */
class ApiToken extends Model
{
    protected $fillable = [
        'user_id', 'name', 'token', 'device', 'last_used_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at'   => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Issue a new token for a user. Returns the PLAIN token (shown once);
     * only its sha256 hash is stored.
     */
    public static function issue(User $user, ?string $device = null, int $days = 90): string
    {
        $plain = Str::random(48);

        static::create([
            'user_id'    => $user->id,
            'name'       => 'mobile',
            'token'      => hash('sha256', $plain),
            'device'     => $device ? Str::limit($device, 145) : null,
            'expires_at' => now()->addDays($days),
        ]);

        return $plain;
    }

    public static function findValid(string $plain): ?self
    {
        $token = static::where('token', hash('sha256', $plain))->first();

        if (!$token) {
            return null;
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            $token->delete();

            return null;
        }

        return $token;
    }
}
