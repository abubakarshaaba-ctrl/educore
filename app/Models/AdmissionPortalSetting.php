<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionPortalSetting extends BaseTenantModel
{
    protected $table = 'admission_portal_settings';

    protected $fillable = [
        'tenant_id', 'is_open', 'opens_on', 'closes_on', 'academic_year',
        'application_fee', 'welcome_message', 'requirements',
        'require_passport', 'require_birth_cert', 'require_report_card',
        'notify_guardian_sms', 'notify_guardian_email',
        'auto_shortlist', 'footer_note',
    ];

    protected $casts = [
        'is_open'            => 'boolean',
        'require_passport'   => 'boolean',
        'require_birth_cert' => 'boolean',
        'require_report_card'=> 'boolean',
        'notify_guardian_sms'=> 'boolean',
        'notify_guardian_email' => 'boolean',
        'auto_shortlist'     => 'boolean',
        'opens_on'           => 'date',
        'closes_on'          => 'date',
    ];

    public function tenant() { return $this->belongsTo(Tenant::class); }

    public function isCurrentlyOpen(): bool
    {
        if (!$this->is_open) return false;
        $now = now()->toDateString();
        if ($this->opens_on && $this->opens_on->toDateString() > $now) return false;
        if ($this->closes_on && $this->closes_on->toDateString() < $now) return false;
        return true;
    }
}
