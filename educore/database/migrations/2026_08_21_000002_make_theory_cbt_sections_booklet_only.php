<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cbt_exam_sections')
            ->whereIn('section_type', ['theory', 'essay'])
            ->update([
                'scoring_method' => 'manual',
                'answer_mode' => 'paper',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // The former answer mode cannot be inferred safely; keep booklet-only theory sections.
    }
};
