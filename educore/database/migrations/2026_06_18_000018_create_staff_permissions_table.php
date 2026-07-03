<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('staff_permissions')) {
            Schema::create('staff_permissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('user_id');
                $table->string('module', 60);
                $table->enum('type', ['grant','deny'])->default('grant');
                $table->unsignedBigInteger('granted_by');
                $table->timestamps();
                $table->unique(['tenant_id','user_id','module']);
                $table->index(['tenant_id','user_id']);
            });
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('staff_permissions');
    }
};
