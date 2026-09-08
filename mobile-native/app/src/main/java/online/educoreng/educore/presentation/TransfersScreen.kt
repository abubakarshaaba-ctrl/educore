package online.educoreng.educore.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Apartment
import androidx.compose.material.icons.filled.CompareArrows
import androidx.compose.material.icons.filled.PendingActions
import androidx.compose.material.icons.filled.School
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.FilterChip
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
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreConfirmationDialog
import online.educoreng.educore.core.designsystem.component.EduCoreDangerButton
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
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.CrossSchoolTransferDto
import online.educoreng.educore.core.network.dto.InterclassTransferDto
import online.educoreng.educore.core.network.dto.TransferClassOptionDto
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
    onClassStudent: (Long?) -> Unit,
    onClassDestination: (Long?) -> Unit,
    onClassDate: (String) -> Unit,
    onClassReason: (String) -> Unit,
    onClassRequest: () -> Unit,
    onClassApprove: (Long, String) -> Unit,
    onClassReject: (Long, String) -> Unit,
    onClassCancel: (Long, String) -> Unit,
    onActionReason: (String) -> Unit,
    onConfirm: () -> Unit,
    onCancelConfirm: () -> Unit,
    onRetry: () -> Unit,
) {
    val workspace = state.workspace
    val confirmation = state.confirmation
    val classConfirmation = confirmation?.target != null &&
        confirmation.target != TransferConfirmationTarget.CROSS_SCHOOL
    val needsReason = classConfirmation &&
        confirmation?.action in setOf(TransferConfirmationAction.REJECT, TransferConfirmationAction.CANCEL)

    if (needsReason && confirmation != null) {
        val typeLabel = if (confirmation.target == TransferConfirmationTarget.INTRA_CLASS) "intra-class" else "interclass"
        AlertDialog(
            onDismissRequest = onCancelConfirm,
            title = {
                Text(
                    if (confirmation.action == TransferConfirmationAction.REJECT) "Reject $typeLabel transfer?"
                    else "Cancel $typeLabel transfer?"
                )
            },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                    Text(
                        if (confirmation.action == TransferConfirmationAction.REJECT)
                            "Record why ${confirmation.studentName}'s request is being rejected. Enrollment will not change."
                        else
                            "Record why ${confirmation.studentName}'s pending request is being cancelled. Enrollment will not change."
                    )
                    OutlinedTextField(
                        value = state.actionReason,
                        onValueChange = onActionReason,
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text("Reason") },
                        minLines = 2,
                        maxLines = 5,
                    )
                }
            },
            confirmButton = {
                TextButton(
                    onClick = onConfirm,
                    enabled = state.actionReason.isNotBlank() && !state.isMutating,
                ) {
                    Text(if (confirmation.action == TransferConfirmationAction.REJECT) "Reject" else "Cancel request")
                }
            },
            dismissButton = {
                TextButton(onClick = onCancelConfirm, enabled = !state.isMutating) { Text("Back") }
            },
        )
    } else {
        EduCoreConfirmationDialog(
            visible = confirmation != null,
            title = when (confirmation?.action) {
                TransferConfirmationAction.REJECT -> "Reject transfer?"
                TransferConfirmationAction.CANCEL -> "Cancel transfer?"
                else -> "Approve transfer?"
            },
            message = when {
                confirmation == null -> ""
                confirmation.target == TransferConfirmationTarget.CROSS_SCHOOL &&
                    confirmation.action == TransferConfirmationAction.APPROVE ->
                    "Approve ${confirmation.studentName}'s transfer? EduCore will archive the source-school student and create a fresh student record for this school."
                confirmation.target == TransferConfirmationTarget.CROSS_SCHOOL ->
                    "Reject ${confirmation.studentName}'s incoming transfer request?"
                confirmation.target == TransferConfirmationTarget.INTRA_CLASS &&
                    confirmation.action == TransferConfirmationAction.APPROVE ->
                    "Approve ${confirmation.studentName}'s intra-class transfer? The current enrollment will close and a new enrollment will open in another arm of the same class level."
                confirmation.action == TransferConfirmationAction.APPROVE ->
                    "Approve ${confirmation.studentName}'s interclass transfer? The current enrollment will close and a new enrollment will open in the destination class level."
                else -> "Confirm this transfer action?"
            },
            confirmLabel = when (confirmation?.action) {
                TransferConfirmationAction.REJECT -> "Reject"
                TransferConfirmationAction.CANCEL -> "Cancel request"
                else -> "Approve"
            },
            destructive = confirmation?.action != TransferConfirmationAction.APPROVE,
            onConfirm = onConfirm,
            onDismiss = onCancelConfirm,
        )
    }

    Column(Modifier.fillMaxSize()) {
        EduCorePageHeader(
            title = "Student Transfers",
            subtitle = "Cross-school, intra-class and interclass movement",
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
                    subtitle = "Cross-school transfers create a receiving-school identity. Intra-class and interclass transfers replace only the current enrollment while preserving academic history.",
                )
            }

            workspace?.let { data ->
                item {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    ) {
                        EduCoreShowcaseStat(
                            label = "Cross pending",
                            value = data.metrics.crossPending.toString(),
                            icon = Icons.Default.School,
                            modifier = Modifier.weight(1f),
                        )
                        EduCoreShowcaseStat(
                            label = "Intra-class pending",
                            value = data.metrics.intraClassPending.toString(),
                            icon = Icons.Default.Apartment,
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

                state.message?.let { message ->
                    item { EduCoreInfoBanner(message = message, title = "Transfer updated") }
                }

                item {
                    LazyRow(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                        items(TransferWorkspaceTab.entries) { tab ->
                            FilterChip(
                                selected = state.tab == tab,
                                onClick = { onTab(tab) },
                                label = { Text(tab.label) },
                            )
                        }
                    }
                }

                state.errorMessage?.let { error -> item { EduCoreErrorBanner(error) } }

                when (state.tab) {
                    TransferWorkspaceTab.CROSS_SCHOOL -> {
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
                                supportingText = "Incoming and outgoing requests involving this school",
                            )
                        }
                        if (data.crossSchool.isEmpty()) {
                            item {
                                EduCoreEmptyState(
                                    title = "No cross-school transfers",
                                    message = "Cross-school requests involving this school will appear here.",
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
                    }

                    TransferWorkspaceTab.INTRA_CLASS,
                    TransferWorkspaceTab.INTERCLASS -> {
                        val isIntra = state.tab == TransferWorkspaceTab.INTRA_CLASS
                        val canView = if (isIntra) data.capabilities.intraClassView else data.capabilities.interclassView
                        val canRequest = if (isIntra) data.capabilities.intraClassRequest else data.capabilities.interclassRequest
                        val canApprove = if (isIntra) data.capabilities.intraClassApprove else data.capabilities.interclassApprove
                        val canReject = if (isIntra) data.capabilities.intraClassReject else data.capabilities.interclassReject
                        val canCancel = if (isIntra) data.capabilities.intraClassCancel else data.capabilities.interclassCancel
                        val rows = if (isIntra) data.intraClass else data.interclass
                        val label = if (isIntra) "Intra-class" else "Interclass"

                        if (canRequest) {
                            item {
                                ClassTransferRequestCard(
                                    type = state.tab,
                                    students = data.options.students,
                                    classes = state.classDestinationOptions,
                                    selectedStudentId = state.classStudentId,
                                    selectedClassId = state.classDestinationArmId,
                                    effectiveDate = state.classEffectiveDate,
                                    reason = state.classReason,
                                    enabled = !state.isMutating,
                                    canSubmit = state.canSubmitClassTransfer,
                                    onStudent = onClassStudent,
                                    onClass = onClassDestination,
                                    onDate = onClassDate,
                                    onReason = onClassReason,
                                    onSubmit = onClassRequest,
                                )
                            }
                        }

                        item {
                            EduCoreSectionHeader(
                                title = "$label transfers",
                                supportingText = if (isIntra)
                                    "Move a student to another arm within the same class level"
                                else
                                    "Move a student to an arm in a different class level",
                            )
                        }
                        if (!canView) {
                            item {
                                EduCoreEmptyState(
                                    title = "$label access unavailable",
                                    message = "Your role does not have permission to view this transfer history.",
                                )
                            }
                        } else if (rows.isEmpty()) {
                            item {
                                EduCoreEmptyState(
                                    title = "No ${label.lowercase()} transfers",
                                    message = "$label transfer requests for this school will appear here.",
                                )
                            }
                        } else {
                            items(rows, key = { "${if (isIntra) "intra" else "inter"}-${it.id}" }) { transfer ->
                                ClassTransferCard(
                                    transfer = transfer,
                                    busy = state.isMutating,
                                    canApprove = canApprove,
                                    canReject = canReject,
                                    canCancel = canCancel,
                                    onApprove = onClassApprove,
                                    onReject = onClassReject,
                                    onCancel = onClassCancel,
                                )
                            }
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
    TransferFormCard(
        title = "New cross-school request",
        subtitle = "Select an active student and receiving school",
    ) {
        TransferDropdown(
            label = "Student",
            value = students.firstOrNull { it.id == selectedStudentId }?.displayName() ?: "Select student",
            enabled = enabled && students.isNotEmpty(),
            options = students,
            optionLabel = TransferStudentOptionDto::displayName,
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

@Composable
private fun ClassTransferRequestCard(
    type: TransferWorkspaceTab,
    students: List<TransferStudentOptionDto>,
    classes: List<TransferClassOptionDto>,
    selectedStudentId: Long?,
    selectedClassId: Long?,
    effectiveDate: String,
    reason: String,
    enabled: Boolean,
    canSubmit: Boolean,
    onStudent: (Long?) -> Unit,
    onClass: (Long?) -> Unit,
    onDate: (String) -> Unit,
    onReason: (String) -> Unit,
    onSubmit: () -> Unit,
) {
    val selectedStudent = students.firstOrNull { it.id == selectedStudentId }
    val isIntra = type == TransferWorkspaceTab.INTRA_CLASS
    val label = if (isIntra) "intra-class" else "interclass"

    TransferFormCard(
        title = "New $label request",
        subtitle = if (isIntra)
            "Only other arms in the student's current class level are available"
        else
            "Only arms in a different class level are available",
    ) {
        TransferDropdown(
            label = "Student",
            value = selectedStudent?.displayName() ?: "Select student",
            enabled = enabled && students.isNotEmpty(),
            options = students.filter { it.classArmId != null && it.classLevelId != null },
            optionLabel = TransferStudentOptionDto::displayName,
            optionId = { it.id },
            onSelected = onStudent,
        )
        TransferDropdown(
            label = "Destination class arm",
            value = classes.firstOrNull { it.id == selectedClassId }?.name ?: "Select destination arm",
            enabled = enabled && selectedStudent != null && classes.isNotEmpty(),
            options = classes,
            optionLabel = { it.name },
            optionId = { it.id },
            onSelected = onClass,
        )
        if (selectedStudent != null && classes.isEmpty()) {
            Text(
                if (isIntra)
                    "No other class arm exists in this student's class level."
                else
                    "No destination arm exists in another class level.",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
        }
        OutlinedTextField(
            value = effectiveDate,
            onValueChange = onDate,
            modifier = Modifier.fillMaxWidth(),
            enabled = enabled,
            label = { Text("Effective date (YYYY-MM-DD)") },
            supportingText = { Text("Example: 2026-09-14") },
            singleLine = true,
        )
        OutlinedTextField(
            value = reason,
            onValueChange = onReason,
            modifier = Modifier.fillMaxWidth(),
            enabled = enabled,
            label = { Text("Reason") },
            minLines = 2,
            maxLines = 5,
        )
        EduCorePrimaryButton(
            text = "Create $label request",
            onClick = onSubmit,
            modifier = Modifier.fillMaxWidth(),
            enabled = canSubmit,
            loading = !enabled,
        )
    }
}

@Composable
private fun TransferFormCard(
    title: String,
    subtitle: String,
    content: @Composable () -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            EduCoreSectionHeader(title = title, supportingText = subtitle)
            content()
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
            TransferCardHeader(transfer.studentName, transfer.admissionNumber, transfer.status)
            Text("${transfer.fromSchool} → ${transfer.toSchool}", style = MaterialTheme.typography.bodyMedium)
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
private fun ClassTransferCard(
    transfer: InterclassTransferDto,
    busy: Boolean,
    canApprove: Boolean,
    canReject: Boolean,
    canCancel: Boolean,
    onApprove: (Long, String) -> Unit,
    onReject: (Long, String) -> Unit,
    onCancel: (Long, String) -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            TransferCardHeader(transfer.studentName, transfer.admissionNumber, transfer.status)
            Text(
                "${transfer.fromClass ?: "Unassigned"} → ${transfer.toClass ?: "Unassigned"}",
                style = MaterialTheme.typography.bodyMedium,
            )
            Text(
                if (transfer.movementType == "intra_class") "Same class level · different arm" else "Different class level",
                style = MaterialTheme.typography.labelMedium,
                color = EduCoreColors.Navy900,
            )
            transfer.effectiveDate?.let {
                Text("Effective $it", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
            transfer.requestedBy?.let {
                Text("Requested by $it", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
            transfer.reason?.takeIf(String::isNotBlank)?.let {
                Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }

            if (transfer.status == "pending") {
                if (canApprove) {
                    EduCorePrimaryButton(
                        text = "Approve & move enrollment",
                        onClick = { onApprove(transfer.id, transfer.studentName) },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !busy,
                    )
                }
                if (canReject || canCancel) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    ) {
                        if (canReject) {
                            EduCoreDangerButton(
                                text = "Reject",
                                onClick = { onReject(transfer.id, transfer.studentName) },
                                modifier = Modifier.weight(1f),
                                enabled = !busy,
                            )
                        }
                        if (canCancel) {
                            EduCoreSecondaryButton(
                                text = "Cancel request",
                                onClick = { onCancel(transfer.id, transfer.studentName) },
                                modifier = Modifier.weight(1f),
                                enabled = !busy,
                            )
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun TransferCardHeader(name: String, admissionNumber: String?, status: String) {
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
        Column(Modifier.weight(1f)) {
            Text(name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            admissionNumber?.let {
                Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
        }
        Text(
            status.replace('_', ' ').replaceFirstChar { it.uppercase() },
            style = MaterialTheme.typography.labelLarge,
            color = if (status == "completed") EduCoreColors.Success700 else EduCoreColors.Navy900,
        )
    }
}

private fun TransferStudentOptionDto.displayName(): String =
    listOfNotNull(name, admissionNumber).joinToString(" · ")
