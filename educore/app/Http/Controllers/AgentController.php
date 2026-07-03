<?php
namespace App\Http\Controllers;

use App\Models\AgentPayout;
use App\Models\PlatformAgent;
use App\Models\AgentReferral;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class AgentController extends Controller
{
    private function guard(): void
    {
        abort_unless(auth()->user()?->is_super_admin, 403);
    }

    public function index()
    {
        $this->guard();

        $agents = PlatformAgent::withCount('referrals')->latest()->paginate(20);
        $stats  = [
            'total_agents'      => PlatformAgent::count(),
            'active_agents'     => PlatformAgent::where('is_active', true)->count(),
            'total_commissions' => PlatformAgent::sum('total_earned') ?? 0,
            'unpaid'            => PlatformAgent::selectRaw('SUM(total_earned - total_paid) as unpaid')->value('unpaid') ?? 0,
        ];
        $settings = $this->getSettings();
        return view('super.agents', compact('agents', 'stats', 'settings'));
    }

    public function store(Request $request)
    {
        $this->guard();

        $data = $request->validate([
            'name'            => ['required', 'string', 'max:150'],
            'email'           => ['required', 'email', 'unique:platform_agents'],
            'phone'           => ['nullable', 'string'],
            'state'           => ['nullable', 'string'],
            'commission_rate' => ['required', 'numeric', 'min:1', 'max:50'],
        ]);
        $data['referral_code'] = strtoupper(Str::random(8));
        PlatformAgent::create($data);
        return back()->with('success', 'Agent created. Referral code: ' . $data['referral_code']);
    }

    public function show(PlatformAgent $agent)
    {
        $this->guard();

        $referrals = AgentReferral::where('agent_id', $agent->id)
            ->with('tenant')->latest()->paginate(20);
        return view('super.agent-show', compact('agent', 'referrals'));
    }

    public function toggle(PlatformAgent $agent)
    {
        $this->guard();

        $agent->update(['is_active' => !$agent->is_active]);
        return back()->with('success', 'Agent status updated.');
    }

    public function updatePassword(Request $request, PlatformAgent $agent)
    {
        $this->guard();

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $agent->update([
            'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
        ]);

        return back()->with('success', 'Agent password updated.');
    }

    public function approveCommission(AgentReferral $referral)
    {
        $this->guard();

        $referral->update(['status' => 'approved']);
        $referral->agent->increment('total_earned', $referral->commission_amount);
        return back()->with('success', 'Commission approved.');
    }

    public function recordPayment(Request $request, PlatformAgent $agent)
    {
        $this->guard();

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'note'   => ['nullable', 'string', 'max:255'],
        ]);
        $max = $agent->unpaidBalance();
        if ($data['amount'] > $max) {
            return back()->withErrors(['amount' => "Cannot pay more than unpaid balance of ₦" . number_format($max)]);
        }
        $agent->increment('total_paid', $data['amount']);
        return back()->with('success', '₦' . number_format($data['amount']) . ' recorded as paid to ' . $agent->name . '.');
    }

    // ── Broadcast Message to Agents ──────────────────────────────────
    public function sendMessage(Request $request)
    {
        $this->guard();

        $data = $request->validate([
            'subject'  => ['required','string','max:200'],
            'body'     => ['required','string'],
            'audience' => ['required','in:all,active,inactive'],
        ]);
        $data['sent_by'] = auth()->id();
        $data['sent_at'] = now();
        \App\Models\AgentMessage::create($data);
        return back()->with('success', 'Message broadcast to agents.');
    }

    // ── Activate Agent + Set Password ────────────────────────────────
    public function activate(Request $request, PlatformAgent $agent)
    {
        $this->guard();

        $data = $request->validate([
            'password' => ['required','min:8'],
        ]);
        $agent->update([
            'is_active' => true,
            'password'  => \Illuminate\Support\Facades\Hash::make($data['password']),
        ]);
        return back()->with('success', "Agent {$agent->name} activated. They can now log in to the portal.");
    }

    // ── Record Commission from subscription payment ───────────────────
    // Called from PaymentGatewayController::paystackWebhook() on successful subscription.
    // Commission is calculated as a PERCENTAGE of the subscription sale amount.
    // If agent has reached the bonus threshold (number of schools), their bonus
    // commission rate is applied instead of the base rate.
    public static function recordReferralCommission(int $tenantId, float $saleAmount): void
    {
        $tenant = \App\Models\Tenant::find($tenantId);
        if (!$tenant || !$tenant->referred_by_agent_id) return;

        $agent = PlatformAgent::find($tenant->referred_by_agent_id);
        if (!$agent || !$agent->is_active) return;

        // Get programme settings to check bonus rate
        $settings = \Illuminate\Support\Facades\Cache::get('agent_settings', [
            'bonus_threshold' => 5,
            'bonus_amount'    => 0, // bonus_amount stores bonus % rate
        ]);

        // Count approved referrals for this agent to determine which rate applies
        $approvedCount = \App\Models\AgentReferral::where('agent_id', $agent->id)
            ->where('status', 'approved')->count();

        $bonusThreshold = (int) ($settings['bonus_threshold'] ?? 5);
        $bonusRate      = (float) ($settings['bonus_amount'] ?? 0);

        // Apply bonus % rate if agent has hit threshold and bonus rate > base rate
        $applicableRate = $agent->commission_rate;
        $rateNote       = "Base rate {$applicableRate}%";

        if ($bonusRate > 0 && $approvedCount >= $bonusThreshold && $bonusRate > $agent->commission_rate) {
            $applicableRate = $bonusRate;
            $rateNote       = "Bonus rate {$applicableRate}% (reached {$bonusThreshold} referrals)";
        }

        $commission = round($saleAmount * ($applicableRate / 100), 2);

        $referral = \App\Models\AgentReferral::create([
            'agent_id'          => $agent->id,
            'tenant_id'         => $tenantId,
            'sale_amount'       => $saleAmount,
            'commission_amount' => $commission,
            'status'            => 'approved',
            'sale_date'         => now()->toDateString(),
            'notes'             => "Auto-credited on subscription payment. {$rateNote}.",
        ]);

        $payoutReference = 'PAYOUT-' . strtoupper(Str::random(10));
        $payoutStatus = $agent->bank_account_number ? 'paid' : 'pending';

        AgentPayout::create([
            'agent_id'       => $agent->id,
            'amount'         => $commission,
            'reference'      => $payoutReference,
            'bank_name'      => $agent->bank_name,
            'account_number' => $agent->bank_account_number,
            'status'         => $payoutStatus,
            'note'           => "Auto-remitted from school subscription payment. {$rateNote}.",
            'processed_by'   => auth()->id(),
        ]);

        if ($payoutStatus === 'paid') {
            $referral->update(['status' => 'paid']);
            $agent->increment('total_paid', $commission);
        }

        // Instantly credit the agent's earned balance
        $agent->increment('total_earned', $commission);
    }

    // ── Agent Programme Settings ──────────────────────────────────────
    public function settings()
    {
        $this->guard();

        $settings = $this->getSettings();
        return view('super.agent-settings', compact('settings'));
    }

    public function saveSettings(Request $request)
    {
        $this->guard();

        $data = $request->validate([
            'default_commission_rate' => ['required', 'numeric', 'min:1', 'max:50'],
            'bonus_threshold'         => ['nullable', 'numeric', 'min:0'],
            'bonus_amount'            => ['nullable', 'numeric', 'min:0'],
            'auto_approve'            => ['boolean'],
            'payment_cycle'           => ['required', 'in:weekly,monthly,manual'],
            'min_payout'              => ['nullable', 'numeric', 'min:0'],
            'programme_name'          => ['nullable', 'string', 'max:100'],
            'programme_description'   => ['nullable', 'string'],
            'terms_text'              => ['nullable', 'string'],
        ]);
        Cache::put('agent_settings', $data, now()->addYears(10));
        return back()->with('success', 'Agent programme settings saved.');
    }

    private function getSettings(): array
    {
        return Cache::get('agent_settings', [
            'default_commission_rate' => 10,
            'bonus_threshold'         => 5,
            'bonus_amount'            => 5000,
            'auto_approve'            => false,
            'payment_cycle'           => 'monthly',
            'min_payout'              => 5000,
            'programme_name'          => 'Enterprise SMS Referral Programme',
            'programme_description'   => 'Earn commission for every school you refer that subscribes to Enterprise SMS.',
            'terms_text'              => '',
        ]);
    }
}
