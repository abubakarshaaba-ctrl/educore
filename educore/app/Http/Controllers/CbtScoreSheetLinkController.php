<?php

namespace App\Http\Controllers;

use App\Models\AssessmentType;
use App\Models\CbtExam;
use App\Models\CbtStudentSession;
use App\Services\Cbt\CbtResultSyncService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CbtScoreSheetLinkController extends Controller
{
    public function update(Request $request, CbtExam $exam, CbtResultSyncService $sync)
    {
        $user = auth()->user();
        abort_unless(
            $user && ! $user->isStudent() && ($user->isSuperAdmin() || $user->canAccessModule('cbt')),
            403,
            'You are not authorized to configure CBT score-sheet synchronization.'
        );
        abort_unless((int) $exam->tenant_id === (int) $user->tenant_id, 404);

        $data = $request->validate([
            'assessment_type_id' => [
                'nullable',
                'integer',
                Rule::exists('assessment_types', 'id')->where('tenant_id', $exam->tenant_id),
            ],
        ]);

        $assessment = null;
        if (! empty($data['assessment_type_id'])) {
            $assessment = AssessmentType::withoutTenantScope()
                ->where('tenant_id', $exam->tenant_id)
                ->findOrFail($data['assessment_type_id']);

            if (! $assessment->is_exam || (int) $assessment->term_id !== (int) $exam->term_id) {
                throw ValidationException::withMessages([
                    'assessment_type_id' => 'Select an Exam component from the same academic term as this CBT examination.',
                ]);
            }

            $classLevelIds = $exam->classArms()->pluck('class_arms.class_level_id');
            if ($classLevelIds->isEmpty() && $exam->classArm) {
                $classLevelIds = collect([(int) $exam->classArm->class_level_id]);
            }
            $classLevelIds = $classLevelIds->map(fn ($id) => (int) $id)->unique()->values();

            $configuredLevelIds = $assessment->classLevels()->pluck('class_levels.id')
                ->map(fn ($id) => (int) $id)->unique()->values();
            if ($configuredLevelIds->isNotEmpty() && $classLevelIds->diff($configuredLevelIds)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'assessment_type_id' => 'The selected Exam component is not configured for every class assigned to this CBT examination.',
                ]);
            }
        }

        $exam->update(['assessment_type_id' => $assessment?->id]);

        if (! $assessment) {
            return back()->with('success', 'CBT examination disconnected from Score Entry. Existing synchronized scores were left unchanged for audit safety.');
        }

        $synced = 0;
        $skipped = 0;
        CbtStudentSession::where('cbt_exam_id', $exam->id)
            ->whereNotNull('grading_completed_at')
            ->where('is_authorized_attempt', true)
            ->whereNotIn('status', ['invalidated', 'cancelled'])
            ->orderBy('student_id')
            ->orderByDesc('attempt_number')
            ->get()
            ->unique('student_id')
            ->each(function (CbtStudentSession $session) use ($sync, &$synced, &$skipped): void {
                $result = $sync->sync($session);
                if ($result['synced'] ?? false) {
                    $synced++;
                } else {
                    $skipped++;
                }
            });

        $message = "CBT examination linked to '{$assessment->name}' ({$assessment->weight_percentage} marks).";
        $message .= " {$synced} completed student result(s) synchronized to Score Entry.";
        if ($skipped > 0) {
            $message .= " {$skipped} result(s) could not be synchronized yet, usually because marking is incomplete or the published result is locked.";
        }

        return back()->with('success', $message);
    }
}
