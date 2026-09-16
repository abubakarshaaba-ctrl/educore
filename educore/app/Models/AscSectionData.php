<?php

namespace App\Models;

class AscSectionData extends BaseTenantModel
{
    protected $table = 'asc_section_data';

    protected $fillable = [
        'tenant_id', 'session_id', 'census_year', 'section', 'data', 'is_complete', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_complete' => 'boolean',
        ];
    }

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
