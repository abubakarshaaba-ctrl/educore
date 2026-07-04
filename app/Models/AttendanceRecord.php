<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'student_id', 'class_arm_id', 'term_id',
        'marked_by', 'attendance_date', 'status', 'remark',
    ];

    protected function casts(): array
    {
        return ['attendance_date' => 'date'];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function classArm(): BelongsTo { return $this->belongsTo(ClassArm::class); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function markedBy(): BelongsTo { return $this->belongsTo(User::class, 'marked_by'); }

    public function isPresent(): bool { return $this->status === 'present'; }
    public function isAbsent(): bool  { return $this->status === 'absent'; }
}
