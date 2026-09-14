<?php

namespace App\Http\Controllers;

use App\Models\AcademicTopic;
use App\Services\AcademicTopicLessonPlanService;
use Illuminate\Http\RedirectResponse;

class AcademicRepositoryLessonPlanController extends Controller
{
    public function store(AcademicTopic $academicTopic, AcademicTopicLessonPlanService $service): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user && ($user->isAdmin() || $user->isTeacher()), 403);

        $plan = $service->save($academicTopic, $user);

        return redirect()
            ->route('lesson-planner.edit', $plan)
            ->with('success', 'The approved repository topic has been saved to Lesson Planner as an editable draft. No AI was used.');
    }
}
