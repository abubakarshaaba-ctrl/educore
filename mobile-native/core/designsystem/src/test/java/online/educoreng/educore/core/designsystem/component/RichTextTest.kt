package online.educoreng.educore.core.designsystem.component

import org.junit.Assert.assertEquals
import org.junit.Test

class RichTextTest {
    @Test
    fun plain_text_projection_removes_markdown_markers() {
        val source = """
            # Important Notice
            ## Schedule
            - First item
            1. Numbered item
            **Bold text** and *italic text*
            [EduCore](https://educoreng.online)
        """.trimIndent()

        assertEquals(
            """
                Important Notice
                Schedule
                • First item
                Numbered item
                Bold text and italic text
                EduCore
            """.trimIndent(),
            eduCoreRichTextPlainText(source),
        )
    }
}
