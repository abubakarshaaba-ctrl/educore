<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlatformBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class MobilePlatformBillingController extends Controller
{
    public function __construct(private PlatformBillingService $billing) {}

    public function index(Request $request): JsonResponse
    {
        $this->guard($request);
        abort_unless(Schema::hasTable('platform_invoices'), 503, 'Platform invoice storage is unavailable.');

        $data = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'pending', 'paid', 'overdue', 'cancelled'])],
            'tenant_id' => ['nullable', 'integer', Rule::exists('tenants', 'id')],
        ]);
        $status = $data['status'] ?? 'all';
        $tenantId = isset($data['tenant_id']) ? (int) $data['tenant_id'] : null;

        $base = DB::table('platform_invoices');
        $invoices = DB::table('platform_invoices')
            ->join('tenants', 'tenants.id', '=', 'platform_invoices.tenant_id')
            ->select('platform_invoices.*', 'tenants.name as school_name')
            ->when($status !== 'all', fn ($query) => $query->where('platform_invoices.status', $status))
            ->when($tenantId, fn ($query) => $query->where('platform_invoices.tenant_id', $tenantId))
            ->orderByDesc('platform_invoices.created_at')
            ->limit(100)
            ->get()
            ->map(fn ($invoice): array => $this->invoicePayload($invoice));

        $tenants = Tenant::query()->orderBy('name')->limit(500)->get(['id', 'name', 'status'])->map(fn (Tenant $tenant): array => [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'status' => $tenant->status,
        ]);

        return response()->json([
            'summary' => [
                'total_invoiced' => (float) (clone $base)->sum('amount'),
                'total_paid' => (float) (clone $base)->where('status', 'paid')->sum('amount'),
                'total_overdue' => (float) (clone $base)->where('status', 'overdue')->sum('amount'),
                'pending_count' => (int) (clone $base)->where('status', 'pending')->count(),
            ],
            'invoices' => $invoices,
            'tenants' => $tenants,
            'selected' => ['status' => $status, 'tenant_id' => $tenantId],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', Rule::exists('tenants', 'id')],
            'billing_cycle' => ['required', Rule::in(['termly', 'annual'])],
            'capacity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $tenant = Tenant::findOrFail($data['tenant_id']);
        $invoice = $this->billing->generateInvoice(
            tenant: $tenant,
            billingCycle: $data['billing_cycle'],
            requestedCapacity: (int) $data['capacity'],
            dueDate: $data['due_date'],
            notes: $data['notes'] ?? null,
            actor: $user,
            request: $request,
        );

        return response()->json([
            'message' => "Invoice {$invoice->invoice_number} generated for {$tenant->name}.",
            'invoice' => $this->invoicePayload($invoice, $tenant->name),
        ], 201);
    }

    public function settle(Request $request, int $invoice): JsonResponse
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(['bank_transfer', 'card', 'cash', 'pos', 'other'])],
            'payment_ref' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->billing->settleInvoice(
            invoiceId: $invoice,
            paymentMethod: $data['payment_method'],
            paymentReference: $data['payment_ref'] ?? null,
            actor: $user,
            request: $request,
        );

        return response()->json([
            'message' => $result['processed']
                ? 'Invoice marked as paid and the school subscription was extended.'
                : 'This invoice was already paid; no duplicate payment was recorded.',
            'processed' => $result['processed'],
            'invoice' => $this->invoicePayload($result['invoice'], $result['tenant']->name),
            'tenant' => [
                'id' => $result['tenant']->id,
                'name' => $result['tenant']->name,
                'status' => $result['tenant']->status,
                'subscription_expires_at' => $result['tenant']->subscription_expires_at?->toDateString(),
                'students_capacity' => $result['tenant']->students_capacity,
            ],
        ]);
    }

    private function invoicePayload(object $invoice, ?string $schoolName = null): array
    {
        return [
            'id' => (int) $invoice->id,
            'tenant_id' => (int) $invoice->tenant_id,
            'school' => $schoolName ?? ($invoice->school_name ?? null),
            'invoice_number' => $invoice->invoice_number,
            'amount' => (float) $invoice->amount,
            'student_count' => (int) ($invoice->student_count ?? 0),
            'billing_cycle' => $invoice->billing_cycle,
            'status' => $invoice->status,
            'due_date' => $invoice->due_date,
            'paid_at' => $invoice->paid_at ?? null,
            'payment_method' => $invoice->payment_method ?? null,
            'payment_ref' => $invoice->payment_ref ?? null,
            'notes' => $invoice->notes ?? null,
            'created_at' => $invoice->created_at ?? null,
        ];
    }

    private function guard(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless($user->isSuperAdmin(), 403, 'Platform Super Admin access required.');
        return $user;
    }
}
