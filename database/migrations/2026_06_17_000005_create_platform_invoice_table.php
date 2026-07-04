<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('platform_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('plan_id');
            $table->string('invoice_number', 40)->unique();
            $table->decimal('amount', 12, 2);
            $table->enum('billing_cycle', ['monthly','annual'])->default('monthly');
            $table->enum('status', ['pending','paid','overdue','cancelled'])->default('pending');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method', 60)->nullable();
            $table->string('payment_ref', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_invoices');
    }
};
