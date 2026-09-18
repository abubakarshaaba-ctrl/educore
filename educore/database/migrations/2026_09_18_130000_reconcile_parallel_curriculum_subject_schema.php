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
            // This migration may be retried after a shared-host deployment was
            // interrupted mid-DDL. Drop only constraints/indexes that still
            // exist, then remove the legacy conventional-subject column.
            $this->dropForeignKeyIfExists(
                'parallel_curriculum_class_subjects',
                'fk_pc_cs_subject'
            );
            $this->dropUniqueIndexIfExists(
                'parallel_curriculum_class_subjects',
                'uq_pc_class_subject'
            );

            if (Schema::hasColumn('parallel_curriculum_class_subjects', 'subject_id')) {
                Schema::table('parallel_curriculum_class_subjects', function (Blueprint $table): void {
                    $table->dropColumn('subject_id');
                });
            }
        }

        if ($legacyScores) {
            $this->dropForeignKeyIfExists(
                'parallel_curriculum_scores',
                'fk_pc_score_subject'
            );
            $this->dropUniqueIndexIfExists(
                'parallel_curriculum_scores',
                'uq_pc_score_cell'
            );

            if (Schema::hasColumn('parallel_curriculum_scores', 'subject_id')) {
                Schema::table('parallel_curriculum_scores', function (Blueprint $table): void {
                    $table->dropColumn('subject_id');
                });
            }
        }

        // Re-create the new constraints even when a previous migration attempt
        // already removed subject_id before failing. That makes this migration
        // safely restartable instead of trapping production in a partial schema.
        $this->ensureForeignKey(
            'parallel_curriculum_class_subjects',
            'parallel_curriculum_subject_id',
            'fk_pc_cs_subject',
            'parallel_curriculum_subjects'
        );
        $this->ensureUniqueIndex(
            'parallel_curriculum_class_subjects',
            'uq_pc_class_subject',
            ['parallel_curriculum_class_id', 'parallel_curriculum_subject_id']
        );

        $this->ensureForeignKey(
            'parallel_curriculum_scores',
            'parallel_curriculum_subject_id',
            'fk_pc_score_subject',
            'parallel_curriculum_subjects'
        );
        $this->ensureUniqueIndex(
            'parallel_curriculum_scores',
            'uq_pc_score_cell',
            [
                'parallel_curriculum_id',
                'student_id',
                'parallel_curriculum_subject_id',
                'assessment_template_component_id',
                'term_id',
            ]
        );
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if (($foreignKey['name'] ?? null) === $name) {
                return true;
            }
        }

        return false;
    }

    private function indexExists(string $table, string $name): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        foreach (Schema::getIndexes($table) as $index) {
            if (($index['name'] ?? null) === $name) {
                return true;
            }
        }

        return false;
    }

    private function dropForeignKeyIfExists(string $tableName, string $name): void
    {
        if (! $this->foreignKeyExists($tableName, $name)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($name): void {
            $table->dropForeign($name);
        });
    }

    private function dropUniqueIndexIfExists(string $tableName, string $name): void
    {
        if (! $this->indexExists($tableName, $name)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($name): void {
            $table->dropUnique($name);
        });
    }

    private function ensureForeignKey(
        string $tableName,
        string $column,
        string $name,
        string $referencedTable,
    ): void {
        if (
            ! Schema::hasTable($tableName)
            || ! Schema::hasColumn($tableName, $column)
            || $this->foreignKeyExists($tableName, $name)
        ) {
            return;
        }

        Schema::table(
            $tableName,
            function (Blueprint $table) use ($column, $name, $referencedTable): void {
                $table->foreign($column, $name)
                    ->references('id')
                    ->on($referencedTable)
                    ->cascadeOnDelete();
            }
        );
    }

    private function ensureUniqueIndex(
        string $tableName,
        string $name,
        array $columns,
    ): void {
        if (! Schema::hasTable($tableName) || $this->indexExists($tableName, $name)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($tableName, $column)) {
                return;
            }
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $name): void {
            $table->unique($columns, $name);
        });
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
