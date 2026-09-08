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
import androidx.compose.material.icons.filled.ReceiptLong
import androidx.compose.material.icons.filled.Summarize
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
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.OperationsRecord
import online.educoreng.educore.core.model.OperationsWorkspace

@Composable
internal fun ExpensesScreen(
    state: OperationsUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
) {
    val workspace = state.workspace ?: return
    val expenses = remember(workspace.sections, state.query) {
        workspace.sections.firstOrNull()?.records.orEmpty().filterOperations(state.query)
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Expenses",
                subtitle = "Recorded school expenditure, categories and payment references",
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item { ExpensesSummary(workspace) }
        item {
            EduCoreSearchBar(
                query = state.query,
                onQueryChange = onQuery,
                placeholder = "Search expense, category or reference",
            )
        }

        if (expenses.isEmpty()) {
            item {
                EduCoreEmptyState(
                    title = if (state.query.isBlank()) "No expenses recorded" else "No matching expenses",
                    message = "Recorded school expenditure will appear here with payment method and reference details.",
                )
            }
        } else {
            items(expenses, key = { "expense-${it.id}" }) { expense -> ExpenseCard(expense) }
        }

        item {
            Text(
                text = if (workspace.module.canManage) {
                    "Your account has expense-management authority. Native create/edit expense actions will be enabled after the server mutation contract is hardened."
                } else {
                    "This account has read-only expense access."
                },
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }
    }
}

@Composable
private fun ExpensesSummary(workspace: OperationsWorkspace) {
    val metrics = workspace.metrics.associateBy { it.key }
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
    ) {
        metrics["total"]?.let {
            EduCoreMetricCard(
                label = it.label,
                value = it.value,
                icon = Icons.Default.ReceiptLong,
                modifier = Modifier.weight(1f),
                tone = EduCoreTone.Warning,
            )
        }
        metrics["records"]?.let {
            EduCoreMetricCard(
                label = it.label,
                value = it.value,
                icon = Icons.Default.Summarize,
                modifier = Modifier.weight(1f),
                tone = EduCoreTone.Brand,
            )
        }
    }
}

@Composable
private fun ExpenseCard(expense: OperationsRecord) {
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
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                verticalAlignment = Alignment.Top,
            ) {
                Icon(Icons.Default.ReceiptLong, contentDescription = null, tint = MaterialTheme.colorScheme.primary)
                Column(Modifier.weight(1f)) {
                    Text(expense.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    expense.subtitle?.let {
                        Text(
                            it,
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis,
                        )
                    }
                }
                Text(
                    expense.fieldValue("Amount") ?: "—",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                )
            }

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                ExpenseValue("Date", expense.fieldValue("Date") ?: "Not recorded", Modifier.weight(1f))
                ExpenseValue("Method", expense.fieldValue("Method") ?: "Not recorded", Modifier.weight(1f))
            }
            ExpenseValue("Reference", expense.fieldValue("Reference") ?: "None")
        }
    }
}

@Composable
private fun ExpenseValue(label: String, value: String, modifier: Modifier = Modifier) {
    Column(modifier) {
        Text(label.uppercase(), style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Text(value, style = MaterialTheme.typography.bodyMedium, maxLines = 1, overflow = TextOverflow.Ellipsis)
    }
}

private fun OperationsRecord.fieldValue(label: String): String? =
    fields.firstOrNull { it.label.equals(label, ignoreCase = true) }?.value
