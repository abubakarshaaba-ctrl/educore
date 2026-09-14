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

        // Some deployments received an earlier shape of this table before the
        // structured Academic Repository contract was finalised. The migration
        // that creates the table cannot repair those installations once it has
        // already been recorded, so add the runtime-required columns explicitly.
        Schema::table('academic_topic_blocks', function (Blueprint $table) {
            if (! Schema::hasColumn('academic_topic_blocks', 'sequence')) {
                $table->unsignedSmallInteger('sequence')->default(1);
            }
            if (! Schema::hasColumn('academic_topic_blocks', 'title')) {
                $table->string('title', 255)->nullable();
            }
            if (! Schema::hasColumn('academic_topic_blocks', 'metadata')) {
                $table->json('metadata')->nullable();
            }
            if (! Schema::hasColumn('academic_topic_blocks', 'is_required')) {
                $table->boolean('is_required')->default(false);
            }
            if (! Schema::hasColumn('academic_topic_blocks', 'is_approved')) {
                $table->boolean('is_approved')->default(false);
            }
        });

        // Preserve data from the legacy column names used by the first draft
        // of the repository schema.
        if (Schema::hasColumn('academic_topic_blocks', 'position')) {
            DB::table('academic_topic_blocks')
                ->whereNotNull('position')
                ->update(['sequence' => DB::raw('position')]);
        }

        if (Schema::hasColumn('academic_topic_blocks', 'heading')) {
            DB::table('academic_topic_blocks')
                ->whereNull('title')
                ->whereNotNull('heading')
                ->update(['title' => DB::raw('heading')]);
        }

        // If approval was introduced by this repair, existing blocks that
        // belong to already-approved topics are trusted at the same level as
        // their parent topic. Draft/review topics remain unapproved.
        if (Schema::hasTable('academic_topics')
            && Schema::hasColumn('academic_topic_blocks', 'is_approved')
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
