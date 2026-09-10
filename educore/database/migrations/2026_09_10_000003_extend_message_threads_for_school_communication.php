<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_threads', function (Blueprint $table): void {
            $table->unsignedBigInteger('student_id')->nullable()->change();
            $table->string('conversation_type', 40)->default('student')->after('student_id')->index();
            $table->unsignedBigInteger('recipient_user_id')->nullable()->after('conversation_type')->index();
            $table->string('audience', 40)->nullable()->after('recipient_user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('message_threads', function (Blueprint $table): void {
            $table->dropIndex(['conversation_type']);
            $table->dropIndex(['recipient_user_id']);
            $table->dropIndex(['audience']);
            $table->dropColumn(['conversation_type', 'recipient_user_id', 'audience']);
        });
    }
};
