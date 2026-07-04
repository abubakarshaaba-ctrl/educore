<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCardPublication extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id','class_arm_id','term_id','status',
        'published_at','published_by','archived_at','note'
    ];
    protected function casts(): array {
        return ['published_at'=>'datetime','archived_at'=>'datetime'];
    }
    public function classArm(): BelongsTo  { return $this->belongsTo(ClassArm::class); }
    public function term(): BelongsTo      { return $this->belongsTo(Term::class); }
    public function publishedBy(): BelongsTo { return $this->belongsTo(User::class,'published_by'); }
    public function isPublished(): bool    { return $this->status === 'published'; }
}
