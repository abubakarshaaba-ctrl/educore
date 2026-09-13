<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MobileSubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->guardSchoolAdmin($request);
        $tenant = $user->tenant;
        $activeStudents = PricingService::activeStudentCount((int) $tenant->id);
        $capacity = PricingService::capacityFor($tenant);
        $settings = $this->paymentSettings();

        $invoices = DB::table('platform_invoices')
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($invoice): array => $this->invoicePayload($invoice))
            ->values();

        $outstanding = $invoices->first(fn (array $invoice): bool => in_array($invoice['status'], ['pending', 'overdue'], true));
        $expiresAt = $tenant->subscription_expires_at;
        $daysRemaining = $expiresAt ? max(0, now()->startOfDay()->diffInDays($expiresAt, false)) : null;

        return response()->json([
            'contract_version' => 1,
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'status' => (string) $tenant->status,
                'subscription_expires_at' => $expiresAt?->toIso8601String(),
                'days_remaining' => $daysRemaining,
                'students_capacity' => $capacity,
                'active_students' => $activeStudents,
                'is_free_tier' => PricingService::isFree($capacity),
            ],
            'pricing' => [
                'free_threshold' => PricingService::freeStudentLimit(),
                'rate_per_student_per_term' => PricingService::ratePerStudentPerTerm(),
                'termly_amount' => PricingService::termlyAmount(max(1, $capacity)),
                'annual_amount' => PricingService::annualAmount(max(1, $capacity)),
            ],
            'gateways' => $this->gatewayPayloads($settings),
            'outstanding_invoice' => $outstanding,
            'invoices' => $invoices,
            'payments' => [],
        ]);
    }

    public function createInvoice(Request $request): JsonResponse
    {
        $user = $this->guardSchoolAdmin($request);
        $tenant = $user->tenant;
        $data = $request->validate([
            'billing_cycle' => ['required', Rule::in(['termly', 'annual'])],
            'anticipated_enrollment' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);

        $activeStudents = PricingService::activeStudentCount((int) $tenant->id);
        $capacity = max($activeStudents, (int) $data['anticipated_enrollment']);
        if (PricingService::isFree($capacity)) {
            return response()->json([
                'free' => true,
                'message' => "Anticipated enrollment of {$capacity} students is covered by the free plan.",
                'amount' => 0,
                'capacity' => $capacity,
                'invoice' => null,
            ]);
        }

        $amount = $data['billing_cycle'] === 'annual'
            ? PricingService::annualAmount($capacity)
            : PricingService::termlyAmount($capacity);

        $invoice = DB::transaction(function () use ($tenant, $data, $capacity, $amount) {
            $existing = DB::table('platform_invoices')
                ->where('tenant_id', $tenant->id)
                ->where('billing_cycle', $data['billing_cycle'])
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                abort_if(
                    filled($existing->payment_ref) && $existing->payment_method === 'bank_transfer',
                    422,
                    'A bank transfer is already awaiting verification for this billing cycle.'
                );
                DB::table('platform_invoices')->where('id', $existing->id)->update([
                    'amount' => $amount,
                    'student_count' => $capacity,
                    'due_date' => now()->addDays(7)->toDateString(),
                    'payment_method' => null,
                    'payment_ref' => null,
                    'notes' => 'Mobile self-service estimate for '.$capacity.' anticipated students.',
                    'updated_at' => now(),
                ]);
                return DB::table('platform_invoices')->where('id', $existing->id)->first();
            }

            $id = DB::table('platform_invoices')->insertGetId([
                'tenant_id' => $tenant->id,
                'plan_id' => null,
                'invoice_number' => 'INV-'.strtoupper(Str::random(8)),
                'amount' => $amount,
                'student_count' => $capacity,
                'billing_cycle' => $data['billing_cycle'],
                'status' => 'pending',
                'due_date' => now()->addDays(7)->toDateString(),
                'notes' => 'Mobile self-service estimate for '.$capacity.' anticipated students.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return DB::table('platform_invoices')->where('id', $id)->first();
        });

        return response()->json([
            'free' => false,
            'amount' => (float) $invoice->amount,
            'capacity' => (int) $invoice->student_count,
            'invoice' => $this->invoicePayload($invoice),
        ], 201);
    }

    public function submitBankTransfer(Request $request, int $invoice): JsonResponse
    {
        $user = $this->guardSchoolAdmin($request);
        $data = $request->validate([
            'transfer_reference' => [
                'required', 'string', 'min:3', 'max:100',
                Rule::unique('platform_invoices', 'payment_ref')->ignore($invoice),
            ],
        ]);

        $record = DB::transaction(function () use ($invoice, $user, $data) {
            $record = DB::table('platform_invoices')->where('id', $invoice)->lockForUpdate()->first();
            abort_if(!$record, 404);
            abort_unless((int) $record->tenant_id === (int) $user->tenant_id, 403);
            abort_unless(in_array($record->status, ['pending', 'overdue'], true), 422, 'This invoice is not payable.');
            abort_if((float) $record->amount <= 0, 422, 'This invoice has no payable amount.');

            DB::table('platform_invoices')->where('id', $record->id)->update([
                'payment_method' => 'bank_transfer',
                'payment_ref' => trim($data['transfer_reference']),
                'updated_at' => now(),
            ]);
            return DB::table('platform_invoices')->where('id', $record->id)->first();
        });

        return response()->json([
            'message' => 'Bank transfer reference submitted. EduCore will verify the transfer before activating the paid capacity.',
            'invoice' => $this->invoicePayload($record),
        ]);
    }

    public function checkout(Request $request, int $invoice): JsonResponse
    {
        $this->guardSchoolAdmin($request);
        return response()->json([
            'message' => 'Use the configured bank-transfer option from the mobile app. Online subscription checkout is not configured for this endpoint.',
        ], 503);
    }

    public function verify(Request $request): JsonResponse
    {
        $this->guardSchoolAdmin($request);
        return response()->json(['message' => 'Subscription verification is completed by the configured platform payment workflow.'], 503);
    }

    private function guardSchoolAdmin(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless($user->isAdmin() && $user->tenant_id, 403, 'School administrator billing access required.');
        abort_unless($user->tenant, 403, 'A school account is required for self-service billing.');
        return $user;
    }

    private function paymentSettings(): array
    {
        return PlatformSetting::valuesFor([
            'bank_transfer_bank_name',
            'bank_transfer_account_name',
            'bank_transfer_account_number',
        ]);
    }

    private function gatewayPayloads(array $settings): array
    {
        $bankAvailable = filled($settings['bank_transfer_bank_name'] ?? null)
            && filled($settings['bank_transfer_account_name'] ?? null)
            && filled($settings['bank_transfer_account_number'] ?? null);

        return $bankAvailable ? [[
            'name' => 'bank_transfer',
            'available' => true,
            'bank_name' => $settings['bank_transfer_bank_name'],
            'account_name' => $settings['bank_transfer_account_name'],
            'account_number' => $settings['bank_transfer_account_number'],
        ]] : [];
    }

    private function invoicePayload(object $invoice): array
    {
        return [
            'id' => (int) $invoice->id,
            'number' => (string) $invoice->invoice_number,
            'amount' => (float) $invoice->amount,
            'student_count' => (int) ($invoice->student_count ?? 0),
            'billing_cycle' => (string) $invoice->billing_cycle,
            'status' => (string) $invoice->status,
            'payment_status' => $invoice->status === 'paid' ? 'paid' : null,
            'due_date' => $invoice->due_date ?? null,
            'paid_at' => $invoice->paid_at ?? null,
            'payment_method' => $invoice->payment_method ?? null,
            'payment_reference' => $invoice->payment_ref ?? null,
        ];
    }
}
