<?php

namespace App\Http\Controllers;

use App\Models\AscInfrastructure;
use App\Models\AcademicSession;
use App\Models\ClassLevel;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AscController extends Controller
{
    // ── Infrastructure form (GET) ──────────────────────────────────────
    public function infrastructure(Request $request)
    {
        $tenant   = auth()->user()->tenant;
        $sessions = AcademicSession::where('tenant_id', $tenant->id)->orderByDesc('id')->get();
        $year     = $request->integer('year', now()->year);

        $infra = AscInfrastructure::where('tenant_id', $tenant->id)
                    ->where('census_year', $year)
                    ->first();

        return view('asc.infrastructure', compact('tenant', 'sessions', 'year', 'infra'));
    }

    // ── Infrastructure form (POST) ────────────────────────────────────
    public function saveInfrastructure(Request $request)
    {
        $tenant = auth()->user()->tenant;

        $data = $request->validate([
            'census_year'                  => ['required', 'integer', 'min:2000', 'max:2100'],
            'school_ownership'             => ['nullable', 'string'],
            'school_type'                  => ['nullable', 'string'],
            'school_lga'                   => ['nullable', 'string', 'max:100'],
            'school_state'                 => ['nullable', 'string', 'max:100'],
            'school_senatorial_district'   => ['nullable', 'string', 'max:100'],
            'head_teacher_name'            => ['nullable', 'string', 'max:150'],
            'head_teacher_qualification'   => ['nullable', 'string', 'max:100'],
            'head_teacher_gender'          => ['nullable', 'in:male,female'],
            'classrooms_permanent'         => ['nullable', 'integer', 'min:0'],
            'classrooms_temporary'         => ['nullable', 'integer', 'min:0'],
            'classrooms_good_condition'    => ['nullable', 'integer', 'min:0'],
            'classrooms_bad_condition'     => ['nullable', 'integer', 'min:0'],
            'toilets_male_pupils'          => ['nullable', 'integer', 'min:0'],
            'toilets_female_pupils'        => ['nullable', 'integer', 'min:0'],
            'toilets_male_staff'           => ['nullable', 'integer', 'min:0'],
            'toilets_female_staff'         => ['nullable', 'integer', 'min:0'],
            'water_source'                 => ['nullable', 'string'],
            'electricity_source'           => ['nullable', 'string'],
            'has_library'                  => ['nullable', 'boolean'],
            'has_computer_lab'             => ['nullable', 'boolean'],
            'computer_count'               => ['nullable', 'integer', 'min:0'],
            'has_science_lab'              => ['nullable', 'boolean'],
            'has_sports_facility'          => ['nullable', 'boolean'],
            'has_first_aid'                => ['nullable', 'boolean'],
            'fence_type'                   => ['nullable', 'string'],
        ]);

        $data['tenant_id']       = $tenant->id;
        $data['has_library']     = $request->boolean('has_library');
        $data['has_computer_lab']= $request->boolean('has_computer_lab');
        $data['has_science_lab'] = $request->boolean('has_science_lab');
        $data['has_sports_facility'] = $request->boolean('has_sports_facility');
        $data['has_first_aid']   = $request->boolean('has_first_aid');

        AscInfrastructure::updateOrCreate(
            ['tenant_id' => $tenant->id, 'census_year' => $data['census_year']],
            $data
        );

        return redirect()->route('asc.infrastructure', ['year' => $data['census_year']])
                         ->with('success', 'Infrastructure data saved.');
    }

    // ── Report (GET) ──────────────────────────────────────────────────
    public function report(Request $request)
    {
        $tenant  = auth()->user()->tenant;
        $year    = $request->integer('year', now()->year);
        $session = AcademicSession::where('tenant_id', $tenant->id)->where('is_current', true)->first();

        $infra = AscInfrastructure::where('tenant_id', $tenant->id)
                    ->where('census_year', $year)
                    ->first();

        // ── Enrollment by section & gender ──────────────────────────
        $enrollments = StudentEnrollment::query()
            ->join('students', 'students.id', '=', 'student_enrollments.student_id')
            ->join('class_arms', 'class_arms.id', '=', 'student_enrollments.class_arm_id')
            ->join('class_levels', 'class_levels.id', '=', 'class_arms.class_level_id')
            ->where('student_enrollments.tenant_id', $tenant->id)
            ->where('student_enrollments.is_current', true)
            ->select(
                'class_levels.section',
                'class_levels.name as level_name',
                'class_levels.order_index',
                'students.gender',
                'students.has_special_needs',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('class_levels.section', 'class_levels.name', 'class_levels.order_index', 'students.gender', 'students.has_special_needs')
            ->orderBy('class_levels.order_index')
            ->get();

        // ── Age distribution (current enrollments) ───────────────────
        $ageGroups = StudentEnrollment::query()
            ->join('students', 'students.id', '=', 'student_enrollments.student_id')
            ->where('student_enrollments.tenant_id', $tenant->id)
            ->where('student_enrollments.is_current', true)
            ->whereNotNull('students.date_of_birth')
            ->select(
                'students.gender',
                DB::raw('TIMESTAMPDIFF(YEAR, students.date_of_birth, CURDATE()) as age'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('students.gender', 'age')
            ->orderBy('age')
            ->get();

        // ── Staff by role, gender, qualification ─────────────────────
        $staffRoles = [
            'admin', 'principal', 'head', 'head_teacher', 'vice_principal',
            'academic_administrator', 'admission_officer', 'form_teacher',
            'asst_form_teacher', 'subject_teacher', 'form_subject_teacher',
            'accountant', 'health_officer', 'librarian',
        ];

        $staff = User::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereIn('role', $staffRoles)
            ->select('role', 'date_of_birth',
                DB::raw("COALESCE(NULLIF(qualification,''), 'Not Specified') as qualification"),
                DB::raw("COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(JSON_OBJECT(), '$')), ''), 'unknown') as gender_raw")
            )
            ->get();

        // Fetch staff with gender from profile if stored, else derive from name
        $staffData = User::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereIn('role', $staffRoles)
            ->select('role', 'qualification',
                DB::raw('COUNT(*) as count'),
                DB::raw("SUM(CASE WHEN role IN('principal','head','head_teacher','vice_principal','academic_administrator','admin') THEN 1 ELSE 0 END) as is_management")
            )
            ->groupBy('role', 'qualification')
            ->get();

        // Simpler aggregation
        $allStaff = User::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereIn('role', $staffRoles)
            ->get(['role', 'qualification', 'name']);

        $teachingRoles = ['form_teacher','asst_form_teacher','subject_teacher','form_subject_teacher'];
        $managementRoles = ['admin','principal','head','head_teacher','vice_principal','academic_administrator'];
        $nonTeachingRoles = ['accountant','health_officer','librarian','admission_officer'];

        $qualificationMap = [
            'phd'     => 'PhD / Doctorate',
            'masters' => 'Masters Degree',
            'pgde'    => 'PGDE',
            'bed'     => 'B.Ed',
            'bsc'     => 'B.Sc / B.A',
            'hnd'     => 'HND',
            'nce'     => 'NCE',
            'nd'      => 'ND / OND',
            'ssce'    => 'SSCE / WAEC',
            'other'   => 'Other',
        ];

        $staffByQual = $allStaff->groupBy(fn($u) => strtolower($u->qualification ?? 'not_specified'));

        // ── Totals summary ───────────────────────────────────────────
        $totalStudents    = StudentEnrollment::where('tenant_id', $tenant->id)->where('is_current', true)->count();
        $totalTeachers    = $allStaff->whereIn('role', $teachingRoles)->count();
        $totalManagement  = $allStaff->whereIn('role', $managementRoles)->count();
        $totalNonTeaching = $allStaff->whereIn('role', $nonTeachingRoles)->count();
        $specialNeeds     = StudentEnrollment::query()
            ->join('students','students.id','=','student_enrollments.student_id')
            ->where('student_enrollments.tenant_id', $tenant->id)
            ->where('student_enrollments.is_current', true)
            ->where('students.has_special_needs', true)
            ->count();

        return view('asc.report', compact(
            'tenant', 'year', 'infra', 'session',
            'enrollments', 'ageGroups',
            'allStaff', 'staffByQual',
            'qualificationMap',
            'teachingRoles', 'managementRoles', 'nonTeachingRoles',
            'totalStudents', 'totalTeachers', 'totalManagement', 'totalNonTeaching',
            'specialNeeds'
        ));
    }
}
