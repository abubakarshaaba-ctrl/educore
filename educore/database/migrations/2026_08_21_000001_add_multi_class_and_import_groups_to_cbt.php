<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cbt_questions', function (Blueprint $table) {
            $table->string('source_section_code', 20)->nullable()->after('reference_code');
            $table->string('source_section_name', 120)->nullable()->after('source_section_code');
            $table->index(['question_bank_id', 'source_section_code'], 'idx_cbtqs_source_section');
        });

        Schema::create('cbt_exam_class_arms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('cbt_exam_id');
            $table->unsignedBigInteger('class_arm_id');
            $table->timestamps();
            $table->foreign('tenant_id', 'fk_cbtexamclasses_tenant')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('cbt_exam_id', 'fk_cbtexamclasses_exam')->references('id')->on('cbt_exams')->cascadeOnDelete();
            $table->foreign('class_arm_id', 'fk_cbtexamclasses_arm')->references('id')->on('class_arms')->cascadeOnDelete();
            $table->unique(['cbt_exam_id', 'class_arm_id'], 'uq_cbtexamclasses_exam_arm');
            $table->index(['tenant_id', 'class_arm_id', 'cbt_exam_id'], 'idx_cbtexamclasses_arm_exam');
        });

        DB::table('cbt_exams')->orderBy('id')->each(function ($exam) {
            DB::table('cbt_exam_class_arms')->insertOrIgnore([
                'tenant_id' => $exam->tenant_id,
                'cbt_exam_id' => $exam->id,
                'class_arm_id' => $exam->class_arm_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $drafts = DB::table('cbt_exams')->where('status', 'draft')->orderBy('id')->get()
            ->groupBy(fn ($exam) => implode('|', [
                $exam->tenant_id,
                $exam->question_bank_id,
                $exam->term_id,
                mb_strtolower(trim(preg_replace('/\s+/', ' ', $exam->title))),
            ]));

        foreach ($drafts as $duplicates) {
            if ($duplicates->count() < 2) continue;
            $hasWork = function ($exam): bool {
                return DB::table('cbt_exam_sections')->where('cbt_exam_id', $exam->id)->exists()
                    || DB::table('cbt_student_sessions')->where('cbt_exam_id', $exam->id)->exists()
                    || DB::table('cbt_import_batches')->where('cbt_exam_id', $exam->id)->exists()
                    || DB::table('cbt_retake_authorizations')->where('cbt_exam_id', $exam->id)->exists()
                    || DB::table('cbt_integrity_events')->where('cbt_exam_id', $exam->id)->exists()
                    || DB::table('scores')->where('cbt_exam_id', $exam->id)->exists();
            };
            $primary = $duplicates->first(fn ($exam) => $hasWork($exam)) ?: $duplicates->first();
            foreach ($duplicates->where('id', '!=', $primary->id) as $duplicate) {
                if ($hasWork($duplicate)) continue;
                DB::table('cbt_exam_class_arms')->insertOrIgnore([
                    'tenant_id' => $primary->tenant_id,
                    'cbt_exam_id' => $primary->id,
                    'class_arm_id' => $duplicate->class_arm_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('cbt_exams')->where('id', $duplicate->id)->delete();
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cbt_exam_class_arms');
        Schema::table('cbt_questions', function (Blueprint $table) {
            $table->dropIndex('idx_cbtqs_source_section');
            $table->dropColumn(['source_section_code', 'source_section_name']);
        });
    }
};
