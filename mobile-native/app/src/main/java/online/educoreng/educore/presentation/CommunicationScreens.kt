package online.educoreng.educore.presentation

import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.horizontalScroll
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
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyListScope
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.text.KeyboardOptions
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreFilterChip
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseSectionCard
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseStat
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
import online.educoreng.educore.core.model.PlatformNotice
import online.educoreng.educore.core.model.SchoolEvent

@Composable
internal fun CommunicationCenterScreen(
    state: CommunicationUiState,
    onBack: () -> Unit,
    onTab: (Int) -> Unit,
    onNoticeFilter: (String) -> Unit,
    onMarkRead: (Long) -> Unit,
    onMarkAllRead: () -> Unit,
    onOpenThread: (Long) -> Unit,
    onCompose: () -> Unit,
    onRetry: () -> Unit,
    onMarkPlatformRead: (Long) -> Unit = {},
    onDismissPlatformNotice: (Long) -> Unit = {},
) {
    if (state.isLoading && state.notifications.isEmpty() && state.platformNotices.isEmpty() && state.messagePage == null && state.events.isEmpty()) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading communications")
    }

    val unreadMessages = state.messagePage?.unreadCount ?: 0

    LazyColumn(
        modifier = Modifier.fillMaxSize().imePadding().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Inbox",
                subtitle = "School and EduCore platform communication in one secure workspace",
                onBack = onBack,
            )
        }

        item {
            EduCoreShowcaseHero(
                eyebrow = "COMMUNICATION CENTRE",
                title = "Stay current without leaving EduCore.",
                subtitle = "Read school and EduCore platform notices, message permitted staff or School Administration, and review upcoming events from one native inbox.",
                trailing = {
                    Surface(
                        modifier = Modifier.size(48.dp),
                        shape = CircleShape,
                        color = EduCoreColors.Gold100,
                        contentColor = EduCoreColors.Navy900,
                    ) {
                        Box(contentAlignment = Alignment.Center) {
                            Icon(Icons.Default.Notifications, contentDescription = null, modifier = Modifier.size(24.dp))
                        }
                    }
                },
            )
        }

        item {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                EduCoreShowcaseStat(
                    label = "Unread notices",
                    value = state.totalUnreadNotices.toString(),
                    icon = Icons.Default.Notifications,
                    tone = if (state.totalUnreadNotices > 0) EduCoreTone.Accent else EduCoreTone.Neutral,
                    modifier = Modifier.weight(1f),
                )
                EduCoreShowcaseStat(
                    label = "Unread messages",
                    value = unreadMessages.toString(),
                    icon = Icons.Default.MarkEmailRead,
                    tone = if (unreadMessages > 0) EduCoreTone.Info else EduCoreTone.Neutral,
                    modifier = Modifier.weight(1f),
                )
                EduCoreShowcaseStat(
                    label = "Events",
                    value = state.events.size.toString(),
                    icon = Icons.Default.CalendarMonth,
                    tone = EduCoreTone.Brand,
                    modifier = Modifier.weight(1f),
                )
            }
        }

        item {
            EduCoreTabs(
                labels = CommunicationTab.entries.map { tab ->
                    when (tab) {
                        CommunicationTab.NOTICES -> if (state.totalUnreadNotices > 0) "${tab.label} (${state.totalUnreadNotices})" else tab.label
                        CommunicationTab.MESSAGES -> if (unreadMessages > 0) "${tab.label} ($unreadMessages)" else tab.label
                        CommunicationTab.EVENTS -> tab.label
                    }
                },
                selectedIndex = state.selectedTab.ordinal,
                onSelected = onTab,
                modifier = Modifier.fillMaxWidth(),
            )
        }

        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }

        when (state.selectedTab) {
            CommunicationTab.NOTICES -> noticesContent(
                state,
                onNoticeFilter,
                onMarkRead,
                onMarkAllRead,
                onMarkPlatformRead,
                onDismissPlatformNotice,
            )
            CommunicationTab.MESSAGES -> messagesContent(state, onOpenThread, onCompose)
            CommunicationTab.EVENTS -> eventsContent(state)
        }

        if (state.errorMessage != null && state.notifications.isEmpty() && state.platformNotices.isEmpty() && state.messagePage == null && state.events.isEmpty()) {
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
    onMarkPlatformRead: (Long) -> Unit,
    onDismissPlatformNotice: (Long) -> Unit,
) {
    item {
        Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Row(
                modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                listOf("all" to "All", "unread" to "Unread", "read" to "Read").forEach { (key, label) ->
                    EduCoreFilterChip(label, state.noticeFilter == key, { onFilter(key) })
                }
            }
        }
    }

    if (state.platformNotices.isNotEmpty()) {
        item {
            EduCoreSectionHeader(
                title = "EduCore platform notices",
                supportingText = if (state.platformUnreadNotifications > 0) {
                    "${state.platformUnreadNotifications} platform notice${if (state.platformUnreadNotifications == 1) "" else "s"} waiting for your attention"
                } else {
                    "Official service notices from the EduCore platform"
                },
            )
        }
        items(state.platformNotices, key = { "platform-${it.id}" }) { notice ->
            PlatformNoticeCard(notice, state.isSaving, onMarkPlatformRead, onDismissPlatformNotice)
        }
    }

    item {
        EduCoreSectionHeader(
            title = "School notices",
            supportingText = if (state.unreadNotifications > 0) {
                "${state.unreadNotifications} school notice${if (state.unreadNotifications == 1) "" else "s"} waiting for your attention"
            } else {
                "You are up to date with school notices"
            },
        )
    }

    if (state.unreadNotifications > 0) {
        item {
            EduCoreSecondaryButton(
                text = "Mark all school notices as read",
                onClick = onMarkAllRead,
                modifier = Modifier.fillMaxWidth(),
            )
        }
    }

    if (state.notifications.isEmpty() && state.platformNotices.isEmpty()) {
        item {
            EduCoreShowcaseSectionCard {
                EduCoreEmptyState(
                    title = when (state.noticeFilter) {
                        "unread" -> "No unread notices"
                        "read" -> "No read notices"
                        else -> "No notices"
                    },
                    message = when (state.noticeFilter) {
                        "unread" -> "You have read every notice currently available to your account."
                        "read" -> "Notices you have read will appear here."
                        else -> "Published school and EduCore platform notices will appear here when available."
                    },
                    icon = Icons.Default.Notifications,
                )
            }
        }
    } else if (state.notifications.isNotEmpty()) {
        items(state.notifications, key = { "school-${it.id}" }) { notice ->
            NotificationCard(notice, onMarkRead)
        }
    }
}

private fun LazyListScope.messagesContent(
    state: CommunicationUiState,
    onOpenThread: (Long) -> Unit,
    onCompose: () -> Unit,
) {
    item {
        EduCoreSectionHeader(
            title = "Conversations",
            supportingText = "${state.messagePage?.unreadCount ?: 0} unread · secure school messaging",
        )
    }
    item {
        EduCorePrimaryButton(
            text = "New message",
            onClick = onCompose,
            modifier = Modifier.fillMaxWidth(),
            leadingIcon = { Icon(Icons.AutoMirrored.Filled.Send, contentDescription = null) },
        )
    }

    val threads = state.messagePage?.threads.orEmpty()
    if (threads.isEmpty()) {
        item {
            EduCoreShowcaseSectionCard {
                EduCoreEmptyState(
                    title = "No conversations",
                    message = "Start a message to School Administration or another recipient permitted for your account.",
                    icon = Icons.Default.MarkEmailRead,
                )
            }
        }
    } else {
        items(threads, key = MessageThreadSummary::id) { thread ->
            MessageThreadCard(thread) { onOpenThread(thread.id) }
        }
    }
}

private fun LazyListScope.eventsContent(state: CommunicationUiState) {
    item {
        EduCoreSectionHeader(
            title = "School calendar",
            supportingText = if (state.events.isEmpty()) "No published upcoming events" else "${state.events.size} upcoming event${if (state.events.size == 1) "" else "s"}",
        )
    }
    if (state.events.isEmpty()) {
        item {
            EduCoreShowcaseSectionCard {
                EduCoreEmptyState(
                    title = "No upcoming events",
                    message = "Published school events will appear here. Events are notices and do not require RSVP.",
                    icon = Icons.Default.CalendarMonth,
                )
            }
        }
    } else {
        items(state.events, key = SchoolEvent::id) { event -> SchoolEventCard(event) }
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
    if (state.isLoading && state.thread == null) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Opening conversation")
    }
    val thread = state.thread ?: return EduCoreErrorState(
        message = state.errorMessage ?: "This conversation is unavailable.",
        modifier = Modifier.fillMaxSize(),
        onRetry = onRetry,
    )
    val picker = rememberLauncherForActivityResult(ActivityResultContracts.OpenDocument()) { uri ->
        uri?.let { onAttachmentPicked(it.toString()) }
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize().imePadding().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            CommunicationHeader(
                thread.summary.subject,
                thread.summary.otherName ?: thread.summary.studentName ?: "School conversation",
                onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        items(thread.replies, key = MessageReply::id) { reply -> ReplyCard(reply, onDownload) }
        if (thread.summary.status == "open") {
            item {
                Card(
                    colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                    border = BorderStroke(1.dp, EduCoreColors.Line300),
                ) {
                    Column(
                        Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
                        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    ) {
                        EduCoreSectionHeader("Reply", "Continue this private school conversation")
                        OutlinedTextField(
                            value = state.replyBody,
                            onValueChange = onReplyChange,
                            label = { Text("Message") },
                            minLines = 3,
                            modifier = Modifier.fillMaxWidth(),
                        )
                        AttachmentSelection(state, { picker.launch(ALLOWED_ATTACHMENTS) }, onClearAttachment)
                        EduCorePrimaryButton(
                            "Send reply",
                            onReply,
                            Modifier.fillMaxWidth(),
                            enabled = state.replyBody.isNotBlank(),
                            loading = state.isSaving,
                        )
                    }
                }
            }
        }
        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
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
    LaunchedEffect(composedThread?.summary?.id) {
        composedThread?.let { onComposed(it.summary.id) }
    }
    if (state.isLoading && state.recipients.isEmpty()) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Preparing message")
    }
    if (state.errorMessage != null && state.recipients.isEmpty()) {
        return EduCoreErrorState(state.errorMessage, Modifier.fillMaxSize(), onRetry = onRetry)
    }

    val picker = rememberLauncherForActivityResult(ActivityResultContracts.OpenDocument()) { uri ->
        uri?.let { onAttachmentPicked(it.toString()) }
    }
    var recipientsOpen by remember { mutableStateOf(false) }
    val selected = state.recipients.firstOrNull { it.studentId == state.selectedRecipientId }

    LazyColumn(
        modifier = Modifier.fillMaxSize().imePadding().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { CommunicationHeader("New message", "Secure school conversation", onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item {
            EduCoreShowcaseSectionCard {
                Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                    EduCoreSectionHeader("Message details", "Choose a permitted recipient and write your message")
                    Box(Modifier.fillMaxWidth()) {
                        OutlinedButton(onClick = { recipientsOpen = true }, modifier = Modifier.fillMaxWidth()) {
                            Text(
                                selected?.let { recipient ->
                                    listOf(recipient.name, recipient.admissionNumber)
                                        .filter(String::isNotBlank)
                                        .joinToString(" · ")
                                } ?: "Choose recipient",
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis,
                            )
                        }
                        DropdownMenu(
                            expanded = recipientsOpen,
                            onDismissRequest = { recipientsOpen = false },
                            modifier = Modifier.fillMaxWidth(.9f),
                        ) {
                            state.recipients.forEach { recipient ->
                                DropdownMenuItem(
                                    text = {
                                        Text(
                                            listOf(recipient.name, recipient.admissionNumber)
                                                .filter(String::isNotBlank)
                                                .joinToString(" · ")
                                        )
                                    },
                                    onClick = {
                                        onRecipient(recipient.studentId)
                                        recipientsOpen = false
                                    },
                                )
                            }
                        }
                    }
                    OutlinedTextField(
                        state.composeSubject,
                        onSubject,
                        label = { Text("Subject") },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth(),
                        keyboardOptions = KeyboardOptions(imeAction = ImeAction.Next),
                    )
                    OutlinedTextField(
                        state.composeBody,
                        onBody,
                        label = { Text("Message") },
                        minLines = 6,
                        modifier = Modifier.fillMaxWidth(),
                        keyboardOptions = KeyboardOptions(imeAction = ImeAction.Done),
                    )
                    AttachmentSelection(state, { picker.launch(ALLOWED_ATTACHMENTS) }, onClearAttachment)
                }
            }
        }
        item {
            EduCorePrimaryButton(
                "Send message",
                onSend,
                Modifier.fillMaxWidth(),
                enabled = state.selectedRecipientId != null && state.composeSubject.isNotBlank() && state.composeBody.isNotBlank(),
                loading = state.isSaving,
                leadingIcon = { Icon(Icons.AutoMirrored.Filled.Send, contentDescription = null) },
            )
        }
        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

@Composable
private fun PlatformNoticeCard(
    notice: PlatformNotice,
    busy: Boolean,
    onMarkRead: (Long) -> Unit,
    onDismiss: (Long) -> Unit,
) {
    Card(
        onClick = { if (!notice.isRead) onMarkRead(notice.id) },
        modifier = Modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.large,
        colors = CardDefaults.cardColors(
            containerColor = when {
                notice.priority.equals("urgent", true) && !notice.isRead -> EduCoreColors.Gold100
                notice.isRead -> EduCoreColors.White
                else -> EduCoreColors.Info100
            },
        ),
        border = BorderStroke(
            1.dp,
            when {
                notice.priority.equals("urgent", true) -> EduCoreColors.Gold700
                notice.isRead -> EduCoreColors.Line300
                else -> EduCoreColors.Info700
            },
        ),
    ) {
        Column(
            Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f)) {
                    Text("EDUCORE PLATFORM NOTICE", style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Gold700)
                    Text(notice.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                }
                EduCoreStatusBadge(notice.priority.replaceFirstChar(Char::uppercase), notice.priority.noticeTone())
            }
            Text(notice.body, style = MaterialTheme.typography.bodyMedium, color = EduCoreColors.Ink900)
            notice.createdAt?.let { Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Muted500) }
            Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                if (!notice.isRead) {
                    EduCoreSecondaryButton(
                        text = "Mark read",
                        onClick = { onMarkRead(notice.id) },
                        enabled = !busy,
                        modifier = Modifier.weight(1f),
                    )
                }
                EduCoreSecondaryButton(
                    text = "Dismiss",
                    onClick = { onDismiss(notice.id) },
                    enabled = !busy,
                    modifier = Modifier.weight(1f),
                )
            }
        }
    }
}

@Composable
private fun NotificationCard(notice: NotificationItem, onMarkRead: (Long) -> Unit) {
    Card(
        onClick = { if (!notice.isRead) onMarkRead(notice.id) },
        modifier = Modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.large,
        colors = CardDefaults.cardColors(
            containerColor = if (notice.isRead) EduCoreColors.White else EduCoreColors.Info100,
        ),
        border = BorderStroke(1.dp, if (notice.isRead) EduCoreColors.Line300 else EduCoreColors.Info700),
    ) {
        Row(
            Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalAlignment = Alignment.Top,
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            Surface(
                modifier = Modifier.size(42.dp),
                shape = CircleShape,
                color = if (notice.isRead) EduCoreColors.Page50 else EduCoreColors.Gold100,
                contentColor = EduCoreColors.Navy900,
            ) {
                Box(contentAlignment = Alignment.Center) {
                    Icon(
                        if (notice.isRead) Icons.Default.MarkEmailRead else Icons.Default.Notifications,
                        contentDescription = null,
                        modifier = Modifier.size(20.dp),
                    )
                }
            }
            Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text(
                        notice.title,
                        Modifier.weight(1f),
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.SemiBold,
                        color = EduCoreColors.Ink900,
                    )
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
    Card(
        onClick = onOpen,
        modifier = Modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.large,
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line300),
    ) {
        Row(
            Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            Surface(
                modifier = Modifier.size(42.dp),
                color = if (thread.unreadCount > 0) EduCoreColors.Gold100 else EduCoreColors.Page50,
                shape = CircleShape,
                contentColor = EduCoreColors.Navy900,
            ) {
                Box(contentAlignment = Alignment.Center) {
                    Text(
                        thread.unreadCount.takeIf { it > 0 }?.toString() ?: "✉",
                        color = EduCoreColors.Navy900,
                        fontWeight = FontWeight.Bold,
                    )
                }
            }
            Column(
                Modifier.weight(1f),
                verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs),
            ) {
                Text(
                    thread.subject,
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.SemiBold,
                    color = EduCoreColors.Ink900,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis,
                )
                Text(
                    listOfNotNull(thread.otherName, thread.studentName).distinct().joinToString(" · "),
                    color = EduCoreColors.Slate700,
                    style = MaterialTheme.typography.bodySmall,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis,
                )
                thread.lastMessage?.let {
                    Text(
                        it,
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis,
                        color = EduCoreColors.Ink900,
                        style = MaterialTheme.typography.bodySmall,
                    )
                }
            }
            EduCoreStatusBadge(
                thread.status.replaceFirstChar(Char::uppercase),
                if (thread.status == "open") EduCoreTone.Success else EduCoreTone.Neutral,
            )
        }
    }
}

@Composable
private fun SchoolEventCard(event: SchoolEvent) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.large,
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line300),
    ) {
        Row(
            Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalAlignment = Alignment.Top,
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            Surface(
                modifier = Modifier.size(42.dp),
                color = EduCoreColors.Info100,
                shape = CircleShape,
                contentColor = EduCoreColors.Navy900,
            ) {
                Box(contentAlignment = Alignment.Center) {
                    Icon(Icons.Default.CalendarMonth, contentDescription = null, modifier = Modifier.size(20.dp))
                }
            }
            Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                Text(event.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                Text(
                    if (event.endDate != null && event.endDate != event.startDate) "${event.startDate} – ${event.endDate}" else event.startDate,
                    color = EduCoreColors.Gold700,
                    fontWeight = FontWeight.SemiBold,
                    style = MaterialTheme.typography.bodySmall,
                )
                event.description?.takeIf(String::isNotBlank)?.let {
                    Text(it, color = EduCoreColors.Slate700, style = MaterialTheme.typography.bodySmall)
                }
            }
            EduCoreStatusBadge(
                event.type.replace('_', ' ').replaceFirstChar(Char::uppercase),
                EduCoreTone.Info,
            )
        }
    }
}

@Composable
private fun ReplyCard(reply: MessageReply, onDownload: (MessageAttachment) -> Unit) {
    Row(
        Modifier.fillMaxWidth(),
        horizontalArrangement = if (reply.isMine) Arrangement.End else Arrangement.Start,
    ) {
        Surface(
            modifier = Modifier.fillMaxWidth(.88f),
            color = if (reply.isMine) EduCoreColors.Navy900 else EduCoreColors.White,
            shape = MaterialTheme.shapes.medium,
            border = if (reply.isMine) null else BorderStroke(1.dp, EduCoreColors.Line300),
        ) {
            Column(
                Modifier.padding(EduCoreSpacing.Lg),
                verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                Text(
                    reply.senderName ?: if (reply.isMine) "You" else "School",
                    color = if (reply.isMine) EduCoreColors.Gold400 else EduCoreColors.Gold700,
                    fontWeight = FontWeight.SemiBold,
                )
                Text(reply.body, color = if (reply.isMine) EduCoreColors.White else EduCoreColors.Ink900)
                reply.attachment?.let { attachment ->
                    OutlinedButton(onClick = { onDownload(attachment) }) {
                        Icon(Icons.Default.Download, contentDescription = null)
                        Spacer(Modifier.width(EduCoreSpacing.Sm))
                        Text(attachment.name, maxLines = 1, overflow = TextOverflow.Ellipsis)
                    }
                }
                Text(
                    reply.createdAt,
                    style = MaterialTheme.typography.bodySmall,
                    color = if (reply.isMine) EduCoreColors.Line200 else EduCoreColors.Muted500,
                )
            }
        }
    }
}

@Composable
private fun AttachmentSelection(
    state: CommunicationUiState,
    onPick: () -> Unit,
    onClear: () -> Unit,
) {
    Column(
        Modifier.fillMaxWidth(),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
    ) {
        EduCoreSecondaryButton(
            text = if (state.attachment == null) "Attach file" else "Replace attachment",
            onClick = onPick,
            modifier = Modifier.fillMaxWidth(),
        )
        state.attachment?.let { file ->
            Row(
                Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Text(file.name, Modifier.weight(1f), maxLines = 1, overflow = TextOverflow.Ellipsis)
                EduCoreSecondaryButton("Remove", onClear)
            }
        }
    }
}

@Composable
private fun CommunicationHeader(title: String, subtitle: String, onBack: () -> Unit) {
    EduCorePageHeader(title = title, subtitle = subtitle, onBack = onBack)
}

private fun String.noticeTone(): EduCoreTone = when (lowercase()) {
    "urgent" -> EduCoreTone.Danger
    "high", "important" -> EduCoreTone.Warning
    else -> EduCoreTone.Info
}

private val ALLOWED_ATTACHMENTS = arrayOf(
    "image/jpeg",
    "image/png",
    "application/pdf",
    "application/msword",
    "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    "application/vnd.ms-excel",
    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
)
