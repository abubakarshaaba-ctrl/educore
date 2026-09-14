<?php

namespace App\Http\Controllers;

use App\Models\AcademicTopic;
use App\Services\AcademicTopicStudentNoteService;
use Illuminate\Http\RedirectResponse;

class AcademicRepositoryStudentNoteController extends Controller
{
    public function store(AcademicTopic $academicTopic, AcademicTopicStudentNoteService $service): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user && ($user->isAdmin() || $user->isTeacher()), 403);

        $revision = $service->save($academicTopic, $user);
        $plan = $revision->lessonPlan;

        return redirect()
            ->route('lesson-planner.show', $plan)
            ->with('success', 'The approved repository student note has been saved as an editable Lesson Planner note draft. No AI was used.');
    }
}
