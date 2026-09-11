package online.educoreng.educore.presentation

import android.content.Context
import android.net.Uri
import android.provider.OpenableColumns
import java.io.ByteArrayOutputStream
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import dagger.hilt.android.qualifiers.ApplicationContext
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Locale
import javax.inject.Inject
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.data.repository.CommunicationRepository
import online.educoreng.educore.core.model.DownloadedDocument
import online.educoreng.educore.core.model.MessagePage
import online.educoreng.educore.core.model.MessageRecipient
import online.educoreng.educore.core.model.MessageThread
import online.educoreng.educore.core.model.NotificationItem
import online.educoreng.educore.core.model.PendingAttachment
import online.educoreng.educore.core.model.PlatformNotice
import online.educoreng.educore.core.model.SchoolEvent

enum class CommunicationTab(val label: String) { NOTICES("Notices"), MESSAGES("Messages"), EVENTS("Events") }

data class CommunicationUiState(
    val selectedTab: CommunicationTab = CommunicationTab.NOTICES,
    val noticeFilter: String = "all",
    val notifications: List<NotificationItem> = emptyList(),
    val unreadNotifications: Int = 0,
    val platformNotices: List<PlatformNotice> = emptyList(),
    val platformUnreadNotifications: Int = 0,
    val messagePage: MessagePage? = null,
    val events: List<SchoolEvent> = emptyList(),
    val thread: MessageThread? = null,
    val recipients: List<MessageRecipient> = emptyList(),
    val selectedRecipientId: Long? = null,
    val composeSubject: String = "",
    val composeBody: String = "",
    val replyBody: String = "",
    val eventTitle: String = "",
    val eventDescription: String = "",
    val eventStartDate: String = "",
    val eventEndDate: String = "",
    val eventAudience: String = "all",
    val attachment: PendingAttachment? = null,
    val downloadedDocument: DownloadedDocument? = null,
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val totalUnreadNotices: Int get() = unreadNotifications + platformUnreadNotifications
}

@HiltViewModel
class CommunicationViewModel @Inject constructor(
    @param:ApplicationContext private val context: Context,
    private val repository: CommunicationRepository,
) : ViewModel() {
    private val _uiState = MutableStateFlow(CommunicationUiState())
    val uiState: StateFlow<CommunicationUiState> = _uiState.asStateFlow()

    fun loadAll() {
        loadNotifications()
        loadPlatformNotices()
        loadMessages()
        loadEvents()
    }

    fun selectTab(index: Int) {
        val tab = CommunicationTab.entries.getOrElse(index) { CommunicationTab.NOTICES }
        _uiState.update { it.copy(selectedTab = tab, errorMessage = null) }
        when (tab) {
            CommunicationTab.NOTICES -> {
                loadNotifications()
                loadPlatformNotices()
            }
            CommunicationTab.MESSAGES -> loadMessages()
            CommunicationTab.EVENTS -> loadEvents()
        }
    }

    fun setNoticeFilter(filter: String) {
        _uiState.update { it.copy(noticeFilter = filter) }
        loadNotifications()
        loadPlatformNotices()
    }

    fun loadNotifications() = viewModelScope.launch {
        beginLoading()
        when (val result = repository.notifications(_uiState.value.noticeFilter)) {
            is AppResult.Success -> _uiState.update {
                it.copy(notifications = result.value.items, unreadNotifications = result.value.unreadCount, isLoading = false)
            }
            is AppResult.Failure -> failLoading(result.error.userMessage)
        }
    }

    fun loadPlatformNotices() = viewModelScope.launch {
        when (val result = repository.platformNotices()) {
            is AppResult.Success -> _uiState.update { state ->
                val notices = when (state.noticeFilter) {
                    "unread" -> result.value.notices.filterNot { it.isRead }
                    "read" -> result.value.notices.filter { it.isRead }
                    else -> result.value.notices
                }
                state.copy(platformNotices = notices, platformUnreadNotifications = result.value.unreadCount)
            }
            is AppResult.Failure -> {
                if (_uiState.value.notifications.isEmpty()) fail(result.error.userMessage)
            }
        }
    }

    fun markRead(id: Long) = viewModelScope.launch {
        when (val result = repository.markNotificationRead(id)) {
            is AppResult.Success -> _uiState.update { state ->
                state.copy(
                    notifications = if (state.noticeFilter == "unread") {
                        state.notifications.filterNot { it.id == id }
                    } else {
                        state.notifications.map { if (it.id == id) result.value else it }
                    },
                    unreadNotifications = (state.unreadNotifications - 1).coerceAtLeast(0),
                )
            }
            is AppResult.Failure -> fail(result.error.userMessage)
        }
    }

    fun markPlatformRead(id: Long) = viewModelScope.launch {
        when (val result = repository.markPlatformNoticeRead(id)) {
            is AppResult.Success -> _uiState.update { state ->
                state.copy(
                    platformNotices = if (state.noticeFilter == "unread") {
                        state.platformNotices.filterNot { it.id == id }
                    } else {
                        state.platformNotices.map { if (it.id == id) it.copy(isRead = true) else it }
                    },
                    platformUnreadNotifications = (state.platformUnreadNotifications - 1).coerceAtLeast(0),
                )
            }
            is AppResult.Failure -> fail(result.error.userMessage)
        }
    }

    fun dismissPlatformNotice(id: Long) = viewModelScope.launch {
        _uiState.update { it.copy(isSaving = true, errorMessage = null) }
        when (val result = repository.dismissPlatformNotice(id)) {
            is AppResult.Success -> _uiState.update { state ->
                val dismissed = state.platformNotices.firstOrNull { it.id == id }
                state.copy(
                    platformNotices = state.platformNotices.filterNot { it.id == id },
                    platformUnreadNotifications = if (dismissed?.isRead == false) {
                        (state.platformUnreadNotifications - 1).coerceAtLeast(0)
                    } else state.platformUnreadNotifications,
                    isSaving = false,
                    message = "Platform notice dismissed.",
                )
            }
            is AppResult.Failure -> _uiState.update { it.copy(isSaving = false, errorMessage = result.error.userMessage) }
        }
    }

    fun markAllRead() = viewModelScope.launch {
        _uiState.update { it.copy(isSaving = true, errorMessage = null) }
        when (val result = repository.markAllNotificationsRead()) {
            is AppResult.Success -> _uiState.update { state ->
                state.copy(
                    notifications = if (state.noticeFilter == "unread") emptyList() else state.notifications.map { it.copy(isRead = true) },
                    unreadNotifications = 0,
                    isSaving = false,
                    message = "${result.value} school notification(s) marked as read.",
                )
            }
            is AppResult.Failure -> _uiState.update { it.copy(isSaving = false, errorMessage = result.error.userMessage) }
        }
    }

    fun loadMessages() = viewModelScope.launch {
        beginLoading()
        when (val result = repository.messages()) {
            is AppResult.Success -> _uiState.update { it.copy(messagePage = result.value, isLoading = false) }
            is AppResult.Failure -> failLoading(result.error.userMessage)
        }
    }

    fun openThread(id: Long) = viewModelScope.launch {
        _uiState.update { it.copy(isLoading = true, thread = null, errorMessage = null, attachment = null, replyBody = "") }
        when (val result = repository.thread(id)) {
            is AppResult.Success -> {
                val refreshedPage = when (val messages = repository.messages()) {
                    is AppResult.Success -> messages.value
                    is AppResult.Failure -> _uiState.value.messagePage
                }
                _uiState.update { it.copy(thread = result.value, messagePage = refreshedPage, isLoading = false) }
            }
            is AppResult.Failure -> failLoading(result.error.userMessage)
        }
    }

    fun prepareCompose() = viewModelScope.launch {
        _uiState.update {
            it.copy(isLoading = true, recipients = emptyList(), selectedRecipientId = null, composeSubject = "", composeBody = "", attachment = null, thread = null, errorMessage = null)
        }
        when (val result = repository.recipients()) {
            is AppResult.Success -> _uiState.update { state ->
                state.copy(recipients = result.value, selectedRecipientId = result.value.singleOrNull()?.studentId, isLoading = false)
            }
            is AppResult.Failure -> failLoading(result.error.userMessage)
        }
    }

    fun selectRecipient(id: Long) = _uiState.update { it.copy(selectedRecipientId = id) }
    fun setComposeSubject(value: String) = _uiState.update { it.copy(composeSubject = value.take(150)) }
    fun setComposeBody(value: String) = _uiState.update { it.copy(composeBody = value.take(10000)) }
    fun setReplyBody(value: String) = _uiState.update { it.copy(replyBody = value.take(10000)) }
    fun clearAttachment() = _uiState.update { it.copy(attachment = null) }

    fun setEventTitle(value: String) = _uiState.update { it.copy(eventTitle = value.take(180)) }
    fun setEventDescription(value: String) = _uiState.update { it.copy(eventDescription = value.take(5000)) }
    fun setEventStartDate(value: String) = _uiState.update { it.copy(eventStartDate = value) }
    fun setEventEndDate(value: String) = _uiState.update { it.copy(eventEndDate = value) }
    fun setEventAudience(value: String) = _uiState.update { it.copy(eventAudience = value) }

    fun loadAttachment(uriValue: String) = viewModelScope.launch {
        _uiState.update { it.copy(isSaving = true, errorMessage = null) }
        val attachment = withContext(Dispatchers.IO) { readAttachment(Uri.parse(uriValue)) }
        attachment.fold(
            onSuccess = { file -> _uiState.update { it.copy(attachment = file, isSaving = false) } },
            onFailure = { error -> _uiState.update { it.copy(isSaving = false, errorMessage = error.message ?: "The attachment could not be opened.") } },
        )
    }

    fun compose() = viewModelScope.launch {
        val state = _uiState.value
        val recipientId = state.selectedRecipientId
        if (recipientId == null || state.composeSubject.isBlank() || state.composeBody.isBlank()) {
            return@launch fail("Choose a recipient, then enter a subject and message.")
        }
        _uiState.update { it.copy(isSaving = true, errorMessage = null) }
        when (val result = repository.compose(recipientId, state.composeSubject.trim(), state.composeBody.trim(), state.attachment)) {
            is AppResult.Success -> _uiState.update {
                it.copy(thread = result.value, isSaving = false, attachment = null, message = "Message sent.")
            }
            is AppResult.Failure -> _uiState.update { it.copy(isSaving = false, errorMessage = result.error.userMessage) }
        }
    }

    fun reply() = viewModelScope.launch {
        val state = _uiState.value
        val thread = state.thread ?: return@launch
        if (state.replyBody.isBlank()) return@launch fail("Enter a reply.")
        _uiState.update { it.copy(isSaving = true, errorMessage = null) }
        when (val result = repository.reply(thread.summary.id, state.replyBody.trim(), state.attachment)) {
            is AppResult.Success -> _uiState.update {
                it.copy(
                    thread = thread.copy(replies = thread.replies + result.value),
                    replyBody = "",
                    attachment = null,
                    isSaving = false,
                    message = "Reply sent.",
                )
            }
            is AppResult.Failure -> _uiState.update { it.copy(isSaving = false, errorMessage = result.error.userMessage) }
        }
    }

    fun createEvent() = viewModelScope.launch {
        val state = _uiState.value
        if (state.eventTitle.isBlank() || state.eventStartDate.isBlank()) {
            return@launch fail("Enter an event title and start date.")
        }
        _uiState.update { it.copy(isSaving = true, errorMessage = null) }
        when (val result = repository.createEvent(
            title = state.eventTitle.trim(),
            description = state.eventDescription.trim().takeIf(String::isNotEmpty),
            startDate = state.eventStartDate,
            endDate = state.eventEndDate.takeIf(String::isNotBlank),
            audience = state.eventAudience,
        )) {
            is AppResult.Success -> _uiState.update {
                it.copy(
                    events = (it.events + result.value).distinctBy(SchoolEvent::id).sortedBy(SchoolEvent::startDate),
                    eventTitle = "",
                    eventDescription = "",
                    eventStartDate = "",
                    eventEndDate = "",
                    eventAudience = "all",
                    isSaving = false,
                    message = "Event published as a notice.",
                )
            }
            is AppResult.Failure -> _uiState.update { it.copy(isSaving = false, errorMessage = result.error.userMessage) }
        }
    }

    fun download(attachment: online.educoreng.educore.core.model.MessageAttachment) = viewModelScope.launch {
        _uiState.update { it.copy(isSaving = true, errorMessage = null) }
        when (val result = repository.download(attachment)) {
            is AppResult.Success -> _uiState.update { it.copy(downloadedDocument = result.value, isSaving = false) }
            is AppResult.Failure -> _uiState.update { it.copy(isSaving = false, errorMessage = result.error.userMessage) }
        }
    }

    fun loadEvents() = viewModelScope.launch {
        beginLoading()
        val formatter = SimpleDateFormat("yyyy-MM-dd", Locale.ROOT)
        val from = Calendar.getInstance().apply { add(Calendar.MONTH, -2) }
        val to = Calendar.getInstance().apply { add(Calendar.YEAR, 1) }
        when (val result = repository.events(formatter.format(from.time), formatter.format(to.time))) {
            is AppResult.Success -> _uiState.update { it.copy(events = result.value, isLoading = false) }
            is AppResult.Failure -> failLoading(result.error.userMessage)
        }
    }

    fun consumeMessage() = _uiState.update { it.copy(message = null) }
    fun consumeDocument() = _uiState.update { it.copy(downloadedDocument = null) }

    private fun beginLoading() = _uiState.update { it.copy(isLoading = true, errorMessage = null) }
    private fun failLoading(message: String) = _uiState.update { it.copy(isLoading = false, errorMessage = message) }
    private fun fail(message: String) = _uiState.update { it.copy(errorMessage = message) }

    private fun readAttachment(uri: Uri): Result<PendingAttachment> = runCatching {
        val resolver = context.contentResolver
        var name = "attachment"
        resolver.query(uri, arrayOf(OpenableColumns.DISPLAY_NAME, OpenableColumns.SIZE), null, null, null)?.use { cursor ->
            if (cursor.moveToFirst()) {
                name = cursor.getString(cursor.getColumnIndexOrThrow(OpenableColumns.DISPLAY_NAME)) ?: name
                val sizeIndex = cursor.getColumnIndex(OpenableColumns.SIZE)
                if (sizeIndex >= 0 && ! cursor.isNull(sizeIndex) && cursor.getLong(sizeIndex) > MAX_ATTACHMENT_BYTES) {
                    error("Attachments must not exceed 5 MB.")
                }
            }
        }
        val bytes = requireNotNull(resolver.openInputStream(uri)) { "The attachment is unavailable." }.use { stream ->
            val output = ByteArrayOutputStream()
            val buffer = ByteArray(DEFAULT_BUFFER_SIZE)
            var total = 0L
            while (true) {
                val read = stream.read(buffer)
                if (read < 0) break
                total += read
                require(total <= MAX_ATTACHMENT_BYTES) { "Attachments must not exceed 5 MB." }
                output.write(buffer, 0, read)
            }
            output.toByteArray()
        }
        require(bytes.size <= MAX_ATTACHMENT_BYTES) { "Attachments must not exceed 5 MB." }
        PendingAttachment(name, resolver.getType(uri) ?: "application/octet-stream", bytes)
    }

    private companion object { const val MAX_ATTACHMENT_BYTES = 5L * 1024L * 1024L }
}
