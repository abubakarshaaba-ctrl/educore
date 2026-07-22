<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Announcement;
use App\Models\ClassArm;
use App\Models\ClassArmSubject;
use App\Models\Term;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function me(Request $request)
    {
        $user    = $request->user();
        if ($user->isSuperAdmin()) {
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'staff_id' => null,
                    'role_key' => 'super_admin',
                    'role' => 'Platform Super Admin',
                    'roles' => ['super_admin'],
                    'portal' => 'platform',
                ],
                'school' => ['id' => null, 'name' => 'EduCore Platform', 'slug' => 'platform'],
                'current_session' => null,
                'current_term' => null,
                'permissions' => ['platform.access', 'platform.tenants', 'platform.billing', 'platform.plans'],
            ]);
        }
        $term    = Term::current()->first();

        return response()->json([
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'staff_id' => $user->staff_id,
                'role_key' => $user->roleKey(),
                'role'     => $user->roleLabel() ?? 'staff',
                'roles'    => $user->getRoleNames()->values(),
                'portal'   => in_array($user->roleKey(), ['admin', 'principal', 'head', 'head_teacher', 'vice_principal', 'academic_administrator'], true) ? 'admin' : 'staff',
            ],
            'school' => [
                'id'   => $user->tenant?->id,
                'name' => $user->tenant?->name,
                'slug' => $user->tenant?->slug,
            ],
            'current_session' => $session?->only(['id', 'name']),
            'current_term'    => $term?->only(['id', 'name']),
            'permissions'     => $user->getAllPermissions()->pluck('name')->sort()->values(),
        ]);
    }

    /**
     * Classes this teacher can work with: arms where they are form tutor,
     * plus arms where they teach a subject in the current session.
     */
    public function classes(Request $request)
    {
        $user    = $request->user();
        $session = AcademicSession::current()->first();

        $tutorArms = ClassArm::with('classLevel')
            ->where('form_tutor_id', $user->id)
            ->get()
            ->map(fn ($arm) => [
                'id'    => $arm->id,
                'name'  => trim(optional($arm->classLevel)->name . ' ' . $arm->name),
                'role'  => 'form_tutor',
                'subject' => null,
                'students_count' => $arm->students()->count(),
            ]);

        $subjectArms = ClassArmSubject::with(['classArm.classLevel', 'subject'])
            ->where('teacher_id', $user->id)
            ->get()
            ->filter(fn ($cas) => $cas->classArm)
            ->map(fn ($cas) => [
                'id'    => $cas->classArm->id,
                'name'  => trim(optional($cas->classArm->classLevel)->name . ' ' . $cas->classArm->name),
                'role'  => 'subject_teacher',
                'subject' => optional($cas->subject)->only(['id', 'name']),
                'students_count' => $cas->classArm->students()->count(),
            ]);

        return response()->json([
            'classes' => $tutorArms->concat($subjectArms)->values(),
        ]);
    }

    public function students(Request $request, int $classArmId)
    {
        $user = $request->user();
        $arm  = ClassArm::with('classLevel')->findOrFail($classArmId);

        abort_unless($this->canAccessArm($user, $arm->id), 403, 'You are not assigned to this class.');

        $students = $arm->students()
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->map(fn ($st) => [
                'id'               => $st->id,
                'name'             => $st->full_name,
                'admission_number' => $st->admission_number,
                'gender'           => $st->gender,
            ]);

        return response()->json([
            'class'    => ['id' => $arm->id, 'name' => trim(optional($arm->classLevel)->name . ' ' . $arm->name)],
            'students' => $students,
        ]);
    }

    public function announcements(Request $request)
    {
        $items = Announcement::where('is_published', true)
            ->whereIn('audience', ['all', 'staff'])
            ->where(fn ($q) => $q->whereNull('expire_date')->orWhere('expire_date', '>=', now()->toDateString()))
            ->orderByDesc('publish_date')
            ->limit(30)
            ->get(['id', 'title', 'body', 'priority', 'publish_date']);

        return response()->json(['announcements' => $items]);
    }

    public static function canAccessArm($user, int $armId): bool
    {
        if (ClassArm::where('id', $armId)->where('form_tutor_id', $user->id)->exists()) {
            return true;
        }

        return ClassArmSubject::where('teacher_id', $user->id)
            ->where('class_arm_id', $armId)
            ->exists();
    }
}
