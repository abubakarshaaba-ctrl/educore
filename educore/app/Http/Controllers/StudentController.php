<?php

namespace App\Http\Controllers;

use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\Guardian;
use App\Models\Student;
use App\Services\PlanLimitService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    // ---------------------------------------------------------------
    // LIST ALL STUDENTS
    // ---------------------------------------------------------------
    public function index(Request $request)
    {
        $query = Student::with(['currentClassArm.classLevel'])
            ->orderBy('first_name');

        // Search
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%$s%")
                  ->orWhere('last_name', 'like', "%$s%")
                  ->orWhere('admission_number', 'like', "%$s%");
            });
        }

        // Filter by class
        if ($request->filled('class_arm_id')) {
            $query->where('current_class_arm_id', $request->class_arm_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', Student::STATUS_ACTIVE);
        }

        $students  = $query->paginate(20)->withQueryString();
        $classArms = ClassArm::with('classLevel')->get();

        return view('students.index', compact('students', 'classArms'));
    }

    // ---------------------------------------------------------------
    // SHOW ADMISSION FORM
    // ---------------------------------------------------------------
    public function create()
    {
        $classLevels = ClassLevel::with('classArms')->orderBy('order_index')->get();
        return view('students.create', compact('classLevels'));
    }

    // ---------------------------------------------------------------
    // STORE NEW STUDENT
    // ---------------------------------------------------------------
    public function store(Request $request)
    {
        $tenant = auth()->user()->tenant;
        if ($error = PlanLimitService::checkStudentLimit($tenant)) {
            return back()->withErrors(['limit' => $error]);
        }

        $validated = $request->validate([
            'first_name'          => ['required', 'string', 'max:100'],
            'last_name'           => ['required', 'string', 'max:100'],
            'middle_name'         => ['nullable', 'string', 'max:100'],
            'gender'              => ['required', 'in:male,female,other'],
            'date_of_birth'       => ['required', 'date', 'before:today'],
            'current_class_arm_id'=> ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'admission_date'      => ['required', 'date'],
            'state_of_origin'     => ['nullable', 'string', 'max:100'],
            'lga_of_origin'       => ['nullable', 'string', 'max:100'],
            'religion'            => ['nullable', 'string', 'max:50'],
            'blood_group'         => ['nullable', 'string', 'max:5'],
            'genotype'            => ['nullable', 'string', 'max:5'],
            // Guardian
            'guardian_first_name' => ['required', 'string', 'max:100'],
            'guardian_last_name'  => ['required', 'string', 'max:100'],
            'guardian_phone'      => ['required', 'string', 'max:20'],
            'guardian_email'      => ['nullable', 'email', 'max:150'],
            'guardian_relationship' => ['required', 'in:father,mother,guardian,other'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            // Auto-generate admission number
            $lastNumber = Student::withoutTenantScope()
                ->where('tenant_id', auth()->user()->tenant_id)
                ->max(DB::raw('CAST(SUBSTRING(admission_number, 4) AS UNSIGNED)'));
            $admissionNumber = 'STU' . str_pad(($lastNumber + 1), 4, '0', STR_PAD_LEFT);

            // Create student
            $student = Student::create([
                'first_name'           => $validated['first_name'],
                'last_name'            => $validated['last_name'],
                'middle_name'          => $validated['middle_name'] ?? null,
                'gender'               => $validated['gender'],
                'date_of_birth'        => $validated['date_of_birth'],
                'current_class_arm_id' => $validated['current_class_arm_id'],
                'admission_date'       => $validated['admission_date'],
                'admission_number'     => $admissionNumber,
                'state_of_origin'      => $validated['state_of_origin'] ?? null,
                'lga_of_origin'        => $validated['lga_of_origin'] ?? null,
                'religion'             => $validated['religion'] ?? null,
                'blood_group'          => $validated['blood_group'] ?? null,
                'genotype'             => $validated['genotype'] ?? null,
                'status'               => Student::STATUS_ACTIVE,
            ]);

            // Create guardian and link
            $guardian = Guardian::create([
                'first_name'   => $validated['guardian_first_name'],
                'last_name'    => $validated['guardian_last_name'],
                'phone'        => $validated['guardian_phone'],
                'email'        => $validated['guardian_email'] ?? null,
                'relationship' => $validated['guardian_relationship'],
            ]);

            $student->guardians()->attach($guardian->id, [
                'tenant_id'          => auth()->user()->tenant_id,
                'is_primary_contact' => true,
            ]);
        });

        return redirect()->route('students.index')
            ->with('success', 'Student admitted successfully.');
    }

    // ---------------------------------------------------------------
    // SHOW STUDENT PROFILE
    // ---------------------------------------------------------------
    public function show(Student $student)
    {
        $student->load([
            'currentClassArm.classLevel',
            'currentClassArm.academicTrack',
            'guardians',
            'invoices' => fn($q) => $q->latest()->limit(5),
        ]);

        // All summaries for the student (for the report card panel)
        $summaries = \App\Models\TermlySummary::where('student_id', $student->id)
            ->with(['term.session', 'classArm.classLevel'])
            ->orderByDesc('term_id')->get();

        // Terms that have published report cards
        $terms = \App\Models\Term::with('session')
            ->whereIn('id', $summaries->pluck('term_id')->filter()->unique())
            ->latest()->get();

        return view('students.show', compact('student', 'terms', 'summaries'));
    }

    // ---------------------------------------------------------------
    // EDIT FORM
    // ---------------------------------------------------------------
    public function edit(Student $student)
    {
        $classLevels = ClassLevel::with('classArms')->orderBy('order_index')->get();
        $student->load('guardians');
        $classArms = \App\Models\ClassArm::with('classLevel')->get();
        return view('students.edit', compact('student', 'classLevels', 'classArms'));
    }

    // ---------------------------------------------------------------
    // UPDATE STUDENT
    // ---------------------------------------------------------------
    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'first_name'           => ['required', 'string', 'max:100'],
            'last_name'            => ['required', 'string', 'max:100'],
            'middle_name'          => ['nullable', 'string', 'max:100'],
            'gender'               => ['required', 'in:male,female,other'],
            'date_of_birth'        => ['required', 'date'],
            'current_class_arm_id' => ['required', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'state_of_origin'      => ['nullable', 'string', 'max:100'],
            'lga_of_origin'        => ['nullable', 'string', 'max:100'],
            'religion'             => ['nullable', 'string', 'max:50'],
            'blood_group'          => ['nullable', 'string', 'max:5'],
            'genotype'             => ['nullable', 'string', 'max:5'],
            'has_special_needs'    => ['nullable', 'boolean'],
            'special_needs_type'   => ['nullable', 'string', 'max:150'],
        ]);

        $validated['has_special_needs'] = $request->boolean('has_special_needs');

        if ((int) $validated['current_class_arm_id'] !== (int) $student->current_class_arm_id) {
            return back()
                ->withErrors(['current_class_arm_id' => 'Use the interclass transfer workflow to change a student class.'])
                ->withInput();
        }

        $student->update($validated);

        return redirect()->route('students.show', $student)
            ->with('success', 'Student record updated.');
    }

    // ─── DELETE STUDENT ──────────────────────────────────────────
    public function destroy(Student $student)
    {
        return back()->withErrors([
            'error' => 'Student records are preserved for audit and history. Use the lifecycle status workflow instead.',
        ]);

        // Prevent delete if student has scores or invoices
        if ($student->scores()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete '.$student->full_name.' — they have score records. Withdraw the student instead.']);
        }
        $name = $student->full_name;
        $student->delete();
        return redirect()->route('students.index')->with('success', $name.' deleted.');
    }

    // ── Transcript / Academic Portfolio ───────────────────────────────
    // ── Transcript Index (search page) ───────────────────────────────
    public function transcriptIndex(Request $request)
    {
        if (!auth()->user()->canAccessModule('transcript')) {
            abort(403);
        }
        $query    = $request->input('q');
        $students = null;
        if ($query) {
            $tid      = auth()->user()->tenant_id;
            $students = \App\Models\Student::where('tenant_id', $tid)
                ->where(function ($q) use ($query) {
                    $q->where('first_name', 'like', "%{$query}%")
                      ->orWhere('last_name',   'like', "%{$query}%")
                      ->orWhere('middle_name', 'like', "%{$query}%")
                      ->orWhere('admission_number', 'like', "%{$query}%");
                })
                ->with('currentClassArm.classLevel')
                ->orderBy('first_name')->limit(20)->get();
        }
        return view('students.transcript-index', compact('query', 'students'));
    }

    public function transcript(Student $student)
    {
        if (!auth()->user()->canAccessModule('transcript')) {
            abort(403, 'Access denied. Transcripts are restricted to senior administration only.');
        }
        $student->load(['currentClassArm.classLevel', 'guardians', 'enrollments.classArm.classLevel']);
        ['summaries' => $summaries, 'bySession' => $bySession, 'scoresByTerm' => $scoresByTerm, 'allSubjects' => $allSubjects, 'gradingSystems' => $gradingSystems]
            = $this->buildTranscriptData($student);
        $tenant = auth()->user()->tenant;
        return view('students.transcript', compact('student', 'summaries', 'bySession', 'tenant', 'scoresByTerm', 'allSubjects', 'gradingSystems'));
    }

    public function transcriptPdf(Student $student)
    {
        if (!auth()->user()->canAccessModule('transcript')) {
            abort(403, 'Access denied. Transcripts are restricted to senior administration only.');
        }
        $student->load(['currentClassArm.classLevel', 'guardians']);
        ['summaries' => $summaries, 'bySession' => $bySession, 'scoresByTerm' => $scoresByTerm, 'allSubjects' => $allSubjects, 'gradingSystems' => $gradingSystems]
            = $this->buildTranscriptData($student);
        $tenant = auth()->user()->tenant;
        $pdf = Pdf::loadView('students.transcript-pdf', compact('student', 'summaries', 'bySession', 'tenant', 'scoresByTerm', 'allSubjects', 'gradingSystems'))->setPaper('a3', 'landscape');
        $filename = 'Transcript_' . str_replace(' ', '_', $student->full_name) . '_' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    private function buildTranscriptData(Student $student): array
    {
        $summaries = \App\Models\TermlySummary::where('student_id', $student->id)
            ->with(['term.session', 'classArm.classLevel'])
            ->orderBy('session_id')->orderBy('term_id')->get();

        $termIds = $summaries->pluck('term_id')->filter()->unique();
        $scores  = \App\Models\Score::where('student_id', $student->id)
            ->whereIn('term_id', $termIds)
            ->with(['subject'])
            ->get();

        // Load all relevant grading systems indexed by class_level_id
        $classLevelIds = $summaries->pluck('classArm.class_level_id')->filter()->unique();
        $gradingSystems = \App\Models\GradingSystem::whereIn('class_level_id', $classLevelIds)->get()->groupBy('class_level_id');

        // Map term_id -> class_level_id via summary
        $termClassLevel = $summaries->pluck('classArm.class_level_id', 'term_id');

        $scoresByTerm = [];
        foreach ($scores as $score) {
            $tid = $score->term_id;
            $sid = $score->subject_id;
            $scoresByTerm[$tid][$sid]['name']  = optional($score->subject)->name;
            $scoresByTerm[$tid][$sid]['total'] = round(($scoresByTerm[$tid][$sid]['total'] ?? 0) + $score->score, 1);
        }

        // Enrich with grade letter using grading system for that term's class level
        foreach ($scoresByTerm as $tid => &$subjects) {
            $clId  = $termClassLevel[$tid] ?? null;
            $gs    = $clId ? ($gradingSystems[$clId] ?? collect()) : collect();
            foreach ($subjects as $sid => &$data) {
                $total = $data['total'];
                $grade = $gs->first(fn($g) => $total >= $g->min_score && $total <= $g->max_score);
                if (!$grade && $gs->isEmpty()) {
                    // Default grading scale when none configured
                    $letter = match(true) {
                        $total >= 70 => 'A',
                        $total >= 60 => 'B',
                        $total >= 50 => 'C',
                        $total >= 45 => 'D',
                        $total >= 40 => 'E',
                        default      => 'F',
                    };
                    $data['grade']   = $letter;
                    $data['is_pass'] = $total >= 40;
                } else {
                    $data['grade']   = $grade?->grade_letter ?? '—';
                    $data['is_pass'] = $grade?->is_pass_grade ?? ($total >= 40);
                }
            }
            unset($data);
            uasort($subjects, fn($a, $b) => strcmp($a['name'], $b['name']));
        }
        unset($subjects);

        $allSubjects = collect();
        foreach ($scoresByTerm as $subjects) {
            foreach ($subjects as $sid => $data) {
                $allSubjects->put($sid, $data['name']);
            }
        }
        $allSubjects = $allSubjects->sort();
        $bySession   = $summaries->groupBy(fn($s) => $s->session_id);

        return compact('summaries', 'bySession', 'scoresByTerm', 'allSubjects', 'gradingSystems');
    }
}
