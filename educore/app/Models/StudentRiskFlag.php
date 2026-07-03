<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentRiskFlag extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'student_id', 'term_id', 'class_arm_id',
        'academic_risk', 'attendance_risk', 'fee_risk', 'subjects_failed',
        'composite_risk', 'risk_level', 'flags',
        'status', 'intervention_note',
        'acknowledged_by', 'acknowledged_at',
        'resolved_by', 'resolved_at', 'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'flags'           => 'array',
            'acknowledged_at' => 'datetime',
            'resolved_at'     => 'datetime',
            'computed_at'     => 'datetime',
        ];
    }

    public function student(): BelongsTo    { return $this->belongsTo(Student::class); }
    public function term(): BelongsTo       { return $this->belongsTo(Term::class); }
    public function classArm(): BelongsTo   { return $this->belongsTo(ClassArm::class); }
    public function acknowledgedBy(): BelongsTo { return $this->belongsTo(User::class, 'acknowledged_by'); }
    public function resolvedBy(): BelongsTo { return $this->belongsTo(User::class, 'resolved_by'); }

    public function riskColor(): string
    {
        return match($this->risk_level) {
            'critical' => '#DC2626',
            'high'     => '#EA580C',
            'medium'   => '#D97706',
            default    => '#16A34A',
        };
    }

    public function riskBg(): string
    {
        return match($this->risk_level) {
            'critical' => '#FEF2F2',
            'high'     => '#FFF7ED',
            'medium'   => '#FFFBEB',
            default    => '#F0FDF4',
        };
    }

    public function flagLabels(): array
    {
        $map = [
            'avg_below_threshold'     => 'Average below pass mark',
            'avg_critically_low'      => 'Average critically low (< 30%)',
            'subjects_failed'         => "Failed {$this->subjects_failed} subject(s)",
            'high_absenteeism'        => 'High absenteeism',
            'critical_absenteeism'    => 'Critical absenteeism (> 50% absent)',
            'no_attendance_recorded'  => 'No attendance recorded',
            'fees_overdue'            => 'Outstanding fee balance',
            'no_scores_recorded'      => 'No scores recorded this term',
            'declining_performance'   => 'Performance declining vs last term',
        ];

        return array_map(fn($f) => $map[$f] ?? ucwords(str_replace('_', ' ', $f)),
            $this->flags ?? []);
    }
}
