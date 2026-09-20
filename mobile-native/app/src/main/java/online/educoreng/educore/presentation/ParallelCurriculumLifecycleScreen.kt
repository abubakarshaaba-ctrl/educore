package online.educoreng.educore.presentation

import android.provider.OpenableColumns
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyListScope
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Checkbox
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Switch
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
import online.educoreng.educore.core.designsystem.component.EduCoreDangerButton
import online.educoreng.educore.core.designsystem.component.EduCoreDashboardCard
import online.educoreng.educore.core.designsystem.component.EduCoreConfirmationDialog
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreInfoBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreTabs
import online.educoreng.educore.core.designsystem.component.EduCoreTextField
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.ParallelLifecycleArm
import online.educoreng.educore.core.model.ParallelLifecycleClass
import online.educoreng.educore.core.model.ParallelLifecycleStaff
import online.educoreng.educore.core.model.ParallelLifecycleSubjectAssignment
import online.educoreng.educore.core.model.ParallelLifecycleWorkspace
import online.educoreng.educore.core.model.ParallelPromotionPreviewRow
import online.educoreng.educore.core.model.ParallelAttendanceDraft
import online.educoreng.educore.core.model.ParallelWorkingDayDraft
import online.educoreng.educore.core.model.ParallelSkillStudent
import online.educoreng.educore.core.model.ParallelSkillStudentDraft

private enum class ParallelLifecycleTab(val label: String) {
    OVERVIEW("Overview"),
    STRUCTURE("Structure"),
    ARMS("Class Arms"),
    STUDENTS("Students"),
    TEACHERS("Teachers"),
    GRADES("Grade System"),
    OPERATIONS("Timetable & Attendance"),
    SKILLS("Skills Rating"),
    RESULTS("Results"),
    PROMOTION("Promotion"),
    TRANSFERS("Transfers"),
    HISTORY("History"),
}

@Composable
fun ParallelCurriculumLifecycleScreen(
    state: ParallelLifecycleUiState,
    onRefresh: () -> Unit,
    onSelectCurriculum: (Long) -> Unit,
    onSelectSession: (Long) -> Unit,
    onSelectPromotionSessions: (Long?, Long?) -> Unit,
    onPreviewPromotion: (List<Long>) -> Unit,
    onExecutePromotion: () -> Unit,
    onCreateProgramme: (String, String?, Long?) -> Unit,
    onUpdateProgramme: (Long, String, String?, Long?) -> Unit,
    onCreateClass: (Long, String, String?, Long?) -> Unit,
    onUpdateClass: (Long, String, String?, Long?) -> Unit,
    onCreateSubject: (Long, String, String?) -> Unit,
    onUpdateSubject: (Long, String, String?) -> Unit,
    onSaveClassSubject: (Long, Long, Long?) -> Unit,
    onRemoveClassSubject: (Long) -> Unit,
    onSaveProgrammeGrade: (String, Double?, Double?, String?, Boolean) -> Unit,
    onDeleteProgrammeGrade: (Long) -> Unit,
    onLoadResults: (Long?, Long?) -> Unit,
    onLoadStudentResult: (Long, Long, Long) -> Unit,
    onCloseStudentResult: () -> Unit,
    onPublishResult: () -> Unit,
    onUnpublishResult: () -> Unit,
    onDownloadResultExport: (String) -> Unit,
    onDownloadStudentResultPdf: () -> Unit,
    onDownloadCumulativeStudentResultPdf: (Long, Long, Long) -> Unit,
    onDownloadParallelBroadsheetPdf: (String, Long, Long?, Long?, Long?) -> Unit,
    onCreateArm: (Long, String, String?, Int?) -> Unit,
    onUpdateArm: (Long, String, String?, Int?) -> Unit,
    onArchiveArm: (Long) -> Unit,
    onLoadStudents: (Long?, String, String, String?, String, Int) -> Unit,
    onAssignStudents: (Long, Long, List<Long>) -> Unit,
    onDownloadAssignmentTemplate: () -> Unit,
    onImportAssignments: (String, String, ByteArray) -> Unit,
    onRemoveStudent: (Long) -> Unit,
    onSaveArmTeacher: (Long, Long, Long?) -> Unit,
    onSaveArmTeachingMode: (Long, String, Long?) -> Unit,
    onSaveGrade: (List<Long>, String, Double?, Double?, String?, Boolean, Double?) -> Unit,
    onDeleteGrade: (Long) -> Unit,
    onLoadOperations: (Long?, Long?, Long?, String?) -> Unit,
    onCreateTimetablePeriod: (Long, Long, Long, String, String, String, String?) -> Unit,
    onDeleteTimetablePeriod: (Long) -> Unit,
    onSaveWorkingDays: (List<ParallelWorkingDayDraft>) -> Unit,
    onClockInParallelStaff: () -> Unit,
    onClockOutParallelStaff: () -> Unit,
    onSaveParallelAttendance: (List<ParallelAttendanceDraft>) -> Unit,
    onDownloadAttendanceExport: (String) -> Unit,
    onLoadSkills: (Long?, Long?) -> Unit,
    onSaveSkills: (List<ParallelSkillStudentDraft>) -> Unit,
    onSavePromotionRule: (List<Long>, String, Long?, Double?, Int?, Boolean, String, String, Boolean) -> Unit,
    onTransfer: (Long, Long, Long, String, String?) -> Unit,
    onDocumentOpened: () -> Unit,
) {
    OpenDocumentEffect(state.downloadedDocument, onDocumentOpened)
    val workspace = state.workspace
    var tab by remember { mutableStateOf(ParallelLifecycleTab.OVERVIEW) }

    LaunchedEffect(
        tab,
        state.selectedCurriculumId,
        state.selectedSessionId,
        state.operationsWorkspace?.selected?.curriculumId,
        state.operationsWorkspace?.selected?.sessionId,
        state.skillWorkspace?.selectedArmId,
        state.skillWorkspace?.selectedTermId,
    ) {
        val operationsSelection = state.operationsWorkspace?.selected
        if (
            tab == ParallelLifecycleTab.OPERATIONS &&
            state.selectedCurriculumId != null &&
            state.selectedSessionId != null &&
            (
                operationsSelection == null ||
                    operationsSelection.curriculumId != state.selectedCurriculumId ||
                    operationsSelection.sessionId != state.selectedSessionId
                )
        ) {
            onLoadOperations(null, null, null, null)
        }
        if (tab == ParallelLifecycleTab.SKILLS && state.skillWorkspace == null) {
            onLoadSkills(null, null)
        }
    }

    if (state.isLoading && workspace == null) {
        EduCoreLoadingState(message = "Loading parallel academic lifecycle")
        return
    }

    LazyColumn(
        modifier = Modifier.fillMaxWidth(),
        contentPadding = androidx.compose.foundation.layout.PaddingValues(EduCoreSpacing.Lg),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        state.errorMessage?.let { item { EduCoreErrorBanner(message = it, title = "Parallel Curriculum") } }
        state.message?.let { item { EduCoreInfoBanner(message = it, title = "Parallel Curriculum") } }

        if (workspace == null) {
            item {
                EduCoreEmptyState(
                    title = "Parallel curriculum unavailable",
                    message = "Reload the academic lifecycle workspace.",
                    actionLabel = "Retry",
                    onAction = onRefresh,
                )
            }
            return@LazyColumn
        }

        if (workspace.curricula.isEmpty()) {
            item {
                ProgrammeBootstrapCard(
                    workspace = workspace,
                    busy = state.isMutating,
                    onCreateProgramme = onCreateProgramme,
                )
            }
            return@LazyColumn
        }

        item {
            EduCoreDashboardCard(Modifier.fillMaxWidth()) {
                Text("Academic Lifecycle", style = MaterialTheme.typography.titleLarge, color = EduCoreColors.Navy900)
                Spacer(Modifier.height(EduCoreSpacing.Xs))
                Text(
                    "Programme → class level → class arm → learner. Manage grading, transfers and next-session progression independently from conventional placement.",
                    style = MaterialTheme.typography.bodyMedium,
                    color = EduCoreColors.Slate600,
                )
                Spacer(Modifier.height(EduCoreSpacing.Md))
                LifecycleMenu(
                    label = "Parallel programme",
                    current = workspace.selectedCurriculum?.name ?: "Select programme",
                    options = workspace.curricula.map { it.id to it.name },
                    enabled = !state.isMutating,
                    onSelect = onSelectCurriculum,
                )
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                LifecycleMenu(
                    label = "Working session",
                    current = workspace.selectedSession?.name ?: "Select session",
                    options = workspace.sessions.map { session ->
                        session.id to (session.name + if (session.isCurrent) " · Current" else "")
                    },
                    enabled = !state.isMutating,
                    onSelect = onSelectSession,
                )
            }
        }

        item {
            EduCoreTabs(
                labels = ParallelLifecycleTab.entries.map { it.label },
                selectedIndex = tab.ordinal,
                onSelected = { tab = ParallelLifecycleTab.entries[it] },
                modifier = Modifier.fillMaxWidth(),
            )
        }

        when (tab) {
            ParallelLifecycleTab.OVERVIEW -> lifecycleOverview(workspace)
            ParallelLifecycleTab.STRUCTURE -> lifecycleStructure(
                state = state,
                onCreateProgramme = onCreateProgramme,
                onUpdateProgramme = onUpdateProgramme,
                onCreateClass = onCreateClass,
                onUpdateClass = onUpdateClass,
                onCreateSubject = onCreateSubject,
                onUpdateSubject = onUpdateSubject,
                onSaveClassSubject = onSaveClassSubject,
                onRemoveClassSubject = onRemoveClassSubject,
                onSaveProgrammeGrade = onSaveProgrammeGrade,
                onDeleteProgrammeGrade = onDeleteProgrammeGrade,
            )
            ParallelLifecycleTab.ARMS -> lifecycleArms(state, onCreateArm, onUpdateArm, onArchiveArm)
            ParallelLifecycleTab.STUDENTS -> lifecycleStudents(
                state,
                onLoadStudents,
                onAssignStudents,
                onDownloadAssignmentTemplate,
                onImportAssignments,
                onRemoveStudent,
            )
            ParallelLifecycleTab.TEACHERS -> lifecycleTeachers(
                state,
                onSaveArmTeacher,
                onSaveArmTeachingMode,
            )
            ParallelLifecycleTab.GRADES -> lifecycleGrades(state, onSaveGrade, onDeleteGrade)
            ParallelLifecycleTab.OPERATIONS -> lifecycleOperations(
                state = state,
                onLoadOperations = onLoadOperations,
                onCreateTimetablePeriod = onCreateTimetablePeriod,
                onDeleteTimetablePeriod = onDeleteTimetablePeriod,
                onSaveWorkingDays = onSaveWorkingDays,
                onClockInParallelStaff = onClockInParallelStaff,
                onClockOutParallelStaff = onClockOutParallelStaff,
                onSaveParallelAttendance = onSaveParallelAttendance,
                onDownloadAttendanceExport = onDownloadAttendanceExport,
            )
            ParallelLifecycleTab.SKILLS -> lifecycleSkills(
                state = state,
                onLoadSkills = onLoadSkills,
                onSaveSkills = onSaveSkills,
            )
            ParallelLifecycleTab.RESULTS -> lifecycleResults(
                state = state,
                onLoadResults = onLoadResults,
                onLoadStudentResult = onLoadStudentResult,
                onCloseStudentResult = onCloseStudentResult,
                onPublishResult = onPublishResult,
                onUnpublishResult = onUnpublishResult,
                onDownloadResultExport = onDownloadResultExport,
                onDownloadStudentResultPdf = onDownloadStudentResultPdf,
                onDownloadCumulativeStudentResultPdf = onDownloadCumulativeStudentResultPdf,
                onDownloadParallelBroadsheetPdf = onDownloadParallelBroadsheetPdf,
            )
            ParallelLifecycleTab.PROMOTION -> lifecyclePromotion(
                state,
                onSelectPromotionSessions,
                onPreviewPromotion,
                onExecutePromotion,
                onSavePromotionRule,
            )
            ParallelLifecycleTab.TRANSFERS -> lifecycleTransfers(state, onTransfer)
            ParallelLifecycleTab.HISTORY -> lifecycleHistory(workspace)
        }
    }
}

@Composable
private fun ProgrammeBootstrapCard(
    workspace: ParallelLifecycleWorkspace,
    busy: Boolean,
    onCreateProgramme: (String, String?, Long?) -> Unit,
) {
    var name by remember { mutableStateOf("") }
    var code by remember { mutableStateOf("") }
    var templateId by remember { mutableStateOf<Long?>(workspace.assessmentTemplates.firstOrNull()?.id) }

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text(
            "Create your first parallel programme",
            style = MaterialTheme.typography.titleLarge,
            color = EduCoreColors.Navy900,
        )
        Spacer(Modifier.height(EduCoreSpacing.Xs))
        Text(
            "A programme has its own class levels, subjects, assessment structure, teachers, learner placements and results.",
            style = MaterialTheme.typography.bodyMedium,
            color = EduCoreColors.Slate600,
        )
        Spacer(Modifier.height(EduCoreSpacing.Md))
        EduCoreTextField(
            value = name,
            onValueChange = { name = it },
            label = "Programme name",
            modifier = Modifier.fillMaxWidth(),
            enabled = !busy,
        )
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        EduCoreTextField(
            value = code,
            onValueChange = { code = it },
            label = "Programme code (optional)",
            modifier = Modifier.fillMaxWidth(),
            enabled = !busy,
        )
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        LifecycleMenu(
            label = "Default assessment template",
            current = workspace.assessmentTemplates.firstOrNull { it.id == templateId }?.name
                ?: "Select assessment template",
            options = workspace.assessmentTemplates.map { it.id to it.name },
            enabled = !busy,
            onSelect = { templateId = it },
        )
        Spacer(Modifier.height(EduCoreSpacing.Md))
        EduCorePrimaryButton(
            text = "Create Programme",
            onClick = { onCreateProgramme(name, code, templateId) },
            modifier = Modifier.fillMaxWidth(),
            enabled = !busy && name.isNotBlank() && templateId != null,
            loading = busy,
        )
        if (workspace.assessmentTemplates.isEmpty()) {
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreInfoBanner(
                title = "Assessment template required",
                message = "Create an active assessment template on the web platform before creating a parallel programme.",
            )
        }
    }
}

private fun LazyListScope.lifecycleStructure(
    state: ParallelLifecycleUiState,
    onCreateProgramme: (String, String?, Long?) -> Unit,
    onUpdateProgramme: (Long, String, String?, Long?) -> Unit,
    onCreateClass: (Long, String, String?, Long?) -> Unit,
    onUpdateClass: (Long, String, String?, Long?) -> Unit,
    onCreateSubject: (Long, String, String?) -> Unit,
    onUpdateSubject: (Long, String, String?) -> Unit,
    onSaveClassSubject: (Long, Long, Long?) -> Unit,
    onRemoveClassSubject: (Long) -> Unit,
    onSaveProgrammeGrade: (String, Double?, Double?, String?, Boolean) -> Unit,
    onDeleteProgrammeGrade: (Long) -> Unit,
) {
    val workspace = state.workspace ?: return
    val curriculum = workspace.selectedCurriculum ?: return

    item {
        ProgrammeDetailsEditor(
            workspace = workspace,
            curriculum = curriculum,
            busy = state.isMutating,
            onCreateProgramme = onCreateProgramme,
            onUpdateProgramme = onUpdateProgramme,
        )
    }
    item {
        ParallelClassEditor(
            workspace = workspace,
            curriculum = curriculum,
            busy = state.isMutating,
            onCreateClass = onCreateClass,
            onUpdateClass = onUpdateClass,
        )
    }
    item {
        ParallelSubjectEditor(
            curriculum = curriculum,
            busy = state.isMutating,
            onCreateSubject = onCreateSubject,
            onUpdateSubject = onUpdateSubject,
        )
    }
    item {
        ClassSubjectAssignmentEditor(
            workspace = workspace,
            curriculum = curriculum,
            busy = state.isMutating,
            onSave = onSaveClassSubject,
            onRemove = onRemoveClassSubject,
        )
    }
    item {
        ProgrammeGradeEditor(
            curriculum = curriculum,
            busy = state.isMutating,
            onSave = onSaveProgrammeGrade,
            onDelete = onDeleteProgrammeGrade,
        )
    }
}

@Composable
private fun ProgrammeDetailsEditor(
    workspace: ParallelLifecycleWorkspace,
    curriculum: online.educoreng.educore.core.model.ParallelLifecycleCurriculum,
    busy: Boolean,
    onCreateProgramme: (String, String?, Long?) -> Unit,
    onUpdateProgramme: (Long, String, String?, Long?) -> Unit,
) {
    var name by remember(curriculum.id, curriculum.name) { mutableStateOf(curriculum.name) }
    var code by remember(curriculum.id, curriculum.code) { mutableStateOf(curriculum.code.orEmpty()) }
    var templateId by remember(curriculum.id, curriculum.defaultAssessmentTemplateId) {
        mutableStateOf(curriculum.defaultAssessmentTemplateId)
    }
    var newName by remember { mutableStateOf("") }
    var newCode by remember { mutableStateOf("") }
    var newTemplateId by remember(workspace.assessmentTemplates) {
        mutableStateOf<Long?>(workspace.assessmentTemplates.firstOrNull()?.id)
    }

    Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
        SectionHeading(
            "Programme structure",
            "Manage the parallel programme itself, class levels, subjects and default result rules.",
        )

        EduCoreDashboardCard(Modifier.fillMaxWidth()) {
            Text("Programme details", style = MaterialTheme.typography.titleMedium)
            Spacer(Modifier.height(EduCoreSpacing.Md))
            EduCoreTextField(name, { name = it }, "Programme name", Modifier.fillMaxWidth(), enabled = !busy)
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreTextField(code, { code = it }, "Programme code", Modifier.fillMaxWidth(), enabled = !busy)
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            LifecycleMenu(
                label = "Default assessment template",
                current = workspace.assessmentTemplates.firstOrNull { it.id == templateId }?.name
                    ?: curriculum.defaultAssessmentTemplateName
                    ?: "Select template",
                options = workspace.assessmentTemplates.map { it.id to it.name },
                enabled = !busy,
                onSelect = { templateId = it },
            )
            Spacer(Modifier.height(EduCoreSpacing.Md))
            EduCorePrimaryButton(
                text = "Save Programme Details",
                onClick = { onUpdateProgramme(curriculum.id, name, code, templateId) },
                modifier = Modifier.fillMaxWidth(),
                enabled = !busy && name.isNotBlank() && templateId != null,
                loading = busy,
            )
        }

        EduCoreDashboardCard(Modifier.fillMaxWidth()) {
            Text("Create another programme", style = MaterialTheme.typography.titleMedium)
            Spacer(Modifier.height(EduCoreSpacing.Md))
            EduCoreTextField(newName, { newName = it }, "Programme name", Modifier.fillMaxWidth(), enabled = !busy)
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreTextField(newCode, { newCode = it }, "Programme code", Modifier.fillMaxWidth(), enabled = !busy)
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            LifecycleMenu(
                label = "Default assessment template",
                current = workspace.assessmentTemplates.firstOrNull { it.id == newTemplateId }?.name
                    ?: "Select template",
                options = workspace.assessmentTemplates.map { it.id to it.name },
                enabled = !busy,
                onSelect = { newTemplateId = it },
            )
            Spacer(Modifier.height(EduCoreSpacing.Md))
            EduCoreSecondaryButton(
                text = "Create Programme",
                onClick = {
                    onCreateProgramme(newName, newCode, newTemplateId)
                    if (newName.isNotBlank()) {
                        newName = ""
                        newCode = ""
                    }
                },
                modifier = Modifier.fillMaxWidth(),
                enabled = !busy && newName.isNotBlank() && newTemplateId != null,
            )
        }
    }
}

@Composable
private fun ParallelClassEditor(
    workspace: ParallelLifecycleWorkspace,
    curriculum: online.educoreng.educore.core.model.ParallelLifecycleCurriculum,
    busy: Boolean,
    onCreateClass: (Long, String, String?, Long?) -> Unit,
    onUpdateClass: (Long, String, String?, Long?) -> Unit,
) {
    var editingId by remember(curriculum.id) { mutableStateOf<Long?>(null) }
    val editing = curriculum.classes.firstOrNull { it.id == editingId }
    var name by remember(curriculum.id, editingId) { mutableStateOf(editing?.name.orEmpty()) }
    var code by remember(curriculum.id, editingId) { mutableStateOf(editing?.code.orEmpty()) }
    var templateId by remember(curriculum.id, editingId) { mutableStateOf(editing?.assessmentTemplateId) }

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text("Class levels", style = MaterialTheme.typography.titleMedium)
        Text(
            "Each new level starts with Arm A automatically.",
            style = MaterialTheme.typography.bodySmall,
            color = EduCoreColors.Slate600,
        )
        Spacer(Modifier.height(EduCoreSpacing.Md))
        EduCoreTextField(
            name,
            { name = it },
            if (editing == null) "New class level name" else "Class level name",
            Modifier.fillMaxWidth(),
            enabled = !busy,
        )
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        EduCoreTextField(code, { code = it }, "Class code", Modifier.fillMaxWidth(), enabled = !busy)
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        NullableLifecycleMenu(
            label = "Assessment template",
            current = templateId?.let { id ->
                workspace.assessmentTemplates.firstOrNull { it.id == id }?.name
            } ?: "Use programme default · " + (curriculum.defaultAssessmentTemplateName ?: "Default"),
            options = listOf<Long?>(null).map {
                it to ("Use programme default · " + (curriculum.defaultAssessmentTemplateName ?: "Default"))
            } + workspace.assessmentTemplates.map { (it.id as Long?) to it.name },
            enabled = !busy,
            onSelect = { templateId = it },
        )
        Spacer(Modifier.height(EduCoreSpacing.Md))
        EduCorePrimaryButton(
            text = if (editing == null) "Create Class Level" else "Save Class Level",
            onClick = {
                if (editing == null) {
                    onCreateClass(curriculum.id, name, code, templateId)
                } else {
                    onUpdateClass(editing.id, name, code, templateId)
                }
                if (name.isNotBlank()) {
                    editingId = null
                    name = ""
                    code = ""
                    templateId = null
                }
            },
            modifier = Modifier.fillMaxWidth(),
            enabled = !busy && name.isNotBlank(),
            loading = busy,
        )
        if (editing != null) {
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreSecondaryButton(
                text = "Cancel Editing",
                onClick = {
                    editingId = null
                    name = ""
                    code = ""
                    templateId = null
                },
                modifier = Modifier.fillMaxWidth(),
                enabled = !busy,
            )
        }

        curriculum.classes.forEach { level ->
            Spacer(Modifier.height(EduCoreSpacing.Lg))
            HorizontalDivider()
            Spacer(Modifier.height(EduCoreSpacing.Md))
            Text(level.name, style = MaterialTheme.typography.titleSmall)
            Text(
                listOfNotNull(
                    level.code,
                    level.assessmentTemplateName ?: curriculum.defaultAssessmentTemplateName,
                    level.arms.count { it.isActive }.toString() + " active arm(s)",
                ).joinToString(" · "),
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreSecondaryButton(
                text = "Edit " + level.name,
                onClick = {
                    editingId = level.id
                    name = level.name
                    code = level.code.orEmpty()
                    templateId = level.assessmentTemplateId
                },
                modifier = Modifier.fillMaxWidth(),
                enabled = !busy,
            )
        }
    }
}

@Composable
private fun ParallelSubjectEditor(
    curriculum: online.educoreng.educore.core.model.ParallelLifecycleCurriculum,
    busy: Boolean,
    onCreateSubject: (Long, String, String?) -> Unit,
    onUpdateSubject: (Long, String, String?) -> Unit,
) {
    var editingId by remember(curriculum.id) { mutableStateOf<Long?>(null) }
    val editing = curriculum.subjects.firstOrNull { it.id == editingId }
    var name by remember(curriculum.id, editingId) { mutableStateOf(editing?.name.orEmpty()) }
    var code by remember(curriculum.id, editingId) { mutableStateOf(editing?.code.orEmpty()) }

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text("Programme subjects", style = MaterialTheme.typography.titleMedium)
        Spacer(Modifier.height(EduCoreSpacing.Md))
        EduCoreTextField(
            name,
            { name = it },
            if (editing == null) "New subject name" else "Subject name",
            Modifier.fillMaxWidth(),
            enabled = !busy,
        )
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        EduCoreTextField(code, { code = it }, "Subject code", Modifier.fillMaxWidth(), enabled = !busy)
        Spacer(Modifier.height(EduCoreSpacing.Md))
        EduCorePrimaryButton(
            text = if (editing == null) "Create Subject" else "Save Subject",
            onClick = {
                if (editing == null) onCreateSubject(curriculum.id, name, code)
                else onUpdateSubject(editing.id, name, code)
                if (name.isNotBlank()) {
                    editingId = null
                    name = ""
                    code = ""
                }
            },
            modifier = Modifier.fillMaxWidth(),
            enabled = !busy && name.isNotBlank(),
            loading = busy,
        )
        if (editing != null) {
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreSecondaryButton(
                "Cancel Editing",
                {
                    editingId = null
                    name = ""
                    code = ""
                },
                Modifier.fillMaxWidth(),
                enabled = !busy,
            )
        }

        curriculum.subjects.filter { it.isActive }.forEach { subject ->
            Spacer(Modifier.height(EduCoreSpacing.Lg))
            HorizontalDivider()
            Spacer(Modifier.height(EduCoreSpacing.Md))
            Text(
                subject.name + (subject.code?.let { " · $it" } ?: ""),
                style = MaterialTheme.typography.titleSmall,
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreSecondaryButton(
                text = "Edit " + subject.name,
                onClick = {
                    editingId = subject.id
                    name = subject.name
                    code = subject.code.orEmpty()
                },
                modifier = Modifier.fillMaxWidth(),
                enabled = !busy,
            )
        }
    }
}

@Composable
private fun ClassSubjectAssignmentEditor(
    workspace: ParallelLifecycleWorkspace,
    curriculum: online.educoreng.educore.core.model.ParallelLifecycleCurriculum,
    busy: Boolean,
    onSave: (Long, Long, Long?) -> Unit,
    onRemove: (Long) -> Unit,
) {
    var classId by remember(curriculum.id) { mutableStateOf(curriculum.classes.firstOrNull()?.id) }
    val level = curriculum.classes.firstOrNull { it.id == classId }
    var subjectId by remember(curriculum.id, classId) {
        mutableStateOf(curriculum.subjects.firstOrNull { it.isActive }?.id)
    }
    var teacherId by remember(curriculum.id, classId, subjectId) { mutableStateOf<Long?>(null) }

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text("Class subjects & default teachers", style = MaterialTheme.typography.titleMedium)
        Spacer(Modifier.height(EduCoreSpacing.Md))
        LifecycleMenu(
            label = "Class level",
            current = level?.name ?: "Select class level",
            options = curriculum.classes.filter { it.isActive }.map { it.id to it.name },
            enabled = !busy,
            onSelect = {
                classId = it
                teacherId = null
            },
        )
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        LifecycleMenu(
            label = "Programme subject",
            current = curriculum.subjects.firstOrNull { it.id == subjectId }?.name
                ?: "Select programme subject",
            options = curriculum.subjects.filter { it.isActive }.map { it.id to it.name },
            enabled = !busy,
            onSelect = {
                subjectId = it
                val existing = level?.subjects?.firstOrNull { assignment -> assignment.subjectId == it }
                teacherId = existing?.defaultTeacherId
            },
        )
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        NullableLifecycleMenu(
            label = "Class-level default teacher",
            current = teacherId?.let { id -> workspace.staff.firstOrNull { it.id == id }?.name }
                ?: "Admin / unassigned",
            options = listOf(null to "Admin / unassigned") +
                workspace.staff.map { (it.id as Long?) to it.name },
            enabled = !busy,
            onSelect = { teacherId = it },
        )
        Spacer(Modifier.height(EduCoreSpacing.Md))
        EduCorePrimaryButton(
            text = "Assign / Update Subject",
            onClick = {
                val selectedClass = classId
                val selectedSubject = subjectId
                if (selectedClass != null && selectedSubject != null) {
                    onSave(selectedClass, selectedSubject, teacherId)
                }
            },
            modifier = Modifier.fillMaxWidth(),
            enabled = !busy && classId != null && subjectId != null,
            loading = busy,
        )

        level?.subjects.orEmpty().forEach { assignment ->
            Spacer(Modifier.height(EduCoreSpacing.Lg))
            HorizontalDivider()
            Spacer(Modifier.height(EduCoreSpacing.Md))
            Text(assignment.subjectName ?: "Subject", style = MaterialTheme.typography.titleSmall)
            Text(
                "Default teacher: " + (assignment.defaultTeacherName ?: "Admin / unassigned"),
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreSecondaryButton(
                text = "Edit Teacher",
                onClick = {
                    subjectId = assignment.subjectId
                    teacherId = assignment.defaultTeacherId
                },
                modifier = Modifier.fillMaxWidth(),
                enabled = !busy,
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreDangerButton(
                text = "Remove Subject from " + (level?.name ?: "Class"),
                onClick = { onRemove(assignment.assignmentId) },
                modifier = Modifier.fillMaxWidth(),
                enabled = !busy,
            )
        }
    }
}

@Composable
private fun ProgrammeGradeEditor(
    curriculum: online.educoreng.educore.core.model.ParallelLifecycleCurriculum,
    busy: Boolean,
    onSave: (String, Double?, Double?, String?, Boolean) -> Unit,
    onDelete: (Long) -> Unit,
) {
    var letter by remember(curriculum.id) { mutableStateOf("") }
    var minScore by remember(curriculum.id) { mutableStateOf("") }
    var maxScore by remember(curriculum.id) { mutableStateOf("") }
    var remark by remember(curriculum.id) { mutableStateOf("") }
    var pass by remember(curriculum.id) { mutableStateOf(true) }

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text("Programme grading scale", style = MaterialTheme.typography.titleMedium)
        Text(
            "This is the default scale. Class-specific grade bands in the Grade System tab override it.",
            style = MaterialTheme.typography.bodySmall,
            color = EduCoreColors.Slate600,
        )
        Spacer(Modifier.height(EduCoreSpacing.Md))
        EduCoreTextField(letter, { letter = it }, "Grade letter", Modifier.fillMaxWidth(), enabled = !busy)
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        EduCoreTextField(minScore, { minScore = it }, "Minimum %", Modifier.fillMaxWidth(), enabled = !busy)
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        EduCoreTextField(maxScore, { maxScore = it }, "Maximum %", Modifier.fillMaxWidth(), enabled = !busy)
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        EduCoreTextField(remark, { remark = it }, "Remark", Modifier.fillMaxWidth(), enabled = !busy)
        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
            Checkbox(pass, { pass = it }, enabled = !busy)
            Text("Pass grade", style = MaterialTheme.typography.bodyMedium)
        }
        EduCorePrimaryButton(
            text = "Save Programme Grade",
            onClick = {
                onSave(
                    letter,
                    minScore.toDoubleOrNull(),
                    maxScore.toDoubleOrNull(),
                    remark,
                    pass,
                )
            },
            modifier = Modifier.fillMaxWidth(),
            enabled = !busy && letter.isNotBlank(),
            loading = busy,
        )

        curriculum.grades.forEach { grade ->
            Spacer(Modifier.height(EduCoreSpacing.Lg))
            HorizontalDivider()
            Spacer(Modifier.height(EduCoreSpacing.Md))
            Text(
                grade.gradeLetter + " · " + grade.minScore + "–" + grade.maxScore,
                style = MaterialTheme.typography.titleSmall,
            )
            Text(
                (grade.remark ?: "No remark") + " · " + if (grade.isPassGrade) "Pass" else "Fail",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreDangerButton(
                text = "Remove " + grade.gradeLetter,
                onClick = { onDelete(grade.id) },
                modifier = Modifier.fillMaxWidth(),
                enabled = !busy,
            )
        }
    }
}

private fun LazyListScope.lifecycleOverview(workspace: ParallelLifecycleWorkspace) {
    item { SectionHeading("Programme structure", "Levels, arms, grade overrides and progression rules") }
    items(workspace.selectedCurriculum?.classes.orEmpty(), key = { "level-" + it.id }) { level ->
        EduCoreDashboardCard(Modifier.fillMaxWidth()) {
            Text(level.name, style = MaterialTheme.typography.titleMedium)
            Text(
                listOfNotNull(level.code, level.arms.count { arm -> arm.isActive }.toString() + " active arm(s)").joinToString(" · "),
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            Text(
                if (level.classGrades.isEmpty()) "Programme grading scale"
                else level.classGrades.size.toString() + " class-specific grade band(s)",
                style = MaterialTheme.typography.bodyMedium,
            )
            Text(
                level.promotionRule?.let { rule ->
                    if (rule.isTerminal) "Promotion: terminal → graduate"
                    else "Promotion: next level " + (rule.destinationClassName ?: "not configured")
                } ?: "Promotion rule not configured",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
        }
    }
    item {
        EduCoreDashboardCard(Modifier.fillMaxWidth()) {
            Text("Current placement summary", style = MaterialTheme.typography.titleMedium)
            KeyValueRow("Active learners", workspace.enrolments.size.toString())
            KeyValueRow("Recent transfers", workspace.transfers.size.toString())
            KeyValueRow("Recent promotions", workspace.promotions.size.toString())
        }
    }
}

private fun LazyListScope.lifecycleArms(
    state: ParallelLifecycleUiState,
    onCreateArm: (Long, String, String?, Int?) -> Unit,
    onUpdateArm: (Long, String, String?, Int?) -> Unit,
    onArchiveArm: (Long) -> Unit,
) {
    val levels = state.workspace?.selectedCurriculum?.classes.orEmpty()
    item { SectionHeading("Class arms", "Create, edit, capacity-limit or archive arms") }
    if (levels.isEmpty()) {
        item { EduCoreEmptyState("No class levels", "Create a parallel class level first.") }
    } else {
        items(levels, key = { "arms-" + it.id }) { level ->
            ArmEditor(level, state.isMutating, onCreateArm, onUpdateArm, onArchiveArm)
        }
    }
}

@Composable
private fun ArmEditor(
    level: ParallelLifecycleClass,
    busy: Boolean,
    onCreateArm: (Long, String, String?, Int?) -> Unit,
    onUpdateArm: (Long, String, String?, Int?) -> Unit,
    onArchiveArm: (Long) -> Unit,
) {
    var editing by remember(level.id) { mutableStateOf<ParallelLifecycleArm?>(null) }
    var name by remember(level.id) { mutableStateOf("") }
    var code by remember(level.id) { mutableStateOf("") }
    var capacity by remember(level.id) { mutableStateOf("") }

    fun loadArm(arm: ParallelLifecycleArm?) {
        editing = arm
        name = arm?.name.orEmpty()
        code = arm?.code.orEmpty()
        capacity = arm?.capacity?.toString().orEmpty()
    }

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text(level.name, style = MaterialTheme.typography.titleMedium)
        Text(
            if (editing == null) "Add another arm" else "Editing " + editing?.name.orEmpty(),
            style = MaterialTheme.typography.bodySmall,
            color = EduCoreColors.Slate600,
        )
        Spacer(Modifier.height(EduCoreSpacing.Md))
        EduCoreTextField(name, { name = it }, "Arm name", Modifier.fillMaxWidth(), enabled = !busy)
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        EduCoreTextField(code, { code = it }, "Arm code", Modifier.fillMaxWidth(), enabled = !busy)
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        EduCoreTextField(
            capacity,
            { value -> if (value.all(Char::isDigit)) capacity = value },
            "Capacity (blank = unlimited)",
            Modifier.fillMaxWidth(),
            enabled = !busy,
        )
        Spacer(Modifier.height(EduCoreSpacing.Md))
        EduCorePrimaryButton(
            text = if (editing == null) "Create Arm" else "Save Arm",
            onClick = {
                val current = editing
                if (current == null) onCreateArm(level.id, name, code, capacity.toIntOrNull())
                else onUpdateArm(current.id, name, code, capacity.toIntOrNull())
                if (name.isNotBlank()) loadArm(null)
            },
            modifier = Modifier.fillMaxWidth(),
            enabled = !busy && name.isNotBlank(),
            loading = busy,
        )
        if (editing != null) {
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreSecondaryButton("Cancel", { loadArm(null) }, Modifier.fillMaxWidth(), enabled = !busy)
        }

        level.arms.forEach { arm ->
            Spacer(Modifier.height(EduCoreSpacing.Lg))
            HorizontalDivider()
            Spacer(Modifier.height(EduCoreSpacing.Md))
            Text(arm.name + (arm.code?.let { " · " + it } ?: ""), style = MaterialTheme.typography.titleSmall)
            Text(
                (if (arm.isActive) "Active" else "Archived") +
                    " · Capacity " + (arm.capacity?.toString() ?: "Unlimited"),
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
            if (arm.isActive) {
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                EduCoreSecondaryButton("Edit", { loadArm(arm) }, Modifier.fillMaxWidth(), enabled = !busy)
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                EduCoreDangerButton("Archive", { onArchiveArm(arm.id) }, Modifier.fillMaxWidth(), enabled = !busy)
            }
        }
    }
}

private fun LazyListScope.lifecycleStudents(
    state: ParallelLifecycleUiState,
    onLoadStudents: (Long?, String, String, String?, String, Int) -> Unit,
    onAssignStudents: (Long, Long, List<Long>) -> Unit,
    onDownloadAssignmentTemplate: () -> Unit,
    onImportAssignments: (String, String, ByteArray) -> Unit,
    onRemoveStudent: (Long) -> Unit,
) {
    item {
        StudentPlacementPanel(
            state = state,
            onLoadStudents = onLoadStudents,
            onAssignStudents = onAssignStudents,
            onDownloadAssignmentTemplate = onDownloadAssignmentTemplate,
            onImportAssignments = onImportAssignments,
            onRemoveStudent = onRemoveStudent,
        )
    }
}

@Composable
private fun StudentPlacementPanel(
    state: ParallelLifecycleUiState,
    onLoadStudents: (Long?, String, String, String?, String, Int) -> Unit,
    onAssignStudents: (Long, Long, List<Long>) -> Unit,
    onDownloadAssignmentTemplate: () -> Unit,
    onImportAssignments: (String, String, ByteArray) -> Unit,
    onRemoveStudent: (Long) -> Unit,
) {
    val workspace = state.workspace
    val curriculum = workspace?.selectedCurriculum
    val session = workspace?.selectedSession
    val page = state.studentPage
    val context = LocalContext.current
    var importError by remember(curriculum?.id, session?.id) { mutableStateOf<String?>(null) }
    val assignmentFilePicker = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.OpenDocument(),
    ) { uri ->
        if (uri != null) {
            runCatching {
                val metadata = context.contentResolver.query(
                    uri,
                    arrayOf(OpenableColumns.DISPLAY_NAME, OpenableColumns.SIZE),
                    null,
                    null,
                    null,
                )?.use { cursor ->
                    if (!cursor.moveToFirst()) {
                        null
                    } else {
                        val nameIndex = cursor.getColumnIndex(OpenableColumns.DISPLAY_NAME)
                        val sizeIndex = cursor.getColumnIndex(OpenableColumns.SIZE)
                        val name = if (nameIndex >= 0) cursor.getString(nameIndex) else null
                        val size = if (sizeIndex >= 0 && !cursor.isNull(sizeIndex)) cursor.getLong(sizeIndex) else null
                        name to size
                    }
                }

                val filename = metadata?.first?.takeIf(String::isNotBlank)
                    ?: "parallel_student_assignments.csv"
                val extension = filename.substringAfterLast('.', "").lowercase()
                require(extension in setOf("csv", "txt", "xls", "xlsx")) {
                    "Choose a CSV, XLS or XLSX assignment file."
                }
                val declaredSize = metadata?.second
                require(declaredSize == null || declaredSize <= 5L * 1024L * 1024L) {
                    "The assignment file must not exceed 5 MB."
                }
                val bytes = requireNotNull(context.contentResolver.openInputStream(uri)) {
                    "The selected file could not be opened."
                }.use { it.readBytes() }
                require(bytes.size <= 5 * 1024 * 1024) {
                    "The assignment file must not exceed 5 MB."
                }

                Triple(
                    filename,
                    context.contentResolver.getType(uri) ?: "application/octet-stream",
                    bytes,
                )
            }.onSuccess { (filename, mimeType, bytes) ->
                importError = null
                onImportAssignments(filename, mimeType, bytes)
            }.onFailure {
                importError = it.message ?: "The selected assignment file could not be read."
            }
        }
    }

    var search by remember(curriculum?.id, session?.id) { mutableStateOf(state.studentSearch) }
    var conventionalArmId by remember(curriculum?.id, session?.id) {
        mutableStateOf(state.studentConventionalClassArmId)
    }
    var assignmentStatus by remember(curriculum?.id, session?.id) {
        mutableStateOf(state.studentAssignmentStatus)
    }
    var learnerStatus by remember(curriculum?.id, session?.id) {
        mutableStateOf(state.studentLearnerStatus)
    }
    var gender by remember(curriculum?.id, session?.id) {
        mutableStateOf(state.studentGender)
    }
    var destinationClassId by remember(curriculum?.id, session?.id) { mutableStateOf<Long?>(null) }
    var destinationArmId by remember(curriculum?.id, session?.id) { mutableStateOf<Long?>(null) }
    var selectedStudentIds by remember(curriculum?.id, session?.id) {
        mutableStateOf<Set<Long>>(emptySet())
    }
    var pendingRemovalEnrolmentId by remember(curriculum?.id, session?.id) {
        mutableStateOf<Long?>(null)
    }
    var pendingRemovalStudentName by remember(curriculum?.id, session?.id) {
        mutableStateOf<String?>(null)
    }

    LaunchedEffect(curriculum?.id, session?.id) {
        if (curriculum != null && session != null) {
            onLoadStudents(
                conventionalArmId,
                assignmentStatus,
                learnerStatus,
                gender,
                search,
                1,
            )
        }
    }

    LaunchedEffect(page?.pagination?.currentPage) {
        selectedStudentIds = emptySet()
    }

    Column(
        modifier = Modifier.fillMaxWidth(),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        SectionHeading(
            "Student placement",
            "Filter conventional learners, select several at once, then place them into a parallel class and arm.",
        )

        if (curriculum == null || session == null) {
            EduCoreEmptyState(
                "Programme or session unavailable",
                "Select a parallel programme and working session first.",
            )
            return@Column
        }

        EduCoreDashboardCard(Modifier.fillMaxWidth()) {
            Text("Bulk CSV / Excel assignment", style = MaterialTheme.typography.titleMedium)
            Spacer(Modifier.height(EduCoreSpacing.Xs))
            Text(
                "Upload up to 5 MB. Required columns: admission_number, parallel_class, parallel_arm. Class and arm codes are also accepted.",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
            Spacer(Modifier.height(EduCoreSpacing.Md))
            EduCoreSecondaryButton(
                text = "Download CSV Template",
                onClick = onDownloadAssignmentTemplate,
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isMutating,
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreSecondaryButton(
                text = "Choose CSV / Excel File",
                onClick = {
                    assignmentFilePicker.launch(
                        arrayOf(
                            "text/csv",
                            "text/plain",
                            "application/vnd.ms-excel",
                            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                        )
                    )
                },
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isMutating,
            )
            importError?.let {
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                EduCoreErrorBanner(message = it, title = "Assignment import")
            }
        }

        EduCoreDashboardCard(Modifier.fillMaxWidth()) {
            Text("Find learners", style = MaterialTheme.typography.titleMedium)
            Spacer(Modifier.height(EduCoreSpacing.Md))

            NullableLifecycleMenu(
                label = "Conventional class",
                current = page?.conventionalClassArms
                    ?.firstOrNull { it.id == conventionalArmId }
                    ?.name
                    ?: "All conventional classes",
                options = listOf(null to "All conventional classes") +
                    page.orEmptyClassArmOptions(),
                enabled = !state.isStudentLoading && !state.isMutating,
                onSelect = { conventionalArmId = it },
            )

            Spacer(Modifier.height(EduCoreSpacing.Sm))
            StringLifecycleMenu(
                label = "Assignment status",
                current = when (assignmentStatus) {
                    "assigned" -> "Already assigned"
                    "unassigned" -> "Not yet assigned"
                    else -> "All students"
                },
                options = listOf(
                    "all" to "All students",
                    "unassigned" to "Not yet assigned",
                    "assigned" to "Already assigned",
                ),
                enabled = !state.isStudentLoading && !state.isMutating,
                onSelect = { assignmentStatus = it },
            )

            Spacer(Modifier.height(EduCoreSpacing.Sm))
            StringLifecycleMenu(
                label = "Learner status",
                current = when (learnerStatus) {
                    "inactive" -> "Not active"
                    "all" -> "All learner statuses"
                    else -> "Active"
                },
                options = listOf(
                    "active" to "Active",
                    "inactive" to "Not active",
                    "all" to "All learner statuses",
                ),
                enabled = !state.isStudentLoading && !state.isMutating,
                onSelect = { learnerStatus = it },
            )

            Spacer(Modifier.height(EduCoreSpacing.Sm))
            StringLifecycleMenu(
                label = "Gender",
                current = when (gender) {
                    "male" -> "Male"
                    "female" -> "Female"
                    else -> "All"
                },
                options = listOf(
                    "" to "All",
                    "male" to "Male",
                    "female" to "Female",
                ),
                enabled = !state.isStudentLoading && !state.isMutating,
                onSelect = { gender = it.takeIf(String::isNotBlank) },
            )

            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreTextField(
                value = search,
                onValueChange = { search = it },
                label = "Name or admission number",
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isStudentLoading && !state.isMutating,
            )

            Spacer(Modifier.height(EduCoreSpacing.Md))
            EduCorePrimaryButton(
                text = "Apply Filters",
                onClick = {
                    selectedStudentIds = emptySet()
                    onLoadStudents(
                        conventionalArmId,
                        assignmentStatus,
                        learnerStatus,
                        gender,
                        search,
                        1,
                    )
                },
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isStudentLoading && !state.isMutating,
                loading = state.isStudentLoading,
            )
        }

        EduCoreDashboardCard(Modifier.fillMaxWidth()) {
            Text("Destination", style = MaterialTheme.typography.titleMedium)
            Spacer(Modifier.height(EduCoreSpacing.Md))

            LifecycleMenu(
                label = "Parallel class",
                current = curriculum.classes
                    .firstOrNull { it.id == destinationClassId }
                    ?.name
                    ?: "Choose destination class",
                options = curriculum.classes
                    .filter { it.isActive }
                    .map { it.id to it.name },
                enabled = !state.isMutating,
                onSelect = {
                    destinationClassId = it
                    destinationArmId = null
                },
            )

            val destinationClass = curriculum.classes.firstOrNull { it.id == destinationClassId }
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            LifecycleMenu(
                label = "Class arm",
                current = destinationClass
                    ?.arms
                    ?.firstOrNull { it.id == destinationArmId }
                    ?.name
                    ?: "Choose destination arm",
                options = destinationClass
                    ?.arms
                    ?.filter { it.isActive }
                    ?.map { arm ->
                        arm.id to (
                            arm.name +
                                (arm.capacity?.let { " · Capacity $it" } ?: "")
                            )
                    }
                    .orEmpty(),
                enabled = !state.isMutating && destinationClassId != null,
                onSelect = { destinationArmId = it },
            )

            Spacer(Modifier.height(EduCoreSpacing.Md))
            Text(
                selectedStudentIds.size.toString() + " learner(s) selected",
                style = MaterialTheme.typography.bodyMedium,
                color = EduCoreColors.Slate600,
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCorePrimaryButton(
                text = "Assign Selected Learners",
                onClick = {
                    val classId = destinationClassId
                    val armId = destinationArmId
                    if (classId != null && armId != null) {
                        onAssignStudents(classId, armId, selectedStudentIds.toList())
                        selectedStudentIds = emptySet()
                    }
                },
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isMutating &&
                    selectedStudentIds.isNotEmpty() &&
                    destinationClassId != null &&
                    destinationArmId != null,
                loading = state.isMutating,
            )
            Spacer(Modifier.height(EduCoreSpacing.Xs))
            Text(
                "Same-session assignment moves an existing learner within this programme. Conventional class placement is unchanged.",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
        }

        if (state.isStudentLoading && page == null) {
            EduCoreLoadingState(message = "Loading students")
            return@Column
        }

        if (page == null || page.students.isEmpty()) {
            EduCoreEmptyState(
                "No matching learners",
                "Change the filters or search term and try again.",
            )
            return@Column
        }

        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            EduCoreSecondaryButton(
                text = "Select visible",
                onClick = { selectedStudentIds = page.students.filter { it.isActive }.map { it.id }.toSet() },
                modifier = Modifier.weight(1f),
                enabled = !state.isMutating,
            )
            EduCoreSecondaryButton(
                text = "Clear",
                onClick = { selectedStudentIds = emptySet() },
                modifier = Modifier.weight(1f),
                enabled = !state.isMutating && selectedStudentIds.isNotEmpty(),
            )
        }

        Text(
            "Showing " + page.students.size + " of " + page.pagination.total + " matching learner(s).",
            style = MaterialTheme.typography.bodySmall,
            color = EduCoreColors.Slate600,
        )

        page.students.forEach { student ->
            EduCoreDashboardCard(Modifier.fillMaxWidth()) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.Top,
                ) {
                    Checkbox(
                        checked = selectedStudentIds.contains(student.id),
                        onCheckedChange = { checked ->
                            selectedStudentIds = if (checked) {
                                selectedStudentIds + student.id
                            } else {
                                selectedStudentIds - student.id
                            }
                        },
                        enabled = !state.isMutating && student.isActive,
                    )
                    Column(Modifier.weight(1f)) {
                        Text(student.name, style = MaterialTheme.typography.titleSmall)
                        Text(
                            listOfNotNull(
                                student.admissionNumber,
                                student.conventionalClassName ?: "No conventional class",
                                student.gender?.replaceFirstChar { it.uppercase() },
                                "Status: " + student.status
                                    .replace('_', ' ')
                                    .replaceFirstChar { it.uppercase() },
                            ).joinToString(" · "),
                            style = MaterialTheme.typography.bodySmall,
                            color = EduCoreColors.Slate600,
                        )
                        if (!student.isActive) {
                            Spacer(Modifier.height(EduCoreSpacing.Xs))
                            Text(
                                "This learner is not active and cannot be newly assigned. Existing parallel placement can still be removed.",
                                style = MaterialTheme.typography.bodySmall,
                                color = EduCoreColors.Slate600,
                            )
                        }
                        Spacer(Modifier.height(EduCoreSpacing.Xs))
                        Text(
                            student.assignment?.let { assignment ->
                                "Current parallel placement: " +
                                    (assignment.className ?: "Assigned") +
                                    (assignment.armName?.let { " · $it" } ?: "")
                            } ?: "Current parallel placement: Not assigned",
                            style = MaterialTheme.typography.bodySmall,
                            color = EduCoreColors.Slate600,
                        )
                        val assignment = student.assignment
                        if (assignment != null) {
                            Spacer(Modifier.height(EduCoreSpacing.Sm))
                            EduCoreDangerButton(
                                text = "Remove Parallel Placement",
                                onClick = {
                                    pendingRemovalEnrolmentId = assignment.enrolmentId
                                    pendingRemovalStudentName = student.name
                                },
                                modifier = Modifier.fillMaxWidth(),
                                enabled = !state.isMutating,
                            )
                        }
                    }
                }
            }
        }

        EduCoreConfirmationDialog(
            visible = pendingRemovalEnrolmentId != null,
            title = "Remove parallel placement?",
            message = (pendingRemovalStudentName ?: "This learner") +
                " will be removed from the selected parallel programme for this session. Existing result records are preserved by archiving the placement when necessary.",
            confirmLabel = "Remove Placement",
            destructive = true,
            onConfirm = {
                pendingRemovalEnrolmentId?.let(onRemoveStudent)
                pendingRemovalEnrolmentId = null
                pendingRemovalStudentName = null
            },
            onDismiss = {
                pendingRemovalEnrolmentId = null
                pendingRemovalStudentName = null
            },
        )

        if (page.pagination.lastPage > 1) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                EduCoreSecondaryButton(
                    text = "Previous",
                    onClick = {
                        selectedStudentIds = emptySet()
                        onLoadStudents(
                            conventionalArmId,
                            assignmentStatus,
                            learnerStatus,
                            gender,
                            search,
                            (page.pagination.currentPage - 1).coerceAtLeast(1),
                        )
                    },
                    modifier = Modifier.weight(1f),
                    enabled = !state.isStudentLoading && page.pagination.currentPage > 1,
                )
                EduCoreSecondaryButton(
                    text = "Next",
                    onClick = {
                        selectedStudentIds = emptySet()
                        onLoadStudents(
                            conventionalArmId,
                            assignmentStatus,
                            learnerStatus,
                            gender,
                            search,
                            (page.pagination.currentPage + 1)
                                .coerceAtMost(page.pagination.lastPage),
                        )
                    },
                    modifier = Modifier.weight(1f),
                    enabled = !state.isStudentLoading &&
                        page.pagination.currentPage < page.pagination.lastPage,
                )
            }
            Text(
                "Page " + page.pagination.currentPage + " of " + page.pagination.lastPage,
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
        }
    }
}

private fun online.educoreng.educore.core.model.ParallelLifecycleStudentPage?.orEmptyClassArmOptions():
    List<Pair<Long?, String>> =
    this?.conventionalClassArms?.map { (it.id as Long?) to it.name }.orEmpty()

@Composable
private fun NullableLifecycleMenu(
    label: String,
    current: String,
    options: List<Pair<Long?, String>>,
    enabled: Boolean,
    onSelect: (Long?) -> Unit,
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
            DropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
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

@Composable
private fun StringLifecycleMenu(
    label: String,
    current: String,
    options: List<Pair<String, String>>,
    enabled: Boolean,
    onSelect: (String) -> Unit,
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
            DropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
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

private fun LazyListScope.lifecycleTeachers(
    state: ParallelLifecycleUiState,
    onSaveArmTeacher: (Long, Long, Long?) -> Unit,
    onSaveArmTeachingMode: (Long, String, Long?) -> Unit,
) {
    val workspace = state.workspace ?: return
    val levels = workspace.selectedCurriculum?.classes.orEmpty()

    item {
        SectionHeading(
            "Teaching assignment model",
            "Choose one teacher for every subject in an arm, or assign teachers by subject across any number of classes.",
        )
    }

    if (!workspace.armTeachingModesReady) {
        item {
            EduCoreInfoBanner(
                title = "Teaching modes unavailable",
                message = "Deploy the latest database migration before selecting class-teacher or subject-based assignment modes.",
            )
        }
        return
    }

    if (levels.isEmpty()) {
        item {
            EduCoreEmptyState(
                "No class levels",
                "Create a parallel class level first.",
            )
        }
        return
    }

    levels.forEach { level ->
        val activeArms = level.arms.filter { it.isActive }
        if (activeArms.isEmpty()) {
            item(key = "teacher-empty-" + level.id) {
                EduCoreDashboardCard(Modifier.fillMaxWidth()) {
                    Text(level.name, style = MaterialTheme.typography.titleMedium)
                    Text(
                        "Create at least one active class arm before configuring teachers.",
                        style = MaterialTheme.typography.bodySmall,
                        color = EduCoreColors.Slate600,
                    )
                }
            }
        } else {
            activeArms.forEach { arm ->
                item(key = "teacher-" + level.id + "-" + arm.id) {
                    ArmTeacherCard(
                        level = level,
                        arm = arm,
                        staff = workspace.staff,
                        armTeacherOverridesReady = workspace.armTeacherOverridesReady,
                        busy = state.isMutating,
                        onSaveSubjectTeacher = onSaveArmTeacher,
                        onSaveTeachingMode = onSaveArmTeachingMode,
                    )
                }
            }
        }
    }
}

@Composable
private fun ArmTeacherCard(
    level: ParallelLifecycleClass,
    arm: ParallelLifecycleArm,
    staff: List<ParallelLifecycleStaff>,
    armTeacherOverridesReady: Boolean,
    busy: Boolean,
    onSaveSubjectTeacher: (Long, Long, Long?) -> Unit,
    onSaveTeachingMode: (Long, String, Long?) -> Unit,
) {
    var mode by remember(arm.id, arm.teachingAssignmentMode) {
        mutableStateOf(arm.teachingAssignmentMode.ifBlank { "subject_based" })
    }
    var classTeacherId by remember(arm.id, arm.classTeacherId) {
        mutableStateOf(arm.classTeacherId)
    }

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text(level.name + " " + arm.name, style = MaterialTheme.typography.titleMedium)
        Text(
            "Configure this arm independently from every other parallel class arm.",
            style = MaterialTheme.typography.bodySmall,
            color = EduCoreColors.Slate600,
        )

        Spacer(Modifier.height(EduCoreSpacing.Md))
        StringLifecycleMenu(
            label = "Teaching assignment mode",
            current = if (mode == "class_teacher") {
                "One class teacher · all subjects"
            } else {
                "Subject-based teachers"
            },
            options = listOf(
                "class_teacher" to "One class teacher · all subjects",
                "subject_based" to "Subject-based teachers",
            ),
            enabled = !busy,
            onSelect = { mode = it },
        )

        if (mode == "class_teacher") {
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            ClassTeacherMenu(
                selectedTeacherId = classTeacherId,
                staff = staff,
                enabled = !busy,
                onSelect = { classTeacherId = it },
            )
            Spacer(Modifier.height(EduCoreSpacing.Xs))
            Text(
                "This teacher becomes the effective teacher for every active subject in this class arm.",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
        } else {
            Spacer(Modifier.height(EduCoreSpacing.Xs))
            Text(
                "Teachers can be assigned independently to each subject, including the same subject across several classes or arms.",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
        }

        Spacer(Modifier.height(EduCoreSpacing.Md))
        EduCorePrimaryButton(
            text = "Save Assignment Model",
            onClick = {
                onSaveTeachingMode(
                    arm.id,
                    mode,
                    if (mode == "class_teacher") classTeacherId else null,
                )
            },
            modifier = Modifier.fillMaxWidth(),
            enabled = !busy && (mode != "class_teacher" || classTeacherId != null),
            loading = busy,
        )

        if (mode == "class_teacher") {
            Spacer(Modifier.height(EduCoreSpacing.Lg))
            HorizontalDivider()
            Spacer(Modifier.height(EduCoreSpacing.Md))
            Text(
                "All subjects use " +
                    (staff.firstOrNull { it.id == classTeacherId }?.name
                        ?: arm.classTeacherName
                        ?: "the selected class teacher"),
                style = MaterialTheme.typography.titleSmall,
                color = EduCoreColors.Navy900,
            )
            if (level.subjects.isNotEmpty()) {
                Text(
                    level.subjects.joinToString(" · ") {
                        it.subjectName ?: "Subject"
                    },
                    style = MaterialTheme.typography.bodySmall,
                    color = EduCoreColors.Slate600,
                )
            }
            Text(
                "Existing subject overrides are preserved but ignored until Subject-based teachers is selected again.",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
            return@EduCoreDashboardCard
        }

        Spacer(Modifier.height(EduCoreSpacing.Lg))
        HorizontalDivider()
        Spacer(Modifier.height(EduCoreSpacing.Md))
        Text(
            "Subject teacher assignments",
            style = MaterialTheme.typography.titleSmall,
            color = EduCoreColors.Navy900,
        )

        if (!armTeacherOverridesReady) {
            Text(
                "Deploy the arm-teacher migration before creating subject-specific overrides.",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
            return@EduCoreDashboardCard
        }

        if (level.subjects.isEmpty()) {
            Text(
                "Assign at least one active subject to this class level.",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
            return@EduCoreDashboardCard
        }

        level.subjects.forEach { subject ->
            val override = arm.subjectTeachers.firstOrNull {
                it.subjectId == subject.subjectId
            }
            var selectedTeacherId by remember(
                arm.id,
                subject.subjectId,
                override?.teacherId,
            ) {
                mutableStateOf<Long?>(override?.teacherId)
            }

            Spacer(Modifier.height(EduCoreSpacing.Lg))
            HorizontalDivider()
            Spacer(Modifier.height(EduCoreSpacing.Md))
            Text(
                subject.subjectName ?: "Subject",
                style = MaterialTheme.typography.titleSmall,
                color = EduCoreColors.Navy900,
            )
            Text(
                "Class default: " +
                    (subject.defaultTeacherName ?: "Admin / unassigned"),
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            TeacherMenu(
                subject = subject,
                selectedTeacherId = selectedTeacherId,
                staff = staff,
                enabled = !busy,
                onSelect = { selectedTeacherId = it },
            )
            Spacer(Modifier.height(EduCoreSpacing.Xs))
            val effectiveName = selectedTeacherId
                ?.let { id -> staff.firstOrNull { it.id == id }?.name }
                ?: subject.defaultTeacherName
                ?: "Admin / unassigned"
            Text(
                "Effective teacher: $effectiveName",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreSecondaryButton(
                text = "Save Subject Teacher",
                onClick = {
                    onSaveSubjectTeacher(
                        arm.id,
                        subject.subjectId,
                        selectedTeacherId,
                    )
                },
                modifier = Modifier.fillMaxWidth(),
                enabled = !busy,
            )
        }
    }
}

@Composable
private fun ClassTeacherMenu(
    selectedTeacherId: Long?,
    staff: List<ParallelLifecycleStaff>,
    enabled: Boolean,
    onSelect: (Long?) -> Unit,
) {
    var expanded by remember { mutableStateOf(false) }
    val selectedName = selectedTeacherId
        ?.let { id -> staff.firstOrNull { it.id == id }?.name }
        ?: "Select class teacher"

    Column(Modifier.fillMaxWidth()) {
        Text(
            "Class teacher for all subjects",
            style = MaterialTheme.typography.labelLarge,
            color = EduCoreColors.Slate700,
        )
        Spacer(Modifier.height(EduCoreSpacing.Xs))
        Box(Modifier.fillMaxWidth()) {
            EduCoreSecondaryButton(
                text = selectedName,
                onClick = { expanded = true },
                modifier = Modifier.fillMaxWidth(),
                enabled = enabled && staff.isNotEmpty(),
            )
            DropdownMenu(
                expanded = expanded,
                onDismissRequest = { expanded = false },
            ) {
                staff.forEach { teacher ->
                    DropdownMenuItem(
                        text = { Text(teacher.name) },
                        onClick = {
                            expanded = false
                            onSelect(teacher.id)
                        },
                    )
                }
            }
        }
    }
}

@Composable
private fun TeacherMenu(
    subject: ParallelLifecycleSubjectAssignment,
    selectedTeacherId: Long?,
    staff: List<ParallelLifecycleStaff>,
    enabled: Boolean,
    onSelect: (Long?) -> Unit,
) {
    var expanded by remember { mutableStateOf(false) }
    val selectedName = selectedTeacherId
        ?.let { id -> staff.firstOrNull { it.id == id }?.name }
        ?: "Use class default" +
            (subject.defaultTeacherName?.let { " · $it" } ?: "")

    Column(Modifier.fillMaxWidth()) {
        Text(
            "Teacher for this arm",
            style = MaterialTheme.typography.labelLarge,
            color = EduCoreColors.Slate700,
        )
        Spacer(Modifier.height(EduCoreSpacing.Xs))
        Box(Modifier.fillMaxWidth()) {
            EduCoreSecondaryButton(
                text = selectedName,
                onClick = { expanded = true },
                modifier = Modifier.fillMaxWidth(),
                enabled = enabled,
            )
            DropdownMenu(
                expanded = expanded,
                onDismissRequest = { expanded = false },
            ) {
                DropdownMenuItem(
                    text = {
                        Text(
                            "Use class default" +
                                (subject.defaultTeacherName?.let { " · $it" } ?: "")
                        )
                    },
                    onClick = {
                        expanded = false
                        onSelect(null)
                    },
                )
                staff.forEach { teacher ->
                    DropdownMenuItem(
                        text = { Text(teacher.name) },
                        onClick = {
                            expanded = false
                            onSelect(teacher.id)
                        },
                    )
                }
            }
        }
    }
}

private fun LazyListScope.lifecycleGrades(
    state: ParallelLifecycleUiState,
    onSaveGrade: (List<Long>, String, Double?, Double?, String?, Boolean, Double?) -> Unit,
    onDeleteGrade: (Long) -> Unit,
) {
    val levels = state.workspace?.selectedCurriculum?.classes.orEmpty()
    item { GradeEditor(levels, state.isMutating, onSaveGrade) }
    items(levels, key = { "grades-" + it.id }) { level ->
        EduCoreDashboardCard(Modifier.fillMaxWidth()) {
            Text(level.name, style = MaterialTheme.typography.titleMedium)
            if (level.classGrades.isEmpty()) {
                Text("Uses programme grading scale.", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            } else {
                level.classGrades.forEach { grade ->
                    Spacer(Modifier.height(EduCoreSpacing.Md))
                    Text(
                        grade.gradeLetter + " · " + grade.minScore + "–" + grade.maxScore,
                        style = MaterialTheme.typography.titleSmall,
                    )
                    Text(
                        (grade.remark ?: "No remark") + " · " + if (grade.isPassGrade) "Pass" else "Fail",
                        style = MaterialTheme.typography.bodySmall,
                        color = EduCoreColors.Slate600,
                    )
                    Spacer(Modifier.height(EduCoreSpacing.Sm))
                    EduCoreDangerButton(
                        "Remove " + grade.gradeLetter,
                        { onDeleteGrade(grade.id) },
                        Modifier.fillMaxWidth(),
                        enabled = !state.isMutating,
                    )
                }
            }
        }
    }
}

@Composable
private fun GradeEditor(
    levels: List<ParallelLifecycleClass>,
    busy: Boolean,
    onSaveGrade: (List<Long>, String, Double?, Double?, String?, Boolean, Double?) -> Unit,
) {
    var selectedIds by remember(levels.map { it.id }) { mutableStateOf<Set<Long>>(emptySet()) }
    var letter by remember { mutableStateOf("") }
    var minScore by remember { mutableStateOf("") }
    var maxScore by remember { mutableStateOf("") }
    var remark by remember { mutableStateOf("") }
    var gradePoint by remember { mutableStateOf("") }
    var pass by remember { mutableStateOf(true) }

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text("Class grade system", style = MaterialTheme.typography.titleLarge)
        Text("Class-specific bands override the programme scale.", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
        Spacer(Modifier.height(EduCoreSpacing.Md))
        levels.forEach { level ->
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                Checkbox(
                    checked = selectedIds.contains(level.id),
                    onCheckedChange = { checked ->
                        selectedIds = if (checked) selectedIds + level.id else selectedIds - level.id
                    },
                    enabled = !busy,
                )
                Text(level.name, style = MaterialTheme.typography.bodyMedium)
            }
        }
        EduCoreTextField(letter, { letter = it }, "Grade letter", Modifier.fillMaxWidth(), enabled = !busy)
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        EduCoreTextField(minScore, { minScore = it }, "Minimum %", Modifier.fillMaxWidth(), enabled = !busy)
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        EduCoreTextField(maxScore, { maxScore = it }, "Maximum %", Modifier.fillMaxWidth(), enabled = !busy)
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        EduCoreTextField(remark, { remark = it }, "Remark", Modifier.fillMaxWidth(), enabled = !busy)
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        EduCoreTextField(gradePoint, { gradePoint = it }, "Grade point (optional)", Modifier.fillMaxWidth(), enabled = !busy)
        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
            Checkbox(pass, { pass = it }, enabled = !busy)
            Text("Pass grade", style = MaterialTheme.typography.bodyMedium)
        }
        Spacer(Modifier.height(EduCoreSpacing.Md))
        EduCorePrimaryButton(
            "Apply Grade Band",
            {
                onSaveGrade(
                    selectedIds.toList(),
                    letter,
                    minScore.toDoubleOrNull(),
                    maxScore.toDoubleOrNull(),
                    remark,
                    pass,
                    gradePoint.toDoubleOrNull(),
                )
            },
            Modifier.fillMaxWidth(),
            enabled = !busy && selectedIds.isNotEmpty() && letter.isNotBlank(),
            loading = busy,
        )
    }
}

private fun LazyListScope.lifecycleSkills(
    state: ParallelLifecycleUiState,
    onLoadSkills: (Long?, Long?) -> Unit,
    onSaveSkills: (List<ParallelSkillStudentDraft>) -> Unit,
) {
    val workspace = state.skillWorkspace

    item {
        SectionHeading(
            "Parallel skills rating",
            "Rate affective and psychomotor development independently from the conventional curriculum.",
        )
    }

    if (workspace == null) {
        item {
            if (state.isSkillsLoading) {
                EduCoreLoadingState(message = "Loading parallel skills ratings")
            } else {
                EduCoreSecondaryButton(
                    text = "Load Skills Rating",
                    onClick = { onLoadSkills(null, null) },
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !state.isMutating,
                )
            }
        }
        return
    }

    item {
        EduCoreDashboardCard(Modifier.fillMaxWidth()) {
            Text("Rating context", style = MaterialTheme.typography.titleMedium)
            Spacer(Modifier.height(EduCoreSpacing.Md))
            LifecycleMenu(
                label = "Parallel class arm",
                current = workspace.arms
                    .firstOrNull { it.id == workspace.selectedArmId }
                    ?.label ?: "Select class arm",
                options = workspace.arms.map { it.id to it.label },
                enabled = !state.isSkillsLoading && !state.isMutating,
                onSelect = { onLoadSkills(it, workspace.selectedTermId) },
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            LifecycleMenu(
                label = "Academic term",
                current = workspace.terms
                    .firstOrNull { it.id == workspace.selectedTermId }
                    ?.label ?: "Select term",
                options = workspace.terms.map { it.id to it.label },
                enabled = !state.isSkillsLoading && !state.isMutating,
                onSelect = { onLoadSkills(workspace.selectedArmId, it) },
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            Text(
                workspace.ratingScale.joinToString(" · ") {
                    it.value.toString() + " " + it.label
                },
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
        }
    }

    if (state.isSkillsLoading) {
        item { EduCoreLoadingState(message = "Refreshing skills ratings") }
    }

    if (workspace.students.isEmpty()) {
        item {
            EduCoreEmptyState(
                "No learners",
                "No active learner is assigned to the selected parallel class arm for this term.",
            )
        }
        return
    }

    items(
        workspace.students,
        key = { "skill-student-" + it.enrolmentId },
    ) { student ->
        ParallelSkillStudentCard(
            student = student,
            skills = workspace.skills,
            ratingScale = workspace.ratingScale.map { it.value to it.label },
            canManage = workspace.canManage,
            busy = state.isMutating,
            onSave = onSaveSkills,
        )
    }
}

@Composable
private fun ParallelSkillStudentCard(
    student: ParallelSkillStudent,
    skills: List<online.educoreng.educore.core.model.ParallelSkillDefinition>,
    ratingScale: List<Pair<Int, String>>,
    canManage: Boolean,
    busy: Boolean,
    onSave: (List<ParallelSkillStudentDraft>) -> Unit,
) {
    var ratings by remember(student.enrolmentId, student.ratings) {
        mutableStateOf<Map<Long, Int?>>(
            student.ratings.mapValues { it.value }
        )
    }

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text(student.name, style = MaterialTheme.typography.titleMedium)
        Text(
            student.admissionNumber ?: "No admission number",
            style = MaterialTheme.typography.bodySmall,
            color = EduCoreColors.Slate600,
        )

        listOf("affective", "psychomotor").forEach { category ->
            val categorySkills = skills
                .filter { it.category == category }
                .sortedBy { it.sortOrder }

            if (categorySkills.isNotEmpty()) {
                Spacer(Modifier.height(EduCoreSpacing.Lg))
                Text(
                    if (category == "affective") {
                        "Affective Domain"
                    } else {
                        "Psychomotor Domain"
                    },
                    style = MaterialTheme.typography.titleSmall,
                    color = EduCoreColors.Navy900,
                )

                categorySkills.forEach { skill ->
                    Spacer(Modifier.height(EduCoreSpacing.Sm))
                    StringLifecycleMenu(
                        label = skill.name,
                        current = ratings[skill.id]?.let { saved ->
                            ratingScale.firstOrNull { it.first == saved }
                                ?.let { saved.toString() + " · " + it.second }
                                ?: saved.toString()
                        } ?: "Not rated",
                        options = listOf("" to "Not rated") +
                            ratingScale.map {
                                it.first.toString() to
                                    (it.first.toString() + " · " + it.second)
                            },
                        enabled = canManage && !busy,
                        onSelect = { selected ->
                            ratings = ratings.toMutableMap().apply {
                                this[skill.id] = selected.toIntOrNull()
                            }
                        },
                    )
                }
            }
        }

        if (canManage) {
            Spacer(Modifier.height(EduCoreSpacing.Lg))
            EduCorePrimaryButton(
                text = "Save Skills Rating",
                onClick = {
                    onSave(
                        listOf(
                            ParallelSkillStudentDraft(
                                enrolmentId = student.enrolmentId,
                                ratings = ratings,
                            )
                        )
                    )
                },
                modifier = Modifier.fillMaxWidth(),
                enabled = !busy,
                loading = busy,
            )
        }
    }
}

private fun LazyListScope.lifecycleResults(
    state: ParallelLifecycleUiState,
    onLoadResults: (Long?, Long?) -> Unit,
    onLoadStudentResult: (Long, Long, Long) -> Unit,
    onCloseStudentResult: () -> Unit,
    onPublishResult: () -> Unit,
    onUnpublishResult: () -> Unit,
    onDownloadResultExport: (String) -> Unit,
    onDownloadStudentResultPdf: () -> Unit,
) {
    item {
        ParallelResultControls(
            state = state,
            onLoadResults = onLoadResults,
            onPublishResult = onPublishResult,
            onUnpublishResult = onUnpublishResult,
            onDownloadResultExport = onDownloadResultExport,
        )
    }

    val report = state.resultWorkspace?.report
    if (report != null) {
        if (report.blockers.isNotEmpty()) {
            items(report.blockers, key = { "result-blocker-" + it }) { blocker ->
                EduCoreInfoBanner(
                    title = "Publication requirement",
                    message = blocker,
                )
            }
        }

        if (report.rows.isEmpty()) {
            item {
                EduCoreEmptyState(
                    "No result rows",
                    "No active learner is enrolled in this parallel class for the selected session.",
                )
            }
        } else {
            items(report.rows, key = { "result-row-" + it.studentId }) { row ->
                EduCoreDashboardCard(Modifier.fillMaxWidth()) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.Top,
                    ) {
                        Column(Modifier.weight(1f)) {
                            Text(row.studentName, style = MaterialTheme.typography.titleSmall)
                            Text(
                                listOfNotNull(
                                    row.admissionNumber,
                                    row.armName?.let { "Arm $it" },
                                ).joinToString(" · "),
                                style = MaterialTheme.typography.bodySmall,
                                color = EduCoreColors.Slate600,
                            )
                        }
                        Text(
                            if (row.complete) "Complete" else "Incomplete",
                            style = MaterialTheme.typography.labelLarge,
                            color = if (row.complete) EduCoreColors.Success700 else EduCoreColors.Danger600,
                        )
                    }
                    Spacer(Modifier.height(EduCoreSpacing.Md))
                    KeyValueRow(
                        "Subjects",
                        row.completedSubjectCount.toString() + "/" + row.subjectCount,
                    )
                    KeyValueRow(
                        "Average",
                        row.average?.let { String.format("%.2f%%", it) } ?: "—",
                    )
                    KeyValueRow("Position", row.position?.toString() ?: "—")
                    KeyValueRow("Failed subjects", row.failedSubjects.toString())
                    Spacer(Modifier.height(EduCoreSpacing.Md))
                    EduCoreSecondaryButton(
                        text = "View Result Details",
                        onClick = {
                            onLoadStudentResult(
                                report.classId,
                                row.studentId,
                                report.termId,
                            )
                        },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !state.isResultLoading,
                    )
                }
            }
        }
    }

    state.studentResultDetail?.let { detail ->
        item {
            val sessionId = state.resultWorkspace?.terms
                ?.firstOrNull { it.id == detail.termId }
                ?.sessionId
            ParallelStudentResultDetailCard(
                detail = detail,
                onDownloadPdf = onDownloadStudentResultPdf,
                onDownloadCumulativePdf = sessionId?.let { resolvedSessionId ->
                    {
                        onDownloadCumulativeStudentResultPdf(
                            detail.classId,
                            detail.studentId,
                            resolvedSessionId,
                        )
                    }
                },
                onClose = onCloseStudentResult,
            )
        }
    }
}

@Composable
private fun ParallelResultControls(
    state: ParallelLifecycleUiState,
    onLoadResults: (Long?, Long?) -> Unit,
    onPublishResult: () -> Unit,
    onUnpublishResult: () -> Unit,
    onDownloadResultExport: (String) -> Unit,
    onDownloadParallelBroadsheetPdf: (String, Long, Long?, Long?, Long?) -> Unit,
) {
    val workspace = state.resultWorkspace
    var classId by remember(workspace?.selectedClassId) {
        mutableStateOf(workspace?.selectedClassId)
    }
    var termId by remember(workspace?.selectedTermId) {
        mutableStateOf(workspace?.selectedTermId)
    }
    var confirmPublish by remember { mutableStateOf(false) }
    var confirmUnpublish by remember { mutableStateOf(false) }

    LaunchedEffect(Unit) {
        if (workspace == null) {
            onLoadResults(null, null)
        }
    }

    Column(
        modifier = Modifier.fillMaxWidth(),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        SectionHeading(
            "Parallel results",
            "Review completeness, inspect learner results and control publication independently from conventional report cards.",
        )

        if (workspace == null) {
            if (state.isResultLoading) {
                EduCoreLoadingState(message = "Loading parallel result register")
            } else {
                EduCoreSecondaryButton(
                    text = "Load Results",
                    onClick = { onLoadResults(null, null) },
                    modifier = Modifier.fillMaxWidth(),
                )
            }
            return@Column
        }

        EduCoreDashboardCard(Modifier.fillMaxWidth()) {
            LifecycleMenu(
                label = "Parallel class",
                current = workspace.classes.firstOrNull { it.id == classId }?.label
                    ?: "Select parallel class",
                options = workspace.classes.map { it.id to it.label },
                enabled = !state.isResultLoading && !state.isMutating,
                onSelect = { classId = it },
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            LifecycleMenu(
                label = "Academic term",
                current = workspace.terms.firstOrNull { it.id == termId }?.label
                    ?: "Select academic term",
                options = workspace.terms.map { it.id to it.label },
                enabled = !state.isResultLoading && !state.isMutating,
                onSelect = { termId = it },
            )
            Spacer(Modifier.height(EduCoreSpacing.Md))
            EduCorePrimaryButton(
                text = "Open Result Register",
                onClick = { onLoadResults(classId, termId) },
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isResultLoading && classId != null && termId != null,
                loading = state.isResultLoading,
            )
        }

        workspace.report?.let { report ->
            EduCoreDashboardCard(Modifier.fillMaxWidth()) {
                Text(
                    (report.curriculumName ?: "Parallel Curriculum") + " · " + report.className,
                    style = MaterialTheme.typography.titleLarge,
                    color = EduCoreColors.Navy900,
                )
                Text(
                    listOfNotNull(
                        report.session,
                        report.term,
                        report.templateName?.let { "Template: $it" },
                    ).joinToString(" · "),
                    style = MaterialTheme.typography.bodySmall,
                    color = EduCoreColors.Slate600,
                )
                Spacer(Modifier.height(EduCoreSpacing.Md))
                KeyValueRow("Students", report.studentsCount.toString())
                KeyValueRow("Subjects", report.subjectsCount.toString())
                KeyValueRow(
                    "Complete",
                    report.completeStudentsCount.toString() + "/" + report.studentsCount,
                )
                KeyValueRow(
                    "Assessment weight",
                    String.format("%.2f%%", report.componentWeight),
                )
                KeyValueRow(
                    "Grading",
                    if (report.gradingSource == "class") "Class-specific" else "Programme default",
                )
                KeyValueRow(
                    "Publication",
                    if (report.isPublished) "Published" else "Draft",
                )

                Spacer(Modifier.height(EduCoreSpacing.Md))
                EduCoreSecondaryButton(
                    text = "Export Result Register PDF",
                    onClick = { onDownloadResultExport("pdf") },
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !state.isMutating,
                )
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                EduCoreSecondaryButton(
                    text = "Export Result Register CSV",
                    onClick = { onDownloadResultExport("csv") },
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !state.isMutating,
                )
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                EduCoreSecondaryButton(
                    text = "Download Term Broadsheet PDF",
                    onClick = {
                        onDownloadParallelBroadsheetPdf(
                            "termly",
                            report.classId,
                            null,
                            report.termId,
                            null,
                        )
                    },
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !state.isMutating,
                )
                workspace.terms.firstOrNull { it.id == report.termId }?.sessionId?.let { sessionId ->
                    Spacer(Modifier.height(EduCoreSpacing.Sm))
                    EduCoreSecondaryButton(
                        text = "Download Cumulative Broadsheet PDF",
                        onClick = {
                            onDownloadParallelBroadsheetPdf(
                                "cumulative",
                                report.classId,
                                null,
                                null,
                                sessionId,
                            )
                        },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !state.isMutating,
                    )
                }
                Spacer(Modifier.height(EduCoreSpacing.Md))
                if (report.isPublished) {
                    EduCoreDangerButton(
                        text = "Unpublish Result",
                        onClick = { confirmUnpublish = true },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !state.isMutating,
                    )
                } else {
                    EduCorePrimaryButton(
                        text = "Publish Result",
                        onClick = { confirmPublish = true },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !state.isMutating && report.canPublish,
                        loading = state.isMutating,
                    )
                }
            }

            EduCoreConfirmationDialog(
                visible = confirmPublish,
                title = "Publish parallel result?",
                message = "Publishing locks the source parallel scores and makes this result available through the authorised result channels.",
                confirmLabel = "Publish Result",
                onConfirm = {
                    confirmPublish = false
                    onPublishResult()
                },
                onDismiss = { confirmPublish = false },
            )

            EduCoreConfirmationDialog(
                visible = confirmUnpublish,
                title = "Unpublish parallel result?",
                message = "This reopens score entry unless another publication lock applies. Existing result records are retained.",
                confirmLabel = "Unpublish Result",
                destructive = true,
                onConfirm = {
                    confirmUnpublish = false
                    onUnpublishResult()
                },
                onDismiss = { confirmUnpublish = false },
            )
        }
    }
}

@Composable
private fun ParallelStudentResultDetailCard(
    detail: online.educoreng.educore.core.model.ParallelStudentResultDetail,
    onDownloadPdf: () -> Unit,
    onDownloadCumulativePdf: (() -> Unit)?,
    onClose: () -> Unit,
) {
    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text(
            detail.studentName,
            style = MaterialTheme.typography.titleLarge,
            color = EduCoreColors.Navy900,
        )
        Text(
            listOfNotNull(
                detail.admissionNumber,
                detail.armName?.let { "Arm $it" },
                detail.session,
                detail.term,
            ).joinToString(" · "),
            style = MaterialTheme.typography.bodySmall,
            color = EduCoreColors.Slate600,
        )
        Spacer(Modifier.height(EduCoreSpacing.Md))
        KeyValueRow(
            "Average",
            detail.average?.let { String.format("%.2f%%", it) } ?: "—",
        )
        KeyValueRow("Position", detail.position?.toString() ?: "—")
        KeyValueRow("Failed subjects", detail.failedSubjects.toString())
        KeyValueRow(
            "Status",
            if (detail.complete) "Complete" else "Incomplete",
        )

        detail.subjects.forEach { subject ->
            Spacer(Modifier.height(EduCoreSpacing.Lg))
            HorizontalDivider()
            Spacer(Modifier.height(EduCoreSpacing.Md))
            Text(subject.subject, style = MaterialTheme.typography.titleSmall)
            subject.components.forEach { component ->
                KeyValueRow(
                    component.name,
                    (component.score?.let { String.format("%.2f", it) } ?: "—") +
                        "/" + String.format("%.2f", component.maximum),
                )
            }
            KeyValueRow(
                "Total",
                subject.percentage?.let { String.format("%.2f%%", it) } ?: "—",
            )
            KeyValueRow("Grade", subject.grade ?: "—")
            KeyValueRow("Remark", subject.remark ?: "—")
        }

        Spacer(Modifier.height(EduCoreSpacing.Lg))
        EduCoreSecondaryButton(
            text = "Download Student Result PDF",
            onClick = onDownloadPdf,
            modifier = Modifier.fillMaxWidth(),
        )
        onDownloadCumulativePdf?.let { downloadCumulative ->
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreSecondaryButton(
                text = "Download Cumulative Result PDF",
                onClick = downloadCumulative,
                modifier = Modifier.fillMaxWidth(),
            )
        }
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        EduCoreSecondaryButton(
            text = "Close Result Details",
            onClick = onClose,
            modifier = Modifier.fillMaxWidth(),
        )
    }
}

private fun LazyListScope.lifecyclePromotion(
    state: ParallelLifecycleUiState,
    onSelectPromotionSessions: (Long?, Long?) -> Unit,
    onPreviewPromotion: (List<Long>) -> Unit,
    onExecutePromotion: () -> Unit,
    onSavePromotionRule: (List<Long>, String, Long?, Double?, Int?, Boolean, String, String, Boolean) -> Unit,
) {
    val levels = state.workspace?.selectedCurriculum?.classes.orEmpty()
    item { PromotionRuleEditor(levels, state.isMutating, onSavePromotionRule) }
    item { PromotionRunner(state, onSelectPromotionSessions, onPreviewPromotion, onExecutePromotion) }
    state.promotionPreview?.rows?.let { rows ->
        items(rows, key = { "preview-" + it.studentId }) { row -> PromotionPreviewCard(row) }
    }
}

@Composable
private fun PromotionRuleEditor(
    levels: List<ParallelLifecycleClass>,
    busy: Boolean,
    onSave: (List<Long>, String, Long?, Double?, Int?, Boolean, String, String, Boolean) -> Unit,
) {
    val activeLevels = levels.filter { it.isActive }
    var selectedIds by remember(activeLevels.map { it.id }) {
        mutableStateOf<Set<Long>>(emptySet())
    }
    var destinationMode by remember { mutableStateOf("next_by_order") }
    var destinationId by remember(activeLevels.map { it.id }) { mutableStateOf<Long?>(null) }
    var minimumAverage by remember { mutableStateOf("50") }
    var maxFailed by remember { mutableStateOf("2") }
    var complete by remember { mutableStateOf(true) }
    var failureAction by remember { mutableStateOf("repeat") }
    var armStrategy by remember { mutableStateOf("same_name") }

    LaunchedEffect(selectedIds, activeLevels) {
        if (selectedIds.size == 1) {
            val rule = activeLevels.firstOrNull { it.id == selectedIds.first() }?.promotionRule
            if (rule != null) {
                destinationId = rule.destinationClassId
                minimumAverage = rule.minimumAverage.toString()
                maxFailed = rule.maxFailedSubjects.toString()
                complete = rule.requireCompleteResult
                failureAction = rule.failureAction
                armStrategy = rule.armStrategy
                destinationMode = if (rule.isTerminal) "terminal" else "explicit"
            }
        } else if (selectedIds.size > 1 && destinationMode == "explicit") {
            destinationMode = "next_by_order"
            destinationId = null
        }
    }

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text("Promotion rules", style = MaterialTheme.typography.titleLarge)
        Text(
            "Apply one promotion policy to one or several class levels at once.",
            style = MaterialTheme.typography.bodySmall,
            color = EduCoreColors.Slate600,
        )
        Spacer(Modifier.height(EduCoreSpacing.Md))

        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            EduCoreSecondaryButton(
                text = "Select all",
                onClick = { selectedIds = activeLevels.map { it.id }.toSet() },
                modifier = Modifier.weight(1f),
                enabled = !busy && activeLevels.isNotEmpty(),
            )
            EduCoreSecondaryButton(
                text = "Clear",
                onClick = { selectedIds = emptySet() },
                modifier = Modifier.weight(1f),
                enabled = !busy && selectedIds.isNotEmpty(),
            )
        }
        Spacer(Modifier.height(EduCoreSpacing.Sm))

        activeLevels.forEach { level ->
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Checkbox(
                    checked = selectedIds.contains(level.id),
                    onCheckedChange = { checked ->
                        selectedIds = if (checked) {
                            selectedIds + level.id
                        } else {
                            selectedIds - level.id
                        }
                    },
                    enabled = !busy,
                )
                Column(Modifier.weight(1f)) {
                    Text(level.name, style = MaterialTheme.typography.bodyMedium)
                    Text(
                        level.promotionRule?.let { rule ->
                            if (rule.isTerminal) {
                                "Current: Terminal → Graduate"
                            } else {
                                "Current: Next → " +
                                    (rule.destinationClassName ?: "Not configured")
                            }
                        } ?: "No promotion rule configured",
                        style = MaterialTheme.typography.bodySmall,
                        color = EduCoreColors.Slate600,
                    )
                }
            }
        }

        Spacer(Modifier.height(EduCoreSpacing.Sm))
        StringLifecycleMenu(
            label = "Destination routing",
            current = when (destinationMode) {
                "terminal" -> "Terminal / graduate"
                "explicit" -> "Specific next level"
                else -> "Automatic next level by order"
            },
            options = buildList {
                add("next_by_order" to "Automatic next level by order")
                if (selectedIds.size <= 1) {
                    add("explicit" to "Specific next level")
                }
                add("terminal" to "Terminal / graduate")
            },
            enabled = !busy,
            onSelect = {
                destinationMode = it
                if (it != "explicit") destinationId = null
            },
        )

        if (destinationMode == "explicit") {
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            val sourceId = selectedIds.firstOrNull()
            LifecycleMenu(
                "Specific next level",
                activeLevels.firstOrNull { it.id == destinationId }?.name
                    ?: "Select destination",
                activeLevels.filter { it.id != sourceId }.map { it.id to it.name },
                !busy && selectedIds.size == 1,
            ) { destinationId = it }
        }

        Spacer(Modifier.height(EduCoreSpacing.Sm))
        EduCoreTextField(
            minimumAverage,
            { minimumAverage = it },
            "Minimum average %",
            Modifier.fillMaxWidth(),
            enabled = !busy,
        )
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        EduCoreTextField(
            maxFailed,
            { maxFailed = it },
            "Maximum failed subjects",
            Modifier.fillMaxWidth(),
            enabled = !busy,
        )
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        ToggleSetting(
            "Require complete published result",
            complete,
            !busy,
        ) { complete = it }
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        LifecycleMenu(
            "Failure action",
            if (failureAction == "retain") "Retain" else "Repeat",
            listOf(1L to "Repeat", 2L to "Retain"),
            !busy,
        ) { failureAction = if (it == 2L) "retain" else "repeat" }
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        LifecycleMenu(
            "Arm strategy",
            if (armStrategy == "first_available") "First available arm" else "Keep same arm name",
            listOf(1L to "Keep same arm name", 2L to "First available arm"),
            !busy,
        ) { armStrategy = if (it == 2L) "first_available" else "same_name" }
        Spacer(Modifier.height(EduCoreSpacing.Md))
        EduCorePrimaryButton(
            "Save Promotion Rule(s)",
            {
                onSave(
                    selectedIds.toList(),
                    destinationMode,
                    destinationId,
                    minimumAverage.toDoubleOrNull(),
                    maxFailed.toIntOrNull(),
                    complete,
                    failureAction,
                    armStrategy,
                    destinationMode == "terminal",
                )
            },
            Modifier.fillMaxWidth(),
            enabled = !busy &&
                selectedIds.isNotEmpty() &&
                (destinationMode != "explicit" || destinationId != null),
            loading = busy,
        )
        if (destinationMode == "next_by_order") {
            Spacer(Modifier.height(EduCoreSpacing.Xs))
            Text(
                "Each selected level routes to the next active level by configured order; the final level becomes terminal automatically.",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
        }
    }
}

@Composable
private fun PromotionRunner(
    state: ParallelLifecycleUiState,
    onSelectPromotionSessions: (Long?, Long?) -> Unit,
    onPreviewPromotion: (List<Long>) -> Unit,
    onExecutePromotion: () -> Unit,
) {
    val workspace = state.workspace ?: return
    val levels = workspace.selectedCurriculum?.classes.orEmpty().filter { it.isActive }
    var sourceId by remember(state.sourceSessionId, workspace.sessions) {
        mutableStateOf(state.sourceSessionId)
    }
    var targetId by remember(state.targetSessionId, workspace.sessions) {
        mutableStateOf(state.targetSessionId)
    }
    var selectedClassIds by remember(levels.map { it.id }) {
        mutableStateOf(
            state.promotionSourceClassIds
                .filter { id -> levels.any { it.id == id } }
                .toSet()
                .ifEmpty { levels.map { it.id }.toSet() }
        )
    }

    LaunchedEffect(sourceId, targetId) {
        onSelectPromotionSessions(sourceId, targetId)
    }

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text("Promotion engine", style = MaterialTheme.typography.titleLarge)
        Text(
            "Choose one or several class levels. Preview is mandatory before execution; unselected levels are left untouched.",
            style = MaterialTheme.typography.bodySmall,
            color = EduCoreColors.Slate600,
        )
        Spacer(Modifier.height(EduCoreSpacing.Md))
        LifecycleMenu(
            "Source session",
            workspace.sessions.firstOrNull { it.id == sourceId }?.name ?: "Select source",
            workspace.sessions.map { it.id to it.name },
            !state.isMutating,
        ) { sourceId = it }
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        LifecycleMenu(
            "Target session",
            workspace.sessions.firstOrNull { it.id == targetId }?.name ?: "Select target",
            workspace.sessions.map { it.id to it.name },
            !state.isMutating,
        ) { targetId = it }

        Spacer(Modifier.height(EduCoreSpacing.Md))
        Text(
            "Class levels to promote",
            style = MaterialTheme.typography.labelLarge,
            color = EduCoreColors.Slate700,
        )
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            EduCoreSecondaryButton(
                text = "Select all",
                onClick = { selectedClassIds = levels.map { it.id }.toSet() },
                modifier = Modifier.weight(1f),
                enabled = !state.isMutating && levels.isNotEmpty(),
            )
            EduCoreSecondaryButton(
                text = "Clear",
                onClick = { selectedClassIds = emptySet() },
                modifier = Modifier.weight(1f),
                enabled = !state.isMutating && selectedClassIds.isNotEmpty(),
            )
        }
        levels.forEach { level ->
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Checkbox(
                    checked = selectedClassIds.contains(level.id),
                    onCheckedChange = { checked ->
                        selectedClassIds = if (checked) {
                            selectedClassIds + level.id
                        } else {
                            selectedClassIds - level.id
                        }
                    },
                    enabled = !state.isMutating,
                )
                Text(level.name, style = MaterialTheme.typography.bodyMedium)
            }
        }

        Spacer(Modifier.height(EduCoreSpacing.Md))
        EduCoreSecondaryButton(
            "Preview Promotion",
            onClick = { onPreviewPromotion(selectedClassIds.toList()) },
            modifier = Modifier.fillMaxWidth(),
            enabled = !state.isMutating &&
                sourceId != null &&
                targetId != null &&
                sourceId != targetId &&
                selectedClassIds.isNotEmpty(),
        )
        state.promotionPreview?.let { preview ->
            Spacer(Modifier.height(EduCoreSpacing.Lg))
            KeyValueRow("Selected levels", preview.sourceClassIds.size.toString())
            KeyValueRow("Students", preview.counts.total.toString())
            KeyValueRow("Promote", preview.counts.promoted.toString())
            KeyValueRow(
                "Repeat / retain",
                (preview.counts.repeat + preview.counts.retain).toString(),
            )
            KeyValueRow("Graduate", preview.counts.graduated.toString())
            KeyValueRow("Blocked", preview.counts.blocked.toString())
            Spacer(Modifier.height(EduCoreSpacing.Md))
            EduCorePrimaryButton(
                "Execute Selected Promotion",
                onExecutePromotion,
                Modifier.fillMaxWidth(),
                enabled = !state.isMutating &&
                    preview.counts.blocked == 0 &&
                    preview.counts.total > 0,
                loading = state.isMutating,
            )
        }
    }
}

@Composable
private fun PromotionPreviewCard(row: ParallelPromotionPreviewRow) {
    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text(row.studentName, style = MaterialTheme.typography.titleSmall)
        Text(
            listOfNotNull(row.admissionNumber, row.sourceClass, row.sourceArm).joinToString(" · "),
            style = MaterialTheme.typography.bodySmall,
            color = EduCoreColors.Slate600,
        )
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        KeyValueRow("Decision", row.decision.replaceFirstChar { it.uppercase() })
        KeyValueRow("Average", row.average?.let { "%.1f%%".format(it) } ?: "—")
        KeyValueRow("Failed subjects", row.failedSubjects.toString())
        KeyValueRow(
            "Destination",
            listOfNotNull(row.destinationClass, row.destinationArm).joinToString(" · ").ifBlank { "—" },
        )
        Spacer(Modifier.height(EduCoreSpacing.Xs))
        Text(row.reason, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
    }
}

private fun LazyListScope.lifecycleTransfers(
    state: ParallelLifecycleUiState,
    onTransfer: (Long, Long, Long, String, String?) -> Unit,
) {
    item { TransferEditor(state, onTransfer) }
}

@Composable
private fun TransferEditor(
    state: ParallelLifecycleUiState,
    onTransfer: (Long, Long, Long, String, String?) -> Unit,
) {
    val workspace = state.workspace ?: return
    val levels = workspace.selectedCurriculum?.classes.orEmpty()
    var enrolmentId by remember(workspace.enrolments.map { it.id }) { mutableStateOf<Long?>(null) }
    var destinationClassId by remember(levels.map { it.id }) { mutableStateOf<Long?>(null) }
    var destinationArmId by remember(destinationClassId) { mutableStateOf<Long?>(null) }
    var reason by remember { mutableStateOf("") }
    var effectiveDate by remember { mutableStateOf("") }
    val arms = levels.firstOrNull { it.id == destinationClassId }?.arms.orEmpty().filter { it.isActive }

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text("Intra- and inter-class transfer", style = MaterialTheme.typography.titleLarge)
        Text(
            "Same level + different arm = intra-class. Different level = inter-class. Inter-class movement is blocked after score entry.",
            style = MaterialTheme.typography.bodySmall,
            color = EduCoreColors.Slate600,
        )
        Spacer(Modifier.height(EduCoreSpacing.Md))
        LifecycleMenu(
            "Student / current placement",
            workspace.enrolments.firstOrNull { it.id == enrolmentId }?.let {
                (it.studentName ?: "Student") + " · " + (it.className ?: "") + " " + (it.armName ?: "")
            } ?: "Select learner",
            workspace.enrolments.map {
                it.id to ((it.studentName ?: "Student") + " · " + (it.admissionNumber ?: "") + " · " +
                    (it.className ?: "") + " " + (it.armName ?: ""))
            },
            !state.isMutating,
        ) { enrolmentId = it }
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        LifecycleMenu(
            "Destination level",
            levels.firstOrNull { it.id == destinationClassId }?.name ?: "Select destination",
            levels.filter { it.isActive }.map { it.id to it.name },
            !state.isMutating,
        ) { destinationClassId = it }
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        LifecycleMenu(
            "Destination arm",
            arms.firstOrNull { it.id == destinationArmId }?.name ?: "Select arm",
            arms.map { arm ->
                arm.id to (arm.name + (arm.capacity?.let { " · Capacity " + it } ?: ""))
            },
            !state.isMutating && destinationClassId != null,
        ) { destinationArmId = it }
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        EduCoreTextField(reason, { reason = it }, "Reason", Modifier.fillMaxWidth(), enabled = !state.isMutating)
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        EduCoreTextField(
            effectiveDate,
            { effectiveDate = it },
            "Effective date YYYY-MM-DD (optional)",
            Modifier.fillMaxWidth(),
            enabled = !state.isMutating,
        )
        Spacer(Modifier.height(EduCoreSpacing.Md))
        EduCorePrimaryButton(
            "Complete Transfer",
            {
                val placement = enrolmentId
                val level = destinationClassId
                val arm = destinationArmId
                if (placement != null && level != null && arm != null) {
                    onTransfer(placement, level, arm, reason, effectiveDate)
                }
            },
            Modifier.fillMaxWidth(),
            enabled = !state.isMutating && enrolmentId != null && destinationClassId != null &&
                destinationArmId != null && reason.isNotBlank(),
            loading = state.isMutating,
        )
    }
}

private fun LazyListScope.lifecycleHistory(workspace: ParallelLifecycleWorkspace) {
    item { SectionHeading("Recent transfers", "Latest 30 movement records") }
    if (workspace.transfers.isEmpty()) {
        item { EduCoreEmptyState("No transfer history", "No parallel-curriculum transfer has been processed yet.") }
    } else {
        items(workspace.transfers, key = { "transfer-" + it.id }) { transfer ->
            EduCoreDashboardCard(Modifier.fillMaxWidth()) {
                Text(
                    (transfer.studentName ?: "Student") + " · " + transfer.movementType.replace('_', ' '),
                    style = MaterialTheme.typography.titleSmall,
                )
                Text(
                    (transfer.fromClass ?: "—") + " " + (transfer.fromArm ?: "") + " → " +
                        (transfer.toClass ?: "—") + " " + (transfer.toArm ?: ""),
                    style = MaterialTheme.typography.bodyMedium,
                )
                Text(
                    listOfNotNull(transfer.session, transfer.reason, transfer.effectiveDate).joinToString(" · "),
                    style = MaterialTheme.typography.bodySmall,
                    color = EduCoreColors.Slate600,
                )
            }
        }
    }

    item { SectionHeading("Recent promotions", "Latest 30 progression decisions") }
    if (workspace.promotions.isEmpty()) {
        item { EduCoreEmptyState("No promotion history", "No parallel-curriculum promotion has been processed yet.") }
    } else {
        items(workspace.promotions, key = { "promotion-" + it.id }) { promotion ->
            EduCoreDashboardCard(Modifier.fillMaxWidth()) {
                Text(
                    (promotion.studentName ?: "Student") + " · " +
                        promotion.decision.replaceFirstChar { it.uppercase() },
                    style = MaterialTheme.typography.titleSmall,
                )
                Text(
                    (promotion.sourceClass ?: "—") + " " + (promotion.sourceArm ?: "") + " → " +
                        (promotion.destinationClass ?: "Graduate") + " " + (promotion.destinationArm ?: ""),
                    style = MaterialTheme.typography.bodyMedium,
                )
                Text(
                    (promotion.sourceSession ?: "—") + " → " + (promotion.targetSession ?: "—") +
                        " · Avg " + (promotion.averageScore?.let { "%.1f%%".format(it) } ?: "—"),
                    style = MaterialTheme.typography.bodySmall,
                    color = EduCoreColors.Slate600,
                )
            }
        }
    }
}

private fun LazyListScope.lifecycleOperations(
    state: ParallelLifecycleUiState,
    onLoadOperations: (Long?, Long?, Long?, String?) -> Unit,
    onCreateTimetablePeriod: (Long, Long, Long, String, String, String, String?) -> Unit,
    onDeleteTimetablePeriod: (Long) -> Unit,
    onSaveWorkingDays: (List<ParallelWorkingDayDraft>) -> Unit,
    onClockInParallelStaff: () -> Unit,
    onClockOutParallelStaff: () -> Unit,
    onSaveParallelAttendance: (List<ParallelAttendanceDraft>) -> Unit,
    onDownloadAttendanceExport: (String) -> Unit,
) {
    item {
        ParallelOperationsPanel(
            state = state,
            onLoadOperations = onLoadOperations,
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

@Composable
internal fun ParallelOperationsPanel(
    state: ParallelLifecycleUiState,
    onLoadOperations: (Long?, Long?, Long?, String?) -> Unit,
    onCreateTimetablePeriod: (Long, Long, Long, String, String, String, String?) -> Unit,
    onDeleteTimetablePeriod: (Long) -> Unit,
    onSaveWorkingDays: (List<ParallelWorkingDayDraft>) -> Unit,
    onClockInParallelStaff: () -> Unit,
    onClockOutParallelStaff: () -> Unit,
    onSaveParallelAttendance: (List<ParallelAttendanceDraft>) -> Unit,
    onDownloadAttendanceExport: (String) -> Unit,
) {
    val operations = state.operationsWorkspace

    Column(
        modifier = Modifier.fillMaxWidth(),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        SectionHeading(
            "Parallel timetable & attendance",
            "Schedule each parallel class arm independently and keep its daily attendance separate from conventional attendance.",
        )

        if (operations == null) {
            if (state.isOperationsLoading) {
                EduCoreLoadingState(message = "Loading parallel timetable and attendance")
            } else {
                EduCoreSecondaryButton(
                    text = "Load Timetable & Attendance",
                    onClick = { onLoadOperations(null, null, null, null) },
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !state.isMutating,
                )
            }
            return@Column
        }

        val selected = operations.selected
        val selectedClass = operations.classes.firstOrNull { it.id == selected.classId }
        val selectedArm = selectedClass?.arms?.firstOrNull { it.id == selected.armId }
        var date by remember(selected.date) { mutableStateOf(selected.date) }

        EduCoreDashboardCard(Modifier.fillMaxWidth()) {
            Text("Working context", style = MaterialTheme.typography.titleMedium)
            Spacer(Modifier.height(EduCoreSpacing.Md))
            LifecycleMenu(
                label = "Parallel class level",
                current = selectedClass?.name ?: "Select class level",
                options = operations.classes.map { it.id to it.name },
                enabled = !state.isOperationsLoading && !state.isMutating,
                onSelect = { classId ->
                    onLoadOperations(classId, null, selected.termId, selected.date)
                },
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            LifecycleMenu(
                label = "Class arm",
                current = selectedArm?.name ?: "Select class arm",
                options = selectedClass?.arms.orEmpty().map { it.id to it.name },
                enabled = !state.isOperationsLoading && !state.isMutating && selectedClass != null,
                onSelect = { armId ->
                    onLoadOperations(selectedClass?.id, armId, selected.termId, selected.date)
                },
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            LifecycleMenu(
                label = "Academic term",
                current = operations.terms.firstOrNull { it.id == selected.termId }?.let {
                    listOfNotNull(it.sessionName, it.name).joinToString(" · ")
                } ?: "Select term",
                options = operations.terms.map {
                    it.id to listOfNotNull(it.sessionName, it.name).joinToString(" · ")
                },
                enabled = !state.isOperationsLoading && !state.isMutating,
                onSelect = { termId ->
                    onLoadOperations(selected.classId, selected.armId, termId, selected.date)
                },
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreTextField(
                value = date,
                onValueChange = { date = it },
                label = "Attendance date (YYYY-MM-DD)",
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isOperationsLoading && !state.isMutating,
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreSecondaryButton(
                text = "Load Selected Date",
                onClick = {
                    onLoadOperations(
                        selected.classId,
                        selected.armId,
                        selected.termId,
                        date.trim().takeIf(String::isNotBlank),
                    )
                },
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isOperationsLoading && !state.isMutating && date.isNotBlank(),
            )
        }

        if (state.isOperationsLoading) {
            EduCoreLoadingState(message = "Refreshing parallel operations")
        }

        ParallelWorkingDaysCard(
            state = state,
            onSaveWorkingDays = onSaveWorkingDays,
        )

        ParallelStaffAttendanceCard(
            state = state,
            onClockIn = onClockInParallelStaff,
            onClockOut = onClockOutParallelStaff,
        )

        ParallelTimetableCard(
            state = state,
            selectedClass = selectedClass,
            selectedArmId = selected.armId,
            onCreateTimetablePeriod = onCreateTimetablePeriod,
            onDeleteTimetablePeriod = onDeleteTimetablePeriod,
        )

        ParallelAttendanceCard(
            state = state,
            onSaveParallelAttendance = onSaveParallelAttendance,
            onDownloadAttendanceExport = onDownloadAttendanceExport,
        )
    }
}

@Composable
private fun ParallelWorkingDaysCard(
    state: ParallelLifecycleUiState,
    onSaveWorkingDays: (List<ParallelWorkingDayDraft>) -> Unit,
) {
    val operations = state.operationsWorkspace ?: return
    var drafts by remember(operations.workingDays) {
        mutableStateOf(
            operations.workingDays.map {
                ParallelWorkingDayDraft(
                    dayOfWeek = it.dayOfWeek,
                    isWorking = it.isWorking,
                    resumptionTime = it.resumptionTime,
                    closingTime = it.closingTime,
                    graceMinutes = it.graceMinutes,
                )
            }
        )
    }

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text("Parallel working days & staff hours", style = MaterialTheme.typography.titleMedium)
        Text(
            "These days and hours are independent from the conventional curriculum. Each day can have a different resumption and closing time.",
            style = MaterialTheme.typography.bodySmall,
            color = EduCoreColors.Slate600,
        )
        drafts.forEachIndexed { index, item ->
            Spacer(Modifier.height(EduCoreSpacing.Md))
            HorizontalDivider()
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween,
            ) {
                Text(
                    item.dayOfWeek.replaceFirstChar { it.uppercase() },
                    style = MaterialTheme.typography.titleSmall,
                )
                Switch(
                    checked = item.isWorking,
                    onCheckedChange = { checked ->
                        drafts = drafts.toMutableList().also {
                            it[index] = item.copy(isWorking = checked)
                        }
                    },
                    enabled = operations.capabilities.manageWorkingDays && !state.isMutating,
                )
            }
            if (item.isWorking) {
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                ) {
                    EduCoreTextField(
                        value = item.resumptionTime.orEmpty(),
                        onValueChange = { value ->
                            drafts = drafts.toMutableList().also {
                                it[index] = item.copy(resumptionTime = value)
                            }
                        },
                        label = "Resumption HH:mm",
                        modifier = Modifier.weight(1f),
                        enabled = operations.capabilities.manageWorkingDays && !state.isMutating,
                    )
                    EduCoreTextField(
                        value = item.closingTime.orEmpty(),
                        onValueChange = { value ->
                            drafts = drafts.toMutableList().also {
                                it[index] = item.copy(closingTime = value)
                            }
                        },
                        label = "Closing HH:mm",
                        modifier = Modifier.weight(1f),
                        enabled = operations.capabilities.manageWorkingDays && !state.isMutating,
                    )
                }
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                EduCoreTextField(
                    value = item.graceMinutes.toString(),
                    onValueChange = { value ->
                        val minutes = value.toIntOrNull()?.coerceIn(0, 180) ?: 0
                        drafts = drafts.toMutableList().also {
                            it[index] = item.copy(graceMinutes = minutes)
                        }
                    },
                    label = "Late grace period (minutes)",
                    modifier = Modifier.fillMaxWidth(),
                    enabled = operations.capabilities.manageWorkingDays && !state.isMutating,
                )
            }
        }
        if (operations.capabilities.manageWorkingDays) {
            Spacer(Modifier.height(EduCoreSpacing.Lg))
            EduCorePrimaryButton(
                text = "Save Parallel Working Days",
                onClick = { onSaveWorkingDays(drafts) },
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isMutating && drafts.size == 7,
                loading = state.isMutating,
            )
        }
    }
}

@Composable
private fun ParallelStaffAttendanceCard(
    state: ParallelLifecycleUiState,
    onClockIn: () -> Unit,
    onClockOut: () -> Unit,
) {
    val operations = state.operationsWorkspace ?: return
    val sheet = operations.staffAttendance ?: return

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text("Parallel staff attendance", style = MaterialTheme.typography.titleMedium)
        Text(
            "Attendance is evaluated against the selected parallel curriculum's day-specific working hours, not the conventional school schedule.",
            style = MaterialTheme.typography.bodySmall,
            color = EduCoreColors.Slate600,
        )
        Spacer(Modifier.height(EduCoreSpacing.Md))
        KeyValueRow("Date", sheet.date)
        KeyValueRow("Day", sheet.dayOfWeek.replaceFirstChar { it.uppercase() })
        KeyValueRow("Working day", if (sheet.isWorkingDay) "Yes" else "No")
        KeyValueRow("Resumption", sheet.resumptionTime ?: "—")
        KeyValueRow("Closing", sheet.closingTime ?: "—")
        KeyValueRow("Late grace", sheet.graceMinutes.toString() + " min")

        if (sheet.canClockSelf && operations.capabilities.clockParallelStaff && sheet.isWorkingDay) {
            val selfRecord = sheet.selfRecord
            Spacer(Modifier.height(EduCoreSpacing.Md))
            if (selfRecord == null || selfRecord.clockInTime == null) {
                EduCorePrimaryButton(
                    text = "Clock In",
                    onClick = onClockIn,
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !state.isMutating,
                    loading = state.isMutating,
                )
            } else if (selfRecord.clockOutTime == null) {
                EduCoreInfoBanner(
                    title = "Clocked in",
                    message = selfRecord.clockInTime + " · " +
                        (selfRecord.status?.replace('_', ' ') ?: "recorded"),
                )
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                EduCorePrimaryButton(
                    text = "Clock Out",
                    onClick = onClockOut,
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !state.isMutating,
                    loading = state.isMutating,
                )
            } else {
                EduCoreInfoBanner(
                    title = "Attendance complete",
                    message = selfRecord.clockInTime + "–" + selfRecord.clockOutTime +
                        " · " + (selfRecord.departureStatus?.replace('_', ' ') ?: "completed"),
                )
            }
        }

        Spacer(Modifier.height(EduCoreSpacing.Md))
        if (sheet.staff.isEmpty()) {
            EduCoreEmptyState(
                "No assigned parallel staff",
                "Assign a class teacher or subject teacher to populate this attendance register.",
            )
        } else {
            sheet.staff.forEach { person ->
                HorizontalDivider()
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                Text(person.name, style = MaterialTheme.typography.titleSmall)
                Text(
                    listOf(
                        "Arrival: " + (person.clockInTime ?: "—"),
                        "Status: " + (person.status?.replace('_', ' ') ?: "Not recorded"),
                        "Departure: " + (person.clockOutTime ?: "—"),
                        "Departure status: " + (person.departureStatus?.replace('_', ' ') ?: "—"),
                    ).joinToString(" · "),
                    style = MaterialTheme.typography.bodySmall,
                    color = EduCoreColors.Slate600,
                )
                Spacer(Modifier.height(EduCoreSpacing.Sm))
            }
        }
    }
}

@Composable
private fun ParallelTimetableCard(
    state: ParallelLifecycleUiState,
    selectedClass: online.educoreng.educore.core.model.ParallelOperationsClass?,
    selectedArmId: Long?,
    onCreateTimetablePeriod: (Long, Long, Long, String, String, String, String?) -> Unit,
    onDeleteTimetablePeriod: (Long) -> Unit,
) {
    val operations = state.operationsWorkspace ?: return
    var subjectId by remember(selectedClass?.id, selectedClass?.subjects) {
        mutableStateOf(selectedClass?.subjects?.firstOrNull()?.id)
    }
    val enabledDays = operations.workingDays.filter { it.isWorking }
    var day by remember(enabledDays.map { it.dayOfWeek }) {
        mutableStateOf(enabledDays.firstOrNull()?.dayOfWeek ?: "monday")
    }
    var start by remember { mutableStateOf("") }
    var end by remember { mutableStateOf("") }
    var venue by remember { mutableStateOf("") }

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text("Weekly timetable", style = MaterialTheme.typography.titleMedium)
        Text(
            "Teacher assignments are resolved automatically. EduCore blocks class-arm overlaps and teacher clashes with both conventional and parallel schedules.",
            style = MaterialTheme.typography.bodySmall,
            color = EduCoreColors.Slate600,
        )

        if (enabledDays.isEmpty()) {
            Spacer(Modifier.height(EduCoreSpacing.Md))
            EduCoreInfoBanner(
                title = "No working day enabled",
                message = "Enable at least one parallel working day before adding timetable periods.",
            )
        }

        if (operations.capabilities.manageTimetable && selectedClass != null && selectedArmId != null) {
            Spacer(Modifier.height(EduCoreSpacing.Md))
            LifecycleMenu(
                label = "Subject",
                current = selectedClass.subjects.firstOrNull { it.id == subjectId }?.name
                    ?: "Select subject",
                options = selectedClass.subjects.map { it.id to it.name },
                enabled = !state.isMutating,
                onSelect = { subjectId = it },
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            StringLifecycleMenu(
                label = "Day",
                current = day.replaceFirstChar { it.uppercase() },
                options = enabledDays.map {
                    it.dayOfWeek to (
                        it.dayOfWeek.replaceFirstChar { ch -> ch.uppercase() } +
                            " · " + (it.resumptionTime ?: "—") + "–" + (it.closingTime ?: "—")
                        )
                },
                enabled = !state.isMutating,
                onSelect = { day = it },
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                EduCoreTextField(
                    start,
                    { start = it },
                    "Start HH:mm",
                    Modifier.weight(1f),
                    enabled = !state.isMutating,
                )
                EduCoreTextField(
                    end,
                    { end = it },
                    "End HH:mm",
                    Modifier.weight(1f),
                    enabled = !state.isMutating,
                )
            }
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreTextField(
                venue,
                { venue = it },
                "Venue (optional)",
                Modifier.fillMaxWidth(),
                enabled = !state.isMutating,
            )
            Spacer(Modifier.height(EduCoreSpacing.Md))
            EduCorePrimaryButton(
                text = "Add Timetable Period",
                onClick = {
                    val selectedSubject = subjectId
                    if (selectedSubject != null) {
                        onCreateTimetablePeriod(
                            selectedClass.id,
                            selectedArmId,
                            selectedSubject,
                            day,
                            start.trim(),
                            end.trim(),
                            venue,
                        )
                    }
                },
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isMutating &&
                    enabledDays.isNotEmpty() &&
                    subjectId != null &&
                    start.isNotBlank() &&
                    end.isNotBlank(),
                loading = state.isMutating,
            )
        }

        Spacer(Modifier.height(EduCoreSpacing.Lg))
        if (operations.periods.isEmpty()) {
            EduCoreEmptyState(
                "No timetable periods",
                "No parallel lesson has been scheduled for this class arm in the selected session.",
            )
        } else {
            val dayOrder = operations.workingDays.map { it.dayOfWeek }
            dayOrder.forEach { dayName ->
                val periods = operations.periods
                    .filter { it.dayOfWeek == dayName }
                    .sortedBy { it.startTime }
                if (periods.isNotEmpty()) {
                    Text(
                        dayName.replaceFirstChar { it.uppercase() },
                        style = MaterialTheme.typography.titleSmall,
                        color = EduCoreColors.Navy900,
                    )
                    periods.forEach { period ->
                        Spacer(Modifier.height(EduCoreSpacing.Sm))
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                            verticalAlignment = Alignment.Top,
                        ) {
                            Column(Modifier.weight(1f)) {
                                Text(
                                    period.startTime + "–" + period.endTime + " · " + period.subject,
                                    style = MaterialTheme.typography.bodyMedium,
                                )
                                Text(
                                    listOfNotNull(
                                        period.teacher ?: "Teacher unassigned",
                                        period.venue,
                                    ).joinToString(" · "),
                                    style = MaterialTheme.typography.bodySmall,
                                    color = EduCoreColors.Slate600,
                                )
                            }
                            if (operations.capabilities.manageTimetable) {
                                EduCoreDangerButton(
                                    text = "Remove",
                                    onClick = { onDeleteTimetablePeriod(period.id) },
                                    enabled = !state.isMutating,
                                )
                            }
                        }
                    }
                    Spacer(Modifier.height(EduCoreSpacing.Md))
                }
            }
        }
    }
}

@Composable
private fun ParallelAttendanceCard(
    state: ParallelLifecycleUiState,
    onSaveParallelAttendance: (List<ParallelAttendanceDraft>) -> Unit,
    onDownloadAttendanceExport: (String) -> Unit,
) {
    val operations = state.operationsWorkspace ?: return
    val attendance = operations.attendance

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text("Daily attendance", style = MaterialTheme.typography.titleMedium)
        Text(
            "Attendance is recorded against each learner's parallel enrolment and remains independent from conventional attendance.",
            style = MaterialTheme.typography.bodySmall,
            color = EduCoreColors.Slate600,
        )
        Spacer(Modifier.height(EduCoreSpacing.Md))

        if (operations.capabilities.exportAttendance) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                EduCoreSecondaryButton(
                    text = "Export PDF",
                    onClick = { onDownloadAttendanceExport("pdf") },
                    modifier = Modifier.weight(1f),
                    enabled = !state.isMutating,
                )
                EduCoreSecondaryButton(
                    text = "Export CSV",
                    onClick = { onDownloadAttendanceExport("csv") },
                    modifier = Modifier.weight(1f),
                    enabled = !state.isMutating,
                )
            }
            Spacer(Modifier.height(EduCoreSpacing.Md))
        }

        if (attendance != null && !attendance.isWorkingDay) {
            EduCoreInfoBanner(
                title = "Non-working day",
                message = "The selected date is not enabled as a working day for this parallel curriculum. Learner attendance cannot be saved for this date.",
            )
            return@EduCoreDashboardCard
        }

        if (!operations.capabilities.saveAttendance) {
            EduCoreInfoBanner(
                title = "Attendance restricted",
                message = "Only authorized administrators or an effective teacher assigned to this parallel arm can mark attendance.",
            )
            return@EduCoreDashboardCard
        }

        if (attendance == null) {
            EduCoreEmptyState(
                "Attendance unavailable",
                "Select a class arm, term and date to load the parallel attendance sheet.",
            )
            return@EduCoreDashboardCard
        }

        var drafts by remember(
            attendance.version,
            attendance.date,
            operations.selected.armId,
            attendance.students.map { it.enrolmentId },
        ) {
            mutableStateOf(
                attendance.students.map {
                    ParallelAttendanceDraft(
                        enrolmentId = it.enrolmentId,
                        status = it.status ?: "present",
                        remark = it.remark,
                    )
                }
            )
        }

        KeyValueRow("Date", attendance.date)
        KeyValueRow("Learners", attendance.students.size.toString())

        if (attendance.students.isEmpty()) {
            Spacer(Modifier.height(EduCoreSpacing.Md))
            EduCoreEmptyState(
                "No learners",
                "No active learner is assigned to this parallel arm for the selected session.",
            )
            return@EduCoreDashboardCard
        }

        attendance.students.forEach { student ->
            val current = drafts.firstOrNull { it.enrolmentId == student.enrolmentId }
                ?: ParallelAttendanceDraft(student.enrolmentId, "present")
            Spacer(Modifier.height(EduCoreSpacing.Md))
            HorizontalDivider()
            Spacer(Modifier.height(EduCoreSpacing.Md))
            Text(student.name, style = MaterialTheme.typography.titleSmall)
            Text(
                student.admissionNumber ?: "No admission number",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            StringLifecycleMenu(
                label = "Status",
                current = current.status.replaceFirstChar { it.uppercase() },
                options = listOf(
                    "present" to "Present",
                    "absent" to "Absent",
                    "late" to "Late",
                    "excused" to "Excused",
                ),
                enabled = !state.isMutating,
                onSelect = { status ->
                    drafts = drafts.map {
                        if (it.enrolmentId == student.enrolmentId) it.copy(status = status) else it
                    }
                },
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreTextField(
                value = current.remark.orEmpty(),
                onValueChange = { remark ->
                    drafts = drafts.map {
                        if (it.enrolmentId == student.enrolmentId) {
                            it.copy(remark = remark.takeIf(String::isNotBlank))
                        } else it
                    }
                },
                label = "Remark (optional)",
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isMutating,
            )
        }

        Spacer(Modifier.height(EduCoreSpacing.Lg))
        EduCorePrimaryButton(
            text = "Save Parallel Attendance",
            onClick = { onSaveParallelAttendance(drafts) },
            modifier = Modifier.fillMaxWidth(),
            enabled = !state.isMutating && drafts.isNotEmpty(),
            loading = state.isMutating,
        )
    }
}

@Composable
private fun SectionHeading(title: String, subtitle: String) {
    Column(Modifier.fillMaxWidth()) {
        Text(title, style = MaterialTheme.typography.titleMedium, color = EduCoreColors.Navy900)
        if (subtitle.isNotBlank()) {
            Text(subtitle, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
        }
    }
}

@Composable
private fun LifecycleMenu(
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
            DropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
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

@Composable
private fun ToggleSetting(
    label: String,
    checked: Boolean,
    enabled: Boolean,
    onCheckedChange: (Boolean) -> Unit,
) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.SpaceBetween,
    ) {
        Text(label, style = MaterialTheme.typography.bodyMedium, modifier = Modifier.weight(1f))
        Switch(checked = checked, onCheckedChange = onCheckedChange, enabled = enabled)
    }
}

@Composable
private fun KeyValueRow(label: String, value: String) {
    Row(
        modifier = Modifier.fillMaxWidth().padding(vertical = EduCoreSpacing.Xs),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Text(label, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
        Text(value, style = MaterialTheme.typography.labelLarge, color = EduCoreColors.Navy900)
    }
}
