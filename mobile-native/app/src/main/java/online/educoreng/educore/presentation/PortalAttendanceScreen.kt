package online.educoreng.educore.presentation

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
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import online.educoreng.educore.core.designsystem.component.EduCoreDashboardCard
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreFilterChip
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreWarningBanner
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.PortalAttendanceProgramme
import online.educoreng.educore.core.model.PortalAttendanceRecord
import online.educoreng.educore.core.model.PortalAttendanceSection
import online.educoreng.educore.core.model.PortalAttendanceSummary

@Composable
fun PortalAttendanceScreen(
    state: PortalAttendanceUiState,
    onBack: () -> Unit,
    onChild: (Long) -> Unit,
    onTerm: (Long) -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.workspace == null) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading attendance")
    }

    val workspace = state.workspace ?: return EduCoreErrorState(
        state.errorMessage ?: "Attendance is unavailable.",
        Modifier.fillMaxSize(),
        onRetry = onRetry,
    )

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Attendance",
                subtitle = listOfNotNull(
                    workspace.student.name,
                    workspace.student.admissionNumber,
                ).joinToString(" · "),
                onBack = onBack,
            )
        }

        if (workspace.isFromCache) {
            item {
                EduCoreWarningBanner(
                    "Showing the latest attendance saved on this device. Connect to the internet to refresh."
                )
            }
        }

        state.errorMessage?.let { message ->
            item { EduCoreErrorBanner(message) }
        }

        if (workspace.children.size > 1) {
            item {
                Row(
                    Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                ) {
                    workspace.children.forEach { child ->
                        EduCoreFilterChip(
                            text = child.name.substringBefore(" "),
                            selected = child.id == workspace.student.id,
                            onClick = { onChild(child.id) },
                        )
                    }
                }
            }
        }

        if (workspace.terms.isNotEmpty()) {
            item {
                Row(
                    Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                ) {
                    workspace.terms.forEach { term ->
                        EduCoreFilterChip(
                            text = term.name,
                            selected = term.id == workspace.selectedTermId,
                            onClick = { onTerm(term.id) },
                        )
                    }
                }
            }
        }

        item {
            AttendanceSectionCard(
                title = "Conventional Attendance",
                supporting = "Daily attendance recorded in the learner's conventional class.",
                section = workspace.conventional,
            )
        }

        if (workspace.parallelProgrammes.isEmpty()) {
            item {
                EduCoreEmptyState(
                    title = "No parallel attendance",
                    message = "No parallel curriculum attendance has been recorded for the selected term.",
                )
            }
        } else {
            workspace.parallelProgrammes.forEach { programme ->
                item(key = "parallel-attendance-${programme.curriculumId}") {
                    ParallelAttendanceProgrammeCard(programme)
                }
            }
        }

        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

@Composable
private fun ParallelAttendanceProgrammeCard(programme: PortalAttendanceProgramme) {
    AttendanceSectionCard(
        title = programme.curriculumName,
        supporting = listOfNotNull(programme.className, programme.armName).joinToString(" · "),
        section = PortalAttendanceSection(programme.stats, programme.records),
    )
}

@Composable
private fun AttendanceSectionCard(
    title: String,
    supporting: String,
    section: PortalAttendanceSection,
) {
    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text(title, style = MaterialTheme.typography.titleMedium, color = EduCoreColors.Navy900)
        if (supporting.isNotBlank()) {
            Text(supporting, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
        }
        Spacer(Modifier.height(EduCoreSpacing.Md))
        AttendanceSummaryBlock(section.stats)

        if (section.records.isEmpty()) {
            Spacer(Modifier.height(EduCoreSpacing.Md))
            Text(
                "No attendance records for this term.",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
        } else {
            section.records.forEach { record ->
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                HorizontalDivider()
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                AttendanceRecordRow(record)
            }
        }
    }
}

@Composable
private fun AttendanceSummaryBlock(stats: PortalAttendanceSummary) {
    Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
        SummaryLine("Recorded days", stats.total.toString())
        SummaryLine("Present", stats.present.toString())
        SummaryLine("Absent", stats.absent.toString())
        SummaryLine("Late", stats.late.toString())
        SummaryLine("Excused", stats.excused.toString())
        SummaryLine("Attendance rate", "${stats.rate}%")
    }
}

@Composable
private fun SummaryLine(label: String, value: String) {
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
        Text(label, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
        Text(value, style = MaterialTheme.typography.labelLarge, color = EduCoreColors.Navy900)
    }
}

@Composable
private fun AttendanceRecordRow(record: PortalAttendanceRecord) {
    Row(
        modifier = Modifier.fillMaxWidth().padding(vertical = EduCoreSpacing.Xs),
        horizontalArrangement = Arrangement.SpaceBetween,
    ) {
        Column(Modifier.weight(1f)) {
            Text(record.date ?: "Date unavailable", style = MaterialTheme.typography.bodyMedium)
            record.remark?.takeIf(String::isNotBlank)?.let {
                Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
        }
        Text(
            record.status.replaceFirstChar { it.uppercase() },
            style = MaterialTheme.typography.labelLarge,
            fontWeight = FontWeight.SemiBold,
            color = EduCoreColors.Navy900,
        )
    }
}
