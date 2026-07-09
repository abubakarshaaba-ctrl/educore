<?php

namespace App\Http\Controllers;

use App\Models\JobApplicant;
use App\Models\JobInterview;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RecruitmentController extends Controller
{
    public function index()
    {
        $postings = JobPosting::withCount('applicants')->latest()->get();

        return view('recruitment.index', compact('postings'));
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
        $applicants = $posting->applicants()->with('interviews')->latest()->paginate(25);

        return view('recruitment.show', compact('posting', 'applicants'));
    }

    public function storeApplicant(Request $request, JobPosting $posting)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:4096'],
            'cover_letter' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('resume')) {
            $data['resume_path'] = $request->file('resume')->store("recruitment/{$posting->id}", 'public');
        }

        $data['job_posting_id'] = $posting->id;
        $data['status'] = 'applied';
        $data['applied_at'] = now();
        unset($data['resume']);

        JobApplicant::create($data);

        return back()->with('success', 'Applicant added.');
    }

    public function updateApplicantStatus(Request $request, JobApplicant $applicant)
    {
        $data = $request->validate([
            'status' => ['required', 'in:applied,shortlisted,interview_scheduled,interviewed,offered,hired,rejected'],
            'notes'  => ['nullable', 'string'],
        ]);

        $applicant->update($data);

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

        return back()->with('success', 'Interview scheduled.');
    }
}
