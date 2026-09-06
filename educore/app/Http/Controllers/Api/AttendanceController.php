<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\ClassArm;
use App\Models\Term;
use App\Services\Mobile\MobileClassAccessService;
use App\Services\Mobile\MobileIdempotencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly MobileClassAccessService $access,
        private readonly MobileIdempotencyService $idempotency,
    ) {}

    /**
     * Attendance sheet for a class on a given date: the student list with
     * any statuses already recorded, ready for the app to render/edit.
     */
    public function index(Request $request, int $classArmId)
    {
        $user = $request->user();
        abort_unless($this->access->canMarkAttendance($user, $classArmId), 403, 'Only an authorized school administrator or the assigned form teacher can manage student attendance.');

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
                    'id' => $st->id,
                    'name' => $st->full_name,
                    'admission_number' => $st->admission_number,
                    'status' => $rec?->status,
                    'remark' => $rec?->remark,
                ];
            });

        return response()->json([
            'contract_version' => 2,
            'generated_at' => now()->toIso8601String(),
            'class' => ['id' => $arm->id, 'name' => trim(optional($arm->classLevel)->name.' '.$arm->name)],
            'date' => $date,
            'version' => $this->sheetVersion($existing),
            'capabilities' => ['save' => true],
            'students' => $students,
        ]);
    }

    /**
     * Save attendance for a class and date. Upserts one record per student.
     */
    public function store(Request $request, int $classArmId)
    {
        $user = $request->user();
        abort_unless($this->access->canMarkAttendance($user, $classArmId), 403, 'Only an authorized school administrator or the assigned form teacher can manage student attendance.');

        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'version' => ['nullable', 'string', 'max:64'],
            'request_id' => ['nullable', 'uuid'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer'],
            'records.*.status' => ['required', Rule::in(['present', 'absent', 'late', 'excused'])],
            'records.*.remark' => ['nullable', 'string', 'max:200'],
        ]);

        $term = Term::current()->first();
        if (! $term) {
            return response()->json(['message' => 'No current term is set for this school.'], 422);
        }

        $arm = ClassArm::findOrFail($classArmId);
        $validStudentIds = $arm->students()->active()->pluck('id')->map(fn ($id) => (int) $id);
        $submittedStudentIds = collect($data['records'])->pluck('student_id')->map(fn ($id) => (int) $id);
        $invalidIds = $submittedStudentIds->diff($validStudentIds)->values();
        if ($invalidIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'records' => 'Every attendance record must belong to an active student in this class.',
            ]);
        }

        $requestId = $data['request_id'] ?? (string) \Illuminate\Support\Str::uuid();
        $payload = ['class_arm_id' => $classArmId, 'request_id' => $requestId] + $data;
        $response = $this->idempotency->execute(
            $user,
            "attendance.class.{$classArmId}.{$data['date']}.save",
            $requestId,
            $payload,
            function () use ($data, $classArmId, $term, $user, $requestId): array {
                $result = DB::transaction(function () use ($data, $classArmId, $term, $user): array {
                    $current = AttendanceRecord::where('class_arm_id', $classArmId)
                        ->whereDate('attendance_date', $data['date'])
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('student_id');

                    if (! empty($data['version']) && ! hash_equals($data['version'], $this->sheetVersion($current))) {
                        abort(409, 'Attendance changed on the server. Reload the sheet before saving your draft.');
                    }

                    foreach ($data['records'] as $record) {
                        AttendanceRecord::updateOrCreate(
                            [
                                'student_id' => $record['student_id'],
                                'class_arm_id' => $classArmId,
                                'attendance_date' => $data['date'],
                            ],
                            [
                                'term_id' => $term->id,
                                'marked_by' => $user->id,
                                'status' => $record['status'],
                                'remark' => $record['remark'] ?? null,
                            ]
                        );
                    }

                    $savedRecords = AttendanceRecord::where('class_arm_id', $classArmId)
                        ->whereDate('attendance_date', $data['date'])
                        ->get()
                        ->keyBy('student_id');

                    return [
                        'saved' => count($data['records']),
                        'version' => $this->sheetVersion($savedRecords),
                        'summary' => [
                            'present' => $savedRecords->where('status', 'present')->count(),
                            'absent' => $savedRecords->where('status', 'absent')->count(),
                            'late' => $savedRecords->where('status', 'late')->count(),
                            'excused' => $savedRecords->where('status', 'excused')->count(),
                        ],
                    ];
                });

                return [
                    'contract_version' => 2,
                    'message' => "Attendance saved for {$result['saved']} students.",
                    'saved' => $result['saved'],
                    'date' => $data['date'],
                    'version' => $result['version'],
                    'request_id' => $requestId,
                    'summary' => $result['summary'],
                ];
            },
        );

        return response()->json($response);
    }

    private function sheetVersion($records): string
    {
        if ($records->isEmpty()) {
            return 'empty';
        }

        return hash('sha256', $records->sortKeys()->map(function (AttendanceRecord $record): string {
            return implode(':', [
                $record->student_id,
                $record->status,
                $record->remark ?? '',
                $record->updated_at?->format('Y-m-d H:i:s.u') ?? '',
            ]);
        })->implode('|'));
    }
}
