<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('staff_onboarding_token', 24)->nullable()->unique()->after('slug');
        });

        Schema::create('staff_profile_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('email', 180);
            $table->string('phone', 30)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 10);
            $table->string('qualification', 40);
            $table->string('address', 255)->nullable();
            $table->date('employment_started_at')->nullable();
            $table->string('position_title', 255)->nullable();
            $table->string('department_name', 255)->nullable();
            $table->string('employment_type', 100)->nullable();
            $table->string('functional_role', 150)->nullable();
            $table->string('grade_level', 100)->nullable();
            $table->string('appointment_type', 100)->nullable();
            $table->string('password_hash');
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id','email']);
            $table->index(['tenant_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_profile_submissions');
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique(['staff_onboarding_token']);
            $table->dropColumn('staff_onboarding_token');
        });
    }
};
