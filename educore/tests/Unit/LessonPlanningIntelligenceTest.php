<?php

namespace Tests\Unit;

use App\Models\LessonPlan;
use App\Services\LessonPlanning\LessonNoteSchema;
use App\Services\LessonPlanning\LessonNoteValidationService;
use App\Services\LessonPlanning\LessonPlanSchema;
use App\Services\LessonPlanning\StructuredNoteRenderer;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LessonPlanningIntelligenceTest extends TestCase
{
    public function test_structured_plan_requires_entry_behaviour_as_a_separate_field(): void
    {
        $this->expectException(ValidationException::class);
        app(LessonPlanSchema::class)->validate([
            'class'=>'Year 12','subject'=>'Biology','lesson'=>'One','topic'=>'Development','sub_topics'=>['Metamorphosis'],
            'duration'=>'40 minutes','sex'=>'Mixed','previous_background_knowledge'=>'Reproduction was previously taught.',
            'behavioural_objectives'=>['Explain metamorphosis'],'instructional_resources'=>['Chart'],'introduction'=>'Review reproduction.',
            'presentation'=>[['step'=>1,'title'=>'Meaning','activities'=>['Teacher guides students.']]],
            'evaluation'=>['Define metamorphosis.'],'assignment'=>'Compare both forms.','references'=>[],
        ]);
    }

    public function test_note_schema_accepts_subject_independent_content_blocks(): void
    {
        $note = $this->note();
        $this->assertSame('Development of new organisms', app(LessonNoteSchema::class)->validate($note)['title']);
        $this->assertStringContainsString('Diagram placeholder', app(StructuredNoteRenderer::class)->toHtml($note));
        $this->assertStringNotContainsString('<script', app(StructuredNoteRenderer::class)->toHtml(array_replace($note,['overview'=>'<script>alert(1)</script>'])));
    }

    public function test_plan_schema_normalises_numeric_ai_metadata_without_rejecting_the_draft(): void
    {
        $plan = app(LessonPlanSchema::class)->validate([
            'class'=>12,'subject'=>'Biology','week'=>4,'lesson'=>1,'topic'=>'Genetics','sub_topics'=>['Mendelian inheritance'],
            'time'=>1020,'duration'=>40,'average_age'=>16,'sex'=>'Mixed','entry_behaviour'=>'Students can identify inherited traits.',
            'previous_background_knowledge'=>'Students were taught genes and chromosomes.','behavioural_objectives'=>['Explain Mendelian inheritance'],
            'instructional_resources'=>['Trait chart'],'introduction'=>'Review familiar family traits.',
            'presentation'=>[['step'=>1,'title'=>'Inheritance','activities'=>['Teacher guides students to examine the chart.']]],
            'evaluation'=>['Explain inheritance.'],'assignment'=>'List three inherited traits.','references'=>null,
        ]);

        $this->assertSame('40', $plan['duration']);
        $this->assertSame('16', $plan['average_age']);
        $this->assertSame('1', $plan['lesson']);
        $this->assertSame([], $plan['references']);
    }

    public function test_validation_reports_uncovered_plan_items_without_fake_percentages(): void
    {
        $plan = new LessonPlan(['subtopic'=>"Metamorphosis in insects\nFormation of seeds and fruits",'behavioural_objectives'=>"Explain metamorphosis\nDescribe fruit formation"]);
        $result = app(LessonNoteValidationService::class)->validate($plan, $this->note(), []);
        $this->assertContains($result['plan_coverage'], ['FULL','PARTIAL']);
        $this->assertArrayHasKey('NERDC', $result['authority_alignment']);
        $this->assertSame('NOT_APPLICABLE', $result['authority_alignment']['NERDC']);
        $this->assertNotEmpty($result['factual_concerns']);
    }

    public function test_institutional_section_order_keeps_entry_behaviour_before_background_knowledge(): void
    {
        $keys = array_keys(LessonPlan::nerdcSections());
        $this->assertLessThan(array_search('previous_knowledge',$keys,true), array_search('entry_behaviour',$keys,true));
    }

    private function note(): array
    {
        return ['title'=>'Development of new organisms','overview'=>'Metamorphosis explains developmental change.',
            'sections'=>[['heading'=>'Metamorphosis in insects','subheading'=>null,'content_blocks'=>[
                ['type'=>'paragraph','content'=>'Complete metamorphosis has egg, larva, pupa and adult stages.'],
                ['type'=>'diagram','title'=>'Complete metamorphosis','labels'=>['egg','larva','pupa','adult']],
            ]]],'key_examination_points'=>['Distinguish complete and incomplete metamorphosis.'],
            'review_questions'=>['objective'=>['Which stage follows larva?'],'structured'=>[],'application'=>[]],
            'summary'=>'Metamorphosis is a sequence of developmental changes.','source_trace'=>[]];
    }
}
