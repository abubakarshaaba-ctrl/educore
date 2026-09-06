package online.educoreng.educore.notification

import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

class NotificationDeepLinkParserTest {
    @Test
    fun `accepts only allow listed destinations with valid identifiers`() {
        val message = NotificationDeepLinkParser.parse(
            mapOf("destination_type" to "message_thread", "destination_id" to "42"),
        )

        assertEquals("message_thread", message?.type)
        assertEquals("42", message?.id)
        assertNull(NotificationDeepLinkParser.parse(mapOf("destination_type" to "message_thread", "destination_id" to "invalid")))
        assertNull(NotificationDeepLinkParser.parse(mapOf("destination_type" to "https://unsafe.example", "destination_id" to "1")))
    }

    @Test
    fun `announcement is valid without an identifier`() {
        val target = NotificationDeepLinkParser.parse(mapOf("destination_type" to "announcement"))

        assertEquals("announcement", target?.type)
        assertNull(target?.id)
    }
}
