<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_topic_blocks')) {
            return;
        }

        // Record the pre-repair shape so data backfills only run for columns
        // introduced by this migration. Existing curation must not be changed.
        $hadSequence = Schema::hasColumn('academic_topic_blocks', 'sequence');
        $hadTitle = Schema::hasColumn('academic_topic_blocks', 'title');
        $hadIsApproved = Schema::hasColumn('academic_topic_blocks', 'is_approved');

        // Some deployments received an earlier shape of this table before the
        // structured Academic Repository contract was finalised. The migration
        // that creates the table cannot repair those installations once it has
        // already been recorded, so add the runtime-required columns explicitly.
        Schema::table('academic_topic_blocks', function (Blueprint $table) use ($hadSequence, $hadTitle, $hadIsApproved) {
            if (! $hadSequence) {
                $table->unsignedSmallInteger('sequence')->default(1);
            }
            if (! $hadTitle) {
                $table->string('title', 255)->nullable();
            }
            if (! Schema::hasColumn('academic_topic_blocks', 'metadata')) {
                $table->json('metadata')->nullable();
            }
            if (! Schema::hasColumn('academic_topic_blocks', 'is_required')) {
                $table->boolean('is_required')->default(false);
            }
            if (! $hadIsApproved) {
                $table->boolean('is_approved')->default(false);
            }
        });

        // Preserve data from legacy column names, but only when their current
        // replacements were introduced by this repair.
        if (! $hadSequence && Schema::hasColumn('academic_topic_blocks', 'position')) {
            DB::table('academic_topic_blocks')
                ->whereNotNull('position')
                ->update(['sequence' => DB::raw('position')]);
        }

        if (! $hadTitle && Schema::hasColumn('academic_topic_blocks', 'heading')) {
            DB::table('academic_topic_blocks')
                ->whereNull('title')
                ->whereNotNull('heading')
                ->update(['title' => DB::raw('heading')]);
        }

        // If approval was introduced by this repair, existing blocks that
        // belong to already-approved topics inherit that parent approval.
        // Draft/review topics remain unapproved. Existing approval values are
        // never overwritten.
        if (! $hadIsApproved
            && Schema::hasTable('academic_topics')
            && Schema::hasColumn('academic_topic_blocks', 'academic_topic_id')
            && Schema::hasColumn('academic_topics', 'status')) {
            DB::table('academic_topic_blocks')
                ->whereIn('academic_topic_id', function ($query) {
                    $query->select('id')
                        ->from('academic_topics')
                        ->where('status', 'approved');
                })
                ->update(['is_approved' => true]);
        }
    }

    public function down(): void
    {
        // Compatibility repair: do not remove columns or restored data.
    }
};
