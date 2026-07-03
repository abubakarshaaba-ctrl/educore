<?php
namespace App\Models;
class TransportAssignment extends BaseTenantModel {
    protected $fillable = ['tenant_id','student_id','route_id','pickup_stop','direction'];
    public function student() { return $this->belongsTo(Student::class); }
    public function route() { return $this->belongsTo(TransportRoute::class,'route_id'); }
}
