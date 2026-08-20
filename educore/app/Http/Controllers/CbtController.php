<?php

namespace App\Http\Controllers;

use App\Models\CbtExam;
use App\Models\CbtImportBatch;
use App\Models\CbtIntegrityEvent;
use App\Models\CbtQuestion;
use App\Models\CbtQuestionBank;
use App\Models\CbtStudentSession;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Services\Cbt\CbtExamConfigurationService;
use App\Services\Cbt\CbtQuestionImportService;
use App\Services\Cbt\CbtQuestionNumberingService;
use App\Services\Cbt\CbtSubmissionService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        return app(CbtExamConfigurationService::class)->questionIdsForAttempt($exam);
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
        $banksQuery  = CbtQuestionBank::with(['subject', 'classLevel'])
            ->withCount(['questions', 'exams'])
            ->latest();
        if (!$this->hasFullCbtAccess($user)) {
            $banksQuery->whereIn('subject_id', $this->teacherSubjectIds($user));
        }
        $banks       = $banksQuery->get();
        return view('cbt.banks', compact('banks'));
    }

    public function createBank()
    {
        $user = Auth::user();
        $subjects    = $this->hasFullCbtAccess($user)
            ? Subject::where('is_active', true)->get()
            : Subject::whereIn('id', $this->teacherSubjectIds($user))->where('is_active', true)->get();
        $classLevels = ClassLevel::orderBy('order_index')->get();
        return view('cbt.bank-create', compact('subjects', 'classLevels'));
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

        $bank = CbtQuestionBank::create($validated);
        return redirect()->route('cbt.questions', $bank)->with('success', 'Question bank created. Add its first question.');
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
        $numbered = app(CbtQuestionNumberingService::class)->number(
            CbtQuestion::where('question_bank_id', $bank->id)
                ->with('parent')
                ->orderBy('sequence')
                ->orderBy('id')
                ->get()
        );
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 50;
        $questions = new LengthAwarePaginator(
            $numbered->forPage($page, $perPage)->values(),
            $numbered->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
        $parentQuestions = $numbered;
        return view('cbt.questions', compact('bank', 'questions', 'parentQuestions'));
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
        $parentQuestions = app(CbtQuestionNumberingService::class)->number(
            $bank->questions()->where('id', '!=', $q->id)->orderBy('sequence')->orderBy('id')->get()
        );
        return view('cbt.question-edit', compact('q', 'bank', 'subjects', 'classLevels', 'parentQuestions'));
    }

    public function updateQuestion(Request $request, CbtQuestion $q)
    {
        abort_unless($this->teacherTeachesBank(Auth::user(), $q->questionBank), 403, 'You can only manage question banks for subjects you teach.');
        $type = $request->input('type', $q->type ?? 'mcq');
        [$rules, $payload] = $this->questionRulesAndPayload($request, $type, $q->question_bank_id, $q);
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
        DB::transaction(function () use ($q) {
            $remove = function (CbtQuestion $question) use (&$remove) {
                foreach ($question->children()->get() as $child) $remove($child);
                if ($question->image_path) Storage::disk('public')->delete($question->image_path);
                $question->delete();
            };
            $remove($q);
        });
        return redirect()->route('cbt.questions', $bankId)->with('success', 'Question branch deleted.');
    }

    public function duplicateQuestionBranch(CbtQuestionBank $bank, CbtQuestion $q)
    {
        abort_unless((int) $q->question_bank_id === (int) $bank->id, 404);
        abort_unless($this->teacherTeachesBank(Auth::user(), $bank), 403, 'You can only manage question banks for subjects you teach.');

        DB::transaction(function () use ($q, $bank) {
            $clone = function (CbtQuestion $source, ?int $parentId) use (&$clone, $bank) {
                $copy = $source->replicate();
                $copy->parent_question_id = $parentId;
                $copy->sequence = $bank->questions()->where('parent_question_id', $parentId)->max('sequence') + 1;
                $copy->reference_code = $source->reference_code ? $source->reference_code.'-copy' : null;
                $copy->save();
                foreach ($source->children()->orderBy('sequence')->get() as $child) $clone($child, $copy->id);
                return $copy;
            };
            $clone($q, $q->parent_question_id);
        });

        return back()->with('success', 'Question branch duplicated.');
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
    private function questionRulesAndPayload(Request $request, string $type, int $bankId, ?CbtQuestion $existing = null): array
    {
        $instructionOnly = $request->boolean('is_instruction_only');
        $rules = [
            'type'          => ['required', 'in:mcq,essay,short_answer,fill_blank,true_false'],
            'question_text' => ['required', 'string'],
            'marks'         => ['nullable', 'numeric', 'min:0'],
            'image'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp,svg', 'max:4096'],
            'parent_question_id' => [
                'nullable',
                Rule::exists('cbt_questions', 'id')->where('question_bank_id', $bankId),
                Rule::notIn(array_filter([$existing?->id])),
            ],
            'numbering_style' => ['nullable', 'in:auto,decimal,lower_alpha,upper_alpha,lower_roman,upper_roman'],
            'reference_code' => ['nullable', 'string', 'max:80'],
            'scoring_method' => ['nullable', 'in:automatic,manual,mixed'],
            'is_instruction_only' => ['nullable', 'boolean'],
            'requires_answer' => ['nullable', 'boolean'],
        ];

        if (!$instructionOnly && $type === 'mcq') {
            $rules['option_a']             = ['required', 'string'];
            $rules['option_b']             = ['required', 'string'];
            $rules['option_c']             = ['nullable', 'string'];
            $rules['option_d']             = ['nullable', 'string'];
            $rules['correct_answer_letter']= ['required', 'in:a,b,c,d'];
        } elseif (!$instructionOnly && $type === 'true_false') {
            $rules['correct_answer_letter']= ['required', 'in:a,b'];
        } elseif (!$instructionOnly && $type === 'essay') {
            $rules['model_answer'] = ['nullable', 'string'];
            $rules['word_limit']   = ['nullable', 'integer', 'min:10'];
        } elseif (!$instructionOnly && in_array($type, ['short_answer', 'fill_blank'])) {
            $rules['model_answer'] = ['nullable', 'string'];
        }

        $v = $request->all();
        $parentId = array_key_exists('parent_question_id', $v)
            ? ($v['parent_question_id'] ?: null)
            : $existing?->parent_question_id;
        $parent = $parentId
            ? CbtQuestion::where('question_bank_id', $bankId)->findOrFail($parentId)
            : null;

        if ($existing && $parent && $this->questionIsWithinBranch($parent, $existing)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'parent_question_id' => 'A question cannot be moved below one of its own sub-questions.',
            ]);
        }

        $sameParent = $existing && (int) ($existing->parent_question_id ?: 0) === (int) ($parentId ?: 0);
        $sequence = $sameParent
            ? $existing->sequence
            : CbtQuestion::where('question_bank_id', $bankId)
                ->where('parent_question_id', $parentId)
                ->max('sequence') + 1;
        $requiresAnswer = !$instructionOnly && (
            $request->has('requires_answer') ? $request->boolean('requires_answer') : true
        );
        $marks = $instructionOnly ? 0 : (float) ($v['marks'] ?? $existing?->marks ?? 1);
        if ($requiresAnswer && $marks <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'marks' => 'An answerable question must carry more than zero marks.',
            ]);
        }

        $payload = [
            'question_bank_id'     => $bankId,
            'parent_question_id'   => $parentId,
            'level'                => $parent ? $parent->level + 1 : 0,
            'sequence'             => $sequence,
            'numbering_style'      => $v['numbering_style'] ?? $existing?->numbering_style ?? 'auto',
            'reference_code'       => $v['reference_code'] ?? $existing?->reference_code,
            'is_instruction_only'  => $instructionOnly,
            'requires_answer'      => $requiresAnswer,
            'scoring_method'       => $instructionOnly
                ? 'manual'
                : ($v['scoring_method'] ?? ($type === 'mcq' || $type === 'true_false' || $type === 'fill_blank' ? 'automatic' : 'manual')),
            'type'                 => $type,
            'question_text'        => $v['question_text'],
            'marks'                => $marks,
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

    private function questionIsWithinBranch(CbtQuestion $candidate, CbtQuestion $branchRoot): bool
    {
        $current = $candidate;
        while ($current) {
            if ((int) $current->id === (int) $branchRoot->id) return true;
            $current = $current->parent;
        }
        return false;
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
            // Receives the weighted aggregate of all completed CBT sections.
            'assessment_type_id'      => ['nullable', 'exists:assessment_types,id'],
            'malpractice_enabled'     => ['nullable', 'boolean'],
            'focus_loss_policy'       => ['nullable', 'in:submit,warn,log'],
            'max_focus_losses'        => ['nullable', 'integer', 'min:0', 'max:20'],
            'require_fullscreen'      => ['nullable', 'boolean'],
        ]);

        $user = Auth::user();
        $bank = CbtQuestionBank::findOrFail($validated['question_bank_id']);
        abort_unless($this->teacherTeachesBank($user, $bank), 403, 'You can only create exams for subjects you teach.');

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
            $exam = CbtExam::create([
                'title'                   => $arms->count() > 1 ? "{$validated['title']} — " . (optional($arm->classLevel)->name ?? '') . " {$arm->name}" : $validated['title'],
                'question_bank_id'        => $validated['question_bank_id'],
                'class_arm_id'            => $arm->id,
                'term_id'                 => $validated['term_id'],
                'assessment_type_id'      => $validated['assessment_type_id'] ?? null,
                'total_questions'         => 0,
                'total_marks'             => 0,
                // Retained only as a compatibility bridge for pre-upgrade records.
                'section_objective_count' => 0,
                'section_objective_marks' => 1,
                'section_theory_count'    => 0,
                'section_theory_marks'    => 1,
                'duration_minutes'        => $validated['duration_minutes'],
                'scheduled_start'         => $validated['scheduled_start'] ?? null,
                'scheduled_end'           => $validated['scheduled_end'] ?? null,
                'shuffle_questions'       => true,
                'shuffle_options'         => true,
                'status'                  => 'draft',
                'created_by'              => auth()->id(),
                'malpractice_enabled'     => $request->boolean('malpractice_enabled', true),
                'focus_loss_policy'       => $validated['focus_loss_policy'] ?? 'submit',
                'max_focus_losses'        => $validated['max_focus_losses'] ?? 0,
                'require_fullscreen'      => $request->boolean('require_fullscreen'),
                'retake_policy'           => 'latest_valid_authorized_attempt',
                'strict_marks_validation' => true,
            ]);
            $created++;
        }

        $msg = $created === 1
            ? 'Exam created. Add the required sections and questions in the dynamic builder.'
            : "{$created} exam drafts created — one per class arm. Open each draft to configure its sections.";
        return $created === 1
            ? redirect()->route('cbt.exams.builder', $exam)->with('success', $msg.' Review sections and marks before publication.')
            : back()->with('success', $msg);
    }

    public function publishExam(CbtExam $exam)
    {
        abort_unless($this->teacherTeachesBank(Auth::user(), $exam->questionBank), 403, 'You can only manage exams for subjects you teach.');
        abort_unless($exam->status === 'draft', 422, 'Only draft exams can be published.');
        $validationErrors = app(CbtExamConfigurationService::class)->publicationErrors($exam);
        if ($validationErrors) return back()->withErrors(['publish' => implode(' ', $validationErrors)]);
        $exam->update(['status' => 'published']);
        \App\Models\AuditLog::create(['tenant_id' => $exam->tenant_id, 'actor_user_id' => auth()->id(), 'auditable_type' => CbtExam::class, 'auditable_id' => $exam->id, 'action' => 'cbt.exam.published', 'new_values' => ['status' => 'published']]);
        return back()->with('success', 'Exam published. Students can now access it.');
    }

    public function closeExam(CbtExam $exam)
    {
        abort_unless($this->teacherTeachesBank(Auth::user(), $exam->questionBank), 403, 'You can only manage exams for subjects you teach.');
        abort_unless(in_array($exam->status, ['published', 'active'], true), 422, 'Only a published exam can be closed.');
        $exam->update(['status' => 'closed']);
        \App\Models\AuditLog::create(['tenant_id' => $exam->tenant_id, 'actor_user_id' => auth()->id(), 'auditable_type' => CbtExam::class, 'auditable_id' => $exam->id, 'action' => 'cbt.exam.closed', 'new_values' => ['status' => 'closed']]);
        CbtStudentSession::where('cbt_exam_id', $exam->id)->whereNotNull('grading_completed_at')
            ->get()->each(fn (CbtStudentSession $attempt) => app(\App\Services\Cbt\CbtResultSyncService::class)->sync($attempt));
        return back()->with('success', 'Exam closed. No more submissions allowed.');
    }

    public function updateSecurity(Request $request, CbtExam $exam)
    {
        abort_unless($this->teacherTeachesBank(Auth::user(), $exam->questionBank), 403);
        abort_unless($exam->status === 'draft', 422, 'Security settings are locked after publication.');
        $data = $request->validate([
            'focus_loss_policy' => ['required', 'in:submit,warn,log'],
            'max_focus_losses' => ['required', 'integer', 'min:0', 'max:20'],
            'malpractice_enabled' => ['nullable', 'boolean'], 'require_fullscreen' => ['nullable', 'boolean'],
        ]);
        $exam->update($data + ['malpractice_enabled' => $request->boolean('malpractice_enabled'), 'require_fullscreen' => $request->boolean('require_fullscreen')]);
        \App\Models\AuditLog::create(['tenant_id' => $exam->tenant_id, 'actor_user_id' => auth()->id(), 'auditable_type' => CbtExam::class, 'auditable_id' => $exam->id, 'action' => 'cbt.exam.security_updated', 'new_values' => $exam->only(['malpractice_enabled', 'focus_loss_policy', 'max_focus_losses', 'require_fullscreen'])]);
        return back()->with('success', 'Exam security settings updated.');
    }

    // ── Results ───────────────────────────────────────────────────────
    public function results(Request $request, ?CbtExam $exam = null)
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
            $exam->loadMissing(['questionBank.subject', 'classArm.classLevel', 'term', 'sections']);

            $sessions = CbtStudentSession::with(['student', 'exam.questionBank.subject', 'sectionAttempts.section', 'questionScores.question', 'integrityEvents', 'retakeAuthorization'])
                ->where('cbt_exam_id', $exam->id)
                ->when($request->filled('student'), fn ($q) => $q->whereHas('student', fn ($student) => $student->where(fn ($name) => $name->where('first_name', 'like', '%'.$request->student.'%')->orWhere('last_name', 'like', '%'.$request->student.'%')->orWhere('admission_number', 'like', '%'.$request->student.'%'))))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
                ->when($request->filled('attempt_number'), fn ($q) => $q->where('attempt_number', $request->integer('attempt_number')))
                ->latest()->get();

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

            $existing = CbtStudentSession::where('cbt_exam_id', $exam->id)->where('student_id', $student->id)->where('status', 'in_progress')->latest('attempt_number')->first();
            if (! $existing || ! $existing->integrity_acknowledged_at) {
                $hasPriorAttempt = CbtStudentSession::where('cbt_exam_id', $exam->id)->where('student_id', $student->id)->whereIn('status', CbtStudentSession::FINAL_STATUSES)->exists();
                $retake = $exam->retakeAuthorizations()->where('student_id', $student->id)->whereNull('used_at')->whereNull('revoked_at')->latest()->first();
                if ($hasPriorAttempt && ! $retake) return redirect()->route('student.portal.exams')->with('info', 'You have already completed this exam. A school administrator must authorize any retake.');
                return view('cbt.acknowledge', compact('exam', 'existing', 'retake'));
            }
            $questions = $this->orderedQuestions($existing->questionIds());
        } else {
            $this->authorizeCbtStaffAccess();

            $questions = $this->orderedQuestions($this->examQuestionIds($exam));
            $existing = null;
        }
        $sectionPayload = app(CbtExamConfigurationService::class)->sectionPayload($exam, $questions);
        return view('cbt.take', compact('exam', 'questions', 'existing', 'sectionPayload'));
    }

    public function beginExam(Request $request, CbtExam $exam)
    {
        abort_unless(Auth::user()?->isStudent(), 403);
        $request->validate(['integrity_acknowledged' => ['accepted']]);
        $student = $this->studentForCurrentUser();
        abort_unless($this->studentCanTakeExam($student, $exam) && $exam->status === 'published', 403);
        if ($exam->scheduled_start && now()->lt($exam->scheduled_start)) return back()->withErrors(['exam' => 'This exam has not started yet.']);
        if ($exam->scheduled_end && now()->gte($exam->scheduled_end)) return back()->withErrors(['exam' => 'This exam window has closed.']);

        $session = DB::transaction(function () use ($exam, $student) {
            $active = CbtStudentSession::where('cbt_exam_id', $exam->id)->where('student_id', $student->id)->where('status', 'in_progress')->lockForUpdate()->first();
            if ($active) {
                $active->update(['integrity_acknowledged_at' => now(), 'started_at' => $active->started_at ?: now()]);
                return $active;
            }
            $lastAttempt = (int) CbtStudentSession::where('cbt_exam_id', $exam->id)->where('student_id', $student->id)->lockForUpdate()->max('attempt_number');
            $authorization = null;
            if ($lastAttempt > 0) {
                $authorization = $exam->retakeAuthorizations()->where('student_id', $student->id)->where('attempt_number', $lastAttempt + 1)->whereNull('used_at')->whereNull('revoked_at')->lockForUpdate()->first();
                abort_unless($authorization, 403, 'A retake has not been authorized.');
            }
            $questionIds = $this->examQuestionIds($exam);
            abort_if(empty($questionIds), 422, 'This exam has no configured questions.');
            $session = CbtStudentSession::create([
                'tenant_id' => $student->tenant_id, 'cbt_exam_id' => $exam->id, 'student_id' => $student->id,
                'attempt_number' => $lastAttempt + 1, 'is_authorized_attempt' => $lastAttempt === 0 || (bool) $authorization,
                'retake_authorization_id' => $authorization?->id, 'question_order' => $questionIds,
                'answers' => [], 'essay_answers' => [], 'started_at' => now(), 'integrity_acknowledged_at' => now(), 'status' => 'in_progress',
            ]);
            $authorization?->update(['used_at' => now()]);
            return $session;
        });
        CbtIntegrityEvent::firstOrCreate(
            ['cbt_student_session_id' => $session->id, 'event_type' => 'exam_started'],
            ['tenant_id' => $session->tenant_id, 'cbt_exam_id' => $session->cbt_exam_id, 'student_id' => $session->student_id,
                'event_uuid' => (string) Str::uuid(), 'severity' => 'info', 'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(), 'metadata' => ['attempt_number' => $session->attempt_number], 'occurred_at' => now()]
        );
        if ($session->attempt_number > 1) {
            CbtIntegrityEvent::firstOrCreate(
                ['cbt_student_session_id' => $session->id, 'event_type' => 'new_attempt_created'],
                ['tenant_id' => $session->tenant_id, 'cbt_exam_id' => $session->cbt_exam_id, 'student_id' => $session->student_id,
                    'event_uuid' => (string) Str::uuid(), 'severity' => 'info', 'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(), 'metadata' => ['attempt_number' => $session->attempt_number, 'retake_authorization_id' => $session->retake_authorization_id], 'occurred_at' => now()]
            );
        }
        return redirect()->route('cbt.exams.start', $exam);
    }

    // ── Submit Exam ───────────────────────────────────────────────────
    public function submitExam(Request $request, CbtStudentSession $session, CbtSubmissionService $submission)
    {
        $user = Auth::user();

        abort_unless($user->isStudent(), 403, 'Only students can submit CBT sessions.');

        $student = $this->studentForCurrentUser();

        abort_unless((int) $session->student_id === (int) $student->id, 403, 'You are not allowed to submit this session.');

        abort_if($session->isFinal(), 409, 'This exam has already been submitted and its answers are locked.');

        abort_unless($session->isInProgress(), 409, 'This exam session is no longer active.');

        $session = $submission->submit($session, (array) $request->input('answers', []), (array) $request->input('essay_answers', []));
        $msg = $session->isFullyScored()
            ? "Exam submitted. Final score: {$session->raw_score}/{$session->maximum_score} ({$session->percentage}%)."
            : 'Exam submitted. The final result will be available after manual scoring is completed.';

        return redirect()->route('student.portal.dashboard')->with('success', $msg);
    }

    // ── Bulk Upload ───────────────────────────────────────────────────
    public function bulkUploadPage(CbtQuestionBank $bank)
    {
        abort_unless($this->teacherTeachesBank(Auth::user(), $bank), 403, 'You can only manage question banks for subjects you teach.');
        $batch = request()->filled('batch')
            ? CbtImportBatch::where('question_bank_id', $bank->id)->whereKey(request()->integer('batch'))->first()
            : null;
        $exams = CbtExam::where('question_bank_id', $bank->id)->where('status', 'draft')->with('sections')->latest()->get();
        return view('cbt.bulk-upload', compact('bank', 'batch', 'exams'));
    }

    public function bulkUploadTemplate(Request $request)
    {
        $rows = [CbtQuestionImportService::HEADERS,
            ['A','Objective','1','','1','single_choice','What is the capital of Nigeria?','Lagos','Abuja','Kano','Ibadan','B','2','automatic','online','1','','yes',''],
            ['B','Theory','1','','1','theory_group','Read the passage and answer the questions that follow.','','','','','','0','manual','online','1','Answer all parts.','yes',''],
            ['B','Theory','1a','1','2','theory','State the central idea of the passage.','','','','','','5','manual','online','2','','yes','A concise statement of the central idea.'],
        ];
        if ($request->query('format') === 'csv') {
            return response()->streamDownload(function () use ($rows) { $h = fopen('php://output', 'w'); foreach ($rows as $row) fputcsv($h, $row); fclose($h); }, 'cbt_questions_template.csv', ['Content-Type' => 'text/csv']);
        }
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Questions')->fromArray($rows);
        $sheet->freezePane('A2');
        $sheet->getStyle('A1:S1')->getFont()->setBold(true);
        $instructions = $spreadsheet->createSheet();
        $instructions->setTitle('Instructions')->fromArray([
            ['Field', 'Requirement'],
            ['section_code', 'Required when importing directly into a draft exam. Must match an existing section code.'],
            ['question_no', 'Required and unique within its section.'],
            ['parent_question_no', 'Use the parent question_no from the same section; leave blank for a top-level question.'],
            ['question_level', 'Top-level = 1; each child increases the level by one.'],
            ['question_type', 'single_choice, true_false, fill_blank, short_answer, theory, or theory_group.'],
            ['marks', 'Enter marks on the lowest assessable parts. Instruction/theory_group rows must be 0.'],
            ['scoring_method', 'automatic or manual. Paper-answer questions must be manual.'],
            ['answer_mode', 'online, paper, or hybrid; must match the selected exam section.'],
        ]);
        $instructions->getStyle('A1:B1')->getFont()->setBold(true);
        $path = tempnam(sys_get_temp_dir(), 'cbt-template-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        return response()->download($path, 'cbt_questions_template.xlsx')->deleteFileAfterSend(true);
    }

    public function bulkImport(Request $request, CbtQuestionBank $bank, CbtQuestionImportService $importer)
    {
        abort_unless($this->teacherTeachesBank(Auth::user(), $bank), 403, 'You can only manage question banks for subjects you teach.');
        $data = $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'], 'exam_id' => ['nullable', Rule::exists('cbt_exams', 'id')->where('question_bank_id', $bank->id)->where('tenant_id', $bank->tenant_id)]]);
        $exam = ! empty($data['exam_id']) ? CbtExam::findOrFail($data['exam_id']) : null;
        $batch = $importer->preview($bank, $request->file('file'), auth()->id(), $exam);
        return redirect()->route('cbt.bulk-upload', ['bank' => $bank, 'batch' => $batch->id]);
    }

    public function confirmBulkImport(CbtQuestionBank $bank, CbtImportBatch $batch, CbtQuestionImportService $importer)
    {
        abort_unless($this->teacherTeachesBank(Auth::user(), $bank) && (int) $batch->question_bank_id === (int) $bank->id, 403);
        $count = $importer->import($batch);
        \App\Models\AuditLog::create(['tenant_id' => $bank->tenant_id, 'actor_user_id' => auth()->id(), 'auditable_type' => CbtQuestionBank::class, 'auditable_id' => $bank->id, 'action' => 'cbt.questions.imported', 'new_values' => ['batch_id' => $batch->id, 'count' => $count, 'file' => $batch->original_name]]);
        return redirect()->route('cbt.questions', $bank)->with('success', "{$count} questions imported transactionally.");
    }

    // ── Essay Grading ─────────────────────────────────────────────────
    public function gradeEssay(Request $request, CbtStudentSession $session, CbtSubmissionService $submission)
    {
        $this->authorizeCbtStaffAccess();
        $user = Auth::user();
        $exam = $session->exam ?? CbtExam::find($session->cbt_exam_id);
        if ($exam && !$this->hasFullCbtAccess($user) && !$this->teacherTeachesBank($user, $exam->questionBank)) {
            abort(403, 'You can only grade essays for subjects you teach.');
        }

        if (!in_array($session->status, ['submitted', 'graded', 'auto_submitted'], true)) {
            return back()->with('info', 'Only submitted CBT sessions can be graded.');
        }
        abort_if(
            \App\Models\ReportCardPublication::where('class_arm_id', $exam->class_arm_id)
                ->where('term_id', $exam->term_id)->where('status', 'published')->exists(),
            423,
            'These results are published and locked. Unpublish the report cards before changing CBT marks.'
        );

        $data = $request->validate(['manual_scores' => ['required','array'], 'manual_scores.*' => ['numeric','min:0']]);
        $graded = $submission->grade($session, $data['manual_scores'], auth()->id());
        return back()->with('success', "Manual scoring completed. Total: {$graded->raw_score}/{$graded->maximum_score} ({$graded->percentage}%)");
    }
}
