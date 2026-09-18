<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parallel_curricula')) {
            return;
        }

        if (! Schema::hasTable('parallel_curriculum_subjects')) {
            Schema::create('parallel_curriculum_subjects', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('parallel_curriculum_id');
                $table->string('name', 120);
                $table->string('code', 40)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_pc_subj_tenant')->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('parallel_curriculum_id', 'fk_pc_subj_curr')->references('id')->on('parallel_curricula')->cascadeOnDelete();
                $table->unique(['parallel_curriculum_id', 'name'], 'uq_pc_subj_name');
                $table->index(['tenant_id', 'parallel_curriculum_id', 'is_active'], 'idx_pc_subj_active');
            });
        }

        $legacyClassSubjects = Schema::hasTable('parallel_curriculum_class_subjects')
            && Schema::hasColumn('parallel_curriculum_class_subjects', 'subject_id');

        $legacyScores = Schema::hasTable('parallel_curriculum_scores')
            && Schema::hasColumn('parallel_curriculum_scores', 'subject_id');

        if ($legacyClassSubjects
            && ! Schema::hasColumn('parallel_curriculum_class_subjects', 'parallel_curriculum_subject_id')) {
            Schema::table('parallel_curriculum_class_subjects', function (Blueprint $table): void {
                $table->unsignedBigInteger('parallel_curriculum_subject_id')->nullable()->after('parallel_curriculum_class_id');
            });
        }

        if ($legacyScores
            && ! Schema::hasColumn('parallel_curriculum_scores', 'parallel_curriculum_subject_id')) {
            Schema::table('parallel_curriculum_scores', function (Blueprint $table): void {
                $table->unsignedBigInteger('parallel_curriculum_subject_id')->nullable()->after('student_id');
            });
        }

        if ($legacyClassSubjects) {
            $rows = DB::table('parallel_curriculum_class_subjects as assignment')
                ->join('parallel_curriculum_classes as class', 'class.id', '=', 'assignment.parallel_curriculum_class_id')
                ->join('subjects as subject', 'subject.id', '=', 'assignment.subject_id')
                ->select([
                    'assignment.id',
                    'assignment.tenant_id',
                    'assignment.subject_id',
                    'class.parallel_curriculum_id',
                    'subject.name as subject_name',
                    'subject.code as subject_code',
                ])
                ->orderBy('assignment.id')
                ->get();

            foreach ($rows as $row) {
                $parallelSubjectId = $this->parallelSubjectId(
                    (int) $row->tenant_id,
                    (int) $row->parallel_curriculum_id,
                    (int) $row->subject_id,
                    (string) $row->subject_name,
                    $row->subject_code !== null ? (string) $row->subject_code : null,
                );

                DB::table('parallel_curriculum_class_subjects')
                    ->where('id', $row->id)
                    ->update(['parallel_curriculum_subject_id' => $parallelSubjectId]);
            }
        }

        if ($legacyScores) {
            $rows = DB::table('parallel_curriculum_scores as score')
                ->join('subjects as subject', 'subject.id', '=', 'score.subject_id')
                ->select([
                    'score.id',
                    'score.tenant_id',
                    'score.parallel_curriculum_id',
                    'score.subject_id',
                    'subject.name as subject_name',
                    'subject.code as subject_code',
                ])
                ->orderBy('score.id')
                ->get();

            foreach ($rows as $row) {
                $parallelSubjectId = $this->parallelSubjectId(
                    (int) $row->tenant_id,
                    (int) $row->parallel_curriculum_id,
                    (int) $row->subject_id,
                    (string) $row->subject_name,
                    $row->subject_code !== null ? (string) $row->subject_code : null,
                );

                DB::table('parallel_curriculum_scores')
                    ->where('id', $row->id)
                    ->update(['parallel_curriculum_subject_id' => $parallelSubjectId]);
            }
        }

        if ($legacyClassSubjects) {
            Schema::table('parallel_curriculum_class_subjects', function (Blueprint $table): void {
                $table->dropForeign('fk_pc_cs_subject');
                $table->dropUnique('uq_pc_class_subject');
            });

            Schema::table('parallel_curriculum_class_subjects', function (Blueprint $table): void {
                $table->dropColumn('subject_id');
            });

            Schema::table('parallel_curriculum_class_subjects', function (Blueprint $table): void {
                $table->foreign('parallel_curriculum_subject_id', 'fk_pc_cs_subject')
                    ->references('id')->on('parallel_curriculum_subjects')->cascadeOnDelete();
                $table->unique(
                    ['parallel_curriculum_class_id', 'parallel_curriculum_subject_id'],
                    'uq_pc_class_subject'
                );
            });
        }

        if ($legacyScores) {
            Schema::table('parallel_curriculum_scores', function (Blueprint $table): void {
                $table->dropForeign('fk_pc_score_subject');
                $table->dropUnique('uq_pc_score_cell');
            });

            Schema::table('parallel_curriculum_scores', function (Blueprint $table): void {
                $table->dropColumn('subject_id');
            });

            Schema::table('parallel_curriculum_scores', function (Blueprint $table): void {
                $table->foreign('parallel_curriculum_subject_id', 'fk_pc_score_subject')
                    ->references('id')->on('parallel_curriculum_subjects')->cascadeOnDelete();
                $table->unique(
                    [
                        'parallel_curriculum_id',
                        'student_id',
                        'parallel_curriculum_subject_id',
                        'assessment_template_component_id',
                        'term_id',
                    ],
                    'uq_pc_score_cell'
                );
            });
        }
    }

    private function parallelSubjectId(
        int $tenantId,
        int $curriculumId,
        int $legacySubjectId,
        string $name,
        ?string $code,
    ): int {
        $existing = DB::table('parallel_curriculum_subjects')
            ->where('parallel_curriculum_id', $curriculumId)
            ->where('name', $name)
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $normalizedCode = $code ? mb_substr($code, 0, 40) : 'LEGACY-'.$legacySubjectId;

        return (int) DB::table('parallel_curriculum_subjects')->insertGetId([
            'tenant_id' => $tenantId,
            'parallel_curriculum_id' => $curriculumId,
            'name' => $name,
            'code' => $normalizedCode,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Intentionally irreversible: this migration converts conventional-subject
        // references into independent parallel-curriculum subjects. Recreating the
        // legacy foreign keys would discard that independence and can lose meaning.
    }
};
