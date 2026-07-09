<?php
namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\ClassLevel;
use App\Models\Student;
use App\Models\ClassArm;
use App\Models\Guardian;
use App\Notifications\AdmissionOfferNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Admission::with('applyingForClassLevel')->latest();
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('search')) {
            $query->where(fn($q) =>
                $q->where('first_name','like','%'.$request->search.'%')
                  ->orWhere('last_name','like','%'.$request->search.'%')
                  ->orWhere('application_number','like','%'.$request->search.'%')
            );
        }
        $admissions = $query->paginate(20)->withQueryString();
        $stats = [
            'total'       => Admission::count(),
            'pending'     => Admission::where('status','pending')->count(),
            'shortlisted' => Admission::where('status','shortlisted')->count(),
            'admitted'    => Admission::where('status','admitted')->count(),
            'rejected'    => Admission::where('status','rejected')->count(),
        ];
        $classLevels = ClassLevel::orderBy('name')->get();
        return view('admissions.index', compact('admissions', 'stats', 'classLevels'));
    }

    public function create()
    {
        $classLevels = ClassLevel::orderBy('name')->get();
        return view('admissions.create', compact('classLevels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'              => ['required','string','max:80'],
            'last_name'               => ['required','string','max:80'],
            'other_names'             => ['nullable','string','max:80'],
            'date_of_birth'           => ['required','date'],
            'gender'                  => ['required','in:male,female'],
            'religion'                => ['nullable','string'],
            'state_of_origin'         => ['nullable','string'],
            'address'                 => ['nullable','string'],
            'applying_for_class_level_id' => ['nullable','exists:class_levels,id'],
            'previous_school'         => ['nullable','string'],
            'guardian_name'           => ['required','string'],
            'guardian_phone'          => ['required','string'],
            'guardian_email'          => ['nullable','email'],
            'guardian_relationship'   => ['required','string'],
            'guardian_occupation'     => ['nullable','string'],
            'guardian_address'        => ['nullable','string'],
            'notes'                   => ['nullable','string'],
        ]);
        $data['application_number'] = 'APP-'.date('Y').'-'.strtoupper(Str::random(6));
        $data['application_date']   = now()->toDateString();
        $data['status']             = 'pending';
        Admission::create($data);
        return redirect()->route('admissions.index')->with('success', 'Application submitted successfully.');
    }

    public function show(Admission $admission)
    {
        $classLevels = ClassLevel::orderBy('name')->get();
        $classArms   = ClassArm::with('classLevel')->get();
        return view('admissions.show', compact('admission', 'classLevels', 'classArms'));
    }

    public function updateStatus(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'status'        => ['required','in:pending,shortlisted,admitted,rejected,withdrawn'],
            'notes'         => ['nullable','string'],
            'class_arm_id'  => ['nullable','exists:class_arms,id'],
        ]);

        $statusChanged = $admission->status !== $data['status'];

        $admission->update([
            'status'        => $data['status'],
            'notes'         => $data['notes'] ?? $admission->notes,
            'reviewed_by'   => auth()->id(),
            'decision_date' => now()->toDateString(),
        ]);

        // If admitted → create student record
        if ($data['status'] === 'admitted' && !$admission->enrolled_as_student_id) {
            $this->enrollStudent($admission, $data['class_arm_id'] ?? null);
        }

        if ($statusChanged) {
            $this->notifyGuardianOfStatus($admission);
        }

        return back()->with('success', "Application status updated to: {$data['status']}.");
    }

    /** Notify the guardian by email/SMS whenever the application's status changes. */
    private function notifyGuardianOfStatus(Admission $admission): void
    {
        $tenant = auth()->user()->tenant;
        $name = $admission->first_name . ' ' . $admission->last_name;

        $line = match ($admission->status) {
            'shortlisted' => "Good news! {$name}'s application has been shortlisted. We will contact you with next steps.",
            'admitted'    => "Congratulations! {$name} has been offered admission. You will receive a formal offer letter shortly.",
            'rejected'    => "Thank you for applying. After careful review, we will not be proceeding with {$name}'s application at this time.",
            'withdrawn'   => "{$name}'s application has been marked as withdrawn as requested.",
            default       => "{$name}'s application status has been updated to: " . ucfirst($admission->status) . '.',
        };

        $guardian = new Guardian([
            'first_name' => $admission->guardian_name,
            'email'      => $admission->guardian_email,
            'phone'      => $admission->guardian_phone,
        ]);
        $guardian->tenant_id = $tenant->id;

        try {
            app(\App\Services\GuardianNotifier::class)->send(
                $guardian,
                'Application update — ' . $name . ' — ' . $tenant->name,
                [$line, 'Application number: ' . $admission->application_number],
                smsBody: "Dear {$admission->guardian_name}, {$line}",
                actionLabel: 'Track Application',
                actionUrl: route('portal.status.form', $tenant->slug),
                schoolName: $tenant->name,
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Admission status-change guardian notification failed: ' . $e->getMessage());
        }
    }

    private function enrollStudent(Admission $admission, ?int $classArmId)
    {
        $enrolled = null;

        DB::transaction(function () use ($admission, $classArmId, &$enrolled) {
            $tenant  = auth()->user()->tenant;
            $session = \App\Models\AcademicSession::where('is_current', true)->first();

            // Generate admission number scoped to this tenant to avoid cross-tenant collisions
            $maxNum = Student::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->whereRaw("admission_number REGEXP '^STU[0-9]+$'")
                ->max(\Illuminate\Support\Facades\DB::raw('CAST(SUBSTRING(admission_number, 4) AS UNSIGNED)'));
            $admNum = 'STU' . str_pad((int) $maxNum + 1, 4, '0', STR_PAD_LEFT);

            $student = Student::create([
                'first_name'           => $admission->first_name,
                'last_name'            => $admission->last_name,
                'middle_name'          => $admission->other_names,
                'admission_number'     => $admNum,
                'date_of_birth'        => $admission->date_of_birth,
                'gender'               => $admission->gender,
                'religion'             => $admission->religion,
                'state_of_origin'      => $admission->state_of_origin,
                'current_class_arm_id' => $classArmId,
                'status'               => 'active',
                'admission_date'       => now(),
            ]);

            // Split guardian_name into first/last
            $nameParts  = explode(' ', trim($admission->guardian_name), 2);
            $firstName  = $nameParts[0];
            $lastName   = $nameParts[1] ?? '';

            $guardian = Guardian::create([
                'tenant_id'    => $tenant->id,
                'first_name'   => $firstName,
                'last_name'    => $lastName,
                'phone'        => $admission->guardian_phone,
                'email'        => $admission->guardian_email,
                'relationship' => $admission->guardian_relationship,
                'occupation'   => $admission->guardian_occupation,
                'address'      => $admission->guardian_address,
            ]);
            // Link guardian to student via pivot (not a direct FK on guardian)
            $student->guardians()->attach($guardian->id, ['is_primary_contact' => true, 'tenant_id' => $tenant->id]);

            $admission->update(['enrolled_as_student_id' => $student->id]);

            $enrolled = ['student' => $student, 'guardian' => $guardian, 'tenant' => $tenant];
        });

        if (!$enrolled) {
            return;
        }

        try {
            app(\App\Services\GuardianNotifier::class)->send(
                $enrolled['guardian'],
                'Enrollment confirmed — ' . $enrolled['student']->full_name,
                [
                    $enrolled['student']->full_name . ' has been successfully enrolled at ' . $enrolled['tenant']->name . '.',
                    'Admission number: ' . $enrolled['student']->admission_number,
                ],
                smsBody: "Dear {$enrolled['guardian']->full_name}, {$enrolled['student']->full_name} has been successfully enrolled at {$enrolled['tenant']->name}. Admission No: {$enrolled['student']->admission_number}.",
                actionLabel: 'Sign In',
                actionUrl: route('login'),
                schoolName: $enrolled['tenant']->name,
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Enrollment guardian notification failed: ' . $e->getMessage());
        }
    }

    public function destroy(Admission $admission)
    {
        $admission->delete();
        return redirect()->route('admissions.index')->with('success', 'Application deleted.');
    }

    // ── Schedule Interview ────────────────────────────────────────────
    public function scheduleInterview(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'interview_date'  => ['required', 'date', 'after_or_equal:today'],
            'interview_notes' => ['nullable', 'string'],
        ]);
        $admission->update($data);

        $tenant = auth()->user()->tenant;
        $name = $admission->first_name . ' ' . $admission->last_name;
        $dateLabel = \Carbon\Carbon::parse($data['interview_date'])->format('d M Y');

        $guardian = new Guardian([
            'first_name' => $admission->guardian_name,
            'email'      => $admission->guardian_email,
            'phone'      => $admission->guardian_phone,
        ]);
        $guardian->tenant_id = $tenant->id;

        try {
            app(\App\Services\GuardianNotifier::class)->send(
                $guardian,
                'Interview scheduled — ' . $name . ' — ' . $tenant->name,
                [
                    "{$name} has been scheduled for an interview on {$dateLabel}. Please arrive 15 minutes early.",
                    'Application number: ' . $admission->application_number,
                ],
                smsBody: "Dear {$admission->guardian_name}, {$name} has been scheduled for an interview on {$dateLabel}. Please arrive 15 minutes early. {$tenant->name}",
                actionLabel: 'Track Application',
                actionUrl: route('portal.status.form', $tenant->slug),
                schoolName: $tenant->name,
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Admission interview guardian notification failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Interview scheduled and guardian notified.');
    }

    // ── Record Interview Score ────────────────────────────────────────
    public function recordInterview(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'interview_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'interview_notes' => ['nullable', 'string'],
        ]);
        $admission->update($data);
        return back()->with('success', 'Interview score recorded.');
    }

    // ── Send Offer Letter ─────────────────────────────────────────────
    public function sendOffer(Admission $admission)
    {
        if ($admission->status !== 'admitted') {
            return back()->withErrors(['error' => 'Applicant must be admitted before sending offer letter.']);
        }

        $tenant = auth()->user()->tenant;
        $pdf = Pdf::loadView('admissions.offer-letter-pdf', [
            'admission' => $admission,
            'tenant'    => $tenant,
            ...$this->offerLetterVars($admission, $tenant),
        ]);
        $pdfContent = $pdf->output();

        $statusUrl = route('portal.status.form', $tenant->slug);

        if ($admission->guardian_email) {
            try {
                \Illuminate\Support\Facades\Notification::route('mail', $admission->guardian_email)
                    ->notify(new AdmissionOfferNotification($admission, $tenant->name, $statusUrl, $pdfContent));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Admission offer email failed: ' . $e->getMessage());
            }
        }

        $smsBody = "Dear {$admission->guardian_name}, we are pleased to offer {$admission->first_name} {$admission->last_name} admission to {$tenant->name}. Please contact us to complete enrollment. Application No: {$admission->application_number}";
        $guardian = new Guardian([
            'first_name' => $admission->guardian_name,
            'phone'      => $admission->guardian_phone,
        ]);
        $guardian->tenant_id = $tenant->id;

        try {
            app(\App\Services\GuardianNotifier::class)->send(
                $guardian,
                'Admission Offer — ' . $admission->first_name . ' ' . $admission->last_name,
                [], // email already sent above with the PDF attached; this call is SMS-only
                smsBody: $smsBody,
                schoolName: $tenant->name,
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Admission offer SMS failed: ' . $e->getMessage());
        }

        $admission->update(['offer_letter_sent' => true, 'offer_sent_at' => now()]);
        return back()->with('success', 'Offer letter emailed to guardian with PDF attached, and SMS sent.');
    }

    // ── Download Offer Letter PDF (admin re-download) ────────────────
    public function downloadOfferLetter(Admission $admission)
    {
        $tenant = auth()->user()->tenant;

        $pdf = Pdf::loadView('admissions.offer-letter-pdf', [
            'admission' => $admission,
            'tenant'    => $tenant,
            ...$this->offerLetterVars($admission, $tenant),
        ]);

        return $pdf->download("Admission-Offer-{$admission->application_number}.pdf");
    }

    /** Merge the tenant's (customisable) admission-offer letter template with this application's details. */
    private function offerLetterVars(Admission $admission, \App\Models\Tenant $tenant): array
    {
        $template = \App\Models\LetterTemplate::forTenant($tenant->id, \App\Models\LetterTemplate::TYPE_ADMISSION_OFFER);

        $vars = [
            'applicant_name' => $admission->first_name . ' ' . $admission->last_name,
            'guardian_name'  => $admission->guardian_name,
            'school_name'    => $tenant->name,
            'class'          => $admission->applyingForClassLevel?->name ?? 'the appropriate class',
            'academic_year'  => $admission->academic_year ?? (date('Y') . '/' . (date('Y') + 1)),
            'application_number' => $admission->application_number,
        ];

        return [
            'intro'      => \App\Models\LetterTemplate::merge($template->intro_text, $vars),
            'body'       => \App\Models\LetterTemplate::merge($template->body_text, $vars),
            'closing'    => \App\Models\LetterTemplate::merge($template->closing_text, $vars),
            'signatory1' => $template->signatory_1_label ?: 'Admissions Officer',
            'signatory2' => $template->signatory_2_label ?: 'Principal / Head of School',
        ];
    }

    // ── Documents view ────────────────────────────────────────────────
    public function documents(Admission $admission)
    {
        $docs = \App\Models\AdmissionDocument::where('admission_id', $admission->id)->get();
        return view('admissions.documents', compact('admission', 'docs'));
    }

    // ── Download a single document ────────────────────────────────────
    public function downloadDocument(Admission $admission, \App\Models\AdmissionDocument $doc)
    {
        abort_if($doc->admission_id !== $admission->id, 403);
        $path = storage_path('app/public/' . $doc->file_path);
        if (!file_exists($path)) {
            return back()->withErrors(['error' => 'File not found on server.']);
        }
        return response()->download($path, $doc->original_name);
    }

    // ── Verify / reject a single document ────────────────────────────
    public function verifyDocument(Request $request, Admission $admission, \App\Models\AdmissionDocument $doc)
    {
        abort_if($doc->admission_id !== $admission->id, 403);

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

        $label = match($data['action']) {
            'verified' => 'verified',
            'rejected' => 'rejected',
            default    => 'reset to pending',
        };

        return back()->with('success', ucwords(str_replace('_', ' ', $doc->document_type)) . ' document ' . $label . '.');
    }

    // ── Bulk status update ────────────────────────────────────────────
    public function bulkStatus(Request $request)
    {
        $data = $request->validate([
            'ids'    => ['required', 'array'],
            'ids.*'  => ['exists:admissions,id'],
            'status' => ['required', 'in:shortlisted,rejected,pending'],
        ]);
        Admission::whereIn('id', $data['ids'])->update([
            'status'        => $data['status'],
            'reviewed_by'   => auth()->id(),
            'decision_date' => today(),
        ]);
        return back()->with('success', count($data['ids']) . ' applications updated to ' . $data['status'] . '.');
    }

    // ── Export applications CSV ───────────────────────────────────────
    public function exportCsv(Request $request)
    {
        $query = Admission::with('applyingForClassLevel')->latest();
        if ($request->filled('status')) $query->where('status', $request->status);

        $admissions = $query->get();
        $filename   = 'Applications_' . date('Y-m-d') . '.csv';

        $callback = function () use ($admissions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['App No','First Name','Last Name','Gender','DOB','Class Applied','Guardian','Phone','Email','Status','Applied On','Source']);
            foreach ($admissions as $a) {
                fputcsv($file, [
                    $a->application_number, $a->first_name, $a->last_name,
                    $a->gender, $a->date_of_birth,
                    $a->applyingForClassLevel?->name ?? '—',
                    $a->guardian_name, $a->guardian_phone, $a->guardian_email ?? '—',
                    $a->status, $a->application_date, $a->source ?? 'manual',
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

}
