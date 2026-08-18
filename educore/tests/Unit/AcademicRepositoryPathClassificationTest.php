<?php

namespace Tests\Unit;

use App\Services\Curriculum\AcademicRepositoryIngestionService;
use PHPUnit\Framework\TestCase;

class AcademicRepositoryPathClassificationTest extends TestCase
{
    private AcademicRepositoryIngestionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AcademicRepositoryIngestionService();
    }

    public function test_subject_first_archive_path_is_normalised_to_class_subject_term(): void
    {
        $result = $this->service->classifyPath(
            'Agricultural Science/JSS 1- 2nd Term/001 - Week 2- Classification Of Crops.docx'
        );

        $this->assertSame('JSS 1', $result['class_label']);
        $this->assertSame('Agricultural Science', $result['subject_label']);
        $this->assertSame('Second Term', $result['term_label']);
        $this->assertSame(2, $result['week_number']);
        $this->assertSame('Classification Of Crops', $result['title']);
        $this->assertSame('jss-1/agricultural-science/second-term', $result['storage_hierarchy']);
    }

    public function test_class_first_archive_path_reads_subject_and_term_from_filename(): void
    {
        $result = $this->service->classifyPath(
            'Lesson_Notes/Primary 5/Phe/021 - Term 3- Week 3-MARTIAL ARTS – JUDO.docx'
        );

        $this->assertSame('Primary 5', $result['class_label']);
        $this->assertSame('PHE', $result['subject_label']);
        $this->assertSame('Third Term', $result['term_label']);
        $this->assertSame('primary-5/phe/third-term', $result['storage_hierarchy']);
    }

    public function test_wrapper_folder_and_sss_alias_are_handled(): void
    {
        $result = $this->service->classifyPath(
            'Lesson_Notes/Yoruba/SSS 1- Third Term/011 - Week 10-LETA GBEFE KIKO.docx'
        );

        $this->assertSame('SS 1', $result['class_label']);
        $this->assertSame('Yoruba', $result['subject_label']);
        $this->assertSame('Third Term', $result['term_label']);
        $this->assertSame('Lesson_Notes/Yoruba/SSS 1- Third Term/011 - Week 10-LETA GBEFE KIKO.docx', $result['original_path']);
    }
}
