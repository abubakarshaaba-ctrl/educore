<?php
namespace App\Http\Controllers;

use App\Models\FeePaymentPlan;
use App\Models\FeeInstallment;
use App\Models\InvoicePaymentPlan;
use App\Models\Invoice;
use App\Models\NotificationQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FeePaymentPlanController extends Controller
{
    // ── List Plans ───────────────────────────────────────────────────
    public function index()
    {
        $plans = FeePaymentPlan::orderByDesc('is_default')->orderBy('name')->get();

        $stats = [
            'active_plans'       => $plans->where('is_active', true)->count(),
            'invoices_on_plan'   => InvoicePaymentPlan::count(),
            'overdue_installments' => FeeInstallment::overdue()->count(),
            'due_this_week'      => FeeInstallment::dueSoon(7)->count(),
            'collected_this_month' => FeeInstallment::where('status', 'paid')
                ->whereMonth('paid_date', now()->month)->sum('amount_paid'),
        ];

        return view('fees.plans.index', compact('plans', 'stats'));
    }

    // ── Create Plan ──────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:100'],
            'description'         => ['nullable', 'string'],
            'installments_count'  => ['required', 'integer', 'min:2', 'max:6'],
            'surcharge_pct'       => ['nullable', 'numeric', 'min:0', 'max:50'],
            'is_default'          => ['boolean'],
            // Installment percentages
            'percentages'         => ['required', 'array'],
            'percentages.*'       => ['required', 'integer', 'min:1', 'max:100'],
            'due_days'            => ['required', 'array'],
            'due_days.*'          => ['required', 'integer', 'min:0'],
        ]);

        // Validate percentages sum to 100
        $total = array_sum($data['percentages']);
        if ($total !== 100) {
            return back()->withErrors(['percentages' => "Installment percentages must add up to 100% (currently {$total}%)"]);
        }

        $schedule = [];
        foreach ($data['percentages'] as $i => $pct) {
            $schedule[] = [
                'installment' => $i + 1,
                'percentage'  => $pct,
                'due_days'    => (int)($data['due_days'][$i] ?? 0),
                'label'       => $this->ordinal($i + 1) . ' Installment',
            ];
        }

        if ($request->boolean('is_default')) {
            FeePaymentPlan::where('is_default', true)->update(['is_default' => false]);
        }

        FeePaymentPlan::create([
            'name'                => $data['name'],
            'description'         => $data['description'],
            'installments_count'  => $data['installments_count'],
            'installment_schedule'=> $schedule,
            'surcharge_pct'       => $data['surcharge_pct'] ?? 0,
            'is_active'           => true,
            'is_default'          => $request->boolean('is_default'),
        ]);

        return back()->with('success', 'Payment plan created.');
    }

    // ── Assign Plan to Invoice ───────────────────────────────────────
    public function assignToInvoice(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'plan_id'    => ['required', 'exists:fee_payment_plans,id'],
            'start_date' => ['required', 'date'],
        ]);

        $plan = FeePaymentPlan::findOrFail($data['plan_id']);

        // Can't reassign if already paid
        if ($invoice->status === 'paid') {
            return back()->withErrors(['error' => 'Cannot assign a payment plan to a fully paid invoice.']);
        }

        DB::transaction(function () use ($invoice, $plan, $data) {
            // Remove existing plan if any
            InvoicePaymentPlan::where('invoice_id', $invoice->id)->delete();
            FeeInstallment::where('invoice_id', $invoice->id)->delete();

            // Apply surcharge if any
            $totalAmount = $invoice->total_amount;
            if ($plan->surcharge_pct > 0) {
                $totalAmount = $totalAmount * (1 + $plan->surcharge_pct / 100);
                $invoice->update(['total_amount' => round($totalAmount, 2)]);
            }

            // Create plan assignment
            $invPlan = InvoicePaymentPlan::create([
                'tenant_id'  => $invoice->tenant_id,
                'invoice_id' => $invoice->id,
                'plan_id'    => $plan->id,
            ]);

            $startDate = Carbon::parse($data['start_date']);
            $remaining = $totalAmount - $invoice->amount_paid;

            // Create installment records
            foreach ($plan->installment_schedule as $slot) {
                $slotAmount = round($totalAmount * ($slot['percentage'] / 100), 2);
                $dueDate    = $startDate->copy()->addDays($slot['due_days']);
                $paidAmount = 0;

                // If already partially paid, apply it to earliest installment
                if ($remaining < $totalAmount && $paidAmount < $slotAmount) {
                    $paidAmount = min($invoice->amount_paid, $slotAmount);
                }

                $status = 'pending';
                if ($paidAmount >= $slotAmount) $status = 'paid';
                elseif ($paidAmount > 0) $status = 'partial';
                elseif ($dueDate->isPast()) $status = 'overdue';

                FeeInstallment::create([
                    'tenant_id'               => $invoice->tenant_id,
                    'invoice_id'              => $invoice->id,
                    'invoice_payment_plan_id' => $invPlan->id,
                    'installment_number'      => $slot['installment'],
                    'amount_due'              => $slotAmount,
                    'amount_paid'             => $paidAmount,
                    'due_date'                => $dueDate->toDateString(),
                    'status'                  => $status,
                ]);
            }

            // Update invoice
            $nextDue = FeeInstallment::where('invoice_id', $invoice->id)
                ->where('status', '!=', 'paid')
                ->orderBy('due_date')->value('due_date');

            $invoice->update([
                'has_payment_plan'    => true,
                'next_installment_due'=> $nextDue,
            ]);
        });

        return back()->with('success', "Payment plan \"{$plan->name}\" assigned to invoice. {$plan->installments_count} installments created.");
    }

    // ── Pay Installment ──────────────────────────────────────────────
    public function payInstallment(Request $request, FeeInstallment $installment)
    {
        $data = $request->validate([
            'amount'         => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string'],
            'reference'      => ['nullable', 'string', 'max:100'],
        ]);

        $amount    = min((float)$data['amount'], $installment->balance);
        $invoice   = $installment->invoice;
        $tenantId  = auth()->user()->tenant_id;

        DB::transaction(function () use ($installment, $amount, $data, $invoice, $tenantId) {
            $installment->amount_paid += $amount;

            if ($installment->amount_paid >= $installment->amount_due) {
                $installment->status    = 'paid';
                $installment->paid_date = today()->toDateString();
            } elseif ($installment->amount_paid > 0) {
                $installment->status = 'partial';
            }
            $installment->save();

            // Update parent invoice
            $invoice->amount_paid += $amount;
            $allPaid = FeeInstallment::where('invoice_id', $invoice->id)
                ->where('status', '!=', 'paid')->doesntExist();
            $invoice->status = $allPaid ? 'paid' : ($invoice->amount_paid > 0 ? 'partially_paid' : 'unpaid');

            // Next installment due
            $nextDue = FeeInstallment::where('invoice_id', $invoice->id)
                ->where('status', '!=', 'paid')
                ->orderBy('due_date')->value('due_date');
            $invoice->next_installment_due = $nextDue;
            $invoice->save();

            // Record transaction
            \App\Models\PaymentTransaction::create([
                'tenant_id'        => $tenantId,
                'invoice_id'       => $invoice->id,
                'student_id'       => $invoice->student_id,
                'amount_paid'      => $amount,
                'gateway'          => $data['payment_method'],
                'gateway_reference'=> $data['reference'] ?? 'INSTALL-' . $installment->id . '-' . date('YmdHis'),
                'status'           => 'success',
                'paid_at'          => now(),
                'gateway_response' => ['note' => "Installment {$installment->installment_number} payment"],
            ]);
        });

        return back()->with('success', '₦' . number_format($amount) . ' payment recorded for installment ' . $installment->installment_number . '.');
    }

    // ── Send Installment Reminders ───────────────────────────────────
    public function sendReminders(Request $request)
    {
        $days    = (int)$request->get('days', 3);
        $pending = FeeInstallment::with(['invoice.student.guardians'])
            ->where('status', '!=', 'paid')
            ->where('reminder_sent', false)
            ->where('due_date', '<=', now()->addDays($days)->toDateString())
            ->get();

        $sent = 0;
        foreach ($pending as $inst) {
            $guardian = $inst->invoice?->student?->guardians?->first();
            if (!$guardian?->phone) continue;

            $balance = number_format($inst->balance);
            $dueDate = $inst->due_date->format('d M Y');
            $student = $inst->invoice->student?->full_name;

            NotificationQueue::create([
                'tenant_id' => auth()->user()->tenant_id,
                'channel'   => 'sms',
                'recipient' => $guardian->phone,
                'body'      => "Dear {$guardian->name}, Installment {$inst->installment_number} payment of ₦{$balance} for {$student} is due on {$dueDate}. Please pay promptly. " . auth()->user()->tenant?->name,
                'gateway'   => 'termii',
                'status'    => 'pending',
            ]);

            $inst->update(['reminder_sent' => true]);
            $sent++;
        }

        return back()->with('success', "Reminders queued for {$sent} installment(s) due within {$days} days.");
    }

    // ── Overdue Installments Dashboard ───────────────────────────────
    public function overdue()
    {
        // Auto-mark overdue
        FeeInstallment::where('status', 'pending')
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        $overdue  = FeeInstallment::with(['invoice.student'])
            ->where('status', 'overdue')
            ->orderBy('due_date')->paginate(30);
        $dueSoon  = FeeInstallment::with(['invoice.student'])
            ->dueSoon(7)->orderBy('due_date')->get();

        $totalOverdue = FeeInstallment::where('status','overdue')
            ->selectRaw('SUM(amount_due - amount_paid) as total')->value('total');

        return view('fees.plans.overdue', compact('overdue', 'dueSoon', 'totalOverdue'));
    }

    // ── Toggle plan active ───────────────────────────────────────────
    public function toggle(FeePaymentPlan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);
        return back()->with('success', 'Plan ' . ($plan->is_active ? 'activated' : 'deactivated') . '.');
    }

    // ── Delete plan (only if no invoices use it) ─────────────────────
    public function destroy(FeePaymentPlan $plan)
    {
        if (InvoicePaymentPlan::where('plan_id', $plan->id)->exists()) {
            return back()->withErrors(['error' => 'Cannot delete plan — it is assigned to invoices.']);
        }
        $plan->delete();
        return back()->with('success', 'Payment plan deleted.');
    }

    private function ordinal(int $n): string
    {
        $suffix = ['th','st','nd','rd'];
        $v = $n % 100;
        return $n . ($suffix[($v - 20) % 10] ?? $suffix[$v] ?? $suffix[0]);
    }
}
