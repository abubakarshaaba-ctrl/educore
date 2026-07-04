<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffProxyRequest extends BaseTenantModel
{
    protected $table = 'staff_proxy_requests';

    protected $fillable = [
        'tenant_id', 'target_user_id', 'requested_by',
        'attendance_date', 'clock_in_time',
        'qr_token', 'friend_photo_path', 'lat', 'lng',
        'verification_method', 'otp_code', 'otp_expires_at',
        'pin_attempts', 'status', 'reject_reason',
    ];

    protected $hidden = ['otp_code'];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'otp_expires_at'  => 'datetime',
            'pin_attempts'    => 'integer',
        ];
    }

    public function targetStaff(): BelongsTo  { return $this->belongsTo(User::class, 'target_user_id'); }
    public function requestedBy(): BelongsTo  { return $this->belongsTo(User::class, 'requested_by'); }

    public function isExpired(): bool
    {
        if ($this->status === 'expired') return true;
        return $this->otp_expires_at && $this->otp_expires_at->isPast();
    }

    public function isPending(): bool { return $this->status === 'pending'; }
}
