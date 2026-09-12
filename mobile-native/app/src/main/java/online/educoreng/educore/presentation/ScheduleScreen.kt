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
import androidx.compose.material.icons.filled.AssignmentTurnedIn
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreFilterChip
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
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

/** Native schedule workspace. Counts are derived from the same records rendered below. */
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
    val totalPeriods = workspace.week.sumOf { it.periods.size }
    val dutyCount = workspace.duties.size
    val examCount = workspace.exams.size

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
                    value = totalPeriods.toString(),
                    icon = Icons.Default.Schedule,
                    tone = EduCoreTone.Brand,
                    modifier = Modifier.weight(1f),
                )
                EduCoreShowcaseStat(
                    label = "Duties",
                    value = dutyCount.toString(),
                    icon = Icons.Default.AssignmentTurnedIn,
                    tone = EduCoreTone.Brand,
                    modifier = Modifier.weight(1f),
                )
                EduCoreShowcaseStat(
                    label = "Exams",
                    value = examCount.toString(),
                    icon = Icons.Default.CalendarMonth,
                    tone = EduCoreTone.Info,
                    modifier = Modifier.weight(1f),
                )
            }
        }

        item {
            Row(
                modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                ScheduleSectionTab("Timetable ($totalPeriods)", state.selectedSection == 0) { onSection(0) }
                ScheduleSectionTab("Exam Duties ($dutyCount)", state.selectedSection == 1) { onSection(1) }
                ScheduleSectionTab("Examinations ($examCount)", state.selectedSection == 2) { onSection(2) }
            }
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
                                label = "${day.day.take(3)} (${day.periods.size})",
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
                if (workspace.duties.isEmpty()) {
                    item {
                        EduCoreEmptyState(
                            "No supervision duties",
                            "No examination duty is assigned within this schedule range.",
                        )
                    }
                }
                items(workspace.duties, key = ExamDuty::id) { DutyListRow(it) }
            }

            else -> {
                if (workspace.exams.isEmpty()) {
                    item {
                        EduCoreEmptyState(
                            "No examinations",
                            "No examination falls within this schedule range.",
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
private fun ScheduleSectionTab(label: String, selected: Boolean, onClick: () -> Unit) {
    Surface(
        onClick = onClick,
        color = EduCoreColors.White,
        contentColor = EduCoreColors.Navy900,
        shape = MaterialTheme.shapes.large,
        border = BorderStroke(if (selected) 1.5.dp else 1.dp, if (selected) EduCoreColors.Navy700 else EduCoreColors.Line300),
    ) {
        Text(
            text = label,
            modifier = Modifier.padding(horizontal = EduCoreSpacing.Md, vertical = EduCoreSpacing.Sm),
            style = MaterialTheme.typography.labelLarge,
            fontWeight = if (selected) FontWeight.SemiBold else FontWeight.Normal,
        )
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
private fun DutyListRow(duty: ExamDuty) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
        shape = MaterialTheme.shapes.large,
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            DutyField("Date", duty.date.ifBlank { "Not set" })
            DutyField("Class", duty.classLevel?.takeIf(String::isNotBlank) ?: "Not assigned")
            DutyField("Subject", duty.subject.ifBlank { "Not assigned" })
            val time = listOfNotNull(duty.startTime, duty.endTime).filter(String::isNotBlank).joinToString(" – ")
            if (time.isNotBlank()) DutyField("Time", time)
            duty.venue?.takeIf(String::isNotBlank)?.let { DutyField("Venue", it) }
        }
    }
}

@Composable
private fun DutyField(label: String, value: String) {
    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
        Text(
            text = label,
            modifier = Modifier.width(62.dp),
            style = MaterialTheme.typography.labelMedium,
            color = EduCoreColors.Slate600,
        )
        Text(
            text = value,
            modifier = Modifier.weight(1f),
            style = MaterialTheme.typography.bodyMedium,
            color = EduCoreColors.Ink900,
        )
    }
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
                color = EduCoreColors.SurfaceBlue50,
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
