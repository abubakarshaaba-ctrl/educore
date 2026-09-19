<?php

namespace App\Services;

use App\Models\ParallelCurriculum;
use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumClassArm;
use App\Models\ParallelCurriculumEnrolment;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ParallelCurriculumStudentAssignmentImportService
{
    public function __construct(
        private readonly ParallelCurriculumService $parallel,
    ) {}

    public function import(
        int $tenantId,
        int $curriculumId,
        int $sessionId,
        UploadedFile $file,
        bool $dryRun = false,
    ): array {
        $curriculum = ParallelCurriculum::with([
            'classes' => fn ($query) => $query
                ->where('is_active', true)
                ->with(['arms' => fn ($armQuery) => $armQuery
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')])
                ->orderBy('sort_order')
                ->orderBy('name'),
        ])
            ->where('tenant_id', $tenantId)
            ->findOrFail($curriculumId);

        abort_unless($curriculum->is_active, 422, 'This parallel curriculum programme is inactive.');
        abort_if(
            $curriculum->classes->isEmpty(),
            422,
            'Create an active parallel class before importing student assignments.'
        );

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'assignment_file' =>
                    'EduCore could not read the uploaded file. Use the assignment template or a valid CSV/XLS/XLSX file.',
            ]);
        }

        if (count($rows) < 2) {
            throw ValidationException::withMessages([
                'assignment_file' =>
                    'The assignment file must contain a header row and at least one student row.',
            ]);
        }

        $header = array_map(
            fn ($value) => str((string) $value)
                ->trim()
                ->lower()
                ->replace([' ', '-'], '_')
                ->toString(),
            array_shift($rows)
        );

        $admissionIndex = array_search('admission_number', $header, true);
        $classIndex = array_search('parallel_class', $header, true);
        if ($classIndex === false) {
            $classIndex = array_search('parallel_class_code', $header, true);
        }

        $armIndex = array_search('parallel_arm', $header, true);
        if ($armIndex === false) {
            $armIndex = array_search('parallel_arm_code', $header, true);
        }

        if ($admissionIndex === false || $classIndex === false || $armIndex === false) {
            throw ValidationException::withMessages([
                'assignment_file' =>
                    'Required columns are admission_number, parallel_class (or parallel_class_code), and parallel_arm (or parallel_arm_code).',
            ]);
        }

        $entries = [];
        $seenAdmissions = [];
        $errors = [];

        foreach ($rows as $offset => $row) {
            $rowNumber = $offset + 2;
            $admissionNumber = trim((string) ($row[$admissionIndex] ?? ''));
            $parallelClass = trim((string) ($row[$classIndex] ?? ''));
            $parallelArm = trim((string) ($row[$armIndex] ?? ''));

            if ($admissionNumber === '' && $parallelClass === '' && $parallelArm === '') {
                continue;
            }

            if ($admissionNumber === '' || $parallelClass === '' || $parallelArm === '') {
                $errors[] =
                    "Row {$rowNumber}: admission_number, parallel_class and parallel_arm are required.";
                continue;
            }

            $admissionKey = mb_strtolower($admissionNumber);
            if (isset($seenAdmissions[$admissionKey])) {
                $errors[] =
                    "Row {$rowNumber}: admission number {$admissionNumber} appears more than once.";
                continue;
            }

            $seenAdmissions[$admissionKey] = true;
            $entries[] = [
                'row' => $rowNumber,
                'admission_number' => $admissionNumber,
                'admission_key' => $admissionKey,
                'parallel_class' => $parallelClass,
                'class_key' => mb_strtolower($parallelClass),
                'parallel_arm' => $parallelArm,
                'arm_key' => mb_strtolower($parallelArm),
            ];
        }

        if ($entries === []) {
            throw ValidationException::withMessages([
                'assignment_file' =>
                    $errors ?: ['No usable student assignment rows were found in the uploaded file.'],
            ]);
        }

        $students = Student::active()
            ->where('tenant_id', $tenantId)
            ->whereIn('admission_number', collect($entries)->pluck('admission_number')->all())
            ->get()
            ->keyBy(
                fn (Student $student) =>
                    mb_strtolower(trim((string) $student->admission_number))
            );

        $classesByName = $curriculum->classes->keyBy(
            fn (ParallelCurriculumClass $class) =>
                mb_strtolower(trim($class->name))
        );
        $classesByCode = $curriculum->classes
            ->filter(fn (ParallelCurriculumClass $class) => filled($class->code))
            ->keyBy(
                fn (ParallelCurriculumClass $class) =>
                    mb_strtolower(trim((string) $class->code))
            );

        $assignments = [];

        foreach ($entries as $entry) {
            $student = $students->get($entry['admission_key']);
            if (! $student) {
                $errors[] =
                    "Row {$entry['row']}: active student {$entry['admission_number']} was not found.";
                continue;
            }

            $class =
                $classesByName->get($entry['class_key'])
                ?: $classesByCode->get($entry['class_key']);

            if (! $class) {
                $errors[] =
                    "Row {$entry['row']}: parallel class {$entry['parallel_class']} was not found in {$curriculum->name}.";
                continue;
            }

            $arm = $class->arms->first(
                fn (ParallelCurriculumClassArm $item) =>
                    mb_strtolower(trim($item->name)) === $entry['arm_key']
                    || (
                        filled($item->code)
                        && mb_strtolower(trim((string) $item->code)) === $entry['arm_key']
                    )
            );

            if (! $arm) {
                $errors[] =
                    "Row {$entry['row']}: arm {$entry['parallel_arm']} was not found in {$class->name}.";
                continue;
            }

            $assignments[] = [$student, $class, $arm, $entry['row']];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'assignment_file' => array_slice($errors, 0, 20),
            ]);
        }

        $studentIds = collect($assignments)
            ->map(fn (array $assignment) => (int) $assignment[0]->id)
            ->unique()
            ->values();

        $existingEnrolments = ParallelCurriculumEnrolment::where('tenant_id', $tenantId)
            ->where('parallel_curriculum_id', $curriculum->id)
            ->where('session_id', $sessionId)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $destinationLocks = [];

        foreach ($assignments as [$student, $class, $arm, $rowNumber]) {
            $existing = $existingEnrolments->get($student->id);
            $changesPlacement =
                ! $existing
                || (int) $existing->parallel_curriculum_class_id !== (int) $class->id
                || (int) $existing->parallel_curriculum_class_arm_id !== (int) $arm->id;

            if (! $changesPlacement) {
                continue;
            }

            if ($existing && $this->parallel->enrolmentPlacementLocked($existing)) {
                $errors[] =
                    "Row {$rowNumber}: {$student->admission_number} cannot be moved because the current parallel-class result is published.";
                continue;
            }

            if (! array_key_exists($class->id, $destinationLocks)) {
                $destinationLocks[$class->id] =
                    $this->parallel->classPlacementLocked($class, $sessionId);
            }

            if ($destinationLocks[$class->id]) {
                $errors[] =
                    "Row {$rowNumber}: {$class->name} already has a published result for this session. Unpublish it before adding or moving students.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'assignment_file' => array_slice($errors, 0, 20),
            ]);
        }

        $assignmentsByArm = collect($assignments)
            ->groupBy(fn (array $assignment) => $assignment[2]->id);

        foreach ($assignmentsByArm as $armId => $armAssignments) {
            $arm = $armAssignments->first()[2];
            if (! $arm->capacity) {
                continue;
            }

            $movingStudentIds = $armAssignments
                ->map(fn (array $assignment) => (int) $assignment[0]->id)
                ->values();

            $existingInArm = ParallelCurriculumEnrolment::where('tenant_id', $tenantId)
                ->where('parallel_curriculum_class_arm_id', $armId)
                ->where('session_id', $sessionId)
                ->where('is_active', true)
                ->whereNotIn('student_id', $movingStudentIds)
                ->count();

            if ($existingInArm + $movingStudentIds->count() > (int) $arm->capacity) {
                throw ValidationException::withMessages([
                    'assignment_file' =>
                        "{$arm->full_name} does not have enough capacity for the imported learners.",
                ]);
            }
        }

        if (! $dryRun) {
            DB::transaction(function () use (
                $assignments,
                $tenantId,
                $sessionId
            ): void {
                foreach ($assignments as [$student, $class, $arm]) {
                    ParallelCurriculumEnrolment::updateOrCreate(
                        [
                            'tenant_id' => $tenantId,
                            'parallel_curriculum_id' => $class->parallel_curriculum_id,
                            'student_id' => $student->id,
                            'session_id' => $sessionId,
                        ],
                        [
                            'parallel_curriculum_class_id' => $class->id,
                            'parallel_curriculum_class_arm_id' => $arm->id,
                            'is_active' => true,
                        ]
                    );
                }
            });
        }

        return [
            'count' => count($assignments),
            'curriculum_id' => (int) $curriculum->id,
            'curriculum_name' => (string) $curriculum->name,
            'session_id' => $sessionId,
            'dry_run' => $dryRun,
        ];
    }
}
