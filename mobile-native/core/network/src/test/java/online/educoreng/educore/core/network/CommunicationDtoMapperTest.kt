package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.CommunicationMetaDto
import online.educoreng.educore.core.network.dto.DeepLinkDto
import online.educoreng.educore.core.network.dto.MessageAttachmentDto
import online.educoreng.educore.core.network.dto.MessageReplyDto
import online.educoreng.educore.core.network.dto.MessageThreadSummaryDto
import online.educoreng.educore.core.network.dto.NotificationItemDto
import online.educoreng.educore.core.network.dto.NotificationsResponseDto
import online.educoreng.educore.core.network.dto.toDomain
import online.educoreng.educore.core.network.dto.toThread
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class CommunicationDtoMapperTest {
    @Test
    fun `notification mapper preserves unread state and safe destination`() {
        val page = NotificationsResponseDto(
            contractVersion = 1,
            notifications = listOf(
                NotificationItemDto(8, "PTA meeting", "Friday at noon", "important", "2026-08-31", null, false, DeepLinkDto("announcement", "8")),
            ),
            unreadCount = 1,
            meta = CommunicationMetaDto(1, 2),
        ).toDomain()

        assertEquals(1, page.unreadCount)
        assertEquals(2, page.lastPage)
        assertFalse(page.items.single().isRead)
        assertEquals("announcement", page.items.single().deepLink.type)
    }

    @Test
    fun `thread mapper keeps complete replies and attachment ownership`() {
        val thread = MessageThreadSummaryDto(
            id = 14,
            subject = "Continuous assessment",
            status = "open",
            studentName = "Amina Bello",
            otherName = "Parent",
            lastMessage = "Please check the score.",
            unreadCount = 1,
            updatedAt = "2026-08-31T10:00:00+01:00",
            deepLink = DeepLinkDto("message_thread", "14"),
            replies = listOf(
                MessageReplyDto(
                    id = 21,
                    body = "Attached is the supporting document.",
                    senderId = 5,
                    senderName = "Teacher",
                    isMine = true,
                    createdAt = "2026-08-31T10:00:00+01:00",
                    attachment = MessageAttachmentDto("evidence.pdf", "application/pdf", 1200, "/messages/replies/21/attachment"),
                ),
            ),
        ).toThread()

        assertEquals(14, thread.summary.id)
        assertTrue(thread.replies.single().isMine)
        assertEquals(21L, thread.replies.single().attachment?.replyId)
        assertEquals("evidence.pdf", thread.replies.single().attachment?.name)
    }
}
