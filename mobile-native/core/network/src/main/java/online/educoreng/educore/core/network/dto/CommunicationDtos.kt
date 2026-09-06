package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import online.educoreng.educore.core.model.DeepLinkTarget
import online.educoreng.educore.core.model.MessageAttachment
import online.educoreng.educore.core.model.MessagePage
import online.educoreng.educore.core.model.MessageRecipient
import online.educoreng.educore.core.model.MessageReply
import online.educoreng.educore.core.model.MessageThread
import online.educoreng.educore.core.model.MessageThreadSummary
import online.educoreng.educore.core.model.NotificationItem
import online.educoreng.educore.core.model.NotificationPage
import online.educoreng.educore.core.model.SchoolEvent

data class DeepLinkDto(val type: String, val id: String? = null)
data class CommunicationMetaDto(
    @param:Json(name = "current_page") val currentPage: Int = 1,
    @param:Json(name = "last_page") val lastPage: Int = 1,
)

data class NotificationItemDto(
    val id: Long,
    val title: String,
    val body: String,
    val priority: String,
    @param:Json(name = "published_at") val publishedAt: String,
    @param:Json(name = "expires_at") val expiresAt: String? = null,
    @param:Json(name = "is_read") val isRead: Boolean,
    @param:Json(name = "deep_link") val deepLink: DeepLinkDto,
)
data class NotificationsResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val notifications: List<NotificationItemDto> = emptyList(),
    @param:Json(name = "unread_count") val unreadCount: Int,
    val meta: CommunicationMetaDto,
)
data class NotificationResponseDto(val notification: NotificationItemDto)
data class ReadAllResponseDto(val message: String, val updated: Int)

data class SchoolEventDto(
    val id: Long,
    val title: String,
    val description: String? = null,
    @param:Json(name = "start_date") val startDate: String,
    @param:Json(name = "end_date") val endDate: String? = null,
    val type: String,
    val color: String,
    @param:Json(name = "is_public") val isPublic: Boolean,
    @param:Json(name = "deep_link") val deepLink: DeepLinkDto,
)
data class EventsResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val events: List<SchoolEventDto> = emptyList(),
    @param:Json(name = "generated_at") val generatedAt: String,
)

data class MessageAttachmentDto(
    val name: String,
    @param:Json(name = "mime_type") val mimeType: String? = null,
    val size: Long? = null,
    @param:Json(name = "download_path") val downloadPath: String,
)
data class MessageReplyDto(
    val id: Long,
    val body: String,
    @param:Json(name = "sender_id") val senderId: Long,
    @param:Json(name = "sender_name") val senderName: String? = null,
    @param:Json(name = "is_me") val isMine: Boolean,
    @param:Json(name = "created_at") val createdAt: String,
    val attachment: MessageAttachmentDto? = null,
)
data class MessageThreadSummaryDto(
    val id: Long,
    val subject: String,
    val status: String,
    @param:Json(name = "student_name") val studentName: String? = null,
    @param:Json(name = "other_name") val otherName: String? = null,
    @param:Json(name = "last_message") val lastMessage: String? = null,
    @param:Json(name = "unread_count") val unreadCount: Int = 0,
    @param:Json(name = "updated_at") val updatedAt: String? = null,
    @param:Json(name = "deep_link") val deepLink: DeepLinkDto,
    val replies: List<MessageReplyDto> = emptyList(),
)
data class MessagesResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val threads: List<MessageThreadSummaryDto> = emptyList(),
    @param:Json(name = "unread_total") val unreadTotal: Int,
    val meta: CommunicationMetaDto,
)
data class MessageThreadResponseDto(val thread: MessageThreadSummaryDto)
data class MessageMutationResponseDto(val thread: MessageThreadSummaryDto? = null, val reply: MessageReplyDto? = null)
data class MessageRecipientDto(
    @param:Json(name = "student_id") val studentId: Long,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String,
    @param:Json(name = "class_name") val className: String? = null,
)
data class MessageRecipientsResponseDto(val recipients: List<MessageRecipientDto> = emptyList())
data class PushTokenRequestDto(val token: String, val platform: String = "android")

fun DeepLinkDto.toDomain() = DeepLinkTarget(type, id)
fun NotificationItemDto.toDomain() = NotificationItem(id, title, body, priority, publishedAt, expiresAt, isRead, deepLink.toDomain())
fun NotificationsResponseDto.toDomain() = NotificationPage(notifications.map(NotificationItemDto::toDomain), unreadCount, meta.currentPage, meta.lastPage)
fun SchoolEventDto.toDomain() = SchoolEvent(id, title, description, startDate, endDate, type, color, isPublic, deepLink.toDomain())
fun MessageThreadSummaryDto.toDomain() = MessageThreadSummary(id, subject, status, studentName, otherName, lastMessage, unreadCount, updatedAt, deepLink.toDomain())
fun MessageReplyDto.toDomain() = MessageReply(id, body, senderId, senderName, isMine, createdAt, attachment?.let { MessageAttachment(id, it.name, it.mimeType ?: "application/octet-stream", it.size) })
fun MessageThreadSummaryDto.toThread() = MessageThread(toDomain(), replies.map(MessageReplyDto::toDomain))
fun MessagesResponseDto.toDomain() = MessagePage(threads.map(MessageThreadSummaryDto::toDomain), unreadTotal, meta.currentPage, meta.lastPage)
fun MessageRecipientDto.toDomain() = MessageRecipient(studentId, name, admissionNumber, className)
