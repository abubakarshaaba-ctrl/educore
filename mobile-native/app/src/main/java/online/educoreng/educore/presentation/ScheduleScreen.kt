package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.AssignmentTurnedIn
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreFilterChip
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSegmentedControl
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseStat
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.component.EduCoreWarningBanner
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.ExamDuty
import online.educoreng.educore.core.model.SchedulePeriod
import online.educoreng.educore.core.model.ScheduledExam

/**
 * Staff schedule workspace aligned with the approved August EduCore concept.
 *
 * Timetable, examination duties and published examinations are native views over
 * the server-scoped schedule payload. Changing visual order never broadens access.
 */
@Composable
internal fun ScheduleScreen(
    state: ScheduleUiState,
    onBack: () -> Unit,
    onSection: (Int) -> Unit,
    onDay: (String) -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.workspace == null) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading schedule")
    }
    val workspace = state.workspace ?: return EduCoreErrorState(
        state.errorMessage ?: "The schedule is unavailable.",
        Modifier.fillMaxSize(),
        onRetry = onRetry,
    )
    val selectedPeriods = workspace.week.firstOrNull { it.day == state.selectedDay }?.periods.orEmpty()

    LazyColumn(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCoreShowcaseHero(
                eyebrow = "TIMETABLE & EXAM DUTIES",
                title = "Your schedule, at a glance.",
                subtitle = listOfNotNull(workspace.className, workspace.termName, workspace.sessionName)
                    .filter(String::isNotBlank)
                    .distinct()
                    .joinToString(" · ")
                    .ifBlank { workspace.title },
                actions = {
                    IconButton(onClick = onBack) {
                        Icon(
                            Icons.AutoMirrored.Filled.ArrowBack,
                            contentDescription = "Back",
                            tint = Color.White,
                        )
                    }
                },
            )
        }

        if (workspace.isFromCache) {
            item { EduCoreWarningBanner("Showing the latest schedule saved on this device.") }
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }

        item {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                EduCoreShowcaseStat(
                    label = "Periods",
                    value = selectedPeriods.size.toString(),
                    icon = Icons.Default.Schedule,
                    tone = EduCoreTone.Brand,
                    modifier = Modifier.weight(1f),
                )
                EduCoreShowcaseStat(
                    label = "Duties",
                    value = workspace.duties.size.toString(),
                    icon = Icons.Default.AssignmentTurnedIn,
                    tone = EduCoreTone.Accent,
                    modifier = Modifier.weight(1f),
                )
                EduCoreShowcaseStat(
                    label = "Exams",
                    value = workspace.exams.size.toString(),
                    icon = Icons.Default.CalendarMonth,
                    tone = EduCoreTone.Info,
                    modifier = Modifier.weight(1f),
                )
            }
        }

        item {
            EduCoreSegmentedControl(
                options = listOf(
                    "Timetable",
                    "Exam Duties (${workspace.duties.size})",
                    "Examinations (${workspace.exams.size})",
                ),
                selectedIndex = state.selectedSection,
                onSelected = onSection,
                modifier = Modifier.fillMaxWidth(),
            )
        }

        when (state.selectedSection) {
            0 -> {
                item {
                    EduCoreSectionHeader(
                        title = "Weekly timetable",
                        supportingText = "Choose a day to review your authorised teaching periods",
                    )
                }
                item {
                    Row(
                        modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    ) {
                        workspace.week.forEach { day ->
                            EduCoreFilterChip(
                                label = day.day.take(3),
                                selected = day.day == state.selectedDay,
                                onClick = { onDay(day.day) },
                            )
                        }
                    }
                }
                if (selectedPeriods.isEmpty()) {
                    item {
                        EduCoreEmptyState(
                            "No timetable periods",
                            "There are no timetable periods for ${state.selectedDay}.",
                        )
                    }
                }
                items(selectedPeriods, key = SchedulePeriod::id) { PeriodCard(it) }
            }

            1 -> {
                item { DateRangeBanner(workspace.from, workspace.to, "My supervision duties") }
                item {
                    EduCoreSectionHeader(
                        title = "Exam duties",
                        supportingText = "Only published duties assigned to this account are shown",
                    )
                }
                if (workspace.duties.isEmpty()) {
                    item {
                        EduCoreEmptyState(
                            "No supervision duties",
                            "No published examination duty is assigned within this schedule range.",
                        )
                    }
                }
                items(workspace.duties, key = ExamDuty::id) { DutyCard(it) }
            }

            else -> {
                item { DateRangeBanner(workspace.from, workspace.to, "Published examinations") }
                item {
                    EduCoreSectionHeader(
                        title = "Examinations",
                        supportingText = "Published examinations available in your current schedule scope",
                    )
                }
                if (workspace.exams.isEmpty()) {
                    item {
                        EduCoreEmptyState(
                            "No published examinations",
                            "No published examination falls within this schedule range.",
                        )
                    }
                }
                items(workspace.exams, key = ScheduledExam::id) { ExamCard(it) }
            }
        }

        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

@Composable
private fun PeriodCard(period: SchedulePeriod) {
    ScheduleCard(
        title = period.subject,
        supporting = listOfNotNull(period.className, period.teacher).joinToString(" · "),
        date = null,
        time = listOfNotNull(period.startTime, period.endTime).joinToString(" – "),
        venue = period.venue,
        badge = "Class",
        tone = EduCoreTone.Brand,
    )
}

@Composable
private fun ExamCard(exam: ScheduledExam) {
    ScheduleCard(
        title = exam.subject,
        supporting = listOfNotNull(exam.title, exam.session, exam.classLevel).joinToString(" · "),
        date = exam.date,
        time = listOfNotNull(exam.startTime, exam.endTime).joinToString(" – "),
        venue = exam.venue,
        badge = "Examination",
        tone = EduCoreTone.Info,
    )
}

@Composable
private fun DutyCard(duty: ExamDuty) {
    ScheduleCard(
        title = duty.subject,
        supporting = listOfNotNull(duty.examTitle, duty.session, duty.classLevel).joinToString(" · "),
        date = duty.date,
        time = listOfNotNull(duty.startTime, duty.endTime).joinToString(" – "),
        venue = duty.venue,
        badge = "Duty",
        tone = EduCoreTone.Accent,
    )
}

@Composable
private fun ScheduleCard(
    title: String,
    supporting: String,
    date: String?,
    time: String,
    venue: String?,
    badge: String,
    tone: EduCoreTone,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
        shape = MaterialTheme.shapes.large,
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            Surface(
                shape = MaterialTheme.shapes.medium,
                color = EduCoreColors.Gold50,
                contentColor = EduCoreColors.Navy900,
            ) {
                Icon(
                    Icons.Default.CalendarMonth,
                    contentDescription = null,
                    modifier = Modifier.padding(EduCoreSpacing.Md),
                )
            }
            Column(
                modifier = Modifier.weight(1f),
                verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs),
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Text(
                        title,
                        modifier = Modifier.weight(1f),
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.SemiBold,
                        color = EduCoreColors.Ink900,
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis,
                    )
                    Spacer(Modifier.width(EduCoreSpacing.Sm))
                    EduCoreStatusBadge(badge, tone)
                }
                if (supporting.isNotBlank()) {
                    Text(
                        supporting,
                        color = EduCoreColors.Slate600,
                        style = MaterialTheme.typography.bodySmall,
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis,
                    )
                }
                val timing = listOfNotNull(date, time.takeIf(String::isNotBlank)).joinToString(" · ")
                if (timing.isNotBlank()) {
                    Text(
                        timing,
                        color = EduCoreColors.Navy900,
                        style = MaterialTheme.typography.bodyMedium,
                        fontWeight = FontWeight.SemiBold,
                    )
                }
                venue?.takeIf(String::isNotBlank)?.let {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(
                            Icons.Default.LocationOn,
                            contentDescription = null,
                            modifier = Modifier.width(14.dp),
                            tint = EduCoreColors.Muted500,
                        )
                        Text(
                            it,
                            color = EduCoreColors.Muted500,
                            style = MaterialTheme.typography.bodySmall,
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun DateRangeBanner(from: String, to: String, title: String) {
    Surface(
        modifier = Modifier.fillMaxWidth(),
        color = EduCoreColors.Gold50,
        shape = MaterialTheme.shapes.large,
        border = BorderStroke(1.dp, EduCoreColors.Gold200),
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs),
        ) {
            Text(
                title,
                style = MaterialTheme.typography.labelLarge,
                color = EduCoreColors.Navy900,
                fontWeight = FontWeight.SemiBold,
            )
            Text(
                "$from to $to",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
        }
    }
}
