<?php
namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\OnlinePaymentLog;
use App\Models\PaymentGatewayConfig;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentGatewayController extends Controller
{
    // ── Gateway Settings (admin only) ─────────────────────────────
    public function settings()
    {
        $config = PaymentGatewayConfig::firstOrNew([
            'tenant_id' => auth()->user()->tenant_id,
        ]);
        return view('fees.gateway-settings', compact('config'));
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'gateway'       => ['required', 'in:paystack,flutterwave,monnify'],
            'public_key'    => ['required_unless:gateway,monnify', 'nullable', 'string'],
            'secret_key'    => ['required', 'string'],
            'contract_code' => ['required_if:gateway,monnify', 'nullable', 'string'],
            'is_live'       => ['boolean'],
        ]);
        $data['is_live']   = $request->boolean('is_live');
        $data['is_active'] = true;

        PaymentGatewayConfig::updateOrCreate(
            ['tenant_id' => auth()->user()->tenant_id],
            $data
        );
        return back()->with('success', 'Payment gateway configured.');
    }

    // ── Initiate from Parent Portal ───────────────────────────────────
    public function initiateFromPortal(\App\Models\Invoice $invoice)
    {
        $user   = auth()->user();
        $tenant = $user->tenant;
        if ($invoice->tenant_id !== $tenant->id) abort(403);

        $config = \App\Models\PaymentGatewayConfig::where('tenant_id', $tenant->id)->where('is_active', true)->first();
        if (!$config) return back()->withErrors(['error' => 'Online payment is not yet configured for this school.']);

        $balance   = $invoice->total_amount - $invoice->amount_paid;
        if ($balance <= 0) return back()->withErrors(['error' => 'This invoice is already fully paid.']);
        $reference = 'SMS-'.strtoupper(\Illuminate\Support\Str::random(12));
        $email     = $invoice->student?->guardians?->first()?->email ?? $user->email ?? 'parent@school.ng';

        \App\Models\OnlinePaymentLog::create(['invoice_id'=>$invoice->id,'student_id'=>$invoice->student_id,'gateway'=>$config->gateway,'reference'=>$reference,'amount'=>$balance,'status'=>'pending']);

        if ($config->gateway === 'monnify') return $this->monnifyRedirect($config, $invoice, $reference, (float)$balance, $email);
        if ($config->gateway === 'paystack') return view('fees.pay-paystack', compact('invoice','config','reference','balance','email'));
        return view('fees.pay-flutterwave', compact('invoice','config','reference','balance','email'));
    }

    // ── Initiate Payment (student/parent pays invoice) ─────────────
    public function initiate(Request $request, Invoice $invoice)
    {
        $tenant  = auth()->user()->tenant;
        $config  = PaymentGatewayConfig::where('tenant_id', $tenant->id)->where('is_active', true)->first();
        if (!$config) return back()->withErrors(['error' => 'Payment gateway not configured. Contact school admin.']);

        $balance   = $invoice->total_amount - $invoice->amount_paid;
        $reference = 'SMS-'.strtoupper(Str::random(12));
        $email     = $invoice->student?->guardians?->first()?->email ?? auth()->user()->email ?? 'parent@school.ng';

        OnlinePaymentLog::create(['invoice_id'=>$invoice->id,'student_id'=>$invoice->student_id,'gateway'=>$config->gateway,'reference'=>$reference,'amount'=>$balance,'status'=>'pending']);

        if ($config->gateway === 'monnify') return $this->monnifyRedirect($config, $invoice, $reference, (float)$balance, $email);
        if ($config->gateway === 'paystack') return view('fees.pay-paystack', compact('invoice','config','reference','balance','email'));
        return view('fees.pay-flutterwave', compact('invoice','config','reference','balance','email'));
    }

    // ── Monnify helpers ───────────────────────────────────────────────
    private function monnifyToken(PaymentGatewayConfig $config): ?string
    {
        $base = $config->is_live ? 'https://api.monnify.com' : 'https://sandbox.monnify.com';
        $resp = Http::withBasicAuth($config->public_key, $config->secret_key)->post("{$base}/api/v1/auth/login");
        return $resp->successful() ? $resp->json('responseBody.accessToken') : null;
    }

    private function monnifyRedirect(PaymentGatewayConfig $config, Invoice $invoice, string $reference, float $balance, string $email)
    {
        $base  = $config->is_live ? 'https://api.monnify.com' : 'https://sandbox.monnify.com';
        $token = $this->monnifyToken($config);
        if (!$token) return back()->withErrors(['error' => 'Could not authenticate with Monnify. Please check API keys.']);

        $init = Http::withToken($token)->post("{$base}/api/v1/merchant/transactions/init-transaction", [
            'amount'            => $balance,
            'customerName'      => $invoice->student?->full_name ?? 'Parent',
            'customerEmail'     => $email,
            'paymentReference'  => $reference,
            'paymentDescription'=> 'School Fees — '.($invoice->student?->full_name ?? ''),
            'currencyCode'      => 'NGN',
            'contractCode'      => $config->contract_code,
            'redirectUrl'       => route('fees.gateway.monnify.callback'),
            'paymentMethods'    => ['CARD', 'ACCOUNT_TRANSFER'],
        ]);
        $checkoutUrl = $init->successful() ? $init->json('responseBody.checkoutUrl') : null;
        if (!$checkoutUrl) return back()->withErrors(['error' => 'Could not start Monnify checkout. Please try again.']);
        return redirect()->away($checkoutUrl);
    }

    // ── Monnify callback ──────────────────────────────────────────────
    public function monnifyCallback(Request $request)
    {
        $reference = $request->get('paymentReference') ?: $request->get('reference');
        $log       = OnlinePaymentLog::where('reference', $reference)->first();
        if (!$log) return redirect()->route('fees.invoices')->withErrors(['error' => 'Payment reference not found.']);
        if ($log->status === 'success') return redirect()->route('fees.invoices.show', $log->invoice_id)->with('success', 'Payment already confirmed.');

        $config   = PaymentGatewayConfig::where('tenant_id', $log->tenant_id)->first();
        $verified = false;
        if ($config && ($token = $this->monnifyToken($config))) {
            $base    = $config->is_live ? 'https://api.monnify.com' : 'https://sandbox.monnify.com';
            $query   = Http::withToken($token)->get("{$base}/api/v1/merchant/transactions/query", ['paymentReference' => $reference]);
            $verified = $query->successful() && $query->json('responseBody.paymentStatus') === 'PAID';
        }

        $log->update(['gateway_response' => $request->all(), 'status' => $verified ? 'success' : 'failed', 'verified_at' => now()]);
        if ($verified) {
            $this->applyPayment($log);
            return redirect()->route('fees.invoices.show', $log->invoice_id)->with('success', 'Payment of ₦'.number_format($log->amount).' confirmed!');
        }
        return redirect()->route('fees.invoices.show', $log->invoice_id)->withErrors(['error' => 'Payment not yet confirmed. If you completed payment, please wait a moment and retry.']);
    }

    // ── Callback: Paystack ─────────────────────────────────────────
    public function paystackCallback(Request $request)
    {
        $reference = $request->get('reference');
        $log = OnlinePaymentLog::where('reference', $reference)->first();

        if (!$log) {
            return redirect()->route('fees.invoices')->withErrors(['error' => 'Payment reference not found.']);
        }

        // Verify with Paystack API
        $config = PaymentGatewayConfig::where('tenant_id', $log->tenant_id)->first();
        $mode   = $config?->is_live ? '' : 'test.';
        $resp   = Http::withHeaders(['Authorization' => 'Bearer '.($config?->secret_key ?? '')])
                    ->get("https://api.{$mode}paystack.co/transaction/verify/{$reference}");

        $body = $resp->json();
        $log->update([
            'gateway_response' => $body,
            'status'           => ($body['data']['status'] ?? '') === 'success' ? 'success' : 'failed',
            'verified_at'      => now(),
        ]);

        if ($log->status === 'success') {
            $this->applyPayment($log);
            return redirect()->route('fees.invoices.show', $log->invoice_id)
                ->with('success', 'Payment of ₦'.number_format($log->amount).' confirmed!');
        }

        return redirect()->route('fees.invoices.show', $log->invoice_id)
            ->withErrors(['error' => 'Payment verification failed. Contact school.']);
    }

    // ── Callback: Flutterwave ──────────────────────────────────────
    public function flutterwaveCallback(Request $request)
    {
        $txId      = $request->get('transaction_id');
        $reference = $request->get('tx_ref');
        $log       = OnlinePaymentLog::where('reference', $reference)->first();

        if (!$log) {
            return redirect()->route('fees.invoices')->withErrors(['error' => 'Transaction not found.']);
        }

        $config = PaymentGatewayConfig::where('tenant_id', $log->tenant_id)->first();
        $resp   = Http::withHeaders(['Authorization' => 'Bearer '.($config?->secret_key ?? '')])
                    ->get("https://api.flutterwave.com/v3/transactions/{$txId}/verify");

        $body   = $resp->json();
        $status = ($body['data']['status'] ?? '') === 'successful' ? 'success' : 'failed';

        $log->update([
            'gateway_response' => $body,
            'status'           => $status,
            'verified_at'      => now(),
        ]);

        if ($status === 'success') {
            $this->applyPayment($log);
            return redirect()->route('fees.invoices.show', $log->invoice_id)
                ->with('success', 'Payment of ₦'.number_format($log->amount).' confirmed!');
        }

        return redirect()->route('fees.invoices.show', $log->invoice_id)
            ->withErrors(['error' => 'Payment failed. Please retry.']);
    }

    // ── Webhook: Paystack ──────────────────────────────────────────
    public function paystackWebhook(Request $request)
    {
        // Step 1: reject immediately if the signature header is absent
        $signature = $request->header('x-paystack-signature');
        if (!$signature) {
            return response('Unauthorized', 401);
        }

        $reference = $request->input('data.reference');
        $log = OnlinePaymentLog::where('reference', $reference)->first();

        // Resolve the tenant's secret key so we can verify the signature.
        // If we can't find it, we still return 200 to avoid Paystack retries
        // leaking whether a reference exists.
        $config = $log ? PaymentGatewayConfig::where('tenant_id', $log->tenant_id)
                                              ->where('gateway', 'paystack')
                                              ->first()
                       : null;

        if ($config) {
            $expected = hash_hmac('sha512', $request->getContent(), $config->secret_key);
            if (!hash_equals($expected, $signature)) {
                \Illuminate\Support\Facades\Log::warning('Paystack webhook: invalid signature', [
                    'reference' => $reference,
                    'ip'        => $request->ip(),
                ]);
                return response('Unauthorized', 401);
            }
        }

        $event = $request->input('event');
        if ($event !== 'charge.success') return response('OK', 200);

        if ($log && $log->status !== 'success') {
            $log->update(['status' => 'success', 'verified_at' => now()]);
            $this->applyPayment($log);
        }
        return response('OK', 200);
    }

    private function applyPayment(OnlinePaymentLog $log): void
    {
        DB::transaction(function () use ($log) {
            if (PaymentTransaction::where('gateway_reference', $log->reference)->exists()) {
                return;
            }

            $invoice = Invoice::lockForUpdate()->find($log->invoice_id);
            if (!$invoice) return;

            $invoice->amount_paid += $log->amount;
            if ($invoice->amount_paid >= $invoice->total_amount) {
                $invoice->status = 'paid';
            } elseif ($invoice->amount_paid > 0) {
                $invoice->status = 'partially_paid';
            }
            $invoice->save();

            $student = $invoice->student;
            $guardian = $student?->primaryGuardian() ?? $student?->guardians()->first();

            PaymentTransaction::create([
                'tenant_id'         => $invoice->tenant_id,
                'invoice_id'        => $invoice->id,
                'student_id'        => $invoice->student_id,
                'gateway_reference' => $log->reference,
                'gateway'           => $log->gateway,
                'amount_paid'       => $log->amount,
                'currency'          => 'NGN',
                'status'            => 'success',
                'gateway_response'  => $log->gateway_response,
                'paid_by_name'      => $guardian?->name ?? $student?->full_name,
                'paid_by_phone'     => $guardian?->phone,
                'paid_at'           => $log->verified_at ?? now(),
            ]);
        });
    }
}
