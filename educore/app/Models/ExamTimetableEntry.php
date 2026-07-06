<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamTimetableEntry extends BaseTenantModel
{
    protected $table = 'exam_timetable_entries';

    protected $fillable = [
        'tenant_id', 'exam_period_id', 'class_level_id', 'subject_id',
        'exam_date', 'exam_session_id', 'venue',
    ];

    protected function casts(): array
    {
        return ['exam_date' => 'date'];
    }

    public function examPeriod(): BelongsTo { return $this->belongsTo(ExamPeriod::class); }
    public function classLevel(): BelongsTo { return $this->belongsTo(ClassLevel::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function examSession(): BelongsTo { return $this->belongsTo(ExamSession::class); }
    public function supervisors(): HasMany { return $this->hasMany(ExamSupervisor::class); }
}
