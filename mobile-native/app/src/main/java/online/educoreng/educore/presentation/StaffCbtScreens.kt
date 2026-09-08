package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
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
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Assignment
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableLongStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Date
import java.util.Locale
import java.util.TimeZone
import online.educoreng.educore.core.designsystem.component.EduCoreBottomSheet
import online.educoreng.educore.core.designsystem.component.EduCoreConfirmationDialog
import online.educoreng.educore.core.designsystem.component.EduCoreDatePicker
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreFilterChip
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseSectionCard
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTextField
import online.educoreng.educore.core.designsystem.component.EduCoreTimePicker
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.StaffCbtExamDto

@Composable
internal fun StaffCbtListScreen(
    state: StaffCbtUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onStatus: (String?) -> Unit,
    onOpen: (Long) -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.exams.isEmpty()) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Loading CBT examinations")
        return
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize().imePadding(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader("CBT Management", "Examinations available to your role", onBack) }
        item {
            EduCoreShowcaseHero(
                eyebrow = "COMPUTER-BASED TESTING",
                title = "Prepare, publish and monitor examinations.",
                subtitle = if (state.capabilities.fullAccess) {
                    "Full CBT management access"
                } else {
                    "Only subjects and classes assigned to you are shown"
                },
                trailing = {
                    Surface(color = EduCoreColors.Gold100, shape = MaterialTheme.shapes.large) {
                        Icon(Icons.Default.Assignment, null, Modifier.padding(EduCoreSpacing.Md), tint = EduCoreColors.Navy900)
                    }
                },
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item {
            Row(
                Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                CbtMetric("All", state.counts.all, EduCoreTone.Brand)
                CbtMetric("Draft", state.counts.draft, EduCoreTone.Neutral)
                CbtMetric("Published", state.counts.published, EduCoreTone.Success)
                CbtMetric("Closed", state.counts.closed, EduCoreTone.Danger)
            }
        }
        item {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                EduCoreSearchBar(state.query, onQuery, Modifier.weight(1f), "Search exam, subject or class")
                Spacer(Modifier.width(EduCoreSpacing.Sm))
                EduCorePrimaryButton("Search", onSearch)
            }
        }
        item {
            Row(
                Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                listOf(null to "All", "draft" to "Draft", "published" to "Published", "active" to "Active", "closed" to "Closed")
                    .forEach { (value, label) ->
                        EduCoreFilterChip(label, state.status == value, { onStatus(value) })
                    }
            }
        }
        if (state.exams.isEmpty()) {
            item {
                EduCoreEmptyState(
                    "No CBT examinations",
                    "No examination matches this filter, or none has been assigned to your permitted subjects/classes.",
                )
            }
        } else {
            items(state.exams, key = StaffCbtExamDto::id) { exam ->
                StaffCbtExamCard(exam) { onOpen(exam.id) }
            }
        }
        if (state.errorMessage != null && state.exams.isEmpty()) {
            item { EduCoreSecondaryButton("Retry", onRetry, Modifier.fillMaxWidth()) }
        }
        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

@Composable
internal fun StaffCbtDetailScreen(
    state: StaffCbtUiState,
    onBack: () -> Unit,
    onPublish: () -> Unit,
    onClose: () -> Unit,
    onReschedule: (start: String, end: String, duration: Int) -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.selectedExam == null) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Opening CBT examination")
        return
    }
    val exam = state.selectedExam ?: run {
        EduCoreErrorState(state.errorMessage ?: "This CBT examination is unavailable.", Modifier.fillMaxSize(), onRetry = onRetry)
        return
    }

    var confirmPublish by rememberSaveable(exam.id) { mutableStateOf(false) }
    var confirmClose by rememberSaveable(exam.id) { mutableStateOf(false) }
    var showReschedule by rememberSaveable(exam.id) { mutableStateOf(false) }
    var startMillis by rememberSaveable(exam.id) { mutableLongStateOf(cbtWallClockMillis(exam.scheduledStart)) }
    var endMillis by rememberSaveable(exam.id) { mutableLongStateOf(cbtWallClockMillis(exam.scheduledEnd, fallbackOffsetMinutes = 60)) }
    var duration by rememberSaveable(exam.id) { mutableStateOf(exam.durationMinutes.toString()) }
    var dateTarget by rememberSaveable(exam.id) { mutableStateOf<CbtScheduleTarget?>(null) }
    var timeTarget by rememberSaveable(exam.id) { mutableStateOf<CbtScheduleTarget?>(null) }

    EduCoreConfirmationDialog(
        visible = confirmPublish,
        title = "Publish examination?",
        message = "Students assigned to this examination will be able to access it once the configured schedule allows.",
        confirmLabel = "Publish",
        onConfirm = {
            confirmPublish = false
            onPublish()
        },
        onDismiss = { confirmPublish = false },
    )
    EduCoreConfirmationDialog(
        visible = confirmClose,
        title = "Close examination?",
        message = "Closing the examination prevents further submissions. Completed graded attempts will be synchronized to results.",
        confirmLabel = "Close exam",
        onConfirm = {
            confirmClose = false
            onClose()
        },
        onDismiss = { confirmClose = false },
        destructive = true,
    )

    EduCoreBottomSheet(
        visible = showReschedule,
        onDismiss = { showReschedule = false },
    ) {
        Text("Reschedule examination", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold, color = EduCoreColors.Navy900)
        Text(
            if (exam.status == "closed") "Saving this schedule will reopen the examination." else "Set the revised examination window and duration.",
            style = MaterialTheme.typography.bodyMedium,
            color = EduCoreColors.Slate600,
        )
        Spacer(Modifier.height(EduCoreSpacing.Lg))
        CbtSchedulePickerRow(
            label = "Start",
            millis = startMillis,
            enabled = !state.isSaving,
            onDate = { dateTarget = CbtScheduleTarget.START },
            onTime = { timeTarget = CbtScheduleTarget.START },
        )
        Spacer(Modifier.height(EduCoreSpacing.Md))
        CbtSchedulePickerRow(
            label = "End",
            millis = endMillis,
            enabled = !state.isSaving,
            onDate = { dateTarget = CbtScheduleTarget.END },
            onTime = { timeTarget = CbtScheduleTarget.END },
        )
        Spacer(Modifier.height(EduCoreSpacing.Md))
        EduCoreTextField(
            value = duration,
            onValueChange = { candidate -> if (candidate.length <= 4 && candidate.all(Char::isDigit)) duration = candidate },
            label = "Duration (minutes)",
            modifier = Modifier.fillMaxWidth(),
            enabled = !state.isSaving,
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
            supportingText = "Allowed range: 5–1440 minutes",
        )
        val durationValue = duration.toIntOrNull()
        val scheduleValid = durationValue != null && durationValue in 5..1440 && endMillis > startMillis
        if (endMillis <= startMillis) {
            Text("End time must be later than start time.", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Danger600)
        }
        Spacer(Modifier.height(EduCoreSpacing.Lg))
        EduCorePrimaryButton(
            text = if (exam.status == "closed") "Save and reopen" else "Save schedule",
            onClick = {
                val minutes = duration.toIntOrNull() ?: return@EduCorePrimaryButton
                showReschedule = false
                onReschedule(cbtApiDateTime(startMillis), cbtApiDateTime(endMillis), minutes)
            },
            modifier = Modifier.fillMaxWidth(),
            enabled = scheduleValid && !state.isSaving,
            loading = state.isSaving,
            leadingIcon = { Icon(Icons.Default.Schedule, null) },
        )
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        EduCoreSecondaryButton("Cancel", { showReschedule = false }, Modifier.fillMaxWidth(), enabled = !state.isSaving)
    }

    dateTarget?.let { target ->
        val current = if (target == CbtScheduleTarget.START) startMillis else endMillis
        EduCoreDatePicker(
            visible = true,
            initialDateMillis = cbtUtcDateMillis(current),
            onDateSelected = { selected ->
                if (target == CbtScheduleTarget.START) startMillis = cbtReplaceDate(startMillis, selected)
                else endMillis = cbtReplaceDate(endMillis, selected)
            },
            onDismiss = { dateTarget = null },
        )
    }
    timeTarget?.let { target ->
        val current = if (target == CbtScheduleTarget.START) startMillis else endMillis
        val calendar = Calendar.getInstance().apply { timeInMillis = current }
        EduCoreTimePicker(
            visible = true,
            initialHour = calendar.get(Calendar.HOUR_OF_DAY),
            initialMinute = calendar.get(Calendar.MINUTE),
            onTimeSelected = { hour, minute ->
                if (target == CbtScheduleTarget.START) startMillis = cbtReplaceTime(startMillis, hour, minute)
                else endMillis = cbtReplaceTime(endMillis, hour, minute)
            },
            onDismiss = { timeTarget = null },
        )
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize().imePadding(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader(exam.title, "CBT Management", onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreStatusBadge(exam.status.replaceFirstChar { it.uppercase() }, exam.status.cbtTone())
                exam.subject?.let { EduCoreStatusBadge(it.name, EduCoreTone.Brand) }
            }
        }
        item {
            EduCoreShowcaseSectionCard {
                Text("Exam overview", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = EduCoreColors.Navy900)
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                CbtLine("Class level", exam.classLevel?.name ?: "Not specified")
                CbtLine("Classes", exam.classes.joinToString { it.name }.ifBlank { "Not specified" })
                CbtLine("Term", listOfNotNull(exam.term?.name, exam.term?.session).joinToString(" · ").ifBlank { "Not specified" })
                CbtLine("Questions", exam.totalQuestions.toString())
                CbtLine("Total marks", exam.totalMarks.toString())
                CbtLine("Duration", "${exam.durationMinutes} minutes")
                CbtLine("Starts", exam.scheduledStart ?: "Not scheduled")
                CbtLine("Ends", exam.scheduledEnd ?: "Not scheduled")
            }
        }
        exam.attempts?.let { attempts ->
            item {
                EduCoreShowcaseSectionCard {
                    Text("Student attempts", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = EduCoreColors.Navy900)
                    Spacer(Modifier.height(EduCoreSpacing.Sm))
                    CbtLine("Started", attempts.total.toString())
                    CbtLine("Submitted", attempts.submitted.toString())
                    CbtLine("Graded", attempts.graded.toString())
                }
            }
        }
        if (exam.sections.isNotEmpty()) {
            item {
                EduCoreShowcaseSectionCard {
                    Text("Sections", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = EduCoreColors.Navy900)
                    exam.sections.sortedBy { it.displayOrder }.forEachIndexed { index, section ->
                        Text("${index + 1}. ${section.title}", style = MaterialTheme.typography.bodyLarge, color = EduCoreColors.Ink900)
                    }
                }
            }
        }
        item {
            EduCoreShowcaseSectionCard {
                Text("Permitted actions", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = EduCoreColors.Navy900)
                Spacer(Modifier.height(EduCoreSpacing.Md))
                if (exam.canPublish) {
                    EduCorePrimaryButton(
                        "Publish exam",
                        { confirmPublish = true },
                        Modifier.fillMaxWidth(),
                        enabled = !state.isSaving,
                        loading = state.isSaving,
                        leadingIcon = { Icon(Icons.Default.CheckCircle, null) },
                    )
                }
                if (exam.canClose) {
                    Spacer(Modifier.height(EduCoreSpacing.Sm))
                    EduCorePrimaryButton(
                        "Close exam",
                        { confirmClose = true },
                        Modifier.fillMaxWidth(),
                        enabled = !state.isSaving,
                        loading = state.isSaving,
                        leadingIcon = { Icon(Icons.Default.Lock, null) },
                    )
                }
                if (exam.canReschedule) {
                    Spacer(Modifier.height(EduCoreSpacing.Sm))
                    EduCoreSecondaryButton(
                        "Reschedule exam",
                        {
                            startMillis = cbtWallClockMillis(exam.scheduledStart)
                            endMillis = cbtWallClockMillis(exam.scheduledEnd, fallbackOffsetMinutes = 60)
                            duration = exam.durationMinutes.coerceAtLeast(5).toString()
                            showReschedule = true
                        },
                        Modifier.fillMaxWidth(),
                        enabled = !state.isSaving,
                    )
                }
                if (!exam.canPublish && !exam.canClose && !exam.canReschedule) {
                    Text("No state-changing action is currently permitted for this exam.", color = EduCoreColors.Slate600)
                }
            }
        }
        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

@Composable
private fun CbtSchedulePickerRow(
    label: String,
    millis: Long,
    enabled: Boolean,
    onDate: () -> Unit,
    onTime: () -> Unit,
) {
    Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
        Text(label, style = MaterialTheme.typography.labelLarge, color = EduCoreColors.Navy900)
        Text(cbtDisplayDateTime(millis), style = MaterialTheme.typography.bodyLarge, fontWeight = FontWeight.SemiBold, color = EduCoreColors.Ink900)
        Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            EduCoreSecondaryButton("Choose date", onDate, Modifier.weight(1f), enabled)
            EduCoreSecondaryButton("Choose time", onTime, Modifier.weight(1f), enabled)
        }
    }
}

@Composable
private fun StaffCbtExamCard(exam: StaffCbtExamDto, onClick: () -> Unit) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
        shape = MaterialTheme.shapes.large,
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f)) {
                    Text(exam.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold, maxLines = 2, overflow = TextOverflow.Ellipsis)
                    Text(
                        listOfNotNull(exam.subject?.name, exam.classLevel?.name).joinToString(" · ").ifBlank { "CBT examination" },
                        style = MaterialTheme.typography.bodySmall,
                        color = EduCoreColors.Slate600,
                    )
                }
                EduCoreStatusBadge(exam.status.replaceFirstChar { it.uppercase() }, exam.status.cbtTone())
            }
            Text(
                "${exam.totalQuestions} questions · ${exam.durationMinutes} min · ${exam.attemptsCount} attempts",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
        }
    }
}

@Composable
private fun CbtMetric(label: String, value: Int, tone: EduCoreTone) {
    Surface(color = EduCoreColors.White, shape = MaterialTheme.shapes.large, border = BorderStroke(1.dp, EduCoreColors.Line200)) {
        Column(Modifier.width(104.dp).padding(EduCoreSpacing.Md)) {
            Text(value.toString(), style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold, color = EduCoreColors.Navy900)
            EduCoreStatusBadge(label, tone)
        }
    }
}

@Composable
private fun CbtLine(label: String, value: String) {
    Row(Modifier.fillMaxWidth().padding(vertical = EduCoreSpacing.Xs)) {
        Text(label, Modifier.weight(1f), style = MaterialTheme.typography.bodyMedium, color = EduCoreColors.Slate600)
        Text(value, Modifier.weight(1.4f), style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.SemiBold, color = EduCoreColors.Ink900)
    }
}

private enum class CbtScheduleTarget { START, END }

private fun cbtWallClockMillis(raw: String?, fallbackOffsetMinutes: Int = 0): Long {
    val fallback = System.currentTimeMillis() + fallbackOffsetMinutes * 60_000L
    val normalized = raw?.trim()?.takeIf(String::isNotEmpty)?.take(16) ?: return fallback
    return runCatching {
        SimpleDateFormat("yyyy-MM-dd'T'HH:mm", Locale.US).apply { isLenient = false }.parse(normalized)?.time
    }.getOrNull() ?: fallback
}

private fun cbtDisplayDateTime(millis: Long): String =
    SimpleDateFormat("EEE, d MMM yyyy · HH:mm", Locale.getDefault()).format(Date(millis))

private fun cbtApiDateTime(millis: Long): String =
    SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.US).format(Date(millis))

private fun cbtUtcDateMillis(localMillis: Long): Long {
    val local = Calendar.getInstance().apply { timeInMillis = localMillis }
    return Calendar.getInstance(TimeZone.getTimeZone("UTC")).apply {
        clear()
        set(local.get(Calendar.YEAR), local.get(Calendar.MONTH), local.get(Calendar.DAY_OF_MONTH), 0, 0, 0)
    }.timeInMillis
}

private fun cbtReplaceDate(existingMillis: Long, selectedUtcDateMillis: Long): Long {
    val selected = Calendar.getInstance(TimeZone.getTimeZone("UTC")).apply { timeInMillis = selectedUtcDateMillis }
    return Calendar.getInstance().apply {
        timeInMillis = existingMillis
        set(Calendar.YEAR, selected.get(Calendar.YEAR))
        set(Calendar.MONTH, selected.get(Calendar.MONTH))
        set(Calendar.DAY_OF_MONTH, selected.get(Calendar.DAY_OF_MONTH))
        set(Calendar.SECOND, 0)
        set(Calendar.MILLISECOND, 0)
    }.timeInMillis
}

private fun cbtReplaceTime(existingMillis: Long, hour: Int, minute: Int): Long =
    Calendar.getInstance().apply {
        timeInMillis = existingMillis
        set(Calendar.HOUR_OF_DAY, hour)
        set(Calendar.MINUTE, minute)
        set(Calendar.SECOND, 0)
        set(Calendar.MILLISECOND, 0)
    }.timeInMillis

private fun String.cbtTone(): EduCoreTone = when (lowercase()) {
    "published", "active" -> EduCoreTone.Success
    "closed" -> EduCoreTone.Danger
    "draft" -> EduCoreTone.Neutral
    else -> EduCoreTone.Warning
}
