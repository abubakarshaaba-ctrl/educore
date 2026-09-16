<?php

namespace Tests\Feature;

use Tests\TestCase;

class PromotionEngineContractTest extends TestCase
{
    public function test_manual_bulk_promotion_form_matches_controller_contract(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ClassController.php'));
        $view = file_get_contents(resource_path('views/classes/bulk-promote.blade.php'));

        $this->assertStringContainsString("'operations.*.from_class_arm_id'", $controller);
        $this->assertStringContainsString("'operations.*.to_class_arm_id'", $controller);
        $this->assertStringContainsString("'operations.*.term_id'", $controller);
        $this->assertStringContainsString("'operations.*.min_average'", $controller);
        $this->assertStringContainsString("'operations.*.student_ids'       => ['nullable','array']", $controller);

        $this->assertStringContainsString('name="operations[{{ $i }}][from_class_arm_id]"', $view);
        $this->assertStringContainsString('name="operations[{{ $i }}][to_class_arm_id]"', $view);
        $this->assertStringContainsString('name="operations[{{ $i }}][term_id]"', $view);
        $this->assertStringContainsString('name="operations[{{ $i }}][min_average]"', $view);
        $this->assertStringContainsString('name="preview_only"', $view);
    }

    public function test_manual_bulk_promotion_contains_duplicate_and_destination_guards(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ClassController.php'));

        $this->assertStringContainsString('source and destination classes cannot be the same', $controller);
        $this->assertStringContainsString('is a terminal class level', $controller);
        $this->assertStringContainsString('Invalid destination for', $controller);
        $this->assertStringContainsString('lockForUpdate()', $controller);
        $this->assertStringContainsString('StudentEnrollment::updateOrCreate(', $controller);
        $this->assertStringContainsString("'is_current' => false", $controller);
    }

    public function test_grade_scales_support_multiple_class_levels_and_overlap_validation(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ClassController.php'));
        $view = file_get_contents(resource_path('views/classes/grading.blade.php'));

        $this->assertStringContainsString("'class_level_ids'   => ['required','array','min:1']", $controller);
        $this->assertStringContainsString("->where('min_score', '<=', \$data['max_score'])", $controller);
        $this->assertStringContainsString("->where('max_score', '>=', \$data['min_score'])", $controller);
        $this->assertStringContainsString('name="class_level_ids[]"', $view);
        $this->assertStringContainsString('Select all', $view);
        $this->assertStringContainsString('Clear', $view);
    }

    public function test_promotion_rules_support_multiple_class_levels_and_conflict_detection(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ClassController.php'));
        $view = file_get_contents(resource_path('views/classes/promotion.blade.php'));

        $this->assertStringContainsString("PromotionRule::where('class_level_id', \$levelId)->count() > 1", $controller);
        $this->assertStringContainsString("foreach (\$data['class_level_ids'] as \$levelId)", $controller);
        $this->assertStringContainsString('name="class_level_ids[]"', $view);
        $this->assertStringContainsString('Apply rule to multiple class levels', $view);
    }
}
