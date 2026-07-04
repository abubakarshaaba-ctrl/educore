<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\AuthAuditLogger;
use App\Services\Auth\LoginRedirector;
use App\Services\Auth\LoginUserResolver;
use App\Services\TenantAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Role-scoped login surfaces for school users.
 *
 * The platform gateway (super administration) lives in LoginController and parents
 * authenticate through ParentPortalController (separate ParentPortalAccount model).
 * This controller serves the three User-backed school surfaces — administration,
 * staff and student — sharing the exact same authentication core as the platform
 * login (resolve → verify → active checks → tenant access), but enforcing that the
 * authenticated user actually belongs to the surface they used. A mismatch sends the
 * user to their correct door instead of signing them in on the wrong one.
 */
class RoleLoginController extends Controller
{
    /** Administrative tenant roles (the "School Administration" surface). */
    private const ADMIN_ROLES = [
        'admin', 'principal', 'head', 'head_teacher',
        'vice_principal', 'academic_administrator', 'admission_officer',
    ];

    /** Per-surface presentation + the roles each surface accepts. */
    private const SURFACES = [
        'admin' => [
            'title'    => 'School Administration',
            'eyebrow'  => 'Administrator Sign-in',
            'lead'     => 'For principals, heads, and administrative staff who manage the school.',
            'id_label' => 'Staff ID or Email',
        ],
        'staff' => [
            'title'    => 'Staff Sign-in',
            'eyebrow'  => 'School Staff',
            'lead'     => 'For teachers and operational staff. Use the staff ID or email issued by your school.',
            'id_label' => 'Staff ID or Email',
        ],
        'student' => [
            'title'    => 'Student Portal',
            'eyebrow'  => 'Student Sign-in',
            'lead'     => 'Check your results, timetable, attendance and exams. Use the student ID issued by your school.',
            'id_label' => 'Student ID or Email',
        ],
        'parent' => [
            'title'    => 'Parent Portal',
            'eyebrow'  => 'Parent Sign-in',
            'lead'     => 'View your child\'s results, attendance, fees and announcements.',
            'id_label' => 'Email Address',
        ],
    ];

    public function show(string $surface, LoginRedirector $redirector)
    {
        abort_unless(array_key_exists($surface, self::SURFACES), 404);

        if (Auth::check()) {
            return $redirector->redirectFor(Auth::user());
        }

        return view('auth.role-login', [
            'surface' => $surface,
            'meta'    => self::SURFACES[$surface],
        ]);
    }

    public function login(
        string $surface,
        Request $request,
        LoginUserResolver $users,
        LoginRedirector $redirector,
        AuthAuditLogger $audit,
        TenantAccessService $tenantAccess
    ) {
        abort_unless(array_key_exists($surface, self::SURFACES), 404);

        $request->validate([
            'login_id' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $loginId  = trim($request->login_id);
        $password = $request->password;
        $user     = $users->resolveGlobal($loginId);

        if (!$user || !Hash::check($password, $user->password)) {
            if ($user) {
                $audit->recordForUser($user, 'auth.login.denied', [
                    'login_surface' => $surface,
                ], $request, 'invalid_credentials');
            }

            return back()
                ->withErrors(['login_id' => 'Credentials not found. Check your ID or email and password.'])
                ->withInput(['login_id' => $loginId]);
        }

        // Enforce that the user belongs to this surface; otherwise guide them to the right one.
        if (!$this->userMatchesSurface($user, $surface)) {
            $audit->recordForUser($user, 'auth.login.denied', [
                'login_surface' => $surface,
            ], $request, 'wrong_surface');

            $target = $this->naturalSurfaceRoute($user);

            return redirect($target['url'])
                ->withErrors(['login_id' => "This is the {$this->surfaceLabel($surface)} login. {$target['hint']}"])
                ->withInput(['login_id' => $loginId]);
        }

        if (!$user->is_active || ($user->isTenantStaff() && !$user->isEmploymentActive())) {
            $audit->recordForUser($user, 'auth.login.denied', [
                'login_surface' => $surface,
            ], $request, 'inactive_or_ineligible_account');

            return back()->withErrors(['login_id' => 'Your account has been deactivated. Contact the school.']);
        }

        $tenant = $user->tenant;

        if (!$tenant) {
            $audit->recordForUser($user, 'auth.login.denied', [
                'login_surface' => $surface,
            ], $request, 'missing_tenant');

            return back()->withErrors(['login_id' => 'Account not linked to any school.']);
        }

        $decision = $tenantAccess->applicationAccess($tenant);
        if ($decision->isDenied()) {
            $audit->recordForUser($user, 'auth.login.denied', [
                'login_surface' => $surface,
                'state'         => $decision->state,
            ], $request, 'tenant_' . $decision->state);

            return back()->withErrors(['login_id' => 'This school account is currently unavailable. Contact support.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->put('tenant_id', $tenant->id);
        $request->session()->put('tenant_slug', $tenant->slug);
        $user->forceFill(['last_login_at' => now()])->save();
        $audit->recordForUser($user, 'auth.login.success', [
            'login_surface' => $surface,
        ], $request);

        return $redirector->redirectFor($user);
    }

    private function userMatchesSurface(User $user, string $surface): bool
    {
        return match ($surface) {
            'admin'   => !$user->isSuperAdmin() && in_array($user->roleKey(), self::ADMIN_ROLES, true),
            'staff'   => !$user->isSuperAdmin()
                            && $user->isStaff()
                            && !in_array($user->roleKey(), self::ADMIN_ROLES, true),
            'student' => $user->isStudent(),
            'parent'  => $user->isParent(),
            default   => false,
        };
    }

    /** Where this user *should* be signing in, for a friendly redirect. */
    private function naturalSurfaceRoute(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return ['url' => route('login'), 'hint' => 'Use the platform login.'];
        }
        if ($user->isStudent()) {
            return ['url' => route('student.login'), 'hint' => 'Use the student login.'];
        }
        if ($user->isParent()) {
            return ['url' => route('parent.login'), 'hint' => 'Use the parent login.'];
        }
        if (in_array($user->roleKey(), self::ADMIN_ROLES, true)) {
            return ['url' => route('admin.login'), 'hint' => 'Use the administrator login.'];
        }
        return ['url' => route('staff.login'), 'hint' => 'Use the staff login.'];
    }

    private function surfaceLabel(string $surface): string
    {
        return match ($surface) {
            'admin'   => 'administrator',
            'staff'   => 'staff',
            'student' => 'student',
            'parent'  => 'parent',
            default   => 'school',
        };
    }
}
