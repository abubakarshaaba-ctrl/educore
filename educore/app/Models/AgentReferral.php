<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentReferral extends Model
{
    protected $table = 'agent_referrals';
    protected $fillable = [
        'agent_id','tenant_id','subscription_id',
        'sale_amount','commission_amount','status','sale_date','notes',
    ];
    protected function casts(): array
    {
        return ['sale_date'=>'date','sale_amount'=>'float','commission_amount'=>'float'];
    }
    public function agent(): BelongsTo  { return $this->belongsTo(PlatformAgent::class,'agent_id'); }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class,'tenant_id'); }
}
