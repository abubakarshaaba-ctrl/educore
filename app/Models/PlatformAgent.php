<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class PlatformAgent extends Authenticatable
{
    use Notifiable;

    protected $table = 'platform_agents';
    protected $guard = 'agent';

    protected $fillable = [
        'name','email','phone','state',
        'commission_rate','total_earned','total_paid',
        'is_active','referral_code','password',
        'bank_name','bank_account_number','bank_account_name','notes',
        'last_login_at',
    ];

    protected $hidden = ['password','remember_token'];

    protected function casts(): array
    {
        return [
            'is_active'       => 'boolean',
            'commission_rate' => 'float',
            'total_earned'    => 'float',
            'total_paid'      => 'float',
            'last_login_at'   => 'datetime',
        ];
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(AgentReferral::class, 'agent_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(AgentPayout::class, 'agent_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AgentMessageRead::class, 'agent_id');
    }

    public function unpaidBalance(): float
    {
        return max(0, ($this->total_earned ?? 0) - ($this->total_paid ?? 0));
    }

    public function onboardingUrl(): string
    {
        return url('/agent/portal/login');
    }

    public function referralLink(): string
    {
        return url('/?ref=' . $this->referral_code);
    }

    public function approvedReferrals(): int
    {
        return $this->referrals()->where('status', 'approved')->count();
    }
}
