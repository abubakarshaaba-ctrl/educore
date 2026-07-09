<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantResetPasswordNotification;
use App\Services\Auth\AuthAuditLogger;
use App\Services\Auth\LoginRedirector;
use App\Services\Auth\LoginUserResolver;
use App\Services\TenantPortal\TenantBrandingService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class TenantHostController extends Controller
{
    private const GENERIC_LOGIN_ERROR = 'The login details are invalid or this account cannot access this school portal.';
    private const GENERIC_RESET_STATUS = 'If the account is eligible, a password reset link has been sent.';
    private const GENERIC_RESET_ERROR = 'The password reset link is invalid or has expired.';

    public function landing(Request $request, TenantBrandingService $branding)
    {
        $tenant = $this->tenant($request);

        return view('tenant.landing', [
            'tenant' => $tenant,
            'branding' => $branding->forTenant($tenant),
            'landingUrl' => $this->hostUrl($request, '/'),
            'loginUrl' => $this->hostUrl($request, '/login'),
            'forgotPasswordUrl' => $this->hostUrl($request, '/forgot-password'),
            'admissionsUrl' => $this->hostUrl($request, '/apply'),
        ]);
    }

    public function showLogin(Request $request, TenantBrandingService $branding, LoginRedirector $redirector)
    {
        $tenant = $this->tenant($request);

        if (auth()->check()) {
            return $redirector->redirectFor(auth()->user());
        }

        return view('tenant.login', [
            'tenant' => $tenant,
            'branding' => $branding->forTenant($tenant),
            'landingUrl' => $this->hostUrl($request, '/'),
            'loginAction' => $this->hostUrl($request, '/login'),
            'forgotPasswordUrl' => $this->hostUrl($request, '/forgot-password'),
            'admissionsUrl' => $this->hostUrl($request, '/apply'),
        ]);
    }

    public function login(
        Request $request,
        LoginUserResolver $users,
        LoginRedirector $redirector,
        AuthAuditLogger $audit
    ) {
        $tenant = $this->tenant($request);

        $validated = $request->validate([
            'login_id' => ['required', 'string', 'max:180'],
            'password' => ['required', 'string'],
            'remember' => ['nullable'],
        ]);

        $loginId = trim($validated['login_id']);
        $user = $users->resolveTenantUser($tenant, $loginId);

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            $audit->recordForTenant($tenant, 'auth.login.denied', [
                'login_id_hash' => hash('sha256', mb_strtolower($loginId)),
                'host' => $request->getHost(),
                'login_surface' => 'tenant_host',
            ], $request, 'invalid_credentials_or_tenant_mismatch');

            return back()
                ->withErrors(['login_id' => self::GENERIC_LOGIN_ERROR])
                ->withInput(['login_id' => $loginId]);
        }

        if (!$this->canUseTenantLogin($user, $tenant)) {
            $audit->recordForUser($user, 'auth.login.denied', [
                'host' => $request->getHost(),
                'login_surface' => 'tenant_host',
            ], $request, 'inactive_or_ineligible_account');

            return back()
                ->withErrors(['login_id' => self::GENERIC_LOGIN_ERROR])
                ->withInput(['login_id' => $loginId]);
        }

        auth()->login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->put('tenant_id', $tenant->id);
        $request->session()->put('tenant_slug', $tenant->slug);
        $request->session()->put('tenant_host', $request->getHost());
        $user->forceFill(['last_login_at' => now()])->save();

        $audit->recordForUser($user, 'auth.login.success', [
            'host' => $request->getHost(),
            'login_surface' => 'tenant_host',
        ], $request);

        // Return 200 + JS redirect instead of 302 to ensure the session cookie
        // is delivered to the browser (Cloudflare strips Set-Cookie from 302s).
        $dest = $redirector->redirectFor($user)->getTargetUrl();
        return response()->view('auth.redirecting', ['url' => $dest]);
    }

    public function showForgot(Request $request, TenantBrandingService $branding)
    {
        $tenant = $this->tenant($request);

        return view('tenant.forgot-password', [
            'tenant' => $tenant,
            'branding' => $branding->forTenant($tenant),
            'landingUrl' => $this->hostUrl($request, '/'),
            'loginUrl' => $this->hostUrl($request, '/login'),
            'forgotPasswordAction' => $this->hostUrl($request, '/forgot-password'),
        ]);
    }

    public function sendResetLink(Request $request, AuthAuditLogger $audit)
    {
        $tenant = $this->tenant($request);
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:180'],
        ]);

        $email = Str::lower(trim($validated['email']));
        $user = $this->eligibleUser($tenant, $email);

        if ($user) {
            $token = Password::broker()->createToken($user);
            $user->notify(new TenantResetPasswordNotification($token, $tenant, $request->getSchemeAndHttpHost()));
            $audit->recordForUser($user, 'auth.password_reset.requested', [
                'host' => $request->getHost(),
                'login_surface' => 'tenant_host',
            ], $request);
        } else {
            $audit->recordForTenant($tenant, 'auth.password_reset.denied', [
                'email_hash' => hash('sha256', $email),
                'host' => $request->getHost(),
            ], $request, 'unknown_or_ineligible_account');
        }

        return back()->with('status', self::GENERIC_RESET_STATUS);
    }

    public function showReset(Request $request, TenantBrandingService $branding)
    {
        $tenant = $this->tenant($request);
        $token = (string) $request->route('token');

        return view('tenant.reset-password', [
            'tenant' => $tenant,
            'branding' => $branding->forTenant($tenant),
            'token' => $token,
            'email' => $request->query('email'),
            'loginUrl' => $this->hostUrl($request, '/login'),
            'resetPasswordAction' => $this->hostUrl($request, '/reset-password'),
        ]);
    }

    public function reset(Request $request, AuthAuditLogger $audit)
    {
        $tenant = $this->tenant($request);
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:180'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $email = Str::lower(trim($validated['email']));
        $user = $this->eligibleUser($tenant, $email);
        $broker = Password::broker();

        if (!$user || !$broker->tokenExists($user, $validated['token'])) {
            $audit->recordForTenant($tenant, 'auth.password_reset.failed', [
                'email_hash' => hash('sha256', $email),
                'host' => $request->getHost(),
            ], $request, 'invalid_token_or_ineligible_account');

            return back()
                ->withErrors(['email' => self::GENERIC_RESET_ERROR])
                ->withInput(['email' => $email]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        $broker->deleteToken($user);
        event(new PasswordReset($user));

        $audit->recordForUser($user, 'auth.password_reset.completed', [
            'host' => $request->getHost(),
            'login_surface' => 'tenant_host',
        ], $request);

        return redirect('/login')
            ->with('status', 'Your password has been reset. You may sign in now.');
    }

    public function applyLanding(Request $request, PublicAdmissionController $admissions)
    {
        return $admissions->landing($this->tenant($request)->slug);
    }

    public function applyForm(Request $request, PublicAdmissionController $admissions)
    {
        return $admissions->form($this->tenant($request)->slug);
    }

    public function applySubmit(Request $request, PublicAdmissionController $admissions)
    {
        return $admissions->submit($request, $this->tenant($request)->slug);
    }

    public function applyStatusForm(Request $request, PublicAdmissionController $admissions)
    {
        return $admissions->statusForm($this->tenant($request)->slug);
    }

    public function applyStatus(Request $request, PublicAdmissionController $admissions)
    {
        return $admissions->checkStatus($request, $this->tenant($request)->slug);
    }

    public function applySuccess(Request $request, PublicAdmissionController $admissions)
    {
        return $admissions->success($this->tenant($request)->slug, (string) $request->route('app'));
    }

    public function careersLanding(Request $request, PublicRecruitmentController $careers)
    {
        return $careers->landing($this->tenant($request)->slug);
    }

    public function careersShow(Request $request, PublicRecruitmentController $careers)
    {
        return $careers->show($this->tenant($request)->slug, (int) $request->route('posting'));
    }

    public function careersApply(Request $request, PublicRecruitmentController $careers)
    {
        return $careers->apply($request, $this->tenant($request)->slug, (int) $request->route('posting'));
    }

    private function canUseTenantLogin(User $user, Tenant $tenant): bool
    {
        if ((int) $user->tenant_id !== (int) $tenant->id || $user->isSuperAdmin() || !(bool) $user->is_active) {
            return false;
        }

        // Staff must also have active employment; students and parents only need is_active
        if ($user->isTenantStaff()) {
            return $user->isEmploymentActive();
        }

        return $user->isStudent() || $user->isParent();
    }

    private function eligibleUser(Tenant $tenant, string $email): ?User
    {
        $user = User::tenantStaff($tenant->id)
            ->where('email', $email)
            ->first();

        if (!$user || !$user->is_active || !$user->isEmploymentActive()) {
            return null;
        }

        return $user;
    }

    private function tenant(Request $request): Tenant
    {
        $tenant = $request->attributes->get('resolved_tenant');
        abort_unless($tenant instanceof Tenant, 404);

        return $tenant;
    }

    private function hostUrl(Request $request, string $path): string
    {
        return rtrim($request->getSchemeAndHttpHost(), '/') . '/' . ltrim($path, '/');
    }
}
