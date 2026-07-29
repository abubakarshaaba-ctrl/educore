<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('exam_body_registrations');
    }

    public function down(): void
    {
        if (Schema::hasTable('exam_body_registrations')) {
            return;
        }

        Schema::create('exam_body_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->enum('exam_body', ['WAEC', 'NECO', 'NABTEB', 'JAMB']);
            $table->string('exam_year', 9);
            $table->string('registration_number', 60)->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->json('subjects')->nullable();
            $table->enum('status', ['pending', 'registered', 'completed'])->default('pending');
            $table->unsignedBigInteger('registered_by')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'student_id']);
        });
    }
};
