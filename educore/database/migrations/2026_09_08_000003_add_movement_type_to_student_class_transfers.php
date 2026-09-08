<?php

use App\Models\StudentClassTransfer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_class_transfers')) {
            return;
        }

        if (!Schema::hasColumn('student_class_transfers', 'movement_type')) {
            Schema::table('student_class_transfers', function (Blueprint $table): void {
                $table->string('movement_type', 30)
                    ->default(StudentClassTransfer::TYPE_INTERCLASS)
                    ->after('to_class_arm_id');
                $table->index(['tenant_id', 'movement_type'], 'idx_stuclasstrans_tenant_movement');
            });
        }

        // Reclassify historical rows portably. A same-level arm move is
        // intra-class; a move between different class levels is interclass.
        DB::table('student_class_transfers')
            ->select(['id', 'from_class_arm_id', 'to_class_arm_id'])
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                $armIds = collect($rows)
                    ->flatMap(fn ($row) => [(int) $row->from_class_arm_id, (int) $row->to_class_arm_id])
                    ->filter()
                    ->unique()
                    ->values();

                $levels = DB::table('class_arms')
                    ->whereIn('id', $armIds)
                    ->pluck('class_level_id', 'id');

                foreach ($rows as $row) {
                    $fromLevel = $levels->get((int) $row->from_class_arm_id);
                    $toLevel = $levels->get((int) $row->to_class_arm_id);
                    if (!$fromLevel || !$toLevel) {
                        continue;
                    }

                    DB::table('student_class_transfers')
                        ->where('id', $row->id)
                        ->update([
                            'movement_type' => (int) $fromLevel === (int) $toLevel
                                ? StudentClassTransfer::TYPE_INTRA_CLASS
                                : StudentClassTransfer::TYPE_INTERCLASS,
                        ]);
                }
            }, 'id');
    }

    public function down(): void
    {
        if (!Schema::hasTable('student_class_transfers') ||
            !Schema::hasColumn('student_class_transfers', 'movement_type')) {
            return;
        }

        Schema::table('student_class_transfers', function (Blueprint $table): void {
            $table->dropIndex('idx_stuclasstrans_tenant_movement');
            $table->dropColumn('movement_type');
        });
    }
};
