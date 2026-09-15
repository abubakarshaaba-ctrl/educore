<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\AssessmentTemplate;
use App\Models\AssessmentTemplateAssignment;
use App\Models\AssessmentType;
use App\Models\ClassLevel;
use App\Models\Score;
use App\Models\Term;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentTemplateService
{
    /**
     * Assign a template to one or more class levels for a session and materialize
     * its components into the existing assessment_types runtime contract.
     */
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

        $components = $template->components()->orderBy('sort_order')->get();
        if ($components->isEmpty()) {
            throw ValidationException::withMessages(['components' => 'Add at least one component before assigning this template.']);
        }

        $total = round((float) $components->sum('weight_percentage'), 2);
        if ($total !== 100.0) {
            throw ValidationException::withMessages(['components' => "Template weight must total 100%. Current total: {$total}%."]);
        }

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
                // Remove these class levels from previous scoped runtime rows for
                // this term. Rows themselves are preserved whenever historical
                // scores reference them.
                $existingScoped = AssessmentType::withoutTenantScope()
                    ->where('tenant_id', $tenantId)
                    ->where('term_id', $term->id)
                    ->whereHas('classLevels', fn ($q) => $q->whereIn('class_levels.id', $validLevelIds))
                    ->get();

                foreach ($existingScoped as $runtimeType) {
                    $runtimeType->classLevels()->detach($validLevelIds);
                    if (!$runtimeType->classLevels()->exists() && !Score::withoutTenantScope()->where('assessment_type_id', $runtimeType->id)->exists()) {
                        $runtimeType->delete();
                    }
                }

                foreach ($components as $component) {
                    $runtimeType = AssessmentType::withoutTenantScope()->firstOrCreate(
                        [
                            'tenant_id' => $tenantId,
                            'term_id' => $term->id,
                            'name' => $component->name,
                            'weight_percentage' => $component->weight_percentage,
                            'is_exam' => $component->isExam(),
                            'objective_max' => $component->objective_max,
                            'theory_max' => $component->theory_max,
                        ]
                    );
                    $runtimeType->classLevels()->syncWithoutDetaching($validLevelIds);
                }
            }

            return count($validLevelIds);
        });
    }
}
