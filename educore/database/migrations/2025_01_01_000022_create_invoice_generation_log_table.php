<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Invoice Generation Batches (audit log) ─────────────────────
        if (!Schema::hasTable('invoice_generation_batches')) {
            Schema::create('invoice_generation_batches', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('term_id');
                $table->unsignedBigInteger('generated_by');
                $table->enum('scope', ['all', 'class_level', 'class_arm', 'individual'])
                      ->default('class_level');
                $table->unsignedBigInteger('class_level_id')->nullable();
                $table->unsignedBigInteger('class_arm_id')->nullable();
                $table->integer('total_students');
                $table->integer('generated_count');
                $table->integer('skipped_count');
                $table->decimal('total_value', 14, 2)->default(0);
                $table->enum('status', ['completed', 'partial', 'failed'])->default('completed');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('tenant_id');
                $table->index('term_id');
            });
        }

        // ── Invoice Discount Templates ─────────────────────────────────
        if (!Schema::hasTable('invoice_discount_templates')) {
            Schema::create('invoice_discount_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('name');                    // e.g. "Staff Ward 50%", "Scholarship"
                $table->enum('type', ['percentage', 'fixed']);
                $table->decimal('value', 8, 2);            // 50 for 50% or 5000 for ₦5000 off
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('tenant_id');
            });
        }

        // ── Add discount_template_id to invoices ───────────────────────
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'discount_template_id'))
                $table->unsignedBigInteger('discount_template_id')->nullable();
            if (!Schema::hasColumn('invoices', 'discount_amount'))
                $table->decimal('discount_amount', 10, 2)->default(0);
            if (!Schema::hasColumn('invoices', 'notes'))
                $table->text('notes')->nullable();
            if (!Schema::hasColumn('invoices', 'generation_batch_id'))
                $table->unsignedBigInteger('generation_batch_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_discount_templates');
        Schema::dropIfExists('invoice_generation_batches');
        Schema::table('invoices', function (Blueprint $table) {
            foreach (['discount_template_id','discount_amount','notes','generation_batch_id'] as $col) {
                if (Schema::hasColumn('invoices', $col)) $table->dropColumn($col);
            }
        });
    }
};
