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
    private const COMPONENT_TYPES = [
        'coursework', 'test', 'practical', 'exam', 'objective_exam', 'theory_exam', 'final_exam',
    ];

    private const ENTRY_MODES = [
        'manual', 'cbt_objective', 'cbt_aggregate', 'theory_manual',
    ];

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
        $components = array_values($data['components'] ?? []);
        $total = $this->componentTotal($components);

        if (($data['status'] ?? AssessmentTemplate::STATUS_DRAFT) === AssessmentTemplate::STATUS_ACTIVE
            && abs($total - 100.0) > 0.001) {
            throw ValidationException::withMessages([
                'status' => 'A template can be activated only when its components total exactly 100%. Save it as Draft while you build the component structure.',
            ]);
        }

        $template = DB::transaction(function () use ($data, $components, $tenantId) {
            $template = AssessmentTemplate::withoutTenantScope()->create([
                'tenant_id' => $tenantId,
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? AssessmentTemplate::STATUS_DRAFT,
            ]);

            if ($components) {
                $this->replaceComponents($template, $components);
            }

            return $template;
        });

        return redirect()->route('scores.assessment-types', ['selected' => $template->id])
            ->with('success', $components
                ? 'Assessment template created. You can continue adding school-defined components before assigning it.'
                : 'Assessment template created as a shell. Add the school’s assessment components, then activate and assign it.');
    }

    public function update(Request $request, AssessmentTemplate $template, AssessmentTemplateService $service)
    {
        $this->authorizeAdmin();
        $this->assertOwnTemplate($template);
        $data = $this->validateTemplatePayload($request, $template);
        $components = array_key_exists('components', $data)
            ? array_values($data['components'] ?? [])
            : $template->components()->orderBy('sort_order')->get()->map(fn ($component) => [
                'name' => $component->name,
                'weight_percentage' => $component->weight_percentage,
                'component_type' => $component->component_type,
                'entry_mode' => $component->entry_mode,
                'objective_max' => $component->objective_max,
                'theory_max' => $component->theory_max,
            ])->all();

        $total = $this->componentTotal($components);
        if ($data['status'] === AssessmentTemplate::STATUS_ACTIVE && abs($total - 100.0) > 0.001) {
            throw ValidationException::withMessages([
                'status' => "This template totals {$total}%. It must total exactly 100% before it can be active.",
            ]);
        }

        DB::transaction(function () use ($template, $data, $components, $service, $request): void {
            $template->update([
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
            ]);

            if ($request->has('components')) {
                $this->assertStructureEditable($template);
                $this->replaceComponents($template, $components);
            }

            $service->resynchronizeAssignments($template->fresh('components'));
        });

        return redirect()->route('scores.assessment-types', ['selected' => $template->id])
            ->with('success', 'Assessment template updated.');
    }

    public function storeComponent(Request $request, AssessmentTemplate $template)
    {
        $this->authorizeAdmin();
        $this->assertOwnTemplate($template);
        $this->assertStructureEditable($template);

        $data = $this->validateComponentPayload($request);
        $newTotal = round((float) $template->components()->sum('weight_percentage') + (float) $data['weight_percentage'], 2);
        if ($newTotal > 100.0 + 0.001) {
            throw ValidationException::withMessages([
                'weight_percentage' => "This component would make the template total {$newTotal}%. Template components cannot exceed 100%.",
            ]);
        }

        $nextOrder = ((int) $template->components()->max('sort_order')) + 1;
        AssessmentTemplateComponent::withoutTenantScope()->create([
            'tenant_id' => $this->tenantId(),
            'assessment_template_id' => $template->id,
            'name' => trim($data['name']),
            'weight_percentage' => $data['weight_percentage'],
            'component_type' => $data['component_type'],
            'entry_mode' => $data['entry_mode'],
            'objective_max' => $data['objective_max'] ?? null,
            'theory_max' => $data['theory_max'] ?? null,
            'sort_order' => $nextOrder,
        ]);

        if ($template->isActive() && abs($newTotal - 100.0) > 0.001) {
            $template->update(['status' => AssessmentTemplate::STATUS_DRAFT]);
        }

        return redirect()->route('scores.assessment-types', ['selected' => $template->id])
            ->with('success', "Component '{$data['name']}' added. Template total is now {$newTotal}%.");
    }

    public function updateComponent(Request $request, AssessmentTemplate $template, AssessmentTemplateComponent $component)
    {
        $this->authorizeAdmin();
        $this->assertOwnTemplate($template);
        $this->assertOwnComponent($template, $component);
        $this->assertStructureEditable($template);

        $data = $this->validateComponentPayload($request);
        $otherTotal = (float) $template->components()->where('id', '!=', $component->id)->sum('weight_percentage');
        $newTotal = round($otherTotal + (float) $data['weight_percentage'], 2);
        if ($newTotal > 100.0 + 0.001) {
            throw ValidationException::withMessages([
                'weight_percentage' => "This change would make the template total {$newTotal}%. Template components cannot exceed 100%.",
            ]);
        }

        $component->update([
            'name' => trim($data['name']),
            'weight_percentage' => $data['weight_percentage'],
            'component_type' => $data['component_type'],
            'entry_mode' => $data['entry_mode'],
            'objective_max' => $data['objective_max'] ?? null,
            'theory_max' => $data['theory_max'] ?? null,
        ]);

        if ($template->isActive() && abs($newTotal - 100.0) > 0.001) {
            $template->update(['status' => AssessmentTemplate::STATUS_DRAFT]);
        }

        return redirect()->route('scores.assessment-types', ['selected' => $template->id])
            ->with('success', "Component '{$component->name}' updated.");
    }

    public function destroyComponent(AssessmentTemplate $template, AssessmentTemplateComponent $component)
    {
        $this->authorizeAdmin();
        $this->assertOwnTemplate($template);
        $this->assertOwnComponent($template, $component);
        $this->assertStructureEditable($template);

        $name = $component->name;
        $component->delete();
        if ($template->isActive()) {
            $template->update(['status' => AssessmentTemplate::STATUS_DRAFT]);
        }

        return redirect()->route('scores.assessment-types', ['selected' => $template->id])
            ->with('success', "Component '{$name}' removed. The template remains Draft until its components total 100% and you activate it.");
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
            return back()->withErrors(['template' => 'Only an active assessment template can be assigned. Complete the school-defined components to 100% and activate the template first.']);
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
            'components' => ['nullable', 'array'],
            'components.*.name' => ['required', 'string', 'max:100'],
            'components.*.weight_percentage' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'components.*.component_type' => ['required', Rule::in(self::COMPONENT_TYPES)],
            'components.*.entry_mode' => ['required', Rule::in(self::ENTRY_MODES)],
            'components.*.objective_max' => ['nullable', 'numeric', 'min:0.01'],
            'components.*.theory_max' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $components = array_values($data['components'] ?? []);
        $total = $this->componentTotal($components);
        if ($total > 100.0 + 0.001) {
            throw ValidationException::withMessages([
                'components' => "Component weights cannot exceed 100%. Current total: {$total}%.",
            ]);
        }

        foreach ($components as $index => $component) {
            $this->validateSplitComponent($component, "components.{$index}.objective_max");
        }

        return $data;
    }

    private function validateComponentPayload(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'weight_percentage' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'component_type' => ['required', Rule::in(self::COMPONENT_TYPES)],
            'entry_mode' => ['required', Rule::in(self::ENTRY_MODES)],
            'objective_max' => ['nullable', 'numeric', 'min:0.01'],
            'theory_max' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $this->validateSplitComponent($data, 'objective_max');

        return $data;
    }

    private function validateSplitComponent(array $component, string $field): void
    {
        $objective = $component['objective_max'] ?? null;
        $theory = $component['theory_max'] ?? null;
        if (($objective === null) xor ($theory === null)) {
            throw ValidationException::withMessages([
                $field => 'Provide both Objective Max and Theory Max, or leave both blank.',
            ]);
        }

        if ($objective !== null && $theory !== null) {
            $splitTotal = round((float) $objective + (float) $theory, 2);
            if (abs($splitTotal - round((float) $component['weight_percentage'], 2)) > 0.001) {
                throw ValidationException::withMessages([
                    $field => 'Objective Max + Theory Max must equal this component weight.',
                ]);
            }
        }
    }

    private function componentTotal(array $components): float
    {
        return round((float) collect($components)->sum(fn ($component) => (float) ($component['weight_percentage'] ?? 0)), 2);
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

    private function assertStructureEditable(AssessmentTemplate $template): void
    {
        if ($template->assignments()->exists()) {
            throw ValidationException::withMessages([
                'template' => 'This template has already been assigned to a session/class level. Duplicate it to change its component structure without affecting historical scores.',
            ]);
        }
    }

    private function assertOwnTemplate(AssessmentTemplate $template): void
    {
        abort_unless((int) $template->tenant_id === $this->tenantId(), 404);
    }

    private function assertOwnComponent(AssessmentTemplate $template, AssessmentTemplateComponent $component): void
    {
        abort_unless(
            (int) $component->tenant_id === $this->tenantId()
            && (int) $component->assessment_template_id === (int) $template->id,
            404
        );
    }
}
