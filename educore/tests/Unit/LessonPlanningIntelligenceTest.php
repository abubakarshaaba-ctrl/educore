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
    public function test_structured_plan_does_not_require_entry_behaviour(): void
    {
        $plan=app(LessonPlanSchema::class)->validate([
            'class'=>'Year 12','subject'=>'Biology','lesson'=>'One','topic'=>'Development','sub_topics'=>['Metamorphosis'],
            'duration'=>'40 minutes','sex'=>'Mixed','previous_background_knowledge'=>'Reproduction was previously taught.',
            'behavioural_objectives'=>['Define metamorphosis accurately.','Identify the stages in an insect life cycle.','Compare observable developmental changes.'],'instructional_resources'=>['Insect life-cycle chart','Photographs of developmental stages'],'introduction'=>'The teacher reviews reproduction through questions, receives responses and links them to developmental changes.',
            'presentation'=>[['step'=>1,'objective_numbers'=>[1,2,3],'title'=>'Metamorphosis','teacher_activities'=>['Teacher guides students to define metamorphosis as a sequence of developmental changes.','Teacher aids students to identify the major stages using a chart.','Teacher helps students compare the observable stages in an insect life cycle.'],'student_activities'=>['Students define metamorphosis and identify the stages on the chart.']]],
            'evaluation'=>['Define metamorphosis.','Identify two stages in a complete life cycle.','Compare two developmental changes.'],'assignment'=>'Compare complete and incomplete metamorphosis with examples.','references'=>[],
        ]);
        $this->assertArrayNotHasKey('entry_behaviour',$plan);
    }

    public function test_note_schema_accepts_subject_independent_content_blocks(): void
    {
        $note = $this->note();
        $this->assertSame('Development of new organisms', app(LessonNoteSchema::class)->validate($note)['topic']);
        $html = app(StructuredNoteRenderer::class)->toHtml($note);
        $this->assertStringContainsString('WEEK 1 | LESSON 1', $html);
        $this->assertStringContainsString('<svg', $html);
        $this->assertStringNotContainsString('Diagram placeholder', $html);
        $this->assertStringNotContainsString('<script', app(StructuredNoteRenderer::class)->toHtml(array_replace($note,['assignment'=>'<script>alert(1)</script>'])));
    }

    public function test_plan_schema_normalises_numeric_ai_metadata_without_rejecting_the_draft(): void
    {
        $plan = app(LessonPlanSchema::class)->validate([
            'class'=>12,'subject'=>'Biology','week'=>4,'lesson'=>1,'topic'=>'Genetics','sub_topics'=>['Mendelian inheritance'],
            'time'=>1020,'duration'=>40,'average_age'=>16,'sex'=>'Mixed',
            'previous_background_knowledge'=>'Students were taught genes and chromosomes in the previous lesson.',
            'behavioural_objectives'=>['Define Mendelian inheritance accurately.','Identify dominant and recessive traits.','Explain inheritance using family traits.'],
            'instructional_resources'=>['A labelled inherited-traits chart','Photographs showing contrasting traits'],
            'introduction'=>'The teacher revises genes through questions, receives student responses, and links familiar family traits to inheritance.',
            'presentation'=>[['step'=>1,'objective_numbers'=>[1,2,3],'title'=>'Mendelian inheritance','teacher_activities'=>[
                'Teacher guides the students to define inheritance as the transmission of traits from parents to offspring.',
                'Teacher aids the students to identify dominant and recessive traits using the labelled chart.',
                'Teacher helps the students to explain inheritance with familiar examples of contrasting family traits.',
            ],'student_activities'=>['Students define inheritance, classify the illustrated traits and explain one inherited family trait.']]],
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

    public function test_institutional_section_order_omits_entry_behaviour(): void
    {
        $keys = array_keys(LessonPlan::nerdcSections());
        $this->assertNotContains('entry_behaviour', $keys);
        $this->assertNotContains('class_activity', $keys);
        $this->assertNotContains('conclusion', $keys);
        $this->assertSame('reference_materials', array_key_last(LessonPlan::nerdcSections()));
    }

    public function test_structured_presentation_is_available_to_screen_and_pdf_when_legacy_text_is_empty(): void
    {
        $plan=new LessonPlan(['presentation'=>null,'structured_plan'=>['presentation'=>[[
            'step'=>1,'objective_numbers'=>[1,2],'title'=>'Courtship Behaviour in Animals',
            'teacher_activities'=>['Teacher guides students to define courtship behaviour.'],
            'student_activities'=>['Students define courtship behaviour and give an example.'],
        ]]]]);
        $this->assertStringContainsString('STEP I: Courtship Behaviour in Animals', $plan->sectionValue('presentation'));
        $this->assertStringNotContainsString('Objective 1', $plan->sectionValue('presentation'));
        $this->assertStringNotContainsString("STUDENTS' ACTIVITY", $plan->sectionValue('presentation'));
    }

    public function test_note_schema_normalises_provider_content_block_aliases(): void
    {
        $note=$this->note();
        $note['sections'][0]['content_blocks'][0]=['type'=>'list','items'=>array_fill(0,40,'Complete metamorphosis proceeds through a distinct developmental stage with specialised structure and function.')];
        $validated=app(LessonNoteSchema::class)->validate($note);
        $this->assertSame('bullets',$validated['sections'][0]['content_blocks'][0]['type']);
    }

    public function test_note_schema_rejects_heading_only_notes(): void
    {
        $this->expectException(ValidationException::class);
        $note=$this->note();
        $note['sections']=[['heading'=>'What is Biology?','subheading'=>'Definition and Branches','content_blocks'=>[
            ['type'=>'paragraph','content'=>''],
        ]]];
        app(LessonNoteSchema::class)->validate($note);
    }

    public function test_plan_schema_rejects_shallow_presentation_that_does_not_cover_each_subtopic(): void
    {
        $this->expectException(ValidationException::class);
        app(LessonPlanSchema::class)->validate([
            'class'=>'Year 12','subject'=>'Biology','lesson'=>'1','topic'=>'Development','week'=>'1','time'=>'12:10','duration'=>'70 minutes','average_age'=>'15','sex'=>'Mixed',
            'sub_topics'=>['Courtship behaviour','Metamorphosis'],
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
            'duration'=>'40 minutes','sex'=>'Mixed',
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
        return ['week'=>'1','lesson'=>'1','topic'=>'Development of new organisms','sub_topics'=>['Metamorphosis in insects'],
            'sections'=>[['heading'=>'Metamorphosis in insects','subheading'=>null,'content_blocks'=>[
                ['type'=>'paragraph','content'=>str_repeat('Complete metamorphosis is a biological developmental process with egg, larva, pupa and adult stages. The larva feeds and grows, while the pupa undergoes extensive reorganisation before the reproductive adult emerges. This pattern occurs in houseflies, mosquitoes and butterflies and differs from incomplete metamorphosis because a true pupal stage is present. ', 9)],
                ['type'=>'diagram','title'=>'Complete metamorphosis','caption'=>'Life cycle of a completely metamorphosing insect','description'=>'Follow the ordered change from egg to reproductive adult.','labels'=>['egg','larva','pupa','adult']],
            ]]],'evaluation'=>['What is metamorphosis?','Name the stages of complete metamorphosis.','Differentiate complete and incomplete metamorphosis.'],
            'assignment'=>'Draw and label the stages of complete metamorphosis.','reading_assignment'=>'Review metamorphosis and write two insect examples.','source_trace'=>[]];
    }
}
