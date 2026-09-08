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
import androidx.compose.material.icons.filled.Download
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
import androidx.compose.runtime.mutableLongStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
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
import online.educoreng.educore.core.network.dto.ReportSubjectBreakdownDto
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
    onDownload: (ReportSummaryRowDto) -> Unit,
    onDocumentOpened: () -> Unit,
    onRetry: () -> Unit,
) {
    OpenDocumentEffect(state.document, onDocumentOpened)

    var selectedSummaryId by rememberSaveable { mutableLongStateOf(0L) }
    val selectedSummary = state.workspace?.students?.firstOrNull { it.summaryId == selectedSummaryId }

    if (selectedSummary != null) {
        ReportDetailContent(
            row = selectedSummary,
            className = state.workspace?.classRoom?.name,
            termLabel = state.workspace?.term?.let { listOfNotNull(it.name, it.session).joinToString(" · ") },
            published = state.workspace?.summary?.published == true,
            isDownloadingPdf = state.isDownloadingPdf,
            message = state.message,
            errorMessage = state.errorMessage,
            onDownload = { onDownload(selectedSummary) },
            onBack = { selectedSummaryId = 0L },
        )
        return
    }

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
                        EduCoreShowcaseStat("Computed", data.summary.computed.toString(), Icons.Default.Groups, Modifier.weight(1f))
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
                    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                        Column(
                            Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
                            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                        ) {
                            EduCoreSectionHeader("Selection", "Every action applies only to this tenant class and term.")
                            ReportDropdown(
                                "Class",
                                data.options.classArms.firstOrNull { it.id == state.selectedClassId }?.name ?: "Select class",
                                data.options.classArms,
                                { it.name }, { it.id }, !state.isMutating, onClass,
                            )
                            ReportDropdown(
                                "Term",
                                data.options.terms.firstOrNull { it.id == state.selectedTermId }?.let {
                                    listOfNotNull(it.name, it.session).joinToString(" · ")
                                } ?: "Select term",
                                data.options.terms,
                                { listOfNotNull(it.name, it.session).joinToString(" · ") }, { it.id }, !state.isMutating, onTerm,
                            )
                        }
                    }
                }

                state.message?.let { message -> item { EduCoreInfoBanner(message, title = "Report cards updated") } }
                state.errorMessage?.let { error -> item { EduCoreErrorBanner(error) } }

                if (state.hasSelection) {
                    item {
                        Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                            Column(
                                Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
                                verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                            ) {
                                EduCoreSectionHeader(
                                    "Lifecycle actions",
                                    if (data.summary.published) {
                                        data.publication?.publishedByName?.let { "Published by $it. Return to draft before recomputing." }
                                            ?: "Published results are locked. Return to draft before recomputing."
                                    } else {
                                        "Compute from current scores, inspect individual reports below, then publish."
                                    },
                                )
                                if (state.canCompute) {
                                    EduCoreSecondaryButton(
                                        if (data.summary.computed > 0) "Recompute report cards" else "Compute report cards",
                                        onCompute,
                                        Modifier.fillMaxWidth(),
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
                                        "Publish report cards", onPublish, Modifier.fillMaxWidth(), loading = state.isMutating,
                                    )
                                    state.canUnpublish -> EduCoreSecondaryButton(
                                        "Return to draft", onUnpublish, Modifier.fillMaxWidth(), enabled = !state.isMutating,
                                    )
                                    data.summary.computed == 0 && !data.summary.published -> EduCoreInfoBanner(
                                        "Compute the report cards before publication.", title = "Nothing to publish",
                                    )
                                }
                            }
                        }
                    }
                }

                when {
                    data.options.classArms.isEmpty() -> item { EduCoreEmptyState("No classes available", "No tenant class arms are available for report management.") }
                    !state.hasSelection -> item { EduCoreEmptyState("Choose class and term", "Select both fields to load report summaries.") }
                    data.students.isEmpty() && !state.isLoading -> item {
                        EduCoreEmptyState("No computed report cards", "Use Compute report cards to build summaries from the current score records.")
                    }
                    else -> {
                        item { EduCoreSectionHeader("Computed summaries", "Open each report to inspect subject-level evidence before publication.") }
                        items(data.students, key = { it.summaryId }) { row ->
                            ReportSummaryCard(row) { selectedSummaryId = row.summaryId }
                        }
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
private fun ReportDetailContent(
    row: ReportSummaryRowDto,
    className: String?,
    termLabel: String?,
    published: Boolean,
    isDownloadingPdf: Boolean,
    message: String?,
    errorMessage: String?,
    onDownload: () -> Unit,
    onBack: () -> Unit,
) {
    Column(Modifier.fillMaxSize()) {
        EduCorePageHeader(
            title = row.student.name,
            subtitle = listOfNotNull(row.student.admissionNumber, className, termLabel).joinToString(" · "),
            onBack = onBack,
        )
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = androidx.compose.foundation.layout.PaddingValues(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            item {
                EduCoreShowcaseHero(
                    eyebrow = if (published) "PUBLISHED REPORT" else "DRAFT REPORT",
                    title = "${String.format("%.1f", row.average)}% average",
                    subtitle = row.position?.let { position ->
                        row.classSize?.let { "Position $position of $it" } ?: "Position $position"
                    } ?: "Class position not available",
                )
            }
            message?.let { item { EduCoreInfoBanner(it, title = "Report card") } }
            errorMessage?.let { item { EduCoreErrorBanner(it) } }
            item {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    ReportMetric("Subjects", row.subjectsOffered.toString(), Modifier.weight(1f))
                    ReportMetric("Failed", row.subjectsFailed.toString(), Modifier.weight(1f))
                    ReportMetric("Status", row.promotionStatus?.replace('_', ' ') ?: "Pending", Modifier.weight(1f))
                }
            }
            item {
                EduCorePrimaryButton(
                    text = "Download PDF",
                    onClick = onDownload,
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !isDownloadingPdf,
                    loading = isDownloadingPdf,
                    leadingIcon = { androidx.compose.material3.Icon(Icons.Default.Download, contentDescription = null) },
                )
            }
            if (row.subjects.isEmpty()) {
                item { EduCoreEmptyState("No subject breakdown", "Recompute this report card to rebuild subject-level details from score records.") }
            } else {
                item { EduCoreSectionHeader("Subject performance", "Computed totals, grading and class comparison") }
                items(row.subjects, key = { it.subjectId ?: it.subject }) { subject -> ReportSubjectCard(subject) }
            }
            item {
                Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                    Column(
                        Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
                        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    ) {
                        Text("Remarks", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                        Text("Form tutor", style = MaterialTheme.typography.labelMedium, color = EduCoreColors.Slate600)
                        Text(row.formTutorRemark?.takeIf(String::isNotBlank) ?: "No form tutor remark yet.")
                        Text("Principal", style = MaterialTheme.typography.labelMedium, color = EduCoreColors.Slate600)
                        Text(row.principalRemark?.takeIf(String::isNotBlank) ?: "No principal remark yet.")
                    }
                }
            }
        }
    }
}

@Composable
private fun ReportSubjectCard(subject: ReportSubjectBreakdownDto) {
    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
        Column(
            Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text(subject.subject, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, modifier = Modifier.weight(1f))
                EduCoreStatusBadge(subject.grade ?: "—", if (subject.isPass) EduCoreTone.Success else EduCoreTone.Warning)
            }
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                ReportMetric("Total", String.format("%.1f", subject.total), Modifier.weight(1f))
                ReportMetric("Position", subject.position?.toString() ?: "—", Modifier.weight(1f))
                ReportMetric("Class avg", subject.classAverage?.let { String.format("%.1f", it) } ?: "—", Modifier.weight(1f))
            }
            Text(
                "Class range: ${subject.classLowest?.let { String.format("%.1f", it) } ?: "—"} – ${subject.classHighest?.let { String.format("%.1f", it) } ?: "—"}",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
            subject.cumulativeAverage?.let {
                Text(
                    "Cumulative average: ${String.format("%.1f", it)}${subject.annualTotal?.let { total -> " · Annual total ${String.format("%.1f", total)}" } ?: ""}",
                    style = MaterialTheme.typography.bodySmall,
                    color = EduCoreColors.Slate600,
                )
            }
            subject.remark?.takeIf(String::isNotBlank)?.let {
                Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
        }
    }
}

@Composable
private fun ReportSummaryCard(row: ReportSummaryRowDto, onOpen: () -> Unit) {
    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
        Column(
            Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column(Modifier.weight(1f)) {
                    Text(row.student.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                    row.student.admissionNumber?.let { Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600) }
                }
                EduCoreStatusBadge(
                    row.promotionStatus?.replace('_', ' ')?.replaceFirstChar(Char::uppercase) ?: "Pending",
                    if (row.subjectsFailed > 0) EduCoreTone.Warning else EduCoreTone.Success,
                )
            }
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                ReportMetric("Average", String.format("%.1f", row.average), Modifier.weight(1f))
                ReportMetric(
                    "Position",
                    row.position?.let { pos -> row.classSize?.let { "$pos/$it" } ?: pos.toString() } ?: "—",
                    Modifier.weight(1f),
                )
                ReportMetric("Failed", row.subjectsFailed.toString(), Modifier.weight(1f))
            }
            EduCoreSecondaryButton("View full report", onOpen, Modifier.fillMaxWidth())
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
