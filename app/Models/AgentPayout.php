<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentPayout extends Model
{
    protected $table = 'agent_payouts';
    protected $fillable = [
        'agent_id','amount','reference','bank_name','account_number','status','note','processed_by',
    ];
    protected function casts(): array { return ['amount'=>'float']; }
    public function agent(): BelongsTo { return $this->belongsTo(PlatformAgent::class,'agent_id'); }
}
