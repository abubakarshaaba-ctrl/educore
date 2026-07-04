<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TenantSubscription extends Model {
    protected $table = 'tenant_subscriptions';
    protected $fillable = ['tenant_id','plan_id','status','billing_cycle','amount_paid','starts_at','expires_at','next_billing_date','payment_reference','payment_method','notes','created_by'];
    protected function casts(): array { return ['starts_at'=>'date','expires_at'=>'date','next_billing_date'=>'date','amount_paid'=>'float']; }
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function plan()   { return $this->belongsTo(SubscriptionPlan::class, 'plan_id'); }
    public function isActive(): bool { return $this->status === 'active' && $this->expires_at?->isFuture() === true; }
}
