<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('school_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 170)->unique();
            $table->text('description')->nullable();
            $table->string('owner_name', 120)->nullable();
            $table->string('owner_email', 180)->nullable();
            $table->timestamps();
        });

        Schema::create('school_group_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('tenant_id');
            $table->enum('role', ['member','lead'])->default('member');
            $table->timestamps();
            $table->unique(['group_id','tenant_id']);
            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_group_members');
        Schema::dropIfExists('school_groups');
    }
};
