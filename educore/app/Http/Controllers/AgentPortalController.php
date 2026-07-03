<?php
namespace App\Http\Controllers;

use App\Models\PlatformAgent;
use App\Models\AgentReferral;
use App\Models\AgentMessage;
use App\Models\AgentMessageRead;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AgentPortalController extends Controller
{
    // ── Helper ────────────────────────────────────────────────────────
    private function agent(): PlatformAgent
    {
        $id = Session::get('agent_id');
        if (!$id) {
            throw new HttpResponseException(redirect()->route('agent.portal.login'));
        }

        $agent = PlatformAgent::find($id);
        if (!$agent || !$agent->is_active) {
            Session::forget('agent_id');
            throw new HttpResponseException(redirect()->route('agent.portal.login'));
        }

        return $agent;
    }

    // ── Auth ──────────────────────────────────────────────────────────
    public function loginForm()
    {
        if (Session::get('agent_id')) return redirect()->route('agent.portal.dashboard');
        return view('agent.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required','email'],
            'password' => ['required'],
        ]);

        $agent = PlatformAgent::where('email', $request->email)->first();
        if (!$agent || !$agent->password || !Hash::check($request->password, $agent->password)) {
            return back()->withErrors(['email' => 'Invalid credentials. Contact platform admin if you need access.']);
        }
        if (!$agent->is_active) {
            return back()->withErrors(['email' => 'Your agent account has been deactivated.']);
        }

        Session::put('agent_id', $agent->id);
        $agent->update(['last_login_at' => now()]);
        return redirect()->route('agent.portal.dashboard');
    }

    public function logout(Request $request)
    {
        Session::forget('agent_id');
        return redirect()->route('agent.portal.login');
    }

    // ── Register (public onboarding) ──────────────────────────────────
    public function registerForm()
    {
        return view('agent.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required','string','max:150'],
            'email'    => ['required','email','unique:platform_agents,email'],
            'phone'    => ['required','string','max:30'],
            'state'    => ['required','string','max:80'],
            'password' => ['required','min:8','confirmed'],
        ]);

        $settings = \Illuminate\Support\Facades\Cache::get('agent_settings', [
            'default_commission_rate' => 10,
        ]);

        $agent = PlatformAgent::create([
            'name'            => $data['name'],
            'email'           => $data['email'],
            'phone'           => $data['phone'],
            'state'           => $data['state'],
            'password'        => Hash::make($data['password']),
            'commission_rate' => $settings['default_commission_rate'],
            'is_active'       => false, // pending approval
            'referral_code'   => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);

        return view('agent.registered', compact('agent'));
    }

    // ── Dashboard ─────────────────────────────────────────────────────
    public function dashboard()
    {
        $agent = $this->agent();
        $referrals = AgentReferral::where('agent_id', $agent->id)
            ->with('tenant')->latest()->limit(10)->get();
        $stats = [
            'total_schools'   => AgentReferral::where('agent_id', $agent->id)->count(),
            'approved'        => AgentReferral::where('agent_id', $agent->id)->where('status','approved')->count(),
            'total_earned'    => $agent->total_earned ?? 0,
            'unpaid'          => $agent->unpaidBalance(),
        ];
        $unreadCount = AgentMessage::whereNotIn('id',
            AgentMessageRead::where('agent_id', $agent->id)->pluck('message_id')
        )->count();

        return view('agent.dashboard', compact('agent','referrals','stats','unreadCount'));
    }

    // ── My Schools ────────────────────────────────────────────────────
    public function schools()
    {
        $agent     = $this->agent();
        $referrals = AgentReferral::where('agent_id', $agent->id)
            ->with('tenant')->latest()->paginate(20);
        return view('agent.schools', compact('agent','referrals'));
    }

    // ── Earnings ─────────────────────────────────────────────────────
    public function earnings()
    {
        $agent    = $this->agent();
        $referrals= AgentReferral::where('agent_id', $agent->id)->where('status','approved')
            ->with('tenant')->latest()->paginate(20);
        $payouts  = \App\Models\AgentPayout::where('agent_id', $agent->id)->latest()->get();
        return view('agent.earnings', compact('agent','referrals','payouts'));
    }

    // ── Messages ──────────────────────────────────────────────────────
    public function messages()
    {
        $agent    = $this->agent();
        $messages = AgentMessage::latest()->paginate(20);
        // Mark all as read
        foreach ($messages as $msg) {
            AgentMessageRead::firstOrCreate(
                ['message_id' => $msg->id, 'agent_id' => $agent->id],
                ['read_at' => now()]
            );
        }
        return view('agent.messages', compact('agent','messages'));
    }

    // ── Profile ───────────────────────────────────────────────────────
    public function profile()
    {
        $agent = $this->agent();
        return view('agent.profile', compact('agent'));
    }

    public function updateProfile(Request $request)
    {
        $agent = $this->agent();
        $data  = $request->validate([
            'phone'               => ['nullable','string','max:30'],
            'state'               => ['nullable','string','max:80'],
            'bank_name'           => ['nullable','string','max:100'],
            'bank_account_number' => ['nullable','string','max:30'],
            'bank_account_name'   => ['nullable','string','max:150'],
        ]);
        $agent->update($data);
        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $agent = $this->agent();
        $data  = $request->validate([
            'current_password' => ['required'],
            'password'         => ['required','min:8','confirmed'],
        ]);
        if (!Hash::check($data['current_password'], $agent->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }
        $agent->update(['password' => Hash::make($data['password'])]);
        return back()->with('success', 'Password changed.');
    }
}
