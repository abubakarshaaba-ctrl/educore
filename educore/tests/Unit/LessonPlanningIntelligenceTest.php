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
            'previous_background_knowledge'=>'Students were taught genes and chromosomes in the previous lesson.',
            'behavioural_objectives'=>['Define Mendelian inheritance accurately.','Identify dominant and recessive traits.','Explain inheritance using family traits.'],
            'instructional_resources'=>['A labelled inherited-traits chart','Photographs showing contrasting traits'],
            'introduction'=>'The teacher revises genes through questions, receives student responses, and links familiar family traits to inheritance.',
            'presentation'=>[['step'=>1,'title'=>'Inheritance','activities'=>[
                'Teacher guides the students to define inheritance as the transmission of traits from parents to offspring.',
                'Teacher aids the students to identify dominant and recessive traits using the labelled chart.',
                'Teacher helps the students to explain inheritance with familiar examples of contrasting family traits.',
            ]]],
            'evaluation'=>['What is biological inheritance?','Differentiate dominant and recessive traits.','Give two examples of inherited traits.'],
            'assignment'=>'List and explain three inherited traits observed in families.','references'=>null,
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

    public function test_plan_schema_rejects_shallow_presentation_that_does_not_cover_each_subtopic(): void
    {
        $this->expectException(ValidationException::class);
        app(LessonPlanSchema::class)->validate([
            'class'=>'Year 12','subject'=>'Biology','lesson'=>'1','topic'=>'Development','week'=>'1','time'=>'12:10','duration'=>'70 minutes','average_age'=>'15','sex'=>'Mixed',
            'sub_topics'=>['Courtship behaviour','Metamorphosis'],'entry_behaviour'=>'Students can identify examples of animal reproduction.',
            'previous_background_knowledge'=>'Students were previously taught sexual and asexual reproduction.',
            'behavioural_objectives'=>['Define courtship behaviour accurately.','Explain metamorphosis in insects.','Compare complete and incomplete metamorphosis.'],
            'instructional_resources'=>['Insect life-cycle chart','Preserved insect specimens'],'introduction'=>'The teacher revises animal reproduction through questions, receives student responses, and links them to development.',
            'presentation'=>[['step'=>1,'title'=>'Courtship behaviour','activities'=>['Teacher briefly discusses the selected lesson topic with the students.']]],
            'evaluation'=>['What is courtship behaviour?','What is metamorphosis?','Compare both forms of metamorphosis.'],
            'assignment'=>'Describe complete metamorphosis with an example.','references'=>[],
        ]);
    }

    public function test_plan_schema_rejects_non_measurable_objectives_and_generic_teaching_filler(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(\App\Services\LessonPlanning\LessonPlanSchema::class)->validate([
            'class'=>'SS 1','subject'=>'Biology','lesson'=>'1','topic'=>'The cell','sub_topics'=>['Cell structure'],
            'duration'=>'40 minutes','sex'=>'Mixed','entry_behaviour'=>'Learners can identify common living things around them.',
            'previous_background_knowledge'=>'Learners previously studied the characteristics of living things.',
            'behavioural_objectives'=>['Understand cells very well','Know the parts of a cell','Appreciate cell structure'],
            'instructional_resources'=>['Cell chart','Onion epidermal slide'],'introduction'=>'Teacher revises living things through questions, receives learner responses and links them to cells.',
            'presentation'=>[['step'=>1,'title'=>'Cell structure','activities'=>[
                'Teacher will discuss the topic with all the students in the classroom.',
                'Teacher will explain key concepts and ask the learners to listen carefully.',
                'Teacher will provide examples before learners copy their class notes.',
            ]]],
            'evaluation'=>['Define a cell.','Name two cell parts.','State one function of the nucleus.'],
            'assignment'=>'Draw and label a typical plant cell.','references'=>[],
        ]);
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
