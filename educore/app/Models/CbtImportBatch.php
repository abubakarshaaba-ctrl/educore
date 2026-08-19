<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbtImportBatch extends BaseTenantModel
{
    protected $fillable = ['tenant_id', 'question_bank_id', 'cbt_exam_id', 'uploaded_by', 'original_name', 'status', 'rows', 'validation_errors', 'row_count', 'imported_count'];
    protected function casts(): array { return ['rows' => 'array', 'validation_errors' => 'array', 'row_count' => 'integer', 'imported_count' => 'integer']; }
    public function bank(): BelongsTo { return $this->belongsTo(CbtQuestionBank::class, 'question_bank_id'); }
    public function exam(): BelongsTo { return $this->belongsTo(CbtExam::class, 'cbt_exam_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}
