<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonPlan extends BaseTenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'teacher_id', 'subject_id', 'class_level_id', 'class_arm_id',
        'term_id', 'curriculum_type', 'topic', 'subtopic', 'week_number',
        'plan_date', 'duration_minutes', 'status',
        // NERDC/TRCN sections
        'previous_knowledge', 'entry_behaviour', 'behavioural_objectives',
        'instructional_materials', 'reference_materials', 'set_induction',
        'presentation', 'class_activity', 'evaluation', 'assignment', 'conclusion',
        // British sections
        'learning_objectives', 'success_criteria', 'starter_activity',
        'differentiation', 'plenary', 'assessment_for_learning',
        'ai_generated', 'lesson_notes',
    ];

    protected function casts(): array
    {
        return [
            'plan_date'     => 'date',
            'ai_generated'  => 'boolean',
        ];
    }

    public function teacher(): BelongsTo    { return $this->belongsTo(User::class, 'teacher_id'); }
    public function subject(): BelongsTo    { return $this->belongsTo(Subject::class); }
    public function classLevel(): BelongsTo { return $this->belongsTo(ClassLevel::class); }
    public function classArm(): BelongsTo   { return $this->belongsTo(ClassArm::class); }
    public function term(): BelongsTo       { return $this->belongsTo(Term::class); }

    public function isNerdc(): bool    { return $this->curriculum_type === 'nerdc'; }
    public function isBritish(): bool  { return $this->curriculum_type === 'british'; }
    public function isPublished(): bool { return $this->status === 'published'; }

    // NERDC sections in TRCN order
    public static function nerdcSections(): array
    {
        return [
            'previous_knowledge'      => 'Previous Knowledge',
            'entry_behaviour'         => 'Entry Behaviour',
            'behavioural_objectives'  => 'Behavioural Objectives',
            'instructional_materials' => 'Instructional Materials',
            'reference_materials'     => 'Reference Materials',
            'set_induction'           => 'Introduction / Set Induction',
            'presentation'            => 'Presentation / Development',
            'class_activity'          => 'Class Activity / Students\' Activity',
            'evaluation'              => 'Evaluation',
            'assignment'              => 'Assignment / Homework',
            'conclusion'              => 'Conclusion / Summary',
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
