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
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreMetricCard
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.OperationsRecord
import online.educoreng.educore.core.model.OperationsWorkspace

@Composable
internal fun PayrollScreen(
    state: OperationsUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
) {
    val workspace = state.workspace ?: return
    val periods = remember(workspace.sections, state.query) {
        workspace.sections.firstOrNull()?.records.orEmpty().filterOperations(state.query)
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Payroll",
                subtitle = "Authoritative payroll periods, gross pay, deductions and net obligations",
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item { PayrollSummary(workspace) }
        item {
            EduCoreSearchBar(
                query = state.query,
                onQueryChange = onQuery,
                placeholder = "Search payroll period or status",
            )
        }

        if (periods.isEmpty()) {
            item {
                EduCoreEmptyState(
                    title = if (state.query.isBlank()) "No payroll periods" else "No matching payroll periods",
                    message = "Prepared payroll periods will appear here with their gross, deduction and net totals.",
                )
            }
        } else {
            items(periods, key = { "payroll-${it.id}" }) { period -> PayrollPeriodCard(period) }
        }

        item {
            Text(
                text = if (workspace.module.canManage) {
                    "Your account has payroll-management authority. Payroll preparation remains server-authorized while write workflows are migrated into the native app."
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
private fun PayrollSummary(workspace: OperationsWorkspace) {
    val metrics = workspace.metrics.associateBy { it.key }
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
    ) {
        metrics["periods"]?.let {
            EduCoreMetricCard(
                label = it.label,
                value = it.value,
                icon = Icons.Default.Badge,
                modifier = Modifier.weight(1f),
                tone = EduCoreTone.Brand,
            )
        }
        metrics["net"]?.let {
            EduCoreMetricCard(
                label = it.label,
                value = it.value,
                icon = Icons.Default.AccountBalance,
                modifier = Modifier.weight(1f),
                tone = EduCoreTone.Success,
            )
        }
    }
}

@Composable
private fun PayrollPeriodCard(period: OperationsRecord) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.Top,
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
            ) {
                Column(Modifier.weight(1f)) {
                    Text(period.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    period.subtitle?.let {
                        Text(
                            it,
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis,
                        )
                    }
                }
                period.status?.let {
                    EduCoreStatusBadge(
                        text = it.replace('_', ' ').replaceFirstChar(Char::uppercase),
                        tone = payrollTone(it),
                    )
                }
            }

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                PayrollValue("Gross", period.fieldValue("Gross") ?: "—", Modifier.weight(1f))
                PayrollValue("Deductions", period.fieldValue("Deductions") ?: "—", Modifier.weight(1f))
                PayrollValue("Net", period.fieldValue("Net") ?: "—", Modifier.weight(1f))
            }
            PayrollValue("Payment date", period.fieldValue("Payment date") ?: "Not paid")
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

private fun OperationsRecord.fieldValue(label: String): String? =
    fields.firstOrNull { it.label.equals(label, ignoreCase = true) }?.value

private fun payrollTone(status: String): EduCoreTone = when (status.lowercase()) {
    "paid", "completed", "approved" -> EduCoreTone.Success
    "draft", "pending", "processing" -> EduCoreTone.Warning
    "cancelled", "failed" -> EduCoreTone.Danger
    else -> EduCoreTone.Info
}
