<?php

namespace App\Services;

use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollTaxBand;
use App\Models\StaffDeduction;
use App\Models\StaffDisciplinaryAction;
use App\Models\StaffSalarySetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollGenerationService
{
    /**
     * Generate one tenant payroll period using the same authoritative rules for
     * every caller (web, mobile, future jobs/imports).
     *
     * @param  array{title:string,period_start:string,period_end:string}  $data
     * @return array{period:PayrollPeriod,skipped_staff_ids:array<int,int>,skipped_staff_names:array<int,string>}
     */
    public function generate(int $tenantId, array $data): array
    {
        return DB::transaction(function () use ($tenantId, $data): array {
            $duplicateExists = PayrollPeriod::where('tenant_id', $tenantId)
                ->where('period_start', $data['period_start'])
                ->where('period_end', $data['period_end'])
                ->lockForUpdate()
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'period_start' => 'A payroll period already exists for the selected date range.',
                ]);
            }

            $period = PayrollPeriod::create([
                'tenant_id' => $tenantId,
                'title' => $data['title'],
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
            ]);

            $settings = StaffSalarySetting::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->whereHas('staff', fn ($query) => $query->payrollEligibleForPeriod(
                    $tenantId,
                    $data['period_start'],
                    $data['period_end']
                ))
                ->get();

            $bands = PayrollTaxBand::where('tenant_id', $tenantId)
                ->orderBy('order_index')
                ->get();
            $bandArray = $bands->isNotEmpty()
                ? $bands->map(fn ($band): array => [
                    'lower_bound' => $band->lower_bound,
                    'upper_bound' => $band->upper_bound,
                    'rate_percent' => $band->rate_percent,
                ])->all()
                : PayrollTaxService::defaultBands();

            $staffIds = $settings->pluck('staff_id');
            $staffDeductions = StaffDeduction::where('tenant_id', $tenantId)
                ->whereIn('staff_id', $staffIds)
                ->where('is_active', true)
                ->whereDoesntHave('disciplinaryAction')
                ->with('template')
                ->get()
                ->groupBy('staff_id');

            $disciplinaryByStaff = StaffDisciplinaryAction::where('tenant_id', $tenantId)
                ->whereIn('staff_id', $staffIds)
                ->whereIn('action_type', StaffDisciplinaryAction::FINANCE_ACTIONS)
                ->where('status', 'active')
                ->whereNull('applied_payroll_item_id')
                ->whereNotNull('amount')
                ->where('amount', '>', 0)
                ->whereDate('effective_date', '<=', $data['period_end'])
                ->lockForUpdate()
                ->get()
                ->groupBy('staff_id');

            $totalGross = 0.0;
            $totalNet = 0.0;
            $totalDeductions = 0.0;
            $skippedStaffIds = [];

            foreach ($settings as $setting) {
                if ((float) $setting->basic_salary <= 0) {
                    $skippedStaffIds[] = (int) $setting->staff_id;
                    continue;
                }

                $gross = (float) $setting->basic_salary
                    + (float) $setting->housing_allowance
                    + (float) $setting->transport_allowance
                    + (float) $setting->other_allowances;

                $pensionBase = (float) $setting->basic_salary
                    + (float) $setting->housing_allowance
                    + (float) $setting->transport_allowance;
                $pension = round($pensionBase * 0.08, 2);

                $rentRelief = PayrollTaxService::rentRelief((float) ($setting->annual_rent_paid ?? 0));
                $tax = PayrollTaxService::monthlyPaye($gross, $pension, $rentRelief, $bandArray);

                $breakdown = [];
                $otherDeductions = 0.0;

                foreach ($staffDeductions->get($setting->staff_id, collect()) as $deduction) {
                    $amount = $deduction->amountFor($gross);
                    $otherDeductions += $amount;
                    $breakdown[] = [
                        'label' => $deduction->label(),
                        'amount' => round($amount, 2),
                    ];
                }

                foreach ($disciplinaryByStaff->get($setting->staff_id, collect()) as $disciplinaryAction) {
                    $amount = round((float) $disciplinaryAction->amount, 2);
                    $otherDeductions += $amount;
                    $breakdown[] = [
                        'label' => 'Disciplinary deduction: '.$disciplinaryAction->actionLabel()
                            .' - '.$disciplinaryAction->offenceLabel(),
                        'amount' => $amount,
                    ];
                }

                $totalDeduction = $tax + $pension + $otherDeductions;
                $net = $gross - $totalDeduction;

                $payrollItem = PayrollItem::create([
                    'tenant_id' => $tenantId,
                    'payroll_period_id' => $period->id,
                    'staff_id' => $setting->staff_id,
                    'basic_salary' => $setting->basic_salary,
                    'housing_allowance' => $setting->housing_allowance,
                    'transport_allowance' => $setting->transport_allowance,
                    'other_allowances' => $setting->other_allowances,
                    'gross_pay' => $gross,
                    'tax_deduction' => $tax,
                    'pension_deduction' => $pension,
                    'other_deductions' => round($otherDeductions, 2),
                    'deduction_breakdown' => $breakdown !== [] ? $breakdown : null,
                    'total_deductions' => round($totalDeduction, 2),
                    'net_pay' => round($net, 2),
                    'bank_name' => $setting->bank_name,
                    'account_number' => $setting->account_number,
                    'account_name' => $setting->account_name,
                ]);

                foreach ($disciplinaryByStaff->get($setting->staff_id, collect()) as $disciplinaryAction) {
                    $disciplinaryAction->update([
                        'applied_payroll_item_id' => $payrollItem->id,
                        'applied_at' => now(),
                    ]);
                    $disciplinaryAction->staffDeduction?->update(['is_active' => false]);
                }

                $totalGross += $gross;
                $totalDeductions += $totalDeduction;
                $totalNet += $net;
            }

            $period->update([
                'total_gross' => round($totalGross, 2),
                'total_deductions' => round($totalDeductions, 2),
                'total_net' => round($totalNet, 2),
            ]);

            $skippedNames = $skippedStaffIds === []
                ? []
                : User::where('tenant_id', $tenantId)
                    ->whereIn('id', $skippedStaffIds)
                    ->orderBy('name')
                    ->pluck('name')
                    ->values()
                    ->all();

            return [
                'period' => $period->fresh(),
                'skipped_staff_ids' => $skippedStaffIds,
                'skipped_staff_names' => $skippedNames,
            ];
        });
    }
}
