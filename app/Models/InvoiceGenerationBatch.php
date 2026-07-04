<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceGenerationBatch extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'term_id', 'generated_by', 'scope',
        'class_level_id', 'class_arm_id',
        'total_students', 'generated_count', 'skipped_count',
        'total_value', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['total_value' => 'float'];
    }

    public function term(): BelongsTo        { return $this->belongsTo(Term::class); }
    public function generatedBy(): BelongsTo { return $this->belongsTo(User::class, 'generated_by'); }
    public function classLevel(): BelongsTo  { return $this->belongsTo(ClassLevel::class); }
    public function classArm(): BelongsTo    { return $this->belongsTo(ClassArm::class); }
    public function invoices(): HasMany      { return $this->hasMany(Invoice::class, 'generation_batch_id'); }
}
