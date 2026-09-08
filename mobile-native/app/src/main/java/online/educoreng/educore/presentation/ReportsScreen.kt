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
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Groups
import androidx.compose.material.icons.filled.School
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import online.educoreng.educore.core.designsystem.component.EduCoreConfirmationDialog
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreInfoBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseStat
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.ReportSummaryRowDto

@Composable
internal fun ReportsScreen(
    state: ReportsUiState,
    onBack: () -> Unit,
    onClass: (Long?) -> Unit,
    onTerm: (Long?) -> Unit,
    onNote: (String) -> Unit,
    onCompute: () -> Unit,
    onPublish: () -> Unit,
    onUnpublish: () -> Unit,
    onConfirm: () -> Unit,
    onDismissConfirmation: () -> Unit,
    onRetry: () -> Unit,
) {
    val action = state.confirmation
    EduCoreConfirmationDialog(
        visible = action != null,
        title = when (action) {
            ReportManagementAction.COMPUTE -> "Compute report cards?"
            ReportManagementAction.UNPUBLISH -> "Unpublish report cards?"
            else -> "Publish report cards?"
        },
        message = when (action) {
            ReportManagementAction.COMPUTE ->
                "EduCore will recompute term summaries from the current tenant-scoped score records. Existing authorised remarks are preserved."
            ReportManagementAction.UNPUBLISH ->
                "Parent and student result access will be locked again, and score entry can resume for this class and term."
            else ->
                "Publishing locks score changes for this class and term and makes computed results available to authorised parent and student accounts."
        },
        confirmLabel = when (action) {
            ReportManagementAction.COMPUTE -> "Compute"
            ReportManagementAction.UNPUBLISH -> "Unpublish"
            else -> "Publish"
        },
        destructive = action == ReportManagementAction.UNPUBLISH,
        onConfirm = onConfirm,
        onDismiss = onDismissConfirmation,
    )

    Column(Modifier.fillMaxSize()) {
        EduCorePageHeader(
            title = "Report Cards",
            subtitle = "Compute, review and publish term results",
            onBack = onBack,
        )

        if (state.isLoading && state.workspace == null) {
            EduCoreLoadingState(message = "Loading report cards")
            return@Column
        }

        val workspace = state.workspace
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = androidx.compose.foundation.layout.PaddingValues(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            item {
                EduCoreShowcaseHero(
                    eyebrow = "REPORT LIFECYCLE",
                    title = workspace?.classRoom?.name ?: "Report Cards",
                    subtitle = workspace?.term?.let {
                        listOfNotNull(it.name, it.session).joinToString(" · ")
                    } ?: "Choose a class and term to compute, review and publish report cards.",
                )
            }

            workspace?.let { data ->
                item {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    ) {
                        EduCoreShowcaseStat(
                            label = "Computed",
                            value = data.summary.computed.toString(),
                            icon = Icons.Default.Groups,
                            modifier = Modifier.weight(1f),
                        )
                        EduCoreShowcaseStat(
                            label = "Missing",
                            value = data.summary.missing?.toString() ?: "—",
                            icon = Icons.Default.Warning,
                            tone = if ((data.summary.missing ?: 0) > 0) EduCoreTone.Warning else EduCoreTone.Success,
                            modifier = Modifier.weight(1f),
                        )
                        EduCoreShowcaseStat(
                            label = "Status",
                            value = if (data.summary.published) "Published" else "Draft",
                            icon = if (data.summary.published) Icons.Default.CheckCircle else Icons.Default.School,
                            tone = if (data.summary.published) EduCoreTone.Success else EduCoreTone.Brand,
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
                                supportingText = "Every action applies only to this tenant class and term.",
                            )
                            ReportDropdown(
                                label = "Class",
                                value = data.options.classArms.firstOrNull { it.id == state.selectedClassId }?.name ?: "Select class",
                                options = data.options.classArms,
                                optionLabel = { it.name },
                                optionId = { it.id },
                                enabled = !state.isMutating,
                                onSelected = onClass,
                            )
                            ReportDropdown(
                                label = "Term",
                                value = data.options.terms.firstOrNull { it.id == state.selectedTermId }?.let {
                                    listOfNotNull(it.name, it.session).joinToString(" · ")
                                } ?: "Select term",
                                options = data.options.terms,
                                optionLabel = { listOfNotNull(it.name, it.session).joinToString(" · ") },
                                optionId = { it.id },
                                enabled = !state.isMutating,
                                onSelected = onTerm,
                            )
                        }
                    }
                }

                state.message?.let { message -> item { EduCoreInfoBanner(message, title = "Report cards updated") } }
                state.errorMessage?.let { error -> item { EduCoreErrorBanner(error) } }

                if (state.hasSelection) {
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
                                    title = "Lifecycle actions",
                                    supportingText = if (data.summary.published) {
                                        data.publication?.publishedByName?.let { "Published by $it. Return to draft before recomputing." }
                                            ?: "Published results are locked. Return to draft before recomputing."
                                    } else {
                                        "Compute from current scores, review the summaries below, then publish."
                                    },
                                )

                                if (state.canCompute) {
                                    EduCoreSecondaryButton(
                                        text = if (data.summary.computed > 0) "Recompute report cards" else "Compute report cards",
                                        onClick = onCompute,
                                        modifier = Modifier.fillMaxWidth(),
                                        enabled = !state.isMutating,
                                    )
                                }

                                Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                                    FilterChip(
                                        selected = data.summary.published,
                                        onClick = {},
                                        enabled = false,
                                        label = { Text(if (data.summary.published) "Published" else "Draft") },
                                    )
                                }
                                OutlinedTextField(
                                    value = state.publicationNote,
                                    onValueChange = onNote,
                                    modifier = Modifier.fillMaxWidth(),
                                    enabled = !state.isMutating && !data.summary.published,
                                    label = { Text("Publication note (optional)") },
                                    minLines = 2,
                                    maxLines = 4,
                                    supportingText = { Text("${state.publicationNote.length}/2000") },
                                )
                                when {
                                    state.canPublish -> EduCorePrimaryButton(
                                        text = "Publish report cards",
                                        onClick = onPublish,
                                        modifier = Modifier.fillMaxWidth(),
                                        loading = state.isMutating,
                                    )
                                    state.canUnpublish -> EduCoreSecondaryButton(
                                        text = "Return to draft",
                                        onClick = onUnpublish,
                                        modifier = Modifier.fillMaxWidth(),
                                        enabled = !state.isMutating,
                                    )
                                    data.summary.computed == 0 && !data.summary.published -> EduCoreInfoBanner(
                                        message = "Compute the report cards before publication.",
                                        title = "Nothing to publish",
                                    )
                                }
                            }
                        }
                    }
                }

                when {
                    data.options.classArms.isEmpty() -> item {
                        EduCoreEmptyState("No classes available", "No tenant class arms are available for report management.")
                    }
                    !state.hasSelection -> item {
                        EduCoreEmptyState("Choose class and term", "Select both fields to load report summaries.")
                    }
                    data.students.isEmpty() && !state.isLoading -> item {
                        EduCoreEmptyState(
                            "No computed report cards",
                            "Use Compute report cards to build summaries from the current score records.",
                        )
                    }
                    else -> {
                        item {
                            EduCoreSectionHeader(
                                title = "Computed summaries",
                                supportingText = "Review these summaries before publication.",
                            )
                        }
                        items(data.students, key = { it.summaryId }) { row -> ReportSummaryCard(row) }
                    }
                }
            }

            if (workspace == null && state.errorMessage != null) {
                item {
                    EduCoreEmptyState(
                        title = "Unable to load report cards",
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
private fun ReportSummaryCard(row: ReportSummaryRowDto) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column(Modifier.weight(1f)) {
                    Text(row.student.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                    row.student.admissionNumber?.let {
                        Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                    }
                }
                EduCoreStatusBadge(
                    row.promotionStatus?.replace('_', ' ')?.replaceFirstChar(Char::uppercase) ?: "Pending",
                    if (row.subjectsFailed > 0) EduCoreTone.Warning else EduCoreTone.Success,
                )
            }
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                ReportMetric("Average", String.format("%.1f", row.average), Modifier.weight(1f))
                ReportMetric(
                    "Position",
                    row.position?.let { pos -> row.classSize?.let { "$pos/$it" } ?: pos.toString() } ?: "—",
                    Modifier.weight(1f),
                )
                ReportMetric("Failed", row.subjectsFailed.toString(), Modifier.weight(1f))
            }
            row.formTutorRemark?.takeIf(String::isNotBlank)?.let {
                Text("Form tutor: $it", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
            row.principalRemark?.takeIf(String::isNotBlank)?.let {
                Text("Principal: $it", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
        }
    }
}

@Composable
private fun ReportMetric(label: String, value: String, modifier: Modifier = Modifier) {
    Column(modifier) {
        Text(label, style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Slate600)
        Text(value, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
    }
}

@Composable
private fun <T> ReportDropdown(
    label: String,
    value: String,
    options: List<T>,
    optionLabel: (T) -> String,
    optionId: (T) -> Long,
    enabled: Boolean,
    onSelected: (Long?) -> Unit,
) {
    var expanded by remember { mutableStateOf(false) }
    Box(Modifier.fillMaxWidth()) {
        EduCoreSecondaryButton(
            text = "$label: $value",
            onClick = { expanded = true },
            modifier = Modifier.fillMaxWidth(),
            enabled = enabled && options.isNotEmpty(),
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
