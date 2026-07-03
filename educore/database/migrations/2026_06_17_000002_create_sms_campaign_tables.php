<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('title', 150);
            $table->text('message');
            $table->enum('audience', ['all_parents','all_staff','class_parents','custom'])->default('all_parents');
            $table->unsignedBigInteger('class_arm_id')->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->enum('status', ['draft','scheduled','sent','failed'])->default('draft');
            $table->timestamp('schedule_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
        });

        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->string('phone', 30);
            $table->text('message');
            $table->enum('status', ['queued','sent','delivered','failed'])->default('queued');
            $table->timestamp('sent_at')->nullable();
            $table->string('error', 255)->nullable();
            $table->timestamps();
            $table->index('campaign_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('sms_campaigns');
    }
};
