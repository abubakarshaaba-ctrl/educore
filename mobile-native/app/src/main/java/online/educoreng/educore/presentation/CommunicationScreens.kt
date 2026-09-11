package online.educoreng.educore.presentation

import android.app.DatePickerDialog
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyListScope
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Send
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Download
import androidx.compose.material.icons.filled.MarkEmailRead
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.ui.unit.dp
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Locale
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreFilterChip
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTabs
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.MessageAttachment
import online.educoreng.educore.core.model.MessageReply
import online.educoreng.educore.core.model.MessageThreadSummary
import online.educoreng.educore.core.model.NotificationItem
import online.educoreng.educore.core.model.SchoolEvent

@Composable
internal fun CommunicationCenterScreen(
    state: CommunicationUiState,
    canCreateEvent: Boolean,
    onBack: () -> Unit,
    onTab: (Int) -> Unit,
    onNoticeFilter: (String) -> Unit,
    onMarkRead: (Long) -> Unit,
    onMarkAllRead: () -> Unit,
    onOpenThread: (Long) -> Unit,
    onCompose: () -> Unit,
    onEventTitle: (String) -> Unit,
    onEventDescription: (String) -> Unit,
    onEventStartDate: (String) -> Unit,
    onEventEndDate: (String) -> Unit,
    onEventAudience: (String) -> Unit,
    onCreateEvent: () -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.notifications.isEmpty() && state.messagePage == null && state.events.isEmpty()) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading communications")
    }
    LazyColumn(
        Modifier.fillMaxSize().imePadding().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { CommunicationHeader("Communication centre", "Notices, messages and school events", onBack) }
        item {
            EduCoreTabs(
                labels = CommunicationTab.entries.map { tab ->
                    if (tab == CommunicationTab.NOTICES && state.unreadNotifications > 0) "${tab.label} (${state.unreadNotifications})" else tab.label
                },
                selectedIndex = state.selectedTab.ordinal,
                onSelected = onTab,
                modifier = Modifier.fillMaxWidth(),
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        when (state.selectedTab) {
            CommunicationTab.NOTICES -> noticesContent(state, onNoticeFilter, onMarkRead, onMarkAllRead)
            CommunicationTab.MESSAGES -> messagesContent(state, onOpenThread, onCompose)
            CommunicationTab.EVENTS -> eventsContent(
                state = state,
                canCreateEvent = canCreateEvent,
                onTitle = onEventTitle,
                onDescription = onEventDescription,
                onStartDate = onEventStartDate,
                onEndDate = onEventEndDate,
                onAudience = onEventAudience,
                onCreate = onCreateEvent,
            )
        }
        if (state.errorMessage != null && state.notifications.isEmpty() && state.messagePage == null && state.events.isEmpty()) {
            item { EduCoreSecondaryButton("Try again", onRetry, Modifier.fillMaxWidth()) }
        }
        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

private fun LazyListScope.noticesContent(
    state: CommunicationUiState,
    onFilter: (String) -> Unit,
    onMarkRead: (Long) -> Unit,
    onMarkAllRead: () -> Unit,
) {
    item {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm), verticalAlignment = Alignment.CenterVertically) {
            Row(Modifier.weight(1f), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                listOf("all" to "All", "unread" to "Unread", "read" to "Read").forEach { (key, label) ->
                    EduCoreFilterChip(label, state.noticeFilter == key, { onFilter(key) })
                }
            }
            if (state.unreadNotifications > 0) EduCoreSecondaryButton("Mark all read", onMarkAllRead)
        }
    }
    if (state.notifications.isEmpty()) {
        item { EduCoreEmptyState("No notices", "There are no ${state.noticeFilter.takeUnless { it == "all" }.orEmpty()} notices to show.") }
    } else {
        items(state.notifications, key = NotificationItem::id) { notice -> NotificationCard(notice, onMarkRead) }
    }
}

private fun LazyListScope.messagesContent(
    state: CommunicationUiState,
    onOpenThread: (Long) -> Unit,
    onCompose: () -> Unit,
) {
    item {
        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text("Conversations", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.SemiBold)
                Text("${state.messagePage?.unreadCount ?: 0} unread", color = EduCoreColors.Slate600)
            }
            EduCorePrimaryButton("New message", onCompose, leadingIcon = { Icon(Icons.AutoMirrored.Filled.Send, null) })
        }
    }
    val threads = state.messagePage?.threads.orEmpty()
    if (threads.isEmpty()) item { EduCoreEmptyState("No conversations", "Start a message when you need to contact the school, staff or a parent.") }
    items(threads, key = MessageThreadSummary::id) { thread -> MessageThreadCard(thread) { onOpenThread(thread.id) } }
}

private fun LazyListScope.eventsContent(
    state: CommunicationUiState,
    canCreateEvent: Boolean,
    onTitle: (String) -> Unit,
    onDescription: (String) -> Unit,
    onStartDate: (String) -> Unit,
    onEndDate: (String) -> Unit,
    onAudience: (String) -> Unit,
    onCreate: () -> Unit,
) {
    item {
        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text("School calendar", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.SemiBold)
                Text("Events published here also appear as Notices.", color = EduCoreColors.Slate600)
            }
        }
    }
    if (canCreateEvent) {
        item {
            EventCreateCard(
                state = state,
                onTitle = onTitle,
                onDescription = onDescription,
                onStartDate = onStartDate,
                onEndDate = onEndDate,
                onAudience = onAudience,
                onCreate = onCreate,
            )
        }
    }
    if (state.events.isEmpty()) item { EduCoreEmptyState("No upcoming events", "Published school events will appear here.") }
    items(state.events, key = SchoolEvent::id) { event -> SchoolEventCard(event) }
}

@Composable
private fun EventCreateCard(
    state: CommunicationUiState,
    onTitle: (String) -> Unit,
    onDescription: (String) -> Unit,
    onStartDate: (String) -> Unit,
    onEndDate: (String) -> Unit,
    onAudience: (String) -> Unit,
    onCreate: () -> Unit,
) {
    val context = LocalContext.current
    fun chooseDate(current: String, onSelected: (String) -> Unit) {
        val calendar = Calendar.getInstance()
        if (current.isNotBlank()) runCatching {
            SimpleDateFormat("yyyy-MM-dd", Locale.US).parse(current)
        }.getOrNull()?.let { calendar.time = it }
        DatePickerDialog(
            context,
            { _, year, month, day -> onSelected(String.format(Locale.US, "%04d-%02d-%02d", year, month + 1, day)) },
            calendar.get(Calendar.YEAR),
            calendar.get(Calendar.MONTH),
            calendar.get(Calendar.DAY_OF_MONTH),
        ).show()
    }

    Card(
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(
            Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            Text("Publish school event", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
            Text("This creates a calendar event and a Notice. No response is required from recipients.", color = EduCoreColors.Slate600, style = MaterialTheme.typography.bodySmall)
            OutlinedTextField(
                value = state.eventTitle,
                onValueChange = onTitle,
                label = { Text("Event title") },
                singleLine = true,
                modifier = Modifier.fillMaxWidth(),
            )
            OutlinedTextField(
                value = state.eventDescription,
                onValueChange = onDescription,
                label = { Text("Description (optional)") },
                minLines = 3,
                modifier = Modifier.fillMaxWidth(),
            )
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                OutlinedButton(
                    onClick = { chooseDate(state.eventStartDate, onStartDate) },
                    modifier = Modifier.weight(1f),
                ) { Text(state.eventStartDate.ifBlank { "Start date" }) }
                OutlinedButton(
                    onClick = { chooseDate(state.eventEndDate, onEndDate) },
                    modifier = Modifier.weight(1f),
                ) { Text(state.eventEndDate.ifBlank { "End date" }) }
            }
            Text("Audience", style = MaterialTheme.typography.labelLarge)
            Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                listOf("all" to "Everyone", "staff" to "Staff", "parents" to "Parents").forEach { (key, label) ->
                    EduCoreFilterChip(label, state.eventAudience == key, { onAudience(key) })
                }
            }
            EduCorePrimaryButton(
                text = "Publish event as notice",
                onClick = onCreate,
                modifier = Modifier.fillMaxWidth(),
                enabled = state.eventTitle.isNotBlank() && state.eventStartDate.isNotBlank(),
                loading = state.isSaving,
                leadingIcon = { Icon(Icons.Default.CalendarMonth, null) },
            )
        }
    }
}

@Composable
internal fun MessageThreadScreen(
    state: CommunicationUiState,
    onBack: () -> Unit,
    onReplyChange: (String) -> Unit,
    onReply: () -> Unit,
    onAttachmentPicked: (String) -> Unit,
    onClearAttachment: () -> Unit,
    onDownload: (MessageAttachment) -> Unit,
    onRetry: () -> Unit,
    onDocumentOpened: () -> Unit,
) {
    OpenDocumentEffect(state.downloadedDocument, onDocumentOpened)
    if (state.isLoading && state.thread == null) return EduCoreLoadingState(Modifier.fillMaxSize(), "Opening conversation")
    val thread = state.thread ?: return EduCoreErrorState(
        message = state.errorMessage ?: "This conversation is unavailable.",
        modifier = Modifier.fillMaxSize(),
        onRetry = onRetry,
    )
    val picker = rememberLauncherForActivityResult(ActivityResultContracts.OpenDocument()) { uri -> uri?.let { onAttachmentPicked(it.toString()) } }
    LazyColumn(
        Modifier.fillMaxSize().imePadding(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { CommunicationHeader(thread.summary.subject, thread.summary.otherName ?: thread.summary.studentName ?: "School conversation", onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        items(thread.replies, key = MessageReply::id) { reply -> ReplyCard(reply, onDownload) }
        if (thread.summary.status == "open") {
            item {
                Card(colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
                    Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                        OutlinedTextField(
                            value = state.replyBody,
                            onValueChange = onReplyChange,
                            label = { Text("Reply") },
                            minLines = 3,
                            modifier = Modifier.fillMaxWidth(),
                        )
                        AttachmentSelection(state, { picker.launch(ALLOWED_ATTACHMENTS) }, onClearAttachment)
                        EduCorePrimaryButton("Send reply", onReply, Modifier.fillMaxWidth(), enabled = state.replyBody.isNotBlank(), loading = state.isSaving)
                    }
                }
            }
        }
    }
}

@Composable
internal fun ComposeMessageScreen(
    state: CommunicationUiState,
    onBack: () -> Unit,
    onRecipient: (Long) -> Unit,
    onSubject: (String) -> Unit,
    onBody: (String) -> Unit,
    onAttachmentPicked: (String) -> Unit,
    onClearAttachment: () -> Unit,
    onSend: () -> Unit,
    onComposed: (Long) -> Unit,
    onRetry: () -> Unit,
) {
    val composedThread = state.thread
    LaunchedEffect(composedThread?.summary?.id) { composedThread?.let { onComposed(it.summary.id) } }
    if (state.isLoading && state.recipients.isEmpty()) return EduCoreLoadingState(Modifier.fillMaxSize(), "Preparing message")
    if (state.errorMessage != null && state.recipients.isEmpty()) return EduCoreErrorState(state.errorMessage, Modifier.fillMaxSize(), onRetry = onRetry)
    val picker = rememberLauncherForActivityResult(ActivityResultContracts.OpenDocument()) { uri -> uri?.let { onAttachmentPicked(it.toString()) } }
    var recipientsOpen by remember { mutableStateOf(false) }
    val selected = state.recipients.firstOrNull { it.studentId == state.selectedRecipientId }

    LazyColumn(
        Modifier.fillMaxSize().imePadding(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { CommunicationHeader("New message", "Secure school communication", onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item {
            Box(Modifier.fillMaxWidth()) {
                OutlinedButton(onClick = { recipientsOpen = true }, modifier = Modifier.fillMaxWidth()) {
                    Text(
                        selected?.let { selectedRecipientLabel(it.name, it.admissionNumber, it.className) } ?: "Choose recipient",
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                    )
                }
                DropdownMenu(expanded = recipientsOpen, onDismissRequest = { recipientsOpen = false }, modifier = Modifier.fillMaxWidth(.9f)) {
                    state.recipients.forEach { recipient ->
                        DropdownMenuItem(
                            text = { Text(selectedRecipientLabel(recipient.name, recipient.admissionNumber, recipient.className)) },
                            onClick = { onRecipient(recipient.studentId); recipientsOpen = false },
                        )
                    }
                }
            }
        }
        item { OutlinedTextField(state.composeSubject, onSubject, label = { Text("Subject") }, singleLine = true, modifier = Modifier.fillMaxWidth(), keyboardOptions = KeyboardOptions(imeAction = ImeAction.Next)) }
        item { OutlinedTextField(state.composeBody, onBody, label = { Text("Message") }, minLines = 6, modifier = Modifier.fillMaxWidth(), keyboardOptions = KeyboardOptions(imeAction = ImeAction.Done)) }
        item { AttachmentSelection(state, { picker.launch(ALLOWED_ATTACHMENTS) }, onClearAttachment) }
        item {
            EduCorePrimaryButton(
                "Send message", onSend, Modifier.fillMaxWidth(),
                enabled = state.selectedRecipientId != null && state.composeSubject.isNotBlank() && state.composeBody.isNotBlank(),
                loading = state.isSaving,
                leadingIcon = { Icon(Icons.AutoMirrored.Filled.Send, null) },
            )
        }
    }
}

private fun selectedRecipientLabel(name: String, supporting: String, className: String?): String =
    listOf(name, className?.takeIf(String::isNotBlank) ?: supporting.takeIf(String::isNotBlank)).filterNotNull().joinToString(" · ")

@Composable
private fun NotificationCard(notice: NotificationItem, onMarkRead: (Long) -> Unit) {
    Card(
        onClick = { if (! notice.isRead) onMarkRead(notice.id) },
        colors = CardDefaults.cardColors(containerColor = if (notice.isRead) EduCoreColors.White else EduCoreColors.Info100),
        border = BorderStroke(1.dp, if (notice.isRead) EduCoreColors.Line200 else EduCoreColors.Info700),
    ) {
        Row(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalAlignment = Alignment.Top) {
            Icon(if (notice.isRead) Icons.Default.MarkEmailRead else Icons.Default.Notifications, null, tint = if (notice.isRead) EduCoreColors.Slate600 else EduCoreColors.Navy900)
            Spacer(Modifier.width(EduCoreSpacing.Md))
            Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text(notice.title, Modifier.weight(1f), style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    EduCoreStatusBadge(notice.priority.replaceFirstChar(Char::uppercase), notice.priority.noticeTone())
                }
                Text(notice.body, style = MaterialTheme.typography.bodyMedium, color = EduCoreColors.Ink900)
                Text(notice.publishedAt, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Muted500)
            }
        }
    }
}

@Composable
private fun MessageThreadCard(thread: MessageThreadSummary, onOpen: () -> Unit) {
    Card(onClick = onOpen, colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
        Row(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalAlignment = Alignment.CenterVertically) {
            Surface(color = if (thread.unreadCount > 0) EduCoreColors.Gold100 else EduCoreColors.Page50, shape = MaterialTheme.shapes.medium) {
                Text(thread.unreadCount.takeIf { it > 0 }?.toString() ?: "✉", Modifier.padding(EduCoreSpacing.Md), color = EduCoreColors.Navy900, fontWeight = FontWeight.SemiBold)
            }
            Spacer(Modifier.width(EduCoreSpacing.Md))
            Column(Modifier.weight(1f)) {
                Text(thread.subject, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                Text(listOfNotNull(thread.studentName, thread.otherName).joinToString(" · "), color = EduCoreColors.Slate600, style = MaterialTheme.typography.bodySmall)
                thread.lastMessage?.let { Text(it, maxLines = 2, overflow = TextOverflow.Ellipsis, color = EduCoreColors.Ink900) }
            }
            EduCoreStatusBadge(thread.status.replaceFirstChar(Char::uppercase), if (thread.status == "open") EduCoreTone.Success else EduCoreTone.Neutral)
        }
    }
}

@Composable
private fun SchoolEventCard(event: SchoolEvent) {
    Card(colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
        Row(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalAlignment = Alignment.Top) {
            Surface(color = EduCoreColors.Info100, shape = MaterialTheme.shapes.medium) {
                Icon(Icons.Default.CalendarMonth, null, Modifier.padding(EduCoreSpacing.Md), tint = EduCoreColors.Navy900)
            }
            Spacer(Modifier.width(EduCoreSpacing.Md))
            Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                Text(event.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                Text(if (event.endDate != null && event.endDate != event.startDate) "${event.startDate} – ${event.endDate}" else event.startDate, color = EduCoreColors.Gold600, fontWeight = FontWeight.SemiBold)
                event.description?.takeIf(String::isNotBlank)?.let { Text(it, color = EduCoreColors.Slate600) }
            }
            EduCoreStatusBadge(event.type.replace('_', ' ').replaceFirstChar(Char::uppercase), EduCoreTone.Info)
        }
    }
}

@Composable
private fun ReplyCard(reply: MessageReply, onDownload: (MessageAttachment) -> Unit) {
    Row(Modifier.fillMaxWidth(), horizontalArrangement = if (reply.isMine) Arrangement.End else Arrangement.Start) {
        Surface(
            modifier = Modifier.fillMaxWidth(.88f),
            color = if (reply.isMine) EduCoreColors.Navy900 else EduCoreColors.White,
            shape = MaterialTheme.shapes.medium,
            border = if (reply.isMine) null else BorderStroke(1.dp, EduCoreColors.Line200),
        ) {
            Column(Modifier.padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                Text(reply.senderName ?: if (reply.isMine) "You" else "School", color = if (reply.isMine) EduCoreColors.Gold400 else EduCoreColors.Gold600, fontWeight = FontWeight.SemiBold)
                Text(reply.body, color = if (reply.isMine) EduCoreColors.White else EduCoreColors.Ink900)
                reply.attachment?.let { attachment ->
                    OutlinedButton(onClick = { onDownload(attachment) }) {
                        Icon(Icons.Default.Download, null)
                        Spacer(Modifier.width(EduCoreSpacing.Sm))
                        Text(attachment.name, maxLines = 1, overflow = TextOverflow.Ellipsis)
                    }
                }
                Text(reply.createdAt, style = MaterialTheme.typography.bodySmall, color = if (reply.isMine) EduCoreColors.Line300 else EduCoreColors.Muted500)
            }
        }
    }
}

@Composable
private fun AttachmentSelection(state: CommunicationUiState, onPick: () -> Unit, onClear: () -> Unit) {
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm), verticalAlignment = Alignment.CenterVertically) {
        EduCoreSecondaryButton(if (state.attachment == null) "Attach file" else "Replace file", onPick)
        state.attachment?.let { file ->
            Text(file.name, Modifier.weight(1f), maxLines = 1, overflow = TextOverflow.Ellipsis)
            EduCoreSecondaryButton("Remove", onClear)
        }
    }
}

@Composable
private fun CommunicationHeader(title: String, subtitle: String, onBack: () -> Unit) {
    EduCorePageHeader(title = title, subtitle = subtitle, onBack = onBack)
}

private fun String.noticeTone(): EduCoreTone = when (lowercase()) {
    "urgent" -> EduCoreTone.Danger
    "important" -> EduCoreTone.Warning
    else -> EduCoreTone.Info
}

private val ALLOWED_ATTACHMENTS = arrayOf(
    "image/jpeg", "image/png", "application/pdf", "application/msword",
    "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    "application/vnd.ms-excel", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
)
