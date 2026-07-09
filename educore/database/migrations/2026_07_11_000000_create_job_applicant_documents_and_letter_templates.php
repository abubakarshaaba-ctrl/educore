<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('job_applicant_documents')) {
            Schema::create('job_applicant_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('job_applicant_id');
                $table->string('document_type'); // resume, certificate, other
                $table->string('file_path');
                $table->string('original_name');
                $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
                $table->text('verification_note')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
                $table->index(['job_applicant_id', 'document_type']);
            });
        }

        if (!Schema::hasTable('letter_templates')) {
            Schema::create('letter_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->enum('type', ['admission_offer', 'job_offer']);
                $table->text('intro_text')->nullable();
                $table->text('body_text')->nullable();
                $table->text('closing_text')->nullable();
                $table->string('signatory_1_label', 100)->nullable();
                $table->string('signatory_2_label', 100)->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'type']);
            });
        }

        if (!Schema::hasColumn('job_applicants', 'offer_letter_sent')) {
            Schema::table('job_applicants', function (Blueprint $table) {
                $table->boolean('offer_letter_sent')->default(false)->after('status');
                $table->timestamp('offer_sent_at')->nullable()->after('offer_letter_sent');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_templates');
        Schema::dropIfExists('job_applicant_documents');
        if (Schema::hasColumn('job_applicants', 'offer_letter_sent')) {
            Schema::table('job_applicants', function (Blueprint $table) {
                $table->dropColumn(['offer_letter_sent', 'offer_sent_at']);
            });
        }
    }
};
