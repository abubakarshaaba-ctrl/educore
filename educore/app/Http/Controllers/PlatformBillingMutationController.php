<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\PlatformBillingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlatformBillingMutationController extends Controller
{
    public function __construct(private PlatformBillingService $billing) {}

    public function generate(Request $request)
    {
        $this->guard($request);
        $data = $request->validate([
            'tenant_id' => ['required', Rule::exists('tenants', 'id')],
            'billing_cycle' => ['required', Rule::in(['termly', 'annual'])],
            'capacity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $tenant = Tenant::findOrFail($data['tenant_id']);
        $invoice = $this->billing->generateInvoice(
            tenant: $tenant,
            billingCycle: $data['billing_cycle'],
            requestedCapacity: (int) $data['capacity'],
            dueDate: $data['due_date'],
            notes: $data['notes'] ?? null,
            actor: $request->user(),
            request: $request,
        );

        return back()->with('success', "Invoice {$invoice->invoice_number} generated for {$tenant->name} — ₦".number_format((float) $invoice->amount).'.');
    }

    public function paid(Request $request, int $invoice)
    {
        $this->guard($request);
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(['bank_transfer', 'card', 'cash', 'pos', 'other'])],
            'payment_ref' => ['nullable', 'string', 'max:100'],
        ]);
        $result = $this->billing->settleInvoice(
            invoiceId: $invoice,
            paymentMethod: $data['payment_method'],
            paymentReference: $data['payment_ref'] ?? null,
            actor: $request->user(),
            request: $request,
        );

        return back()->with('success', $result['processed']
            ? 'Invoice marked as paid and subscription extended.'
            : 'This invoice was already paid. No duplicate payment was recorded.');
    }

    private function guard(Request $request): void
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
    }
}
