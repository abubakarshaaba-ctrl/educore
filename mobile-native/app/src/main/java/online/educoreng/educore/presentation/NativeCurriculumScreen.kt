package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AccountTree
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Rule
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Switch
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
import online.educoreng.educore.core.designsystem.component.EduCoreMetricCard
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSegmentedControl
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTabs
import online.educoreng.educore.core.designsystem.component.EduCoreTextField
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.CurriculumEntityOptionDto
import online.educoreng.educore.core.network.dto.CurriculumRuleDto
import online.educoreng.educore.core.network.dto.CurriculumTrackDto

@Composable
internal fun NativeCurriculumScreen(
    state: CurriculumUiState,
    onBack: () -> Unit,
    onTab: (CurriculumTab) -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onLevelFilter: (Long?) -> Unit,
    onTrackFilter: (Long?) -> Unit,
    onStatusFilter: (String) -> Unit,
    onCreateTrack: () -> Unit,
    onEditTrack: (CurriculumTrackDto) -> Unit,
    onTrackName: (String) -> Unit,
    onTrackSection: (String) -> Unit,
    onTrackActive: (Boolean) -> Unit,
    onSaveTrack: () -> Unit,
    onRequestDeleteTrack: (CurriculumTrackDto) -> Unit,
    onCancelDeleteTrack: () -> Unit,
    onConfirmDeleteTrack: () -> Unit,
    onCreateRule: () -> Unit,
    onEditRule: (CurriculumRuleDto) -> Unit,
    onRuleLevel: (Long?) -> Unit,
    onRuleTrack: (Long?) -> Unit,
    onRuleSubject: (Long?) -> Unit,
    onRuleStatus: (String) -> Unit,
    onRuleGroup: (String) -> Unit,
    onRuleMin: (String) -> Unit,
    onRuleMax: (String) -> Unit,
    onRuleActive: (Boolean) -> Unit,
    onSaveRule: () -> Unit,
    onRequestDeleteRule: (CurriculumRuleDto) -> Unit,
    onCancelDeleteRule: () -> Unit,
    onConfirmDeleteRule: () -> Unit,
    onCloseEditor: () -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.workspace == null) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading curriculum")
    }
    state.workspace ?: return EduCoreErrorState(
        message = state.errorMessage ?: "Curriculum is unavailable.",
        modifier = Modifier.fillMaxSize(),
        onRetry = onRetry,
    )

    when (state.editor) {
        CurriculumEditor.TRACK -> CurriculumTrackEditor(
            state = state,
            onBack = onCloseEditor,
            onName = onTrackName,
            onSection = onTrackSection,
            onActive = onTrackActive,
            onSave = onSaveTrack,
        )
        CurriculumEditor.RULE -> CurriculumRuleEditor(
            state = state,
            onBack = onCloseEditor,
            onLevel = onRuleLevel,
            onTrack = onRuleTrack,
            onSubject = onRuleSubject,
            onStatus = onRuleStatus,
            onGroup = onRuleGroup,
            onMin = onRuleMin,
            onMax = onRuleMax,
            onActive = onRuleActive,
            onSave = onSaveRule,
        )
        CurriculumEditor.NONE -> CurriculumRegister(
            state = state,
            onBack = onBack,
            onTab = onTab,
            onQuery = onQuery,
            onSearch = onSearch,
            onLevelFilter = onLevelFilter,
            onTrackFilter = onTrackFilter,
            onStatusFilter = onStatusFilter,
            onCreateTrack = onCreateTrack,
            onEditTrack = onEditTrack,
            onRequestDeleteTrack = onRequestDeleteTrack,
            onCancelDeleteTrack = onCancelDeleteTrack,
            onConfirmDeleteTrack = onConfirmDeleteTrack,
            onCreateRule = onCreateRule,
            onEditRule = onEditRule,
            onRequestDeleteRule = onRequestDeleteRule,
            onCancelDeleteRule = onCancelDeleteRule,
            onConfirmDeleteRule = onConfirmDeleteRule,
        )
    }
}

@Composable
private fun CurriculumRegister(
    state: CurriculumUiState,
    onBack: () -> Unit,
    onTab: (CurriculumTab) -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onLevelFilter: (Long?) -> Unit,
    onTrackFilter: (Long?) -> Unit,
    onStatusFilter: (String) -> Unit,
    onCreateTrack: () -> Unit,
    onEditTrack: (CurriculumTrackDto) -> Unit,
    onRequestDeleteTrack: (CurriculumTrackDto) -> Unit,
    onCancelDeleteTrack: () -> Unit,
    onConfirmDeleteTrack: () -> Unit,
    onCreateRule: () -> Unit,
    onEditRule: (CurriculumRuleDto) -> Unit,
    onRequestDeleteRule: (CurriculumRuleDto) -> Unit,
    onCancelDeleteRule: () -> Unit,
    onConfirmDeleteRule: () -> Unit,
) {
    val workspace = state.workspace ?: return
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Curriculum",
                subtitle = "Academic tracks and class-level subject rules",
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let { item { Text(it, color = EduCoreColors.Success700) } }

        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreMetricCard("Tracks", workspace.metrics.tracks.toString(), Modifier.weight(1f), icon = Icons.Default.AccountTree, tone = EduCoreTone.Brand)
                EduCoreMetricCard("Rules", workspace.metrics.rules.toString(), Modifier.weight(1f), icon = Icons.Default.Rule, tone = EduCoreTone.Info)
                EduCoreMetricCard("Active", workspace.metrics.activeRules.toString(), Modifier.weight(1f), icon = Icons.Default.CheckCircle, tone = EduCoreTone.Success)
            }
        }
        item {
            EduCoreTabs(
                labels = listOf("Academic tracks", "Subject rules"),
                selectedIndex = if (state.tab == CurriculumTab.TRACKS) 0 else 1,
                onSelected = { onTab(if (it == 0) CurriculumTab.TRACKS else CurriculumTab.RULES) },
            )
        }
        item {
            EduCoreSearchBar(
                value = state.query,
                onValueChange = onQuery,
                placeholder = if (state.tab == CurriculumTab.TRACKS) "Search track" else "Search subject, class, track or group",
            )
        }
        item {
            EduCoreSecondaryButton("Search", onSearch, Modifier.fillMaxWidth(), enabled = !state.isLoading)
        }

        if (state.tab == CurriculumTab.RULES) {
            item {
                SelectionStrip(
                    label = "Class level",
                    options = workspace.classLevels,
                    selected = state.levelId,
                    allLabel = "All levels",
                    onSelected = onLevelFilter,
                )
            }
            item {
                TrackSelectionStrip(
                    label = "Academic track",
                    tracks = workspace.tracks,
                    selected = state.trackId,
                    allLabel = "All tracks",
                    onSelected = onTrackFilter,
                )
            }
            if (workspace.statusOptions.isNotEmpty()) {
                item {
                    EduCoreSegmentedControl(
                        options = workspace.statusOptions.map { it.label },
                        selectedIndex = workspace.statusOptions.indexOfFirst { it.key == state.status }.coerceAtLeast(0),
                        onSelected = { index -> workspace.statusOptions.getOrNull(index)?.let { onStatusFilter(it.key) } },
                    )
                }
            }
        }

        if (state.canManage) {
            item {
                EduCorePrimaryButton(
                    text = if (state.tab == CurriculumTab.TRACKS) "Add academic track" else "Add subject rule",
                    onClick = if (state.tab == CurriculumTab.TRACKS) onCreateTrack else onCreateRule,
                    modifier = Modifier.fillMaxWidth(),
                )
            }
        }

        if (state.tab == CurriculumTab.TRACKS) {
            if (workspace.tracks.isEmpty()) {
                item { EduCoreEmptyState("No academic tracks", "System defaults or school-created tracks will appear here.") }
            } else {
                items(workspace.tracks, key = { "curriculum-track-${it.id}" }) { track ->
                    CurriculumTrackCardNative(track, state.canManage, { onEditTrack(track) }, { onRequestDeleteTrack(track) })
                }
            }
            state.deleteTrackCandidate?.let { track ->
                item {
                    CurriculumDeleteConfirmation(
                        title = "Delete ${track.name}?",
                        message = "Only an unused school-created track can be deleted. System tracks and referenced tracks are protected.",
                        busy = state.isSaving,
                        onCancel = onCancelDeleteTrack,
                        onConfirm = onConfirmDeleteTrack,
                    )
                }
            }
        } else {
            if (workspace.rules.isEmpty()) {
                item { EduCoreEmptyState("No subject rules", "Add class-level subject rules to define the curriculum.") }
            } else {
                items(workspace.rules, key = { "curriculum-rule-${it.id}" }) { rule ->
                    CurriculumRuleCardNative(rule, state.canManage, { onEditRule(rule) }, { onRequestDeleteRule(rule) })
                }
            }
            state.deleteRuleCandidate?.let { rule ->
                item {
                    CurriculumDeleteConfirmation(
                        title = "Remove ${rule.subject ?: "subject rule"}?",
                        message = "This removes the curriculum rule for ${rule.classLevel ?: "the class level"}. Student history is not edited by this action.",
                        busy = state.isSaving,
                        onCancel = onCancelDeleteRule,
                        onConfirm = onConfirmDeleteRule,
                    )
                }
            }
        }
    }
}

@Composable
private fun CurriculumTrackCardNative(
    track: CurriculumTrackDto,
    canManage: Boolean,
    onEdit: () -> Unit,
    onDelete: () -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
                Column(Modifier.weight(1f)) {
                    Text(track.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    Text(
                        "${track.section.replaceFirstChar(Char::uppercase)} · ${if (track.system) "System default" else "School track"}",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                EduCoreStatusBadge(if (track.active) "Active" else "Inactive", if (track.active) EduCoreTone.Success else EduCoreTone.Neutral)
            }
            Text(
                "Classes ${track.references.classArms} · Rules ${track.references.rules} · Student selections ${track.references.studentSelections}",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            if (canManage && track.manageable) {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreSecondaryButton("Edit", onEdit, Modifier.weight(1f))
                    EduCoreSecondaryButton(
                        if (track.references.total == 0) "Delete" else "In use",
                        onDelete,
                        Modifier.weight(1f),
                        enabled = track.references.total == 0,
                    )
                }
            }
        }
    }
}

@Composable
private fun CurriculumRuleCardNative(
    rule: CurriculumRuleDto,
    canManage: Boolean,
    onEdit: () -> Unit,
    onDelete: () -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
                Column(Modifier.weight(1f)) {
                    Text(rule.subject ?: "Subject", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    Text(
                        listOfNotNull(rule.subjectCode, rule.classLevel, rule.track).joinToString(" · "),
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                EduCoreStatusBadge(
                    if (rule.active) rule.status.replace('_', ' ').replaceFirstChar(Char::uppercase) else "Inactive",
                    curriculumRuleTone(if (rule.active) rule.status else "inactive"),
                )
            }
            rule.electiveGroup?.takeIf(String::isNotBlank)?.let {
                Text("Elective group: $it", style = MaterialTheme.typography.bodySmall)
            }
            if (rule.minRequired != null || rule.maxAllowed != null) {
                Text(
                    "Selection limits: min ${rule.minRequired ?: "—"} · max ${rule.maxAllowed ?: "—"}",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            if (canManage) {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreSecondaryButton("Edit", onEdit, Modifier.weight(1f))
                    EduCoreSecondaryButton("Remove", onDelete, Modifier.weight(1f))
                }
            }
        }
    }
}

@Composable
private fun CurriculumTrackEditor(
    state: CurriculumUiState,
    onBack: () -> Unit,
    onName: (String) -> Unit,
    onSection: (String) -> Unit,
    onActive: (Boolean) -> Unit,
    onSave: () -> Unit,
) {
    val workspace = state.workspace ?: return
    val draft = state.trackDraft
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader(if (state.editingTrack) "Edit Academic Track" else "Add Academic Track", "School-owned academic pathway", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item { EduCoreTextField(draft.name, onName, "Track name", Modifier.fillMaxWidth(), enabled = !state.isSaving) }
        item {
            EduCoreSegmentedControl(
                options = workspace.sectionOptions.map { it.label },
                selectedIndex = workspace.sectionOptions.indexOfFirst { it.key == draft.section }.coerceAtLeast(0),
                onSelected = { index -> workspace.sectionOptions.getOrNull(index)?.let { onSection(it.key) } },
                enabled = !state.isSaving,
            )
        }
        item { ActiveSwitchCard("Active track", "Inactive tracks remain available for historical references.", draft.active, !state.isSaving, onActive) }
        item { EduCorePrimaryButton(if (state.editingTrack) "Save changes" else "Create track", onSave, Modifier.fillMaxWidth(), enabled = state.canManage && draft.valid, loading = state.isSaving) }
        item { EduCoreSecondaryButton("Cancel", onBack, Modifier.fillMaxWidth(), enabled = !state.isSaving) }
    }
}

@Composable
private fun CurriculumRuleEditor(
    state: CurriculumUiState,
    onBack: () -> Unit,
    onLevel: (Long?) -> Unit,
    onTrack: (Long?) -> Unit,
    onSubject: (Long?) -> Unit,
    onStatus: (String) -> Unit,
    onGroup: (String) -> Unit,
    onMin: (String) -> Unit,
    onMax: (String) -> Unit,
    onActive: (Boolean) -> Unit,
    onSave: () -> Unit,
) {
    val workspace = state.workspace ?: return
    val draft = state.ruleDraft
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader(if (state.editingRule) "Edit Subject Rule" else "Add Subject Rule", "Class-level curriculum offering rule", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item {
            SelectionStrip("Class level", workspace.classLevels, draft.classLevelId, "Choose level", onLevel, enabled = !state.editingRule && !state.isSaving)
        }
        item {
            TrackSelectionStrip("Academic track", workspace.tracks, draft.trackId, "All tracks", onTrack, enabled = !state.editingRule && !state.isSaving)
        }
        item {
            SelectionStrip("Subject", workspace.subjects, draft.subjectId, "Choose subject", onSubject, enabled = !state.editingRule && !state.isSaving)
        }
        item {
            EduCoreSegmentedControl(
                options = workspace.statusOptions.filter { it.key != "all" }.map { it.label },
                selectedIndex = workspace.statusOptions.filter { it.key != "all" }.indexOfFirst { it.key == draft.status }.coerceAtLeast(0),
                onSelected = { index -> workspace.statusOptions.filter { it.key != "all" }.getOrNull(index)?.let { onStatus(it.key) } },
                enabled = !state.isSaving,
            )
        }
        item { EduCoreTextField(draft.electiveGroup, onGroup, "Elective group", Modifier.fillMaxWidth(), enabled = !state.isSaving, supportingText = "Optional; use for grouped elective limits.") }
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreTextField(draft.minRequired, onMin, "Minimum", Modifier.weight(1f), enabled = !state.isSaving)
                EduCoreTextField(draft.maxAllowed, onMax, "Maximum", Modifier.weight(1f), enabled = !state.isSaving)
            }
        }
        if (state.editingRule) {
            item { ActiveSwitchCard("Active rule", "Deactivate a rule without removing its configuration.", draft.active, !state.isSaving, onActive) }
        }
        item { EduCorePrimaryButton(if (state.editingRule) "Save rule" else "Create rule", onSave, Modifier.fillMaxWidth(), enabled = state.canManage && draft.valid, loading = state.isSaving) }
        item { EduCoreSecondaryButton("Cancel", onBack, Modifier.fillMaxWidth(), enabled = !state.isSaving) }
    }
}

@Composable
private fun SelectionStrip(
    label: String,
    options: List<CurriculumEntityOptionDto>,
    selected: Long?,
    allLabel: String,
    onSelected: (Long?) -> Unit,
    enabled: Boolean = true,
) {
    Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
        Text(label, style = MaterialTheme.typography.labelLarge, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Row(
            modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            EduCoreFilterChip(allLabel, selected == null, { onSelected(null) }, enabled = enabled)
            options.forEach { option ->
                EduCoreFilterChip(
                    label = option.code?.let { "${option.name} ($it)" } ?: option.name,
                    selected = selected == option.id,
                    onClick = { onSelected(option.id) },
                    enabled = enabled,
                )
            }
        }
    }
}

@Composable
private fun TrackSelectionStrip(
    label: String,
    tracks: List<CurriculumTrackDto>,
    selected: Long?,
    allLabel: String,
    onSelected: (Long?) -> Unit,
    enabled: Boolean = true,
) {
    Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
        Text(label, style = MaterialTheme.typography.labelLarge, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Row(
            modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            EduCoreFilterChip(allLabel, selected == null, { onSelected(null) }, enabled = enabled)
            tracks.forEach { track ->
                EduCoreFilterChip(track.name, selected == track.id, { onSelected(track.id) }, enabled = enabled)
            }
        }
    }
}

@Composable
private fun ActiveSwitchCard(
    title: String,
    message: String,
    checked: Boolean,
    enabled: Boolean,
    onChecked: (Boolean) -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            Column(Modifier.weight(1f)) {
                Text(title, style = MaterialTheme.typography.titleSmall)
                Text(message, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            Switch(checked = checked, onCheckedChange = onChecked, enabled = enabled)
        }
    }
}

@Composable
private fun CurriculumDeleteConfirmation(
    title: String,
    message: String,
    busy: Boolean,
    onCancel: () -> Unit,
    onConfirm: () -> Unit,
) {
    Card(
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.Warning100),
        border = BorderStroke(1.dp, EduCoreColors.Warning700),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Text(title, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
            Text(message, style = MaterialTheme.typography.bodySmall)
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreSecondaryButton("Cancel", onCancel, Modifier.weight(1f), enabled = !busy)
                EduCorePrimaryButton("Confirm", onConfirm, Modifier.weight(1f), enabled = !busy, loading = busy)
            }
        }
    }
}

private fun curriculumRuleTone(status: String): EduCoreTone = when (status.lowercase()) {
    "compulsory" -> EduCoreTone.Success
    "elective" -> EduCoreTone.Info
    "optional" -> EduCoreTone.Warning
    "not_offered", "inactive" -> EduCoreTone.Neutral
    else -> EduCoreTone.Info
}
