package online.educoreng.educore.core.data.repository

import java.io.IOException
import okhttp3.ResponseBody.Companion.toResponseBody
import org.junit.Assert.assertArrayEquals
import org.junit.Assert.assertEquals
import org.junit.Test

class SecureBinaryFilesTest {
    @Test
    fun `bounded reader accepts content within limit`() {
        val content = byteArrayOf(1, 2, 3, 4)
        assertArrayEquals(content, content.toResponseBody().readByteArrayBounded(4))
    }

    @Test(expected = IOException::class)
    fun `bounded reader rejects content larger than limit`() {
        ByteArray(5).toResponseBody().readByteArrayBounded(4)
    }

    @Test
    fun `filename sanitizer removes traversal and unsafe characters`() {
        assertEquals("report_ final.pdf", sanitizeFilename("../private/report? final.pdf"))
        assertEquals("educore-document.bin", sanitizeFilename(".."))
    }
}
