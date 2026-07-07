<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The MODIFY/JOIN-UPDATE/information_schema calls below are MySQL-only.
        // SQLite (used by the test suite) has dynamically-typed, already-nullable
        // columns, and its Schema builder handles indexes/FKs without needing
        // information_schema lookups — so those steps are simply skipped there,
        // while the portable Schema::create/table calls further down still run.
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'status')
            && DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `students` MODIFY `status` VARCHAR(40) NOT NULL DEFAULT 'applicant'");
        }

        if (Schema::hasTable('student_enrollments')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                if (!Schema::hasColumn('student_enrollments', 'start_date')) {
                    $table->date('start_date')->nullable()->after('term_id');
                }
                if (!Schema::hasColumn('student_enrollments', 'end_date')) {
                    $table->date('end_date')->nullable()->after('start_date');
                }
                if (!Schema::hasColumn('student_enrollments', 'is_current')) {
                    $table->boolean('is_current')->default(false)->after('end_date');
                }
                if (!Schema::hasColumn('student_enrollments', 'status')) {
                    $table->string('status', 40)->default('active')->after('is_current');
                }
                if (!Schema::hasColumn('student_enrollments', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('status');
                }
                if (!Schema::hasColumn('student_enrollments', 'ended_by')) {
                    $table->unsignedBigInteger('ended_by')->nullable()->after('created_by');
                }
                if (!Schema::hasColumn('student_enrollments', 'ended_reason')) {
                    $table->text('ended_reason')->nullable()->after('ended_by');
                }
            });

            $this->addForeignIfMissing('student_enrollments', 'fk_enrollments_created_by', function (Blueprint $table) {
                $table->foreign('created_by', 'fk_enrollments_created_by')->references('id')->on('users')->nullOnDelete();
            });
            $this->addForeignIfMissing('student_enrollments', 'fk_enrollments_ended_by', function (Blueprint $table) {
                $table->foreign('ended_by', 'fk_enrollments_ended_by')->references('id')->on('users')->nullOnDelete();
            });

            $this->dropIndexIfExists('student_enrollments', 'uq_enrollment_unique');
            $this->addIndexIfMissing('student_enrollments', ['tenant_id', 'student_id'], 'idx_enroll_tenant_student');
            $this->addIndexIfMissing('student_enrollments', ['tenant_id', 'student_id', 'is_current'], 'idx_enroll_tenant_student_current');
            $this->addIndexIfMissing('student_enrollments', ['tenant_id', 'class_arm_id', 'is_current'], 'idx_enroll_tenant_arm_current');
            $this->addIndexIfMissing('student_enrollments', ['tenant_id', 'student_id', 'session_id', 'term_id'], 'idx_enroll_tenant_student_session_term');
            $this->addIndexIfMissing('student_enrollments', ['tenant_id', 'status'], 'idx_enroll_tenant_status');

            $this->backfillCurrentStudentEnrollments();
        }

        if (!Schema::hasTable('student_class_transfers')) {
            Schema::create('student_class_transfers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('academic_session_id');
                $table->unsignedBigInteger('term_id')->nullable();
                $table->unsignedBigInteger('from_class_arm_id');
                $table->unsignedBigInteger('to_class_arm_id');
                $table->date('effective_date');
                $table->text('reason');
                $table->string('status', 40)->default('pending');
                $table->unsignedBigInteger('requested_by');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedBigInteger('rejected_by')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->unsignedBigInteger('cancelled_by')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->string('supporting_document')->nullable();
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_stuclasstrans_tenant')->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('student_id', 'fk_stuclasstrans_student')->references('id')->on('students')->restrictOnDelete();
                $table->foreign('academic_session_id', 'fk_stuclasstrans_session')->references('id')->on('academic_sessions')->restrictOnDelete();
                $table->foreign('term_id', 'fk_stuclasstrans_term')->references('id')->on('terms')->nullOnDelete();
                $table->foreign('from_class_arm_id', 'fk_stuclasstrans_from_arm')->references('id')->on('class_arms')->restrictOnDelete();
                $table->foreign('to_class_arm_id', 'fk_stuclasstrans_to_arm')->references('id')->on('class_arms')->restrictOnDelete();
                $table->foreign('requested_by', 'fk_stuclasstrans_requested_by')->references('id')->on('users')->restrictOnDelete();
                $table->foreign('approved_by', 'fk_stuclasstrans_approved_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('rejected_by', 'fk_stuclasstrans_rejected_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('cancelled_by', 'fk_stuclasstrans_cancelled_by')->references('id')->on('users')->nullOnDelete();

                $table->index(['tenant_id', 'student_id'], 'idx_stuclasstrans_tenant_student');
                $table->index(['tenant_id', 'status'], 'idx_stuclasstrans_tenant_status');
                $table->index(['tenant_id', 'academic_session_id'], 'idx_stuclasstrans_tenant_session');
                $table->index(['tenant_id', 'term_id'], 'idx_stuclasstrans_tenant_term');
                $table->index(['tenant_id', 'from_class_arm_id'], 'idx_stuclasstrans_tenant_from');
                $table->index(['tenant_id', 'to_class_arm_id'], 'idx_stuclasstrans_tenant_to');
            });
        }

        if (!Schema::hasTable('student_status_histories')) {
            Schema::create('student_status_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('student_id');
                $table->string('old_status', 40)->nullable();
                $table->string('new_status', 40);
                $table->date('effective_date');
                $table->text('reason');
                $table->string('destination_school')->nullable();
                $table->string('transfer_certificate_number')->nullable();
                $table->string('document_path')->nullable();
                $table->unsignedBigInteger('changed_by');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_stustatushist_tenant')->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('student_id', 'fk_stustatushist_student')->references('id')->on('students')->restrictOnDelete();
                $table->foreign('changed_by', 'fk_stustatushist_changed_by')->references('id')->on('users')->restrictOnDelete();
                $table->foreign('approved_by', 'fk_stustatushist_approved_by')->references('id')->on('users')->nullOnDelete();

                $table->index(['tenant_id', 'student_id'], 'idx_stustatushist_tenant_student');
                $table->index(['tenant_id', 'old_status'], 'idx_stustatushist_tenant_old');
                $table->index(['tenant_id', 'new_status'], 'idx_stustatushist_tenant_new');
                $table->index(['tenant_id', 'effective_date'], 'idx_stustatushist_tenant_effective');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_status_histories');
        Schema::dropIfExists('student_class_transfers');

        if (Schema::hasTable('student_enrollments')) {
            $this->dropIndexIfExists('student_enrollments', 'idx_enroll_tenant_student');
            $this->dropIndexIfExists('student_enrollments', 'idx_enroll_tenant_student_current');
            $this->dropIndexIfExists('student_enrollments', 'idx_enroll_tenant_arm_current');
            $this->dropIndexIfExists('student_enrollments', 'idx_enroll_tenant_student_session_term');
            $this->dropIndexIfExists('student_enrollments', 'idx_enroll_tenant_status');
            $this->dropForeignIfExists('student_enrollments', 'fk_enrollments_created_by');
            $this->dropForeignIfExists('student_enrollments', 'fk_enrollments_ended_by');

            $this->restoreEnrollmentUniqueConstraintIfSafe();

            $columns = array_values(array_filter([
                Schema::hasColumn('student_enrollments', 'start_date') ? 'start_date' : null,
                Schema::hasColumn('student_enrollments', 'end_date') ? 'end_date' : null,
                Schema::hasColumn('student_enrollments', 'is_current') ? 'is_current' : null,
                Schema::hasColumn('student_enrollments', 'status') ? 'status' : null,
                Schema::hasColumn('student_enrollments', 'created_by') ? 'created_by' : null,
                Schema::hasColumn('student_enrollments', 'ended_by') ? 'ended_by' : null,
                Schema::hasColumn('student_enrollments', 'ended_reason') ? 'ended_reason' : null,
            ]));

            if ($columns) {
                Schema::table('student_enrollments', function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }

        if (Schema::hasTable('students') && Schema::hasColumn('students', 'status')) {
            $incompatible = DB::table('students')
                ->whereNotIn('status', ['applicant', 'active', 'suspended', 'graduated', 'withdrawn'])
                ->exists();

            if ($incompatible) {
                throw new RuntimeException('Cannot safely roll back students.status to the old enum while rows contain new lifecycle statuses.');
            }

            DB::statement("ALTER TABLE `students` MODIFY `status` ENUM('applicant','active','suspended','graduated','withdrawn') NOT NULL DEFAULT 'applicant'");
        }
    }

    private function backfillCurrentStudentEnrollments(): void
    {
        if (
            !Schema::hasColumn('student_enrollments', 'is_current') ||
            !Schema::hasColumn('student_enrollments', 'status') ||
            DB::connection()->getDriverName() !== 'mysql'
        ) {
            return;
        }

        DB::statement(<<<'SQL'
UPDATE student_enrollments se
JOIN (
    SELECT DISTINCT se2.tenant_id, se2.student_id
    FROM student_enrollments se2
    INNER JOIN students s
        ON s.id = se2.student_id
        AND s.tenant_id = se2.tenant_id
        AND s.current_class_arm_id = se2.class_arm_id
    WHERE s.current_class_arm_id IS NOT NULL
) matched_students
    ON matched_students.tenant_id = se.tenant_id
    AND matched_students.student_id = se.student_id
SET se.is_current = 0
SQL);

        DB::statement(<<<'SQL'
UPDATE student_enrollments se
JOIN (
    SELECT MAX(se2.id) AS id
    FROM student_enrollments se2
    INNER JOIN students s
        ON s.id = se2.student_id
        AND s.tenant_id = se2.tenant_id
        AND s.current_class_arm_id = se2.class_arm_id
    WHERE s.current_class_arm_id IS NOT NULL
    GROUP BY se2.tenant_id, se2.student_id
) newest ON newest.id = se.id
SET se.is_current = 1, se.status = 'active'
SQL);
    }

    private function restoreEnrollmentUniqueConstraintIfSafe(): void
    {
        if (!$this->usesInformationSchema() || $this->indexExists('student_enrollments', 'uq_enrollment_unique')) {
            return;
        }

        $duplicates = DB::table('student_enrollments')
            ->select('tenant_id', 'student_id', 'session_id', 'term_id')
            ->groupBy('tenant_id', 'student_id', 'session_id', 'term_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicates) {
            throw new RuntimeException('Cannot safely restore uq_enrollment_unique because same student/session/term enrolment history rows now exist.');
        }

        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->unique(['tenant_id', 'student_id', 'session_id', 'term_id'], 'uq_enrollment_unique');
        });
    }

    private function addIndexIfMissing(string $table, array $columns, string $indexName): void
    {
        if (!$this->usesInformationSchema() || $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $schema) use ($columns, $indexName) {
            $schema->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!$this->usesInformationSchema() || !$this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $schema) use ($indexName) {
            $schema->dropIndex($indexName);
        });
    }

    private function addForeignIfMissing(string $table, string $foreignName, Closure $callback): void
    {
        if (!$this->usesInformationSchema() || $this->foreignKeyExists($table, $foreignName)) {
            return;
        }

        Schema::table($table, $callback);
    }

    private function dropForeignIfExists(string $table, string $foreignName): void
    {
        if (!$this->usesInformationSchema() || !$this->foreignKeyExists($table, $foreignName)) {
            return;
        }

        Schema::table($table, function (Blueprint $schema) use ($foreignName) {
            $schema->dropForeign($foreignName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }

    private function foreignKeyExists(string $table, string $foreignName): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('constraint_name', $foreignName)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }

    // information_schema-backed named index/FK management is a MySQL-only
    // concern (used for zero-downtime idempotent migrations in production).
    // SQLite, used only by the test suite, doesn't need it skipped safely.
    private function usesInformationSchema(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }
};
