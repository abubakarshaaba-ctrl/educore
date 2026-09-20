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

    @Test fun `very small and tablet widths retain usable margins`() {
        assertEquals(8.dp, eduCoreHorizontalPadding(280))
        assertEquals(12.dp, eduCoreHorizontalPadding(359))
        assertEquals(16.dp, eduCoreHorizontalPadding(599))
        assertEquals(20.dp, eduCoreHorizontalPadding(839))
        assertEquals(24.dp, eduCoreHorizontalPadding(840))
        assertEquals(24.dp, eduCoreHorizontalPadding(1600))
    }

    @Test fun `grid cells grow with available device width`() {
        assertEquals(150.dp, eduCoreGridMinCellWidth(EduCoreWindowWidth.Compact))
        assertEquals(176.dp, eduCoreGridMinCellWidth(EduCoreWindowWidth.Medium))
        assertEquals(208.dp, eduCoreGridMinCellWidth(EduCoreWindowWidth.Expanded))
    }
}
