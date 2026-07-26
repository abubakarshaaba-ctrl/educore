<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('student_risk_flags')) {
            Schema::create('student_risk_flags', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('term_id');
                $table->unsignedBigInteger('class_arm_id')->nullable();

                // Risk dimensions (each 0–100, 100 = highest risk)
                $table->tinyInteger('academic_risk')->default(0);     // based on avg score
                $table->tinyInteger('attendance_risk')->default(0);   // based on absence rate
                $table->tinyInteger('fee_risk')->default(0);          // based on outstanding fees
                $table->tinyInteger('subjects_failed')->default(0);   // count of failed subjects

                // Computed composite score 0–100
                $table->tinyInteger('composite_risk')->default(0);

                // Overall level
                $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])
                      ->default('low');

                // Specific flags (JSON array of strings)
                $table->json('flags')->nullable();
                // e.g. ["avg_below_40","absent_7_days","3_subjects_failed","fees_overdue"]

                // Action tracking
                $table->enum('status', ['open', 'acknowledged', 'resolved'])
                      ->default('open');
                $table->text('intervention_note')->nullable();
                $table->unsignedBigInteger('acknowledged_by')->nullable();
                $table->timestamp('acknowledged_at')->nullable();
                $table->unsignedBigInteger('resolved_by')->nullable();
                $table->timestamp('resolved_at')->nullable();

                // Metadata — always set explicitly on write (see
                // RiskFlagController), but a second NOT NULL timestamp column
                // with no default is rejected outright under strict MySQL modes.
                $table->timestamp('computed_at')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'term_id', 'risk_level']);
                $table->index(['student_id', 'term_id']);
                $table->unique(['student_id', 'term_id']);  // one flag record per student per term
            });
        }

        // Risk thresholds config (per tenant, per term)
        if (!Schema::hasTable('risk_threshold_configs')) {
            Schema::create('risk_threshold_configs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->decimal('academic_threshold', 5, 2)->default(45.00);   // avg below this = at risk
                $table->decimal('attendance_threshold', 5, 2)->default(75.00); // presence rate below = at risk
                $table->integer('subjects_failed_threshold')->default(2);       // how many fails = at risk
                $table->boolean('include_fee_risk')->default(true);
                $table->integer('academic_weight')->default(40);    // weights sum to 100
                $table->integer('attendance_weight')->default(35);
                $table->integer('fee_weight')->default(25);
                $table->timestamps();
                $table->unique('tenant_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_threshold_configs');
        Schema::dropIfExists('student_risk_flags');
    }
};
