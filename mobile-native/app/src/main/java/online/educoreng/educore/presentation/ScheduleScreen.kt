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
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.LocationOn
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreFilterChip
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreSegmentedControl
import online.educoreng.educore.core.designsystem.component.EduCoreWarningBanner
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.model.ExamDuty
import online.educoreng.educore.core.model.SchedulePeriod
import online.educoreng.educore.core.model.ScheduledExam

@Composable
internal fun ScheduleScreen(
    state: ScheduleUiState,
    onBack: () -> Unit,
    onSection: (Int) -> Unit,
    onDay: (String) -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.workspace == null) return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading schedule")
    val workspace = state.workspace ?: return EduCoreErrorState(
        state.errorMessage ?: "The schedule is unavailable.",
        Modifier.fillMaxSize(),
        onRetry = onRetry,
    )

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                workspace.title,
                listOfNotNull(workspace.className, workspace.termName, workspace.sessionName).distinct().joinToString(" · "),
                onBack = onBack,
            )
        }
        if (workspace.isFromCache) item { EduCoreWarningBanner("Showing the latest schedule saved on this device.") }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item {
            EduCoreSegmentedControl(
                options = listOf("Timetable", "Exams (${workspace.exams.size})", "Duties (${workspace.duties.size})"),
                selectedIndex = state.selectedSection,
                onSelected = onSection,
                modifier = Modifier.fillMaxWidth(),
            )
        }
        when (state.selectedSection) {
            0 -> {
                item {
                    Row(
                        Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    ) {
                        workspace.week.forEach { day ->
                            EduCoreFilterChip(day.day.take(3), day.day == state.selectedDay, { onDay(day.day) })
                        }
                    }
                }
                val periods = workspace.week.firstOrNull { it.day == state.selectedDay }?.periods.orEmpty()
                if (periods.isEmpty()) item { EduCoreEmptyState("No periods", "There are no timetable periods for ${state.selectedDay}.") }
                items(periods, key = SchedulePeriod::id) { PeriodCard(it) }
            }
            1 -> {
                item { DateRangeBanner(workspace.from, workspace.to) }
                if (workspace.exams.isEmpty()) item { EduCoreEmptyState("No published examinations", "No published examination falls within this schedule range.") }
                items(workspace.exams, key = ScheduledExam::id) { ExamCard(it) }
            }
            else -> {
                item { DateRangeBanner(workspace.from, workspace.to) }
                if (workspace.duties.isEmpty()) item { EduCoreEmptyState("No supervision duties", "No published examination duty is assigned within this schedule range.") }
                items(workspace.duties, key = ExamDuty::id) { DutyCard(it) }
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
    )
}

@Composable
private fun ExamCard(exam: ScheduledExam) {
    ScheduleCard(exam.subject, listOfNotNull(exam.title, exam.session, exam.classLevel).joinToString(" · "), exam.date, listOfNotNull(exam.startTime, exam.endTime).joinToString(" – "), exam.venue)
}

@Composable
private fun DutyCard(duty: ExamDuty) {
    ScheduleCard(duty.subject, listOfNotNull(duty.examTitle, duty.session, duty.classLevel).joinToString(" · "), duty.date, listOfNotNull(duty.startTime, duty.endTime).joinToString(" – "), duty.venue)
}

@Composable
private fun ScheduleCard(title: String, supporting: String, date: String?, time: String, venue: String?) {
    Card(colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
        Row(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalAlignment = Alignment.CenterVertically) {
            Surface(shape = MaterialTheme.shapes.medium, color = EduCoreColors.Info100) {
                Icon(Icons.Default.CalendarMonth, null, Modifier.padding(EduCoreSpacing.Md), tint = EduCoreColors.Navy900)
            }
            Spacer(Modifier.width(EduCoreSpacing.Md))
            Column(Modifier.weight(1f)) {
                Text(title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                if (supporting.isNotBlank()) Text(supporting, color = EduCoreColors.Slate600, style = MaterialTheme.typography.bodySmall)
                Text(listOfNotNull(date, time.takeIf(String::isNotBlank)).joinToString(" · "), color = EduCoreColors.Navy900)
                venue?.takeIf(String::isNotBlank)?.let {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.LocationOn, null, Modifier.width(14.dp), tint = EduCoreColors.Muted500)
                        Text(it, color = EduCoreColors.Muted500, style = MaterialTheme.typography.bodySmall)
                    }
                }
            }
        }
    }
}

@Composable
private fun DateRangeBanner(from: String, to: String) {
    Surface(color = EduCoreColors.Info100, shape = MaterialTheme.shapes.medium) {
        Text("Published schedule · $from to $to", Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), color = EduCoreColors.Navy900)
    }
}
