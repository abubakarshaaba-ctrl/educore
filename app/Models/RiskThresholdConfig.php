<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskThresholdConfig extends Model
{
    protected $fillable = [
        'tenant_id',
        'academic_threshold', 'attendance_threshold', 'subjects_failed_threshold',
        'include_fee_risk',
        'academic_weight', 'attendance_weight', 'fee_weight',
    ];

    protected function casts(): array
    {
        return [
            'include_fee_risk'      => 'boolean',
            'academic_threshold'    => 'float',
            'attendance_threshold'  => 'float',
        ];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }

    public static function forTenant(int $tenantId): self
    {
        return static::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'academic_threshold'         => 45.0,
                'attendance_threshold'       => 75.0,
                'subjects_failed_threshold'  => 2,
                'include_fee_risk'           => true,
                'academic_weight'            => 40,
                'attendance_weight'          => 35,
                'fee_weight'                 => 25,
            ]
        );
    }
}
