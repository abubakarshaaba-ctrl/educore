<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('job_applicants', 'access_token')) {
            Schema::table('job_applicants', function (Blueprint $table) {
                $table->string('access_token', 40)->nullable()->unique()->after('id');
            });
        }

        if (!Schema::hasTable('job_applicant_messages')) {
            Schema::create('job_applicant_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('job_applicant_id');
                $table->enum('sender_type', ['school', 'applicant']);
                $table->unsignedBigInteger('sender_user_id')->nullable();
                $table->text('body');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'job_applicant_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applicant_messages');
        if (Schema::hasColumn('job_applicants', 'access_token')) {
            Schema::table('job_applicants', function (Blueprint $table) {
                $table->dropColumn('access_token');
            });
        }
    }
};
