<?php

namespace Tests\Unit;

use App\Support\EduCoreRichText;
use PHPUnit\Framework\TestCase;

class EduCoreRichTextTest extends TestCase
{
    public function test_plain_text_projection_removes_formatting_markers(): void
    {
        $source = "# Important Notice\n## Schedule\n- First item\n1. Numbered item\n**Bold text** and *italic text*\n[EduCore](https://educoreng.online)";

        $this->assertSame(
            "Important Notice\nSchedule\n• First item\nNumbered item\nBold text and italic text\nEduCore",
            EduCoreRichText::plainText($source),
        );
    }
}
