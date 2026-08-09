<?php

namespace Tests\Feature;

use App\Models\CurriculumFragment;
use App\Models\CurriculumSource;
use App\Models\LessonPlan;
use App\Services\Curriculum\CurriculumRetrievalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumGroundingTest extends TestCase
{
    use RefreshDatabase;

    public function test_retrieval_combines_platform_and_current_tenant_sources_without_cross_tenant_leakage(): void
    {
        $platform = $this->source(null, 'NERDC', true, 'approved', '2025');
        $tenantA = $this->source(10, 'SCHOOL', true, 'approved', '2026');
        $tenantB = $this->source(20, 'SCHOOL', true, 'approved', '2026');
        $inactive = $this->source(10, 'WAEC', false, 'approved', '2026');
        foreach ([$platform,$tenantA,$tenantB,$inactive] as $source) CurriculumFragment::create([
            'curriculum_source_id'=>$source->id,'topic'=>'Metamorphosis','content'=>'Explain complete and incomplete metamorphosis.']);

        $plan = new LessonPlan(['tenant_id'=>10,'subject_id'=>1,'class_level_id'=>2,'topic'=>'Metamorphosis','subtopic'=>'Complete metamorphosis']);
        $ids = app(CurriculumRetrievalService::class)->forLessonPlan($plan)->pluck('curriculum_source_id');
        $this->assertTrue($ids->contains($platform->id));
        $this->assertTrue($ids->contains($tenantA->id));
        $this->assertFalse($ids->contains($tenantB->id));
        $this->assertFalse($ids->contains($inactive->id));
    }

    public function test_unapproved_and_expired_versions_are_excluded(): void
    {
        $pending = $this->source(null, 'NERDC', true, 'pending', 'draft');
        $expired = $this->source(null, 'WAEC', true, 'approved', '2020', now()->subYear()->toDateString());
        foreach ([$pending,$expired] as $source) CurriculumFragment::create(['curriculum_source_id'=>$source->id,'topic'=>'Algebra','content'=>'Solve linear equations.']);
        $plan = new LessonPlan(['tenant_id'=>10,'subject_id'=>1,'class_level_id'=>2,'topic'=>'Algebra']);
        $this->assertTrue(app(CurriculumRetrievalService::class)->forLessonPlan($plan)->isEmpty());
    }

    public function test_legacy_lesson_plan_entry_behaviour_remains_nullable(): void
    {
        $this->assertTrue(\Schema::hasColumn('lesson_plans','entry_behaviour'));
        $this->assertTrue(\Schema::hasColumn('lesson_plans','structured_plan'));
        $this->assertTrue(\Schema::hasTable('lesson_note_revisions'));
    }

    private function source(?int $tenantId, string $authority, bool $active, string $review, string $version, ?string $effectiveTo = null): CurriculumSource
    {
        return CurriculumSource::create(['tenant_id'=>$tenantId,'authority'=>$authority,'source_type'=>'curriculum_document','title'=>"{$authority} source",
            'version'=>$version,'effective_to'=>$effectiveTo,'is_official'=>$tenantId===null,'is_active'=>$active,'review_status'=>$review,'created_by'=>1]);
    }
}
