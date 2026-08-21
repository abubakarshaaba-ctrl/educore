<?php

namespace App\Http\Controllers;

use App\Models\CbtExam;
use App\Models\CbtStudentSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CbtReportController extends Controller
{
    public function export(Request $request, CbtExam $exam, string $format)
    {
        $this->authorizeExam($exam);
        abort_unless(in_array($format, ['csv', 'pdf'], true), 404);
        $exam->load(['sections', 'questionBank.subject', 'classArm.classLevel', 'classArms.classLevel', 'term']);
        $sessions = CbtStudentSession::with(['student', 'sectionAttempts.section'])->where('cbt_exam_id', $exam->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('attempt_number'), fn ($q) => $q->where('attempt_number', $request->integer('attempt_number')))
            ->when($request->filled('student'), fn ($q) => $q->whereHas('student', fn ($student) => $student->where(fn ($name) => $name->where('first_name', 'like', '%'.$request->student.'%')->orWhere('last_name', 'like', '%'.$request->student.'%')->orWhere('admission_number', 'like', '%'.$request->student.'%'))))
            ->orderBy('student_id')->orderBy('attempt_number')->get();

        if ($format === 'pdf') {
            return Pdf::loadView('cbt.report-pdf', compact('exam', 'sessions'))->setPaper('a4', 'landscape')->download('cbt-results-'.$exam->id.'.pdf');
        }
        return response()->streamDownload(function () use ($exam, $sessions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_merge(['Student', 'Admission No.', 'Attempt', 'Status'], $exam->sections->pluck('name')->all(), ['Raw Score', 'Maximum', 'Percentage']));
            foreach ($sessions as $session) {
                $sectionScores = $exam->sections->map(function ($section) use ($session) {
                    $attempt = $session->sectionAttempts->firstWhere('cbt_exam_section_id', $section->id);
                    return $attempt && $attempt->status === 'scored' ? $attempt->raw_score.'/'.$attempt->maximum_score : 'Pending';
                })->all();
                fputcsv($handle, array_merge([$session->student->full_name, $session->student->admission_number, $session->attempt_number, $session->status], $sectionScores, [$session->raw_score, $session->maximum_score, $session->percentage]));
            }
            fclose($handle);
        }, 'cbt-results-'.$exam->id.'.csv', ['Content-Type' => 'text/csv']);
    }

    private function authorizeExam(CbtExam $exam): void
    {
        $user = auth()->user();
        abort_unless($user && ! $user->isStudent() && (int) $user->tenant_id === (int) $exam->tenant_id && ($user->isAdmin() || $user->isSuperAdmin() || \App\Models\ClassArmSubject::where('teacher_id', $user->id)->whereIn('class_arm_id', $exam->assignedClassArmIds())->where('subject_id', $exam->questionBank->subject_id)->exists()), 403);
    }
}
