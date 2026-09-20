package online.educoreng.educore.presentation

import org.junit.Assert.assertEquals
import org.junit.Test

class CbtImageSizingTest {
    @Test
    fun `small diagram is not downsampled`() {
        assertEquals(1, questionImageSampleSize(800, 600, 1280))
    }

    @Test
    fun `large diagram uses power of two sample`() {
        assertEquals(4, questionImageSampleSize(6000, 4000, 1280))
    }

    @Test
    fun `invalid dimensions stay safe`() {
        assertEquals(1, questionImageSampleSize(0, 4000, 1280))
        assertEquals(1, questionImageSampleSize(4000, 4000, 0))
    }
}
