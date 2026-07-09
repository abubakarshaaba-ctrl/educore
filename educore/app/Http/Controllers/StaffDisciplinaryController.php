<?php

namespace App\Http\Controllers;

use App\Models\PayrollDeductionTemplate;
use App\Models\StaffDeduction;
use App\Models\StaffDisciplinaryAction;
use App\Models\StaffSalarySetting;
use App\Models\User;
use App\Services\StaffLifecycle\ChangeStaffStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class StaffDisciplinaryController extends Controller
{
    public function index(Request $request)
    {
        $query = StaffDisciplinaryAction::with(['staff', 'recordedBy', 'staffDeduction'])->latest();

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }
        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        $actions = $query->paginate(25)->withQueryString();
        $staffList = User::activeStaff($this->tenantId())->orderBy('name')->get();

        return view('staff-discipline.index', [
            'actions'      => $actions,
            'staffList'    => $staffList,
            'offenceTypes' => StaffDisciplinaryAction::OFFENCE_TYPES,
            'actionTypes'  => StaffDisciplinaryAction::ACTION_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'staff_id'               => ['required', 'exists:users,id'],
            'offence_type'           => ['required', Rule::in(array_keys(StaffDisciplinaryAction::OFFENCE_TYPES))],
            'offence_description'    => ['nullable', 'string', 'max:2000'],
            'action_type'            => ['required', Rule::in(array_keys(StaffDisciplinaryAction::ACTION_TYPES))],
            'amount'                 => ['required_if:action_type,surcharge', 'nullable', 'numeric', 'min:0'],
            'suspension_start_date'  => ['required_if:action_type,suspension_without_pay', 'nullable', 'date'],
            'suspension_end_date'    => ['required_if:action_type,suspension_without_pay', 'nullable', 'date', 'after_or_equal:suspension_start_date'],
            'effective_date'         => ['required', 'date'],
            'notes'                  => ['nullable', 'string', 'max:2000'],
        ]);

        $staff = User::tenantStaff($this->tenantId())->whereKey($data['staff_id'])->firstOrFail();

        $action = StaffDisciplinaryAction::create([
            'tenant_id'              => $this->tenantId(),
            'staff_id'               => $staff->id,
            'offence_type'           => $data['offence_type'],
            'offence_description'    => $data['offence_description'] ?? null,
            'action_type'            => $data['action_type'],
            'suspension_start_date'  => $data['suspension_start_date'] ?? null,
            'suspension_end_date'    => $data['suspension_end_date'] ?? null,
            'effective_date'         => $data['effective_date'],
            'status'                 => 'active',
            'recorded_by'            => auth()->id(),
            'notes'                  => $data['notes'] ?? null,
        ]);

        if ($data['action_type'] === 'surcharge') {
            $this->linkPayrollDeduction($action, $staff, (float) $data['amount']);
        }

        if ($data['action_type'] === 'suspension_without_pay') {
            $amount = $this->computeSuspensionAmount($staff, $data['suspension_start_date'], $data['suspension_end_date']);
            $action->update(['amount' => $amount]);
            $this->linkPayrollDeduction($action, $staff, $amount);
        }

        if (in_array($data['action_type'], StaffDisciplinaryAction::EMPLOYMENT_EXIT_ACTIONS, true)) {
            try {
                app(ChangeStaffStatus::class)->execute(auth()->user(), $staff, [
                    'new_status'     => User::STAFF_STATUS_TERMINATED,
                    'effective_date' => $data['effective_date'],
                    'reason'         => 'Disciplinary action — ' . $action->actionLabel() . ' (' . $action->offenceLabel() . ')'
                        . ($data['notes'] ?? '' ? ': ' . $data['notes'] : ''),
                ], $request);
            } catch (ValidationException $e) {
                $action->update(['notes' => trim(($action->notes ?? '') . "\n\nEmployment status change failed: " . collect($e->errors())->flatten()->implode(' '))]);

                return back()->withErrors($e->errors())->with(
                    'success',
                    'Disciplinary action recorded, but the staff employment status could not be changed automatically — see notes on the record and update it manually from Staff → Lifecycle.'
                );
            }
        }

        return back()->with('success', 'Disciplinary action recorded for ' . $staff->name . '.');
    }

    /** Stop a surcharge/suspension deduction from continuing into future payroll cycles once applied. */
    public function deactivateDeduction(StaffDisciplinaryAction $action)
    {
        abort_if($action->tenant_id !== $this->tenantId(), 403);

        if ($action->staffDeduction) {
            $action->staffDeduction->update(['is_active' => false]);
        }

        return back()->with('success', 'Linked payroll deduction stopped — it will not apply to future payroll runs.');
    }

    public function rescind(StaffDisciplinaryAction $action)
    {
        abort_if($action->tenant_id !== $this->tenantId(), 403);

        $action->update(['status' => 'rescinded']);

        if ($action->staffDeduction) {
            $action->staffDeduction->update(['is_active' => false]);
        }

        return back()->with('success', 'Disciplinary action rescinded.');
    }

    private function linkPayrollDeduction(StaffDisciplinaryAction $action, User $staff, float $amount): void
    {
        $template = PayrollDeductionTemplate::firstOrCreate(
            ['tenant_id' => $this->tenantId(), 'name' => 'Disciplinary Deduction'],
            ['type' => 'other', 'calc_method' => 'fixed', 'value' => 0, 'is_active' => true,
             'description' => 'Fines/withheld pay arising from staff disciplinary actions. Actual amount is set per-case.']
        );

        $deduction = StaffDeduction::create([
            'tenant_id'                     => $this->tenantId(),
            'staff_id'                      => $staff->id,
            'payroll_deduction_template_id' => $template->id,
            'custom_amount'                 => $amount,
            'notes'                         => $action->actionLabel() . ' — ' . $action->offenceLabel() . ' (' . $action->effective_date->format('d M Y') . ')',
            'is_active'                     => true,
        ]);

        $action->update(['staff_deduction_id' => $deduction->id]);
    }

    private function computeSuspensionAmount(User $staff, string $start, string $end): float
    {
        $setting = StaffSalarySetting::where('tenant_id', $this->tenantId())
            ->where('staff_id', $staff->id)
            ->where('is_active', true)
            ->first();

        if (!$setting) {
            return 0.0;
        }

        $gross = (float) $setting->basic_salary + (float) $setting->housing_allowance
            + (float) $setting->transport_allowance + (float) $setting->other_allowances;

        $days = \Illuminate\Support\Carbon::parse($start)->diffInDays(\Illuminate\Support\Carbon::parse($end)) + 1;
        $dailyRate = $gross / 30;

        return round($dailyRate * $days, 2);
    }

    private function tenantId(): int
    {
        $tenantId = auth()->user()?->tenant_id;
        abort_unless($tenantId, 403, 'A tenant context is required.');

        return (int) $tenantId;
    }
}
