<?php
namespace App\Models;
class TransportBus extends BaseTenantModel {
    protected $fillable = ['tenant_id','plate_number','model','capacity','year','is_active'];
    protected function casts(): array { return ['is_active'=>'boolean']; }
    public function routes() { return $this->hasMany(TransportRoute::class,'bus_id'); }
}
