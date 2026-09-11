package online.educoreng.educore.core.data.repository

import android.content.Context
import com.squareup.moshi.Moshi
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody
import online.educoreng.educore.core.common.AppError
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.DownloadedDocument
import online.educoreng.educore.core.model.MessageAttachment
import online.educoreng.educore.core.model.PendingAttachment
import online.educoreng.educore.core.model.PlatformNotice
import online.educoreng.educore.core.model.PlatformNoticeFeed
import online.educoreng.educore.core.network.EduCoreApi
import online.educoreng.educore.core.network.dto.CreateEventRequestDto
import online.educoreng.educore.core.network.dto.PushTokenRequestDto
import online.educoreng.educore.core.network.dto.toDomain
import online.educoreng.educore.core.network.dto.toThread
import online.educoreng.educore.core.network.safeApiCall

class DefaultCommunicationRepository(
    private val context: Context,
    private val api: EduCoreApi,
    private val moshi: Moshi,
) : CommunicationRepository {
    override suspend fun notifications(status: String) = mapped { api.notifications(status = status).toDomain() }
    override suspend fun markNotificationRead(id: Long) = mapped { api.markNotificationRead(id).notification.toDomain() }
    override suspend fun markAllNotificationsRead() = mapped { api.markAllNotificationsRead().updated }

    override suspend fun platformNotices() = mapped {
        val response = api.platformNotices()
        PlatformNoticeFeed(
            notices = response.notices.map { notice ->
                PlatformNotice(
                    id = notice.id,
                    title = notice.title,
                    body = notice.body,
                    priority = notice.priority,
                    audience = notice.audience,
                    isRead = notice.isRead,
                    createdAt = notice.createdAt,
                    expiresAt = notice.expiresAt,
                )
            },
            unreadCount = response.unreadCount,
        )
    }

    override suspend fun markPlatformNoticeRead(id: Long) = unitCall { api.markPlatformNoticeRead(id) }
    override suspend fun dismissPlatformNotice(id: Long) = unitCall { api.dismissPlatformNotice(id) }

    override suspend fun events(from: String?, to: String?) = mapped { api.communicationEvents(from, to).events.map { it.toDomain() } }
    override suspend fun createEvent(title: String, description: String?, startDate: String, endDate: String?, audience: String) = mapped {
        api.createCommunicationEvent(
            CreateEventRequestDto(
                title = title,
                description = description,
                startDate = startDate,
                endDate = endDate,
                audience = audience,
            ),
        ).event.toDomain()
    }
    override suspend fun messages() = mapped { api.messages(perPage = 50).toDomain() }
    override suspend fun recipients() = mapped { api.messageRecipients().recipients.map { it.toDomain() } }
    override suspend fun thread(id: Long) = mapped { api.messageThread(id).thread.toThread() }

    override suspend fun compose(studentId: Long, subject: String, body: String, attachment: PendingAttachment?) = mapped {
        val response = api.composeMessage(
            studentId.toString().textPart(), subject.textPart(), body.textPart(), attachment?.part(),
        )
        requireNotNull(response.thread).toThread()
    }

    override suspend fun reply(threadId: Long, body: String, attachment: PendingAttachment?) = mapped {
        requireNotNull(api.replyMessage(threadId, body.textPart(), attachment?.part()).reply).toDomain()
    }

    override suspend fun download(attachment: MessageAttachment): AppResult<DownloadedDocument> = withContext(Dispatchers.IO) {
        when (val result = safeApiCall(moshi) { api.downloadMessageAttachment(attachment.replyId) }) {
            is AppResult.Failure -> result
            is AppResult.Success -> runCatching {
                saveDownloadedDocument(context, result.value, attachment.name, attachment.mimeType)
            }.fold({ AppResult.Success(it) }, { AppResult.Failure(AppError.Unexpected("The attachment could not be saved.", it)) })
        }
    }

    override suspend fun registerPushToken(token: String) = unitCall { api.registerPushToken(PushTokenRequestDto(token)) }
    override suspend fun unregisterPushToken(token: String) = unitCall { api.unregisterPushToken(PushTokenRequestDto(token)) }

    private suspend fun <T> mapped(block: suspend () -> T): AppResult<T> = withContext(Dispatchers.IO) { safeApiCall(moshi, block) }
    private suspend fun unitCall(block: suspend () -> Any): AppResult<Unit> = withContext(Dispatchers.IO) {
        when (val result = safeApiCall(moshi, block)) {
            is AppResult.Success -> AppResult.Success(Unit)
            is AppResult.Failure -> result
        }
    }
    private fun String.textPart() = toRequestBody("text/plain".toMediaType())
    private fun PendingAttachment.part() = MultipartBody.Part.createFormData(
        "attachment", name, bytes.toRequestBody(mimeType.toMediaType()),
    )
}
