<?php
namespace App\Http\Controllers;

use App\Models\PayrollPeriod;
use App\Models\PayrollItem;
use App\Models\StaffSalarySetting;
use App\Models\User;
use App\Models\PayrollDeductionTemplate;
use App\Models\PayrollRoleTemplate;
use App\Models\StaffDeduction;
use App\Models\PayrollTaxBand;
use App\Services\PayrollTaxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PayrollController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    public function index()
    {
        $periods = PayrollPeriod::latest()->paginate(15);
        return view('payroll.index', compact('periods'));
    }

    public function create()
    {
        $staff = User::payrollEligible($this->tenantId())->orderBy('name')->get();
        return view('payroll.create', compact('staff'));
    }

    public function generatePeriod(Request $request)
    {
        $data = $request->validate([
            'title'        => ['required','string'],
            'period_start' => ['required','date'],
            'period_end'   => ['required','date','after_or_equal:period_start'],
        ]);
        $tenantId = auth()->user()->tenant_id;

        $period = DB::transaction(function () use ($data, $tenantId) {
            $period = PayrollPeriod::create(array_merge($data, ['tenant_id' => $tenantId]));

            // Auto-populate from salary settings
            $settings = StaffSalarySetting::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->whereHas('staff', fn ($query) => $query->payrollEligibleForPeriod(
                    $tenantId,
                    $data['period_start'],
                    $data['period_end']
                ))
                ->get();

            // Configurable PAYE bands — falls back to the documented default if
            // this tenant hasn't set their own (see PayrollTaxService for caveats).
            $bands = PayrollTaxBand::where('tenant_id', $tenantId)->orderBy('order_index')->get();
            $bandArray = $bands->isNotEmpty()
                ? $bands->map(fn ($b) => ['lower_bound' => $b->lower_bound, 'upper_bound' => $b->upper_bound, 'rate_percent' => $b->rate_percent])->all()
                : PayrollTaxService::defaultBands();

            // Pre-load each eligible staff member's active peculiar deductions
            // (school loan, cooperative, child school fees, etc.)
            $staffIds      = $settings->pluck('staff_id');
            $staffDedsById = StaffDeduction::where('tenant_id', $tenantId)
                ->whereIn('staff_id', $staffIds)
                ->where('is_active', true)
                ->with('template')
                ->get()
                ->groupBy('staff_id');

            $totalGross = 0; $totalNet = 0; $totalDed = 0;
            $skippedUnconfigured = [];

            foreach ($settings as $s) {
                // A salary-settings row with no basic salary means it was never
                // actually filled in — skip it rather than generating a silent
                // ₦0.00 payslip, and report it so it's obvious why someone is missing.
                if ((float) $s->basic_salary <= 0) {
                    $skippedUnconfigured[] = $s->staff_id;
                    continue;
                }

                $gross = $s->basic_salary + $s->housing_allowance + $s->transport_allowance + $s->other_allowances;

                // Pension is calculated on Basic + Housing + Transport only (not total gross).
                $pensionBase = $s->basic_salary + $s->housing_allowance + $s->transport_allowance;
                $pension     = round($pensionBase * 0.08, 2);

                $rentRelief = PayrollTaxService::rentRelief((float) ($s->annual_rent_paid ?? 0));
                $tax        = PayrollTaxService::monthlyPaye($gross, $pension, $rentRelief, $bandArray);

                // Per-staff peculiar deductions (loans, cooperative, school fees, etc.)
                $breakdown = [];
                $otherDed  = 0.0;
                foreach ($staffDedsById->get($s->staff_id, collect()) as $sd) {
                    $amt = $sd->amountFor($gross);
                    $otherDed += $amt;
                    $breakdown[] = ['label' => $sd->label(), 'amount' => round($amt, 2)];
                }

                $totalD = $tax + $pension + $otherDed;
                $net    = $gross - $totalD;

                PayrollItem::create([
                    'tenant_id'          => $tenantId,
                    'payroll_period_id'  => $period->id,
                    'staff_id'           => $s->staff_id,
                    'basic_salary'       => $s->basic_salary,
                    'housing_allowance'  => $s->housing_allowance,
                    'transport_allowance'=> $s->transport_allowance,
                    'other_allowances'   => $s->other_allowances,
                    'gross_pay'          => $gross,
                    'tax_deduction'      => $tax,
                    'pension_deduction'  => $pension,
                    'other_deductions'   => round($otherDed, 2),
                    'deduction_breakdown'=> !empty($breakdown) ? $breakdown : null,
                    'total_deductions'   => round($totalD, 2),
                    'net_pay'            => round($net, 2),
                    'bank_name'          => $s->bank_name,
                    'account_number'     => $s->account_number,
                    'account_name'       => $s->account_name,
                ]);
                $totalGross += $gross; $totalDed += $totalD; $totalNet += $net;
            }

            $period->update(['total_gross' => $totalGross, 'total_deductions' => $totalDed, 'total_net' => $totalNet]);
            return [$period, $skippedUnconfigured];
        });

        [$period, $skippedUnconfigured] = $period;

        if (!empty($skippedUnconfigured)) {
            $names = User::whereIn('id', $skippedUnconfigured)->pluck('name')->implode(', ');
            return redirect()->route('payroll.show', $period)->with('success', 'Payroll generated.')
                ->with('warning', "Skipped " . count($skippedUnconfigured) . " staff with no salary configured (no payslip generated): {$names}. Set their salary in Payroll → Salary Settings.");
        }

        return redirect()->route('payroll.show', $period)->with('success', 'Payroll generated.');
    }

    public function show(PayrollPeriod $period)
    {
        $items = PayrollItem::where('payroll_period_id', $period->id)
            ->with('staff')->get();
        return view('payroll.show', compact('period', 'items'));
    }

    public function approve(PayrollPeriod $period)
    {
        $period->update(['status' => 'approved', 'approved_by' => auth()->id()]);
        return back()->with('success', 'Payroll approved.');
    }

    public function markPaid(PayrollPeriod $period)
    {
        $period->update(['status' => 'paid', 'payment_date' => now()->toDateString()]);
        PayrollItem::where('payroll_period_id', $period->id)->update(['payment_status' => 'paid']);
        return back()->with('success', 'Payroll marked as paid.');
    }

    public function salarySettings()
    {
        $staff = User::payrollEligible($this->tenantId())->orderBy('name')->get();
        $settings = StaffSalarySetting::where('tenant_id', $this->tenantId())
                     ->get()->keyBy('staff_id');
        return view('payroll.salary-settings', compact('staff', 'settings'));
    }

    public function saveSalarySetting(Request $request)
    {
        $data = $request->validate([
            'staff_id'           => ['required', Rule::exists('users', 'id')->where(fn ($query) => $query
                ->where('tenant_id', $this->tenantId())
                ->where('is_super_admin', false)
                ->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('employment_status')->orWhere('employment_status', User::STAFF_STATUS_ACTIVE))
                ->whereIn('role', User::staffRoleNames()))],
            'basic_salary'       => ['required','numeric','min:0'],
            'housing_allowance'  => ['nullable','numeric','min:0'],
            'transport_allowance'=> ['nullable','numeric','min:0'],
            'other_allowances'   => ['nullable','numeric','min:0'],
            'annual_rent_paid'   => ['nullable','numeric','min:0'],
            'bank_name'          => ['nullable','string'],
            'account_number'     => ['nullable','string','size:10'],
            'account_name'       => ['nullable','string'],
            'tax_identification_number' => ['nullable','string','max:30'],
            'bvn'                       => ['nullable','digits:11'],
            'nin'                       => ['nullable','digits:11'],
        ]);
        StaffSalarySetting::updateOrCreate(
            ['tenant_id' => $this->tenantId(), 'staff_id' => $data['staff_id']],
            array_merge($data, ['is_active' => true])
        );
        return back()->with('success', 'Salary setting saved.');
    }

    // ── Staff Deductions (peculiar, per-staff) ──────────────────────────
    public function staffDeductions()
    {
        $staff = User::payrollEligible($this->tenantId())->orderBy('name')->get();
        $templates = PayrollDeductionTemplate::where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->whereIn('type', ['loan', 'other'])
            ->orderBy('name')->get();
        $assigned = StaffDeduction::where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->with('template')
            ->get()
            ->groupBy('staff_id');

        return view('payroll.staff-deductions', compact('staff', 'templates', 'assigned'));
    }

    public function storeStaffDeduction(Request $request)
    {
        $data = $request->validate([
            'staff_id'                       => ['required', Rule::exists('users', 'id')->where(fn ($query) => $query
                ->where('tenant_id', $this->tenantId())
                ->where('is_super_admin', false)
                ->where('is_active', true)
                ->whereIn('role', User::staffRoleNames()))],
            'payroll_deduction_template_id'  => ['required', Rule::exists('payroll_deduction_templates', 'id')->where('tenant_id', $this->tenantId())],
            'custom_amount'                  => ['nullable', 'numeric', 'min:0'],
            'notes'                          => ['nullable', 'string', 'max:150'],
        ]);

        $exists = StaffDeduction::where('tenant_id', $this->tenantId())
            ->where('staff_id', $data['staff_id'])
            ->where('payroll_deduction_template_id', $data['payroll_deduction_template_id'])
            ->where('is_active', true)
            ->exists();

        if ($exists) {
            return back()->withErrors(['error' => 'This staff member already has that deduction assigned.']);
        }

        StaffDeduction::create(array_merge($data, ['is_active' => true]));
        return back()->with('success', 'Deduction assigned.');
    }

    public function destroyStaffDeduction(StaffDeduction $deduction)
    {
        $deduction->delete();
        return back()->with('success', 'Deduction removed.');
    }

    // ── PAYE Tax Bands (configurable, per tenant) ───────────────────────
    public function taxBands()
    {
        $bands = PayrollTaxBand::where('tenant_id', $this->tenantId())->orderBy('order_index')->get();
        $usingDefaults = $bands->isEmpty();
        if ($usingDefaults) {
            $bands = collect(PayrollTaxService::defaultBands())->map(fn ($b) => (object) $b);
        }
        return view('payroll.tax-bands', compact('bands', 'usingDefaults'));
    }

    public function saveTaxBands(Request $request)
    {
        $data = $request->validate([
            'bands'                => ['required', 'array', 'min:1'],
            'bands.*.lower_bound'  => ['required', 'numeric', 'min:0'],
            'bands.*.upper_bound'  => ['nullable', 'numeric', 'gt:bands.*.lower_bound'],
            'bands.*.rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        PayrollTaxBand::where('tenant_id', $this->tenantId())->delete();
        foreach (array_values($data['bands']) as $i => $row) {
            PayrollTaxBand::create([
                'tenant_id'    => $this->tenantId(),
                'lower_bound'  => $row['lower_bound'],
                'upper_bound'  => $row['upper_bound'] ?? null,
                'rate_percent' => $row['rate_percent'],
                'order_index'  => $i,
            ]);
        }

        return back()->with('success', 'PAYE tax bands saved.');
    }

    // ── Payroll Templates ─────────────────────────────────────────────
    public function templates()
    {
        $deductions = PayrollDeductionTemplate::where('is_active', true)->get();
        $roleTemplates = PayrollRoleTemplate::all();
        $roles = ['admin','principal','vice_principal','form_teacher','subject_teacher','teacher','accountant'];
        return view('payroll.templates', compact('deductions', 'roleTemplates', 'roles'));
    }

    public function storeDeduction(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'name'        => ['required','string','max:100'],
            'type'        => ['required','in:tax,pension,loan,other'],
            'calc_method' => ['required','in:percentage,fixed'],
            'value'       => ['required','numeric','min:0'],
            'description' => ['nullable','string'],
        ]);
        PayrollDeductionTemplate::create($data);
        return back()->with('success', 'Deduction template saved.');
    }

    public function destroyDeduction(PayrollDeductionTemplate $deduction)
    {
        $deduction->delete();
        return back()->with('success', 'Deduction removed.');
    }

    public function storeRoleTemplate(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'role'               => ['required','string'],
            'label'              => ['nullable','string','max:80'],
            'basic_salary'       => ['required','numeric','min:0'],
            'housing_allowance'  => ['nullable','numeric','min:0'],
            'transport_allowance'=> ['nullable','numeric','min:0'],
            'other_allowances'   => ['nullable','numeric','min:0'],
            'deduction_ids'      => ['nullable','array'],
            'deduction_ids.*'    => [Rule::exists('payroll_deduction_templates', 'id')->where('tenant_id', $this->tenantId())],
        ]);
        $data['housing_allowance']   = $data['housing_allowance'] ?? 0;
        $data['transport_allowance'] = $data['transport_allowance'] ?? 0;
        $data['other_allowances']    = $data['other_allowances'] ?? 0;

        PayrollRoleTemplate::updateOrCreate(
            ['tenant_id' => $this->tenantId(), 'role' => $data['role']],
            array_merge($data, ['tenant_id' => $this->tenantId()])
        );
        return back()->with('success', 'Role payroll template saved.');
    }

    // ── Payslip view (all items for a period) ─────────────────────────
    public function payslip(PayrollPeriod $period)
    {
        $items  = PayrollItem::where('payroll_period_id', $period->id)
                    ->with('staff')
                    ->orderBy('id')
                    ->get();
        $tenant = auth()->user()->tenant;
        return view('payroll.payslip', compact('period', 'items', 'tenant'));
    }

    // ── Individual payslip PDF ────────────────────────────────────────
    public function payslipPdf(PayrollPeriod $period, PayrollItem $item)
    {
        abort_if($item->payroll_period_id !== $period->id, 403);
        $item->load('staff');
        $tenant = auth()->user()->tenant;
        return view('payroll.payslip-pdf', compact('period', 'item', 'tenant'));
    }

    // ── Full payroll PDF download ─────────────────────────────────────
    public function downloadPdf(PayrollPeriod $period)
    {
        $items = PayrollItem::where('payroll_period_id', $period->id)
            ->with('staff')->orderBy('id')->get();
        $tenant = auth()->user()->tenant;
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('payroll.payroll-pdf', compact('period', 'items', 'tenant'))
            ->setPaper('a4', 'landscape');
        $filename = 'Payroll_' . str_replace([' ', '/'], '_', $period->title) . '.pdf';
        return $pdf->download($filename);
    }

    // ── Full payroll Excel download (HTML table → .xls, opens in Excel/Sheets) ──
    public function downloadExcel(PayrollPeriod $period)
    {
        $items = PayrollItem::where('payroll_period_id', $period->id)
            ->with('staff')->orderBy('id')->get();
        $tenant   = auth()->user()->tenant;
        $filename = 'Payroll_' . str_replace([' ', '/'], '_', $period->title) . '.xls';

        $html  = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        $html .= '<head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Payroll</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>';
        $html .= '<table border="1" style="border-collapse:collapse;font-family:Arial;font-size:11pt">';
        $html .= '<tr><td colspan="15" style="font-weight:bold;font-size:14pt;background:#071E45;color:white;padding:8px">' . e(optional($tenant)->name) . '</td></tr>';
        $html .= '<tr><td colspan="15" style="padding:4px 8px">Payroll Period: <b>' . e($period->title) . '</b> &nbsp;|&nbsp; Status: ' . ucfirst($period->status) . ' &nbsp;|&nbsp; Generated: ' . now()->format('d M Y') . '</td></tr>';
        $html .= '<tr><td colspan="15"></td></tr>';
        $html .= '<tr style="background:#1e3a5f;color:white;font-weight:bold"><td>#</td><td>Staff Name</td><td>Role</td><td>Basic Salary</td><td>Housing Allow.</td><td>Transport Allow.</td><td>Other Allow.</td><td>Gross Pay</td><td>Tax Deduction</td><td>Pension Deduction</td><td>Other Deductions</td><td>Net Pay</td><td>Bank Name</td><td>Account Number</td><td>Payment Status</td></tr>';
        foreach ($items as $i => $item) {
            $html .= '<tr>';
            $html .= '<td>' . ($i + 1) . '</td>';
            $html .= '<td>' . e(optional($item->staff)->name ?? '') . '</td>';
            $html .= '<td>' . e(optional($item->staff)->role ?? '') . '</td>';
            $html .= '<td x:num>' . $item->basic_salary . '</td>';
            $html .= '<td x:num>' . $item->housing_allowance . '</td>';
            $html .= '<td x:num>' . $item->transport_allowance . '</td>';
            $html .= '<td x:num>' . $item->other_allowances . '</td>';
            $html .= '<td x:num style="font-weight:bold">' . $item->gross_pay . '</td>';
            $html .= '<td x:num>' . $item->tax_deduction . '</td>';
            $html .= '<td x:num>' . $item->pension_deduction . '</td>';
            $html .= '<td x:num>' . ($item->other_deductions ?? 0) . '</td>';
            $html .= '<td x:num style="font-weight:bold;color:green">' . $item->net_pay . '</td>';
            $html .= '<td>' . e($item->bank_name ?? '') . '</td>';
            $html .= '<td>' . e($item->account_number ?? '') . '</td>';
            $html .= '<td>' . ucfirst($item->payment_status) . '</td>';
            $html .= '</tr>';
        }
        $html .= '<tr style="background:#f1f5f9;font-weight:bold"><td colspan="3">TOTALS</td>';
        $html .= '<td x:num>' . $period->total_gross . '</td><td></td><td></td><td></td>';
        $html .= '<td x:num>' . $period->total_gross . '</td>';
        $html .= '<td x:num>' . $items->sum('tax_deduction') . '</td>';
        $html .= '<td x:num>' . $items->sum('pension_deduction') . '</td><td></td>';
        $html .= '<td x:num>' . $period->total_net . '</td><td colspan="3"></td></tr>';
        $html .= '</table></body></html>';

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, no-store',
            'Pragma'              => 'no-cache',
        ]);
    }

}
