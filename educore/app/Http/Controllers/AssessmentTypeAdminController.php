<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\AssessmentSchemeTemplate;
use App\Models\AssessmentType;
use App\Models\ClassLevel;
use App\Models\Score;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AssessmentTypeAdminController extends Controller
{
    private function authorizeAdmin(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && ($user->isSuperAdmin() || $user->canAccessExactModule('scores')),
            403,
            'Only administrators can manage assessment types.'
        );
    }

    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    public function index()
    {
        $this->authorizeAdmin();

        $terms = Term::with('session')->latest()->get();
        $assessmentTypes = AssessmentType::with(['term.session', 'classLevels'])
            ->latest()
            ->get();
        $sessions = AcademicSession::orderBy('name')->get();
        $classLevels = ClassLevel::orderBy('order_index')->orderBy('name')->get();
        $schemeTemplates = AssessmentSchemeTemplate::with('items')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('scores.assessment-types-admin', compact(
            'assessmentTypes',
            'terms',
            'sessions',
            'classLevels',
            'schemeTemplates'
        ));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'term_id' => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
            'name' => ['required', 'string', 'max:100'],
            'weight_percentage' => ['required', 'integer', 'min:1', 'max:100'],
            'is_exam' => ['nullable', 'boolean'],
            'objective_max' => ['nullable', 'numeric', 'min:0.5'],
            'theory_max' => ['nullable', 'numeric', 'min:0.5'],
            'class_level_ids' => ['required', 'array', 'min:1'],
            'class_level_ids.*' => [
                'integer',
                Rule::exists('class_levels', 'id')->where('tenant_id', $this->tenantId()),
            ],
        ]);

        $this->validateSplitMarks($request, $validated);
        $classLevelIds = collect($validated['class_level_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->validateClassLevelWeights(
            (int) $validated['term_id'],
            (int) $validated['weight_percentage'],
            $classLevelIds
        );

        DB::transaction(function () use ($request, $validated, $classLevelIds): void {
            $assessmentType = AssessmentType::create([
                'term_id' => $validated['term_id'],
                'name' => trim($validated['name']),
                'weight_percentage' => $validated['weight_percentage'],
                'is_exam' => $request->boolean('is_exam'),
                'objective_max' => $validated['objective_max'] ?? null,
                'theory_max' => $validated['theory_max'] ?? null,
            ]);

            $assessmentType->classLevels()->sync($classLevelIds);
        });

        return back()->with('success', 'Assessment type created for the selected class levels.');
    }

    public function update(Request $request, AssessmentType $at)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'term_id' => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
            'name' => ['required', 'string', 'max:100'],
            'weight_percentage' => ['required', 'integer', 'min:1', 'max:100'],
            'is_exam' => ['nullable', 'boolean'],
            'class_level_ids' => ['nullable', 'array'],
            'class_level_ids.*' => [
                'integer',
                Rule::exists('class_levels', 'id')->where('tenant_id', $this->tenantId()),
            ],
        ]);

        $newClassLevelIds = collect($validated['class_level_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values();
        $oldClassLevelIds = $at->classLevels()->pluck('class_levels.id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values();

        $hasScores = Score::where('assessment_type_id', $at->id)->exists();
        $configurationChanged = (int) $validated['term_id'] !== (int) $at->term_id
            || (int) $validated['weight_percentage'] !== (int) $at->weight_percentage
            || $newClassLevelIds->all() !== $oldClassLevelIds->all();

        if ($hasScores && $configurationChanged) {
            return back()->withErrors([
                'assessment_type' => 'This assessment type already has student scores. Its term, weight or class-level assignment cannot be changed. Create a new assessment type instead.',
            ]);
        }

        if ($newClassLevelIds->isNotEmpty()) {
            $this->validateClassLevelWeights(
                (int) $validated['term_id'],
                (int) $validated['weight_percentage'],
                $newClassLevelIds->all(),
                $at->id
            );
        }

        DB::transaction(function () use ($request, $validated, $newClassLevelIds, $at): void {
            $at->update([
                'term_id' => $validated['term_id'],
                'name' => trim($validated['name']),
                'weight_percentage' => $validated['weight_percentage'],
                'is_exam' => $request->boolean('is_exam'),
            ]);

            $at->classLevels()->sync($newClassLevelIds->all());
        });

        return back()->with('success', 'Assessment type updated.');
    }

    public function migrate(Request $request, AssessmentType $at)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'term_id' => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
            'weight_percentage' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $targetTermId = (int) $validated['term_id'];
        $weight = (int) $validated['weight_percentage'];
        $classLevelIds = $at->classLevels()->pluck('class_levels.id')->map(fn ($id) => (int) $id)->all();

        if ($classLevelIds) {
            $this->validateClassLevelWeights($targetTermId, $weight, $classLevelIds);
        }

        DB::transaction(function () use ($at, $targetTermId, $weight, $classLevelIds): void {
            $copy = [
                'term_id' => $targetTermId,
                'name' => $at->name,
                'weight_percentage' => $weight,
                'is_exam' => $at->is_exam,
                'objective_max' => null,
                'theory_max' => null,
            ];

            if ($at->isSplit() && (float) $at->weight_percentage > 0) {
                $ratio = $weight / (float) $at->weight_percentage;
                $copy['objective_max'] = round((float) $at->objective_max * $ratio, 2);
                $copy['theory_max'] = round($weight - $copy['objective_max'], 2);
            }

            $newType = AssessmentType::create($copy);
            if ($classLevelIds) {
                $newType->classLevels()->sync($classLevelIds);
            }
        });

        return back()->with('success', 'Assessment type copied to the selected term.');
    }

    public function destroy(AssessmentType $at)
    {
        $this->authorizeAdmin();

        $scoreCount = Score::where('assessment_type_id', $at->id)->count();
        if ($scoreCount > 0) {
            return back()->withErrors([
                'assessment_type' => "This assessment type cannot be deleted because {$scoreCount} student score record(s) already use it. This protection prevents accidental result loss.",
            ]);
        }

        $name = $at->name;
        $at->delete();

        return back()->with('success', "Assessment type '{$name}' deleted.");
    }

    public function storeTemplate(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'template_name' => [
                'required', 'string', 'max:120',
                Rule::unique('assessment_scheme_templates', 'name')
                    ->where(fn ($q) => $q->where('tenant_id', $this->tenantId())),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'source_term_id' => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
            'source_class_level_id' => ['required', Rule::exists('class_levels', 'id')->where('tenant_id', $this->tenantId())],
        ]);

        $sourceTypes = AssessmentType::resolvedForClassLevel(
            (int) $validated['source_term_id'],
            (int) $validated['source_class_level_id']
        );

        if ($sourceTypes->isEmpty()) {
            throw ValidationException::withMessages([
                'source_class_level_id' => 'No assessment configuration exists for the selected class level and term.',
            ]);
        }

        $total = (float) $sourceTypes->sum('weight_percentage');
        if (abs($total - 100) > 0.001) {
            throw ValidationException::withMessages([
                'source_class_level_id' => "The selected configuration totals {$total}%. Complete it to 100% before saving it as a reusable scheme.",
            ]);
        }

        DB::transaction(function () use ($validated, $sourceTypes): void {
            $template = AssessmentSchemeTemplate::create([
                'name' => trim($validated['template_name']),
                'description' => $validated['description'] ?? null,
                'is_active' => true,
            ]);

            foreach ($sourceTypes->values() as $index => $type) {
                $template->items()->create([
                    'tenant_id' => $this->tenantId(),
                    'name' => $type->name,
                    'weight_percentage' => (int) $type->weight_percentage,
                    'objective_max' => $type->objective_max,
                    'theory_max' => $type->theory_max,
                    'is_exam' => (bool) $type->is_exam,
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return back()->with('success', 'Reusable assessment scheme saved. It can now be applied to future terms.');
    }

    public function applyTemplate(Request $request, AssessmentSchemeTemplate $template)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'term_id' => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
            'class_level_ids' => ['required', 'array', 'min:1'],
            'class_level_ids.*' => [
                'integer',
                Rule::exists('class_levels', 'id')->where('tenant_id', $this->tenantId()),
            ],
            'replace_existing' => ['nullable', 'boolean'],
        ]);

        $classLevelIds = collect($validated['class_level_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $items = $template->items()->get();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'template' => 'This reusable scheme has no assessment components.',
            ]);
        }

        $total = (float) $items->sum('weight_percentage');
        if (abs($total - 100) > 0.001) {
            throw ValidationException::withMessages([
                'template' => "This reusable scheme totals {$total}% and cannot be applied until it totals 100%.",
            ]);
        }

        $targetTermId = (int) $validated['term_id'];
        $replaceExisting = $request->boolean('replace_existing');

        $existingScoped = AssessmentType::query()
            ->where('term_id', $targetTermId)
            ->whereHas('classLevels', fn ($q) => $q->whereIn('class_levels.id', $classLevelIds))
            ->with('classLevels')
            ->get();

        if ($existingScoped->isNotEmpty() && ! $replaceExisting) {
            throw ValidationException::withMessages([
                'class_level_ids' => 'One or more selected class levels already have an explicit assessment configuration for this term. Tick Replace existing configuration to overwrite only configurations that have no student scores.',
            ]);
        }

        if ($existingScoped->isNotEmpty()) {
            $protected = $existingScoped->filter(
                fn ($type) => Score::where('assessment_type_id', $type->id)->exists()
            );

            if ($protected->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'class_level_ids' => 'The existing configuration cannot be replaced because student scores already use one or more assessment types. Existing results are protected.',
                ]);
            }
        }

        DB::transaction(function () use ($existingScoped, $classLevelIds, $items, $targetTermId, $replaceExisting): void {
            if ($replaceExisting) {
                foreach ($existingScoped as $type) {
                    $detachIds = $type->classLevels->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->intersect($classLevelIds)
                        ->all();

                    if ($detachIds) {
                        $type->classLevels()->detach($detachIds);
                    }

                    if (! $type->classLevels()->exists()) {
                        $type->delete();
                    }
                }
            }

            foreach ($items as $item) {
                $type = AssessmentType::create([
                    'term_id' => $targetTermId,
                    'name' => $item->name,
                    'weight_percentage' => (int) $item->weight_percentage,
                    'objective_max' => $item->objective_max,
                    'theory_max' => $item->theory_max,
                    'is_exam' => (bool) $item->is_exam,
                ]);

                $type->classLevels()->sync($classLevelIds);
            }
        });

        return back()->with('success', "Assessment scheme '{$template->name}' applied to the selected class levels.");
    }

    public function destroyTemplate(AssessmentSchemeTemplate $template)
    {
        $this->authorizeAdmin();
        $name = $template->name;
        $template->delete();

        return back()->with('success', "Reusable assessment scheme '{$name}' deleted. Existing term configurations were not affected.");
    }

    private function validateClassLevelWeights(
        int $termId,
        int $weight,
        array $classLevelIds,
        ?int $excludeAssessmentTypeId = null
    ): void {
        foreach (array_unique($classLevelIds) as $classLevelId) {
            $query = AssessmentType::query()
                ->where('term_id', $termId)
                ->whereHas('classLevels', fn ($q) => $q->where('class_levels.id', $classLevelId));

            if ($excludeAssessmentTypeId) {
                $query->where('assessment_types.id', '!=', $excludeAssessmentTypeId);
            }

            $currentTotal = (float) $query->sum('weight_percentage');
            if ($currentTotal + $weight > 100) {
                $levelName = ClassLevel::find($classLevelId)?->name ?? "Class level {$classLevelId}";
                throw ValidationException::withMessages([
                    'weight_percentage' => "Assessment weights for {$levelName} would exceed 100% for the selected term. Current scoped total: {$currentTotal}%.",
                ]);
            }
        }
    }

    private function validateSplitMarks(Request $request, array $validated): void
    {
        $hasObjective = $request->filled('objective_max');
        $hasTheory = $request->filled('theory_max');

        if ($hasObjective xor $hasTheory) {
            throw ValidationException::withMessages([
                'objective_max' => 'Provide both Objective max and Theory max, or leave both blank.',
            ]);
        }

        if ($hasObjective && $hasTheory) {
            $sum = round((float) $validated['objective_max'] + (float) $validated['theory_max'], 2);
            if ($sum !== (float) $validated['weight_percentage']) {
                throw ValidationException::withMessages([
                    'objective_max' => 'Objective max plus Theory max must equal the assessment weight.',
                ]);
            }
        }
    }
}
