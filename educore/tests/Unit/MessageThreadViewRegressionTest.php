<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MessageThreadViewRegressionTest extends TestCase
{
    public function test_thread_view_uses_a_safe_nested_class_lookup(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/messages/thread.blade.php');

        $this->assertStringContainsString(
            "data_get(\$thread, 'student.currentClassArm.classLevel.name', 'Not assigned')",
            $view,
        );
        $this->assertStringNotContainsString('->optional(classLevel)', $view);
    }
}
