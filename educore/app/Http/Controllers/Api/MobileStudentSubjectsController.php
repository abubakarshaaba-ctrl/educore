<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Student;
use App\Models\StudentSubjectSelection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class MobileStudentSubjectsController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->isStudent(), 403, 'Student portal access only.');

        $student = Student::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->first();
        abort_unless($student, 403, 'No student profile is linked to this account.');

        $session = Schema::hasTable('academic_sessions')
            ? AcademicSession::query()
                ->where('tenant_id', $user->tenant_id)
                ->where('is_current', true)
                ->first()
            : null;

        $selections = collect();
        if ($student->status === Student::STATUS_ACTIVE && Schema::hasTable('student_subject_selections')) {
            $query = StudentSubjectSelection::query()
                ->where('tenant_id', $user->tenant_id)
                ->where('student_id', $student->id)
                ->where('is_active', true)
                ->with([
                    'subject:id,tenant_id,name,code,is_active',
                    'academicTrack:id,tenant_id,name',
                    'session:id,tenant_id,name',
                ]);

            if ($session) {
                $query->where('session_id', $session->id);
            } else {
                // Without an authoritative current session, do not mix historical
                // selections into the student's current mobile workspace.
                $query->whereRaw('1 = 0');
            }

            $selections = $query
                ->orderBy('selection_type')
                ->orderBy('subject_id')
                ->get()
                ->filter(fn (StudentSubjectSelection $selection): bool =>
                    $selection->subject !== null
                    && (int) $selection->subject->tenant_id === (int) $user->tenant_id
                    && (bool) $selection->subject->is_active
                )
                ->values();
        }

        $records = $selections->map(function (StudentSubjectSelection $selection) use ($session): array {
            $type = strtolower((string) ($selection->selection_type ?: 'selected'));
            $track = $selection->academicTrack?->name;

            return [
                'id' => (string) $selection->id,
                'title' => $selection->subject?->name ?? 'Subject',
                'subtitle' => $selection->subject?->code ?: ($track ?: 'Current subject'),
                'status' => $type,
                'fields' => [
                    ['label' => 'Type', 'value' => ucfirst(str_replace('_', ' ', $type))],
                    ['label' => 'Track', 'value' => $track ?: 'All tracks'],
                    ['label' => 'Session', 'value' => $selection->session?->name ?: ($session?->name ?: 'Not set')],
                ],
            ];
        });

        return response()->json([
            'contract_version' => 1,
            'module' => [
                'key' => 'subjects',
                'title' => 'My Subjects',
                'description' => 'Subjects you are taking in the current academic session',
                'can_manage' => false,
                'mobile_policy' => 'read_only',
            ],
            'metrics' => [
                ['key' => 'subjects', 'label' => 'Subjects', 'value' => (string) $records->count(), 'format' => 'number', 'tone' => 'navy'],
                ['key' => 'compulsory', 'label' => 'Compulsory', 'value' => (string) $selections->where('selection_type', 'compulsory')->count(), 'format' => 'number', 'tone' => 'success'],
                ['key' => 'elective', 'label' => 'Elective', 'value' => (string) $selections->where('selection_type', 'elective')->count(), 'format' => 'number', 'tone' => 'blue'],
            ],
            'sections' => [[
                'key' => 'subjects',
                'title' => 'Current subjects',
                'count' => $records->count(),
                'records' => $records,
            ]],
            'generated_at' => now()->toIso8601String(),
        ]);
    }
}
