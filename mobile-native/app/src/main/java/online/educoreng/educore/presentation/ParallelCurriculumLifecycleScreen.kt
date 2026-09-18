package online.educoreng.educore.presentation

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
import online.educoreng.educore.core.designsystem.component.EduCoreDangerButton
import online.educoreng.educore.core.designsystem.component.EduCoreDashboardCard
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
import online.educoreng.educore.core.model.ParallelLifecycleWorkspace
import online.educoreng.educore.core.model.ParallelPromotionPreviewRow

private enum class ParallelLifecycleTab(val label: String) {
    OVERVIEW("Overview"),
    ARMS("Class Arms"),
    GRADES("Grade System"),
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
    onPreviewPromotion: () -> Unit,
    onExecutePromotion: () -> Unit,
    onCreateArm: (Long, String, String?, Int?) -> Unit,
    onUpdateArm: (Long, String, String?, Int?) -> Unit,
    onArchiveArm: (Long) -> Unit,
    onSaveGrade: (List<Long>, String, Double?, Double?, String?, Boolean, Double?) -> Unit,
    onDeleteGrade: (Long) -> Unit,
    onSavePromotionRule: (Long, Long?, Double?, Int?, Boolean, String, String, Boolean) -> Unit,
    onTransfer: (Long, Long, Long, String, String?) -> Unit,
) {
    val workspace = state.workspace
    var tab by remember { mutableStateOf(ParallelLifecycleTab.OVERVIEW) }

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

        if (workspace == null || workspace.curricula.isEmpty()) {
            item {
                EduCoreEmptyState(
                    title = "No parallel curriculum",
                    message = "Enable and configure a parallel curriculum programme first.",
                    actionLabel = "Retry",
                    onAction = onRefresh,
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
            ParallelLifecycleTab.ARMS -> lifecycleArms(state, onCreateArm, onUpdateArm, onArchiveArm)
            ParallelLifecycleTab.GRADES -> lifecycleGrades(state, onSaveGrade, onDeleteGrade)
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

private fun LazyListScope.lifecyclePromotion(
    state: ParallelLifecycleUiState,
    onSelectPromotionSessions: (Long?, Long?) -> Unit,
    onPreviewPromotion: () -> Unit,
    onExecutePromotion: () -> Unit,
    onSavePromotionRule: (Long, Long?, Double?, Int?, Boolean, String, String, Boolean) -> Unit,
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
    onSave: (Long, Long?, Double?, Int?, Boolean, String, String, Boolean) -> Unit,
) {
    var sourceId by remember(levels.map { it.id }) { mutableStateOf(levels.firstOrNull()?.id) }
    var destinationId by remember(levels.map { it.id }) { mutableStateOf<Long?>(null) }
    var minimumAverage by remember { mutableStateOf("50") }
    var maxFailed by remember { mutableStateOf("2") }
    var complete by remember { mutableStateOf(true) }
    var terminal by remember { mutableStateOf(false) }
    var failureAction by remember { mutableStateOf("repeat") }
    var armStrategy by remember { mutableStateOf("same_name") }

    LaunchedEffect(sourceId, levels) {
        val rule = levels.firstOrNull { it.id == sourceId }?.promotionRule
        if (rule != null) {
            destinationId = rule.destinationClassId
            minimumAverage = rule.minimumAverage.toString()
            maxFailed = rule.maxFailedSubjects.toString()
            complete = rule.requireCompleteResult
            terminal = rule.isTerminal
            failureAction = rule.failureAction
            armStrategy = rule.armStrategy
        }
    }

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text("Promotion rules", style = MaterialTheme.typography.titleLarge)
        Spacer(Modifier.height(EduCoreSpacing.Md))
        LifecycleMenu(
            "Source level",
            levels.firstOrNull { it.id == sourceId }?.name ?: "Select source",
            levels.map { it.id to it.name },
            !busy,
        ) { sourceId = it }
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        if (!terminal) {
            LifecycleMenu(
                "Next level",
                levels.firstOrNull { it.id == destinationId }?.name ?: "Select destination",
                levels.filter { it.id != sourceId }.map { it.id to it.name },
                !busy,
            ) { destinationId = it }
            Spacer(Modifier.height(EduCoreSpacing.Sm))
        }
        EduCoreTextField(minimumAverage, { minimumAverage = it }, "Minimum average %", Modifier.fillMaxWidth(), enabled = !busy)
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        EduCoreTextField(maxFailed, { maxFailed = it }, "Maximum failed subjects", Modifier.fillMaxWidth(), enabled = !busy)
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        ToggleSetting("Require complete published result", complete, !busy) { complete = it }
        ToggleSetting("Terminal level — successful learners graduate", terminal, !busy) {
            terminal = it
            if (it) destinationId = null
        }
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
            "Save Promotion Rule",
            {
                sourceId?.let {
                    onSave(
                        it,
                        destinationId,
                        minimumAverage.toDoubleOrNull(),
                        maxFailed.toIntOrNull(),
                        complete,
                        failureAction,
                        armStrategy,
                        terminal,
                    )
                }
            },
            Modifier.fillMaxWidth(),
            enabled = !busy && sourceId != null,
            loading = busy,
        )
    }
}

@Composable
private fun PromotionRunner(
    state: ParallelLifecycleUiState,
    onSelectPromotionSessions: (Long?, Long?) -> Unit,
    onPreviewPromotion: () -> Unit,
    onExecutePromotion: () -> Unit,
) {
    val workspace = state.workspace ?: return
    var sourceId by remember(state.sourceSessionId, workspace.sessions) { mutableStateOf(state.sourceSessionId) }
    var targetId by remember(state.targetSessionId, workspace.sessions) { mutableStateOf(state.targetSessionId) }

    LaunchedEffect(sourceId, targetId) { onSelectPromotionSessions(sourceId, targetId) }

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text("Promotion engine", style = MaterialTheme.typography.titleLarge)
        Text(
            "Preview is mandatory. Existing target-session placements, published target results and full arms are protected.",
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
        EduCoreSecondaryButton(
            "Preview Promotion",
            onPreviewPromotion,
            Modifier.fillMaxWidth(),
            enabled = !state.isMutating && sourceId != null && targetId != null && sourceId != targetId,
        )
        state.promotionPreview?.let { preview ->
            Spacer(Modifier.height(EduCoreSpacing.Lg))
            KeyValueRow("Students", preview.counts.total.toString())
            KeyValueRow("Promote", preview.counts.promoted.toString())
            KeyValueRow("Repeat / retain", (preview.counts.repeat + preview.counts.retain).toString())
            KeyValueRow("Graduate", preview.counts.graduated.toString())
            KeyValueRow("Blocked", preview.counts.blocked.toString())
            Spacer(Modifier.height(EduCoreSpacing.Md))
            EduCorePrimaryButton(
                "Execute Promotion",
                onExecutePromotion,
                Modifier.fillMaxWidth(),
                enabled = !state.isMutating && preview.counts.blocked == 0 && preview.counts.total > 0,
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
