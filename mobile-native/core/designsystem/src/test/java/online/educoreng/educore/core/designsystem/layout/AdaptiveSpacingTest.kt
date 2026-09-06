package online.educoreng.educore.core.designsystem.layout

import androidx.compose.ui.unit.dp
import org.junit.Assert.assertEquals
import org.junit.Test

class AdaptiveSpacingTest {
    @Test fun `screen margins scale without wasting compact width`() {
        assertEquals(12.dp, eduCoreHorizontalPadding(320))
        assertEquals(16.dp, eduCoreHorizontalPadding(480))
        assertEquals(20.dp, eduCoreHorizontalPadding(700))
        assertEquals(24.dp, eduCoreHorizontalPadding(1024))
    }
}
