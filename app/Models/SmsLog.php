<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SmsLog extends Model {
    protected $table = 'sms_logs';
    protected $fillable = ['campaign_id','phone','message','status','sent_at','error'];
    protected function casts(): array { return ['sent_at'=>'datetime']; }
    public function campaign() { return $this->belongsTo(SmsCampaign::class); }
}
