<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('staff_disciplinary_actions')) {
            return;
        }

        Schema::create('staff_disciplinary_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('staff_id');
            $table->string('offence_type', 60);
            $table->text('offence_description')->nullable();
            $table->string('action_type', 40);
            $table->decimal('amount', 12, 2)->nullable();
            $table->date('suspension_start_date')->nullable();
            $table->date('suspension_end_date')->nullable();
            $table->date('effective_date');
            $table->unsignedBigInteger('staff_deduction_id')->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'staff_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_disciplinary_actions');
    }
};
