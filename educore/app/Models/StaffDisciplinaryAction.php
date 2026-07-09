<?php

namespace App\Models;

class StaffDisciplinaryAction extends BaseTenantModel
{
    public const OFFENCE_TYPES = [
        'late_coming'                 => 'Late Coming',
        'late_assignment_submission'  => 'Late Submission of Assignment/Scores',
        'insubordination'             => 'Insubordination',
        'unprofessional_conduct'      => 'Unprofessional Conduct/Ethics',
        'absenteeism'                 => 'Unexcused Absenteeism',
        'other'                       => 'Other',
    ];

    public const ACTION_TYPES = [
        'warning'                => 'Warning (No Financial Impact)',
        'surcharge'              => 'Surcharge (Fine)',
        'suspension_without_pay' => 'Suspension Without Pay',
        'dismissal'              => 'Dismissal from Service',
        'termination'            => 'Termination of Appointment',
    ];

    public const FINANCE_ACTIONS = ['surcharge', 'suspension_without_pay'];
    public const EMPLOYMENT_EXIT_ACTIONS = ['dismissal', 'termination'];

    protected $fillable = [
        'tenant_id', 'staff_id', 'offence_type', 'offence_description', 'action_type',
        'amount', 'suspension_start_date', 'suspension_end_date', 'effective_date',
        'staff_deduction_id', 'status', 'recorded_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'suspension_start_date' => 'date',
            'suspension_end_date'   => 'date',
            'effective_date'        => 'date',
            'amount'                => 'float',
        ];
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function staffDeduction()
    {
        return $this->belongsTo(StaffDeduction::class);
    }

    public function isFinanceLinked(): bool
    {
        return in_array($this->action_type, self::FINANCE_ACTIONS, true);
    }

    public function isEmploymentExit(): bool
    {
        return in_array($this->action_type, self::EMPLOYMENT_EXIT_ACTIONS, true);
    }

    public function offenceLabel(): string
    {
        return self::OFFENCE_TYPES[$this->offence_type] ?? ucwords(str_replace('_', ' ', $this->offence_type));
    }

    public function actionLabel(): string
    {
        return self::ACTION_TYPES[$this->action_type] ?? ucwords(str_replace('_', ' ', $this->action_type));
    }
}
