<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 100)->nullable()->change();
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            DB::table('users')
                ->where('role', 'teacher')
                ->update(['role' => 'subject_teacher']);
        }

        if (! Schema::hasTable('invoice_payments')) {
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
                $payload = ['updated_at' => now()];

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
        Schema::dropIfExists('invoice_payments');
    }
};
