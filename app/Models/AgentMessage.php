<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentMessage extends Model
{
    protected $table = 'agent_messages';
    protected $fillable = ['subject','body','audience','sent_by','sent_at'];
    protected function casts(): array { return ['sent_at'=>'datetime']; }
    public function reads(): HasMany { return $this->hasMany(AgentMessageRead::class,'message_id'); }
}
