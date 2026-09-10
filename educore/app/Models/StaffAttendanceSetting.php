<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class StaffAttendanceSetting extends Model
{
    protected $table = 'staff_attendance_settings';

    protected $fillable = [
        'tenant_id', 'resumption_time', 'grace_minutes', 'closing_time',
        'geo_lat', 'geo_lng', 'geo_radius_meters', 'geo_enabled',
        'qr_secret', 'qr_secret_date',
        'permanent_qr_secret',
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

    public function classifyClockIn(string $clockInTime): string
    {
        $cin   = Carbon::parse($clockInTime);
        $res   = Carbon::parse($this->resumption_time);
        $grace = (clone $res)->addMinutes($this->grace_minutes);

        if ($cin->lt($res)) return 'early';
        if ($cin->lte($grace)) return 'present';
        return 'late';
    }

    /**
     * Return the static school QR secret. Older deployments may not yet have
     * permanent_qr_secret, so use the existing qr_secret column as a safe
     * persistent fallback until migrations are applied.
     */
    public function permanentQrSecret(): string
    {
        if (Schema::hasColumn($this->getTable(), 'permanent_qr_secret')) {
            $secret = (string) ($this->getAttribute('permanent_qr_secret') ?? '');
            if ($secret === '') {
                $secret = bin2hex(random_bytes(16));
                $this->update(['permanent_qr_secret' => $secret]);
            }
            return $secret;
        }

        $secret = (string) ($this->qr_secret ?? '');
        if ($secret === '') {
            $secret = bin2hex(random_bytes(16));
            $this->update(['qr_secret' => $secret, 'qr_secret_date' => null]);
        }
        return $secret;
    }

    public function staticQrPayload(): string
    {
        $secret  = $this->permanentQrSecret();
        $payload = ['tid' => $this->tenant_id, 'type' => 'screen'];
        $sig     = hash_hmac('sha256', json_encode($payload), $secret);
        $payload['sig'] = $sig;
        return base64_encode(json_encode($payload));
    }

    public function verifyStaticQrToken(string $token): bool
    {
        try {
            $data = json_decode(base64_decode($token, true), true);
            if (! is_array($data)) return false;
            $sig = (string) ($data['sig'] ?? '');
            unset($data['sig']);
            if (($data['type'] ?? '') !== 'screen') return false;
            if (($data['tid'] ?? 0) != $this->tenant_id) return false;
            $expected = hash_hmac('sha256', json_encode($data), $this->permanentQrSecret());
            return $sig !== '' && hash_equals($expected, $sig);
        } catch (\Throwable) {
            return false;
        }
    }

    public function resetStaticQr(): void
    {
        $secret = bin2hex(random_bytes(16));
        if (Schema::hasColumn($this->getTable(), 'permanent_qr_secret')) {
            $this->update(['permanent_qr_secret' => $secret]);
            return;
        }

        $this->update(['qr_secret' => $secret, 'qr_secret_date' => null]);
    }

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
        $date = today()->toDateString();
        $secret = $this->todayQrSecret();
        $payload = ['tid' => $this->tenant_id, 'date' => $date, 'ts' => time()];
        $sig = hash_hmac('sha256', json_encode($payload), $secret);
        $payload['sig'] = $sig;
        return base64_encode(json_encode($payload));
    }

    /** @deprecated */
    public function verifyQrToken(string $token): bool
    {
        try {
            $data = json_decode(base64_decode($token), true);
            $sig = $data['sig'] ?? '';
            unset($data['sig']);
            if (($data['type'] ?? '') === 'screen') return false;
            $expected = hash_hmac('sha256', json_encode($data), $this->todayQrSecret());
            return hash_equals($expected, $sig)
                && ($data['date'] ?? '') === today()->toDateString()
                && ($data['tid'] ?? 0) == $this->tenant_id;
        } catch (\Throwable) {
            return false;
        }
    }

    public function verifyPersonalQrToken(string $token): ?\App\Models\User
    {
        $user = \App\Models\User::verifyPersonalQr($token);
        if (!$user) return null;
        return $user->tenant_id == $this->tenant_id ? $user : null;
    }

    public function distanceTo(float $lat, float $lng): float
    {
        if (!$this->geo_lat || !$this->geo_lng) return 0;
        $R = 6371000;
        $φ1 = deg2rad($this->geo_lat);
        $φ2 = deg2rad($lat);
        $Δφ = deg2rad($lat - $this->geo_lat);
        $Δλ = deg2rad($lng - $this->geo_lng);
        $a = sin($Δφ/2)**2 + cos($φ1)*cos($φ2)*sin($Δλ/2)**2;
        return $R * 2 * atan2(sqrt($a), sqrt(1-$a));
    }
}
