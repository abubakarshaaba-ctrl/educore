<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AgentMessageRead extends Model
{
    protected $table = 'agent_message_reads';
    protected $fillable = ['message_id','agent_id','read_at'];
    protected function casts(): array { return ['read_at'=>'datetime']; }
}
