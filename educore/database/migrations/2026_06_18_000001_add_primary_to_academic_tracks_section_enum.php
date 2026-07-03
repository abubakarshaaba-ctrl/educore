<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds 'primary' to the academic_tracks.section enum
 * and inserts the Primary track seed record.
 *
 * Safe to run even if already partially applied.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('academic_tracks')) {
            return;
        }
        // Step 1: Alter the enum to include 'primary'
        // MySQL requires dropping default first, then modifying enum
        DB::statement("
            ALTER TABLE `academic_tracks`
            MODIFY COLUMN `section`
            ENUM('primary','junior','senior','general')
            NOT NULL DEFAULT 'general'
        ");

        // Step 2: Insert Primary track if not already present
        DB::table('academic_tracks')->insertOrIgnore([
            [
                'tenant_id'  => null,
                'name'       => 'Primary',
                'slug'       => 'primary',
                'section'    => 'primary',
                'is_active'  => 1,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Step 3: Ensure the other system tracks exist (idempotent)
        $existing = DB::table('academic_tracks')
            ->whereNull('tenant_id')
            ->pluck('slug')
            ->toArray();

        $tracks = [
            ['slug'=>'general',    'name'=>'General',    'section'=>'general', 'sort_order'=>2],
            ['slug'=>'science',    'name'=>'Science',    'section'=>'senior',  'sort_order'=>3],
            ['slug'=>'humanities', 'name'=>'Humanities', 'section'=>'senior',  'sort_order'=>4],
            ['slug'=>'business',   'name'=>'Business',   'section'=>'senior',  'sort_order'=>5],
        ];

        foreach ($tracks as $track) {
            if (!in_array($track['slug'], $existing)) {
                DB::table('academic_tracks')->insert([
                    'tenant_id'  => null,
                    'name'       => $track['name'],
                    'slug'       => $track['slug'],
                    'section'    => $track['section'],
                    'is_active'  => 1,
                    'sort_order' => $track['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Step 4: Update sort orders for consistency
        DB::table('academic_tracks')
            ->whereNull('tenant_id')
            ->where('slug', 'primary')
            ->update(['sort_order' => 1]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('academic_tracks')) {
            return;
        }
        // Remove Primary track
        DB::table('academic_tracks')
            ->whereNull('tenant_id')
            ->where('slug', 'primary')
            ->delete();

        // Revert enum (remove 'primary')
        DB::statement("
            ALTER TABLE `academic_tracks`
            MODIFY COLUMN `section`
            ENUM('junior','senior','general')
            NOT NULL DEFAULT 'general'
        ");
    }
};
