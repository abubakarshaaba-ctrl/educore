<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── academic_tracks ─────────────────────────────────────────────
        if (!Schema::hasTable('academic_tracks')) {
            Schema::create('academic_tracks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->string('name', 80);
                $table->string('slug', 90)->unique();
                $table->enum('section', ['primary', 'junior', 'senior', 'general'])->default('general');
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->index(['tenant_id', 'is_active'], 'at_tid_active');
            });
        }

        // ── class_level_subjects ─────────────────────────────────────────
        if (!Schema::hasTable('class_level_subjects')) {
            Schema::create('class_level_subjects', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('class_level_id');
                $table->unsignedBigInteger('academic_track_id')->nullable();
                $table->unsignedBigInteger('subject_id');
                $table->enum('subject_status', ['compulsory','elective','optional','not_offered'])
                      ->default('compulsory');
                $table->string('elective_group', 60)->nullable();
                $table->unsignedTinyInteger('min_required')->nullable();
                $table->unsignedTinyInteger('max_allowed')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(
                    ['tenant_id','class_level_id','academic_track_id','subject_id'],
                    'cls_unique'
                );
                $table->index(['tenant_id','class_level_id','academic_track_id'], 'cls_tid_lvl_trk');
                $table->index(['tenant_id','subject_id'], 'cls_tid_subj');
            });
        }

        // ── student_subject_selections ───────────────────────────────────
        if (!Schema::hasTable('student_subject_selections')) {
            Schema::create('student_subject_selections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('class_level_id');
                $table->unsignedBigInteger('academic_track_id')->nullable();
                $table->unsignedBigInteger('subject_id');
                $table->enum('selection_type', ['compulsory','elective'])->default('elective');
                $table->unsignedBigInteger('session_id')->nullable();
                $table->unsignedBigInteger('term_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(
                    ['tenant_id','student_id','subject_id','session_id'],
                    'sss_unique'
                );
                $table->index(['tenant_id','student_id'], 'sss_tid_stu');
                $table->index(['tenant_id','class_level_id','academic_track_id'], 'sss_tid_lvl_trk');
            });
        }

        // ── Add academic_track_id to class_arms (if missing) ─────────────
        if (Schema::hasTable('class_arms') && !Schema::hasColumn('class_arms', 'academic_track_id')) {
            Schema::table('class_arms', function (Blueprint $table) {
                $table->unsignedBigInteger('academic_track_id')->nullable()->after('class_level_id');
                $table->index('academic_track_id', 'ca_track_id');
            });
        }

        // ── Add term_id & is_active to class_arm_subjects (if missing) ───
        if (Schema::hasTable('class_arm_subjects')) {
            Schema::table('class_arm_subjects', function (Blueprint $table) {
                if (!Schema::hasColumn('class_arm_subjects', 'term_id')) {
                    $table->unsignedBigInteger('term_id')->nullable()->after('session_id');
                }
                if (!Schema::hasColumn('class_arm_subjects', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('term_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('class_arm_subjects')) {
            Schema::table('class_arm_subjects', function (Blueprint $table) {
                if (Schema::hasColumn('class_arm_subjects', 'is_active')) $table->dropColumn('is_active');
                if (Schema::hasColumn('class_arm_subjects', 'term_id'))    $table->dropColumn('term_id');
            });
        }
        if (Schema::hasTable('class_arms') && Schema::hasColumn('class_arms', 'academic_track_id')) {
            Schema::table('class_arms', function (Blueprint $table) {
                $table->dropColumn('academic_track_id');
            });
        }
        Schema::dropIfExists('student_subject_selections');
        Schema::dropIfExists('class_level_subjects');
        Schema::dropIfExists('academic_tracks');
    }
};
