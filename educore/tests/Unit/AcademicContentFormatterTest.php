<?php

namespace Tests\Unit;

use App\Support\AcademicContentFormatter;
use PHPUnit\Framework\TestCase;

class AcademicContentFormatterTest extends TestCase
{
    public function test_it_formats_plain_text_into_semantic_reader_markup(): void
    {
        $formatter = new AcademicContentFormatter;

        $html = $formatter->render("INTRODUCTION:\n\nThis is **important** content.\n\n- First point\n- Second point\n\n1. First step\n2. Second step");

        $this->assertStringContainsString('<h3>INTRODUCTION</h3>', $html);
        $this->assertStringContainsString('<p>This is <strong>important</strong> content.</p>', $html);
        $this->assertStringContainsString('<ul><li>First point</li><li>Second point</li></ul>', $html);
        $this->assertStringContainsString('<ol><li>First step</li><li>Second step</li></ol>', $html);
    }

    public function test_docx_mode_preserves_single_line_paragraph_boundaries(): void
    {
        $formatter = new AcademicContentFormatter;

        $html = $formatter->render("First paragraph.\nSecond paragraph.\nThird paragraph.", true);

        $this->assertSame(
            "<p>First paragraph.</p>\n<p>Second paragraph.</p>\n<p>Third paragraph.</p>",
            $html
        );
    }

    public function test_it_preserves_supported_rich_markup_but_removes_attributes_and_unsafe_tags(): void
    {
        $formatter = new AcademicContentFormatter;

        $html = $formatter->render('<h2 class="x">Topic</h2><p onclick="alert(1)">Body <strong>text</strong>.</p><script>alert(1)</script>');

        $this->assertSame('<h2>Topic</h2><p>Body <strong>text</strong>.</p>', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('script', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
    }

    public function test_plain_text_is_html_escaped_before_inline_formatting(): void
    {
        $formatter = new AcademicContentFormatter;

        $html = $formatter->render("Normal <img src=x onerror=alert(1)> text\n\n**Bold**");

        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
        $this->assertStringContainsString('<strong>Bold</strong>', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function test_empty_content_returns_a_reader_friendly_fallback(): void
    {
        $formatter = new AcademicContentFormatter;

        $this->assertSame(
            '<p class="reader-empty-paragraph">No readable content is available for this section.</p>',
            $formatter->render('   ')
        );
    }
}
