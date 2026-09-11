package online.educoreng.educore.core.model

data class DeepLinkTarget(val type: String, val id: String?)

data class NotificationItem(
    val id: Long,
    val title: String,
    val body: String,
    val priority: String,
    val publishedAt: String,
    val expiresAt: String?,
    val isRead: Boolean,
    val deepLink: DeepLinkTarget,
)

data class NotificationPage(
    val items: List<NotificationItem>,
    val unreadCount: Int,
    val currentPage: Int,
    val lastPage: Int,
)

data class PlatformNotice(
    val id: Long,
    val title: String,
    val body: String,
    val priority: String,
    val audience: String,
    val isRead: Boolean,
    val createdAt: String?,
    val expiresAt: String?,
)

data class PlatformNoticeFeed(
    val notices: List<PlatformNotice>,
    val unreadCount: Int,
)

data class SchoolEvent(
    val id: Long,
    val title: String,
    val description: String?,
    val startDate: String,
    val endDate: String?,
    val type: String,
    val color: String,
    val isPublic: Boolean,
    val deepLink: DeepLinkTarget,
)

data class MessageAttachment(
    val replyId: Long,
    val name: String,
    val mimeType: String,
    val size: Long?,
)

data class MessageReply(
    val id: Long,
    val body: String,
    val senderId: Long,
    val senderName: String?,
    val isMine: Boolean,
    val createdAt: String,
    val attachment: MessageAttachment?,
)

data class MessageThreadSummary(
    val id: Long,
    val subject: String,
    val status: String,
    val studentName: String?,
    val otherName: String?,
    val lastMessage: String?,
    val unreadCount: Int,
    val updatedAt: String?,
    val deepLink: DeepLinkTarget,
)

data class MessageThread(
    val summary: MessageThreadSummary,
    val replies: List<MessageReply>,
)

data class MessagePage(
    val threads: List<MessageThreadSummary>,
    val unreadCount: Int,
    val currentPage: Int,
    val lastPage: Int,
)

data class MessageRecipient(
    val studentId: Long,
    val name: String,
    val admissionNumber: String,
    val className: String?,
)

data class PendingAttachment(
    val name: String,
    val mimeType: String,
    val bytes: ByteArray,
) {
    override fun equals(other: Any?): Boolean = other is PendingAttachment &&
        name == other.name && mimeType == other.mimeType && bytes.contentEquals(other.bytes)

    override fun hashCode(): Int = 31 * (31 * name.hashCode() + mimeType.hashCode()) + bytes.contentHashCode()
}
