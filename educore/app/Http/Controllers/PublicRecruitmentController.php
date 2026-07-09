<?php
namespace App\Http\Controllers;

use App\Models\JobApplicant;
use App\Models\JobPosting;
use App\Models\Tenant;
use Illuminate\Http\Request;

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
            'status'         => 'applied',
            'applied_at'     => now(),
        ]);

        try {
            $tenant->notifyAdmins(new \App\Notifications\Tenant\NewJobApplicantNotification($applicant));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('New job applicant admin notification failed: ' . $e->getMessage());
        }

        return redirect()->route('careers.show', [$slug, $jobPosting->id])
            ->with('success', 'Your application has been submitted. Thank you!');
    }
}
