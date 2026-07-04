<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SubscriptionPlan extends Model {
    protected $table = 'subscription_plans';
    protected $fillable = ['name','slug','description','monthly_price','annual_price','max_students','max_staff','has_cbt','has_sms','has_paystack','features','is_active','sort_order'];
    protected function casts(): array { return ['features'=>'array','has_cbt'=>'boolean','has_sms'=>'boolean','has_paystack'=>'boolean','is_active'=>'boolean']; }
    public function subscriptions() { return $this->hasMany(TenantSubscription::class, 'plan_id'); }
}
