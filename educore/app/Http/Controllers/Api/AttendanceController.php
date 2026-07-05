<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\ClassArm;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    /**
     * Attendance sheet for a class on a given date: the student list with
     * any statuses already recorded, ready for the app to render/edit.
     */
    public function index(Request $request, int $classArmId)
    {
        $user = $request->user();
        abort_unless(TeacherController::canAccessArm($user, $classArmId), 403, 'You are not assigned to this class.');

        $data = $request->validate([
            'date' => ['nullable', 'date'],
        ]);
        $date = $data['date'] ?? now()->toDateString();

        $arm = ClassArm::with('classLevel')->findOrFail($classArmId);

        $existing = AttendanceRecord::where('class_arm_id', $classArmId)
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('student_id');

        $students = $arm->students()
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->map(function ($st) use ($existing) {
                $rec = $existing->get($st->id);

                return [
                    'id'               => $st->id,
                    'name'             => $st->full_name,
                    'admission_number' => $st->admission_number,
                    'status'           => $rec?->status,
                    'remark'           => $rec?->remark,
                ];
            });

        return response()->json([
            'class'    => ['id' => $arm->id, 'name' => trim(optional($arm->classLevel)->name . ' ' . $arm->name)],
            'date'     => $date,
            'students' => $students,
        ]);
    }

    /**
     * Save attendance for a class and date. Upserts one record per student.
     */
    public function store(Request $request, int $classArmId)
    {
        $user = $request->user();
        abort_unless(TeacherController::canAccessArm($user, $classArmId), 403, 'You are not assigned to this class.');

        $data = $request->validate([
            'date'                 => ['required', 'date', 'before_or_equal:today'],
            'records'              => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer'],
            'records.*.status'     => ['required', Rule::in(['present', 'absent', 'late', 'excused'])],
            'records.*.remark'     => ['nullable', 'string', 'max:200'],
        ]);

        $term = Term::current()->first();
        if (!$term) {
            return response()->json(['message' => 'No current term is set for this school.'], 422);
        }

        $arm = ClassArm::findOrFail($classArmId);
        $validStudentIds = $arm->students()->pluck('id')->flip();

        $saved = 0;
        foreach ($data['records'] as $rec) {
            if (!$validStudentIds->has($rec['student_id'])) {
                continue; // not in this class — skip silently
            }

            AttendanceRecord::updateOrCreate(
                [
                    'student_id'      => $rec['student_id'],
                    'class_arm_id'    => $classArmId,
                    'attendance_date' => $data['date'],
                ],
                [
                    'term_id'   => $term->id,
                    'marked_by' => $user->id,
                    'status'    => $rec['status'],
                    'remark'    => $rec['remark'] ?? null,
                ]
            );
            $saved++;
        }

        return response()->json([
            'message' => "Attendance saved for {$saved} students.",
            'saved'   => $saved,
            'date'    => $data['date'],
        ]);
    }
}
