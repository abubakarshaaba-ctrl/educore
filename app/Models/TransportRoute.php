<?php
namespace App\Models;
class TransportRoute extends BaseTenantModel {
    protected $fillable = [
        'tenant_id','name','description','fare',
        'morning_time','evening_time','bus_id',
        'driver_id','assistant_id','is_active',
    ];
    protected function casts(): array { return ['fare'=>'float','is_active'=>'boolean']; }
    public function bus()       { return $this->belongsTo(TransportBus::class,'bus_id'); }
    public function driver()    { return $this->belongsTo(User::class,'driver_id'); }
    public function assistant() { return $this->belongsTo(User::class,'assistant_id'); }
    public function assignments(){ return $this->hasMany(TransportAssignment::class,'route_id'); }
}
