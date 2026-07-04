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

        // MySQL/MariaDB requires explicitly widening the ENUM. SQLite stores
        // Laravel enum columns as TEXT and does not support MODIFY COLUMN.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE `academic_tracks`
                MODIFY COLUMN `section`
                ENUM('primary','junior','senior','general')
                NOT NULL DEFAULT 'general'
            ");
        }

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

        $existing = DB::table('academic_tracks')
            ->whereNull('tenant_id')
            ->pluck('slug')
            ->toArray();

        $tracks = [
            ['slug' => 'general', 'name' => 'General', 'section' => 'general', 'sort_order' => 2],
            ['slug' => 'science', 'name' => 'Science', 'section' => 'senior', 'sort_order' => 3],
            ['slug' => 'humanities', 'name' => 'Humanities', 'section' => 'senior', 'sort_order' => 4],
            ['slug' => 'business', 'name' => 'Business', 'section' => 'senior', 'sort_order' => 5],
        ];

        foreach ($tracks as $track) {
            if (! in_array($track['slug'], $existing, true)) {
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

        DB::table('academic_tracks')
            ->whereNull('tenant_id')
            ->where('slug', 'primary')
            ->delete();

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE `academic_tracks`
                MODIFY COLUMN `section`
                ENUM('junior','senior','general')
                NOT NULL DEFAULT 'general'
            ");
        }
    }
};
