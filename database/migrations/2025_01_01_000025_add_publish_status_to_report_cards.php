<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Report Card Publication Status ────────────────────────────
        if (!Schema::hasTable('report_card_publications')) {
            Schema::create('report_card_publications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('class_arm_id');
                $table->unsignedBigInteger('term_id');
                $table->enum('status', ['draft','published','archived'])->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->unsignedBigInteger('published_by')->nullable();
                $table->timestamp('archived_at')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
                $table->unique(['class_arm_id','term_id']);
                $table->index(['tenant_id','term_id','status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('report_card_publications');
    }
};
