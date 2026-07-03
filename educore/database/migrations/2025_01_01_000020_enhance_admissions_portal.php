<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Enhance admissions table for public portal
        Schema::table('admissions', function (Blueprint $table) {
            if (!Schema::hasColumn('admissions', 'passport_photo'))
                $table->string('passport_photo')->nullable()->after('address');
            if (!Schema::hasColumn('admissions', 'birth_certificate'))
                $table->string('birth_certificate')->nullable()->after('passport_photo');
            if (!Schema::hasColumn('admissions', 'last_report_card'))
                $table->string('last_report_card')->nullable()->after('birth_certificate');
            if (!Schema::hasColumn('admissions', 'portal_token'))
                $table->string('portal_token', 64)->nullable()->after('application_number');
            if (!Schema::hasColumn('admissions', 'portal_email_verified'))
                $table->boolean('portal_email_verified')->default(false);
            if (!Schema::hasColumn('admissions', 'source'))
                $table->string('source')->default('portal'); // portal | manual | agent
            if (!Schema::hasColumn('admissions', 'academic_year'))
                $table->string('academic_year')->nullable();
            if (!Schema::hasColumn('admissions', 'interview_date'))
                $table->date('interview_date')->nullable();
            if (!Schema::hasColumn('admissions', 'interview_score'))
                $table->decimal('interview_score', 5, 2)->nullable();
            if (!Schema::hasColumn('admissions', 'interview_notes'))
                $table->text('interview_notes')->nullable();
            if (!Schema::hasColumn('admissions', 'offer_letter_sent'))
                $table->boolean('offer_letter_sent')->default(false);
            if (!Schema::hasColumn('admissions', 'offer_sent_at'))
                $table->timestamp('offer_sent_at')->nullable();
        });

        // Admission portal settings (per school)
        if (!Schema::hasTable('admission_portal_settings')) {
            Schema::create('admission_portal_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->unique();
                $table->boolean('is_open')->default(true);
                $table->date('opens_on')->nullable();
                $table->date('closes_on')->nullable();
                $table->string('academic_year')->nullable(); // e.g. "2025/2026"
                $table->decimal('application_fee', 10, 2)->default(0);
                $table->text('welcome_message')->nullable();
                $table->text('requirements')->nullable(); // JSON or text
                $table->boolean('require_passport')->default(true);
                $table->boolean('require_birth_cert')->default(false);
                $table->boolean('require_report_card')->default(false);
                $table->boolean('notify_guardian_sms')->default(true);
                $table->boolean('notify_guardian_email')->default(true);
                $table->boolean('auto_shortlist')->default(false);
                $table->text('footer_note')->nullable();
                $table->timestamps();
            });
        }

        // Admission documents (uploaded files)
        if (!Schema::hasTable('admission_documents')) {
            Schema::create('admission_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('admission_id');
                $table->unsignedBigInteger('tenant_id');
                $table->string('document_type'); // passport, birth_cert, report_card, other
                $table->string('file_path');
                $table->string('original_name');
                $table->timestamps();
                $table->index(['admission_id', 'document_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_documents');
        Schema::dropIfExists('admission_portal_settings');
    }
};
