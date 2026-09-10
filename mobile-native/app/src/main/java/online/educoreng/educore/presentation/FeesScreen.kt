package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AccountBalanceWallet
import androidx.compose.material.icons.filled.Payments
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.Icon
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
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
import online.educoreng.educore.core.designsystem.component.EduCoreTabs
import online.educoreng.educore.core.designsystem.component.EduCoreTextField
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.FeesInvoiceDto
import online.educoreng.educore.core.network.dto.FeesTransactionDto

@Composable
internal fun FeesScreen(
    state: FeesUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onStatus: (String) -> Unit,
    onTab: (FeesTab) -> Unit,
    onOpenPayment: (FeesInvoiceDto) -> Unit,
    onClosePayment: () -> Unit,
    onPaymentAmount: (String) -> Unit,
    onPaidByName: (String) -> Unit,
    onPaidByPhone: (String) -> Unit,
    onGateway: (FeesGateway) -> Unit,
    onSavePayment: () -> Unit,
    onOpenGeneration: () -> Unit,
    onCloseGeneration: () -> Unit,
    onGenerationTerm: (Long?) -> Unit,
    onGenerationClass: (Long?) -> Unit,
    onGenerate: () -> Unit,
    onLoadMore: () -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.workspace == null) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading fees and payments")
    }
    val workspace = state.workspace ?: return EduCoreErrorState(
        message = state.errorMessage ?: "Fees and payments are unavailable.",
        modifier = Modifier.fillMaxSize(),
        onRetry = onRetry,
    )

    when {
        state.paymentOpen -> FeesPaymentForm(
            state = state,
            onBack = onClosePayment,
            onAmount = onPaymentAmount,
            onPaidByName = onPaidByName,
            onPaidByPhone = onPaidByPhone,
            onGateway = onGateway,
            onSave = onSavePayment,
        )
        state.generationOpen -> FeesGenerationForm(
            state = state,
            onBack = onCloseGeneration,
            onTerm = onGenerationTerm,
            onClass = onGenerationClass,
            onGenerate = onGenerate,
        )
        else -> FeesRegister(
            state = state,
            onBack = onBack,
            onQuery = onQuery,
            onSearch = onSearch,
            onStatus = onStatus,
            onTab = onTab,
            onOpenPayment = onOpenPayment,
            onOpenGeneration = onOpenGeneration,
            onLoadMore = onLoadMore,
        )
    }
}

@Composable
private fun FeesRegister(
    state: FeesUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onStatus: (String) -> Unit,
    onTab: (FeesTab) -> Unit,
    onOpenPayment: (FeesInvoiceDto) -> Unit,
    onOpenGeneration: () -> Unit,
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
                title = "Fees & Payments",
                subtitle = "Billing, balances and verified collections",
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let {
            item { Text(it, color = EduCoreColors.Success700, style = MaterialTheme.typography.bodyMedium) }
        }
        item { FinanceSummary(state) }

        if (state.canManage) {
            item {
                EduCorePrimaryButton(
                    text = "Generate fee bills",
                    onClick = onOpenGeneration,
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !state.isSaving,
                )
            }
        }

        item {
            EduCoreTabs(
                labels = listOf("Invoices (${workspace.meta.total})", "Payments (${workspace.transactions.size})"),
                selectedIndex = if (state.tab == FeesTab.INVOICES) 0 else 1,
                onSelected = { onTab(if (it == 0) FeesTab.INVOICES else FeesTab.PAYMENTS) },
            )
        }

        if (state.tab == FeesTab.INVOICES) {
            item {
                EduCoreSearchBar(
                    value = state.query,
                    onValueChange = onQuery,
                    placeholder = "Search invoice, student or admission number",
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

            if (state.invoices.isEmpty()) {
                item {
                    EduCoreEmptyState(
                        title = if (state.query.isBlank()) "No invoices" else "No matching invoices",
                        message = "Generate fee bills or change the current search/filter.",
                    )
                }
            } else {
                items(state.invoices, key = { "fees-invoice-${it.id}" }) { invoice ->
                    NativeInvoiceCard(invoice, state.canManage, state.isSaving, onOpenPayment)
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
        } else {
            if (workspace.transactions.isEmpty()) {
                item { EduCoreEmptyState("No verified payments", "Successful collections will appear here.") }
            } else {
                items(workspace.transactions, key = { "fees-payment-${it.id}" }) { NativePaymentCard(it) }
            }
        }

        if (!state.canManage) {
            item {
                Text(
                    "This account has read-only finance access.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
        }
    }
}

@Composable
private fun FeesPaymentForm(
    state: FeesUiState,
    onBack: () -> Unit,
    onAmount: (String) -> Unit,
    onPaidByName: (String) -> Unit,
    onPaidByPhone: (String) -> Unit,
    onGateway: (FeesGateway) -> Unit,
    onSave: () -> Unit,
) {
    val invoice = state.payment.invoice ?: return
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader("Record Payment", "${invoice.number} · ${invoice.student.orEmpty()}", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item {
            Card(
                colors = CardDefaults.cardColors(containerColor = EduCoreColors.Info100),
                border = BorderStroke(1.dp, EduCoreColors.Info700),
            ) {
                Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md)) {
                    Text("Outstanding balance", style = MaterialTheme.typography.labelMedium)
                    Text(invoice.balance.money(), style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Bold)
                }
            }
        }
        item { EduCoreTextField(state.payment.amount, onAmount, "Amount", modifier = Modifier.fillMaxWidth(), supportingText = "Maximum ${invoice.balance.money()}") }
        item { EduCoreTextField(state.payment.paidByName, onPaidByName, "Paid by", modifier = Modifier.fillMaxWidth()) }
        item { EduCoreTextField(state.payment.paidByPhone, onPaidByPhone, "Phone (optional)", modifier = Modifier.fillMaxWidth()) }
        item {
            Text("Payment method", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
        }
        item {
            EduCoreSegmentedControl(
                options = FeesGateway.entries.map { it.label },
                selectedIndex = FeesGateway.entries.indexOf(state.payment.gateway),
                onSelected = { FeesGateway.entries.getOrNull(it)?.let(onGateway) },
                enabled = !state.isSaving,
            )
        }
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreSecondaryButton("Cancel", onBack, Modifier.weight(1f), enabled = !state.isSaving)
                EduCorePrimaryButton("Record payment", onSave, Modifier.weight(1f), enabled = state.payment.valid, loading = state.isSaving)
            }
        }
    }
}

@Composable
private fun FeesGenerationForm(
    state: FeesUiState,
    onBack: () -> Unit,
    onTerm: (Long?) -> Unit,
    onClass: (Long?) -> Unit,
    onGenerate: () -> Unit,
) {
    val workspace = state.workspace ?: return
    val termOptions = workspace.terms.map { term ->
        term.id to listOfNotNull(term.name, term.session).joinToString(" · ")
    }
    val classOptions = workspace.classLevels.map { level -> level.id to level.name }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader("Generate Fee Bills", "Create missing invoices from active fee structures", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }

        if (termOptions.isEmpty()) {
            item { EduCoreEmptyState("No terms available", "Configure the academic cycle before generating bills.") }
        } else {
            item {
                FinanceDropdown(
                    label = "Academic term",
                    options = termOptions,
                    selectedId = state.generation.termId,
                    enabled = !state.isSaving,
                    onSelected = onTerm,
                )
            }
        }

        if (classOptions.isEmpty()) {
            item { EduCoreEmptyState("No class levels available", "Configure class levels before generating bills.") }
        } else {
            item {
                FinanceDropdown(
                    label = "Class level",
                    options = classOptions,
                    selectedId = state.generation.classLevelId,
                    enabled = !state.isSaving,
                    onSelected = onClass,
                )
            }
        }

        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreSecondaryButton("Cancel", onBack, Modifier.weight(1f), enabled = !state.isSaving)
                EduCorePrimaryButton("Generate", onGenerate, Modifier.weight(1f), enabled = state.generation.valid, loading = state.isSaving)
            }
        }
    }
}

@Composable
private fun FinanceDropdown(
    label: String,
    options: List<Pair<Long, String>>,
    selectedId: Long?,
    enabled: Boolean,
    onSelected: (Long?) -> Unit,
) {
    var expanded by remember { mutableStateOf(false) }
    val selectedLabel = options.firstOrNull { it.first == selectedId }?.second ?: "Select $label"

    Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
        Text(label, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
        Box(Modifier.fillMaxWidth()) {
            OutlinedButton(
                onClick = { expanded = true },
                enabled = enabled && options.isNotEmpty(),
                modifier = Modifier.fillMaxWidth(),
            ) {
                Text(
                    text = selectedLabel,
                    modifier = Modifier.weight(1f),
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis,
                )
                Text("▾")
            }
            DropdownMenu(
                expanded = expanded,
                onDismissRequest = { expanded = false },
                modifier = Modifier.fillMaxWidth(.92f),
            ) {
                options.forEach { option ->
                    DropdownMenuItem(
                        text = { Text(option.second, maxLines = 2, overflow = TextOverflow.Ellipsis) },
                        onClick = {
                            expanded = false
                            onSelected(option.first)
                        },
                    )
                }
            }
        }
    }
}

@Composable
private fun FinanceSummary(state: FeesUiState) {
    val metrics = state.workspace?.metrics ?: return
    Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            EduCoreMetricCard("Billed", metrics.billed.money(), Modifier.weight(1f), icon = Icons.Default.AccountBalanceWallet, tone = EduCoreTone.Brand)
            EduCoreMetricCard("Collected", metrics.collected.money(), Modifier.weight(1f), icon = Icons.Default.Payments, tone = EduCoreTone.Success)
        }
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            EduCoreMetricCard("Outstanding", metrics.outstanding.money(), Modifier.weight(1f), icon = Icons.Default.AccountBalanceWallet, tone = EduCoreTone.Warning)
            EduCoreMetricCard("Transactions", metrics.successfulTransactions.toString(), Modifier.weight(1f), icon = Icons.Default.Payments, tone = EduCoreTone.Info)
        }
    }
}

@Composable
private fun NativeInvoiceCard(invoice: FeesInvoiceDto, canManage: Boolean, busy: Boolean, onPayment: (FeesInvoiceDto) -> Unit) {
    val progress = if (invoice.total <= 0) 0f else (invoice.paid / invoice.total).coerceIn(0.0, 1.0).toFloat()
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
                Column(Modifier.weight(1f)) {
                    Text(invoice.number, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    Text(
                        listOfNotNull(invoice.student, invoice.admissionNumber).joinToString(" · "),
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                EduCoreStatusBadge(invoice.status.replace('_', ' ').replaceFirstChar(Char::uppercase), financeTone(invoice.status))
            }
            LinearProgressIndicator(progress = { progress }, modifier = Modifier.fillMaxWidth())
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                FinanceValue("Billed", invoice.total.money(), Modifier.weight(1f))
                FinanceValue("Paid", invoice.paid.money(), Modifier.weight(1f))
                FinanceValue("Balance", invoice.balance.money(), Modifier.weight(1f))
            }
            Text(
                listOfNotNull(invoice.term, invoice.session, invoice.dueDate?.let { "Due $it" }).joinToString(" · "),
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            if (canManage && invoice.balance > 0 && invoice.status !in setOf("waived", "paid")) {
                EduCoreSecondaryButton(
                    text = "Record payment",
                    onClick = { onPayment(invoice) },
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !busy,
                )
            }
        }
    }
}

@Composable
private fun NativePaymentCard(payment: FeesTransactionDto) {
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
                Text(payment.reference, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold, maxLines = 1, overflow = TextOverflow.Ellipsis)
                Text(listOfNotNull(payment.student, payment.invoiceNumber).joinToString(" · "), style = MaterialTheme.typography.bodySmall)
                Text("${payment.amount.money()} · ${payment.gateway.replace('_', ' ')} · ${payment.paidAt.orEmpty()}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            EduCoreStatusBadge("Verified", EduCoreTone.Success)
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

private fun Double.money(): String = String.format(Locale.US, "₦%,.2f", this)

private fun financeTone(status: String): EduCoreTone = when (status.lowercase()) {
    "paid", "success" -> EduCoreTone.Success
    "partially_paid", "partially paid", "pending", "unpaid" -> EduCoreTone.Warning
    "failed", "cancelled", "overpaid" -> EduCoreTone.Danger
    "waived" -> EduCoreTone.Neutral
    else -> EduCoreTone.Info
}
