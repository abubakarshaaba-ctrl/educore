package online.educoreng.educore.core.data.repository

import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.DownloadedDocument
import online.educoreng.educore.core.model.MessageAttachment
import online.educoreng.educore.core.model.MessagePage
import online.educoreng.educore.core.model.MessageRecipient
import online.educoreng.educore.core.model.MessageReply
import online.educoreng.educore.core.model.MessageThread
import online.educoreng.educore.core.model.NotificationItem
import online.educoreng.educore.core.model.NotificationPage
import online.educoreng.educore.core.model.PendingAttachment
import online.educoreng.educore.core.model.PlatformNoticeFeed
import online.educoreng.educore.core.model.SchoolEvent

interface CommunicationRepository {
    suspend fun notifications(status: String = "all"): AppResult<NotificationPage>
    suspend fun markNotificationRead(id: Long): AppResult<NotificationItem>
    suspend fun markAllNotificationsRead(): AppResult<Int>
    suspend fun platformNotices(): AppResult<PlatformNoticeFeed>
    suspend fun markPlatformNoticeRead(id: Long): AppResult<Unit>
    suspend fun dismissPlatformNotice(id: Long): AppResult<Unit>
    suspend fun events(from: String? = null, to: String? = null): AppResult<List<SchoolEvent>>
    suspend fun createEvent(title: String, description: String?, startDate: String, endDate: String?, audience: String): AppResult<SchoolEvent>
    suspend fun messages(): AppResult<MessagePage>
    suspend fun recipients(): AppResult<List<MessageRecipient>>
    suspend fun thread(id: Long): AppResult<MessageThread>
    suspend fun compose(studentId: Long, subject: String, body: String, attachment: PendingAttachment?): AppResult<MessageThread>
    suspend fun reply(threadId: Long, body: String, attachment: PendingAttachment?): AppResult<MessageReply>
    suspend fun download(attachment: MessageAttachment): AppResult<DownloadedDocument>
    suspend fun registerPushToken(token: String): AppResult<Unit>
    suspend fun unregisterPushToken(token: String): AppResult<Unit>
}
