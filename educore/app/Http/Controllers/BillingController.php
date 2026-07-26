<?php
namespace App\Http\Controllers;

use App\Services\PricingService;
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

        $gatewayConfigured = (bool) DB::table('platform_settings')
            ->where('key', 'paystack_public_key')
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->exists();

        $hasOutstandingInvoice = $invoices->contains(fn ($inv) => $inv->status !== 'paid');

        $studentCount = PricingService::activeStudentCount($tenant->id);
        $capacity     = PricingService::capacityFor($tenant);
        $atCapacity   = !PricingService::canAddStudent($tenant);

        return view('billing.self-service', compact(
            'tenant', 'invoices', 'totalPaid', 'gatewayConfigured', 'hasOutstandingInvoice', 'studentCount', 'capacity', 'atCapacity'
        ));
    }

    /**
     * Self-service: raises a pending platform invoice for this tenant's own,
     * automatically-computed pay-per-student amount (see PricingService),
     * and sends the admin straight to the payment page. Reuses any existing
     * unpaid invoice for the same cycle so repeated clicks don't stack
     * duplicates. Schools past the custom-quote threshold don't get a
     * self-service invoice — they're directed to contact EduCore instead.
     */
    public function generateInvoice(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isSuperAdmin()) abort(403);

        $tenant = $user->tenant;

        $data = $request->validate([
            'billing_cycle'    => ['required', 'in:termly,annual'],
            'target_capacity'  => ['nullable', 'integer', 'min:1'],
        ]);

        $studentCount = PricingService::activeStudentCount($tenant->id);

        // Admins can buy ahead of their current enrollment (e.g. to leave
        // headroom for pending admissions) — never less than what's in use.
        $capacity = max($studentCount, (int) ($data['target_capacity'] ?? $studentCount));

        if (PricingService::isCustomQuote($capacity)) {
            return back()->withErrors(['plan' => 'That enrollment size qualifies for custom volume pricing — contact EduCore for a tailored quote instead of a self-service invoice.']);
        }

        if (PricingService::isFree($capacity)) {
            return back()->withErrors(['plan' => 'Your current enrollment (' . $studentCount . ' students) falls under the free plan — no invoice needed.']);
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
            'notes'          => 'Self-service pay-per-student invoice — capacity for ' . $capacity . ' students.',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return redirect()->route('super.billing.pay', $invoiceId)
            ->with('success', "Invoice {$ref} created for {$capacity} students — complete payment to activate.");
    }
}
