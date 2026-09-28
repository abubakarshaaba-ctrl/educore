<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_student_class_links', function (Blueprint $table) {
            $table->unsignedBigInteger('class_arm_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('public_student_class_links', function (Blueprint $table) {
            $table->unsignedBigInteger('class_arm_id')->nullable(false)->change();
        });
    }
};
