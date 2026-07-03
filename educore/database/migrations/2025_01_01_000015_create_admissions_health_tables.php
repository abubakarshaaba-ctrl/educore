<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Admissions ──────────────────────────────────────────────
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('application_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('other_names')->nullable();
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female']);
            $table->string('religion')->nullable();
            $table->string('nationality')->default('Nigerian');
            $table->string('state_of_origin')->nullable();
            $table->string('address')->nullable();
            $table->unsignedBigInteger('applying_for_class_level_id')->nullable();
            $table->string('previous_school')->nullable();
            $table->string('previous_class')->nullable();
            // Guardian info
            $table->string('guardian_name');
            $table->string('guardian_phone');
            $table->string('guardian_email')->nullable();
            $table->string('guardian_relationship')->default('parent');
            $table->string('guardian_occupation')->nullable();
            $table->string('guardian_address')->nullable();
            $table->enum('status', ['pending', 'shortlisted', 'admitted', 'rejected', 'withdrawn'])->default('pending');
            $table->text('notes')->nullable();
            $table->date('application_date');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->date('decision_date')->nullable();
            $table->unsignedBigInteger('enrolled_as_student_id')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
            $table->index(['tenant_id', 'status']);
        });

        // ── Student Health Records ──────────────────────────────────
        Schema::create('student_health_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->string('blood_group', 5)->nullable();
            $table->string('genotype', 5)->nullable();
            $table->text('allergies')->nullable();
            $table->text('chronic_conditions')->nullable();
            $table->text('current_medications')->nullable();
            $table->string('disability')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('doctor_phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'student_id']);
            $table->index('tenant_id');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('student_health_records');
        Schema::dropIfExists('admissions');
    }
};
