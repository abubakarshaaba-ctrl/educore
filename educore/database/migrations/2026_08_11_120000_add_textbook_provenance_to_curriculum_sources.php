<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('curriculum_sources', function (Blueprint $table) {
            $table->string('publisher')->nullable()->after('title');
            $table->string('authors')->nullable()->after('publisher');
            $table->string('isbn', 32)->nullable()->after('authors');
            $table->string('approval_reference')->nullable()->after('source_reference');
            $table->string('rights_status', 40)->default('institution_authorised')->after('approval_reference');
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_sources', fn (Blueprint $table) => $table->dropColumn([
            'publisher', 'authors', 'isbn', 'approval_reference', 'rights_status',
        ]));
    }
};
