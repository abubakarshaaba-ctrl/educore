<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoreImport extends BaseTenantModel
{
    protected $table = 'score_imports';

    protected $fillable = [
        'tenant_id',
        'filename',
        'class_arm_id',
        'term_id',
        'rows_imported',
        'rows_failed',
        'errors',
        'status',
        'imported_by',
    ];
}
