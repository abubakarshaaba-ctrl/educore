package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Book
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.PauseCircle
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
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
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreMetricCard
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSegmentedControl
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTextField
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.SubjectAdminDto

@Composable
internal fun NativeSubjectsScreen(
    state: SubjectsUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onStatus: (String) -> Unit,
    onCreate: () -> Unit,
    onEdit: (SubjectAdminDto) -> Unit,
    onCloseEditor: () -> Unit,
    onName: (String) -> Unit,
    onCode: (String) -> Unit,
    onActive: (Boolean) -> Unit,
    onSave: () -> Unit,
    onRequestDelete: (SubjectAdminDto) -> Unit,
    onCancelDelete: () -> Unit,
    onConfirmDelete: () -> Unit,
    onLoadMore: () -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.workspace == null) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading subjects")
    }
    state.workspace ?: return EduCoreErrorState(
        message = state.errorMessage ?: "Subjects are unavailable.",
        modifier = Modifier.fillMaxSize(),
        onRetry = onRetry,
    )

    if (state.editorOpen) {
        SubjectEditorScreen(
            state = state,
            onBack = onCloseEditor,
            onName = onName,
            onCode = onCode,
            onActive = onActive,
            onSave = onSave,
        )
        return
    }

    SubjectRegisterScreen(
        state = state,
        onBack = onBack,
        onQuery = onQuery,
        onSearch = onSearch,
        onStatus = onStatus,
        onCreate = onCreate,
        onEdit = onEdit,
        onRequestDelete = onRequestDelete,
        onCancelDelete = onCancelDelete,
        onConfirmDelete = onConfirmDelete,
        onLoadMore = onLoadMore,
    )
}

@Composable
private fun SubjectRegisterScreen(
    state: SubjectsUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onStatus: (String) -> Unit,
    onCreate: () -> Unit,
    onEdit: (SubjectAdminDto) -> Unit,
    onRequestDelete: (SubjectAdminDto) -> Unit,
    onCancelDelete: () -> Unit,
    onConfirmDelete: () -> Unit,
    onLoadMore: () -> Unit,
) {
    val workspace = state.workspace ?: return
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Subjects",
                subtitle = "Manage the school subject catalogue and usage state",
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let { item { Text(it, color = EduCoreColors.Success700) } }

        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreMetricCard(
                    label = "Subjects",
                    value = workspace.metrics.total.toString(),
                    modifier = Modifier.weight(1f),
                    icon = Icons.Default.Book,
                    tone = EduCoreTone.Brand,
                )
                EduCoreMetricCard(
                    label = "Active",
                    value = workspace.metrics.active.toString(),
                    modifier = Modifier.weight(1f),
                    icon = Icons.Default.CheckCircle,
                    tone = EduCoreTone.Success,
                )
                EduCoreMetricCard(
                    label = "Inactive",
                    value = workspace.metrics.inactive.toString(),
                    modifier = Modifier.weight(1f),
                    icon = Icons.Default.PauseCircle,
                    tone = EduCoreTone.Neutral,
                )
            }
        }

        item {
            EduCoreSearchBar(
                value = state.query,
                onValueChange = onQuery,
                placeholder = "Search subject name or code",
            )
        }
        item {
            EduCoreSecondaryButton(
                text = "Search",
                onClick = onSearch,
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isLoading,
            )
        }
        if (workspace.statusOptions.isNotEmpty()) {
            item {
                EduCoreSegmentedControl(
                    options = workspace.statusOptions.map { it.label },
                    selectedIndex = workspace.statusOptions.indexOfFirst { it.key == state.status }.coerceAtLeast(0),
                    onSelected = { index -> workspace.statusOptions.getOrNull(index)?.let { onStatus(it.key) } },
                )
            }
        }
        if (state.canManage) {
            item {
                EduCorePrimaryButton(
                    text = "Add subject",
                    onClick = onCreate,
                    modifier = Modifier.fillMaxWidth(),
                    leadingIcon = Icons.Default.Add,
                )
            }
        }

        if (state.subjects.isEmpty()) {
            item {
                EduCoreEmptyState(
                    title = if (state.query.isBlank()) "No subjects configured" else "No matching subjects",
                    message = if (state.query.isBlank()) "Add the first subject to build the academic catalogue." else "Try another name or code.",
                )
            }
        } else {
            items(state.subjects, key = { "subject-${it.id}" }) { subject ->
                SubjectAdminCard(
                    subject = subject,
                    canManage = state.canManage,
                    onEdit = { onEdit(subject) },
                    onRequestDelete = { onRequestDelete(subject) },
                )
            }
            if (state.hasMore) {
                item {
                    EduCoreSecondaryButton(
                        text = if (state.isLoadingMore) "Loading…" else "Load more",
                        onClick = onLoadMore,
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !state.isLoadingMore,
                    )
                }
            }
        }

        state.deleteCandidate?.let { subject ->
            item {
                DeleteSubjectConfirmation(
                    subject = subject,
                    busy = state.isSaving,
                    onCancel = onCancelDelete,
                    onConfirm = onConfirmDelete,
                )
            }
        }
    }
}

@Composable
private fun SubjectAdminCard(
    subject: SubjectAdminDto,
    canManage: Boolean,
    onEdit: () -> Unit,
    onRequestDelete: () -> Unit,
) {
    val references = subject.references
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
                Column(Modifier.weight(1f)) {
                    Text(subject.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    Text(
                        subject.code ?: "No subject code",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                EduCoreStatusBadge(
                    text = if (subject.active) "Active" else "Inactive",
                    tone = if (subject.active) EduCoreTone.Success else EduCoreTone.Neutral,
                )
            }
            Text(
                "Class assignments ${references.classAssignments} · Curriculum rules ${references.curriculumRules} · Scores ${references.scores} · Student selections ${references.studentSelections}",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            if (canManage) {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreSecondaryButton(
                        text = "Edit",
                        onClick = onEdit,
                        modifier = Modifier.weight(1f),
                        leadingIcon = Icons.Default.Edit,
                    )
                    EduCoreSecondaryButton(
                        text = if (references.total == 0) "Delete" else "In use",
                        onClick = onRequestDelete,
                        modifier = Modifier.weight(1f),
                        enabled = references.total == 0,
                    )
                }
            }
        }
    }
}

@Composable
private fun SubjectEditorScreen(
    state: SubjectsUiState,
    onBack: () -> Unit,
    onName: (String) -> Unit,
    onCode: (String) -> Unit,
    onActive: (Boolean) -> Unit,
    onSave: () -> Unit,
) {
    val draft = state.draft
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = if (state.editing) "Edit Subject" else "Add Subject",
                subtitle = "Subject identity and catalogue availability",
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item {
            EduCoreTextField(
                value = draft.name,
                onValueChange = onName,
                label = "Subject name",
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isSaving,
            )
        }
        item {
            EduCoreTextField(
                value = draft.code,
                onValueChange = onCode,
                label = "Subject code",
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isSaving,
                supportingText = "Optional, maximum 10 characters; saved in uppercase.",
            )
        }
        item {
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
                        Text("Active subject", style = MaterialTheme.typography.titleSmall)
                        Text(
                            "Inactive subjects remain in historical records but are unavailable for current setup.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                    Switch(
                        checked = draft.active,
                        onCheckedChange = onActive,
                        enabled = !state.isSaving,
                    )
                }
            }
        }
        item {
            EduCorePrimaryButton(
                text = if (state.editing) "Save changes" else "Create subject",
                onClick = onSave,
                modifier = Modifier.fillMaxWidth(),
                enabled = state.canManage && draft.valid && !state.isSaving,
                loading = state.isSaving,
            )
        }
        item {
            EduCoreSecondaryButton(
                text = "Cancel",
                onClick = onBack,
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isSaving,
            )
        }
    }
}

@Composable
private fun DeleteSubjectConfirmation(
    subject: SubjectAdminDto,
    busy: Boolean,
    onCancel: () -> Unit,
    onConfirm: () -> Unit,
) {
    Card(
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.Warning100),
        border = BorderStroke(1.dp, EduCoreColors.Warning700),
    ) {
        Column(
            Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            Text("Delete ${subject.name}?", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
            Text(
                "Only unused subjects can be deleted. Historical or configured references are protected by the server.",
                style = MaterialTheme.typography.bodySmall,
            )
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreSecondaryButton("Cancel", onCancel, Modifier.weight(1f), enabled = !busy)
                EduCorePrimaryButton("Delete", onConfirm, Modifier.weight(1f), enabled = !busy, loading = busy)
            }
        }
    }
}
