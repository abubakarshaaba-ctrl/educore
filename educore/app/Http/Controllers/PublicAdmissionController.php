<?php
namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\AdmissionPortalSetting;
use App\Models\AdmissionDocument;
use App\Models\ClassLevel;
use App\Models\Tenant;
use App\Models\NotificationQueue;
use App\Services\Payments\GatewayPaymentVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

/**
 * PUBLIC Admissions Portal — NO AUTH REQUIRED
 * Access via: /apply/{school_slug}
 * Check status: /apply/{school_slug}/status/{application_number}
 */
class PublicAdmissionController extends Controller
{
    // ── Find tenant from slug ────────────────────────────────────────
    private function getTenant(string $slug): Tenant
    {
        return Tenant::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();
    }

    // ── Portal Landing Page ──────────────────────────────────────────
    public function landing(string $slug)
    {
        $tenant   = $this->getTenant($slug);
        $settings = AdmissionPortalSetting::where('tenant_id', $tenant->id)->first()
                    ?? new AdmissionPortalSetting(['is_open' => true]);

        $classLevels = ClassLevel::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')->get();

        $stats = [
            'total_applied' => Admission::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->whereYear('created_at', date('Y'))->count(),
            'admitted'      => Admission::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->where('status', 'admitted')->count(),
        ];

        return view('portal.admissions.landing',
            compact('tenant', 'settings', 'classLevels', 'stats'));
    }

    // ── Application Form ─────────────────────────────────────────────
    public function form(string $slug)
    {
        $tenant   = $this->getTenant($slug);
        $settings = AdmissionPortalSetting::where('tenant_id', $tenant->id)->first()
                    ?? new AdmissionPortalSetting(['is_open' => true]);

        if (!$settings->isCurrentlyOpen()) {
            return redirect()->route('portal.landing', $slug)
                ->with('error', 'The admissions portal is currently closed.');
        }

        $classLevels = ClassLevel::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')->get();

        $nigerianStates = $this->nigerianStates();

        return view('portal.admissions.apply',
            compact('tenant', 'settings', 'classLevels', 'nigerianStates'));
    }

    // ── Submit Application ───────────────────────────────────────────
    public function submit(Request $request, string $slug)
    {
        $tenant   = $this->getTenant($slug);
        $settings = AdmissionPortalSetting::where('tenant_id', $tenant->id)->first()
                    ?? new AdmissionPortalSetting(['is_open' => true]);

        if (!$settings->isCurrentlyOpen()) {
            return back()->withErrors(['error' => 'Admissions are currently closed.']);
        }

        $data = $request->validate([
            'first_name'                  => ['required','string','max:80'],
            'last_name'                   => ['required','string','max:80'],
            'other_names'                 => ['nullable','string','max:80'],
            'date_of_birth'               => ['required','date','before:today'],
            'gender'                      => ['required','in:male,female'],
            'religion'                    => ['nullable','string','max:50'],
            'state_of_origin'             => ['nullable','string','max:50'],
            'nationality'                 => ['nullable','string','max:50'],
            'address'                     => ['required','string','max:300'],
            'applying_for_class_level_id' => ['required','integer'],
            'previous_school'             => ['nullable','string','max:200'],
            'previous_class'              => ['nullable','string','max:50'],
            'guardian_name'               => ['required','string','max:120'],
            'guardian_phone'              => ['required','string','max:20'],
            'guardian_email'              => ['nullable','email','max:120'],
            'guardian_relationship'       => ['required','string'],
            'guardian_occupation'         => ['nullable','string','max:100'],
            'guardian_address'            => ['nullable','string','max:300'],
            'academic_year'               => ['nullable','string','max:20'],
            // Documents
            'passport_photo'              => ['nullable','image','max:2048'],
            'birth_certificate'           => ['nullable','file','mimes:pdf,jpg,jpeg,png','max:4096'],
            'last_report_card'            => ['nullable','file','mimes:pdf,jpg,jpeg,png','max:4096'],
        ]);

        // Generate unique application number
        $appNumber = 'APP-' . strtoupper($slug) . '-' . date('Y') . '-' . strtoupper(Str::random(6));
        $token     = Str::random(32);

        $admission = Admission::withoutTenantScope()->create([
            ...$data,
            'tenant_id'          => $tenant->id,
            'application_number' => $appNumber,
            'portal_token'       => $token,
            'application_date'   => today()->toDateString(),
            'status'             => 'pending',
            'source'             => 'portal',
            'academic_year'      => $data['academic_year'] ?? $settings->academic_year,
            'nationality'        => $data['nationality'] ?? 'Nigerian',
        ]);

        // Store uploaded documents
        $docTypes = ['passport_photo', 'birth_certificate', 'last_report_card'];
        foreach ($docTypes as $docType) {
            if ($request->hasFile($docType)) {
                $file = $request->file($docType);
                $path = $file->store("admissions/{$tenant->id}/{$admission->id}", 'public');
                AdmissionDocument::create([
                    'admission_id'  => $admission->id,
                    'tenant_id'     => $tenant->id,
                    'document_type' => $docType,
                    'file_path'     => $path,
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }
        }

        // Queue SMS to guardian
        if ($settings->notify_guardian_sms && !empty($data['guardian_phone'])) {
            NotificationQueue::create([
                'tenant_id' => $tenant->id,
                'channel'   => 'sms',
                'recipient' => $data['guardian_phone'],
                'body'      => "Dear {$data['guardian_name']}, your application for {$data['first_name']} {$data['last_name']} to {$tenant->name} has been received. Application No: {$appNumber}. Track status at: " . route('portal.status.form', $slug),
                'gateway'   => 'termii',
                'status'    => 'pending',
            ]);
        }

        // Queue email if provided
        if ($settings->notify_guardian_email && !empty($data['guardian_email'])) {
            NotificationQueue::create([
                'tenant_id' => $tenant->id,
                'channel'   => 'email',
                'recipient' => $data['guardian_email'],
                'subject'   => "Application Received — {$tenant->name}",
                'body'      => "Dear {$data['guardian_name']},\n\nYour application for {$data['first_name']} {$data['last_name']} has been received.\n\nApplication Number: {$appNumber}\n\nTrack your application status at any time using your application number.\n\nThank you.\n{$tenant->name}",
                'gateway'   => 'smtp',
                'status'    => 'pending',
            ]);
        }

        // Auto-shortlist if configured
        if ($settings->auto_shortlist) {
            $admission->update(['status' => 'shortlisted']);
        }

        // If application fee is required, redirect to payment
        if ($settings->application_fee > 0) {
            $config = \App\Models\PaymentGatewayConfig::where('tenant_id', $tenant->id)
                ->where('is_active', true)->first();

            if ($config) {
                $reference = 'APPFEE-' . strtoupper(Str::random(10));
                // Store fee payment reference on the admission
                $admission->update(['payment_reference' => $reference, 'payment_status' => 'pending']);

                $email = $data['guardian_email'] ?? 'applicant@school.ng';
                $amount = $settings->application_fee;

                // Pass to payment view
                if ($config->gateway === 'paystack') {
                    return view('portal.admissions.pay-paystack', compact(
                        'admission', 'config', 'reference', 'amount', 'email', 'slug'
                    ));
                }
                return view('portal.admissions.pay-flutterwave', compact(
                    'admission', 'config', 'reference', 'amount', 'email', 'slug'
                ));
            }
        }

        return redirect()->route('portal.success', [$slug, $appNumber])
            ->with('success', 'Application submitted successfully!');
    }

    // ── Application Fee Payment Callback ────────────────────────────
    public function feeCallback(\Illuminate\Http\Request $request)
    {
        $reference = $request->get('reference');
        $slug      = $request->get('slug', '');

        $admission = \App\Models\Admission::withoutTenantScope()
            ->where('payment_reference', $reference)->first();

        if (!$admission) {
            return redirect('/')->withErrors(['error' => 'Payment reference not found.']);
        }

        // Verify with the configured gateway.
        $tenant = Tenant::find($admission->tenant_id);
        if (!$tenant) {
            return redirect('/')->withErrors(['error' => 'Payment tenant not found.']);
        }

        $config = \App\Models\PaymentGatewayConfig::where('tenant_id', $tenant->id)->first();
        $settings = AdmissionPortalSetting::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->first();
        $amount = (float) ($settings?->application_fee ?? 0);

        $verified = false;
        if ($config) {
            if ($config->gateway === 'flutterwave') {
                $transactionId = $request->get('transaction_id');

                if ($transactionId) {
                    $response = \Illuminate\Support\Facades\Http::withToken($config->secret_key)
                        ->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");

                    $verified = $response->successful()
                        && GatewayPaymentVerifier::flutterwave($response->json() ?: [], $reference, $amount);
                }
            } else {
                $response = \Illuminate\Support\Facades\Http::withToken($config->secret_key)
                    ->get("https://api.paystack.co/transaction/verify/{$reference}");

                $verified = $response->successful()
                    && GatewayPaymentVerifier::paystack($response->json() ?: [], $reference, $amount);
            }
        }

        if ($verified) {
            $admission->update(['payment_status' => 'paid']);
            return redirect()->route('portal.success', [$slug, $admission->application_number])
                ->with('success', 'Application fee paid! Your application is now complete.');
        }

        return redirect()->route('portal.status.form', $slug)
            ->withErrors(['error' => 'Payment could not be verified. Please contact the school.']);
    }

    // ── Success Page ─────────────────────────────────────────────────
    public function success(string $slug, string $appNumber)
    {
        $tenant    = $this->getTenant($slug);
        $admission = Admission::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('application_number', $appNumber)
            ->firstOrFail();
        return view('portal.admissions.success', compact('tenant', 'admission'));
    }

    // ── Application Status Check ─────────────────────────────────────
    public function statusForm(string $slug)
    {
        $tenant = $this->getTenant($slug);
        return view('portal.admissions.status-check', compact('tenant'));
    }

    public function checkStatus(Request $request, string $slug)
    {
        $tenant = $this->getTenant($slug);
        $request->validate([
            'application_number' => ['required','string'],
            'guardian_phone'     => ['required','string'],
        ]);

        $admission = Admission::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('application_number', strtoupper($request->application_number))
            ->first();

        if (!$admission || !str_contains($admission->guardian_phone, substr($request->guardian_phone, -6))) {
            return back()->withErrors(['error' => 'Application not found. Check your application number and phone number.']);
        }

        $documents = AdmissionDocument::where('admission_id', $admission->id)->get();
        $classLevel = ClassLevel::withoutTenantScope()->find($admission->applying_for_class_level_id);

        return view('portal.admissions.status',
            compact('tenant', 'admission', 'documents', 'classLevel'));
    }

    // ── Admin: Portal Settings ───────────────────────────────────────
    public function portalSettings()
    {
        $tenant   = auth()->user()->tenant;
        $settings = AdmissionPortalSetting::firstOrCreate(
            ['tenant_id' => $tenant->id],
            ['is_open' => false, 'academic_year' => date('Y') . '/' . (date('Y') + 1)]
        );
        return view('admissions.portal-settings', compact('settings'));
    }

    public function savePortalSettings(Request $request)
    {
        $data = $request->validate([
            'is_open'              => ['boolean'],
            'opens_on'             => ['nullable','date'],
            'closes_on'            => ['nullable','date','after_or_equal:opens_on'],
            'academic_year'        => ['nullable','string','max:20'],
            'application_fee'      => ['nullable','numeric','min:0'],
            'welcome_message'      => ['nullable','string','max:1000'],
            'requirements'         => ['nullable','string'],
            'require_passport'     => ['boolean'],
            'require_birth_cert'   => ['boolean'],
            'require_report_card'  => ['boolean'],
            'notify_guardian_sms'  => ['boolean'],
            'notify_guardian_email'=> ['boolean'],
            'auto_shortlist'       => ['boolean'],
            'footer_note'          => ['nullable','string','max:500'],
        ]);

        foreach (['is_open','require_passport','require_birth_cert',
                  'require_report_card','notify_guardian_sms',
                  'notify_guardian_email','auto_shortlist'] as $bool) {
            $data[$bool] = $request->boolean($bool);
        }

        AdmissionPortalSetting::updateOrCreate(
            ['tenant_id' => auth()->user()->tenant_id],
            $data
        );

        return back()->with('success', 'Admission portal settings saved.');
    }

    // ── Admin: List by portal source ────────────────────────────────
    public function portalApplications(Request $request)
    {
        $applications = Admission::where('source', 'portal')
            ->latest()->paginate(25);
        $settings = AdmissionPortalSetting::where('tenant_id', auth()->user()->tenant_id)->first();
        $portalUrl = route('portal.landing', auth()->user()->tenant?->slug ?? 'school');
        $portalStats = [
            'pending'     => Admission::where('source','portal')->where('status','pending')->count(),
            'shortlisted' => Admission::where('source','portal')->where('status','shortlisted')->count(),
            'admitted'    => Admission::where('source','portal')->where('status','admitted')->count(),
            'rejected'    => Admission::where('source','portal')->where('status','rejected')->count(),
        ];
        return view('admissions.portal-list',
            compact('applications', 'settings', 'portalUrl', 'portalStats'));
    }

    private function nigerianStates(): array
    {
        return [
            'Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa',
            'Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti',
            'Enugu','FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina',
            'Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo',
            'Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara',
        ];
    }
}
