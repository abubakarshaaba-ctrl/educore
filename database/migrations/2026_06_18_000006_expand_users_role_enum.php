<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Ensure users.role is flexible
        |--------------------------------------------------------------------------
        | Convert ENUM role column to VARCHAR so EduCore can support expanded
        | school roles such as principal, admission_officer, transport_officer, etc.
        */
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            DB::statement("ALTER TABLE `users` MODIFY `role` VARCHAR(100) NULL");
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize legacy teacher role
        |--------------------------------------------------------------------------
        | Keep existing teacher users as subject_teacher by default.
        */
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            DB::table('users')
                ->where('role', 'teacher')
                ->update(['role' => 'subject_teacher']);
        }

        /*
        |--------------------------------------------------------------------------
        | Create invoice_payments table
        |--------------------------------------------------------------------------
        | Required by financial analytics and payment reporting.
        */
        if (!Schema::hasTable('invoice_payments')) {
            Schema::create('invoice_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('invoice_id');
                $table->unsignedBigInteger('student_id')->nullable();
                $table->unsignedBigInteger('received_by')->nullable();

                $table->string('payment_reference')->unique();
                $table->decimal('amount', 12, 2);
                $table->string('payment_method')->nullable();
                $table->string('payment_channel')->nullable();
                $table->date('payment_date')->nullable();
                $table->text('notes')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'invoice_id']);
                $table->index(['tenant_id', 'student_id']);
                $table->index('payment_date');

                if (Schema::hasTable('invoices')) {
                    $table->foreign('invoice_id')
                        ->references('id')
                        ->on('invoices')
                        ->cascadeOnDelete();
                }

                if (Schema::hasTable('tenants')) {
                    $table->foreign('tenant_id')
                        ->references('id')
                        ->on('tenants')
                        ->cascadeOnDelete();
                }

                if (Schema::hasTable('students')) {
                    $table->foreign('student_id')
                        ->references('id')
                        ->on('students')
                        ->nullOnDelete();
                }

                if (Schema::hasTable('users')) {
                    $table->foreign('received_by')
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Seed expanded school roles safely
        |--------------------------------------------------------------------------
        | Supports both role tables with a `label` column and Spatie-style role
        | tables without a `label` column.
        */
        if (Schema::hasTable('roles')) {
            $hasLabel = Schema::hasColumn('roles', 'label');
            $hasGuard = Schema::hasColumn('roles', 'guard_name');

            $roles = [
                ['name' => 'administrator', 'label' => 'Administrator'],
                ['name' => 'principal', 'label' => 'Principal'],
                ['name' => 'vice_principal', 'label' => 'Vice Principal'],
                ['name' => 'admission_officer', 'label' => 'Admission Officer'],

                ['name' => 'form_teacher', 'label' => 'Form Teacher'],
                ['name' => 'assistant_form_teacher', 'label' => 'Assistant Form Teacher'],
                ['name' => 'subject_teacher', 'label' => 'Subject Teacher'],
                ['name' => 'form_subject_teacher', 'label' => 'Form & Subject Teacher'],

                ['name' => 'accountant', 'label' => 'Accountant'],

                ['name' => 'health_officer', 'label' => 'Health Officer'],
                ['name' => 'librarian', 'label' => 'Librarian'],
                ['name' => 'transport_officer', 'label' => 'Transport Officer'],
                ['name' => 'communication_officer', 'label' => 'Communication Officer'],

                ['name' => 'parent', 'label' => 'Parent'],
                ['name' => 'student', 'label' => 'Student'],
            ];

            foreach ($roles as $role) {
                $query = DB::table('roles')->where('name', $role['name']);

                if ($hasGuard) {
                    $query->where('guard_name', 'web');
                }

                $existing = $query->first();

                $payload = [
                    'updated_at' => now(),
                ];

                if ($hasGuard) {
                    $payload['guard_name'] = 'web';
                }

                if ($hasLabel) {
                    $payload['label'] = $role['label'];
                }

                if ($existing) {
                    DB::table('roles')->where('id', $existing->id)->update($payload);
                } else {
                    $payload['name'] = $role['name'];
                    $payload['created_at'] = now();

                    DB::table('roles')->insert($payload);
                }
            }
        }
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Safe rollback
        |--------------------------------------------------------------------------
        | Drop only invoice_payments if this migration created/owns it.
        | Do not convert users.role back to ENUM because doing so may truncate
        | live role values.
        */
        Schema::dropIfExists('invoice_payments');
    }
};
