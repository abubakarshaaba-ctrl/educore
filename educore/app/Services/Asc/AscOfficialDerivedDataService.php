<?php

namespace App\Services\Asc;

use App\Models\AcademicSession;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AscOfficialDerivedDataService
{
    public function build(Tenant $tenant, ?AcademicSession $session, CarbonImmutable $referenceDate): array
    {
        if (!$session) {
            return [
                'section_c' => $this->emptySectionC('No current academic session is configured.'),
                'section_e' => $this->emptySectionE('No current academic session is configured.'),
            ];
        }

        return [
            'section_c' => $this->sectionC($tenant->id, $session, $referenceDate),
            'section_e' => $this->sectionE($tenant->id, $session),
        ];
    }

    private function sectionC(int $tenantId, AcademicSession $session, CarbonImmutable $referenceDate): array
    {
        $current = $this->currentStudentRows($tenantId, $session->id);

        return [
            'source' => 'AUTO / DERIVED',
            'reference_date' => $referenceDate->toDateString(),
            'age_by_grade' => $this->ageByGrade($current, $referenceDate),
            'streams_by_grade' => $this->streamsByGrade($tenantId),
            'new_entrants' => $this->newEntrants($tenantId, $session, $referenceDate),
            'birth_certificate' => $this->birthCertificateCoverage($tenantId, $session),
            'special_needs_by_grade' => $this->specialNeedsByGrade($current),
            'pupil_flow' => $this->pupilFlow($tenantId, $session),
            'unsupported_fields' => [
                'birth_certificate_issuing_authority' => 'EduCore records whether an admission has a birth-certificate file, but does not record whether it was issued by the National Population Commission or another authority.',
                'orphans_by_grade' => 'Guardian records do not contain deceased-parent status, so orphan counts cannot be derived reliably.',
                'multigrade_streams' => 'Class arms do not currently record whether a stream uses multigrade teaching.',
                'dropout_by_grade' => 'Student lifecycle statuses include left/withdrawn, but do not distinguish census-defined dropout from other reasons reliably.',
            ],
        ];
    }

    private function sectionE(int $tenantId, AcademicSession $session): array
    {
        if (!Schema::hasTable('class_arm_subjects')) {
            return $this->emptySectionE('Teacher/class subject allocation table is unavailable.');
        }

        $assignmentQuery = DB::table('class_arm_subjects as cas')
            ->join('users as u', 'u.id', '=', 'cas.teacher_id')
            ->join('class_arms as ca', 'ca.id', '=', 'cas.class_arm_id')
            ->join('class_levels as cl', 'cl.id', '=', 'ca.class_level_id')
            ->where('cas.tenant_id', $tenantId)
            ->whereNotNull('cas.teacher_id')
            ->whereNull('u.deleted_at')
            ->where('u.is_active', true);

        if (Schema::hasColumn('class_arm_subjects', 'session_id')) {
            $assignmentQuery->where(function ($q) use ($session) {
                $q->where('cas.session_id', $session->id)
                    ->orWhereNull('cas.session_id');
            });
        }
        if (Schema::hasColumn('class_arm_subjects', 'is_active')) {
            $assignmentQuery->where('cas.is_active', true);
        }

        $assignments = $assignmentQuery->get([
            'u.id as teacher_id', 'u.name', 'u.gender', 'u.qualification',
            'cl.name as level_name', 'cl.section',
        ]);

        $rows = [];
        $unassigned = [];

        foreach ($assignments->groupBy('teacher_id') as $teacherId => $teacherAssignments) {
            $teacher = $teacherAssignments->first();
            $counts = $teacherAssignments
                ->groupBy(fn ($row) => $this->teachingBand($row->section, $row->level_name))
                ->map->count()
                ->sortDesc();

            $max = $counts->first();
            $leaders = $counts->filter(fn ($count) => $count === $max)->keys()->values();
            $mainBand = $leaders->count() === 1 ? $leaders->first() : 'unclassified';

            if ($mainBand === 'unclassified') {
                $unassigned[] = [
                    'teacher_id' => (int) $teacherId,
                    'name' => $teacher->name,
                    'reason' => 'Equal assignment counts across more than one teaching level.',
                ];
            }

            $qualification = $this->officialQualification($teacher->qualification);
            $gender = $this->gender($teacher->gender);
            $key = $qualification . '|' . $mainBand;

            $rows[$key] ??= [
                'qualification' => $qualification,
                'main_teaching_level' => $mainBand,
                'male' => 0,
                'female' => 0,
                'unknown_gender' => 0,
                'total' => 0,
            ];
            $rows[$key][$gender === 'male' ? 'male' : ($gender === 'female' ? 'female' : 'unknown_gender')]++;
            $rows[$key]['total']++;
        }

        $assignedTeacherIds = $assignments->pluck('teacher_id')->unique()->map(fn ($id) => (int) $id);
        $activeTeaching = DB::table('users')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereIn('role', ['form_teacher', 'asst_form_teacher', 'subject_teacher', 'form_subject_teacher'])
            ->get(['id', 'name']);

        foreach ($activeTeaching->whereNotIn('id', $assignedTeacherIds) as $teacher) {
            $unassigned[] = [
                'teacher_id' => (int) $teacher->id,
                'name' => $teacher->name,
                'reason' => 'No class/subject allocation found for the current session.',
            ];
        }

        return [
            'source' => 'AUTO / DERIVED',
            'basis' => 'Main teaching input is the level with the highest number of active class/subject allocations for each teacher in the current session. Equal-count ties are left unclassified rather than guessed.',
            'qualification_by_main_teaching_level' => array_values($rows),
            'teachers_with_assignments' => $assignedTeacherIds->count(),
            'unclassified_or_unassigned_teachers' => $unassigned,
        ];
    }

    private function currentStudentRows(int $tenantId, int $sessionId): Collection
    {
        return DB::table('student_enrollments as se')
            ->join('students as s', 's.id', '=', 'se.student_id')
            ->leftJoin('class_arms as ca', 'ca.id', '=', 'se.class_arm_id')
            ->leftJoin('class_levels as cl', 'cl.id', '=', 'ca.class_level_id')
            ->where('se.tenant_id', $tenantId)
            ->where('se.session_id', $sessionId)
            ->where('se.is_current', true)
            ->whereNull('s.deleted_at')
            ->get([
                'se.id as enrollment_id', 'se.student_id', 'se.class_arm_id', 'se.start_date',
                's.gender', 's.date_of_birth', 's.admission_date', 's.has_special_needs', 's.special_needs_type',
                'cl.id as class_level_id', 'cl.name as level_name', 'cl.section', 'cl.order_index',
            ]);
    }

    private function ageByGrade(Collection $rows, CarbonImmutable $referenceDate): array
    {
        return $rows->filter(fn ($row) => !blank($row->date_of_birth))
            ->map(function ($row) use ($referenceDate) {
                $dob = CarbonImmutable::parse($row->date_of_birth)->startOfDay();
                if ($dob->greaterThan($referenceDate)) {
                    return null;
                }
                return [
                    'level_name' => $row->level_name ?: 'Unassigned',
                    'grade_key' => $this->gradeKey($row->level_name),
                    'age' => (int) floor($dob->diffInYears($referenceDate, true)),
                    'gender' => $this->gender($row->gender),
                ];
            })
            ->filter()
            ->groupBy(fn ($row) => $row['grade_key'] . '|' . $row['age'])
            ->map(function (Collection $group) {
                $first = $group->first();
                return [
                    'grade_key' => $first['grade_key'],
                    'level_name' => $first['level_name'],
                    'age' => $first['age'],
                    'male' => $group->where('gender', 'male')->count(),
                    'female' => $group->where('gender', 'female')->count(),
                    'unknown_gender' => $group->where('gender', 'unknown')->count(),
                    'total' => $group->count(),
                ];
            })->values()->all();
    }

    private function streamsByGrade(int $tenantId): array
    {
        return DB::table('class_arms as ca')
            ->join('class_levels as cl', 'cl.id', '=', 'ca.class_level_id')
            ->where('ca.tenant_id', $tenantId)
            ->whereNull('ca.deleted_at')
            ->whereNull('cl.deleted_at')
            ->select('cl.name as level_name', 'cl.section', DB::raw('COUNT(ca.id) as streams'))
            ->groupBy('cl.id', 'cl.name', 'cl.section', 'cl.order_index')
            ->orderBy('cl.order_index')
            ->get()
            ->map(fn ($row) => [
                'grade_key' => $this->gradeKey($row->level_name),
                'level_name' => $row->level_name,
                'streams' => (int) $row->streams,
            ])->all();
    }

    private function newEntrants(int $tenantId, AcademicSession $session, CarbonImmutable $referenceDate): array
    {
        $histories = DB::table('student_enrollments as se')
            ->join('students as s', 's.id', '=', 'se.student_id')
            ->join('class_arms as ca', 'ca.id', '=', 'se.class_arm_id')
            ->join('class_levels as cl', 'cl.id', '=', 'ca.class_level_id')
            ->where('se.tenant_id', $tenantId)
            ->whereNull('s.deleted_at')
            ->orderBy('se.start_date')
            ->orderBy('se.id')
            ->get([
                'se.id', 'se.student_id', 'se.session_id', 'se.start_date',
                's.gender', 's.date_of_birth', 'cl.name as level_name', 'cl.section',
            ]);

        $firstInSchool = $histories->groupBy('student_id')->map->first();
        $targetGrades = ['pry1', 'jss1', 'ss1'];

        return $firstInSchool
            ->filter(fn ($row) => (int) $row->session_id === (int) $session->id && in_array($this->gradeKey($row->level_name), $targetGrades, true))
            ->map(function ($row) use ($referenceDate) {
                $age = null;
                if ($row->date_of_birth) {
                    $dob = CarbonImmutable::parse($row->date_of_birth)->startOfDay();
                    $age = $dob->greaterThan($referenceDate) ? null : (int) floor($dob->diffInYears($referenceDate, true));
                }
                return [
                    'grade_key' => $this->gradeKey($row->level_name),
                    'level_name' => $row->level_name,
                    'age' => $age,
                    'gender' => $this->gender($row->gender),
                ];
            })
            ->groupBy(fn ($row) => $row['grade_key'] . '|' . ($row['age'] ?? 'unknown'))
            ->map(function (Collection $group) {
                $first = $group->first();
                return [
                    'grade_key' => $first['grade_key'],
                    'level_name' => $first['level_name'],
                    'age' => $first['age'],
                    'male' => $group->where('gender', 'male')->count(),
                    'female' => $group->where('gender', 'female')->count(),
                    'unknown_gender' => $group->where('gender', 'unknown')->count(),
                    'total' => $group->count(),
                ];
            })->values()->all();
    }

    private function birthCertificateCoverage(int $tenantId, AcademicSession $session): array
    {
        if (!Schema::hasTable('admissions')) {
            return ['with_certificate' => 0, 'source_detail_supported' => false];
        }

        $query = DB::table('admissions as a')
            ->join('student_enrollments as se', 'se.student_id', '=', 'a.enrolled_as_student_id')
            ->join('students as s', 's.id', '=', 'se.student_id')
            ->join('class_arms as ca', 'ca.id', '=', 'se.class_arm_id')
            ->join('class_levels as cl', 'cl.id', '=', 'ca.class_level_id')
            ->where('a.tenant_id', $tenantId)
            ->where('se.session_id', $session->id)
            ->where('se.is_current', true)
            ->whereNotNull('a.birth_certificate')
            ->where('a.birth_certificate', '!=', '')
            ->get(['s.gender', 'cl.name as level_name']);

        return [
            'with_certificate' => $query->count(),
            'by_grade' => $query->groupBy(fn ($row) => $this->gradeKey($row->level_name))
                ->map(function (Collection $group, $grade) {
                    return [
                        'grade_key' => $grade,
                        'male' => $group->filter(fn ($row) => $this->gender($row->gender) === 'male')->count(),
                        'female' => $group->filter(fn ($row) => $this->gender($row->gender) === 'female')->count(),
                        'total' => $group->count(),
                    ];
                })->values()->all(),
            'source_detail_supported' => false,
        ];
    }

    private function specialNeedsByGrade(Collection $rows): array
    {
        return $rows->filter(fn ($row) => (bool) $row->has_special_needs)
            ->groupBy(fn ($row) => $this->gradeKey($row->level_name) . '|' . ($this->specialNeed($row->special_needs_type)))
            ->map(function (Collection $group) {
                $first = $group->first();
                return [
                    'grade_key' => $this->gradeKey($first->level_name),
                    'level_name' => $first->level_name,
                    'special_needs_type' => $this->specialNeed($first->special_needs_type),
                    'male' => $group->filter(fn ($row) => $this->gender($row->gender) === 'male')->count(),
                    'female' => $group->filter(fn ($row) => $this->gender($row->gender) === 'female')->count(),
                    'unknown_gender' => $group->filter(fn ($row) => $this->gender($row->gender) === 'unknown')->count(),
                    'total' => $group->count(),
                ];
            })->values()->all();
    }

    private function pupilFlow(int $tenantId, AcademicSession $session): array
    {
        $previousSession = AcademicSession::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('id', '<', $session->id)
            ->orderByDesc('id')
            ->first();

        $result = [
            'previous_session_id' => $previousSession?->id,
            'previous_session_name' => $previousSession?->name,
            'promoted' => [],
            'repeaters' => [],
            'transfer_in' => [],
            'transfer_out' => [],
        ];

        if ($previousSession && Schema::hasTable('termly_summaries')) {
            $summaries = DB::table('termly_summaries as ts')
                ->join('students as s', 's.id', '=', 'ts.student_id')
                ->join('class_arms as ca', 'ca.id', '=', 'ts.class_arm_id')
                ->join('class_levels as cl', 'cl.id', '=', 'ca.class_level_id')
                ->where('ts.tenant_id', $tenantId)
                ->where('ts.session_id', $previousSession->id)
                ->whereIn('ts.promotion_status', ['promoted', 'repeat'])
                ->get(['ts.student_id', 'ts.promotion_status', 's.gender', 'cl.name as level_name']);

            foreach (['promoted', 'repeat'] as $status) {
                $key = $status === 'repeat' ? 'repeaters' : 'promoted';
                $result[$key] = $summaries->where('promotion_status', $status)
                    ->groupBy(fn ($row) => $this->gradeKey($row->level_name))
                    ->map(fn (Collection $group, $grade) => [
                        'grade_key' => $grade,
                        'male' => $group->filter(fn ($row) => $this->gender($row->gender) === 'male')->unique('student_id')->count(),
                        'female' => $group->filter(fn ($row) => $this->gender($row->gender) === 'female')->unique('student_id')->count(),
                        'total' => $group->unique('student_id')->count(),
                    ])->values()->all();
            }
        }

        if (Schema::hasTable('admissions')) {
            $transferIn = DB::table('admissions as a')
                ->join('student_enrollments as se', 'se.student_id', '=', 'a.enrolled_as_student_id')
                ->join('students as s', 's.id', '=', 'se.student_id')
                ->join('class_arms as ca', 'ca.id', '=', 'se.class_arm_id')
                ->join('class_levels as cl', 'cl.id', '=', 'ca.class_level_id')
                ->where('a.tenant_id', $tenantId)
                ->where('se.session_id', $session->id)
                ->whereNotNull('a.previous_school')
                ->where('a.previous_school', '!=', '')
                ->get(['s.id as student_id', 's.gender', 'cl.name as level_name']);

            $result['transfer_in'] = $this->flowRows($transferIn);
        }

        $transferOut = DB::table('student_enrollments as se')
            ->join('students as s', 's.id', '=', 'se.student_id')
            ->join('class_arms as ca', 'ca.id', '=', 'se.class_arm_id')
            ->join('class_levels as cl', 'cl.id', '=', 'ca.class_level_id')
            ->where('se.tenant_id', $tenantId)
            ->where('se.session_id', $session->id)
            ->where('se.status', 'transferred_out')
            ->get(['s.id as student_id', 's.gender', 'cl.name as level_name']);

        $result['transfer_out'] = $this->flowRows($transferOut);

        return $result;
    }

    private function flowRows(Collection $rows): array
    {
        return $rows->groupBy(fn ($row) => $this->gradeKey($row->level_name))
            ->map(fn (Collection $group, $grade) => [
                'grade_key' => $grade,
                'male' => $group->filter(fn ($row) => $this->gender($row->gender) === 'male')->unique('student_id')->count(),
                'female' => $group->filter(fn ($row) => $this->gender($row->gender) === 'female')->unique('student_id')->count(),
                'total' => $group->unique('student_id')->count(),
            ])->values()->all();
    }

    private function gradeKey(?string $name): string
    {
        $n = Str::lower((string) $name);
        $n = str_replace([' ', '-', '_'], '', $n);

        foreach ([
            'pry1' => ['primary1', 'pry1'], 'pry2' => ['primary2', 'pry2'], 'pry3' => ['primary3', 'pry3'],
            'pry4' => ['primary4', 'pry4'], 'pry5' => ['primary5', 'pry5'], 'pry6' => ['primary6', 'pry6'],
            'jss1' => ['jss1', 'juniorsecondary1'], 'jss2' => ['jss2', 'juniorsecondary2'], 'jss3' => ['jss3', 'juniorsecondary3'],
            'ss1' => ['sss1', 'ss1', 'seniorsecondary1'], 'ss2' => ['sss2', 'ss2', 'seniorsecondary2'], 'ss3' => ['sss3', 'ss3', 'seniorsecondary3'],
        ] as $key => $aliases) {
            if (in_array($n, $aliases, true)) {
                return $key;
            }
        }

        if (str_contains($n, 'nursery') || str_contains($n, 'kg') || str_contains($n, 'eccd') || str_contains($n, 'creche')) {
            return Str::slug((string) $name, '_');
        }

        return Str::slug((string) ($name ?: 'unassigned'), '_');
    }

    private function teachingBand(?string $section, ?string $levelName): string
    {
        $value = Str::lower(trim((string) $section) . ' ' . trim((string) $levelName));
        return match (true) {
            str_contains($value, 'nursery'), str_contains($value, 'pre'), str_contains($value, 'eccd'), str_contains($value, 'creche'), str_contains($value, 'kg') => 'pre_primary',
            str_contains($value, 'primary'), str_contains($value, 'pry') => 'primary',
            str_contains($value, 'junior'), str_contains($value, 'jss') => 'jss',
            str_contains($value, 'senior'), str_contains($value, 'sss'), preg_match('/\bss[123]\b/', $value) === 1 => 'sss',
            default => 'unclassified',
        };
    }

    private function officialQualification(?string $value): string
    {
        $q = Str::lower(trim((string) $value));
        return match (true) {
            $q === '' => 'Not Specified',
            str_contains($q, 'below') && str_contains($q, 'ssce') => 'Below SSCE',
            str_contains($q, 'ssce'), str_contains($q, 'wasc'), str_contains($q, 'waec') => 'SSCE/WASC',
            str_contains($q, 'ond'), str_contains($q, 'diploma') => 'OND/Diploma',
            str_contains($q, 'nce') => 'NCE',
            str_contains($q, 'pgde') => 'PGDE',
            str_contains($q, 'b.ed'), $q === 'bed' => 'B.Ed',
            str_contains($q, 'm.ed'), str_contains($q, 'med'), str_contains($q, 'master') => 'M.Ed',
            str_contains($q, 'grade ii'), str_contains($q, 'grade 2') => 'Grade II',
            str_contains($q, 'b.a(ed)'), str_contains($q, 'ba(ed)') => 'B.A(Ed)',
            str_contains($q, 'b.sc(ed)'), str_contains($q, 'bsc(ed)') => 'B.Sc.(Ed)',
            str_contains($q, 'hnd') => 'HND',
            str_contains($q, 'b.sc'), str_contains($q, 'bsc') => 'B.Sc./HND',
            default => 'Other degree/graduate',
        };
    }

    private function specialNeed(?string $value): string
    {
        $v = Str::lower(trim((string) $value));
        return match (true) {
            $v === '' => 'Unspecified',
            str_contains($v, 'blind'), str_contains($v, 'visual') => 'Blind / Visually impaired',
            str_contains($v, 'hearing'), str_contains($v, 'speech'), str_contains($v, 'deaf') => 'Hearing / Speech impaired',
            str_contains($v, 'physical') => 'Physically challenged',
            str_contains($v, 'mental'), str_contains($v, 'intellectual') => 'Mentally challenged',
            str_contains($v, 'albin') => 'Albinism',
            str_contains($v, 'autis') => 'Autism',
            default => trim((string) $value),
        };
    }

    private function gender(?string $value): string
    {
        return match (Str::lower(trim((string) $value))) {
            'male', 'm' => 'male',
            'female', 'f' => 'female',
            default => 'unknown',
        };
    }

    private function emptySectionC(string $reason): array
    {
        return ['source' => 'AUTO / DERIVED', 'available' => false, 'reason' => $reason];
    }

    private function emptySectionE(string $reason): array
    {
        return ['source' => 'AUTO / DERIVED', 'available' => false, 'reason' => $reason, 'qualification_by_main_teaching_level' => []];
    }
}
