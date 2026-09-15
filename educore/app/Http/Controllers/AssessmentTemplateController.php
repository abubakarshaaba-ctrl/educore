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
use Illuminate\Validation\ValidationException;

class AssessmentTemplateController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function authorizeAdmin(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->canAccessExactModule('scores')), 403,
            'Only administrators can manage assessment templates.');
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin();
        $tenantId = $this->tenantId();

        $templates = AssessmentTemplate::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->with(['components', 'assignments.classLevel', 'assignments.session'])
            ->orderBy('name')
            ->get();

        $selectedId = (int) $request->query('selected', $templates->first()?->id ?? 0);
        $selectedTemplate = $templates->firstWhere('id', $selectedId) ?? $templates->first();

        $templateClassLevels = ClassLevel::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->orderBy('order_index')
            ->orderBy('name')
            ->get();

        $templateSessions = AcademicSession::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->get();

        return view('scores.assessment-types', compact(
            'templates',
            'selectedTemplate',
            'templateClassLevels',
            'templateSessions'
        ));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();
        $tenantId = $this->tenantId();
        $data = $this->validateTemplatePayload($request, null);

        $template = DB::transaction(function () use ($data, $tenantId) {
            $template = AssessmentTemplate::withoutTenantScope()->create([
                'tenant_id' => $tenantId,
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
            ]);

            $this->replaceComponents($template, $data['components']);

            return $template;
        });

        return redirect()->route('scores.assessment-types', ['selected' => $template->id])
            ->with('success', 'Assessment template created. Assign it to class levels to activate it.');
    }

    public function update(Request $request, AssessmentTemplate $template, AssessmentTemplateService $service)
    {
        $this->authorizeAdmin();
        $this->assertOwnTemplate($template);
        $data = $this->validateTemplatePayload($request, $template);

        DB::transaction(function () use ($template, $data, $service): void {
            $template->update([
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
            ]);

            $this->replaceComponents($template, $data['components']);
            $service->resynchronizeAssignments($template->fresh('components'));
        });

        return redirect()->route('scores.assessment-types', ['selected' => $template->id])
            ->with('success', 'Assessment template updated and its unscored runtime configurations synchronized.');
    }

    public function duplicate(AssessmentTemplate $template)
    {
        $this->authorizeAdmin();
        $this->assertOwnTemplate($template);
        $template->load('components');

        $copy = DB::transaction(function () use ($template) {
            $base = $template->name . ' Copy';
            $name = $base;
            $counter = 2;
            while (AssessmentTemplate::withoutTenantScope()
                ->where('tenant_id', $this->tenantId())
                ->where('name', $name)
                ->exists()) {
                $name = $base . ' ' . $counter++;
            }

            $copy = AssessmentTemplate::withoutTenantScope()->create([
                'tenant_id' => $this->tenantId(),
                'name' => $name,
                'description' => $template->description,
                'status' => AssessmentTemplate::STATUS_DRAFT,
            ]);

            foreach ($template->components as $component) {
                AssessmentTemplateComponent::withoutTenantScope()->create([
                    'tenant_id' => $this->tenantId(),
                    'assessment_template_id' => $copy->id,
                    'name' => $component->name,
                    'weight_percentage' => $component->weight_percentage,
                    'component_type' => $component->component_type,
                    'entry_mode' => $component->entry_mode,
                    'objective_max' => $component->objective_max,
                    'theory_max' => $component->theory_max,
                    'sort_order' => $component->sort_order,
                ]);
            }

            return $copy;
        });

        return redirect()->route('scores.assessment-types', ['selected' => $copy->id])
            ->with('success', 'Assessment template duplicated as a draft.');
    }

    public function assign(Request $request, AssessmentTemplate $template, AssessmentTemplateService $service)
    {
        $this->authorizeAdmin();
        $this->assertOwnTemplate($template);

        if (! $template->isActive()) {
            return back()->withErrors(['template' => 'Only an active assessment template can be assigned.']);
        }

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
            ->with('success', "Template assigned to {$count} class level(s) for {$session->name}. All terms in that session were synchronized safely.");
    }

    public function destroy(AssessmentTemplate $template)
    {
        $this->authorizeAdmin();
        $this->assertOwnTemplate($template);

        if ($template->assignments()->where('is_active', true)->exists()) {
            return back()->withErrors([
                'template' => 'This template is currently assigned. Reassign those class levels before deleting it.',
            ]);
        }

        $template->delete();

        return redirect()->route('scores.assessment-types')
            ->with('success', 'Assessment template deleted. Historical assessment rows and scores were preserved.');
    }

    private function validateTemplatePayload(Request $request, ?AssessmentTemplate $template): array
    {
        $tenantId = $this->tenantId();
        $nameRule = Rule::unique('assessment_templates', 'name')->where('tenant_id', $tenantId);
        if ($template) {
            $nameRule = $nameRule->ignore($template->id);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', $nameRule],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in([AssessmentTemplate::STATUS_ACTIVE, AssessmentTemplate::STATUS_DRAFT])],
            'components' => ['required', 'array', 'min:1'],
            'components.*.name' => ['required', 'string', 'max:100'],
            'components.*.weight_percentage' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'components.*.component_type' => ['required', Rule::in(['coursework', 'test', 'practical', 'exam', 'objective_exam', 'theory_exam', 'final_exam'])],
            'components.*.entry_mode' => ['required', Rule::in(['manual', 'cbt_objective', 'cbt_aggregate', 'theory_manual'])],
            'components.*.objective_max' => ['nullable', 'numeric', 'min:0.01'],
            'components.*.theory_max' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $total = round(collect($data['components'])->sum(fn ($component) => (float) $component['weight_percentage']), 2);
        if (abs($total - 100.0) > 0.001) {
            throw ValidationException::withMessages([
                'components' => "Component weights must total exactly 100%. Current total: {$total}%.",
            ]);
        }

        foreach ($data['components'] as $index => $component) {
            $objective = $component['objective_max'] ?? null;
            $theory = $component['theory_max'] ?? null;
            if (($objective === null) xor ($theory === null)) {
                throw ValidationException::withMessages([
                    "components.{$index}.objective_max" => 'Provide both Objective Max and Theory Max, or leave both blank.',
                ]);
            }
            if ($objective !== null && $theory !== null) {
                $splitTotal = round((float) $objective + (float) $theory, 2);
                if (abs($splitTotal - round((float) $component['weight_percentage'], 2)) > 0.001) {
                    throw ValidationException::withMessages([
                        "components.{$index}.objective_max" => 'Objective Max + Theory Max must equal this component weight.',
                    ]);
                }
            }
        }

        return $data;
    }

    private function replaceComponents(AssessmentTemplate $template, array $components): void
    {
        $template->components()->delete();

        foreach (array_values($components) as $index => $component) {
            AssessmentTemplateComponent::withoutTenantScope()->create([
                'tenant_id' => $this->tenantId(),
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
    }

    private function assertOwnTemplate(AssessmentTemplate $template): void
    {
        abort_unless((int) $template->tenant_id === $this->tenantId(), 404);
    }
}
