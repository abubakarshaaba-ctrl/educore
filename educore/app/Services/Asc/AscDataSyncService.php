<?php

namespace App\Services\Asc;

use App\Models\AcademicSession;
use App\Models\AscInfrastructure;
use App\Models\AscSectionData;
use App\Models\StudentEnrollment;
use App\Models\Tenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AscDataSyncService
{
    private const STAFF_ROLES = [
        'admin', 'principal', 'head', 'head_teacher', 'vice_principal',
        'academic_administrator', 'admission_officer', 'form_teacher',
        'asst_form_teacher', 'subject_teacher', 'form_subject_teacher',
        'accountant', 'health_officer', 'librarian',
    ];

    private const TEACHING_ROLES = [
        'form_teacher', 'asst_form_teacher', 'subject_teacher', 'form_subject_teacher',
    ];

    private const MANAGEMENT_ROLES = [
        'admin', 'principal', 'head', 'head_teacher', 'vice_principal', 'academic_administrator',
    ];

    private const NON_TEACHING_ROLES = [
        'accountant', 'health_officer', 'librarian', 'admission_officer',
    ];

    public function build(Tenant $tenant, int $censusYear, CarbonImmutable $referenceDate): array
    {
        $session = AcademicSession::where('tenant_id', $tenant->id)
            ->where('is_current', true)
            ->first();

        $infrastructure = AscInfrastructure::where('tenant_id', $tenant->id)
            ->where('census_year', $censusYear)
            ->first();

        $officialSections = AscSectionData::where('tenant_id', $tenant->id)
            ->where('census_year', $censusYear)
            ->get()
            ->keyBy('section');

        $studentRows = $this->studentRows($tenant->id);
        $staff = $this->staffRows($tenant->id);

        $enrolment = $this->enrolmentBreakdown($studentRows);
        $ageDistribution = $this->ageDistribution($studentRows, $referenceDate);
        $staffSummary = $this->staffSummary($staff);
        $completeness = $this->completeness($tenant, $studentRows, $staff, $infrastructure, $officialSections);
        $reconciliation = $this->reconciliation($tenant->id, $studentRows);

        return [
            'session_id' => $session?->id,
            'auto_data' => [
                'metadata' => [
                    'census_year' => $censusYear,
                    'reference_date' => $referenceDate->toDateString(),
                    'generated_at' => now()->toIso8601String(),
                    'session_id' => $session?->id,
                    'session_name' => $session?->name,
                ],
                'school' => [
                    'name' => $tenant->name,
                    'address' => $tenant->address,
                    'phone' => $tenant->phone,
                    'email' => $tenant->email,
                    'state' => $infrastructure?->school_state,
                    'lga' => $infrastructure?->school_lga,
                    'senatorial_district' => $infrastructure?->school_senatorial_district,
                    'ownership' => $infrastructure?->school_ownership,
                    'school_type' => $infrastructure?->school_type,
                ],
                'enrolment' => $enrolment,
                'age_distribution' => $ageDistribution,
                'staff' => $staffSummary,
                'special_needs' => [
                    'total' => $studentRows->where('has_special_needs', true)->count(),
                    'male' => $studentRows->where('has_special_needs', true)->filter(fn ($row) => $this->gender($row->gender) === 'male')->count(),
                    'female' => $studentRows->where('has_special_needs', true)->filter(fn ($row) => $this->gender($row->gender) === 'female')->count(),
                ],
            ],
            'manual_data' => [
                'legacy_infrastructure' => $this->infrastructureSnapshot($infrastructure),
                'official_sections' => $officialSections->map(fn ($record) => [
                    'section' => $record->section,
                    'is_complete' => (bool) $record->is_complete,
                    'data' => $record->data ?? [],
                    'updated_at' => optional($record->updated_at)->toIso8601String(),
                ])->all(),
            ],
            'completeness' => $completeness,
            'reconciliation' => $reconciliation,
        ];
    }

    private function studentRows(int $tenantId): Collection
    {
        return StudentEnrollment::query()
            ->join('students', 'students.id', '=', 'student_enrollments.student_id')
            ->leftJoin('class_arms', 'class_arms.id', '=', 'student_enrollments.class_arm_id')
            ->leftJoin('class_levels', 'class_levels.id', '=', 'class_arms.class_level_id')
            ->where('student_enrollments.tenant_id', $tenantId)
            ->where('student_enrollments.is_current', true)
            ->select([
                'student_enrollments.id as enrollment_id',
                'student_enrollments.student_id',
                'student_enrollments.class_arm_id',
                'students.gender',
                'students.date_of_birth',
                'students.has_special_needs',
                'students.special_needs_type',
                'class_levels.id as class_level_id',
                'class_levels.name as level_name',
                'class_levels.section',
                'class_levels.order_index',
            ])
            ->orderBy('class_levels.order_index')
            ->get();
    }

    private function staffRows(int $tenantId): Collection
    {
        return User::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereIn('role', self::STAFF_ROLES)
            ->get(['id', 'name', 'role', 'gender', 'qualification', 'qualifications', 'employment_status']);
    }

    private function enrolmentBreakdown(Collection $rows): array
    {
        $levels = $rows
            ->groupBy(fn ($row) => ($row->section ?: 'Unspecified') . '|' . ($row->level_name ?: 'Unassigned'))
            ->map(function (Collection $group) {
                $first = $group->first();
                $male = $group->filter(fn ($row) => $this->gender($row->gender) === 'male')->count();
                $female = $group->filter(fn ($row) => $this->gender($row->gender) === 'female')->count();
                $unknown = $group->count() - $male - $female;

                return [
                    'section' => $first->section ?: 'Unspecified',
                    'level_name' => $first->level_name ?: 'Unassigned',
                    'order_index' => $first->order_index,
                    'male' => $male,
                    'female' => $female,
                    'unknown_gender' => $unknown,
                    'total' => $group->count(),
                ];
            })
            ->sortBy('order_index')
            ->values()
            ->all();

        $male = $rows->filter(fn ($row) => $this->gender($row->gender) === 'male')->count();
        $female = $rows->filter(fn ($row) => $this->gender($row->gender) === 'female')->count();

        return [
            'total' => $rows->count(),
            'male' => $male,
            'female' => $female,
            'unknown_gender' => $rows->count() - $male - $female,
            'by_level' => $levels,
        ];
    }

    private function ageDistribution(Collection $rows, CarbonImmutable $referenceDate): array
    {
        return $rows
            ->filter(fn ($row) => !empty($row->date_of_birth))
            ->map(function ($row) use ($referenceDate) {
                $dob = CarbonImmutable::parse($row->date_of_birth)->startOfDay();
                $age = $dob->greaterThan($referenceDate)
                    ? null
                    : (int) floor($dob->diffInYears($referenceDate, true));

                return [
                    'age' => $age,
                    'gender' => $this->gender($row->gender),
                ];
            })
            ->filter(fn (array $row) => $row['age'] !== null)
            ->groupBy('age')
            ->map(function (Collection $group, $age) {
                $male = $group->where('gender', 'male')->count();
                $female = $group->where('gender', 'female')->count();

                return [
                    'age' => (int) $age,
                    'male' => $male,
                    'female' => $female,
                    'unknown_gender' => $group->count() - $male - $female,
                    'total' => $group->count(),
                ];
            })
            ->sortBy('age')
            ->values()
            ->all();
    }

    private function staffSummary(Collection $staff): array
    {
        $male = $staff->filter(fn ($user) => $this->gender($user->gender) === 'male')->count();
        $female = $staff->filter(fn ($user) => $this->gender($user->gender) === 'female')->count();

        $byQualification = $staff
            ->groupBy(fn ($user) => trim((string) ($user->qualification ?: 'Not Specified')))
            ->map(fn (Collection $group, $qualification) => [
                'qualification' => $qualification,
                'total' => $group->count(),
            ])
            ->values()
            ->all();

        return [
            'total' => $staff->count(),
            'male' => $male,
            'female' => $female,
            'unknown_gender' => $staff->count() - $male - $female,
            'teaching' => $staff->whereIn('role', self::TEACHING_ROLES)->count(),
            'management' => $staff->whereIn('role', self::MANAGEMENT_ROLES)->count(),
            'non_teaching' => $staff->whereIn('role', self::NON_TEACHING_ROLES)->count(),
            'by_qualification' => $byQualification,
        ];
    }

    private function completeness(
        Tenant $tenant,
        Collection $students,
        Collection $staff,
        ?AscInfrastructure $infrastructure,
        Collection $officialSections
    ): array {
        $issues = [];
        $blocking = [];
        $checks = [];

        $this->addCheck($checks, $issues, 'School address', !blank($tenant->address), 'Complete the school address in School Settings.');
        $this->addCheck($checks, $issues, 'School phone', !blank($tenant->phone), 'Complete the school phone number in School Settings.');
        $this->addCheck($checks, $issues, 'School email', !blank($tenant->email), 'Complete the school email address in School Settings.');

        $missingDob = $students->filter(fn ($row) => blank($row->date_of_birth))->count();
        $missingGender = $students->filter(fn ($row) => !in_array($this->gender($row->gender), ['male', 'female'], true))->count();
        $missingClass = $students->filter(fn ($row) => blank($row->class_arm_id))->count();

        $this->addCountCheck($checks, $issues, $blocking, 'Student date of birth', $missingDob, 'student(s) have no date of birth.');
        $this->addCountCheck($checks, $issues, $blocking, 'Student gender', $missingGender, 'student(s) have no valid gender.');
        $this->addCountCheck($checks, $issues, $blocking, 'Student class assignment', $missingClass, 'student(s) have no current class assignment.');

        $missingStaffGender = $staff->filter(fn ($user) => !in_array($this->gender($user->gender), ['male', 'female'], true))->count();
        $missingStaffQualification = $staff->filter(fn ($user) => blank($user->qualification) && empty($user->qualifications))->count();

        $this->addCountCheck($checks, $issues, $blocking, 'Staff gender', $missingStaffGender, 'staff record(s) have no valid gender.', false);
        $this->addCountCheck($checks, $issues, $blocking, 'Staff qualification', $missingStaffQualification, 'staff record(s) have no qualification.', false);

        $infraOk = $infrastructure !== null;
        $this->addCheck($checks, $issues, 'Infrastructure profile', $infraOk, 'Complete and save the ASC Infrastructure & Profile section.');

        if ($infrastructure) {
            foreach ([
                'school_state' => 'School state',
                'school_lga' => 'School LGA',
                'school_ownership' => 'School ownership',
                'school_type' => 'School type',
                'water_source' => 'Water source',
                'electricity_source' => 'Electricity source',
                'fence_type' => 'Fence type',
            ] as $field => $label) {
                $this->addCheck($checks, $issues, $label, !blank($infrastructure->{$field}), "Complete {$label} in ASC Infrastructure & Profile.");
            }
        }

        foreach (config('asc.manual_sections', []) as $section) {
            $record = $officialSections->get($section);
            $complete = (bool) ($record?->is_complete ?? false);
            $label = 'Official Section ' . $section . ' — ' . config("asc.sections.{$section}.title", $section);
            $checks[] = ['label' => $label, 'complete' => $complete];
            if (!$complete) {
                $message = "Complete official census Section {$section} before finalization.";
                $issues[] = $message;
                $blocking[] = $message;
            }
        }

        $passed = collect($checks)->where('complete', true)->count();
        $total = max(count($checks), 1);
        $score = (int) round(($passed / $total) * 100);

        return [
            'score' => $score,
            'complete_checks' => $passed,
            'total_checks' => count($checks),
            'issues' => array_values($issues),
            'blocking_issues' => array_values($blocking),
            'checks' => $checks,
            'ready_to_finalize' => count($blocking) === 0 && $infrastructure !== null,
        ];
    }

    private function reconciliation(int $tenantId, Collection $studentRows): array
    {
        $enrollmentRows = StudentEnrollment::where('tenant_id', $tenantId)
            ->where('is_current', true)
            ->count();

        $distinctStudents = StudentEnrollment::where('tenant_id', $tenantId)
            ->where('is_current', true)
            ->distinct('student_id')
            ->count('student_id');

        return [
            'current_enrollment_rows' => $enrollmentRows,
            'distinct_current_students' => $distinctStudents,
            'synchronized_students' => $studentRows->count(),
            'duplicate_current_enrollments' => max(0, $enrollmentRows - $distinctStudents),
            'balanced' => $enrollmentRows === $distinctStudents && $distinctStudents === $studentRows->count(),
        ];
    }

    private function infrastructureSnapshot(?AscInfrastructure $infrastructure): array
    {
        if (!$infrastructure) {
            return [];
        }

        return collect($infrastructure->getAttributes())
            ->except(['id', 'tenant_id', 'session_id', 'created_at', 'updated_at'])
            ->all();
    }

    private function gender(?string $value): string
    {
        return match (strtolower(trim((string) $value))) {
            'male', 'm' => 'male',
            'female', 'f' => 'female',
            default => 'unknown',
        };
    }

    private function addCheck(array &$checks, array &$issues, string $label, bool $complete, string $issue): void
    {
        $checks[] = ['label' => $label, 'complete' => $complete];
        if (!$complete) {
            $issues[] = $issue;
        }
    }

    private function addCountCheck(
        array &$checks,
        array &$issues,
        array &$blocking,
        string $label,
        int $missing,
        string $suffix,
        bool $isBlocking = true
    ): void {
        $complete = $missing === 0;
        $checks[] = ['label' => $label, 'complete' => $complete, 'missing' => $missing];

        if (!$complete) {
            $message = "{$missing} {$suffix}";
            $issues[] = $message;
            if ($isBlocking) {
                $blocking[] = $message;
            }
        }
    }
}
