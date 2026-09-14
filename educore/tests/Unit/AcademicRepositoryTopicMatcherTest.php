<?php

namespace Tests\Unit;

use App\Services\AcademicRepositoryTopicMatcher;
use PHPUnit\Framework\TestCase;

class AcademicRepositoryTopicMatcherTest extends TestCase
{
    public function test_it_normalises_common_topic_variants(): void
    {
        $matcher = new AcademicRepositoryTopicMatcher;

        $this->assertTrue($matcher->isNearDuplicate('Photosynthesis', 'Process of Photosynthesis'));
        $this->assertTrue($matcher->isNearDuplicate('Reproduction in flowering plants', 'Reproduction of Flowering Plants'));
        $this->assertTrue($matcher->isNearDuplicate('Development of new organisms', 'Development in New Organisms'));
    }

    public function test_it_does_not_merge_unrelated_topics(): void
    {
        $matcher = new AcademicRepositoryTopicMatcher;

        $this->assertFalse($matcher->isNearDuplicate('Photosynthesis', 'Nervous coordination'));
        $this->assertFalse($matcher->isNearDuplicate('Fruits and seeds', 'Hormonal coordination'));
    }

    public function test_similarity_is_stable_and_bounded(): void
    {
        $matcher = new AcademicRepositoryTopicMatcher;

        $same = $matcher->similarity('Variation in population', 'Population variation');
        $different = $matcher->similarity('Variation in population', 'Neurone transmission');

        $this->assertGreaterThanOrEqual(0.0, $different);
        $this->assertLessThanOrEqual(1.0, $same);
        $this->assertGreaterThan($different, $same);
    }
}
