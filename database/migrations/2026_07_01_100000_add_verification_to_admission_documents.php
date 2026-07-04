<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_documents', function (Blueprint $table) {
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])
                  ->default('pending')->after('original_name');
            $table->text('verification_note')->nullable()->after('verification_status');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete()->after('verification_note');
            $table->timestamp('verified_at')->nullable()->after('verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('admission_documents', function (Blueprint $table) {
            $table->dropColumn(['verification_status', 'verification_note', 'verified_by', 'verified_at']);
        });
    }
};
