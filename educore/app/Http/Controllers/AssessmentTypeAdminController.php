<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\AssessmentType;
use App\Models\Score;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $assessmentTypes = AssessmentType::with(['term.session'])
            ->latest()
            ->get();
        $sessions = AcademicSession::orderBy('name')->get();

        return view('scores.assessment-types-admin', compact(
            'assessmentTypes',
            'terms',
            'sessions'
        ));
    }

    public function update(Request $request, AssessmentType $at)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'term_id' => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
            'name' => ['required', 'string', 'max:100'],
            'weight_percentage' => ['required', 'integer', 'min:1', 'max:100'],
            'is_exam' => ['nullable', 'boolean'],
        ]);

        $otherWeight = AssessmentType::where('term_id', $validated['term_id'])
            ->where('id', '!=', $at->id)
            ->sum('weight_percentage');

        if ($otherWeight + (int) $validated['weight_percentage'] > 100) {
            return back()->withErrors([
                'weight_percentage' => 'The assessment weights for the selected term cannot exceed 100%.',
            ]);
        }

        $hasScores = Score::where('assessment_type_id', $at->id)->exists();
        if ($hasScores && ((int) $validated['term_id'] !== (int) $at->term_id
            || (int) $validated['weight_percentage'] !== (int) $at->weight_percentage)) {
            return back()->withErrors([
                'assessment_type' => 'This assessment type already has student scores. Its term or weight cannot be changed; create a new assessment type instead.',
            ]);
        }

        $at->update([
            'term_id' => $validated['term_id'],
            'name' => trim($validated['name']),
            'weight_percentage' => $validated['weight_percentage'],
            'is_exam' => $request->boolean('is_exam'),
        ]);

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
        $currentTotal = AssessmentType::where('term_id', $targetTermId)->sum('weight_percentage');

        if ($currentTotal + $weight > 100) {
            return back()->withErrors([
                'weight_percentage' => 'The assessment weights for the target term cannot exceed 100%.',
            ]);
        }

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

        AssessmentType::create($copy);

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
}
