<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── School Settings (per tenant key-value) ─────────────────
        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->string('group', 50)->default('general');
            $table->timestamps();
            $table->unique(['tenant_id', 'key']);
            $table->index('tenant_id');
        });

        // ── Academic Calendar & Events ──────────────────────────────
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('type', 50)->default('event');
            // types: holiday, exam, pta, event, resumption, closing
            $table->string('color', 20)->default('#2563EB');
            $table->boolean('is_public')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
        Schema::dropIfExists('school_settings');
    }
};
