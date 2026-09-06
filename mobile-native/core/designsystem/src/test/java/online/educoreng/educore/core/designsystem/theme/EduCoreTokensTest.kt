package online.educoreng.educore.core.designsystem.theme

import androidx.compose.ui.graphics.Color
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test

class EduCoreTokensTest {
    @Test
    fun spacing_scale_is_monotonic_and_touch_target_is_accessible() {
        val spacing = listOf(
            EduCoreSpacing.Xs,
            EduCoreSpacing.Sm,
            EduCoreSpacing.Md,
            EduCoreSpacing.Lg,
            EduCoreSpacing.Xl,
            EduCoreSpacing.Xxl,
            EduCoreSpacing.Xxxl,
        ).map { it.value }

        assertEquals(spacing.sorted(), spacing)
        assertTrue(EduCoreSizes.TouchTarget.value >= 48f)
    }

    @Test
    fun institutional_brand_tokens_are_stable() {
        assertEquals(Color(0xFF071E45), EduCoreColors.Navy900)
        assertEquals(Color(0xFFD79A21), EduCoreColors.Gold600)
    }
}
