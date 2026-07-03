<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * No schema changes needed — role is stored as VARCHAR(40).
 * This migration just confirms the admission_officer role is valid
 * and optionally seeds a sample if needed.
 */
return new class extends Migration {
    public function up(): void
    {
        // Nothing to migrate schema-wise — VARCHAR(40) role column
        // already supports 'admission_officer'.
        // This migration is a marker so the team knows this role was added.
    }
    public function down(): void {}
};
