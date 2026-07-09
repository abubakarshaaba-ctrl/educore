<?php

namespace App\Http\Controllers;

use App\Models\JobApplicant;
use App\Models\JobApplicantDocument;
use App\Models\JobApplicantMessage;
use App\Models\JobInterview;
use App\Models\JobPosting;
use App\Models\LetterTemplate;
use App\Notifications\ApplicantMessageReceivedNotification;
use App\Notifications\ApplicantStatusChangedNotification;
use App\Notifications\JobOfferNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class RecruitmentController extends Controller
{
    public function index()
    {
        $postings = JobPosting::withCount('applicants')->latest()->get();
        $careersUrl = route('careers.landing', auth()->user()->tenant?->slug ?? 'school');

        return view('recruitment.index', compact('postings', 'careersUrl'));
    }

    public function storePosting(Request $request)
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:150'],
            'department'   => ['nullable', 'string', 'max:120'],
            'description'  => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'closes_at'    => ['nullable', 'date'],
        ]);

        $data['status'] = 'open';
        $data['posted_by'] = auth()->id();

        JobPosting::create($data);

        return back()->with('success', 'Job posting created.');
    }

    public function closePosting(JobPosting $posting)
    {
        $posting->update(['status' => 'closed']);
        return back()->with('success', 'Job posting closed.');
    }

    public function show(JobPosting $posting)
    {
        $applicants = $posting->applicants()->with(['interviews', 'messages'])->withCount('documents')->latest()->paginate(25);

        return view('recruitment.show', compact('posting', 'applicants'));
    }

    public function storeApplicant(Request $request, JobPosting $posting)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:4096'],
            'certificates' => ['nullable', 'array'],
            'certificates.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:4096'],
            'cover_letter' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('resume')) {
            $data['resume_path'] = $request->file('resume')->store("recruitment/{$posting->id}", 'public');
        }

        $data['job_posting_id'] = $posting->id;
        $data['access_token'] = \Illuminate\Support\Str::random(32);
        $data['status'] = 'applied';
        $data['applied_at'] = now();
        unset($data['resume'], $data['certificates']);

        $applicant = JobApplicant::create($data);

        if ($applicant->resume_path) {
            JobApplicantDocument::create([
                'job_applicant_id' => $applicant->id,
                'document_type'    => 'resume',
                'file_path'        => $applicant->resume_path,
                'original_name'    => $request->file('resume')->getClientOriginalName(),
            ]);
        }

        foreach ($request->file('certificates', []) as $certFile) {
            $path = $certFile->store("recruitment/{$posting->id}/certificates", 'public');
            JobApplicantDocument::create([
                'job_applicant_id' => $applicant->id,
                'document_type'    => 'certificate',
                'file_path'        => $path,
                'original_name'    => $certFile->getClientOriginalName(),
            ]);
        }

        return back()->with('success', 'Applicant added.');
    }

    // ── Documents review ──────────────────────────────────────────────
    public function documents(JobApplicant $applicant)
    {
        $docs = JobApplicantDocument::where('job_applicant_id', $applicant->id)->get();

        return view('recruitment.documents', compact('applicant', 'docs'));
    }

    public function downloadDocument(JobApplicant $applicant, JobApplicantDocument $doc)
    {
        abort_if($doc->job_applicant_id !== $applicant->id, 403);

        $path = storage_path('app/public/' . $doc->file_path);
        if (!file_exists($path)) {
            return back()->withErrors(['error' => 'File not found on server.']);
        }

        return response()->download($path, $doc->original_name);
    }

    public function verifyDocument(Request $request, JobApplicant $applicant, JobApplicantDocument $doc)
    {
        abort_if($doc->job_applicant_id !== $applicant->id, 403);

        $data = $request->validate([
            'action' => ['required', 'in:verified,rejected,pending'],
            'note'   => ['nullable', 'string', 'max:500'],
        ]);

        $doc->update([
            'verification_status' => $data['action'],
            'verification_note'   => $data['note'] ?? null,
            'verified_by'         => auth()->id(),
            'verified_at'         => now(),
        ]);

        $label = match ($data['action']) {
            'verified' => 'verified',
            'rejected' => 'rejected',
            default    => 'reset to pending',
        };

        return back()->with('success', ucwords(str_replace('_', ' ', $doc->document_type)) . ' document ' . $label . '.');
    }

    public function updateApplicantStatus(Request $request, JobApplicant $applicant)
    {
        $data = $request->validate([
            'status' => ['required', 'in:applied,shortlisted,interview_scheduled,interviewed,offered,hired,rejected'],
            'notes'  => ['nullable', 'string'],
        ]);

        $statusChanged = $applicant->status !== $data['status'];
        $applicant->update($data);

        if ($statusChanged) {
            $this->notifyApplicantOfStatus($applicant);
        }

        return back()->with('success', 'Applicant status updated.');
    }

    public function scheduleInterview(Request $request, JobApplicant $applicant)
    {
        $data = $request->validate([
            'interview_at'   => ['required', 'date'],
            'interviewer_id' => ['nullable', 'exists:users,id'],
            'notes'          => ['nullable', 'string'],
        ]);

        JobInterview::create([
            'applicant_id'   => $applicant->id,
            'interview_at'   => $data['interview_at'],
            'interviewer_id' => $data['interviewer_id'] ?? auth()->id(),
            'notes'          => $data['notes'] ?? null,
            'outcome'        => 'pending',
        ]);

        $applicant->update(['status' => 'interview_scheduled']);
        $this->notifyApplicantOfStatus($applicant);

        return back()->with('success', 'Interview scheduled.');
    }

    private function notifyApplicantOfStatus(JobApplicant $applicant): void
    {
        if (!$applicant->email) {
            return;
        }

        if (!$applicant->access_token) {
            $applicant->update(['access_token' => \Illuminate\Support\Str::random(32)]);
        }

        $tenant = auth()->user()->tenant;
        $trackUrl = route('careers.track', [$tenant->slug, $applicant->access_token]);

        try {
            Notification::route('mail', $applicant->email)
                ->notify(new ApplicantStatusChangedNotification($applicant, $tenant->name, $trackUrl));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Applicant status-changed email failed: ' . $e->getMessage());
        }
    }

    public function sendMessage(Request $request, JobApplicant $applicant)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        if (!$applicant->access_token) {
            $applicant->update(['access_token' => \Illuminate\Support\Str::random(32)]);
        }

        $message = JobApplicantMessage::create([
            'job_applicant_id' => $applicant->id,
            'sender_type'       => 'school',
            'sender_user_id'    => auth()->id(),
            'body'              => $data['body'],
        ]);

        if ($applicant->email) {
            $tenant = auth()->user()->tenant;
            $trackUrl = route('careers.track', [$tenant->slug, $applicant->access_token]);

            try {
                Notification::route('mail', $applicant->email)
                    ->notify(new ApplicantMessageReceivedNotification($message, $tenant->name, $trackUrl));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Applicant message-received email failed: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Message sent to applicant.');
    }

    // ── Send / Download Job Offer Letter ──────────────────────────────
    public function sendOffer(JobApplicant $applicant)
    {
        $tenant = auth()->user()->tenant;
        $vars = $this->offerLetterVars($applicant, $tenant);

        $pdf = Pdf::loadView('recruitment.job-offer-letter-pdf', [
            'applicant' => $applicant,
            'tenant'    => $tenant,
            ...$vars,
        ]);
        $pdfContent = $pdf->output();

        if (!$applicant->access_token) {
            $applicant->update(['access_token' => \Illuminate\Support\Str::random(32)]);
        }
        $trackUrl = route('careers.track', [$tenant->slug, $applicant->access_token]);

        if ($applicant->email) {
            try {
                Notification::route('mail', $applicant->email)
                    ->notify(new JobOfferNotification($applicant, $tenant->name, $trackUrl, $pdfContent));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Job offer email failed: ' . $e->getMessage());
            }
        }

        $applicant->update([
            'status'            => 'offered',
            'offer_letter_sent' => true,
            'offer_sent_at'     => now(),
        ]);

        return back()->with('success', 'Offer letter emailed to candidate with PDF attached.');
    }

    public function downloadOfferLetter(JobApplicant $applicant)
    {
        $tenant = auth()->user()->tenant;

        $pdf = Pdf::loadView('recruitment.job-offer-letter-pdf', [
            'applicant' => $applicant,
            'tenant'    => $tenant,
            ...$this->offerLetterVars($applicant, $tenant),
        ]);

        return $pdf->download("Job-Offer-{$applicant->id}-{$applicant->name}.pdf");
    }

    /** Merge the tenant's (customisable) job-offer letter template with this applicant's details. */
    private function offerLetterVars(JobApplicant $applicant, \App\Models\Tenant $tenant): array
    {
        $template = LetterTemplate::forTenant($tenant->id, LetterTemplate::TYPE_JOB_OFFER);

        $vars = [
            'applicant_name' => $applicant->name,
            'school_name'    => $tenant->name,
            'position'       => $applicant->jobPosting->title,
            'department'     => $applicant->jobPosting->department ?? 'the school',
        ];

        return [
            'intro'      => LetterTemplate::merge($template->intro_text, $vars),
            'body'       => LetterTemplate::merge($template->body_text, $vars),
            'closing'    => LetterTemplate::merge($template->closing_text, $vars),
            'signatory1' => $template->signatory_1_label ?: 'HR / Recruitment Officer',
            'signatory2' => $template->signatory_2_label ?: 'Principal / Head of School',
        ];
    }
}
