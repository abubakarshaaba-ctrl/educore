<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonPlan extends BaseTenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'teacher_id', 'subject_id', 'class_level_id', 'class_arm_id',
        'term_id', 'curriculum_type', 'topic', 'subtopic', 'week_number', 'lesson_number', 'lesson_time', 'average_age', 'sex',
        'plan_date', 'duration_minutes', 'status',
        // NERDC/TRCN sections
        'previous_knowledge', 'entry_behaviour', 'behavioural_objectives',
        'instructional_materials', 'reference_materials', 'set_induction',
        'presentation', 'class_activity', 'evaluation', 'assignment', 'conclusion',
        // British sections
        'learning_objectives', 'success_criteria', 'starter_activity',
        'differentiation', 'plenary', 'assessment_for_learning',
        'ai_generated', 'lesson_notes', 'structured_plan', 'note_depth', 'approved_at',
        'approved_by', 'current_note_revision',
    ];

    protected function casts(): array
    {
        return [
            'plan_date'     => 'date',
            'ai_generated'  => 'boolean',
            'structured_plan' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo    { return $this->belongsTo(User::class, 'teacher_id'); }
    public function subject(): BelongsTo    { return $this->belongsTo(Subject::class); }
    public function classLevel(): BelongsTo { return $this->belongsTo(ClassLevel::class); }
    public function classArm(): BelongsTo   { return $this->belongsTo(ClassArm::class); }
    public function term(): BelongsTo       { return $this->belongsTo(Term::class); }
    public function noteRevisions(): HasMany { return $this->hasMany(LessonNoteRevision::class); }
    public function currentNoteRevision() { return $this->hasOne(LessonNoteRevision::class)->ofMany('revision', 'max'); }

    public function isNerdc(): bool    { return $this->curriculum_type === 'nerdc'; }
    public function isBritish(): bool  { return $this->curriculum_type === 'british'; }
    public function isPublished(): bool { return $this->status === 'published'; }

    // NERDC sections in TRCN order
    public static function nerdcSections(): array
    {
        return [
            'entry_behaviour'         => 'Entry Behaviour',
            'previous_knowledge'      => 'Previous / Background Knowledge',
            'behavioural_objectives'  => 'Behavioural Objectives',
            'instructional_materials' => 'Instructional Resources',
            'set_induction'           => 'Introduction',
            'presentation'            => 'Presentation',
            'evaluation'              => 'Evaluation',
            'assignment'              => 'Assignment',
            'reference_materials'     => 'Reference',
        ];
    }

    public static function britishSections(): array
    {
        return [
            'learning_objectives'    => 'Learning Objectives',
            'success_criteria'       => 'Success Criteria (WALT/WILF)',
            'starter_activity'       => 'Starter Activity',
            'presentation'           => 'Main Teaching Sequence',
            'class_activity'         => 'Student Activities',
            'differentiation'        => 'Differentiation (SEN/EAL/G&T)',
            'plenary'                => 'Plenary',
            'assessment_for_learning'=> 'Assessment for Learning',
            'assignment'             => 'Homework',
        ];
    }

    public function sections(): array
    {
        return $this->isNerdc() ? self::nerdcSections() : self::britishSections();
    }
}
