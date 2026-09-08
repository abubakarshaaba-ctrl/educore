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
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
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
import online.educoreng.educore.core.designsystem.component.EduCoreTextField
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.ExpenseDto

@Composable
internal fun ExpensesScreen(
    state: ExpensesUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onCategory: (String) -> Unit,
    onCreate: () -> Unit,
    onEdit: (ExpenseDto) -> Unit,
    onCloseEditor: () -> Unit,
    onField: (ExpenseField, String) -> Unit,
    onDraftCategory: (String) -> Unit,
    onSession: (Long?) -> Unit,
    onTerm: (Long?) -> Unit,
    onSave: () -> Unit,
    onDelete: (Long) -> Unit,
    onLoadMore: () -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.workspace == null) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading expenses")
    }
    state.workspace ?: return EduCoreErrorState(
        message = state.errorMessage ?: "Expenses are unavailable.",
        modifier = Modifier.fillMaxSize(),
        onRetry = onRetry,
    )

    if (state.editorOpen) {
        ExpenseEditor(
            state = state,
            onBack = onCloseEditor,
            onField = onField,
            onCategory = onDraftCategory,
            onSession = onSession,
            onTerm = onTerm,
            onSave = onSave,
        )
        return
    }

    ExpenseRegister(
        state = state,
        onBack = onBack,
        onQuery = onQuery,
        onSearch = onSearch,
        onCategory = onCategory,
        onCreate = onCreate,
        onEdit = onEdit,
        onDelete = onDelete,
        onLoadMore = onLoadMore,
    )
}

@Composable
private fun ExpenseRegister(
    state: ExpensesUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onCategory: (String) -> Unit,
    onCreate: () -> Unit,
    onEdit: (ExpenseDto) -> Unit,
    onDelete: (Long) -> Unit,
    onLoadMore: () -> Unit,
) {
    val workspace = state.workspace ?: return
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader("Expenses", "School expenditure register and controls", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let { item { Text(it, color = EduCoreColors.Success700) } }
        item { ExpenseSummary(state) }
        if (state.canManage) {
            item { EduCorePrimaryButton("Record expense", onCreate, Modifier.fillMaxWidth(), enabled = !state.isSaving) }
        }
        item {
            EduCoreSearchBar(
                value = state.query,
                onValueChange = onQuery,
                placeholder = "Search expense, reference or payment method",
            )
        }
        item { EduCoreSecondaryButton("Search", onSearch, Modifier.fillMaxWidth(), enabled = !state.isLoading) }
        if (workspace.categories.isNotEmpty()) {
            item {
                EduCoreSegmentedControl(
                    options = workspace.categories.map { it.label },
                    selectedIndex = workspace.categories.indexOfFirst { it.key == state.category }.coerceAtLeast(0),
                    onSelected = { index -> workspace.categories.getOrNull(index)?.let { onCategory(it.key) } },
                )
            }
        }
        if (state.expenses.isEmpty()) {
            item {
                EduCoreEmptyState(
                    if (state.query.isBlank()) "No expenses recorded" else "No matching expenses",
                    "Recorded school expenditure will appear here.",
                )
            }
        } else {
            items(state.expenses, key = { "native-expense-${it.id}" }) { expense ->
                ExpenseCard(expense, state.canManage, state.isSaving, onEdit, onDelete)
            }
            if (state.hasMore) {
                item {
                    EduCoreSecondaryButton(
                        if (state.isLoadingMore) "Loading…" else "Load more",
                        onLoadMore,
                        Modifier.fillMaxWidth(),
                        enabled = !state.isLoadingMore,
                    )
                }
            }
        }
        if (!state.canManage) {
            item { Text("This account has read-only expense access.", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant) }
        }
    }
}

@Composable
private fun ExpenseEditor(
    state: ExpensesUiState,
    onBack: () -> Unit,
    onField: (ExpenseField, String) -> Unit,
    onCategory: (String) -> Unit,
    onSession: (Long?) -> Unit,
    onTerm: (Long?) -> Unit,
    onSave: () -> Unit,
) {
    val workspace = state.workspace ?: return
    val draft = state.draft
    val categories = workspace.categories.filter { it.key != "all" }
    val visibleTerms = workspace.terms.filter { draft.sessionId == null || it.sessionId == draft.sessionId }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader(if (draft.id == null) "Record Expense" else "Edit Expense", "Tenant-scoped finance record", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item { EduCoreTextField(draft.title, { onField(ExpenseField.TITLE, it) }, "Title", Modifier.fillMaxWidth(), enabled = !state.isSaving) }
        item { EduCoreTextField(draft.amount, { onField(ExpenseField.AMOUNT, it) }, "Amount", Modifier.fillMaxWidth(), supportingText = "Amount must be at least ₦1", enabled = !state.isSaving) }
        item { EduCoreTextField(draft.expenseDate, { onField(ExpenseField.DATE, it) }, "Expense date", Modifier.fillMaxWidth(), supportingText = "Use YYYY-MM-DD", enabled = !state.isSaving) }
        if (categories.isNotEmpty()) {
            item {
                Text("Category", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
                EduCoreSegmentedControl(
                    options = categories.map { it.label },
                    selectedIndex = categories.indexOfFirst { it.key == draft.category }.coerceAtLeast(0),
                    onSelected = { index -> categories.getOrNull(index)?.let { onCategory(it.key) } },
                    enabled = !state.isSaving,
                )
            }
        }
        item { EduCoreTextField(draft.paymentMethod, { onField(ExpenseField.PAYMENT_METHOD, it) }, "Payment method (optional)", Modifier.fillMaxWidth(), enabled = !state.isSaving) }
        item { EduCoreTextField(draft.reference, { onField(ExpenseField.REFERENCE, it) }, "Reference (optional)", Modifier.fillMaxWidth(), enabled = !state.isSaving) }

        if (workspace.sessions.isNotEmpty()) {
            item {
                Text("Academic session", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
                val options = listOf("None") + workspace.sessions.map { it.name }
                val selected = draft.sessionId?.let { id -> workspace.sessions.indexOfFirst { it.id == id }.takeIf { it >= 0 }?.plus(1) } ?: 0
                EduCoreSegmentedControl(
                    options = options,
                    selectedIndex = selected,
                    onSelected = { index -> onSession(if (index == 0) null else workspace.sessions.getOrNull(index - 1)?.id) },
                    enabled = !state.isSaving,
                )
            }
        }
        if (visibleTerms.isNotEmpty()) {
            item {
                Text("Academic term", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
                val options = listOf("None") + visibleTerms.map { it.name }
                val selected = draft.termId?.let { id -> visibleTerms.indexOfFirst { it.id == id }.takeIf { it >= 0 }?.plus(1) } ?: 0
                EduCoreSegmentedControl(
                    options = options,
                    selectedIndex = selected,
                    onSelected = { index -> onTerm(if (index == 0) null else visibleTerms.getOrNull(index - 1)?.id) },
                    enabled = !state.isSaving,
                )
            }
        }
        item {
            EduCoreTextField(
                draft.description,
                { onField(ExpenseField.DESCRIPTION, it) },
                "Description (optional)",
                Modifier.fillMaxWidth(),
                enabled = !state.isSaving,
                singleLine = false,
            )
        }
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreSecondaryButton("Cancel", onBack, Modifier.weight(1f), enabled = !state.isSaving)
                EduCorePrimaryButton("Save", onSave, Modifier.weight(1f), enabled = draft.valid, loading = state.isSaving)
            }
        }
    }
}

@Composable
private fun ExpenseSummary(state: ExpensesUiState) {
    val metrics = state.workspace?.metrics ?: return
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
        EduCoreMetricCard("Total expenses", metrics.total.money(), Modifier.weight(1f), icon = Icons.Default.ReceiptLong, tone = EduCoreTone.Warning)
        EduCoreMetricCard("Records", metrics.records.toString(), Modifier.weight(1f), icon = Icons.Default.Summarize, tone = EduCoreTone.Brand)
    }
}

@Composable
private fun ExpenseCard(
    expense: ExpenseDto,
    canManage: Boolean,
    busy: Boolean,
    onEdit: (ExpenseDto) -> Unit,
    onDelete: (Long) -> Unit,
) {
    var confirmDelete by rememberSaveable(expense.id) { mutableStateOf(false) }
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
                Column(Modifier.weight(1f)) {
                    Text(expense.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    Text(expense.category.replace('_', ' ').replaceFirstChar(Char::uppercase), style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                Text(expense.amount.money(), style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            }
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                ExpenseValue("Date", expense.expenseDate, Modifier.weight(1f))
                ExpenseValue("Method", expense.paymentMethod ?: "Not recorded", Modifier.weight(1f))
            }
            expense.reference?.let { ExpenseValue("Reference", it) }
            val period = listOfNotNull(expense.term, expense.session).joinToString(" · ")
            if (period.isNotBlank()) ExpenseValue("Academic period", period)
            expense.description?.takeIf { it.isNotBlank() }?.let {
                Text(it, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant, maxLines = 3, overflow = TextOverflow.Ellipsis)
            }
            if (canManage) {
                if (!confirmDelete) {
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                        EduCoreSecondaryButton("Edit", { onEdit(expense) }, Modifier.weight(1f), enabled = !busy)
                        EduCoreSecondaryButton("Delete", { confirmDelete = true }, Modifier.weight(1f), enabled = !busy)
                    }
                } else {
                    Text("Delete this expense record? This action cannot be undone.", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Danger700)
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                        EduCoreSecondaryButton("Cancel", { confirmDelete = false }, Modifier.weight(1f), enabled = !busy)
                        EduCorePrimaryButton("Confirm delete", {
                            confirmDelete = false
                            onDelete(expense.id)
                        }, Modifier.weight(1f), enabled = !busy, loading = busy)
                    }
                }
            }
        }
    }
}

@Composable
private fun ExpenseValue(label: String, value: String, modifier: Modifier = Modifier) {
    Column(modifier) {
        Text(label.uppercase(), style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Text(value, style = MaterialTheme.typography.bodyMedium, maxLines = 2, overflow = TextOverflow.Ellipsis)
    }
}

private fun Double.money(): String = String.format(Locale.US, "₦%,.2f", this)
