<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\OnlinePaymentLog;
use App\Models\PaymentGatewayConfig;
use App\Models\PaymentTransaction;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Services\PricingService;
use App\Services\SchoolFeePaymentService;
use App\Services\SubscriptionPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MobilePaymentController extends Controller
{
    public function subscription(Request $request, PricingService $pricing)
    {
        [, $tenant] = $this->schoolAdmin($request);
        $activeStudents = $pricing->activeStudentCount($tenant->id);
        $capacity = (int) ($tenant->students_capacity ?? PricingService::FREE_THRESHOLD);
        $expiresAt = $tenant->subscription_expires_at;
        $daysRemaining = $expiresAt ? max(0, (int) now()->startOfDay()->diffInDays($expiresAt->copy()->startOfDay(), false)) : null;

        return response()->json([
            'contract_version' => 2,
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'status' => $tenant->status,
                'subscription_expires_at' => $expiresAt?->toDateString(),
                'days_remaining' => $daysRemaining,
                'students_capacity' => $capacity,
                'active_students' => $activeStudents,
                'is_free_tier' => PricingService::isFree(max($activeStudents, $capacity)),
            ],
            'pricing' => [
                'free_threshold' => PricingService::FREE_THRESHOLD,
                'rate_per_student_per_term' => PricingService::PAID_RATE,
                'termly_amount' => $pricing->termlyAmount(max($activeStudents, $capacity)),
                'annual_amount' => $pricing->annualAmount(max($activeStudents, $capacity)),
            ],
            'gateways' => $this->platformGateways(),
            'outstanding_invoice' => $this->outstandingSubscriptionInvoice($tenant->id),
            'invoices' => $this->subscriptionInvoiceCollection($tenant->id),
            'payments' => $this->subscriptionPaymentCollection($tenant->id),
        ]);
    }

    public function subscriptionInvoices(Request $request)
    {
        [, $tenant] = $this->schoolAdmin($request);

        return response()->json([
            'invoices' => $this->subscriptionInvoiceCollection($tenant->id),
            'payments' => $this->subscriptionPaymentCollection($tenant->id),
        ]);
    }

    public function createSubscriptionInvoice(Request $request, PricingService $pricing)
    {
        [, $tenant] = $this->schoolAdmin($request);
        $data = $request->validate([
            'billing_cycle' => ['required', 'in:termly,annual'],
            'anticipated_enrollment' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);

        $active = $pricing->activeStudentCount($tenant->id);
        $capacity = max($active, (int) $data['anticipated_enrollment']);
        if (PricingService::isFree($capacity)) {
            return response()->json([
                'free' => true,
                'message' => 'This anticipated enrolment is covered by EduCore free tier.',
                'amount' => 0,
                'capacity' => $capacity,
            ]);
        }

        $amount = $data['billing_cycle'] === 'annual'
            ? $pricing->annualAmount($capacity)
            : $pricing->termlyAmount($capacity);

        $invoice = DB::transaction(function () use ($tenant, $data, $capacity, $amount) {
            $existing = DB::table('platform_invoices')
                ->where('tenant_id', $tenant->id)
                ->where('billing_cycle', $data['billing_cycle'])
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                abort_if($existing->payment_method === 'bank_transfer' && filled($existing->payment_ref), 422,
                    'A bank transfer is already awaiting verification for this billing cycle.');

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

        return response()->json(['free' => false, 'invoice' => $this->platformInvoicePayload($invoice)]);
    }

    public function subscriptionCheckout(Request $request, int $invoiceId)
    {
        [$user, $tenant] = $this->schoolAdmin($request);
        $data = $request->validate(['gateway' => ['required', 'in:paystack,monnify']]);
        $invoice = $this->tenantPlatformInvoice($tenant->id, $invoiceId);
        abort_if($invoice->status === 'paid', 422, 'This subscription invoice is already paid.');
        abort_if((float) $invoice->amount <= 0, 422, 'This invoice has no payable amount.');

        $reference = 'SUB-'.strtoupper(Str::random(14));
        $checkoutUrl = $data['gateway'] === 'paystack'
            ? $this->startPlatformPaystack($invoice, $user->email, $reference)
            : $this->startPlatformMonnify($invoice, $tenant, $user->email, $reference);
        abort_unless($checkoutUrl, 503, 'The selected payment gateway could not start checkout.');

        DB::table('platform_invoices')->where('id', $invoice->id)->update([
            'payment_method' => $data['gateway'].'_online',
            'payment_ref' => $reference,
            'updated_at' => now(),
        ]);

        return response()->json([
            'provider' => $data['gateway'],
            'reference' => $reference,
            'amount' => (float) $invoice->amount,
            'currency' => 'NGN',
            'checkout_url' => $checkoutUrl,
        ]);
    }

    public function submitSubscriptionBankTransfer(Request $request, int $invoiceId)
    {
        [, $tenant] = $this->schoolAdmin($request);
        $invoice = $this->tenantPlatformInvoice($tenant->id, $invoiceId);
        abort_if($invoice->status === 'paid', 422, 'This subscription invoice is already paid.');

        $data = $request->validate([
            'transfer_reference' => ['required', 'string', 'min:3', 'max:100'],
        ]);
        $reference = trim($data['transfer_reference']);

        abort_if(DB::table('platform_payments')->where('reference', $reference)->exists(), 422,
            'This transfer reference has already been used.');

        DB::table('platform_invoices')->where('id', $invoice->id)->update([
            'payment_method' => 'bank_transfer',
            'payment_ref' => $reference,
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Bank transfer reference submitted. Subscription will update only after server-side verification.',
            'invoice' => $this->platformInvoicePayload(DB::table('platform_invoices')->where('id', $invoice->id)->first()),
        ]);
    }

    public function verifySubscription(Request $request, SubscriptionPaymentService $settlement)
    {
        [, $tenant] = $this->schoolAdmin($request);
        $data = $request->validate(['reference' => ['required', 'string', 'max:120']]);
        $invoice = DB::table('platform_invoices')
            ->where('tenant_id', $tenant->id)
            ->where('payment_ref', $data['reference'])
            ->first();
        abort_unless($invoice, 404, 'Subscription payment reference not found.');

        if ($invoice->status !== 'paid') {
            $gateway = Str::before((string) $invoice->payment_method, '_');
            abort_if($gateway === 'bank', 422, 'Bank transfers require platform verification before settlement.');
            abort_unless(in_array($gateway, ['paystack', 'monnify'], true), 422, 'This payment cannot be verified online.');
            abort_unless($this->verifyPlatformPayment($gateway, $data['reference'], (float) $invoice->amount), 422,
                'Payment could not be verified yet. If payment completed, retry shortly.');
            $settlement->settle((int) $invoice->id, $data['reference'], $gateway.'_online');
        }

        return $this->subscriptionInvoiceStatus($request, (int) $invoice->id);
    }

    public function subscriptionInvoiceStatus(Request $request, int $invoiceId)
    {
        [, $tenant] = $this->schoolAdmin($request);
        $invoice = $this->tenantPlatformInvoice($tenant->id, $invoiceId);
        $tenant->refresh();

        return response()->json([
            'invoice' => $this->platformInvoicePayload($invoice),
            'subscription' => [
                'status' => $tenant->status,
                'subscription_expires_at' => $tenant->subscription_expires_at?->toDateString(),
                'days_remaining' => $tenant->subscription_expires_at
                    ? max(0, (int) now()->startOfDay()->diffInDays($tenant->subscription_expires_at->copy()->startOfDay(), false))
                    : null,
                'students_capacity' => (int) ($tenant->students_capacity ?? PricingService::FREE_THRESHOLD),
            ],
            'payments' => $this->subscriptionPaymentCollection($tenant->id),
        ]);
    }

    public function parentFees(Request $request)
    {
        [$guardian, $students, $student] = $this->parentContext($request);
        $gateway = PaymentGatewayConfig::where('tenant_id', $request->user()->tenant_id)
            ->where('is_active', true)->first();

        return response()->json([
            'contract_version' => 2,
            'guardian' => ['id' => $guardian->id, 'name' => $guardian->full_name],
            'children' => $students->map(fn ($child) => [
                'id' => $child->id,
                'name' => $child->full_name,
                'admission_number' => $child->admission_number,
            ])->values(),
            'selected_child_id' => $student?->id,
            'gateway' => $gateway ? ['name' => $gateway->gateway, 'available' => true] : null,
            'invoices' => $student ? $this->childInvoices($student->id) : [],
            'payments' => $this->parentPaymentCollection($request, $students),
        ]);
    }

    public function parentFeeChild(Request $request, int $studentId)
    {
        [, $students] = $this->parentContext($request);
        abort_unless($students->contains('id', $studentId), 403, 'This child is not linked to your parent account.');

        return response()->json([
            'child_id' => $studentId,
            'invoices' => $this->childInvoices($studentId),
            'payments' => $this->parentPaymentCollection($request, $students, $studentId),
        ]);
    }

    public function parentFeeInvoice(Request $request, int $invoiceId)
    {
        [, $students] = $this->parentContext($request);
        $invoice = $this->parentInvoice($request, $students, $invoiceId);

        return response()->json([
            'invoice' => $this->schoolInvoicePayload($invoice),
            'payments' => PaymentTransaction::where('tenant_id', $request->user()->tenant_id)
                ->where('invoice_id', $invoice->id)->latest('paid_at')->get()
                ->map(fn (PaymentTransaction $payment) => $this->schoolPaymentPayload($payment))->values(),
        ]);
    }

    public function parentFeeCheckout(Request $request, int $invoiceId)
    {
        [$guardian, $students] = $this->parentContext($request);
        $invoice = $this->parentInvoice($request, $students, $invoiceId);
        $balance = max(0, (float) $invoice->total_amount - (float) $invoice->amount_paid);
        abort_if($balance <= 0 || $invoice->status === 'paid', 422, 'This invoice is already fully paid.');

        $config = PaymentGatewayConfig::where('tenant_id', $invoice->tenant_id)->where('is_active', true)->first();
        abort_unless($config, 422, 'Online fee payment has not been configured by this school.');
        abort_unless(in_array($config->gateway, ['paystack', 'flutterwave', 'monnify'], true), 422,
            'This school payment gateway is not supported by the mobile app.');

        $reference = 'SMS-'.strtoupper(Str::random(14));
        $log = OnlinePaymentLog::create([
            'tenant_id' => $invoice->tenant_id,
            'invoice_id' => $invoice->id,
            'student_id' => $invoice->student_id,
            'gateway' => $config->gateway,
            'reference' => $reference,
            'amount' => $balance,
            'status' => 'pending',
        ]);

        $email = $request->user()->email ?: 'parent@educoreng.online';
        $checkoutUrl = $this->startTenantGateway($config, $log, $guardian->full_name ?: $invoice->student?->full_name, $email);
        if (! $checkoutUrl) {
            $log->update(['status' => 'failed']);
            abort(503, 'The school payment gateway could not start checkout.');
        }

        return response()->json([
            'provider' => $config->gateway,
            'reference' => $reference,
            'amount' => $balance,
            'currency' => 'NGN',
            'checkout_url' => $checkoutUrl,
        ]);
    }

    public function verifyParentFee(Request $request, SchoolFeePaymentService $settlement)
    {
        [, $students] = $this->parentContext($request);
        $data = $request->validate(['reference' => ['required', 'string', 'max:120']]);
        $log = OnlinePaymentLog::where('tenant_id', $request->user()->tenant_id)
            ->where('reference', $data['reference'])->first();
        abort_unless($log && $students->contains('id', $log->student_id), 403,
            'This payment does not belong to your parent account.');

        if ($log->status !== 'success' || ! $log->verified_at) {
            $config = PaymentGatewayConfig::where('tenant_id', $log->tenant_id)
                ->where('gateway', $log->gateway)->where('is_active', true)->first();
            abort_unless($config && $this->verifyTenantPayment($config, $log), 422,
                'Payment could not be verified yet. If payment completed, retry shortly.');
            $log->update(['status' => 'success', 'verified_at' => now()]);
            $log->refresh();
        }

        $settlement->settle($log);

        return $this->parentFeeStatus($request, (int) $log->invoice_id);
    }

    public function parentFeeStatus(Request $request, int $invoiceId)
    {
        [, $students] = $this->parentContext($request);
        $invoice = $this->parentInvoice($request, $students, $invoiceId);
        $latest = PaymentTransaction::where('tenant_id', $request->user()->tenant_id)
            ->where('invoice_id', $invoice->id)->latest('paid_at')->first();

        return response()->json([
            'invoice' => $this->schoolInvoicePayload($invoice->fresh()),
            'latest_payment' => $latest ? $this->schoolPaymentPayload($latest) : null,
        ]);
    }

    public function parentPayments(Request $request)
    {
        [, $students] = $this->parentContext($request);

        return response()->json([
            'payments' => $this->parentPaymentCollection($request, $students),
        ]);
    }

    private function schoolAdmin(Request $request): array
    {
        $user = $request->user();
        abort_unless($user && $user->tenant_id && $user->hasRole('admin'), 403,
            'Only the school administrator can manage subscription payments.');
        $tenant = Tenant::find($user->tenant_id);
        abort_unless($tenant, 404, 'School account not found.');
        return [$user, $tenant];
    }

    private function parentContext(Request $request): array
    {
        $user = $request->user();
        abort_unless($user?->isParent(), 403, 'Parent portal access only.');
        $guardian = Guardian::where('user_id', $user->id)->first();
        abort_unless($guardian && (int) $guardian->tenant_id === (int) $user->tenant_id, 403,
            'No guardian profile is linked to this account.');
        $students = $guardian->students()->where('students.tenant_id', $user->tenant_id)->get();
        $student = $students->first();
        if ($request->filled('child_id')) {
            $student = $students->firstWhere('id', (int) $request->integer('child_id'));
            abort_unless($student, 403, 'This child is not linked to your parent account.');
        }
        return [$guardian, $students, $student];
    }

    private function tenantPlatformInvoice(int $tenantId, int $invoiceId): object
    {
        $invoice = DB::table('platform_invoices')->where('id', $invoiceId)->where('tenant_id', $tenantId)->first();
        abort_unless($invoice, 404, 'Subscription invoice not found.');
        return $invoice;
    }

    private function parentInvoice(Request $request, $students, int $invoiceId): Invoice
    {
        $invoice = Invoice::with(['student', 'term.session'])
            ->where('id', $invoiceId)->where('tenant_id', $request->user()->tenant_id)->first();
        abort_unless($invoice && $students->contains('id', $invoice->student_id), 403,
            'This invoice does not belong to a child linked to your account.');
        return $invoice;
    }

    private function childInvoices(int $studentId)
    {
        return Invoice::with(['student', 'term.session'])->where('student_id', $studentId)
            ->latest()->limit(50)->get()->map(fn (Invoice $invoice) => $this->schoolInvoicePayload($invoice))->values();
    }

    private function outstandingSubscriptionInvoice(int $tenantId): ?array
    {
        $invoice = DB::table('platform_invoices')->where('tenant_id', $tenantId)
            ->where('status', '!=', 'paid')->latest('id')->first();
        return $invoice ? $this->platformInvoicePayload($invoice) : null;
    }

    private function subscriptionInvoiceCollection(int $tenantId)
    {
        return DB::table('platform_invoices')->where('tenant_id', $tenantId)->latest('id')->limit(50)->get()
            ->map(fn ($invoice) => $this->platformInvoicePayload($invoice))->values();
    }

    private function subscriptionPaymentCollection(int $tenantId)
    {
        return DB::table('platform_payments')->where('tenant_id', $tenantId)->latest('id')->limit(50)->get()
            ->map(fn ($payment) => [
                'id' => (int) $payment->id,
                'reference' => $payment->reference,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency ?? 'NGN',
                'status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'paid_at' => $payment->paid_at,
            ])->values();
    }

    private function parentPaymentCollection(Request $request, $students, ?int $studentId = null)
    {
        $studentIds = $students->pluck('id');
        $query = PaymentTransaction::where('tenant_id', $request->user()->tenant_id)
            ->whereIn('student_id', $studentIds)->latest('paid_at')->limit(100);
        if ($studentId) $query->where('student_id', $studentId);
        return $query->get()->map(fn (PaymentTransaction $payment) => $this->schoolPaymentPayload($payment))->values();
    }

    private function platformGateways(): array
    {
        $settings = PlatformSetting::valuesFor([
            'paystack_secret_key', 'monnify_api_key', 'monnify_secret_key', 'monnify_contract_code',
            'bank_transfer_bank_name', 'bank_transfer_account_name', 'bank_transfer_account_number',
        ]);
        $result = [];
        if (filled($settings['paystack_secret_key'] ?? null)) $result[] = ['name' => 'paystack', 'available' => true];
        if (filled($settings['monnify_api_key'] ?? null) && filled($settings['monnify_secret_key'] ?? null)
            && filled($settings['monnify_contract_code'] ?? null)) $result[] = ['name' => 'monnify', 'available' => true];
        if (filled($settings['bank_transfer_bank_name'] ?? null) && filled($settings['bank_transfer_account_name'] ?? null)
            && filled($settings['bank_transfer_account_number'] ?? null)) {
            $result[] = [
                'name' => 'bank_transfer', 'available' => true,
                'bank_name' => $settings['bank_transfer_bank_name'],
                'account_name' => $settings['bank_transfer_account_name'],
                'account_number' => $settings['bank_transfer_account_number'],
            ];
        }
        return $result;
    }

    private function startPlatformPaystack(object $invoice, ?string $email, string $reference): ?string
    {
        $secret = PlatformSetting::valueFor('paystack_secret_key');
        if (! filled($secret)) return null;
        $response = Http::withToken($secret)->post('https://api.paystack.co/transaction/initialize', [
            'email' => $email ?: 'admin@educoreng.online',
            'amount' => (int) round(((float) $invoice->amount) * 100),
            'currency' => 'NGN',
            'reference' => $reference,
            'callback_url' => config('app.url').'/billing/subscription',
        ]);
        return $response->successful() && $response->json('status') ? $response->json('data.authorization_url') : null;
    }

    private function startPlatformMonnify(object $invoice, Tenant $tenant, ?string $email, string $reference): ?string
    {
        $cfg = $this->platformMonnifyConfig();
        if (! $cfg || ! ($token = $this->monnifyToken($cfg))) return null;
        $response = Http::withToken($token)->post($cfg['base'].'/api/v1/merchant/transactions/init-transaction', [
            'amount' => (float) $invoice->amount,
            'customerName' => $tenant->name,
            'customerEmail' => $email ?: 'admin@educoreng.online',
            'paymentReference' => $reference,
            'paymentDescription' => 'EduCore subscription — '.$tenant->name,
            'currencyCode' => 'NGN',
            'contractCode' => $cfg['contract'],
            'redirectUrl' => config('app.url').'/billing/subscription',
            'paymentMethods' => ['CARD', 'ACCOUNT_TRANSFER'],
        ]);
        return $response->successful() ? $response->json('responseBody.checkoutUrl') : null;
    }

    private function verifyPlatformPayment(string $gateway, string $reference, float $amount): bool
    {
        if ($gateway === 'paystack') {
            $secret = PlatformSetting::valueFor('paystack_secret_key');
            if (! filled($secret)) return false;
            $response = Http::withToken($secret)->get('https://api.paystack.co/transaction/verify/'.rawurlencode($reference));
            return $response->successful() && $response->json('status') === true
                && $response->json('data.status') === 'success'
                && $response->json('data.reference') === $reference
                && strtoupper((string) $response->json('data.currency')) === 'NGN'
                && (int) $response->json('data.amount') === (int) round($amount * 100);
        }
        if ($gateway === 'monnify') {
            $cfg = $this->platformMonnifyConfig();
            if (! $cfg || ! ($token = $this->monnifyToken($cfg))) return false;
            $response = Http::withToken($token)->get($cfg['base'].'/api/v1/merchant/transactions/query', ['paymentReference' => $reference]);
            $body = $response->json('responseBody') ?: [];
            return $response->successful()
                && in_array($body['paymentStatus'] ?? null, ['PAID', 'OVERPAID'], true)
                && ($body['paymentReference'] ?? null) === $reference
                && strtoupper((string) ($body['currencyCode'] ?? '')) === 'NGN'
                && (float) ($body['amountPaid'] ?? 0) >= $amount;
        }
        return false;
    }

    private function startTenantGateway(PaymentGatewayConfig $config, OnlinePaymentLog $log, ?string $name, string $email): ?string
    {
        $amount = (float) $log->amount;
        if ($config->gateway === 'paystack') {
            $response = Http::withToken($config->secret_key)->post('https://api.paystack.co/transaction/initialize', [
                'email' => $email, 'amount' => (int) round($amount * 100), 'currency' => 'NGN',
                'reference' => $log->reference, 'callback_url' => config('app.url').'/parent/fees',
            ]);
            return $response->successful() && $response->json('status') ? $response->json('data.authorization_url') : null;
        }
        if ($config->gateway === 'flutterwave') {
            $response = Http::withToken($config->secret_key)->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $log->reference, 'amount' => $amount, 'currency' => 'NGN',
                'redirect_url' => config('app.url').'/parent/fees',
                'customer' => ['email' => $email, 'name' => $name ?: 'EduCore Parent'],
                'customizations' => ['title' => 'EduCore School Fees', 'description' => 'School fee payment'],
            ]);
            return $response->successful() && $response->json('status') === 'success' ? $response->json('data.link') : null;
        }
        if ($config->gateway === 'monnify') {
            $base = $config->is_live ? 'https://api.monnify.com' : 'https://sandbox.monnify.com';
            $auth = Http::withBasicAuth($config->public_key, $config->secret_key)->post($base.'/api/v1/auth/login');
            $token = $auth->successful() ? $auth->json('responseBody.accessToken') : null;
            if (! $token || ! filled($config->contract_code)) return null;
            $response = Http::withToken($token)->post($base.'/api/v1/merchant/transactions/init-transaction', [
                'amount' => $amount, 'customerName' => $name ?: 'EduCore Parent', 'customerEmail' => $email,
                'paymentReference' => $log->reference, 'paymentDescription' => 'EduCore School Fees',
                'currencyCode' => 'NGN', 'contractCode' => $config->contract_code,
                'redirectUrl' => config('app.url').'/parent/fees', 'paymentMethods' => ['CARD', 'ACCOUNT_TRANSFER'],
            ]);
            return $response->successful() ? $response->json('responseBody.checkoutUrl') : null;
        }
        return null;
    }

    private function verifyTenantPayment(PaymentGatewayConfig $config, OnlinePaymentLog $log): bool
    {
        $amount = (float) $log->amount;
        $response = null;
        if ($config->gateway === 'paystack') {
            $response = Http::withToken($config->secret_key)->get('https://api.paystack.co/transaction/verify/'.rawurlencode($log->reference));
            $verified = $response->successful() && $response->json('status') === true
                && $response->json('data.status') === 'success'
                && $response->json('data.reference') === $log->reference
                && strtoupper((string) $response->json('data.currency')) === 'NGN'
                && (int) $response->json('data.amount') === (int) round($amount * 100);
        } elseif ($config->gateway === 'flutterwave') {
            $response = Http::withToken($config->secret_key)->get('https://api.flutterwave.com/v3/transactions/verify_by_reference', ['tx_ref' => $log->reference]);
            $data = $response->json('data') ?: [];
            $verified = $response->successful() && $response->json('status') === 'success'
                && ($data['status'] ?? null) === 'successful' && ($data['tx_ref'] ?? null) === $log->reference
                && strtoupper((string) ($data['currency'] ?? '')) === 'NGN'
                && abs((float) ($data['amount'] ?? 0) - $amount) < 0.01;
        } else {
            $base = $config->is_live ? 'https://api.monnify.com' : 'https://sandbox.monnify.com';
            $auth = Http::withBasicAuth($config->public_key, $config->secret_key)->post($base.'/api/v1/auth/login');
            $token = $auth->successful() ? $auth->json('responseBody.accessToken') : null;
            if (! $token) return false;
            $response = Http::withToken($token)->get($base.'/api/v1/merchant/transactions/query', ['paymentReference' => $log->reference]);
            $data = $response->json('responseBody') ?: [];
            $verified = $response->successful() && in_array($data['paymentStatus'] ?? null, ['PAID', 'OVERPAID'], true)
                && ($data['paymentReference'] ?? null) === $log->reference
                && strtoupper((string) ($data['currencyCode'] ?? '')) === 'NGN'
                && (float) ($data['amountPaid'] ?? 0) >= $amount;
        }
        if ($verified && $response) $log->update(['gateway_response' => json_encode($response->json())]);
        return $verified;
    }

    private function platformMonnifyConfig(): ?array
    {
        $settings = PlatformSetting::valuesFor(['monnify_api_key', 'monnify_secret_key', 'monnify_contract_code', 'monnify_is_live']);
        if (blank($settings['monnify_api_key'] ?? null) || blank($settings['monnify_secret_key'] ?? null)
            || blank($settings['monnify_contract_code'] ?? null)) return null;
        return [
            'api_key' => $settings['monnify_api_key'], 'secret' => $settings['monnify_secret_key'],
            'contract' => $settings['monnify_contract_code'],
            'base' => ! empty($settings['monnify_is_live']) ? 'https://api.monnify.com' : 'https://sandbox.monnify.com',
        ];
    }

    private function monnifyToken(array $cfg): ?string
    {
        $response = Http::withBasicAuth($cfg['api_key'], $cfg['secret'])->post($cfg['base'].'/api/v1/auth/login');
        return $response->successful() ? $response->json('responseBody.accessToken') : null;
    }

    private function platformInvoicePayload(object $invoice): array
    {
        return [
            'id' => (int) $invoice->id,
            'number' => $invoice->invoice_number,
            'amount' => (float) $invoice->amount,
            'student_count' => (int) ($invoice->student_count ?? 0),
            'billing_cycle' => $invoice->billing_cycle,
            'status' => $invoice->status,
            'payment_status' => $invoice->status === 'paid' ? 'paid' : (($invoice->payment_method === 'bank_transfer' && filled($invoice->payment_ref)) ? 'awaiting_verification' : 'unpaid'),
            'due_date' => $invoice->due_date ? (string) $invoice->due_date : null,
            'paid_at' => $invoice->paid_at,
            'payment_method' => $invoice->payment_method,
            'payment_reference' => $invoice->payment_ref,
        ];
    }

    private function schoolInvoicePayload(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'student_id' => $invoice->student_id,
            'student_name' => $invoice->student?->full_name,
            'number' => $invoice->invoice_number,
            'term' => $invoice->term?->name,
            'session' => $invoice->session?->name ?? $invoice->term?->session?->name,
            'total_amount' => (float) $invoice->total_amount,
            'amount_paid' => (float) $invoice->amount_paid,
            'balance' => max(0, (float) $invoice->total_amount - (float) $invoice->amount_paid),
            'status' => $invoice->status,
            'can_pay' => $invoice->status !== 'paid' && ((float) $invoice->total_amount - (float) $invoice->amount_paid) > 0,
            'due_date' => optional($invoice->due_date)->toDateString(),
        ];
    }

    private function schoolPaymentPayload(PaymentTransaction $payment): array
    {
        return [
            'id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'student_id' => $payment->student_id,
            'reference' => $payment->gateway_reference,
            'gateway' => $payment->gateway,
            'amount' => (float) $payment->amount_paid,
            'currency' => $payment->currency ?? 'NGN',
            'status' => $payment->status,
            'paid_at' => optional($payment->paid_at)->toIso8601String(),
        ];
    }
}
