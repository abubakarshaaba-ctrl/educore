<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\AssessmentTemplate;
use App\Models\AssessmentTemplateAssignment;
use App\Models\AssessmentType;
use App\Models\ClassLevel;
use App\Models\Score;
use App\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentTemplateService
{
    public function assign(
        AssessmentTemplate $template,
        AcademicSession $session,
        array $classLevelIds
    ): int {
        $tenantId = (int) $template->tenant_id;
        $classLevelIds = collect($classLevelIds)->map(fn ($id) => (int) $id)->unique()->values()->all();

        if ((int) $session->tenant_id !== $tenantId) {
            throw ValidationException::withMessages(['session_id' => 'The selected session belongs to another school.']);
        }

        if (! $template->isActive()) {
            throw ValidationException::withMessages(['template' => 'Only an active assessment template can be assigned.']);
        }

        $components = $this->validatedComponents($template);

        $validLevelIds = ClassLevel::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $classLevelIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($validLevelIds) !== count($classLevelIds)) {
            throw ValidationException::withMessages(['class_level_ids' => 'One or more selected class levels are invalid.']);
        }

        return DB::transaction(function () use ($template, $session, $tenantId, $validLevelIds, $components) {
            foreach ($validLevelIds as $classLevelId) {
                AssessmentTemplateAssignment::withoutTenantScope()->updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'class_level_id' => $classLevelId,
                        'session_id' => $session->id,
                    ],
                    [
                        'assessment_template_id' => $template->id,
                        'is_active' => true,
                    ]
                );
            }

            $terms = Term::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->where('session_id', $session->id)
                ->orderBy('start_date')
                ->orderBy('id')
                ->get();

            foreach ($terms as $term) {
                foreach ($validLevelIds as $classLevelId) {
                    $this->syncTermClassLevel($template, $components, $term, $classLevelId);
                }
            }

            return count($validLevelIds);
        });
    }

    public function resynchronizeAssignments(AssessmentTemplate $template): int
    {
        $components = $this->validatedComponents($template);
        $assignments = AssessmentTemplateAssignment::withoutTenantScope()
            ->where('tenant_id', $template->tenant_id)
            ->where('assessment_template_id', $template->id)
            ->where('is_active', true)
            ->get();

        $synced = 0;
        foreach ($assignments as $assignment) {
            $terms = Term::withoutTenantScope()
                ->where('tenant_id', $template->tenant_id)
                ->where('session_id', $assignment->session_id)
                ->orderBy('start_date')
                ->orderBy('id')
                ->get();

            foreach ($terms as $term) {
                $this->syncTermClassLevel($template, $components, $term, (int) $assignment->class_level_id);
                $synced++;
            }
        }

        return $synced;
    }

    public function deleteTemplate(AssessmentTemplate $template): void
    {
        if ($template->hasRecordedScores()) {
            throw ValidationException::withMessages([
                'template' => 'Delete is disabled because student scores have already been recorded on this assessment template.',
            ]);
        }

        DB::transaction(function () use ($template): void {
            $assignments = AssessmentTemplateAssignment::withoutTenantScope()
                ->where('tenant_id', $template->tenant_id)
                ->where('assessment_template_id', $template->id)
                ->get();

            foreach ($assignments as $assignment) {
                $termIds = Term::withoutTenantScope()
                    ->where('tenant_id', $template->tenant_id)
                    ->where('session_id', $assignment->session_id)
                    ->pluck('id');

                if ($termIds->isEmpty()) {
                    continue;
                }

                $runtimeTypes = AssessmentType::withoutTenantScope()
                    ->where('tenant_id', $template->tenant_id)
                    ->whereIn('term_id', $termIds)
                    ->whereHas('classLevels', fn ($query) => $query
                        ->where('class_levels.id', $assignment->class_level_id))
                    ->with('classLevels')
                    ->get();

                foreach ($runtimeTypes as $runtimeType) {
                    $runtimeType->classLevels()->detach([(int) $assignment->class_level_id]);
                    if (! $runtimeType->classLevels()->exists()) {
                        $runtimeType->delete();
                    }
                }
            }

            AssessmentTemplateAssignment::withoutTenantScope()
                ->where('tenant_id', $template->tenant_id)
                ->where('assessment_template_id', $template->id)
                ->delete();

            $template->delete();
        });
    }

    public function materializeAssignmentsForTerm(Term $term): int
    {
        $assignments = AssessmentTemplateAssignment::withoutTenantScope()
            ->where('tenant_id', $term->tenant_id)
            ->where('session_id', $term->session_id)
            ->where('is_active', true)
            ->with('template.components')
            ->get();

        $synced = 0;
        foreach ($assignments as $assignment) {
            $template = $assignment->template;
            if (! $template || ! $template->isActive()) {
                continue;
            }

            $components = $this->validatedComponents($template);
            $this->syncTermClassLevel($template, $components, $term, (int) $assignment->class_level_id);
            $synced++;
        }

        return $synced;
    }

    private function syncTermClassLevel(
        AssessmentTemplate $template,
        Collection $components,
        Term $term,
        int $classLevelId
    ): void {
        $tenantId = (int) $template->tenant_id;

        $existingScoped = AssessmentType::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('term_id', $term->id)
            ->whereHas('classLevels', fn ($q) => $q->where('class_levels.id', $classLevelId))
            ->with('classLevels')
            ->get();

        $protected = $existingScoped->filter(
            fn (AssessmentType $type) => Score::withoutTenantScope()
                ->where('assessment_type_id', $type->id)
                ->exists()
        );

        if ($protected->isNotEmpty()) {
            $levelName = ClassLevel::withoutTenantScope()->find($classLevelId)?->name ?? "#{$classLevelId}";
            throw ValidationException::withMessages([
                'template' => "{$levelName} already has student scores for {$term->name}. Its assessment structure is locked. Duplicate this template for a future session instead of changing historical scoring.",
            ]);
        }

        foreach ($existingScoped as $runtimeType) {
            $runtimeType->classLevels()->detach([$classLevelId]);
            if (! $runtimeType->classLevels()->exists()) {
                $runtimeType->delete();
            }
        }

        foreach ($components as $component) {
            $runtimeType = AssessmentType::withoutTenantScope()->firstOrCreate([
                'tenant_id' => $tenantId,
                'term_id' => $term->id,
                'name' => $component->name,
                'weight_percentage' => $component->weight_percentage,
                'is_exam' => $component->isExam(),
                // Objective/theory maxima belong to the CBT exam and its
                // question/section marks. The score-sheet runtime stores only
                // the template contribution weight.
                'objective_max' => null,
                'theory_max' => null,
            ]);

            $runtimeType->classLevels()->syncWithoutDetaching([$classLevelId]);
        }
    }

    private function validatedComponents(AssessmentTemplate $template): Collection
    {
        $components = $template->components()->orderBy('sort_order')->orderBy('id')->get();
        if ($components->isEmpty()) {
            throw ValidationException::withMessages(['components' => 'Add at least one component before assigning this template.']);
        }

        $total = round((float) $components->sum('weight_percentage'), 2);
        if (abs($total - 100.0) > 0.001) {
            throw ValidationException::withMessages(['components' => "Template weight must total 100%. Current total: {$total}%."]);
        }

        return $components;
    }
}
