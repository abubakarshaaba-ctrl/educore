<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantResetPasswordNotification;
use App\Services\Auth\AuthAuditLogger;
use App\Services\TenantPortal\TenantBrandingService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class TenantPasswordResetController extends Controller
{
    private const GENERIC_STATUS = 'If the account is eligible, a password reset link has been sent.';
    private const GENERIC_RESET_ERROR = 'The password reset link is invalid or has expired.';

    public function showForgot(Request $request, string $slug, TenantBrandingService $branding)
    {
        $tenant = $this->tenant($request);

        return view('tenant.forgot-password', [
            'tenant' => $tenant,
            'branding' => $branding->forTenant($tenant),
        ]);
    }

    public function sendResetLink(Request $request, string $slug, AuthAuditLogger $audit)
    {
        $tenant = $this->tenant($request);
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:180'],
        ]);

        $email = Str::lower(trim($validated['email']));
        $user = $this->eligibleUser($tenant, $email);

        if ($user) {
            $token = Password::broker()->createToken($user);
            $user->notify(new TenantResetPasswordNotification($token, $tenant));
            $audit->recordForUser($user, 'auth.password_reset.requested', [
                'tenant_slug' => $tenant->slug,
            ], $request);
        } else {
            $audit->recordForTenant($tenant, 'auth.password_reset.denied', [
                'email_hash' => hash('sha256', $email),
                'tenant_slug' => $tenant->slug,
            ], $request, 'unknown_or_ineligible_account');
        }

        return back()->with('status', self::GENERIC_STATUS);
    }

    public function showReset(Request $request, string $slug, string $token, TenantBrandingService $branding)
    {
        $tenant = $this->tenant($request);

        return view('tenant.reset-password', [
            'tenant' => $tenant,
            'branding' => $branding->forTenant($tenant),
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request, string $slug, AuthAuditLogger $audit)
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
                'tenant_slug' => $tenant->slug,
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
            'tenant_slug' => $tenant->slug,
        ], $request);

        return redirect()
            ->route('tenant.login', $tenant->slug)
            ->with('status', 'Your password has been reset. You may sign in now.');
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
}
