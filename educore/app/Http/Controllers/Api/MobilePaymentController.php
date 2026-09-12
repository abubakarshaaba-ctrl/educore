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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MobilePaymentController extends Controller
{
    public function subscription(Request $request, PricingService $pricing)
    {
        [$user, $tenant] = $this->schoolAdmin($request);
        $activeStudents = $pricing->activeStudentCount($tenant->id);
        $invoices = DB::table('platform_invoices')
            ->where('tenant_id', $tenant->id)
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn ($invoice) => $this->platformInvoicePayload($invoice));

        return response()->json([
            'contract_version' => 1,
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'status' => $tenant->status,
                'subscription_expires_at' => optional($tenant->subscription_expires_at)->toDateString(),
                'students_capacity' => (int) ($tenant->students_capacity ?? PricingService::FREE_THRESHOLD),
                'active_students' => $activeStudents,
            ],
            'pricing' => [
                'free_threshold' => PricingService::FREE_THRESHOLD,
                'rate_per_student_per_term' => PricingService::PAID_RATE,
                'termly_amount' => $pricing->termlyAmount(max($activeStudents, (int) ($tenant->students_capacity ?? 0))),
            ],
            'gateways' => $this->platformGateways(),
            'invoices' => $invoices,
        ]);
    }

    public function createSubscriptionInvoice(Request $request, PricingService $pricing)
    {
        [, $tenant] = $this->schoolAdmin($request);
        $data = $request->validate([
            'billing_cycle' => ['required', 'in:termly,annual'],
            'anticipated_enrollment' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        $count = max($pricing->activeStudentCount($tenant->id), (int) $data['anticipated_enrollment']);
        $amount = $data['billing_cycle'] === 'annual'
            ? $pricing->annualAmount($count)
            : $pricing->termlyAmount($count);

        if ($amount <= 0) {
            return response()->json([
                'free' => true,
                'message' => 'This school is currently within EduCore free-tier capacity.',
                'amount' => 0,
            ]);
        }

        $existing = DB::table('platform_invoices')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->where('billing_cycle', $data['billing_cycle'])
            ->where('amount', $amount)
            ->latest('id')
            ->first();

        if (! $existing) {
            $id = DB::table('platform_invoices')->insertGetId([
                'tenant_id' => $tenant->id,
                'plan_id' => null,
                'invoice_number' => 'INV-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)),
                'amount' => $amount,
                'billing_cycle' => $data['billing_cycle'],
                'status' => 'pending',
                'due_date' => now()->addDays(7)->toDateString(),
                'notes' => json_encode(['anticipated_enrollment' => $count]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $existing = DB::table('platform_invoices')->where('id', $id)->first();
        }

        return response()->json(['free' => false, 'invoice' => $this->platformInvoicePayload($existing)]);
    }

    public function subscriptionCheckout(Request $request, int $invoiceId)
    {
        [$user, $tenant] = $this->schoolAdmin($request);
        $data = $request->validate(['gateway' => ['required', 'in:paystack,monnify']]);
        $invoice = DB::table('platform_invoices')
            ->where('id', $invoiceId)
            ->where('tenant_id', $tenant->id)
            ->first();
        abort_unless($invoice, 404, 'Subscription invoice not found.');
        abort_if($invoice->status === 'paid', 422, 'This subscription invoice is already paid.');

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
            'checkout_url' => $checkoutUrl,
        ]);
    }

    public function verifySubscription(Request $request, PricingService $pricing)
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
            abort_unless($this->verifyPlatformPayment($gateway, $data['reference'], (float) $invoice->amount), 422,
                'Payment could not be verified yet. If you completed payment, try Verify again shortly.');
            $this->creditSubscription($invoice, $data['reference'], $gateway.'_online', $pricing);
        }

        $tenant->refresh();
        $invoice = DB::table('platform_invoices')->where('id', $invoice->id)->first();
        return response()->json([
            'message' => 'Subscription payment confirmed.',
            'tenant' => [
                'status' => $tenant->status,
                'subscription_expires_at' => optional($tenant->subscription_expires_at)->toDateString(),
                'students_capacity' => (int) $tenant->students_capacity,
            ],
            'invoice' => $this->platformInvoicePayload($invoice),
        ]);
    }

    public function parentFees(Request $request)
    {
        [$guardian, $students, $student] = $this->parentContext($request);
        $gateway = PaymentGatewayConfig::where('tenant_id', $request->user()->tenant_id)
            ->where('is_active', true)->first();

        $invoices = $student ? Invoice::with(['term.session'])
            ->where('student_id', $student->id)->latest()->limit(50)->get()
            ->map(fn (Invoice $invoice) => $this->schoolInvoicePayload($invoice)) : collect();

        return response()->json([
            'contract_version' => 1,
            'guardian' => ['id' => $guardian->id, 'name' => $guardian->full_name],
            'children' => $students->map(fn ($child) => [
                'id' => $child->id,
                'name' => $child->full_name,
                'admission_number' => $child->admission_number,
            ])->values(),
            'selected_child_id' => $student?->id,
            'gateway' => $gateway ? ['name' => $gateway->gateway, 'available' => true] : null,
            'invoices' => $invoices,
        ]);
    }

    public function parentFeeCheckout(Request $request, int $invoiceId)
    {
        [$guardian, $students] = $this->parentContext($request);
        $invoice = Invoice::where('id', $invoiceId)->where('tenant_id', $request->user()->tenant_id)->first();
        abort_unless($invoice && $students->contains('id', $invoice->student_id), 403,
            'This invoice does not belong to a child linked to your account.');
        $balance = max(0, (float) $invoice->total_amount - (float) $invoice->amount_paid);
        abort_if($balance <= 0 || $invoice->status === 'paid', 422, 'This invoice is already fully paid.');

        $config = PaymentGatewayConfig::where('tenant_id', $invoice->tenant_id)
            ->where('is_active', true)->first();
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
        $student = $invoice->student;
        $email = $request->user()->email ?: 'parent@educoreng.online';
        $checkoutUrl = $this->startTenantGateway($config, $log, $guardian->full_name ?: $student?->full_name, $email);

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

    public function verifyParentFee(Request $request)
    {
        [, $students] = $this->parentContext($request);
        $data = $request->validate(['reference' => ['required', 'string', 'max:120']]);
        $log = OnlinePaymentLog::where('tenant_id', $request->user()->tenant_id)
            ->where('reference', $data['reference'])->first();
        abort_unless($log && $students->contains('id', $log->student_id), 403,
            'This payment does not belong to your parent account.');

        if ($log->status !== 'success') {
            $config = PaymentGatewayConfig::where('tenant_id', $log->tenant_id)
                ->where('gateway', $log->gateway)->first();
            abort_unless($config && $this->verifyTenantPayment($config, $log), 422,
                'Payment could not be verified yet. If you completed payment, try Verify again shortly.');
            $log->update(['status' => 'success', 'verified_at' => now()]);
            $this->applySchoolFeePayment($log->fresh());
        } else {
            $this->applySchoolFeePayment($log);
        }

        $invoice = Invoice::findOrFail($log->invoice_id);
        return response()->json([
            'message' => 'School fee payment confirmed.',
            'invoice' => $this->schoolInvoicePayload($invoice),
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

    private function platformGateways(): array
    {
        $gateways = [];
        if (filled(PlatformSetting::valueFor('paystack_secret_key'))) $gateways[] = 'paystack';
        if (filled(PlatformSetting::valueFor('monnify_api_key')) && filled(PlatformSetting::valueFor('monnify_secret_key'))
            && filled(PlatformSetting::valueFor('monnify_contract_code'))) $gateways[] = 'monnify';
        return $gateways;
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
            $response = Http::withToken($token)->get($cfg['base'].'/api/v1/merchant/transactions/query', [
                'paymentReference' => $reference,
            ]);
            $body = $response->json('responseBody') ?: [];
            return $response->successful()
                && in_array($body['paymentStatus'] ?? null, ['PAID', 'OVERPAID'], true)
                && ($body['paymentReference'] ?? $reference) === $reference
                && strtoupper((string) ($body['currencyCode'] ?? '')) === 'NGN'
                && (float) ($body['amountPaid'] ?? 0) >= $amount;
        }
        return false;
    }

    private function creditSubscription(object $invoice, string $reference, string $method, PricingService $pricing): void
    {
        DB::transaction(function () use ($invoice, $reference, $method, $pricing) {
            $locked = DB::table('platform_invoices')->where('id', $invoice->id)->lockForUpdate()->first();
            if (! $locked || $locked->status === 'paid') return;
            if (DB::table('platform_payments')->where('reference', $reference)->exists()) return;

            $tenant = Tenant::lockForUpdate()->findOrFail($locked->tenant_id);
            DB::table('platform_payments')->insert([
                'tenant_id' => $tenant->id,
                'subscription_id' => null,
                'reference' => $reference,
                'amount' => $locked->amount,
                'currency' => 'NGN',
                'status' => 'confirmed',
                'payment_method' => $method,
                'description' => 'EduCore subscription payment',
                'paid_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('platform_invoices')->where('id', $locked->id)->update([
                'status' => 'paid', 'paid_at' => now(), 'payment_method' => $method,
                'payment_ref' => $reference, 'updated_at' => now(),
            ]);

            $days = $locked->billing_cycle === 'annual' ? 365 : ($locked->billing_cycle === 'termly' ? 112 : 30);
            $base = $tenant->subscription_expires_at && $tenant->subscription_expires_at->isFuture()
                ? $tenant->subscription_expires_at : now();
            $capacity = $pricing->capacityFor($tenant);
            $notes = json_decode((string) $locked->notes, true) ?: [];
            $capacity = max($capacity, (int) ($notes['anticipated_enrollment'] ?? 0));
            $tenant->forceFill([
                'status' => Tenant::STATUS_ACTIVE,
                'subscription_expires_at' => $base->copy()->addDays($days),
                'students_capacity' => max(PricingService::FREE_THRESHOLD, $capacity),
            ])->save();
        });
    }

    private function startTenantGateway(PaymentGatewayConfig $config, OnlinePaymentLog $log, ?string $name, string $email): ?string
    {
        $amount = (float) $log->amount;
        if ($config->gateway === 'paystack') {
            $response = Http::withToken($config->secret_key)->post('https://api.paystack.co/transaction/initialize', [
                'email' => $email,
                'amount' => (int) round($amount * 100),
                'currency' => 'NGN', 'reference' => $log->reference,
                'callback_url' => config('app.url').'/parent/fees',
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
        if ($config->gateway === 'paystack') {
            $response = Http::withToken($config->secret_key)->get('https://api.paystack.co/transaction/verify/'.rawurlencode($log->reference));
            $verified = $response->successful() && $response->json('status') === true
                && $response->json('data.status') === 'success'
                && $response->json('data.reference') === $log->reference
                && strtoupper((string) $response->json('data.currency')) === 'NGN'
                && (int) $response->json('data.amount') === (int) round($amount * 100);
        } elseif ($config->gateway === 'flutterwave') {
            $response = Http::withToken($config->secret_key)->get('https://api.flutterwave.com/v3/transactions/verify_by_reference', [
                'tx_ref' => $log->reference,
            ]);
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
                && ($data['paymentReference'] ?? $log->reference) === $log->reference
                && strtoupper((string) ($data['currencyCode'] ?? '')) === 'NGN'
                && (float) ($data['amountPaid'] ?? 0) >= $amount;
        }
        if ($verified) $log->update(['gateway_response' => json_encode($response->json())]);
        return $verified;
    }

    private function applySchoolFeePayment(OnlinePaymentLog $log): void
    {
        $notify = null;
        DB::transaction(function () use ($log, &$notify) {
            if (PaymentTransaction::where('gateway_reference', $log->reference)->exists()) return;
            $invoice = Invoice::lockForUpdate()->find($log->invoice_id);
            if (! $invoice) return;
            $remaining = max(0, (float) $invoice->total_amount - (float) $invoice->amount_paid);
            $amount = min((float) $log->amount, $remaining);
            if ($amount <= 0) return;
            $invoice->amount_paid = (float) $invoice->amount_paid + $amount;
            $invoice->status = $invoice->amount_paid >= $invoice->total_amount ? 'paid' : 'partially_paid';
            $invoice->save();
            $student = $invoice->student;
            $guardian = $student?->primaryGuardian() ?? $student?->guardians()->first();
            PaymentTransaction::create([
                'tenant_id' => $invoice->tenant_id, 'invoice_id' => $invoice->id, 'student_id' => $invoice->student_id,
                'gateway_reference' => $log->reference, 'gateway' => $log->gateway, 'amount_paid' => $amount,
                'currency' => 'NGN', 'status' => 'success', 'gateway_response' => $log->gateway_response,
                'paid_by_name' => $guardian?->name ?? $student?->full_name, 'paid_by_phone' => $guardian?->phone,
                'paid_at' => $log->verified_at ?? now(),
            ]);
            $notify = [$invoice, $student, $guardian, $amount];
        });
        if (! $notify) return;
        [$invoice, $student, $guardian, $amount] = $notify;
        try {
            app(\App\Services\GuardianNotifier::class)->send(
                $guardian,
                'Payment received'.($student ? ' — '.$student->full_name : ''),
                ['We have received a payment of ₦'.number_format($amount, 2).'.', 'Invoice status: '.ucfirst(str_replace('_', ' ', $invoice->status))],
                smsBody: ($invoice->tenant?->name ?? 'EduCore').': Payment of ₦'.number_format($amount, 2).' received. Thank you.',
                schoolName: $invoice->tenant?->name,
            );
        } catch (\Throwable $e) {
            Log::warning('Mobile parent payment notification failed', ['error' => $e->getMessage()]);
        }
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
            'id' => (int) $invoice->id, 'number' => $invoice->invoice_number, 'amount' => (float) $invoice->amount,
            'billing_cycle' => $invoice->billing_cycle, 'status' => $invoice->status, 'due_date' => (string) $invoice->due_date,
            'paid_at' => $invoice->paid_at, 'payment_method' => $invoice->payment_method,
            'payment_reference' => $invoice->payment_ref,
        ];
    }

    private function schoolInvoicePayload(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id, 'number' => $invoice->invoice_number, 'term' => $invoice->term?->name,
            'session' => $invoice->session?->name, 'total_amount' => (float) $invoice->total_amount,
            'amount_paid' => (float) $invoice->amount_paid,
            'balance' => max(0, (float) $invoice->total_amount - (float) $invoice->amount_paid),
            'status' => $invoice->status, 'due_date' => optional($invoice->due_date)->toDateString(),
        ];
    }
}
