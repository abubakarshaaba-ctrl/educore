<?php

namespace Tests\Unit;

use App\Models\AcademicTopic;
use App\Models\CurriculumSource;
use App\Services\AcademicRepositorySourcePriorityService;
use PHPUnit\Framework\TestCase;

class AcademicRepositorySourcePriorityServiceTest extends TestCase
{
    public function test_curriculum_outranks_lesson_note(): void
    {
        $service = new AcademicRepositorySourcePriorityService;

        $curriculum = new AcademicTopic(['resource_type' => 'curriculum']);
        $curriculum->setRelation('source', new CurriculumSource(['is_official' => true, 'is_active' => true]));

        $lessonNote = new AcademicTopic(['resource_type' => 'lesson_note']);
        $lessonNote->setRelation('source', new CurriculumSource(['is_official' => false, 'is_active' => true]));

        $this->assertGreaterThan($service->score($lessonNote), $service->score($curriculum));
    }

    public function test_official_source_gets_small_bonus_without_overriding_resource_class(): void
    {
        $service = new AcademicRepositorySourcePriorityService;

        $officialLessonNote = new AcademicTopic(['resource_type' => 'lesson_note']);
        $officialLessonNote->setRelation('source', new CurriculumSource(['is_official' => true, 'is_active' => true]));

        $syllabus = new AcademicTopic(['resource_type' => 'syllabus']);
        $syllabus->setRelation('source', new CurriculumSource(['is_official' => false, 'is_active' => true]));

        $this->assertGreaterThan($service->score($officialLessonNote), $service->score($syllabus));
    }
}
