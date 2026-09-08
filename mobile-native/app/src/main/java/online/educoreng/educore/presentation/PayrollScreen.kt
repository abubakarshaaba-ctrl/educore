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
import androidx.compose.material.icons.filled.AccountBalance
import androidx.compose.material.icons.filled.Badge
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import java.util.Locale
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
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.PayrollItemDto
import online.educoreng.educore.core.network.dto.PayrollPeriodDto

private enum class PayrollConfirmation { NONE, APPROVE, PAID }

@Composable
internal fun PayrollScreen(
    state: PayrollUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onStatus: (String) -> Unit,
    onOpen: (PayrollPeriodDto) -> Unit,
    onCloseDetail: () -> Unit,
    onApprove: () -> Unit,
    onMarkPaid: () -> Unit,
    onLoadMore: () -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.workspace == null) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading payroll")
    }
    state.workspace ?: return EduCoreErrorState(
        message = state.errorMessage ?: "Payroll is unavailable.",
        modifier = Modifier.fillMaxSize(),
        onRetry = onRetry,
    )

    if (state.detail != null) {
        PayrollDetailScreen(
            state = state,
            onBack = onCloseDetail,
            onApprove = onApprove,
            onMarkPaid = onMarkPaid,
        )
        return
    }

    PayrollRegisterScreen(
        state = state,
        onBack = onBack,
        onQuery = onQuery,
        onSearch = onSearch,
        onStatus = onStatus,
        onOpen = onOpen,
        onLoadMore = onLoadMore,
    )
}

@Composable
private fun PayrollRegisterScreen(
    state: PayrollUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onStatus: (String) -> Unit,
    onOpen: (PayrollPeriodDto) -> Unit,
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
                title = "Payroll",
                subtitle = "Review payroll periods, obligations and payment status",
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let { item { Text(it, color = EduCoreColors.Success700) } }
        item { PayrollSummary(state) }
        item {
            EduCoreSearchBar(
                value = state.query,
                onValueChange = onQuery,
                placeholder = "Search payroll period",
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

        if (state.periods.isEmpty()) {
            item {
                EduCoreEmptyState(
                    title = if (state.query.isBlank()) "No payroll periods" else "No matching payroll periods",
                    message = "Payroll periods generated by the authoritative payroll engine will appear here.",
                )
            }
        } else {
            items(state.periods, key = { "native-payroll-${it.id}" }) { period ->
                PayrollPeriodCard(period = period, onClick = { onOpen(period) })
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

        item {
            Text(
                text = if (state.canManage) {
                    "Payroll calculation remains controlled by EduCore's authoritative payroll engine. This workspace approves and closes prepared payroll periods."
                } else {
                    "This account has read-only payroll access."
                },
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }
    }
}

@Composable
private fun PayrollDetailScreen(
    state: PayrollUiState,
    onBack: () -> Unit,
    onApprove: () -> Unit,
    onMarkPaid: () -> Unit,
) {
    val detail = state.detail ?: return
    val period = detail.period
    var confirmation by rememberSaveable(period.id) { mutableStateOf(PayrollConfirmation.NONE) }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = period.title,
                subtitle = "${period.start} — ${period.end}",
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let { item { Text(it, color = EduCoreColors.Success700) } }
        item {
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
                            Text("Payroll summary", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                            period.paymentDate?.let {
                                Text("Paid on $it", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                        }
                        EduCoreStatusBadge(
                            period.status.replace('_', ' ').replaceFirstChar(Char::uppercase),
                            payrollTone(period.status),
                        )
                    }
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                        PayrollValue("Gross", period.gross.money(), Modifier.weight(1f))
                        PayrollValue("Deductions", period.deductions.money(), Modifier.weight(1f))
                        PayrollValue("Net", period.net.money(), Modifier.weight(1f))
                    }
                }
            }
        }

        if (state.canManage && period.status == "draft") {
            item {
                PayrollTransitionControl(
                    confirmation = confirmation,
                    requested = PayrollConfirmation.APPROVE,
                    actionText = "Approve payroll",
                    warning = "Approval confirms that this prepared payroll has been reviewed. The salary calculations themselves are not changed by this action.",
                    busy = state.isSaving,
                    onRequest = { confirmation = PayrollConfirmation.APPROVE },
                    onCancel = { confirmation = PayrollConfirmation.NONE },
                    onConfirm = {
                        confirmation = PayrollConfirmation.NONE
                        onApprove()
                    },
                )
            }
        }
        if (state.canManage && period.status == "approved") {
            item {
                PayrollTransitionControl(
                    confirmation = confirmation,
                    requested = PayrollConfirmation.PAID,
                    actionText = "Mark payroll paid",
                    warning = "This marks the entire payroll period and every payroll item as paid. Confirm only after payment has actually been completed.",
                    busy = state.isSaving,
                    onRequest = { confirmation = PayrollConfirmation.PAID },
                    onCancel = { confirmation = PayrollConfirmation.NONE },
                    onConfirm = {
                        confirmation = PayrollConfirmation.NONE
                        onMarkPaid()
                    },
                )
            }
        }

        item {
            Text(
                "Staff payroll lines (${detail.items.size})",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold,
            )
        }
        if (detail.items.isEmpty()) {
            item { EduCoreEmptyState("No payroll items", "No staff payroll lines are attached to this period.") }
        } else {
            items(detail.items, key = { "payroll-item-${it.id}" }) { PayrollItemCard(it) }
        }
    }
}

@Composable
private fun PayrollTransitionControl(
    confirmation: PayrollConfirmation,
    requested: PayrollConfirmation,
    actionText: String,
    warning: String,
    busy: Boolean,
    onRequest: () -> Unit,
    onCancel: () -> Unit,
    onConfirm: () -> Unit,
) {
    if (confirmation != requested) {
        EduCorePrimaryButton(
            text = actionText,
            onClick = onRequest,
            modifier = Modifier.fillMaxWidth(),
            enabled = !busy,
        )
        return
    }

    Card(
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.Warning100),
        border = BorderStroke(1.dp, EduCoreColors.Warning700),
    ) {
        Column(
            Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            Text("Confirm action", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
            Text(warning, style = MaterialTheme.typography.bodySmall)
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreSecondaryButton("Cancel", onCancel, Modifier.weight(1f), enabled = !busy)
                EduCorePrimaryButton("Confirm", onConfirm, Modifier.weight(1f), enabled = !busy, loading = busy)
            }
        }
    }
}

@Composable
private fun PayrollSummary(state: PayrollUiState) {
    val metrics = state.workspace?.metrics ?: return
    Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            EduCoreMetricCard("Periods", metrics.periods.toString(), Modifier.weight(1f), icon = Icons.Default.Badge, tone = EduCoreTone.Brand)
            EduCoreMetricCard("Net obligation", metrics.netTotal.money(), Modifier.weight(1f), icon = Icons.Default.AccountBalance, tone = EduCoreTone.Success)
        }
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            EduCoreMetricCard("Draft", metrics.draft.toString(), Modifier.weight(1f), tone = EduCoreTone.Warning)
            EduCoreMetricCard("Approved", metrics.approved.toString(), Modifier.weight(1f), tone = EduCoreTone.Info)
            EduCoreMetricCard("Paid", metrics.paid.toString(), Modifier.weight(1f), tone = EduCoreTone.Success)
        }
    }
}

@Composable
private fun PayrollPeriodCard(period: PayrollPeriodDto, onClick: () -> Unit) {
    Card(
        onClick = onClick,
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
                    Text(period.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    Text("${period.start} — ${period.end}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                EduCoreStatusBadge(period.status.replace('_', ' ').replaceFirstChar(Char::uppercase), payrollTone(period.status))
            }
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                PayrollValue("Gross", period.gross.money(), Modifier.weight(1f))
                PayrollValue("Deductions", period.deductions.money(), Modifier.weight(1f))
                PayrollValue("Net", period.net.money(), Modifier.weight(1f))
            }
        }
    }
}

@Composable
private fun PayrollItemCard(item: PayrollItemDto) {
    val breakdown = remember(item.deductionBreakdown) {
        item.deductionBreakdown.filter { !it.label.isNullOrBlank() && it.amount != null }
    }
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
                    Text(item.staffName ?: "Staff member", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
                    Text(
                        listOfNotNull(item.staffNumber, item.role?.replace('_', ' ')).joinToString(" · "),
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                EduCoreStatusBadge(item.paymentStatus.replace('_', ' ').replaceFirstChar(Char::uppercase), payrollTone(item.paymentStatus))
            }
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                PayrollValue("Gross", item.gross.money(), Modifier.weight(1f))
                PayrollValue("Deductions", item.deductions.money(), Modifier.weight(1f))
                PayrollValue("Net", item.net.money(), Modifier.weight(1f))
            }
            if (item.tax > 0 || item.pension > 0 || item.otherDeductions > 0) {
                Text(
                    "PAYE ${item.tax.money()} · Pension ${item.pension.money()} · Other ${item.otherDeductions.money()}",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            breakdown.forEach { line ->
                Text(
                    "${line.label}: ${line.amount?.money().orEmpty()}",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis,
                )
            }
        }
    }
}

@Composable
private fun PayrollValue(label: String, value: String, modifier: Modifier = Modifier) {
    Column(modifier) {
        Text(label.uppercase(), style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Text(value, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Medium, maxLines = 1, overflow = TextOverflow.Ellipsis)
    }
}

private fun Double.money(): String = String.format(Locale.US, "₦%,.2f", this)

private fun payrollTone(status: String): EduCoreTone = when (status.lowercase()) {
    "paid", "completed", "approved" -> EduCoreTone.Success
    "draft", "pending", "processing" -> EduCoreTone.Warning
    "cancelled", "failed" -> EduCoreTone.Danger
    else -> EduCoreTone.Info
}
