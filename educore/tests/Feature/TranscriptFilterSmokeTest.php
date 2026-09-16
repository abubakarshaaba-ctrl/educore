<?php

namespace Tests\Feature;

use Tests\TestCase;

class TranscriptFilterSmokeTest extends TestCase
{
    public function test_transcript_filter_view_contains_requested_filters(): void
    {
        $view = file_get_contents(resource_path('views/students/transcript-index.blade.php'));

        $this->assertStringContainsString('name="graduation_year"', $view);
        $this->assertStringContainsString('name="class_arm_id"', $view);
        $this->assertStringContainsString("whereYear('graduation_date'", $view);
        $this->assertStringContainsString("whereHas('enrollments'", $view);
    }
}
