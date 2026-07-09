<?php
namespace App\Http\Controllers;

use App\Models\JobApplicant;
use App\Models\JobApplicantMessage;
use App\Models\JobPosting;
use App\Models\Tenant;
use App\Notifications\ApplicantApplicationReceivedNotification;
use App\Notifications\ApplicantMessageReceivedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;

/**
 * PUBLIC Careers Portal — NO AUTH REQUIRED
 * Access via: /careers/{school_slug}
 */
class PublicRecruitmentController extends Controller
{
    private function getTenant(string $slug): Tenant
    {
        return Tenant::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();
    }

    public function landing(string $slug)
    {
        $tenant = $this->getTenant($slug);

        $postings = JobPosting::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'open')
            ->latest()
            ->get();

        return view('portal.careers.landing', compact('tenant', 'postings'));
    }

    public function show(string $slug, int $posting)
    {
        $tenant = $this->getTenant($slug);

        $jobPosting = JobPosting::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'open')
            ->findOrFail($posting);

        return view('portal.careers.show', ['tenant' => $tenant, 'posting' => $jobPosting]);
    }

    public function apply(Request $request, string $slug, int $posting)
    {
        $tenant = $this->getTenant($slug);

        $jobPosting = JobPosting::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'open')
            ->findOrFail($posting);

        $data = $request->validate([
            'name'         => ['required', 'string', 'max:150'],
            'email'        => ['nullable', 'email', 'max:150'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'resume'       => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:4096'],
            'cover_letter' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('resume')) {
            $data['resume_path'] = $request->file('resume')->store("recruitment/{$jobPosting->id}", 'public');
        }
        unset($data['resume']);

        $applicant = JobApplicant::withoutTenantScope()->create([
            ...$data,
            'tenant_id'      => $tenant->id,
            'job_posting_id' => $jobPosting->id,
            'access_token'   => Str::random(32),
            'status'         => 'applied',
            'applied_at'     => now(),
        ]);

        try {
            $tenant->notifyAdmins(new \App\Notifications\Tenant\NewJobApplicantNotification($applicant));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('New job applicant admin notification failed: ' . $e->getMessage());
        }

        $trackUrl = rtrim($request->getSchemeAndHttpHost(), '/') . '/careers/track/' . $applicant->access_token;

        if ($applicant->email) {
            try {
                NotificationFacade::route('mail', $applicant->email)
                    ->notify(new ApplicantApplicationReceivedNotification($applicant, $tenant->name, $trackUrl));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Applicant application-received email failed: ' . $e->getMessage());
            }
        }

        return redirect($trackUrl)
            ->with('success', 'Your application has been submitted. Thank you!');
    }

    // ── Public: Track Application & Message Thread ───────────────────
    public function track(string $slug, string $token)
    {
        $tenant = $this->getTenant($slug);

        $applicant = JobApplicant::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('access_token', $token)
            ->with(['jobPosting', 'messages'])
            ->firstOrFail();

        return view('portal.careers.track', compact('tenant', 'applicant'));
    }

    public function reply(Request $request, string $slug, string $token)
    {
        $tenant = $this->getTenant($slug);

        $applicant = JobApplicant::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('access_token', $token)
            ->firstOrFail();

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = JobApplicantMessage::withoutTenantScope()->create([
            'tenant_id'       => $tenant->id,
            'job_applicant_id' => $applicant->id,
            'sender_type'     => 'applicant',
            'body'            => $data['body'],
        ]);

        try {
            $tenant->notifyAdmins(new \App\Notifications\Tenant\NewApplicantMessageNotification($message));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('New applicant message admin notification failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Your message has been sent.');
    }
}
