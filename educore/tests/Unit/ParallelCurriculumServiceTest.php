<?php

namespace Tests\Unit;

use App\Models\AssessmentType;
use App\Services\ParallelCurriculumService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class ParallelCurriculumServiceTest extends TestCase
{
    public function test_composite_average_is_distributed_by_conventional_template_weights(): void
    {
        $ca = new AssessmentType(['name' => 'CA', 'weight_percentage' => 30]);
        $ca->setAttribute('id', 101);

        $exam = new AssessmentType(['name' => 'Exam', 'weight_percentage' => 70]);
        $exam->setAttribute('id', 102);

        $distribution = (new ParallelCurriculumService())->distributeScore(
            78.0,
            new Collection([$ca, $exam])
        );

        $this->assertSame(23.4, $distribution[101]);
        $this->assertSame(54.6, $distribution[102]);
        $this->assertSame(78.0, round(array_sum($distribution), 2));
    }

    public function test_distribution_normalizes_non_one_hundred_runtime_weights(): void
    {
        $first = new AssessmentType(['name' => 'First', 'weight_percentage' => 20]);
        $first->setAttribute('id', 1);

        $second = new AssessmentType(['name' => 'Second', 'weight_percentage' => 30]);
        $second->setAttribute('id', 2);

        $distribution = (new ParallelCurriculumService())->distributeScore(
            80.0,
            new Collection([$first, $second])
        );

        $this->assertSame(32.0, $distribution[1]);
        $this->assertSame(48.0, $distribution[2]);
        $this->assertSame(80.0, round(array_sum($distribution), 2));
    }
}
