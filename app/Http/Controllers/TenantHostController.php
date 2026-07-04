<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\LoginController;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantResetPasswordNotification;
use App\Services\Auth\AuthAuditLogger;
use App\Services\Auth\LoginRedirector;
use App\Services\Auth\LoginUserResolver;
use App\Services\TenantAccessService;
use App\Services\TenantPortal\TenantBrandingService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class TenantHostController extends Controller
{
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

    /**
     * Backward-compatible tenant route adapter.
     * Authentication logic and the rendered page now live exclusively in LoginController.
     */
    public function showLogin(Request $request, LoginController $controller, LoginRedirector $redirector)
    {
        return $controller->showLogin($request, $redirector);
    }

    /**
     * Backward-compatible tenant route adapter.
     * This method performs no authentication logic of its own.
     */
    public function login(
        Request $request,
        LoginController $controller,
        LoginUserResolver $users,
        LoginRedirector $redirector,
        AuthAuditLogger $audit,
        TenantAccessService $tenantAccess
    ) {
        return $controller->login($request, $users, $redirector, $audit, $tenantAccess);
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
