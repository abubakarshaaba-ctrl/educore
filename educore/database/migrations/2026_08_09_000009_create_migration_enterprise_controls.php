<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('is_migration_admin')->default(false)->index()->after('is_super_admin');
        });
        Schema::create('migration_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('migration_id')->constrained('data_migrations')->cascadeOnDelete();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $t->string('status', 32)->index();
            $t->string('requested_scope', 64);
            $t->text('business_justification');
            $t->json('data_scope');
            $t->string('risk_level', 16)->default('high');
            $t->foreignId('school_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('school_approved_at')->nullable();
            $t->foreignId('platform_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('platform_approved_at')->nullable();
            $t->text('decision_reason')->nullable();
            $t->timestamps();
            $t->unique('migration_id');
            $t->index(['tenant_id', 'status']);
        });
        Schema::create('migration_notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('migration_id')->nullable()->constrained('data_migrations')->cascadeOnDelete();
            $t->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $t->foreignId('recipient_user_id')->constrained('users')->cascadeOnDelete();
            $t->string('event', 80)->index();
            $t->string('channel', 24)->default('in_app');
            $t->json('payload');
            $t->string('status', 20)->default('pending')->index();
            $t->timestamp('read_at')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_notifications');
        Schema::dropIfExists('migration_requests');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_migration_admin'));
    }
};
