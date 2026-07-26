<?php

namespace App\Http\Controllers;

use App\Models\PlatformAgent;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SchoolRegistrationController extends Controller
{
    public function show(Request $request): View
    {
        // Accept ref from URL param or from session (set when visiting homepage with ?ref=)
        $ref = strtoupper($request->get('ref', session('referral_code', '')));
        return view('school-register', compact('ref'));
    }

    public function store(Request $request, TenantOnboardingService $onboarding): RedirectResponse
    {
        $request->merge([
            'slug' => Tenant::normalizeSlug($request->input('school_name')),
        ]);

        $validated = $request->validate([
            'school_name'  => ['required', 'string', 'max:150'],
            'slug'         => Tenant::slugRules(),
            'admin_name'   => ['required', 'string', 'max:120'],
            'admin_email'  => ['required', 'email', 'max:180', 'unique:users,email'],
            'phone'        => ['required', 'string', 'max:30'],
            'password'     => ['required', 'confirmed', Password::min(8)],
            'ref'          => ['nullable', 'string', 'max:20'],
        ]);

        // Resolve the agent from the referral code (form field takes priority, session is fallback)
        $refCode        = strtoupper($validated['ref'] ?? session('referral_code', ''));
        $referringAgent = null;
        if ($refCode) {
            $referringAgent = PlatformAgent::where('referral_code', $refCode)
                ->where('is_active', true)
                ->first();
        }
        session()->forget('referral_code'); // consumed

        $tenant = DB::transaction(function () use ($validated, $onboarding, $referringAgent) {
            $trialDays = (int) DB::table('platform_settings')->where('key', 'trial_days')->value('value') ?: 30;
            $trialEnds = now()->addDays($trialDays);

            $tenant = Tenant::create([
                'name'                    => $validated['school_name'],
                'slug'                    => $validated['slug'],
                'email'                   => $validated['admin_email'],
                'phone'                   => $validated['phone'],
                'status'                  => Tenant::STATUS_ACTIVE,
                'subscription_expires_at' => $trialEnds,
                'theme_primary'           => '#071E45',
                'theme_accent'            => '#D79A21',
                'theme_sidebar'           => '#071E45',
                'referred_by_agent_id'    => $referringAgent?->id,
            ]);

            // Create agent referral record so the agent's portal counts this school
            if ($referringAgent && Schema::hasTable('agent_referrals')) {
                DB::table('agent_referrals')->insert([
                    'agent_id'          => $referringAgent->id,
                    'tenant_id'         => $tenant->id,
                    'subscription_id'   => null,
                    'sale_amount'       => 0,
                    'commission_amount' => 0,
                    'status'            => 'pending',
                    'sale_date'         => now()->toDateString(),
                    'notes'             => 'Self-registered via referral link',
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }

            $admin = User::create([
                'tenant_id'              => $tenant->id,
                'name'                   => $validated['admin_name'],
                'email'                  => $validated['admin_email'],
                'password'               => Hash::make($validated['password']),
                'role'                   => 'admin',
                'is_super_admin'         => false,
                'is_active'              => true,
                'employment_status'      => User::STAFF_STATUS_ACTIVE,
                'employment_started_at'  => now()->toDateString(),
                'status_changed_at'      => now(),
            ]);
            $admin->assignRole('admin');

            $onboarding->createProvisioningDefaults($tenant);

            return $tenant;
        });

        $user = User::where('tenant_id', $tenant->id)->where('role', 'admin')->first();

        try {
            $tenant->notifyAdmins(new \App\Notifications\Tenant\TenantWelcomeNotification(
                $tenant,
                $tenant->subscription_expires_at?->format('d M Y')
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Tenant welcome notification failed: ' . $e->getMessage());
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'Welcome to EduCore! Let\'s get your school set up.');
    }
}
