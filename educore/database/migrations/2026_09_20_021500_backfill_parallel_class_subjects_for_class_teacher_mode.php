<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('parallel_curriculum_class_arms')
            || ! Schema::hasTable('parallel_curriculum_classes')
            || ! Schema::hasTable('parallel_curriculum_subjects')
            || ! Schema::hasTable('parallel_curriculum_class_subjects')
            || ! Schema::hasColumn('parallel_curriculum_class_arms', 'teaching_assignment_mode')
            || ! Schema::hasColumn('parallel_curriculum_class_arms', 'class_teacher_id')
        ) {
            return;
        }

        $classIds = DB::table('parallel_curriculum_class_arms as arms')
            ->join(
                'parallel_curriculum_classes as classes',
                'classes.id',
                '=',
                'arms.parallel_curriculum_class_id'
            )
            ->where('arms.is_active', true)
            ->where('classes.is_active', true)
            ->where('arms.teaching_assignment_mode', 'class_teacher')
            ->whereNotNull('arms.class_teacher_id')
            ->select(
                'classes.id',
                'classes.tenant_id',
                'classes.parallel_curriculum_id'
            )
            ->distinct()
            ->get();

        foreach ($classIds as $class) {
            $hasActiveClassSubjects = DB::table('parallel_curriculum_class_subjects')
                ->where('tenant_id', $class->tenant_id)
                ->where('parallel_curriculum_class_id', $class->id)
                ->where('is_active', true)
                ->exists();

            // Preserve every explicitly configured class subject subset.
            if ($hasActiveClassSubjects) {
                continue;
            }

            $subjects = DB::table('parallel_curriculum_subjects')
                ->where('tenant_id', $class->tenant_id)
                ->where('parallel_curriculum_id', $class->parallel_curriculum_id)
                ->where('is_active', true)
                ->pluck('id');

            $now = now();

            foreach ($subjects as $subjectId) {
                DB::table('parallel_curriculum_class_subjects')->updateOrInsert(
                    [
                        'tenant_id' => $class->tenant_id,
                        'parallel_curriculum_class_id' => $class->id,
                        'parallel_curriculum_subject_id' => $subjectId,
                    ],
                    [
                        'teacher_id' => null,
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        // Data-only reconciliation. Do not remove class-subject assignments on
        // rollback because they may already contain scores or intentional edits.
    }
};
