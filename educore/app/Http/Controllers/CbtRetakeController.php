<?php

namespace App\Http\Controllers;

use App\Models\CbtExam;
use App\Models\CbtRetakeAuthorization;
use App\Models\CbtStudentSession;
use App\Models\Student;
use App\Services\Cbt\CbtResultSyncService;
use App\Services\Cbt\CbtRetakeService;
use Illuminate\Http\Request;

class CbtRetakeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin();
        $exams = CbtExam::with('questionBank.subject')->latest()->get();
        $query = CbtStudentSession::with(['student', 'exam.questionBank.subject', 'retakeAuthorization'])->latest();
        if ($request->filled('exam_id')) $query->where('cbt_exam_id', $request->integer('exam_id'));
        if ($request->filled('student')) $query->whereHas('student', fn ($q) => $q->where('first_name', 'like', '%'.$request->student.'%')->orWhere('last_name', 'like', '%'.$request->student.'%')->orWhere('admission_number', 'like', '%'.$request->student.'%'));
        if ($request->filled('status')) $query->where('status', $request->status);
        $attempts = $query->paginate(30)->withQueryString();
        $pendingAuthorizations = CbtRetakeAuthorization::with(['exam', 'student', 'authorizer'])
            ->whereNull('used_at')->whereNull('revoked_at')->latest()->get();
        return view('cbt.retakes', compact('attempts', 'exams', 'pendingAuthorizations'));
    }

    public function authorizeRetake(Request $request, CbtExam $exam, Student $student, CbtRetakeService $retakes)
    {
        $this->authorizeAdmin();
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $retakes->authorize($exam, $student, auth()->user(), $data['reason']);
        return back()->with('success', 'Retake authorized. The student can now begin one new attempt.');
    }

    public function revoke(Request $request, CbtRetakeAuthorization $authorization, CbtRetakeService $retakes)
    {
        $this->authorizeAdmin();
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $retakes->revoke($authorization, auth()->user(), $data['reason']);
        return back()->with('success', 'Unused retake authorization revoked.');
    }

    public function invalidate(Request $request, CbtStudentSession $session, CbtResultSyncService $sync)
    {
        $this->authorizeAdmin();
        $classArmId = $session->student?->current_class_arm_id ?: $session->exam->class_arm_id;
        abort_if(\App\Models\ReportCardPublication::where('class_arm_id', $classArmId)->where('term_id', $session->exam->term_id)->where('status', 'published')->exists(), 423, 'Unpublish the report cards before invalidating an attempt.');
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $session->update(['status' => 'invalidated', 'is_active_result' => false, 'submission_reason' => $data['reason']]);
        $fallback = CbtStudentSession::where('cbt_exam_id', $session->cbt_exam_id)->where('student_id', $session->student_id)->where('is_authorized_attempt', true)->whereNotIn('status', ['invalidated', 'cancelled'])->whereNotNull('grading_completed_at')->latest('attempt_number')->first();
        if ($fallback) $sync->sync($fallback);
        return back()->with('success', 'Attempt invalidated and active result recalculated.');
    }

    private function authorizeAdmin(): void
    {
        $user = auth()->user();
        abort_unless($user && ! $user->isStudent() && ($user->isAdmin() || $user->isSuperAdmin() || $user->can('cbt.authorize_retake')), 403, 'Only authorized school administrators can control retakes.');
    }
}
