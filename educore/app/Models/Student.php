<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends BaseTenantModel
{
    use SoftDeletes;

    public const STATUS_APPLICANT = 'applicant';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_LEFT = 'left';
    public const STATUS_WITHDRAWN = 'withdrawn';
    public const STATUS_TRANSFERRED_OUT = 'transferred_out';
    public const STATUS_GRADUATED = 'graduated';

    public const LIFECYCLE_STATUSES = [
        self::STATUS_APPLICANT,
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
        self::STATUS_LEFT,
        self::STATUS_WITHDRAWN,
        self::STATUS_TRANSFERRED_OUT,
        self::STATUS_GRADUATED,
    ];

    public const ARCHIVE_STATUSES = [
        self::STATUS_LEFT,
        self::STATUS_WITHDRAWN,
        self::STATUS_TRANSFERRED_OUT,
        self::STATUS_GRADUATED,
    ];

    public const BILLING_ELIGIBLE_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_APPLICANT => 'Applicant',
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_SUSPENDED => 'Suspended',
        self::STATUS_LEFT => 'Left',
        self::STATUS_WITHDRAWN => 'Withdrawn',
        self::STATUS_TRANSFERRED_OUT => 'Transferred Out',
        self::STATUS_GRADUATED => 'Graduated',
    ];

    protected $fillable = [
        'tenant_id',
        'user_id',
        'admission_number',
        'first_name',
        'last_name',
        'middle_name',
        'gender',
        'date_of_birth',
        'state_of_origin',
        'lga_of_origin',
        'religion',
        'blood_group',
        'genotype',
        'passport_photo_path',
        'current_class_arm_id',
        'status',
        'admission_date',
        'graduation_date',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth'   => 'date',
            'admission_date'  => 'date',
            'graduation_date' => 'date',
        ];
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentClassArm(): BelongsTo
    {
        return $this->belongsTo(ClassArm::class, 'current_class_arm_id');
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'guardian_student')
                    ->withPivot('is_primary_contact')
                    ->withTimestamps();
    }

    public function primaryGuardian()
    {
        return $this->guardians()->wherePivot('is_primary_contact', true)->first();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function enrolmentHistory(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class)
            ->orderByDesc('start_date')
            ->orderByDesc('id');
    }

    public function currentEnrollment(): HasOne
    {
        return $this->hasOne(StudentEnrollment::class)
            ->where('is_current', true)
            ->latestOfMany();
    }

    public function classTransfers(): HasMany
    {
        return $this->hasMany(StudentClassTransfer::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(StudentStatusHistory::class);
    }

    public function healthRecord() { return $this->hasOne(\App\Models\StudentHealthRecord::class); }
    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function termSummaries(): HasMany
    {
        return $this->hasMany(TermlySummary::class);
    }

    public function termlySummaries(): HasMany
    {
        return $this->termSummaries();
    }

    public function termySummaries(): HasMany
    {
        return $this->termSummaries();
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function cbtSessions(): HasMany
    {
        return $this->hasMany(CbtStudentSession::class);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeBillingEligible($query)
    {
        return $query->whereIn('status', self::BILLING_ELIGIBLE_STATUSES);
    }

    public function isArchivedLifecycleStatus(): bool
    {
        return in_array($this->status, self::ARCHIVE_STATUSES, true);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? str($this->status ?? 'unknown')->replace('_', ' ')->title()->toString();
    }

    public function scopeArchived($query)
    {
        return $query->whereIn('status', self::ARCHIVE_STATUSES);
    }

    public function scopeInClass($query, int $classArmId)
    {
        return $query->where('current_class_arm_id', $classArmId);
    }
    // ── NEW: Subject selection relationships ────────────────────────────
    public function subjectSelections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StudentSubjectSelection::class);
    }

    public function activeSubjectSelections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StudentSubjectSelection::class)->where('is_active', true);
    }

    // ── NEW: Transport ─────────────────────────────────────────────────
    public function transportAssignment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\TransportAssignment::class);
    }

    // ── NEW: Academic track helper ─────────────────────────────────────
    /**
     * The academic track of this student (inherited from current class arm).
     */
    public function academicTrack(): ?AcademicTrack
    {
        return $this->currentClassArm?->academicTrack;
    }

    /**
     * The class level of this student (from current class arm).
     */
    public function currentClassLevel(): ?ClassLevel
    {
        return $this->currentClassArm?->classLevel;
    }

    /**
     * Returns the FINAL list of subjects this student is taking:
     *  - All compulsory subjects from class_level_subjects (for their level + track)
     *  - All elected elective subjects from student_subject_selections
     *
     * Uses student_subject_selections as the canonical record when available.
     * Falls back to class_level_subjects compulsory list if no selections exist.
     */
    public function currentEligibleSubjects(
        ?int $sessionId = null,
        bool $forceFromRules = false
    ): \Illuminate\Support\Collection {
        $arm     = $this->currentClassArm;
        if (!$arm) return collect();

        $levelId = $arm->class_level_id;
        $trackId = $arm->academic_track_id;

        // Check if student has selections recorded for this session
        $selectionsQuery = $this->subjectSelections()
            ->where('class_level_id', $levelId)
            ->where('is_active', true);

        if ($sessionId) {
            $selectionsQuery->where('session_id', $sessionId);
        }

        $selections = $selectionsQuery->get();

        // If selections exist, use them (they include compulsory + elected electives)
        if ($selections->isNotEmpty() && !$forceFromRules) {
            return Subject::whereIn('id', $selections->pluck('subject_id'))
                ->orderBy('name')->get();
        }

        // Fall back: derive from ClassLevelSubject rules (compulsory only)
        return Subject::whereHas('classLevelRules', function ($q) use ($levelId, $trackId) {
            $q->where('class_level_id', $levelId)
              ->where('subject_status', 'compulsory')
              ->where('is_active', true)
              ->where(function ($q2) use ($trackId) {
                  $q2->whereNull('academic_track_id');
                  if ($trackId) $q2->orWhere('academic_track_id', $trackId);
              });
        })->orderBy('name')->get();
    }

    /**
     * Sync compulsory subjects into student_subject_selections.
     * Called when a student is assigned to a class arm.
     */
    public function syncCompulsorySubjects(?int $sessionId = null): int
    {
        $arm = $this->currentClassArm;
        if (!$arm) return 0;

        $levelId = $arm->class_level_id;
        $trackId = $arm->academic_track_id;
        $tid     = $this->tenant_id;

        $rules = ClassLevelSubject::where('tenant_id', $tid)
            ->where('class_level_id', $levelId)
            ->where('subject_status', 'compulsory')
            ->where('is_active', true)
            ->where(function ($q) use ($trackId) {
                $q->whereNull('academic_track_id');
                if ($trackId) $q->orWhere('academic_track_id', $trackId);
            })->get();

        $synced = 0;
        foreach ($rules as $rule) {
            StudentSubjectSelection::updateOrCreate(
                [
                    'tenant_id'         => $tid,
                    'student_id'        => $this->id,
                    'subject_id'        => $rule->subject_id,
                    'session_id'        => $sessionId,
                ],
                [
                    'class_level_id'    => $levelId,
                    'academic_track_id' => $trackId,
                    'selection_type'    => 'compulsory',
                    'is_active'        => true,
                ]
            );
            $synced++;
        }
        return $synced;
    }


}
