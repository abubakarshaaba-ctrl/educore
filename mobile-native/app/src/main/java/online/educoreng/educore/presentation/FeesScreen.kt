package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AccountBalanceWallet
import androidx.compose.material.icons.filled.Payments
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreMetricCard
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTabs
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.OperationsField
import online.educoreng.educore.core.model.OperationsRecord
import online.educoreng.educore.core.model.OperationsWorkspace

@Composable
internal fun FeesScreen(
    state: OperationsUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSection: (Int) -> Unit,
) {
    val workspace = state.workspace ?: return
    val selected = workspace.sections.getOrNull(state.selectedSection)
    val records = remember(selected?.records, state.query) {
        selected?.records.orEmpty().filterOperations(state.query)
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Fees & Payments",
                subtitle = "Billing position, outstanding balances and verified collections",
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }

        item {
            FinanceSummary(workspace)
        }

        if (workspace.sections.size > 1) {
            item {
                EduCoreTabs(
                    labels = workspace.sections.map { "${it.title} (${it.count})" },
                    selectedIndex = state.selectedSection,
                    onSelected = onSection,
                )
            }
        }

        item {
            EduCoreSearchBar(
                query = state.query,
                onQueryChange = onQuery,
                placeholder = if (selected?.key == "payments") "Search payment reference or student" else "Search invoice or student",
            )
        }

        if (records.isEmpty()) {
            item {
                EduCoreEmptyState(
                    title = if (state.query.isBlank()) "No finance records" else "No matching records",
                    message = if (selected?.key == "payments") {
                        "Verified payments will appear here after collection is recorded."
                    } else {
                        "Invoices will appear here after fee bills are generated."
                    },
                )
            }
        } else if (selected?.key == "payments") {
            items(records, key = { "payment-${it.id}" }) { PaymentCard(it) }
        } else {
            items(records, key = { "invoice-${it.id}" }) { InvoiceCard(it) }
        }

        item {
            Text(
                text = if (workspace.module.canManage) {
                    "Your account has finance-management authority. Bill generation and payment-entry actions remain server-authorized while the native transaction workflow is being migrated."
                } else {
                    "This account has read-only finance access."
                },
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }
    }
}

@Composable
private fun FinanceSummary(workspace: OperationsWorkspace) {
    val metrics = workspace.metrics.associateBy { it.key }
    Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            metrics["billed"]?.let {
                EduCoreMetricCard(
                    label = it.label,
                    value = it.value,
                    icon = Icons.Default.AccountBalanceWallet,
                    modifier = Modifier.weight(1f),
                    tone = EduCoreTone.Brand,
                )
            }
            metrics["paid"]?.let {
                EduCoreMetricCard(
                    label = it.label,
                    value = it.value,
                    icon = Icons.Default.Payments,
                    modifier = Modifier.weight(1f),
                    tone = EduCoreTone.Success,
                )
            }
        }
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            metrics["balance"]?.let {
                EduCoreMetricCard(
                    label = it.label,
                    value = it.value,
                    icon = Icons.Default.AccountBalanceWallet,
                    modifier = Modifier.weight(1f),
                    tone = EduCoreTone.Warning,
                )
            }
            metrics["transactions"]?.let {
                EduCoreMetricCard(
                    label = it.label,
                    value = it.value,
                    icon = Icons.Default.Payments,
                    modifier = Modifier.weight(1f),
                    tone = EduCoreTone.Info,
                )
            }
        }
    }
}

@Composable
private fun InvoiceCard(record: OperationsRecord) {
    val total = record.field("Total")
    val paid = record.field("Paid")
    val balance = record.field("Balance")
    val progress = paymentProgress(total, paid)

    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.Top,
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
            ) {
                Column(Modifier.weight(1f)) {
                    Text(record.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    record.subtitle?.let {
                        Text(
                            it,
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis,
                        )
                    }
                }
                record.status?.let {
                    EduCoreStatusBadge(
                        text = it.replace('_', ' ').replaceFirstChar(Char::uppercase),
                        tone = financeTone(it),
                    )
                }
            }

            LinearProgressIndicator(
                progress = { progress },
                modifier = Modifier.fillMaxWidth(),
            )

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                FinanceValue("Billed", total ?: "—", Modifier.weight(1f))
                FinanceValue("Paid", paid ?: "—", Modifier.weight(1f))
                FinanceValue("Balance", balance ?: "—", Modifier.weight(1f))
            }

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                FinanceValue("Term", record.field("Term") ?: "Not assigned", Modifier.weight(1f))
                FinanceValue("Due", record.field("Due") ?: "Not set", Modifier.weight(1f))
            }
        }
    }
}

@Composable
private fun PaymentCard(record: OperationsRecord) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
            verticalAlignment = Alignment.Top,
        ) {
            Icon(Icons.Default.Payments, contentDescription = null, tint = MaterialTheme.colorScheme.primary)
            Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                Text(record.title, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
                record.subtitle?.let {
                    Text(it, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                Text(
                    listOfNotNull(
                        record.field("Amount"),
                        record.field("Gateway"),
                        record.field("Paid at"),
                    ).joinToString(" · "),
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            record.status?.let {
                EduCoreStatusBadge(
                    text = it.replace('_', ' ').replaceFirstChar(Char::uppercase),
                    tone = financeTone(it),
                )
            }
        }
    }
}

@Composable
private fun FinanceValue(label: String, value: String, modifier: Modifier = Modifier) {
    Column(modifier) {
        Text(label.uppercase(), style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Text(value, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Medium, maxLines = 1, overflow = TextOverflow.Ellipsis)
    }
}

private fun OperationsRecord.field(label: String): String? =
    fields.firstOrNull { it.label.equals(label, ignoreCase = true) }?.value

private fun paymentProgress(total: String?, paid: String?): Float {
    val totalValue = total.moneyValue()
    val paidValue = paid.moneyValue()
    if (totalValue <= 0.0) return 0f
    return (paidValue / totalValue).coerceIn(0.0, 1.0).toFloat()
}

private fun String?.moneyValue(): Double = this
    ?.replace(Regex("[^0-9.-]"), "")
    ?.toDoubleOrNull()
    ?: 0.0

private fun financeTone(status: String): EduCoreTone = when (status.lowercase()) {
    "paid", "success" -> EduCoreTone.Success
    "partially_paid", "partially paid", "pending", "unpaid" -> EduCoreTone.Warning
    "failed", "cancelled" -> EduCoreTone.Danger
    else -> EduCoreTone.Info
}
