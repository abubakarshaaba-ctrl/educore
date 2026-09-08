<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassLevel;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentTransaction;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobileFeesController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['all', 'unpaid', 'partially_paid', 'paid', 'waived', 'overpaid'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);
        $search = trim((string) ($data['q'] ?? ''));
        $status = (string) ($data['status'] ?? 'all');
        $perPage = (int) ($data['per_page'] ?? 30);

        $base = Invoice::where('tenant_id', $tenantId);
        $invoices = (clone $base)
            ->with(['student:id,first_name,last_name,admission_number', 'term:id,name,session_id', 'session:id,name'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('student', fn ($studentQuery) => $studentQuery
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('admission_number', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate($perPage);

        $transactions = PaymentTransaction::where('tenant_id', $tenantId)
            ->where('status', 'success')
            ->with(['student:id,first_name,last_name,admission_number', 'invoice:id,invoice_number'])
            ->latest('paid_at')
            ->limit(50)
            ->get()
            ->map(fn (PaymentTransaction $payment): array => [
                'id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'invoice_number' => $payment->invoice?->invoice_number,
                'student' => $payment->student?->full_name,
                'admission_number' => $payment->student?->admission_number,
                'reference' => $payment->gateway_reference,
                'gateway' => $payment->gateway,
                'amount' => (float) $payment->amount_paid,
                'currency' => $payment->currency ?: 'NGN',
                'paid_by_name' => $payment->paid_by_name,
                'paid_by_phone' => $payment->paid_by_phone,
                'paid_at' => $payment->paid_at?->toIso8601String(),
            ]);

        $billed = (float) (clone $base)->sum('total_amount');
        $collected = (float) (clone $base)->sum('amount_paid');

        return response()->json([
            'contract_version' => 1,
            'capabilities' => ['manage' => $user->canManage('fees')],
            'metrics' => [
                'billed' => $billed,
                'collected' => $collected,
                'outstanding' => max(0, $billed - $collected),
                'successful_transactions' => PaymentTransaction::where('tenant_id', $tenantId)->where('status', 'success')->count(),
            ],
            'status_options' => [
                ['key' => 'all', 'label' => 'All'],
                ['key' => 'unpaid', 'label' => 'Unpaid'],
                ['key' => 'partially_paid', 'label' => 'Partially paid'],
                ['key' => 'paid', 'label' => 'Paid'],
                ['key' => 'waived', 'label' => 'Waived'],
                ['key' => 'overpaid', 'label' => 'Overpaid'],
            ],
            'terms' => Term::where('tenant_id', $tenantId)
                ->with('session:id,name')
                ->latest('id')
                ->get()
                ->map(fn (Term $term): array => [
                    'id' => $term->id,
                    'name' => $term->name,
                    'session' => $term->session?->name,
                ]),
            'class_levels' => ClassLevel::where('tenant_id', $tenantId)
                ->orderBy('order_index')
                ->get(['id', 'name']),
            'invoices' => collect($invoices->items())->map(fn (Invoice $invoice): array => $this->invoicePayload($invoice)),
            'transactions' => $transactions,
            'selected' => ['search' => $search, 'status' => $status],
            'meta' => [
                'page' => $invoices->currentPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
                'last_page' => $invoices->lastPage(),
                'has_more' => $invoices->hasMorePages(),
            ],
        ]);
    }

    public function recordPayment(Request $request, int $invoice)
    {
        $user = $this->guard($request, manage: true);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'paid_by_name' => ['required', 'string', 'max:150'],
            'paid_by_phone' => ['nullable', 'string', 'max:30'],
            'gateway' => ['required', Rule::in(['cash', 'bank_transfer', 'paystack', 'monnify'])],
        ]);

        $result = DB::transaction(function () use ($user, $invoice, $data): array {
            $locked = Invoice::where('tenant_id', $user->tenant_id)
                ->whereKey($invoice)
                ->lockForUpdate()
                ->firstOrFail();

            $balance = max(0, (float) $locked->total_amount - (float) $locked->amount_paid);
            $amount = round((float) $data['amount'], 2);
            if ($balance <= 0) {
                throw ValidationException::withMessages(['amount' => 'This invoice has no outstanding balance.']);
            }
            if ($amount > $balance + 0.0001) {
                throw ValidationException::withMessages(['amount' => 'Payment cannot exceed the outstanding invoice balance.']);
            }

            $payment = PaymentTransaction::create([
                'tenant_id' => $user->tenant_id,
                'invoice_id' => $locked->id,
                'student_id' => $locked->student_id,
                'gateway_reference' => 'MAN-'.Str::upper((string) Str::ulid()),
                'gateway' => $data['gateway'],
                'amount_paid' => $amount,
                'currency' => 'NGN',
                'status' => 'success',
                'paid_by_name' => trim($data['paid_by_name']),
                'paid_by_phone' => filled($data['paid_by_phone'] ?? null) ? trim($data['paid_by_phone']) : null,
                'paid_at' => now(),
            ]);

            $newAmountPaid = round((float) $locked->amount_paid + $amount, 2);
            $newStatus = $newAmountPaid >= (float) $locked->total_amount ? 'paid' : 'partially_paid';
            $locked->update(['amount_paid' => $newAmountPaid, 'status' => $newStatus]);
            $locked->refresh()->load(['student:id,first_name,last_name,admission_number', 'term:id,name,session_id', 'session:id,name']);

            return [
                'message' => 'Payment recorded successfully.',
                'invoice' => $this->invoicePayload($locked),
                'payment' => [
                    'id' => $payment->id,
                    'reference' => $payment->gateway_reference,
                    'amount' => (float) $payment->amount_paid,
                    'gateway' => $payment->gateway,
                    'paid_at' => $payment->paid_at?->toIso8601String(),
                ],
            ];
        });

        return response()->json($result, 201);
    }

    public function generate(Request $request)
    {
        $user = $this->guard($request, manage: true);
        $data = $request->validate([
            'term_id' => ['required', Rule::exists('terms', 'id')->where('tenant_id', $user->tenant_id)],
            'class_level_id' => ['required', Rule::exists('class_levels', 'id')->where('tenant_id', $user->tenant_id)],
        ]);

        $term = Term::where('tenant_id', $user->tenant_id)->findOrFail($data['term_id']);
        $level = ClassLevel::where('tenant_id', $user->tenant_id)->with('classArms:id,tenant_id,class_level_id')->findOrFail($data['class_level_id']);
        $studentIds = Student::where('tenant_id', $user->tenant_id)
            ->whereIn('current_class_arm_id', $level->classArms->pluck('id'))
            ->billingEligible()
            ->pluck('id');
        $structures = FeeStructure::where('tenant_id', $user->tenant_id)
            ->where('class_level_id', $level->id)
            ->where('term_id', $term->id)
            ->where('is_active', true)
            ->with('feeCategory:id,name')
            ->get();

        if ($structures->isEmpty()) {
            throw ValidationException::withMessages(['class_level_id' => 'No active fee structure exists for this class and term.']);
        }

        $generated = 0;
        $skipped = 0;
        $total = round((float) $structures->sum('amount'), 2);

        DB::transaction(function () use ($user, $studentIds, $term, $structures, $total, &$generated, &$skipped): void {
            foreach ($studentIds as $studentId) {
                $existing = Invoice::where('tenant_id', $user->tenant_id)
                    ->where('student_id', $studentId)
                    ->where('term_id', $term->id)
                    ->where('session_id', $term->session_id)
                    ->exists();
                if ($existing) {
                    $skipped++;
                    continue;
                }

                $invoice = Invoice::create([
                    'tenant_id' => $user->tenant_id,
                    'student_id' => $studentId,
                    'term_id' => $term->id,
                    'session_id' => $term->session_id,
                    'invoice_number' => 'INV-'.$user->tenant_id.'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
                    'total_amount' => $total,
                    'amount_paid' => 0,
                    'status' => 'unpaid',
                    'due_date' => $term->end_date,
                ]);

                foreach ($structures as $structure) {
                    InvoiceItem::create([
                        'tenant_id' => $user->tenant_id,
                        'invoice_id' => $invoice->id,
                        'fee_category_id' => $structure->fee_category_id,
                        'description' => $structure->feeCategory?->name ?? 'School fee',
                        'amount' => $structure->amount,
                    ]);
                }
                $generated++;
            }
        });

        return response()->json([
            'message' => "{$generated} fee bills prepared; {$skipped} existing bills skipped.",
            'generated' => $generated,
            'skipped' => $skipped,
        ], 201);
    }

    private function invoicePayload(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'number' => $invoice->invoice_number,
            'student' => $invoice->student?->full_name,
            'admission_number' => $invoice->student?->admission_number,
            'term' => $invoice->term?->name,
            'session' => $invoice->session?->name,
            'total' => (float) $invoice->total_amount,
            'paid' => (float) $invoice->amount_paid,
            'balance' => max(0, (float) $invoice->total_amount - (float) $invoice->amount_paid),
            'status' => $invoice->status,
            'due_date' => $invoice->due_date?->toDateString(),
        ];
    }

    private function guard(Request $request, bool $manage = false): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403, 'School fee access required.');
        abort_unless($user->tenant_id, 403, 'School fee access required.');
        abort_unless(
            $manage ? $user->canManage('fees') : $user->canAccessModule('fees'),
            403,
            $manage ? 'Fee management permission required.' : 'Fee access required.'
        );

        return $user;
    }
}
