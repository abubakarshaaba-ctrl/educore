<?php

namespace Tests\Unit;

use App\Models\AcademicTopic;
use App\Models\AcademicTopicBlock;
use App\Services\AcademicRepositoryQualityService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class AcademicRepositoryQualityServiceTest extends TestCase
{
    public function test_strong_topic_passes_core_quality_checks(): void
    {
        $topic = new AcademicTopic([
            'introduction' => 'The teacher displays two insect life-cycle charts and asks learners to identify visible differences.',
            'entry_behaviour' => 'Learners have previously observed common insects in their environment.',
            'previous_knowledge' => 'Learners were previously taught reproduction and growth in living organisms.',
            'reference' => 'Senior Secondary Biology, Development of New Organisms.',
        ]);

        $topic->setRelation('blocks', new Collection([
            $this->block('objective', 'Define metamorphosis in insects.', 1),
            $this->block('objective', 'Differentiate complete and incomplete metamorphosis.', 2),
            $this->block('presentation', 'The teacher introduces metamorphosis, explains the meaning and relates the concept to familiar insect life cycles observed by learners.', 1),
            $this->block('presentation', 'The teacher compares complete and incomplete metamorphosis using labelled stages and relevant examples of insects.', 2),
            $this->block('explanation', str_repeat('Metamorphosis is a developmental process in insects involving recognisable changes in body form and life-cycle stages. ', 4), 1),
            $this->block('evaluation', 'What is metamorphosis in insects?', 1),
            $this->block('evaluation', 'Differentiate complete and incomplete metamorphosis.', 2),
            $this->block('assignment', 'List two insects that undergo complete metamorphosis and state their developmental stages.', 1),
        ]));

        $result = (new AcademicRepositoryQualityService)->inspect($topic);

        $this->assertGreaterThanOrEqual(70, $result['score']);
        $this->assertTrue($result['checks']['Measurable objectives']);
        $this->assertTrue($result['checks']['Evaluation aligns with objectives']);
        $this->assertSame([], $result['critical']);
    }

    public function test_vague_objective_is_flagged_as_quality_critical(): void
    {
        $topic = new AcademicTopic([
            'introduction' => 'The teacher introduces the topic with a classroom discussion that connects prior knowledge to the new lesson.',
            'entry_behaviour' => 'Learners have encountered the topic informally in earlier classes.',
            'previous_knowledge' => 'Learners have some background knowledge relevant to the lesson content.',
            'reference' => 'Biology reference material.',
        ]);

        $topic->setRelation('blocks', new Collection([
            $this->block('objective', 'Understand metamorphosis.', 1),
            $this->block('objective', 'Know the stages of insect development.', 2),
            $this->block('presentation', str_repeat('The teacher explains the concept with relevant examples and guided learner responses. ', 2), 1),
            $this->block('presentation', str_repeat('The teacher compares the stages using a chart and checks learner understanding. ', 2), 2),
            $this->block('explanation', str_repeat('Learner-ready explanatory content about the topic and its key concepts. ', 5), 1),
            $this->block('evaluation', 'What is metamorphosis?', 1),
            $this->block('evaluation', 'State the stages of insect development.', 2),
            $this->block('assignment', 'Write short notes on the stages of insect development.', 1),
        ]));

        $result = (new AcademicRepositoryQualityService)->inspect($topic);

        $this->assertFalse($result['checks']['Measurable objectives']);
        $this->assertNotEmpty($result['critical']);
    }

    private function block(string $type, string $content, int $sequence): AcademicTopicBlock
    {
        return new AcademicTopicBlock([
            'block_type' => $type,
            'content' => $content,
            'sequence' => $sequence,
            'is_approved' => true,
        ]);
    }
}
