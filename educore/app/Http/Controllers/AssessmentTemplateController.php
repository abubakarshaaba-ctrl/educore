<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\AssessmentTemplate;
use App\Models\AssessmentTemplateComponent;
use App\Models\ClassLevel;
use App\Services\AssessmentTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AssessmentTemplateController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function authorizeAdmin(): void
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->canAccessExactModule('scores'), 403);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();
        $tenantId = $this->tenantId();

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('assessment_templates', 'name')->where('tenant_id', $tenantId),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'draft'])],
            'components' => ['required', 'array', 'min:1'],
            'components.*.name' => ['required', 'string', 'max:100'],
            'components.*.weight_percentage' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'components.*.component_type' => ['required', Rule::in(['coursework', 'test', 'practical', 'exam', 'objective_exam', 'theory_exam', 'final_exam'])],
            'components.*.entry_mode' => ['required', Rule::in(['manual', 'cbt_objective', 'cbt_aggregate', 'theory_manual'])],
            'components.*.objective_max' => ['nullable', 'numeric', 'min:0.01'],
            'components.*.theory_max' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $total = round(collect($data['components'])->sum(fn ($component) => (float) $component['weight_percentage']), 2);
        if ($total !== 100.0) {
            return back()->withInput()->withErrors(['components' => "Component weights must total exactly 100%. Current total: {$total}%."]);
        }

        foreach ($data['components'] as $index => $component) {
            $objective = $component['objective_max'] ?? null;
            $theory = $component['theory_max'] ?? null;
            if (($objective === null) xor ($theory === null)) {
                return back()->withInput()->withErrors(["components.{$index}.objective_max" => 'Provide both Objective Max and Theory Max, or leave both blank.']);
            }
            if ($objective !== null && $theory !== null) {
                $splitTotal = round((float) $objective + (float) $theory, 2);
                if ($splitTotal !== round((float) $component['weight_percentage'], 2)) {
                    return back()->withInput()->withErrors(["components.{$index}.objective_max" => 'Objective Max + Theory Max must equal this component weight.']);
                }
            }
        }

        DB::transaction(function () use ($data, $tenantId) {
            $template = AssessmentTemplate::withoutTenantScope()->create([
                'tenant_id' => $tenantId,
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
            ]);

            foreach (array_values($data['components']) as $index => $component) {
                AssessmentTemplateComponent::withoutTenantScope()->create([
                    'tenant_id' => $tenantId,
                    'assessment_template_id' => $template->id,
                    'name' => trim($component['name']),
                    'weight_percentage' => $component['weight_percentage'],
                    'component_type' => $component['component_type'],
                    'entry_mode' => $component['entry_mode'],
                    'objective_max' => $component['objective_max'] ?? null,
                    'theory_max' => $component['theory_max'] ?? null,
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return redirect()->route('scores.assessment-types')->with('success', 'Assessment template created. Assign it to class levels to activate it.');
    }

    public function update(Request $request, AssessmentTemplate $template)
    {
        $this->authorizeAdmin();
        abort_unless((int) $template->tenant_id === $this->tenantId(), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('assessment_templates', 'name')->where('tenant_id', $this->tenantId())->ignore($template->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'draft'])],
        ]);

        $template->update($data);

        return redirect()->route('scores.assessment-types', ['selected' => $template->id])->with('success', 'Assessment template updated.');
    }

    public function assign(Request $request, AssessmentTemplate $template, AssessmentTemplateService $service)
    {
        $this->authorizeAdmin();
        abort_unless((int) $template->tenant_id === $this->tenantId(), 404);

        $data = $request->validate([
            'session_id' => ['required', Rule::exists('academic_sessions', 'id')->where('tenant_id', $this->tenantId())],
            'class_level_ids' => ['required', 'array', 'min:1'],
            'class_level_ids.*' => ['integer', Rule::exists('class_levels', 'id')->where('tenant_id', $this->tenantId())],
        ]);

        $session = AcademicSession::withoutTenantScope()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($data['session_id']);

        $count = $service->assign($template, $session, $data['class_level_ids']);

        return redirect()->route('scores.assessment-types', ['selected' => $template->id])
            ->with('success', "Template assigned to {$count} class level(s) for {$session->name}. Runtime assessment rows were synchronized safely.");
    }

    public function destroy(AssessmentTemplate $template)
    {
        $this->authorizeAdmin();
        abort_unless((int) $template->tenant_id === $this->tenantId(), 404);

        $template->delete();

        return redirect()->route('scores.assessment-types')->with('success', 'Assessment template deleted. Historical assessment rows and scores were preserved.');
    }
}
