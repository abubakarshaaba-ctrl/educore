<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StaffAttendanceSetting extends Model
{
    protected $table = 'staff_attendance_settings';

    protected $fillable = [
        'tenant_id', 'resumption_time', 'grace_minutes', 'closing_time',
        'geo_lat', 'geo_lng', 'geo_radius_meters', 'geo_enabled',
        'qr_secret', 'qr_secret_date',
        'permanent_qr_secret',   // ← static display-screen QR
    ];

    protected function casts(): array
    {
        return [
            'geo_enabled'       => 'boolean',
            'geo_lat'           => 'float',
            'geo_lng'           => 'float',
            'geo_radius_meters' => 'integer',
            'grace_minutes'     => 'integer',
            'qr_secret_date'    => 'date',
        ];
    }

    public static function forTenant(int $tenantId): self
    {
        return self::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'resumption_time'   => '08:00:00',
                'grace_minutes'     => 15,
                'closing_time'      => '15:00:00',
                'geo_enabled'       => false,
                'geo_radius_meters' => 100,
            ]
        );
    }

    // ── Time classification ───────────────────────────────────────────
    public function classifyClockIn(string $clockInTime): string
    {
        $cin   = Carbon::parse($clockInTime);
        $res   = Carbon::parse($this->resumption_time);
        $grace = (clone $res)->addMinutes($this->grace_minutes);

        if ($cin->lt($res))    return 'early';
        if ($cin->lte($grace)) return 'present';
        return 'late';
    }

    // ── STATIC display-screen QR (never changes) ──────────────────────
    /**
     * Get or generate the school's permanent display-screen QR secret.
     * This never rotates — the QR printed/displayed in the room is always valid.
     */
    public function permanentQrSecret(): string
    {
        if (!$this->permanent_qr_secret) {
            $secret = bin2hex(random_bytes(16));
            $this->update(['permanent_qr_secret' => $secret]);
        }
        return $this->permanent_qr_secret;
    }

    /**
     * Build the static screen QR payload.
     * Contains: tenant_id + HMAC signature.
     * No date — valid indefinitely unless admin resets it.
     */
    public function staticQrPayload(): string
    {
        $secret  = $this->permanentQrSecret();
        $payload = ['tid' => $this->tenant_id, 'type' => 'screen'];
        $sig     = hash_hmac('sha256', json_encode($payload), $secret);
        $payload['sig'] = $sig;
        return base64_encode(json_encode($payload));
    }

    /**
     * Verify a static screen QR token.
     */
    public function verifyStaticQrToken(string $token): bool
    {
        try {
            $data   = json_decode(base64_decode($token), true);
            $sig    = $data['sig'] ?? '';
            unset($data['sig']);
            if (($data['type'] ?? '') !== 'screen') return false;
            if (($data['tid']  ?? 0) != $this->tenant_id) return false;
            $secret   = $this->permanentQrSecret();
            $expected = hash_hmac('sha256', json_encode($data), $secret);
            return hash_equals($expected, $sig);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Reset the static QR — generates a new secret, invalidating all old QR prints.
     * Call this if the QR is ever compromised.
     */
    public function resetStaticQr(): void
    {
        $this->update(['permanent_qr_secret' => bin2hex(random_bytes(16))]);
    }

    // ── Legacy daily QR kept for offline compatibility ────────────────
    /** @deprecated Use staticQrPayload() for display screen */
    public function todayQrSecret(): string
    {
        $today = today()->toDateString();
        if ($this->qr_secret_date?->toDateString() !== $today) {
            $secret = bin2hex(random_bytes(16));
            $this->update(['qr_secret' => $secret, 'qr_secret_date' => $today]);
        }
        return $this->qr_secret;
    }

    /** @deprecated */
    public function todayQrPayload(): string
    {
        $date    = today()->toDateString();
        $secret  = $this->todayQrSecret();
        $payload = ['tid' => $this->tenant_id, 'date' => $date, 'ts' => time()];
        $sig     = hash_hmac('sha256', json_encode($payload), $secret);
        $payload['sig'] = $sig;
        return base64_encode(json_encode($payload));
    }

    /** @deprecated */
    public function verifyQrToken(string $token): bool
    {
        try {
            $data   = json_decode(base64_decode($token), true);
            $sig    = $data['sig'] ?? '';
            unset($data['sig']);
            // Reject if it's a screen-type token — use verifyStaticQrToken for those
            if (($data['type'] ?? '') === 'screen') return false;
            $secret   = $this->todayQrSecret();
            $expected = hash_hmac('sha256', json_encode($data), $secret);
            return hash_equals($expected, $sig)
                && ($data['date'] ?? '') === today()->toDateString()
                && ($data['tid']  ?? 0) == $this->tenant_id;
        } catch (\Throwable) {
            return false;
        }
    }

    // ── Personal staff QR ─────────────────────────────────────────────
    public function verifyPersonalQrToken(string $token): ?\App\Models\User
    {
        $user = \App\Models\User::verifyPersonalQr($token);
        if (!$user) return null;
        return $user->tenant_id == $this->tenant_id ? $user : null;
    }

    // ── Geo-fence ─────────────────────────────────────────────────────
    public function distanceTo(float $lat, float $lng): float
    {
        if (!$this->geo_lat || !$this->geo_lng) return 0;
        $R  = 6371000;
        $φ1 = deg2rad($this->geo_lat);
        $φ2 = deg2rad($lat);
        $Δφ = deg2rad($lat - $this->geo_lat);
        $Δλ = deg2rad($lng - $this->geo_lng);
        $a  = sin($Δφ/2)**2 + cos($φ1)*cos($φ2)*sin($Δλ/2)**2;
        return $R * 2 * atan2(sqrt($a), sqrt(1-$a));
    }
}
