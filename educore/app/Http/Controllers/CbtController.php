<?php

namespace App\Http\Controllers;

use App\Models\CbtExam;
use App\Models\CbtQuestion;
use App\Models\CbtQuestionBank;
use App\Models\CbtStudentSession;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CbtController extends Controller
{
    private function canManageCbt($user): bool
    {
        return $user
            && !$user->isStudent()
            && ($user->isSuperAdmin() || $user->canAccessModule('cbt'));
    }

    private function authorizeCbtStaffAccess(): void
    {
        abort_unless($this->canManageCbt(Auth::user()), 403, 'You are not authorized to access CBT management.');
    }

    /** Admin-tier roles get full CBT access; subject/form-subject teachers are scoped to what they teach. */
    private function hasFullCbtAccess($user): bool
    {
        if (!$user) return false;
        if ($user->isSuperAdmin() || $user->isAdmin()) return true;
        return !in_array($user->roleKey(), ['subject_teacher', 'teacher', 'form_subject_teacher'], true);
    }

    /** True if this teacher is assigned (via class_arm_subjects) to teach this bank's subject at its class level. */
    private function teacherTeachesBank($user, CbtQuestionBank $bank): bool
    {
        if ($this->hasFullCbtAccess($user)) return true;
        return \App\Models\ClassArmSubject::where('teacher_id', $user->id)
            ->where('subject_id', $bank->subject_id)
            ->whereHas('classArm', fn ($q) => $q->where('class_level_id', $bank->class_level_id))
            ->exists();
    }

    /** Subject IDs this teacher is assigned to teach (used to scope bank/exam listings and creation). */
    private function teacherSubjectIds($user): \Illuminate\Support\Collection
    {
        return \App\Models\ClassArmSubject::where('teacher_id', $user->id)->pluck('subject_id')->unique();
    }

    /** Class arm IDs this teacher is assigned to teach in (any subject). */
    private function teacherClassArmIds($user): \Illuminate\Support\Collection
    {
        return \App\Models\ClassArmSubject::where('teacher_id', $user->id)->pluck('class_arm_id')->unique();
    }

    private function studentForCurrentUser(): Student
    {
        $student = Student::where('user_id', Auth::id())->first();

        abort_unless($student, 403, 'No student profile linked to your account.');

        return $student;
    }

    private function studentCanTakeExam(Student $student, CbtExam $exam): bool
    {
        return (int) $student->tenant_id === (int) $exam->tenant_id
            && $student->status === Student::STATUS_ACTIVE
            && (int) $student->current_class_arm_id === (int) $exam->class_arm_id;
    }

    private function orderedQuestions(array $questionIds): Collection
    {
        $ids = array_values(array_unique(array_map('intval', $questionIds)));

        if (empty($ids)) {
            return collect();
        }

        $questions = CbtQuestion::whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)
            ->map(fn (int $id) => $questions->get($id))
            ->filter()
            ->values();
    }

    private function examQuestionIds(CbtExam $exam): array
    {
        $objectiveTypes = ['mcq', 'true_false', 'fill_blank'];
        $theoryTypes    = ['essay', 'short_answer'];

        $objCount    = (int) ($exam->section_objective_count ?? 0);
        $theoryCount = (int) ($exam->section_theory_count ?? 0);

        // Legacy exams (created before sections) — just draw total_questions randomly.
        if ($objCount === 0 && $theoryCount === 0) {
            return CbtQuestion::where('question_bank_id', $exam->question_bank_id)
                ->inRandomOrder()
                ->limit($exam->total_questions)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $ids = collect();

        // Section A — objective questions
        if ($objCount > 0) {
            $objIds = CbtQuestion::where('question_bank_id', $exam->question_bank_id)
                ->whereIn('type', $objectiveTypes)
                ->inRandomOrder()
                ->limit($objCount)
                ->pluck('id')
                ->map(fn ($id) => (int) $id);
            $ids = $ids->merge($objIds);
        }

        // Section B — theory/essay questions
        if ($theoryCount > 0) {
            $theoryIds = CbtQuestion::where('question_bank_id', $exam->question_bank_id)
                ->whereIn('type', $theoryTypes)
                ->inRandomOrder()
                ->limit($theoryCount)
                ->pluck('id')
                ->map(fn ($id) => (int) $id);
            $ids = $ids->merge($theoryIds);
        }

        return $ids->all();
    }

    private function calculateSessionScore(Collection $questions, array $answers, ?CbtExam $exam = null): array
    {
        $objectiveTypes = ['mcq', 'true_false', 'fill_blank'];
        $theoryTypes    = ['essay', 'short_answer'];

        // Exam-level section marks override per-question marks when set.
        $objMarkEach    = ($exam && $exam->section_objective_count > 0 && $exam->section_objective_marks > 0)
            ? (float) $exam->section_objective_marks : null;
        $theoryMarkEach = ($exam && $exam->section_theory_count > 0 && $exam->section_theory_marks > 0)
            ? (float) $exam->section_theory_marks : null;

        $questionMarks = fn($question) => in_array($question->type ?? 'mcq', $objectiveTypes)
            ? ($objMarkEach    ?? (float) ($question->marks ?? 1))
            : ($theoryMarkEach ?? (float) ($question->marks ?? 1));

        $correct   = 0;
        $autoScore = 0.0;
        $autoTotal = 0;
        $totalMarks = (float) $questions->sum($questionMarks);

        foreach ($questions as $question) {
            if (!$question->isAutoGraded()) {
                continue;
            }
            $autoTotal++;
            $answer = $answers[$question->id] ?? null;
            if ($answer && $question->isCorrect((string) $answer)) {
                $correct++;
                $autoScore += $questionMarks($question);
            }
        }

        return [
            'correct'    => $correct,
            'auto_score' => $autoScore,
            'auto_total' => $autoTotal,
            'total_marks'=> $totalMarks,
            'percentage' => $totalMarks > 0 ? round(($autoScore / $totalMarks) * 100, 1) : 0,
            'has_manual' => $questions->contains(fn ($question) => $question->isManualGraded()),
        ];
    }

    // ── Question Banks ────────────────────────────────────────────────
    public function banks()
    {
        $user        = Auth::user();
        $banksQuery  = CbtQuestionBank::with(['subject', 'classLevel'])->latest();
        if (!$this->hasFullCbtAccess($user)) {
            $banksQuery->whereIn('subject_id', $this->teacherSubjectIds($user));
        }
        $banks       = $banksQuery->get();
        $subjects    = $this->hasFullCbtAccess($user)
            ? Subject::where('is_active', true)->get()
            : Subject::whereIn('id', $this->teacherSubjectIds($user))->where('is_active', true)->get();
        $classLevels = ClassLevel::orderBy('order_index')->get();
        return view('cbt.banks', compact('banks', 'subjects', 'classLevels'));
    }

    public function storeBank(Request $request)
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'subject_id'     => ['required', 'exists:subjects,id'],
            'class_level_id' => ['required', 'exists:class_levels,id'],
            'description'    => ['nullable', 'string'],
        ]);

        $user = Auth::user();
        if (!$this->hasFullCbtAccess($user) && !$this->teacherSubjectIds($user)->contains((int) $validated['subject_id'])) {
            abort(403, 'You can only create question banks for subjects you teach.');
        }

        CbtQuestionBank::create($validated);
        return back()->with('success', 'Question bank created.');
    }

    public function editBank(CbtQuestionBank $bank)
    {
        abort_unless($this->teacherTeachesBank(Auth::user(), $bank), 403, 'You can only manage question banks for subjects you teach.');
        $subjects    = Subject::where('is_active', true)->get();
        $classLevels = ClassLevel::orderBy('order_index')->get();
        return view('cbt.bank-edit', compact('bank', 'subjects', 'classLevels'));
    }

    public function updateBank(Request $request, CbtQuestionBank $bank)
    {
        $user = Auth::user();
        abort_unless($this->teacherTeachesBank($user, $bank), 403, 'You can only manage question banks for subjects you teach.');
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'subject_id'     => ['required', 'exists:subjects,id'],
            'class_level_id' => ['required', 'exists:class_levels,id'],
            'description'    => ['nullable', 'string'],
        ]);
        if (!$this->hasFullCbtAccess($user) && !$this->teacherSubjectIds($user)->contains((int) $validated['subject_id'])) {
            abort(403, 'You can only assign question banks to subjects you teach.');
        }
        $bank->update($validated);
        return redirect()->route('cbt.questions', $bank)->with('success', 'Question bank updated.');
    }

    public function destroyBank(CbtQuestionBank $bank)
    {
        abort_unless($this->teacherTeachesBank(Auth::user(), $bank), 403, 'You can only manage question banks for subjects you teach.');

        // Count linked exams so we can warn in the success message
        $examCount = CbtExam::where('question_bank_id', $bank->id)->count();

        // Delete linked exams and their student sessions first
        // (FK: cbt_exams.question_bank_id → cbt_question_banks.id)
        $examIds = CbtExam::where('question_bank_id', $bank->id)->pluck('id');
        if ($examIds->isNotEmpty()) {
            CbtStudentSession::whereIn('cbt_exam_id', $examIds)->delete();
            CbtExam::whereIn('id', $examIds)->delete();
        }

        // Delete all question images then the questions themselves
        foreach ($bank->questions as $q) {
            if ($q->image_path) Storage::disk('public')->delete($q->image_path);
        }
        $bank->questions()->delete();
        $bank->delete();

        $msg = 'Question bank and all its questions deleted.';
        if ($examCount > 0) {
            $msg .= " {$examCount} linked exam(s) and their student sessions were also removed.";
        }

        return redirect()->route('cbt.banks')->with('success', $msg);
    }

    public function showBank(CbtQuestionBank $bank)
    {
        return redirect()->route('cbt.questions', $bank);
    }

    // ── Questions ─────────────────────────────────────────────────────
    public function questions(CbtQuestionBank $bank)
    {
        abort_unless($this->teacherTeachesBank(Auth::user(), $bank), 403, 'You can only manage question banks for subjects you teach.');
        $questions = CbtQuestion::where('question_bank_id', $bank->id)
                        ->latest()->paginate(25);
        return view('cbt.questions', compact('bank', 'questions'));
    }

    public function storeQuestion(Request $request, CbtQuestionBank $bank)
    {
        abort_unless($this->teacherTeachesBank(Auth::user(), $bank), 403, 'You can only manage question banks for subjects you teach.');
        $type = $request->input('type', 'mcq');
        [$rules, $payload] = $this->questionRulesAndPayload($request, $type, $bank->id);
        $request->validate($rules);

        // Handle image upload
        if ($request->hasFile('image')) {
            $payload['image_path'] = $request->file('image')
                ->store('cbt/questions', 'public');
        }

        CbtQuestion::create($payload);
        return back()->with('success', ucfirst(str_replace('_',' ',$type)) . ' question added.');
    }

    public function editQuestion(CbtQuestion $q)
    {
        $bank = $q->questionBank;
        abort_unless($this->teacherTeachesBank(Auth::user(), $bank), 403, 'You can only manage question banks for subjects you teach.');
        $subjects    = Subject::where('is_active', true)->get();
        $classLevels = ClassLevel::orderBy('order_index')->get();
        return view('cbt.question-edit', compact('q', 'bank', 'subjects', 'classLevels'));
    }

    public function updateQuestion(Request $request, CbtQuestion $q)
    {
        abort_unless($this->teacherTeachesBank(Auth::user(), $q->questionBank), 403, 'You can only manage question banks for subjects you teach.');
        $type = $request->input('type', $q->type ?? 'mcq');
        [$rules, $payload] = $this->questionRulesAndPayload($request, $type, $q->question_bank_id);
        $request->validate($rules);

        // Handle image upload
        if ($request->hasFile('image')) {
            if ($q->image_path) Storage::disk('public')->delete($q->image_path);
            $payload['image_path'] = $request->file('image')
                ->store('cbt/questions', 'public');
        }
        // Remove image
        if ($request->boolean('remove_image') && $q->image_path) {
            Storage::disk('public')->delete($q->image_path);
            $payload['image_path'] = null;
        }

        $q->update($payload);
        return redirect()->route('cbt.questions', $q->question_bank_id)
            ->with('success', 'Question updated successfully.');
    }

    public function destroyQuestion(CbtQuestion $q)
    {
        abort_unless($this->teacherTeachesBank(Auth::user(), $q->questionBank), 403, 'You can only manage question banks for subjects you teach.');
        $bankId = $q->question_bank_id;
        if ($q->image_path) Storage::disk('public')->delete($q->image_path);
        $q->delete();
        return redirect()->route('cbt.questions', $bankId)->with('success', 'Question deleted.');
    }

    /**
     * Reshuffle (randomise order) of questions in a bank
     * by regenerating their sort_order / created_at offsets.
     */
    public function reshuffleBank(CbtQuestionBank $bank)
    {
        abort_unless($this->teacherTeachesBank(Auth::user(), $bank), 403, 'You can only manage question banks for subjects you teach.');
        $ids = $bank->questions()->pluck('id')->shuffle();
        foreach ($ids as $i => $id) {
            CbtQuestion::where('id', $id)->update([
                'created_at' => now()->subSeconds($ids->count() - $i),
            ]);
        }
        return redirect()->route('cbt.questions', $bank)
            ->with('success', 'Questions reshuffled successfully.');
    }

    // ── Shared validation + payload builder ───────────────────────────
    private function questionRulesAndPayload(Request $request, string $type, int $bankId): array
    {
        $rules = [
            'type'          => ['required', 'in:mcq,essay,short_answer,fill_blank,true_false'],
            'question_text' => ['required', 'string'],
            'difficulty'    => ['nullable', 'integer', 'min:1', 'max:3'],
            'marks'         => ['nullable', 'numeric', 'min:0.5'],
            'explanation'   => ['nullable', 'string'],
            'image'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp,svg', 'max:4096'],
        ];

        if ($type === 'mcq') {
            $rules['option_a']             = ['required', 'string'];
            $rules['option_b']             = ['required', 'string'];
            $rules['option_c']             = ['nullable', 'string'];
            $rules['option_d']             = ['nullable', 'string'];
            $rules['correct_answer_letter']= ['required', 'in:a,b,c,d'];
        } elseif ($type === 'true_false') {
            $rules['correct_answer_letter']= ['required', 'in:a,b'];
        } elseif ($type === 'essay') {
            $rules['model_answer'] = ['nullable', 'string'];
            $rules['word_limit']   = ['nullable', 'integer', 'min:10'];
        } elseif (in_array($type, ['short_answer', 'fill_blank'])) {
            $rules['model_answer'] = ['nullable', 'string'];
        }

        $v = $request->all();
        $payload = [
            'question_bank_id'     => $bankId,
            'type'                 => $type,
            'question_text'        => $v['question_text'],
            'difficulty'           => $v['difficulty'] ?? 1,
            'marks'                => $v['marks'] ?? 1,
            'explanation'          => $v['explanation'] ?? null,
            'option_a'             => $v['option_a'] ?? ($type === 'true_false' ? 'True'  : null),
            'option_b'             => $v['option_b'] ?? ($type === 'true_false' ? 'False' : null),
            'option_c'             => $v['option_c'] ?? null,
            'option_d'             => $v['option_d'] ?? null,
            'correct_answer_letter'=> $v['correct_answer_letter'] ?? null,
            'correct_option'       => null,     // never set legacy tinyint
            'model_answer'         => $v['model_answer'] ?? null,
            'word_limit'           => isset($v['word_limit']) ? (int)$v['word_limit'] : null,
        ];

        return [$rules, $payload];
    }

    // ── Exams ─────────────────────────────────────────────────────────
    public function exams()
    {
        $user = Auth::user();
        $examsQuery = CbtExam::with(['questionBank', 'classArm.classLevel', 'term'])->latest();
        $banksQuery = CbtQuestionBank::with(['subject', 'classLevel']);
        $classArmsQuery = ClassArm::with('classLevel');

        if (!$this->hasFullCbtAccess($user)) {
            $subjectIds = $this->teacherSubjectIds($user);
            $banksQuery->whereIn('subject_id', $subjectIds);
            $myBankIds = (clone $banksQuery)->pluck('id');
            $examsQuery->whereIn('question_bank_id', $myBankIds);
            $classArmsQuery->whereIn('id', $this->teacherClassArmIds($user));
        }

        $exams     = $examsQuery->get();
        $banks     = $banksQuery->get();
        $classArms = $classArmsQuery->get();
        $classLevels = $this->hasFullCbtAccess($user)
            ? ClassLevel::orderBy('order_index')->get()
            : ClassLevel::whereIn('id', $classArms->pluck('class_level_id')->unique())->orderBy('order_index')->get();
        $terms     = Term::with('session')->latest()->get();
        $assessmentTypes = \App\Models\AssessmentType::orderBy('term_id')->orderBy('name')->get();
        return view('cbt.exams', compact('exams', 'banks', 'classArms', 'classLevels', 'terms', 'assessmentTypes'));
    }

    public function storeExam(Request $request)
    {
        $validated = $request->validate([
            'title'                   => ['required', 'string', 'max:150'],
            'question_bank_id'        => ['required', 'exists:cbt_question_banks,id'],
            'target'                  => ['required', 'string'],
            'term_id'                 => ['required', 'exists:terms,id'],
            'duration_minutes'        => ['required', 'integer', 'min:5'],
            'scheduled_start'         => ['nullable', 'date'],
            'scheduled_end'           => ['nullable', 'date', 'after:scheduled_start'],
            // Section A — Objective questions (MCQ, True/False, Fill-in-Blank)
            'section_objective_count' => ['nullable', 'integer', 'min:0'],
            'section_objective_marks' => ['nullable', 'numeric', 'min:0.25'],
            // Section B — Theory / Short Answer / Essay
            'section_theory_count'    => ['nullable', 'integer', 'min:0'],
            'section_theory_marks'    => ['nullable', 'numeric', 'min:0.25'],
            // Feeds the objective score into this report-card assessment
            // type on the score entry sheet (optional).
            'assessment_type_id'      => ['nullable', 'exists:assessment_types,id'],
        ]);

        $objCount    = (int)   ($validated['section_objective_count'] ?? 0);
        $objMarks    = (float) ($validated['section_objective_marks'] ?? 1.0);
        $theoryCount = (int)   ($validated['section_theory_count']    ?? 0);
        $theoryMarks = (float) ($validated['section_theory_marks']    ?? 5.0);

        if ($objCount === 0 && $theoryCount === 0) {
            return back()->withErrors(['section_objective_count' => 'Please set at least one objective or theory question for this exam.']);
        }

        $user = Auth::user();
        $bank = CbtQuestionBank::findOrFail($validated['question_bank_id']);
        abort_unless($this->teacherTeachesBank($user, $bank), 403, 'You can only create exams for subjects you teach.');

        // Validate objective stock
        if ($objCount > 0) {
            $availObj = CbtQuestion::where('question_bank_id', $validated['question_bank_id'])
                ->whereIn('type', ['mcq', 'true_false', 'fill_blank'])->count();
            if ($objCount > $availObj) {
                return back()->withErrors(['section_objective_count' => "Only {$availObj} objective question(s) in this bank. Reduce Section A count."]);
            }
        }

        // Validate theory stock
        if ($theoryCount > 0) {
            $availTheory = CbtQuestion::where('question_bank_id', $validated['question_bank_id'])
                ->whereIn('type', ['essay', 'short_answer'])->count();
            if ($theoryCount > $availTheory) {
                return back()->withErrors(['section_theory_count' => "Only {$availTheory} theory question(s) in this bank. Reduce Section B count."]);
            }
        }

        $totalQuestions = $objCount + $theoryCount;
        $totalMarks     = round(($objCount * $objMarks) + ($theoryCount * $theoryMarks), 2);

        [$type, $id] = array_pad(explode(':', $validated['target'], 2), 2, null);

        if ($type === 'level') {
            $arms = ClassArm::with('classLevel')->where('class_level_id', $id)->get();
            if ($arms->isEmpty()) {
                return back()->withErrors(['target' => 'No class arms exist under that class level yet.']);
            }
        } else { // arm
            $arm = ClassArm::with('classLevel')->find($id);
            if (!$arm) {
                return back()->withErrors(['target' => 'Selected class not found.']);
            }
            $arms = collect([$arm]);
        }

        // If scoped, only allow arms this teacher actually teaches.
        if (!$this->hasFullCbtAccess($user)) {
            $myArmIds = $this->teacherClassArmIds($user);
            $arms = $arms->filter(fn ($a) => $myArmIds->contains($a->id))->values();
            if ($arms->isEmpty()) {
                return back()->withErrors(['target' => 'You can only assign exams to classes you teach.']);
            }
        }

        $created = 0;
        foreach ($arms as $arm) {
            CbtExam::create([
                'title'                   => $arms->count() > 1 ? "{$validated['title']} — " . (optional($arm->classLevel)->name ?? '') . " {$arm->name}" : $validated['title'],
                'question_bank_id'        => $validated['question_bank_id'],
                'class_arm_id'            => $arm->id,
                'term_id'                 => $validated['term_id'],
                'assessment_type_id'      => $validated['assessment_type_id'] ?? null,
                'total_questions'         => $totalQuestions,
                'total_marks'             => $totalMarks,
                'section_objective_count' => $objCount,
                'section_objective_marks' => $objMarks,
                'section_theory_count'    => $theoryCount,
                'section_theory_marks'    => $theoryMarks,
                'duration_minutes'        => $validated['duration_minutes'],
                'scheduled_start'         => $validated['scheduled_start'] ?? null,
                'scheduled_end'           => $validated['scheduled_end'] ?? null,
                'shuffle_questions'       => true,
                'shuffle_options'         => true,
                'status'                  => 'draft',
            ]);
            $created++;
        }

        $msg = $created === 1 ? 'Exam created successfully.' : "{$created} exams created — one per class arm.";
        return back()->with('success', $msg);
    }

    public function publishExam(CbtExam $exam)
    {
        abort_unless($this->teacherTeachesBank(Auth::user(), $exam->questionBank), 403, 'You can only manage exams for subjects you teach.');
        $exam->update(['status' => 'published']);
        return back()->with('success', 'Exam published. Students can now access it.');
    }

    public function closeExam(CbtExam $exam)
    {
        abort_unless($this->teacherTeachesBank(Auth::user(), $exam->questionBank), 403, 'You can only manage exams for subjects you teach.');
        $exam->update(['status' => 'closed']);
        return back()->with('success', 'Exam closed. No more submissions allowed.');
    }

    // ── Results ───────────────────────────────────────────────────────
    public function results(?CbtExam $exam = null)
    {
        $this->authorizeCbtStaffAccess();
        $user = Auth::user();

        $examsQuery = CbtExam::with(['questionBank.subject', 'classArm.classLevel', 'term'])->latest();
        if (!$this->hasFullCbtAccess($user)) {
            $myBankIds = CbtQuestionBank::whereIn('subject_id', $this->teacherSubjectIds($user))->pluck('id');
            $examsQuery->whereIn('question_bank_id', $myBankIds);
        }
        $exams = $examsQuery->get();

        if ($exam && !$this->hasFullCbtAccess($user) && !$this->teacherTeachesBank($user, $exam->questionBank)) {
            abort(403, 'You can only view results for exams in subjects you teach.');
        }

        $exam ??= $exams->first();
        $sessions = collect();
        $stats = [
            'total' => 0,
            'submitted' => 0,
            'avg_score' => 0,
            'highest' => 0,
            'lowest' => 0,
        ];

        if ($exam) {
            $exam->loadMissing(['questionBank.subject', 'classArm.classLevel', 'term']);

            $sessions = CbtStudentSession::with(['student', 'exam.questionBank.subject'])
                ->where('cbt_exam_id', $exam->id)
                ->latest()
                ->get();

            $percentages = $sessions
                ->map(fn (CbtStudentSession $session) => $session->display_percentage)
                ->filter(fn ($percentage) => $percentage !== null)
                ->values();

            $stats = [
                'total' => $sessions->count(),
                'submitted' => $sessions->filter(fn (CbtStudentSession $session) => $session->isFinal())->count(),
                'avg_score' => $percentages->count() ? round($percentages->avg(), 1) : 0,
                'highest' => $percentages->count() ? round($percentages->max(), 1) : 0,
                'lowest' => $percentages->count() ? round($percentages->min(), 1) : 0,
            ];
        }

        return view('cbt.results', compact('exam', 'exams', 'sessions', 'stats'));
    }

    // ── Start Exam ────────────────────────────────────────────────────
    public function startExam(CbtExam $exam)
    {
        $user = Auth::user();

        if ($user->isStudent()) {
            $student = $this->studentForCurrentUser();

            abort_unless($this->studentCanTakeExam($student, $exam), 403, 'You are not allowed to access this exam.');

            if ($exam->status !== 'published') {
                return redirect()->route('student.portal.exams')
                    ->with('info', 'This exam is not currently available.');
            }

            $existing = CbtStudentSession::where('cbt_exam_id', $exam->id)
                ->where('student_id', $student->id)->first();

            if ($existing && $existing->isFinal()) {
                return redirect()->route('student.portal.exams')
                    ->with('info', 'You have already submitted this exam.');
            }

            if (!$existing) {
                $questionIds = $this->examQuestionIds($exam);

                if (empty($questionIds)) {
                    return redirect()->route('student.portal.exams')
                        ->withErrors(['error' => 'This exam has no available questions yet.']);
                }

                $existing = CbtStudentSession::create([
                    'tenant_id'      => $student->tenant_id,
                    'cbt_exam_id'    => $exam->id,
                    'student_id'     => $student->id,
                    'question_order' => $questionIds,
                    'answers'        => [],
                    'essay_answers'  => [],
                    'started_at'     => now(),
                    'status'         => 'in_progress',
                ]);
            }

            $questions = $this->orderedQuestions($existing->questionIds());
        } else {
            $this->authorizeCbtStaffAccess();

            $questions = $this->orderedQuestions($this->examQuestionIds($exam));
            $existing = null;
        }

        return view('cbt.take', compact('exam', 'questions', 'existing'));
    }

    // ── Submit Exam ───────────────────────────────────────────────────
    public function submitExam(Request $request, CbtStudentSession $session)
    {
        $user = Auth::user();

        abort_unless($user->isStudent(), 403, 'Only students can submit CBT sessions.');

        $student = $this->studentForCurrentUser();

        abort_unless((int) $session->student_id === (int) $student->id, 403, 'You are not allowed to submit this session.');

        if ($session->isFinal()) {
            return redirect()->route('student.portal.exams')
                ->with('info', 'This exam has already been submitted.');
        }

        if (!$session->isInProgress()) {
            return redirect()->route('student.portal.exams')
                ->with('info', 'This exam session is no longer active.');
        }

        $answers      = (array) $request->input('answers', []);
        $essayAnswers = (array) $request->input('essay_answers', []);
        $questions    = $session->resolvedQuestions();

        if ($questions->isEmpty()) {
            return redirect()->route('student.portal.exams')
                ->withErrors(['error' => 'This exam session has no questions to submit.']);
        }

        $score = $this->calculateSessionScore($questions, $answers, $session->exam);
        $newStatus = $score['has_manual'] ? 'submitted' : 'graded';

        $session->update([
            'answers'       => $answers,
            'essay_answers' => $essayAnswers,
            'score'         => $score['auto_score'],
            'percentage'    => $score['percentage'],
            'submitted_at'  => now(),
            'status'        => $newStatus,
        ]);

        $msg = $score['has_manual']
            ? "Exam submitted! Auto score: {$score['correct']}/{$score['auto_total']}. Essays await manual marking."
            : "Exam submitted! Score: {$score['auto_score']}/{$score['total_marks']} ({$score['percentage']}%)";

        return redirect()->route('student.portal.dashboard')->with('success', $msg);
    }

    // ── Bulk Upload ───────────────────────────────────────────────────
    public function bulkUploadPage(CbtQuestionBank $bank)
    {
        abort_unless($this->teacherTeachesBank(Auth::user(), $bank), 403, 'You can only manage question banks for subjects you teach.');
        return view('cbt.bulk-upload', compact('bank'));
    }

    public function bulkUploadTemplate()
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="cbt_questions_template.csv"',
        ];
        $rows = [
            ['type','question_text','option_a','option_b','option_c','option_d','correct_option','explanation','difficulty','marks','model_answer'],
            ['mcq','What is the capital of Nigeria?','Lagos','Abuja','Kano','Ibadan','b','Abuja became capital in 1991','1','1',''],
            ['true_false','The sun rises from the west','True','False','','','b','The sun rises from the east','1','1',''],
            ['essay','Explain the process of photosynthesis in plants.','','','','','','','2','5','Photosynthesis is the process...'],
            ['short_answer','Name the longest river in Africa','','','','','','','1','2','River Nile'],
            ['fill_blank','The process of water turning to vapour is called _____.','','','','','evaporation','','1','1',''],
        ];
        $cb = function() use ($rows) {
            $h = fopen('php://output','w');
            foreach ($rows as $r) fputcsv($h, $r);
            fclose($h);
        };
        return response()->stream($cb, 200, $headers);
    }

    public function bulkImport(Request $request, CbtQuestionBank $bank)
    {
        abort_unless($this->teacherTeachesBank(Auth::user(), $bank), 403, 'You can only manage question banks for subjects you teach.');
        $request->validate(['file' => ['required','file','mimes:csv,txt','max:5120']]);
        $path = $request->file('file')->getRealPath();
        $rows = []; $headers = null;
        if (($h = fopen($path,'r')) !== false) {
            while (($d = fgetcsv($h)) !== false) {
                if (!$headers) { $headers = array_map('strtolower', array_map('trim', $d)); continue; }
                if (count($d) >= 2) $rows[] = array_combine($headers, array_pad($d, count($headers), ''));
            }
            fclose($h);
        }

        $validTypes = ['mcq','essay','short_answer','fill_blank','true_false'];
        $imported = 0; $skipped = 0; $errors = [];

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;
            $text   = trim($row['question_text'] ?? $row['question'] ?? '');
            if (!$text) { $errors[] = "Row {$rowNum}: question_text required — skipped."; $skipped++; continue; }

            $type = strtolower(trim($row['type'] ?? 'mcq'));
            if (!in_array($type, $validTypes)) $type = 'mcq';

            $correctLetter = strtolower(trim($row['correct_option'] ?? ''));
            if (!in_array($correctLetter, ['a','b','c','d'])) $correctLetter = null;

            if ($type === 'mcq' && !$correctLetter) {
                $errors[] = "Row {$rowNum}: MCQ missing correct_option — defaulted to 'a'.";
                $correctLetter = 'a';
            }
            if ($type === 'true_false' && !$correctLetter) {
                $errors[] = "Row {$rowNum}: true_false missing correct_option (a=True, b=False) — defaulted to 'a'.";
                $correctLetter = 'a';
            }

            try {
                CbtQuestion::create([
                    'question_bank_id'     => $bank->id,
                    'type'                 => $type,
                    'question_text'        => $text,
                    'option_a'             => trim($row['option_a'] ?? '') ?: ($type === 'true_false' ? 'True'  : null),
                    'option_b'             => trim($row['option_b'] ?? '') ?: ($type === 'true_false' ? 'False' : null),
                    'option_c'             => trim($row['option_c'] ?? '') ?: null,
                    'option_d'             => trim($row['option_d'] ?? '') ?: null,
                    'correct_answer_letter'=> $correctLetter,
                    'correct_option'       => null,
                    'explanation'          => trim($row['explanation'] ?? '') ?: null,
                    'difficulty'           => max(1, min(3, (int)($row['difficulty'] ?? 1))),
                    'marks'                => max(0.5, (float)($row['marks'] ?? 1)),
                    'model_answer'         => trim($row['model_answer'] ?? '') ?: null,
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row {$rowNum}: " . $e->getMessage(); $skipped++;
            }
        }

        return back()
            ->with('success', "{$imported} question(s) imported into '{$bank->name}'." . ($skipped ? " {$skipped} skipped." : ''))
            ->with('errors_list', $errors);
    }

    // ── Essay Grading ─────────────────────────────────────────────────
    public function gradeEssay(Request $request, CbtStudentSession $session)
    {
        $this->authorizeCbtStaffAccess();
        $user = Auth::user();
        $exam = $session->exam ?? CbtExam::find($session->cbt_exam_id);
        if ($exam && !$this->hasFullCbtAccess($user) && !$this->teacherTeachesBank($user, $exam->questionBank)) {
            abort(403, 'You can only grade essays for subjects you teach.');
        }

        if (!in_array($session->status, ['submitted', 'graded'], true)) {
            return back()->with('info', 'Only submitted CBT sessions can be graded.');
        }

        $data = $request->validate([
            'manual_scores'   => ['required','array'],
            'manual_scores.*' => ['numeric','min:0'],
        ]);
        $autoScore   = $session->score ?? 0;
        $manualTotal = array_sum($data['manual_scores']);
        $allQ        = $session->resolvedQuestions();
        $totalMax    = $allQ->sum(fn($q) => $q->marks ?? 1);
        $totalScore  = $autoScore + $manualTotal;
        $percentage  = $totalMax > 0 ? round(($totalScore / $totalMax) * 100, 1) : 0;

        $session->update([
            'manual_scores' => $data['manual_scores'],
            'marked_by'     => auth()->id(),
            'score'         => $totalScore,
            'percentage'    => $percentage,
            'status'        => 'graded',
        ]);
        return back()->with('success', "Essay graded. Total: {$totalScore}/{$totalMax} ({$percentage}%)");
    }
}
