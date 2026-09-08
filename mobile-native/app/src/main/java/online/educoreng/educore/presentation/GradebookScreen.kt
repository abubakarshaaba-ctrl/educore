package online.educoreng.educore.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.People
import androidx.compose.material.icons.filled.School
import androidx.compose.material.icons.filled.TrendingUp
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreInfoBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseStat
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.GradebookClassDto
import online.educoreng.educore.core.network.dto.GradebookStudentRowDto
import online.educoreng.educore.core.network.dto.GradebookTermDto

@Composable
internal fun GradebookScreen(
    state: GradebookUiState,
    onBack: () -> Unit,
    onClass: (Long?) -> Unit,
    onTerm: (Long?) -> Unit,
    onEditFormRemark: (GradebookStudentRowDto) -> Unit,
    onEditPrincipalRemark: (GradebookStudentRowDto) -> Unit,
    onRemarkDraft: (String) -> Unit,
    onSaveRemark: () -> Unit,
    onDismissEditor: () -> Unit,
    onRetry: () -> Unit,
) {
    state.editor?.let { editor ->
        AlertDialog(
            onDismissRequest = onDismissEditor,
            title = { Text(editor.field.label) },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                    Text(editor.studentName, style = MaterialTheme.typography.titleSmall)
                    OutlinedTextField(
                        value = editor.draft,
                        onValueChange = onRemarkDraft,
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text(editor.field.label) },
                        minLines = 4,
                        maxLines = 8,
                        supportingText = { Text("${editor.draft.length}/2000") },
                    )
                }
            },
            confirmButton = {
                TextButton(onClick = onSaveRemark, enabled = state.canSaveRemark) {
                    Text(if (state.isSaving) "Saving…" else "Save remark")
                }
            },
            dismissButton = {
                TextButton(onClick = onDismissEditor, enabled = !state.isSaving) { Text("Cancel") }
            },
        )
    }

    Column(Modifier.fillMaxSize()) {
        EduCorePageHeader(
            title = "Gradebook & Remarks",
            subtitle = "Term scores and controlled report remarks",
            onBack = onBack,
        )

        if (state.isLoading && state.workspace == null) {
            EduCoreLoadingState(message = "Loading gradebook")
            return@Column
        }

        val workspace = state.workspace
        val rows = workspace?.students.orEmpty()
        val summaries = rows.mapNotNull { it.summary }
        val classAverage = summaries.takeIf { it.isNotEmpty() }?.map { it.average }?.average()

        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = androidx.compose.foundation.layout.PaddingValues(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            item {
                EduCoreShowcaseHero(
                    eyebrow = "ACADEMIC REVIEW",
                    title = workspace?.classRoom?.name ?: "Gradebook & Remarks",
                    subtitle = workspace?.term?.let { term ->
                        listOfNotNull(term.name, term.session).joinToString(" · ")
                    } ?: "Choose a permitted class and term. Remark editing never computes or publishes a report card.",
                )
            }

            workspace?.let { data ->
                item {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    ) {
                        EduCoreShowcaseStat(
                            label = "Students",
                            value = rows.size.toString(),
                            icon = Icons.Default.People,
                            modifier = Modifier.weight(1f),
                        )
                        EduCoreShowcaseStat(
                            label = "Summaries",
                            value = summaries.size.toString(),
                            icon = Icons.Default.School,
                            modifier = Modifier.weight(1f),
                        )
                        EduCoreShowcaseStat(
                            label = "Class avg",
                            value = classAverage?.let { String.format("%.1f", it) } ?: "—",
                            icon = Icons.Default.TrendingUp,
                            modifier = Modifier.weight(1f),
                        )
                    }
                }

                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                    ) {
                        Column(
                            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
                            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                        ) {
                            EduCoreSectionHeader(
                                title = "Selection",
                                supportingText = "Only classes your account is permitted to review are listed.",
                            )
                            GradebookDropdown(
                                label = "Class",
                                value = state.selectedClassName,
                                options = data.options.classArms,
                                optionLabel = { it.name },
                                optionId = { it.id },
                                onSelected = onClass,
                            )
                            GradebookDropdown(
                                label = "Term",
                                value = state.selectedTermName,
                                options = data.options.terms,
                                optionLabel = { listOfNotNull(it.name, it.session).joinToString(" · ") },
                                optionId = { it.id },
                                onSelected = onTerm,
                            )
                        }
                    }
                }

                state.message?.let { message -> item { EduCoreInfoBanner(message, title = "Gradebook updated") } }
                state.errorMessage?.let { error -> item { EduCoreErrorBanner(error) } }

                if (!data.capabilities.computeReports && !data.capabilities.publishReports) {
                    item {
                        EduCoreInfoBanner(
                            message = "This mobile workspace can review scores and save authorised remarks only. Report computation and publication remain controlled server workflows.",
                            title = "Protected report lifecycle",
                        )
                    }
                }

                when {
                    data.options.classArms.isEmpty() -> item {
                        EduCoreEmptyState(
                            title = "No assigned gradebook class",
                            message = "No form class or leadership-level class access is assigned to this account.",
                        )
                    }
                    state.selectedClassId == null || state.selectedTermId == null -> item {
                        EduCoreEmptyState(
                            title = "Choose class and term",
                            message = "Select a class and term to load its gradebook.",
                        )
                    }
                    rows.isEmpty() && !state.isLoading -> item {
                        EduCoreEmptyState(
                            title = "No gradebook students",
                            message = "No current or summarized students were found for this class and term.",
                        )
                    }
                    else -> {
                        item {
                            EduCoreSectionHeader(
                                title = "Student gradebook",
                                supportingText = "Scores are read-only here. Remarks use the existing report summary record.",
                            )
                        }
                        items(rows, key = { it.student.id }) { row ->
                            GradebookStudentCard(
                                row = row,
                                canEditFormRemark = data.capabilities.editFormTutorRemark,
                                canEditPrincipalRemark = data.capabilities.editPrincipalRemark,
                                busy = state.isSaving,
                                onEditFormRemark = { onEditFormRemark(row) },
                                onEditPrincipalRemark = { onEditPrincipalRemark(row) },
                            )
                        }
                    }
                }
            }

            if (workspace == null && state.errorMessage != null) {
                item {
                    EduCoreEmptyState(
                        title = "Unable to load gradebook",
                        message = state.errorMessage,
                        actionLabel = "Retry",
                        onAction = onRetry,
                    )
                }
            }
        }
    }
}

@Composable
private fun GradebookStudentCard(
    row: GradebookStudentRowDto,
    canEditFormRemark: Boolean,
    canEditPrincipalRemark: Boolean,
    busy: Boolean,
    onEditFormRemark: () -> Unit,
    onEditPrincipalRemark: () -> Unit,
) {
    val summary = row.summary
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            Text(row.student.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            row.student.admissionNumber?.let {
                Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }

            if (summary == null) {
                EduCoreInfoBanner(
                    message = "This student's term summary has not been computed. Scores are read-only and remarks cannot be entered yet.",
                    title = "Summary unavailable",
                )
            } else {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                ) {
                    GradebookMetric("Average", String.format("%.1f", summary.average), Modifier.weight(1f))
                    GradebookMetric(
                        "Position",
                        summary.position?.let { pos -> summary.classSize?.let { "$pos/$it" } ?: pos.toString() } ?: "—",
                        Modifier.weight(1f),
                    )
                    GradebookMetric("Failed", summary.subjectsFailed.toString(), Modifier.weight(1f))
                }

                if (row.subjects.isNotEmpty()) {
                    Text("Subject totals", style = MaterialTheme.typography.labelLarge, color = EduCoreColors.Navy900)
                    row.subjects.take(6).forEach { subject ->
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            Text(subject.code ?: subject.subject, style = MaterialTheme.typography.bodySmall)
                            Text(String.format("%.1f", subject.total), style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.SemiBold)
                        }
                    }
                    if (row.subjects.size > 6) {
                        Text("+${row.subjects.size - 6} more subjects", style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Slate600)
                    }
                }

                RemarkBlock(
                    title = "Form tutor remark",
                    remark = summary.formTutorRemark,
                    editable = canEditFormRemark,
                    busy = busy,
                    onEdit = onEditFormRemark,
                )
                RemarkBlock(
                    title = "Principal remark",
                    remark = summary.principalRemark,
                    editable = canEditPrincipalRemark,
                    busy = busy,
                    onEdit = onEditPrincipalRemark,
                )
            }
        }
    }
}

@Composable
private fun GradebookMetric(label: String, value: String, modifier: Modifier = Modifier) {
    Card(modifier = modifier, colors = CardDefaults.cardColors(containerColor = EduCoreColors.Gold50)) {
        Column(Modifier.padding(EduCoreSpacing.Sm)) {
            Text(value, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = EduCoreColors.Navy900)
            Text(label, style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Slate600)
        }
    }
}

@Composable
private fun RemarkBlock(
    title: String,
    remark: String?,
    editable: Boolean,
    busy: Boolean,
    onEdit: () -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.Page50),
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            Text(title, style = MaterialTheme.typography.labelLarge, color = EduCoreColors.Navy900)
            Text(
                remark?.takeIf(String::isNotBlank) ?: "No remark entered.",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
            if (editable) {
                EduCoreSecondaryButton(
                    text = if (remark.isNullOrBlank()) "Add remark" else "Edit remark",
                    onClick = onEdit,
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !busy,
                    leadingIcon = { androidx.compose.material3.Icon(Icons.Default.Edit, contentDescription = null) },
                )
            }
        }
    }
}

@Composable
private fun <T> GradebookDropdown(
    label: String,
    value: String,
    options: List<T>,
    optionLabel: (T) -> String,
    optionId: (T) -> Long,
    onSelected: (Long?) -> Unit,
) {
    var expanded by remember { mutableStateOf(false) }
    Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
        Text(label, style = MaterialTheme.typography.labelMedium, color = EduCoreColors.Slate600)
        Box {
            EduCoreSecondaryButton(
                text = value,
                onClick = { expanded = true },
                modifier = Modifier.fillMaxWidth(),
                enabled = options.isNotEmpty(),
            )
            DropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
                options.forEach { option ->
                    DropdownMenuItem(
                        text = { Text(optionLabel(option)) },
                        onClick = {
                            expanded = false
                            onSelected(optionId(option))
                        },
                    )
                }
            }
        }
    }
}
