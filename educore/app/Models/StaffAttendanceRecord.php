<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendanceRecord extends BaseTenantModel
{
    protected $table = 'staff_attendance_records';

    protected $fillable = [
        'tenant_id', 'user_id', 'attendance_date', 'status',
        'clock_in_time', 'clock_out_time',
        'clock_in_method', 'clocked_in_by',
        'clock_in_lat', 'clock_in_lng', 'geo_verified',
        'notes', 'is_offline_upload', 'clock_in_photo', 'proxy_photo',
        'proxy_review_status', 'proxy_reviewed_by', 'proxy_reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date'   => 'date',
            'geo_verified'      => 'boolean',
            'is_offline_upload' => 'boolean',
            'clock_in_lat'      => 'float',
            'clock_in_lng'      => 'float',
            'proxy_reviewed_at' => 'datetime',
        ];
    }

    public function staff(): BelongsTo        { return $this->belongsTo(User::class, 'user_id'); }
    public function clockedInBy(): BelongsTo  { return $this->belongsTo(User::class, 'clocked_in_by'); }
    public function proxyReviewedBy(): BelongsTo { return $this->belongsTo(User::class, 'proxy_reviewed_by'); }
    public function tenant(): BelongsTo       { return $this->belongsTo(Tenant::class); }

    public function statusLabel(): string
    {
        return match($this->status) {
            'early'   => 'Early',
            'present' => 'Present',
            'late'    => 'Late',
            'absent'  => 'Absent',
            default   => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match($this->status) {
            'early'   => '#0284C7',
            'present' => '#059669',
            'late'    => '#D97706',
            'absent'  => '#DC2626',
            default   => '#64748B',
        };
    }
}
