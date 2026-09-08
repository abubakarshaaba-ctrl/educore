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
import androidx.compose.material.icons.filled.Apartment
import androidx.compose.material.icons.filled.CompareArrows
import androidx.compose.material.icons.filled.PendingActions
import androidx.compose.material.icons.filled.School
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
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreConfirmationDialog
import online.educoreng.educore.core.designsystem.component.EduCoreDangerButton
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseStat
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.CrossSchoolTransferDto
import online.educoreng.educore.core.network.dto.InterclassTransferDto
import online.educoreng.educore.core.network.dto.TransferDestinationOptionDto
import online.educoreng.educore.core.network.dto.TransferStudentOptionDto

@Composable
internal fun TransfersScreen(
    state: TransfersUiState,
    onBack: () -> Unit,
    onTab: (TransferWorkspaceTab) -> Unit,
    onStudent: (Long?) -> Unit,
    onDestination: (Long?) -> Unit,
    onReason: (String) -> Unit,
    onRequest: () -> Unit,
    onApprove: (Long, String) -> Unit,
    onReject: (Long, String) -> Unit,
    onConfirm: () -> Unit,
    onCancelConfirm: () -> Unit,
    onRetry: () -> Unit,
) {
    val workspace = state.workspace
    val confirmation = state.confirmation

    EduCoreConfirmationDialog(
        visible = confirmation != null,
        title = if (confirmation?.action == TransferConfirmationAction.REJECT) "Reject transfer?" else "Approve transfer?",
        message = when (confirmation?.action) {
            TransferConfirmationAction.APPROVE ->
                "Approve ${confirmation.studentName}'s transfer? EduCore will archive the source-school student and create a fresh student record for this school."
            TransferConfirmationAction.REJECT ->
                "Reject ${confirmation.studentName}'s incoming transfer request?"
            null -> ""
        },
        confirmLabel = if (confirmation?.action == TransferConfirmationAction.REJECT) "Reject" else "Approve",
        destructive = confirmation?.action == TransferConfirmationAction.REJECT,
        onConfirm = onConfirm,
        onDismiss = onCancelConfirm,
    )

    Column(Modifier.fillMaxSize()) {
        EduCorePageHeader(
            title = "Student Transfers",
            subtitle = "Cross-school and interclass movement",
            onBack = onBack,
        )

        if (state.isLoading && workspace == null) {
            EduCoreLoadingState(message = "Loading transfers")
            return@Column
        }

        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = androidx.compose.foundation.layout.PaddingValues(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            item {
                EduCoreShowcaseHero(
                    eyebrow = "STUDENT LIFECYCLE",
                    title = "Move students without moving history",
                    subtitle = "Cross-school approval creates a fresh receiving-school record while source academic and finance history stays with the originating school.",
                )
            }

            workspace?.let { data ->
                item {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    ) {
                        EduCoreShowcaseStat(
                            label = "Outgoing",
                            value = data.metrics.crossOutgoing.toString(),
                            icon = Icons.Default.School,
                            modifier = Modifier.weight(1f),
                        )
                        EduCoreShowcaseStat(
                            label = "Incoming",
                            value = data.metrics.crossIncoming.toString(),
                            icon = Icons.Default.Apartment,
                            modifier = Modifier.weight(1f),
                        )
                    }
                }
                item {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    ) {
                        EduCoreShowcaseStat(
                            label = "Cross pending",
                            value = data.metrics.crossPending.toString(),
                            icon = Icons.Default.PendingActions,
                            modifier = Modifier.weight(1f),
                        )
                        EduCoreShowcaseStat(
                            label = "Interclass pending",
                            value = data.metrics.interclassPending.toString(),
                            icon = Icons.Default.CompareArrows,
                            modifier = Modifier.weight(1f),
                        )
                    }
                }

                item {
                    Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                        TransferWorkspaceTab.entries.forEach { tab ->
                            FilterChip(
                                selected = state.tab == tab,
                                onClick = { onTab(tab) },
                                label = { Text(tab.label) },
                            )
                        }
                    }
                }

                state.errorMessage?.let { error ->
                    item { EduCoreErrorBanner(error) }
                }

                if (state.tab == TransferWorkspaceTab.CROSS_SCHOOL) {
                    if (data.capabilities.crossSchoolRequest) {
                        item {
                            CrossSchoolRequestCard(
                                students = data.options.students,
                                destinations = data.options.destinations,
                                selectedStudentId = state.selectedStudentId,
                                selectedDestinationId = state.selectedDestinationId,
                                reason = state.reason,
                                enabled = !state.isMutating,
                                canSubmit = state.canSubmitRequest,
                                onStudent = onStudent,
                                onDestination = onDestination,
                                onReason = onReason,
                                onSubmit = onRequest,
                            )
                        }
                    }

                    item {
                        EduCoreSectionHeader(
                            title = "Cross-school transfers",
                            subtitle = "Incoming and outgoing requests visible to your school",
                        )
                    }
                    if (data.crossSchool.isEmpty()) {
                        item {
                            EduCoreEmptyState(
                                title = "No cross-school transfers",
                                message = "Transfer requests involving this school will appear here.",
                            )
                        }
                    } else {
                        items(data.crossSchool, key = { "cross-${it.id}" }) { transfer ->
                            CrossSchoolTransferCard(
                                transfer = transfer,
                                busy = state.isMutating,
                                canApprove = data.capabilities.crossSchoolApprove,
                                canReject = data.capabilities.crossSchoolReject,
                                onApprove = onApprove,
                                onReject = onReject,
                            )
                        }
                    }
                } else {
                    item {
                        EduCoreSectionHeader(
                            title = "Interclass transfers",
                            subtitle = if (data.capabilities.interclassMobileMutation)
                                "Manage movement between class arms"
                            else
                                "Read-only lifecycle history on mobile",
                        )
                    }
                    if (!data.capabilities.interclassView) {
                        item {
                            EduCoreEmptyState(
                                title = "Interclass access unavailable",
                                message = "Your role does not have permission to view interclass transfer history.",
                            )
                        }
                    } else if (data.interclass.isEmpty()) {
                        item {
                            EduCoreEmptyState(
                                title = "No interclass transfers",
                                message = "Interclass transfer requests for this school will appear here.",
                            )
                        }
                    } else {
                        items(data.interclass, key = { "inter-${it.id}" }) { transfer ->
                            InterclassTransferCard(transfer)
                        }
                    }
                }
            }

            if (workspace == null && state.errorMessage != null) {
                item {
                    EduCoreEmptyState(
                        title = "Unable to load transfers",
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
private fun CrossSchoolRequestCard(
    students: List<TransferStudentOptionDto>,
    destinations: List<TransferDestinationOptionDto>,
    selectedStudentId: Long?,
    selectedDestinationId: Long?,
    reason: String,
    enabled: Boolean,
    canSubmit: Boolean,
    onStudent: (Long?) -> Unit,
    onDestination: (Long?) -> Unit,
    onReason: (String) -> Unit,
    onSubmit: () -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            EduCoreSectionHeader(
                title = "New cross-school request",
                subtitle = "Select an active student and receiving school",
            )
            TransferDropdown(
                label = "Student",
                value = students.firstOrNull { it.id == selectedStudentId }?.let {
                    listOfNotNull(it.name, it.admissionNumber).joinToString(" · ")
                } ?: "Select student",
                enabled = enabled && students.isNotEmpty(),
                options = students,
                optionLabel = { listOfNotNull(it.name, it.admissionNumber).joinToString(" · ") },
                optionId = { it.id },
                onSelected = onStudent,
            )
            TransferDropdown(
                label = "Receiving school",
                value = destinations.firstOrNull { it.id == selectedDestinationId }?.name ?: "Select school",
                enabled = enabled && destinations.isNotEmpty(),
                options = destinations,
                optionLabel = { it.name },
                optionId = { it.id },
                onSelected = onDestination,
            )
            OutlinedTextField(
                value = reason,
                onValueChange = onReason,
                modifier = Modifier.fillMaxWidth(),
                enabled = enabled,
                label = { Text("Reason (optional)") },
                minLines = 2,
                maxLines = 4,
            )
            EduCorePrimaryButton(
                text = "Submit transfer request",
                onClick = onSubmit,
                modifier = Modifier.fillMaxWidth(),
                enabled = canSubmit,
                loading = !enabled,
            )
        }
    }
}

@Composable
private fun <T> TransferDropdown(
    label: String,
    value: String,
    enabled: Boolean,
    options: List<T>,
    optionLabel: (T) -> String,
    optionId: (T) -> Long,
    onSelected: (Long?) -> Unit,
) {
    var expanded by remember { mutableStateOf(false) }
    Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
        Text(label, style = MaterialTheme.typography.labelMedium, color = EduCoreColors.Slate600)
        Box {
            EduCoreSecondaryButton(
                text = value,
                onClick = { expanded = true },
                modifier = Modifier.fillMaxWidth(),
                enabled = enabled,
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

@Composable
private fun CrossSchoolTransferCard(
    transfer: CrossSchoolTransferDto,
    busy: Boolean,
    canApprove: Boolean,
    canReject: Boolean,
    onApprove: (Long, String) -> Unit,
    onReject: (Long, String) -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column(Modifier.weight(1f)) {
                    Text(transfer.studentName, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                    transfer.admissionNumber?.let {
                        Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                    }
                }
                Text(
                    transfer.status.replace('_', ' ').replaceFirstChar { it.uppercase() },
                    style = MaterialTheme.typography.labelLarge,
                    color = if (transfer.status == "completed") EduCoreColors.Success700 else EduCoreColors.Navy900,
                )
            }
            Text(
                "${transfer.fromSchool} → ${transfer.toSchool}",
                style = MaterialTheme.typography.bodyMedium,
            )
            transfer.reason?.takeIf(String::isNotBlank)?.let {
                Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
            transfer.destinationStudentId?.let {
                Text(
                    "Receiving-school student #$it",
                    style = MaterialTheme.typography.labelMedium,
                    color = EduCoreColors.Success700,
                )
            }
            if (transfer.direction == "incoming" && transfer.status == "pending") {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                ) {
                    if (canApprove) {
                        EduCorePrimaryButton(
                            text = "Approve",
                            onClick = { onApprove(transfer.id, transfer.studentName) },
                            modifier = Modifier.weight(1f),
                            enabled = !busy,
                        )
                    }
                    if (canReject) {
                        EduCoreDangerButton(
                            text = "Reject",
                            onClick = { onReject(transfer.id, transfer.studentName) },
                            modifier = Modifier.weight(1f),
                            enabled = !busy,
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun InterclassTransferCard(transfer: InterclassTransferDto) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs),
        ) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text(transfer.studentName, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                Text(
                    transfer.status.replace('_', ' ').replaceFirstChar { it.uppercase() },
                    style = MaterialTheme.typography.labelLarge,
                    color = EduCoreColors.Navy900,
                )
            }
            transfer.admissionNumber?.let {
                Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
            Text(
                "${transfer.fromClass ?: "Unassigned"} → ${transfer.toClass ?: "Unassigned"}",
                style = MaterialTheme.typography.bodyMedium,
            )
            transfer.effectiveDate?.let {
                Text("Effective $it", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
            transfer.reason?.takeIf(String::isNotBlank)?.let {
                Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
        }
    }
}
