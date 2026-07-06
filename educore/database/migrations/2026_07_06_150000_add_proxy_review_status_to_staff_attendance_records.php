<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_attendance_records', function (Blueprint $table) {
            if (!Schema::hasColumn('staff_attendance_records', 'proxy_review_status')) {
                // null = not a proxy clock-in; 'pending' = awaiting admin review
                // against the passport photo; 'confirmed' / 'flagged' = reviewed.
                $table->string('proxy_review_status', 20)->nullable()->after('proxy_photo');
            }
            if (!Schema::hasColumn('staff_attendance_records', 'proxy_reviewed_by')) {
                $table->foreignId('proxy_reviewed_by')->nullable()->after('proxy_review_status')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('staff_attendance_records', 'proxy_reviewed_at')) {
                $table->timestamp('proxy_reviewed_at')->nullable()->after('proxy_reviewed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('staff_attendance_records', function (Blueprint $table) {
            $table->dropColumn(['proxy_review_status', 'proxy_reviewed_by', 'proxy_reviewed_at']);
        });
    }
};
