<?php
namespace App\Models;
class SmsCampaign extends BaseTenantModel {
    protected $fillable = ['tenant_id','title','message','audience','class_arm_id','recipient_count','status','schedule_at','sent_at'];
    protected function casts(): array { return ['schedule_at'=>'datetime','sent_at'=>'datetime']; }
    public function logs() { return $this->hasMany(SmsLog::class,'campaign_id'); }
    public function classArm() { return $this->belongsTo(ClassArm::class); }
}
