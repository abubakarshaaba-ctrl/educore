<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CbtExam;
use App\Models\CbtExamSection;
use App\Models\CbtQuestion;
use App\Services\Cbt\CbtExamConfigurationService;
use App\Services\Cbt\CbtQuestionNumberingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CbtExamSectionController extends Controller
{
    public function __construct(
        private readonly CbtExamConfigurationService $configuration,
        private readonly CbtQuestionNumberingService $numbering,
    ) {}

    public function builder(CbtExam $exam)
    {
        $this->authorizeExam($exam);
        if ($exam->status === 'draft' && ! $exam->sections()->exists()) {
            $this->configuration->createSectionsFromBank($exam, auth()->id());
        }
        $exam->load(['sections.questions.parent', 'questionBank.subject', 'classArm.classLevel', 'classArms.classLevel']);
        $availableQuestions = $this->numbering->number(
            $exam->questionBank->questions()->with('parent')->orderBy('sequence')->orderBy('id')->get()
        );
        $publicationErrors = $this->configuration->publicationErrors($exam);
        return view('cbt.builder', compact('exam', 'availableQuestions', 'publicationErrors'));
    }

    public function store(Request $request, CbtExam $exam)
    {
        $this->authorizeDraft($exam);
        $data = $this->validatedSection($request, $exam);
        $data['tenant_id'] = $exam->tenant_id;
        $data['created_by'] = auth()->id();
        $data['display_order'] = $exam->sections()->max('display_order') + 1;
        $section = $exam->sections()->create($data);
        $this->audit($section, 'cbt.section.created', $data);
        $this->configuration->recalculateExamTotals($exam);
        return back()->with('success', 'Section added.');
    }

    public function update(Request $request, CbtExamSection $section)
    {
        $this->authorizeDraft($section->exam);
        $data = $this->validatedSection($request, $section->exam, $section);
        $old = $section->only(array_keys($data));
        $section->update($data);
        $this->audit($section, 'cbt.section.updated', $data, $old);
        $this->configuration->recalculateExamTotals($section->exam);
        return back()->with('success', 'Section updated.');
    }

    public function destroy(CbtExamSection $section)
    {
        $this->authorizeDraft($section->exam);
        $exam = $section->exam;
        $old = $section->toArray();
        $section->delete();
        $this->audit($exam, 'cbt.section.deleted', [], $old);
        $this->configuration->recalculateExamTotals($exam);
        return back()->with('success', 'Section removed. Questions remain in the bank.');
    }

    public function duplicate(CbtExamSection $section)
    {
        $this->authorizeDraft($section->exam);
        $copy = DB::transaction(function () use ($section) {
            $copy = $section->replicate(['code', 'display_order']);
            $copy->code = $this->nextCode($section->exam);
            $copy->name = $section->name.' Copy';
            $copy->display_order = $section->exam->sections()->max('display_order') + 1;
            $copy->created_by = auth()->id();
            $copy->save();
            foreach ($section->questions as $question) {
                $copy->questions()->attach($question->id, [
                    'tenant_id' => $section->tenant_id, 'cbt_exam_id' => $section->cbt_exam_id,
                    'display_order' => $question->pivot->display_order, 'marks_override' => $question->pivot->marks_override,
                ]);
            }
            return $copy;
        });
        $this->audit($copy, 'cbt.section.duplicated', ['source_section_id' => $section->id]);
        $this->configuration->recalculateExamTotals($section->exam);
        return back()->with('success', 'Section duplicated.');
    }

    public function reorder(Request $request, CbtExam $exam)
    {
        $this->authorizeDraft($exam);
        $data = $request->validate(['section_ids' => ['required', 'array'], 'section_ids.*' => ['integer']]);
        $allowed = $exam->sections()->pluck('id')->map(fn ($id) => (int) $id)->all();
        abort_unless(collect($data['section_ids'])->map(fn ($id) => (int) $id)->sort()->values()->all() === collect($allowed)->sort()->values()->all(), 422, 'Invalid section order.');
        foreach ($data['section_ids'] as $index => $id) CbtExamSection::whereKey($id)->update(['display_order' => $index + 1]);
        return response()->json(['ok' => true]);
    }

    public function attachQuestion(Request $request, CbtExamSection $section)
    {
        $this->authorizeDraft($section->exam);
        $data = $request->validate([
            'question_id' => ['required', Rule::exists('cbt_questions', 'id')->where('question_bank_id', $section->exam->question_bank_id)],
            'marks_override' => ['nullable', 'numeric', 'min:0'],
        ]);
        $selected = CbtQuestion::findOrFail($data['question_id']);
        $branchIds = collect();
        $ancestor = $selected->parent;
        while ($ancestor) {
            $branchIds->prepend($ancestor->id);
            $ancestor = $ancestor->parent;
        }
        $collectDescendants = function (CbtQuestion $question) use (&$collectDescendants, $branchIds) {
            $branchIds->push($question->id);
            foreach ($question->children()->orderBy('sequence')->get() as $child) $collectDescendants($child);
        };
        $collectDescendants($selected);

        $displayOrder = $section->questions()->count();
        foreach ($branchIds->unique() as $questionId) {
            if ($section->questions()->whereKey($questionId)->exists()) continue;
            $displayOrder++;
            $section->questions()->attach($questionId, [
                'tenant_id' => $section->tenant_id,
                'cbt_exam_id' => $section->cbt_exam_id,
                'display_order' => $displayOrder,
                'marks_override' => (int) $questionId === (int) $selected->id ? ($data['marks_override'] ?? null) : null,
            ]);
        }
        $this->configuration->recalculateExamTotals($section->exam);
        return back()->with('success', 'Question hierarchy added to the section.');
    }

    public function createQuestion(Request $request, CbtExamSection $section)
    {
        $this->authorizeDraft($section->exam);
        $data = $request->validate([
            'parent_question_id' => ['nullable', Rule::exists('cbt_questions', 'id')->where('question_bank_id', $section->exam->question_bank_id)],
            'type' => ['required', 'in:mcq,essay,short_answer,fill_blank,true_false'],
            'question_text' => ['required', 'string'], 'marks' => ['nullable', 'numeric', 'min:0'],
            'is_instruction_only' => ['nullable', 'boolean'], 'requires_answer' => ['nullable', 'boolean'],
            'numbering_style' => ['nullable', 'in:auto,decimal,lower_alpha,upper_alpha,lower_roman,upper_roman'],
            'model_answer' => ['nullable', 'string'], 'option_a' => ['required_if:type,mcq', 'nullable', 'string'], 'option_b' => ['required_if:type,mcq', 'nullable', 'string'],
            'option_c' => ['nullable', 'string'], 'option_d' => ['nullable', 'string'],
            'correct_answer_letter' => [Rule::requiredIf(in_array($request->input('type'), ['mcq', 'true_false'], true)), 'nullable', 'in:a,b,c,d'],
        ]);
        $parent = ! empty($data['parent_question_id']) ? CbtQuestion::find($data['parent_question_id']) : null;
        $question = CbtQuestion::create(array_merge($data, [
            'tenant_id' => $section->tenant_id, 'question_bank_id' => $section->exam->question_bank_id,
            'level' => $parent ? $parent->level + 1 : 0,
            'sequence' => CbtQuestion::where('question_bank_id', $section->exam->question_bank_id)->where('parent_question_id', $data['parent_question_id'] ?? null)->max('sequence') + 1,
            'is_instruction_only' => $request->boolean('is_instruction_only'),
            'requires_answer' => $request->has('requires_answer') ? $request->boolean('requires_answer') : ! $request->boolean('is_instruction_only'),
            'marks' => $request->boolean('is_instruction_only') ? 0 : ($data['marks'] ?? 1),
            'numbering_style' => $data['numbering_style'] ?? 'auto',
            'option_a' => $data['option_a'] ?? ($data['type'] === 'true_false' ? 'True' : null),
            'option_b' => $data['option_b'] ?? ($data['type'] === 'true_false' ? 'False' : null),
        ]));
        $section->questions()->attach($question->id, ['tenant_id' => $section->tenant_id, 'cbt_exam_id' => $section->cbt_exam_id, 'display_order' => $section->questions()->count() + 1]);
        $this->configuration->recalculateExamTotals($section->exam);
        return back()->with('success', 'Question added.');
    }

    public function duplicateQuestion(CbtExamSection $section, CbtQuestion $question)
    {
        $this->authorizeDraft($section->exam);
        abort_unless($section->questions()->whereKey($question->id)->exists(), 404);
        $copy = DB::transaction(function () use ($section, $question) {
            $map = [];
            $clone = function (CbtQuestion $source, ?int $parentId = null) use (&$clone, &$map, $section) {
                $copy = $source->replicate();
                $copy->parent_question_id = $parentId;
                $copy->reference_code = $source->reference_code ? $source->reference_code.'-copy' : null;
                $copy->sequence = CbtQuestion::where('question_bank_id', $source->question_bank_id)->where('parent_question_id', $parentId)->max('sequence') + 1;
                $copy->save();
                $map[$source->id] = $copy->id;
                foreach ($source->children as $child) $clone($child, $copy->id);
                return $copy;
            };
            $copy = $clone($question, $question->parent_question_id);
            foreach ($map as $newId) $section->questions()->syncWithoutDetaching([$newId => ['tenant_id' => $section->tenant_id, 'cbt_exam_id' => $section->cbt_exam_id, 'display_order' => $section->questions()->count() + 1]]);
            return $copy;
        });
        $this->configuration->recalculateExamTotals($section->exam);
        return back()->with('success', 'Question branch duplicated.');
    }

    public function removeQuestion(CbtExamSection $section, CbtQuestion $question)
    {
        $this->authorizeDraft($section->exam);
        $section->questions()->detach($question->id);
        $this->configuration->recalculateExamTotals($section->exam);
        return back()->with('success', 'Question removed from this section.');
    }

    public function updateQuestion(Request $request, CbtExamSection $section, CbtQuestion $question)
    {
        $this->authorizeDraft($section->exam);
        abort_unless($section->questions()->whereKey($question->id)->exists(), 404);
        $data = $request->validate(['marks_override' => ['required', 'numeric', 'min:0']]);
        $section->questions()->updateExistingPivot($question->id, ['marks_override' => $data['marks_override']]);
        $this->configuration->recalculateExamTotals($section->exam);
        return back()->with('success', 'Question marks updated.');
    }

    public function reorderQuestions(Request $request, CbtExamSection $section)
    {
        $this->authorizeDraft($section->exam);
        $data = $request->validate(['question_ids' => ['required', 'array'], 'question_ids.*' => ['integer']]);
        $allowed = $section->questions()->pluck('cbt_questions.id')->map(fn ($id) => (int) $id)->all();
        abort_unless(collect($data['question_ids'])->map(fn ($id) => (int) $id)->sort()->values()->all() === collect($allowed)->sort()->values()->all(), 422, 'Invalid question order.');
        $parentCounters = [];
        foreach ($data['question_ids'] as $index => $id) {
            DB::table('cbt_exam_section_questions')->where('cbt_exam_section_id', $section->id)->where('cbt_question_id', $id)->update(['display_order' => $index + 1]);
            $question = CbtQuestion::findOrFail($id);
            $parentKey = (int) ($question->parent_question_id ?: 0);
            $parentCounters[$parentKey] = ($parentCounters[$parentKey] ?? 0) + 1;
            $question->update(['sequence' => $parentCounters[$parentKey]]);
        }
        return response()->json(['ok' => true]);
    }

    private function validatedSection(Request $request, CbtExam $exam, ?CbtExamSection $section = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:20', Rule::unique('cbt_exam_sections', 'code')->where('cbt_exam_id', $exam->id)->ignore($section?->id)],
            'title' => ['nullable', 'string', 'max:180'], 'instructions' => ['nullable', 'string'],
            'section_type' => ['required', 'in:objective,theory,essay,practical,comprehension,oral,other,mixed'],
            'scoring_method' => ['required', 'in:automatic,manual,mixed'],
            'answer_mode' => ['required', 'in:online,paper,hybrid'], 'max_marks' => ['required', 'numeric', 'min:0'],
            'is_required' => ['nullable', 'boolean'], 'is_active' => ['nullable', 'boolean'],
        ]) + ['is_required' => $request->boolean('is_required'), 'is_active' => $request->boolean('is_active')];

        if (in_array($data['section_type'], ['theory', 'essay'], true)) {
            $data['scoring_method'] = 'manual';
            $data['answer_mode'] = 'paper';
        }

        return $data;
    }

    private function authorizeExam(CbtExam $exam): void
    {
        $user = auth()->user();
        $assignedArmIds = $exam->assignedClassArmIds();
        abort_unless($user && ! $user->isStudent() && (int) $user->tenant_id === (int) $exam->tenant_id && ($user->isAdmin() || $user->isSuperAdmin() || \App\Models\ClassArmSubject::where('teacher_id', $user->id)->whereIn('class_arm_id', $assignedArmIds)->where('subject_id', $exam->questionBank->subject_id)->exists()), 403);
    }

    private function authorizeDraft(CbtExam $exam): void { $this->authorizeExam($exam); abort_unless($exam->status === 'draft', 422, 'Published or closed exams cannot be structurally edited.'); }
    private function nextCode(CbtExam $exam): string { $used = $exam->sections()->pluck('code')->all(); foreach (range('A', 'Z') as $code) if (! in_array($code, $used, true)) return $code; return 'S'.($exam->sections()->count() + 1); }
    private function audit($model, string $action, array $new = [], array $old = []): void { AuditLog::create(['tenant_id' => $model->tenant_id, 'actor_user_id' => auth()->id(), 'auditable_type' => $model::class, 'auditable_id' => $model->id, 'action' => $action, 'old_values' => $old, 'new_values' => $new, 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()]); }
}
