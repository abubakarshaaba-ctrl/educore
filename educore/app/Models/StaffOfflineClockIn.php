<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffOfflineClockIn extends BaseTenantModel
{
    protected $table = 'staff_offline_clockins';

    protected $fillable = [
        'tenant_id', 'user_id', 'clocked_by',
        'attendance_date', 'clock_in_time', 'qr_token',
        'lat', 'lng', 'status', 'reject_reason',
    ];

    protected function casts(): array { return ['attendance_date' => 'date']; }

    public function staff(): BelongsTo    { return $this->belongsTo(User::class, 'user_id'); }
    public function clockedBy(): BelongsTo{ return $this->belongsTo(User::class, 'clocked_by'); }
}
