<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Boarding / hostel management ─────────────────────────────────
        Schema::create('hostels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 120);
            $table->enum('gender', ['male', 'female', 'mixed'])->default('mixed');
            $table->unsignedInteger('capacity')->default(0);
            $table->unsignedBigInteger('warden_id')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
        });

        Schema::create('hostel_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('hostel_id');
            $table->string('room_number', 30);
            $table->unsignedInteger('capacity')->default(4);
            $table->timestamps();
            $table->unique(['hostel_id', 'room_number']);
        });

        Schema::create('hostel_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('hostel_id');
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->decimal('boarding_fee_amount', 12, 2)->default(0);
            $table->enum('boarding_fee_status', ['unpaid', 'paid'])->default('unpaid');
            $table->date('allocated_at');
            $table->date('vacated_at')->nullable();
            $table->enum('status', ['active', 'vacated'])->default('active');
            $table->timestamps();
            $table->index(['tenant_id', 'student_id']);
            $table->index('room_id');
        });

        // ── Staff leave management ───────────────────────────────────────
        Schema::create('staff_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('leave_type', ['annual', 'sick', 'maternity', 'paternity', 'compassionate', 'unpaid', 'other']);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('days_requested')->default(0);
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'user_id']);
        });

        // ── Substitute / relief teacher coverage ─────────────────────────
        Schema::create('class_coverage_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('timetable_period_id')->nullable();
            $table->unsignedBigInteger('class_arm_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('absent_teacher_id');
            $table->unsignedBigInteger('covering_teacher_id');
            $table->date('coverage_date');
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'coverage_date']);
        });

        // ── Recruitment / applicant tracking ─────────────────────────────
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('title', 150);
            $table->string('department', 120)->nullable();
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->date('closes_at')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
        });

        Schema::create('job_applicants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('job_posting_id');
            $table->string('name', 150);
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('resume_path', 255)->nullable();
            $table->text('cover_letter')->nullable();
            $table->enum('status', ['applied', 'shortlisted', 'interview_scheduled', 'interviewed', 'offered', 'hired', 'rejected'])->default('applied');
            $table->text('notes')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'job_posting_id']);
        });

        Schema::create('job_interviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('applicant_id');
            $table->dateTime('interview_at');
            $table->unsignedBigInteger('interviewer_id')->nullable();
            $table->text('notes')->nullable();
            $table->enum('outcome', ['pending', 'pass', 'fail'])->default('pending');
            $table->timestamps();
            $table->index('applicant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_interviews');
        Schema::dropIfExists('job_applicants');
        Schema::dropIfExists('job_postings');
        Schema::dropIfExists('class_coverage_assignments');
        Schema::dropIfExists('staff_leave_requests');
        Schema::dropIfExists('hostel_allocations');
        Schema::dropIfExists('hostel_rooms');
        Schema::dropIfExists('hostels');
    }
};
