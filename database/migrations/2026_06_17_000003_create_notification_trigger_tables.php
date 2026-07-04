<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_triggers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('event', 80);
            $table->boolean('is_enabled')->default(false);
            $table->enum('channel', ['sms','email','both'])->default('sms');
            $table->text('template')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id','event']);
        });

        Schema::create('notification_trigger_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('event', 80);
            $table->string('channel', 20);
            $table->string('recipient', 150);
            $table->enum('status', ['queued','sent','failed'])->default('queued');
            $table->timestamps();
            $table->index(['tenant_id','event']);
        });

        Schema::create('notification_queues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('channel', 20);
            $table->string('recipient', 150);
            $table->text('body');
            $table->string('event', 80)->nullable();
            $table->enum('status', ['pending','sent','failed'])->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_queues');
        Schema::dropIfExists('notification_trigger_logs');
        Schema::dropIfExists('notification_triggers');
    }
};
