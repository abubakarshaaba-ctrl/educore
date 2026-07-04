<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'employment_status')) {
                    $table->string('employment_status', 40)->default('active')->after('is_active');
                }
                if (!Schema::hasColumn('users', 'employment_started_at')) {
                    $table->date('employment_started_at')->nullable()->after('employment_status');
                }
                if (!Schema::hasColumn('users', 'employment_ended_at')) {
                    $table->date('employment_ended_at')->nullable()->after('employment_started_at');
                }
                if (!Schema::hasColumn('users', 'status_changed_at')) {
                    $table->timestamp('status_changed_at')->nullable()->after('employment_ended_at');
                }
                if (!Schema::hasColumn('users', 'exit_reason')) {
                    $table->text('exit_reason')->nullable()->after('status_changed_at');
                }
            });

            $this->addIndexIfMissing('users', ['tenant_id', 'employment_status'], 'idx_users_tenant_employment_status');
        }

        if (!Schema::hasTable('staff_status_histories')) {
            Schema::create('staff_status_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('user_id');
                $table->string('old_status', 40)->nullable();
                $table->string('new_status', 40);
                $table->date('effective_date');
                $table->date('last_working_date')->nullable();
                $table->text('reason');
                $table->string('document_path')->nullable();
                $table->unsignedBigInteger('changed_by');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_staffstatushist_tenant')->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('user_id', 'fk_staffstatushist_user')->references('id')->on('users')->restrictOnDelete();
                $table->foreign('changed_by', 'fk_staffstatushist_changed_by')->references('id')->on('users')->restrictOnDelete();
                $table->foreign('approved_by', 'fk_staffstatushist_approved_by')->references('id')->on('users')->nullOnDelete();

                $table->index(['tenant_id', 'user_id'], 'idx_staffstatushist_tenant_user');
                $table->index(['tenant_id', 'old_status'], 'idx_staffstatushist_tenant_old');
                $table->index(['tenant_id', 'new_status'], 'idx_staffstatushist_tenant_new');
                $table->index(['tenant_id', 'effective_date'], 'idx_staffstatushist_tenant_effective');
            });
        }

        if (!Schema::hasTable('staff_work_histories')) {
            Schema::create('staff_work_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('user_id');
                $table->string('position_title')->nullable();
                $table->string('department_name')->nullable();
                $table->string('employment_type')->nullable();
                $table->string('functional_role')->nullable();
                $table->string('grade_level')->nullable();
                $table->string('appointment_type')->nullable();
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->string('change_type', 50);
                $table->text('reason')->nullable();
                $table->string('document_path')->nullable();
                $table->unsignedBigInteger('recorded_by');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->foreign('tenant_id', 'fk_staffworkhist_tenant')->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('user_id', 'fk_staffworkhist_user')->references('id')->on('users')->restrictOnDelete();
                $table->foreign('recorded_by', 'fk_staffworkhist_recorded_by')->references('id')->on('users')->restrictOnDelete();
                $table->foreign('approved_by', 'fk_staffworkhist_approved_by')->references('id')->on('users')->nullOnDelete();

                $table->index(['tenant_id', 'user_id'], 'idx_staffworkhist_tenant_user');
                $table->index(['tenant_id', 'change_type'], 'idx_staffworkhist_tenant_change');
                $table->index(['tenant_id', 'start_date'], 'idx_staffworkhist_tenant_start');
                $table->index(['tenant_id', 'end_date'], 'idx_staffworkhist_tenant_end');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_work_histories');
        Schema::dropIfExists('staff_status_histories');

        if (Schema::hasTable('users')) {
            $this->dropIndexIfExists('users', 'idx_users_tenant_employment_status');

            $columns = array_values(array_filter([
                Schema::hasColumn('users', 'employment_status') ? 'employment_status' : null,
                Schema::hasColumn('users', 'employment_started_at') ? 'employment_started_at' : null,
                Schema::hasColumn('users', 'employment_ended_at') ? 'employment_ended_at' : null,
                Schema::hasColumn('users', 'status_changed_at') ? 'status_changed_at' : null,
                Schema::hasColumn('users', 'exit_reason') ? 'exit_reason' : null,
            ]));

            if ($columns) {
                Schema::table('users', function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }
    }

    private function addIndexIfMissing(string $table, array $columns, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $schema) use ($columns, $indexName) {
            $schema->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!$this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $schema) use ($indexName) {
            $schema->dropIndex($indexName);
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
};
