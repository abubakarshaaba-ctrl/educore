<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSupervisor extends BaseTenantModel
{
    protected $table = 'exam_supervisors';

    protected $fillable = ['tenant_id', 'exam_timetable_entry_id', 'user_id'];

    public function entry(): BelongsTo { return $this->belongsTo(ExamTimetableEntry::class, 'exam_timetable_entry_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
