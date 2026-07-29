<?php
namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    public function index()
    {
        $user   = auth()->user();
        if (!$user->isAdmin() && !$user->isSuperAdmin()) abort(403);

        $tenant = $user->tenant;

        $invoices = DB::table('platform_invoices')
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get();

        $totalPaid = DB::table('platform_invoices')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'paid')
            ->sum('amount');

        $paymentSettings = PlatformSetting::valuesFor([
            'paystack_public_key',
            'monnify_api_key',
            'monnify_secret_key',
            'monnify_contract_code',
            'bank_transfer_bank_name',
            'bank_transfer_account_name',
            'bank_transfer_account_number',
        ]);
        $gatewayConfigured = filled($paymentSettings['paystack_public_key'] ?? null)
            || (
                filled($paymentSettings['monnify_api_key'] ?? null)
                && filled($paymentSettings['monnify_secret_key'] ?? null)
                && filled($paymentSettings['monnify_contract_code'] ?? null)
            );
        $bankTransferConfigured = filled($paymentSettings['bank_transfer_bank_name'] ?? null)
            && filled($paymentSettings['bank_transfer_account_name'] ?? null)
            && filled($paymentSettings['bank_transfer_account_number'] ?? null);
        $paymentConfigured = $gatewayConfigured || $bankTransferConfigured;

        $hasOutstandingInvoice = $invoices->contains(fn ($inv) => $inv->status !== 'paid');

        $studentCount = PricingService::activeStudentCount($tenant->id);
        $capacity     = PricingService::capacityFor($tenant);
        $atCapacity   = !PricingService::canAddStudent($tenant);

        return view('billing.self-service', compact(
            'tenant', 'invoices', 'totalPaid', 'gatewayConfigured', 'bankTransferConfigured',
            'paymentConfigured', 'hasOutstandingInvoice', 'studentCount', 'capacity', 'atCapacity'
        ));
    }

    /**
     * Self-service: raises a pending platform invoice for this tenant's own,
     * automatically-computed pay-per-student amount (see PricingService),
     * and sends the admin straight to the payment page. Reuses any existing
     * unpaid invoice for the same cycle so repeated clicks don't stack
     * duplicates.
     */
    public function generateInvoice(Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isSuperAdmin()) abort(403);

        $tenant = $user->tenant;

        $data = $request->validate([
            'billing_cycle'          => ['required', 'in:termly,annual'],
            'anticipated_enrollment' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);

        $studentCount = PricingService::activeStudentCount($tenant->id);

        // Schools may buy ahead for the students they expect to enroll, but
        // an invoice can never reduce capacity below current active usage.
        $capacity = max($studentCount, (int) $data['anticipated_enrollment']);

        if (PricingService::isFree($capacity)) {
            return back()->withErrors(['plan' => 'Anticipated enrollment of ' . $capacity . ' students is covered by the free plan, so no invoice is needed.']);
        }

        $amount = $data['billing_cycle'] === 'annual'
            ? PricingService::annualAmount($capacity)
            : PricingService::termlyAmount($capacity);

        $existing = DB::table('platform_invoices')
            ->where('tenant_id', $tenant->id)
            ->where('billing_cycle', $data['billing_cycle'])
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            if (filled($existing->payment_ref) && $existing->payment_method === 'bank_transfer') {
                return back()->withErrors([
                    'payment' => 'A bank transfer is already awaiting verification for this billing cycle. Contact support before changing the enrollment estimate.',
                ]);
            }

            // Repair zero-value legacy invoices and reuse one pending invoice
            // for each cycle instead of stacking duplicates.
            DB::table('platform_invoices')->where('id', $existing->id)->update([
                'amount' => $amount,
                'student_count' => $capacity,
                'due_date' => now()->addDays(7)->toDateString(),
                'payment_method' => null,
                'payment_ref' => null,
                'notes' => 'Self-service estimate for ' . $capacity . ' anticipated students.',
                'updated_at' => now(),
            ]);

            return redirect()->route('super.billing.pay', $existing->id);
        }

        $ref = 'INV-' . strtoupper(Str::random(8));

        $invoiceId = DB::table('platform_invoices')->insertGetId([
            'tenant_id'      => $tenant->id,
            'plan_id'        => null,
            'invoice_number' => $ref,
            'amount'         => $amount,
            'student_count'  => $capacity,
            'billing_cycle'  => $data['billing_cycle'],
            'status'         => 'pending',
            'due_date'       => now()->addDays(7)->toDateString(),
            'notes'          => 'Self-service estimate for ' . $capacity . ' anticipated students.',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return redirect()->route('super.billing.pay', $invoiceId)
            ->with('success', "Invoice {$ref} created for {$capacity} anticipated students. Choose a payment method to continue.");
    }

    public function submitBankTransfer(Request $request, int $invoice)
    {
        $user = $request->user();
        abort_unless($user && ($user->isAdmin() || $user->isSuperAdmin()), 403);

        $record = DB::table('platform_invoices')->where('id', $invoice)->first();
        abort_if(!$record, 404);
        abort_if(!$user->isSuperAdmin() && (int) $user->tenant_id !== (int) $record->tenant_id, 403);

        if ($record->status === 'paid') {
            return back()->withErrors(['payment' => 'This invoice has already been paid.']);
        }
        if ((float) $record->amount <= 0) {
            return back()->withErrors(['payment' => 'This invoice has no payable amount. Recalculate it from the billing page first.']);
        }

        $data = $request->validate([
            'transfer_reference' => ['required', 'string', 'min:3', 'max:100'],
        ]);

        DB::table('platform_invoices')->where('id', $record->id)->update([
            'payment_method' => 'bank_transfer',
            'payment_ref' => trim($data['transfer_reference']),
            'updated_at' => now(),
        ]);

        $route = $user->isSuperAdmin() ? 'super.billing' : 'billing.subscription';

        return redirect()->route($route)->with(
            'success',
            'Bank transfer reference submitted. EduCore will verify the transfer before activating the paid capacity.'
        );
    }
}
