package online.educoreng.educore.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import online.educoreng.educore.core.designsystem.component.EduCoreDashboardCard
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.ParallelAttendanceDraft
import online.educoreng.educore.core.model.ParallelWorkingDayDraft

@Composable
fun ParallelCurriculumOperationsScreen(
    state: ParallelLifecycleUiState,
    onBack: () -> Unit,
    onLoadContext: (Long?, Long?, Long?, Long?, Long?, String?) -> Unit,
    onCreateTimetablePeriod: (Long, Long, Long, String, String, String, String?) -> Unit,
    onDeleteTimetablePeriod: (Long) -> Unit,
    onSaveWorkingDays: (List<ParallelWorkingDayDraft>) -> Unit,
    onClockInParallelStaff: () -> Unit,
    onClockOutParallelStaff: () -> Unit,
    onSaveParallelAttendance: (List<ParallelAttendanceDraft>) -> Unit,
    onDownloadAttendanceExport: (String) -> Unit,
    onDocumentOpened: () -> Unit,
) {
    OpenDocumentEffect(state.downloadedDocument, onDocumentOpened)
    LaunchedEffect(Unit) {
        val selected = state.operationsWorkspace?.selected
        onLoadContext(
            selected?.curriculumId,
            selected?.sessionId,
            selected?.classId,
            selected?.armId,
            selected?.termId,
            selected?.date,
        )
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        val operations = state.operationsWorkspace
        item {
            EduCorePageHeader(
                title = "Parallel Timetable & Attendance",
                subtitle = listOfNotNull(
                    operations?.curricula?.firstOrNull { it.id == operations.selected.curriculumId }?.name,
                    operations?.sessions?.firstOrNull { it.id == operations.selected.sessionId }?.name,
                ).joinToString(" · "),
                onBack = onBack,
            )
        }

        state.errorMessage?.let {
            item { EduCoreErrorBanner(title = "Parallel Timetable & Attendance", message = it) }
        }


        if (state.isOperationsLoading && operations == null) {
            item { EduCoreLoadingState(message = "Loading parallel timetable and attendance") }
            return@LazyColumn
        }

        if (operations == null) {
            item {
                EduCoreDashboardCard(Modifier.fillMaxWidth()) {
                    Text(
                        "Parallel timetable and attendance could not be loaded.",
                        style = MaterialTheme.typography.bodyMedium,
                    )
                    Spacer(Modifier.height(EduCoreSpacing.Md))
                    EduCoreSecondaryButton(
                        text = "Retry",
                        onClick = { onLoadContext(null, null, null, null, null, null) },
                        modifier = Modifier.fillMaxWidth(),
                    )
                }
            }
            return@LazyColumn
        }

        item {
            EduCoreDashboardCard(Modifier.fillMaxWidth()) {
                Text("Programme context", style = MaterialTheme.typography.titleMedium)
                Text(
                    "Choose the parallel programme and academic session. Class arm, term and date are selected below.",
                    style = MaterialTheme.typography.bodySmall,
                    color = EduCoreColors.Slate600,
                )
                Spacer(Modifier.height(EduCoreSpacing.Md))

                OperationsContextMenu(
                    label = "Parallel programme",
                    current = operations.curricula
                        .firstOrNull { it.id == operations.selected.curriculumId }
                        ?.name ?: "Select programme",
                    options = operations.curricula.map { it.id to it.name },
                    enabled = !state.isOperationsLoading && !state.isMutating,
                ) { curriculumId ->
                    onLoadContext(
                        curriculumId,
                        operations.selected.sessionId,
                        null,
                        null,
                        null,
                        operations.selected.date,
                    )
                }

                Spacer(Modifier.height(EduCoreSpacing.Sm))
                OperationsContextMenu(
                    label = "Academic session",
                    current = operations.sessions
                        .firstOrNull { it.id == operations.selected.sessionId }
                        ?.name ?: "Select session",
                    options = operations.sessions.map { it.id to it.name },
                    enabled = !state.isOperationsLoading && !state.isMutating,
                ) { sessionId ->
                    onLoadContext(
                        operations.selected.curriculumId,
                        sessionId,
                        operations.selected.classId,
                        operations.selected.armId,
                        null,
                        operations.selected.date,
                    )
                }
            }
        }

        item {
            ParallelOperationsPanel(
                state = state,
                onLoadOperations = { classId, armId, termId, date ->
                    val selected = state.operationsWorkspace?.selected
                    onLoadContext(
                        selected?.curriculumId,
                        selected?.sessionId,
                        classId,
                        armId,
                        termId,
                        date,
                    )
                },
                onCreateTimetablePeriod = onCreateTimetablePeriod,
                onDeleteTimetablePeriod = onDeleteTimetablePeriod,
                onSaveWorkingDays = onSaveWorkingDays,
                onClockInParallelStaff = onClockInParallelStaff,
                onClockOutParallelStaff = onClockOutParallelStaff,
                onSaveParallelAttendance = onSaveParallelAttendance,
                onDownloadAttendanceExport = onDownloadAttendanceExport,
            )
        }
    }
}

@Composable
private fun OperationsContextMenu(
    label: String,
    current: String,
    options: List<Pair<Long, String>>,
    enabled: Boolean,
    onSelect: (Long) -> Unit,
) {
    var expanded by remember { mutableStateOf(false) }

    Column(Modifier.fillMaxWidth()) {
        Text(label, style = MaterialTheme.typography.labelLarge, color = EduCoreColors.Slate700)
        Spacer(Modifier.height(EduCoreSpacing.Xs))
        Box(Modifier.fillMaxWidth()) {
            EduCoreSecondaryButton(
                text = current,
                onClick = { expanded = true },
                modifier = Modifier.fillMaxWidth(),
                enabled = enabled && options.isNotEmpty(),
            )
            DropdownMenu(
                expanded = expanded,
                onDismissRequest = { expanded = false },
            ) {
                options.forEach { option ->
                    DropdownMenuItem(
                        text = { Text(option.second) },
                        onClick = {
                            expanded = false
                            onSelect(option.first)
                        },
                    )
                }
            }
        }
    }
}
