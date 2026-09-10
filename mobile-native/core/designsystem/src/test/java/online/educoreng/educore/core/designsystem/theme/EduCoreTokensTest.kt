package online.educoreng.educore.core.designsystem.theme

import androidx.compose.ui.graphics.Color
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotEquals
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
        assertEquals(Color(0xFF0B2D63), EduCoreColors.Navy800)
        assertEquals(Color(0xFF15447F), EduCoreColors.Navy700)

        // Gold is a first-class EduCore brand colour. It must never regress
        // to the old blue aliases because the wordmark contract is:
        // dark/navy surfaces -> Edu white, Core gold.
        assertEquals(Color(0xFF855800), EduCoreColors.Gold700)
        assertEquals(Color(0xFFA36A00), EduCoreColors.Gold600)
        assertEquals(Color(0xFFD09100), EduCoreColors.Gold500)
        assertEquals(Color(0xFFF1B947), EduCoreColors.Gold400)
        assertNotEquals(EduCoreColors.Navy800, EduCoreColors.Gold600)
        assertNotEquals(EduCoreColors.Navy700, EduCoreColors.Gold400)
    }
}
