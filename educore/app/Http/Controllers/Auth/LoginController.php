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

class LoginController extends Controller
{
    public function showLogin(LoginRedirector $redirector)
    {
        if (Auth::check()) {
            return $redirector->redirectFor(Auth::user());
        }

        return view('auth.login');
    }

    public function login(
        Request $request,
        LoginUserResolver $users,
        LoginRedirector $redirector,
        AuthAuditLogger $audit,
        TenantAccessService $tenantAccess
    )
    {
        $request->validate([
            'login_id' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $loginId = trim($request->login_id);
        $password = $request->password;
        $user = $users->resolveGlobal($loginId);

        if (!$user || !Hash::check($password, $user->password)) {
            if ($user) {
                $audit->recordForUser($user, 'auth.login.denied', [
                    'login_surface' => 'global',
                ], $request, 'invalid_credentials');
            }

            return back()
                ->withErrors(['login_id' => 'Credentials not found. Check your ID or email and password.'])
                ->withInput(['login_id' => $loginId]);
        }

        if (!$user->is_active || ($user->isTenantStaff() && !$user->isEmploymentActive())) {
            $audit->recordForUser($user, 'auth.login.denied', [
                'login_surface' => 'global',
            ], $request, 'inactive_or_ineligible_account');

            return back()->withErrors(['login_id' => 'Your account has been deactivated. Contact the school.']);
        }

        // The platform gateway is reserved for super administration. Any valid school
        // account (admin / staff / student / parent) is guided to its own login surface
        // instead of being signed in here.
        if (!$user->is_super_admin) {
            $audit->recordForUser($user, 'auth.login.denied', [
                'login_surface' => 'global',
            ], $request, 'non_platform_user');

            $target = $this->schoolSurfaceFor($user);

            return redirect($target['url'])
                ->withErrors(['login_id' => "School accounts sign in on their own page. {$target['hint']}"])
                ->withInput(['login_id' => $loginId]);
        }

        if ($user->is_super_admin) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            $audit->recordForUser($user, 'auth.login.success', [
                'login_surface' => 'global',
            ], $request);

            return redirect()->route('super.dashboard');
        }

        $tenant = $user->tenant;

        if (!$tenant) {
            $audit->recordForUser($user, 'auth.login.denied', [
                'login_surface' => 'global',
            ], $request, 'missing_tenant');

            return back()->withErrors(['login_id' => 'Account not linked to any school.']);
        }

        $decision = $tenantAccess->applicationAccess($tenant);
        if ($decision->isDenied()) {
            $audit->recordForUser($user, 'auth.login.denied', [
                'login_surface' => 'global',
                'state' => $decision->state,
            ], $request, 'tenant_' . $decision->state);

            return back()->withErrors(['login_id' => 'This school account is currently unavailable. Contact support.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->put('tenant_id', $tenant->id);
        $request->session()->put('tenant_slug', $tenant->slug);
        $user->forceFill(['last_login_at' => now()])->save();
        $audit->recordForUser($user, 'auth.login.success', [
            'login_surface' => 'global',
        ], $request);

        return $redirector->redirectFor($user);
    }

    public function logout(Request $request, AuthAuditLogger $audit)
    {
        if ($user = Auth::user()) {
            $audit->recordForUser($user, 'auth.logout', [
                'tenant_slug' => $user->tenant?->slug,
            ], $request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /** Which dedicated login a non-super (school) user should use. */
    private function schoolSurfaceFor(User $user): array
    {
        if ($user->isStudent()) {
            return ['url' => route('student.login'), 'hint' => 'Use the student login.'];
        }
        if ($user->isParent()) {
            return ['url' => route('parent.login'), 'hint' => 'Use the parent login.'];
        }
        $adminRoles = ['admin', 'principal', 'head', 'head_teacher', 'vice_principal', 'academic_administrator', 'admission_officer'];
        if (in_array($user->roleKey(), $adminRoles, true)) {
            return ['url' => route('admin.login'), 'hint' => 'Use the administrator login.'];
        }
        return ['url' => route('staff.login'), 'hint' => 'Use the staff login.'];
    }
}
