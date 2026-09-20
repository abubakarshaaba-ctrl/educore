<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ParallelCurriculumExperienceTest extends TestCase
{
    public function test_parallel_teacher_workspaces_and_reporting_routes_are_registered(): void
    {
        foreach ([
            'parallel-curriculum.attendance.index',
            'parallel-curriculum.skills.index',
            'parallel-curriculum.skills.save',
            'parallel-curriculum.results.broadsheet',
            'parallel-curriculum.results.broadsheet.pdf',
            'parallel-curriculum.results.student.cumulative',
            'parallel-curriculum.results.student.cumulative.pdf',
            'scores.cumulative-broadsheet',
            'scores.cumulative-broadsheet.pdf',
        ] as $routeName) {
            $this->assertTrue(
                Route::has($routeName),
                "Missing route: {$routeName}"
            );
        }
    }

    public function test_parallel_result_title_uses_programme_name(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/ParallelCurriculumResultController.php')
        );

        $this->assertStringContainsString(
            "\$programmeName.' Student Termly Performance Report'",
            $controller
        );
        $this->assertStringContainsString(
            "\$programmeName.' Cumulative Student Performance Report'",
            $controller
        );
    }

    public function test_parallel_skills_are_independent_from_conventional_ratings(): void
    {
        $migration = file_get_contents(
            database_path(
                'migrations/2026_09_20_120000_create_parallel_curriculum_skill_ratings.php'
            )
        );
        $service = file_get_contents(
            app_path('Services/ParallelCurriculumResultService.php')
        );

        $this->assertStringContainsString(
            "Schema::create('parallel_curriculum_skill_ratings'",
            $migration
        );
        $this->assertStringContainsString(
            "ParallelCurriculumSkillRating::withoutTenantScope()",
            $service
        );
        $this->assertStringNotContainsString(
            'StudentSkillRating::withoutTenantScope()',
            $service
        );
    }

    public function test_parallel_attendance_has_a_focused_teacher_workspace(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/ParallelCurriculumOperationsController.php')
        );
        $view = file_get_contents(
            resource_path('views/parallel-curriculum/operations.blade.php')
        );

        $this->assertStringContainsString(
            'public function attendance(Request $request)',
            $controller
        );
        $this->assertStringContainsString(
            "'attendanceOnly' => \$attendanceOnly",
            $controller
        );
        $this->assertStringContainsString(
            'Parallel Student Attendance',
            $view
        );
        $this->assertStringContainsString(
            'Load Attendance Register',
            $view
        );
    }

    public function test_termly_and_cumulative_broadsheets_are_print_ready(): void
    {
        $parallel = file_get_contents(
            resource_path(
                'views/parallel-curriculum/results/broadsheet.blade.php'
            )
        );
        $parallelPdf = file_get_contents(
            resource_path(
                'views/parallel-curriculum/results/broadsheet-pdf.blade.php'
            )
        );
        $conventional = file_get_contents(
            resource_path('views/scores/cumulative-broadsheet.blade.php')
        );
        $conventionalPdf = file_get_contents(
            resource_path('views/scores/cumulative-broadsheet-pdf.blade.php')
        );

        $this->assertStringContainsString('@media print', $parallel);
        $this->assertStringContainsString('Cumulative Broadsheet', $parallel);
        $this->assertStringContainsString('CUMULATIVE', $parallelPdf);

        $this->assertStringContainsString('@media print', $conventional);
        $this->assertStringContainsString(
            'Conventional Curriculum Cumulative Broadsheet',
            $conventional
        );
        $this->assertStringContainsString(
            'CUMULATIVE CLASS BROADSHEET',
            $conventionalPdf
        );
    }
}
