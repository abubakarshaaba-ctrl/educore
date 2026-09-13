<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('terms') || ! Schema::hasTable('assessment_types')) {
            return;
        }

        $now = now();
        $terms = DB::table('terms')
            ->select(['id', 'tenant_id'])
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('assessment_types')
                    ->whereColumn('assessment_types.term_id', 'terms.id');
            })
            ->get();

        foreach ($terms as $term) {
            DB::table('assessment_types')->insert([
                [
                    'tenant_id' => $term->tenant_id,
                    'term_id' => $term->id,
                    'name' => 'Continuous Assessment',
                    'weight_percentage' => 30,
                    'objective_max' => null,
                    'theory_max' => null,
                    'is_exam' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'tenant_id' => $term->tenant_id,
                    'term_id' => $term->id,
                    'name' => 'Examination',
                    'weight_percentage' => 70,
                    'objective_max' => null,
                    'theory_max' => null,
                    'is_exam' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }

    public function down(): void
    {
        // Deliberately non-destructive: assessment types may already have scores
        // after this migration runs, so rollback must not remove academic data.
    }
};
