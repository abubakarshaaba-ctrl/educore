<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassArmSubject extends BaseTenantModel
{
    protected $table = 'class_arm_subjects';

    protected $fillable = [
        'tenant_id',
        'class_arm_id',
        'subject_id',
        'teacher_id',
        'session_id',
    ];

    public function classArm(): BelongsTo  { return $this->belongsTo(ClassArm::class); }
    public function subject(): BelongsTo   { return $this->belongsTo(Subject::class); }
    public function teacher(): BelongsTo   { return $this->belongsTo(User::class, 'teacher_id'); }
    public function session(): BelongsTo   { return $this->belongsTo(AcademicSession::class, 'session_id'); }
}
