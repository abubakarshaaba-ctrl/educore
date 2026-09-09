package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreFilterChip
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreMetricCard
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTextField
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.PlatformInvoiceDto

@Composable
internal fun PlatformBillingManagementScreen(
    state: PlatformBillingUiState,
    onBack: () -> Unit,
    onStatus: (String) -> Unit,
    onTenant: (Long?) -> Unit,
    onOpenInvoice: () -> Unit,
    onCloseInvoice: () -> Unit,
    onInvoiceTenant: (Long?) -> Unit,
    onCycle: (String) -> Unit,
    onCapacity: (String) -> Unit,
    onDueDate: (String) -> Unit,
    onNotes: (String) -> Unit,
    onCreateInvoice: () -> Unit,
    onDownloadInvoice: (PlatformInvoiceDto) -> Unit,
    onDocumentOpened: () -> Unit,
    onRequestSettlement: (PlatformInvoiceDto) -> Unit,
    onCancelSettlement: () -> Unit,
    onSettlementMethod: (String) -> Unit,
    onSettlementReference: (String) -> Unit,
    onConfirmSettlement: () -> Unit,
    onRetry: () -> Unit,
) {
    OpenDocumentEffect(state.document, onDocumentOpened)

    if (state.isLoading && state.workspace == null) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading platform invoices")
    }
    val workspace = state.workspace ?: return EduCoreErrorState(
        message = state.errorMessage ?: "Platform invoices are unavailable.",
        modifier = Modifier.fillMaxSize(),
        onRetry = onRetry,
    )

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader("Invoices & Billing", "Generate invoices, download official PDFs and confirm verified offline payments", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let { item { Text(it, color = EduCoreColors.Success700) } }

        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreMetricCard("Invoiced", "₦${workspace.summary.totalInvoiced.toLong()}", Modifier.weight(1f), tone = EduCoreTone.Brand)
                EduCoreMetricCard("Paid", "₦${workspace.summary.totalPaid.toLong()}", Modifier.weight(1f), tone = EduCoreTone.Success)
            }
        }
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreMetricCard("Overdue", "₦${workspace.summary.totalOverdue.toLong()}", Modifier.weight(1f), tone = EduCoreTone.Danger)
                EduCoreMetricCard("Pending", workspace.summary.pendingCount.toString(), Modifier.weight(1f), tone = EduCoreTone.Warning)
            }
        }
        item {
            Row(Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                listOf("all", "pending", "paid", "overdue", "cancelled").forEach { option ->
                    EduCoreFilterChip(option.replaceFirstChar(Char::uppercase), state.status == option, { onStatus(option) })
                }
            }
        }
        item {
            Row(Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreFilterChip("All schools", state.tenantId == null, { onTenant(null) })
                workspace.tenants.forEach { tenant ->
                    EduCoreFilterChip(tenant.name, state.tenantId == tenant.id, { onTenant(tenant.id) })
                }
            }
        }
        item { EduCorePrimaryButton("Generate invoice", onOpenInvoice, Modifier.fillMaxWidth(), enabled = !state.isMutating && state.downloadingInvoiceId == null) }

        if (state.invoiceEditorOpen) {
            item {
                BillingEditorCard(
                    state = state,
                    onTenant = onInvoiceTenant,
                    onCycle = onCycle,
                    onCapacity = onCapacity,
                    onDueDate = onDueDate,
                    onNotes = onNotes,
                    onSave = onCreateInvoice,
                    onCancel = onCloseInvoice,
                )
            }
        }

        if (workspace.invoices.isEmpty()) {
            item { EduCoreEmptyState("No invoices", "Invoices matching the selected filters will appear here.") }
        } else {
            items(workspace.invoices, key = { "platform-invoice-${it.id}" }) { invoice ->
                PlatformInvoiceCard(
                    invoice = invoice,
                    busy = state.isMutating,
                    downloading = state.downloadingInvoiceId == invoice.id,
                    onDownload = { onDownloadInvoice(invoice) },
                    onSettle = { onRequestSettlement(invoice) },
                )
            }
        }

        state.settlementDraft.invoice?.let { invoice ->
            item {
                SettlementConfirmationCard(
                    state = state,
                    invoice = invoice,
                    onMethod = onSettlementMethod,
                    onReference = onSettlementReference,
                    onConfirm = onConfirmSettlement,
                    onCancel = onCancelSettlement,
                )
            }
        }
    }
}

@Composable
private fun BillingEditorCard(
    state: PlatformBillingUiState,
    onTenant: (Long?) -> Unit,
    onCycle: (String) -> Unit,
    onCapacity: (String) -> Unit,
    onDueDate: (String) -> Unit,
    onNotes: (String) -> Unit,
    onSave: () -> Unit,
    onCancel: () -> Unit,
) {
    val draft = state.invoiceDraft
    Card(colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Text("New invoice", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
            Text("School", style = MaterialTheme.typography.labelLarge)
            Row(Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                state.workspace?.tenants.orEmpty().forEach { tenant ->
                    EduCoreFilterChip(tenant.name, draft.tenantId == tenant.id, { onTenant(tenant.id) }, enabled = !state.isMutating)
                }
            }
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreFilterChip("Termly", draft.billingCycle == "termly", { onCycle("termly") }, enabled = !state.isMutating)
                EduCoreFilterChip("Annual", draft.billingCycle == "annual", { onCycle("annual") }, enabled = !state.isMutating)
            }
            EduCoreTextField(draft.capacity, onCapacity, "Anticipated enrollment", Modifier.fillMaxWidth(), enabled = !state.isMutating)
            EduCoreTextField(draft.dueDate, onDueDate, "Due date", Modifier.fillMaxWidth(), enabled = !state.isMutating, supportingText = "YYYY-MM-DD")
            EduCoreTextField(draft.notes, onNotes, "Notes", Modifier.fillMaxWidth(), enabled = !state.isMutating)
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreSecondaryButton("Cancel", onCancel, Modifier.weight(1f), enabled = !state.isMutating)
                EduCorePrimaryButton("Generate", onSave, Modifier.weight(1f), enabled = draft.valid && !state.isMutating, loading = state.isMutating)
            }
        }
    }
}

@Composable
private fun PlatformInvoiceCard(
    invoice: PlatformInvoiceDto,
    busy: Boolean,
    downloading: Boolean,
    onDownload: () -> Unit,
    onSettle: () -> Unit,
) {
    Card(colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
                Column(Modifier.weight(1f)) {
                    Text(invoice.invoiceNumber, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    Text(invoice.school ?: "School", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                EduCoreStatusBadge(invoice.status.replaceFirstChar(Char::uppercase), invoiceStatusTone(invoice.status))
            }
            Text("₦${invoice.amount.toLong()} · ${invoice.studentCount} students · ${invoice.billingCycle.replaceFirstChar(Char::uppercase)}", style = MaterialTheme.typography.bodyMedium)
            Text("Due ${invoice.dueDate ?: "—"}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            EduCoreSecondaryButton(
                text = if (downloading) "Downloading PDF…" else "Download PDF",
                onClick = onDownload,
                modifier = Modifier.fillMaxWidth(),
                enabled = !busy && !downloading,
            )
            if (invoice.status != "paid") {
                EduCoreSecondaryButton("Confirm payment", onSettle, Modifier.fillMaxWidth(), enabled = !busy && !downloading)
            } else {
                Text("Paid ${invoice.paidAt ?: ""} · ${invoice.paymentMethod ?: "payment"}", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Success700)
            }
        }
    }
}

@Composable
private fun SettlementConfirmationCard(
    state: PlatformBillingUiState,
    invoice: PlatformInvoiceDto,
    onMethod: (String) -> Unit,
    onReference: (String) -> Unit,
    onConfirm: () -> Unit,
    onCancel: () -> Unit,
) {
    val draft = state.settlementDraft
    Card(colors = CardDefaults.cardColors(containerColor = EduCoreColors.Warning100), border = BorderStroke(1.dp, EduCoreColors.Warning700)) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Text("Confirm payment", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
            Text("${invoice.invoiceNumber} · ${invoice.school ?: "School"} · ₦${invoice.amount.toLong()}", style = MaterialTheme.typography.bodyMedium)
            Text("Use this only after the payment has been independently verified. This action records revenue and extends the school subscription.", style = MaterialTheme.typography.bodySmall)
            Row(Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                listOf("bank_transfer", "card", "cash", "pos", "other").forEach { method ->
                    EduCoreFilterChip(method.replace('_', ' ').replaceFirstChar(Char::uppercase), draft.method == method, { onMethod(method) }, enabled = !state.isMutating)
                }
            }
            EduCoreTextField(draft.reference, onReference, "Payment reference", Modifier.fillMaxWidth(), enabled = !state.isMutating, supportingText = "Optional; EduCore generates a unique reference when blank.")
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreSecondaryButton("Cancel", onCancel, Modifier.weight(1f), enabled = !state.isMutating)
                EduCorePrimaryButton("Mark paid", onConfirm, Modifier.weight(1f), enabled = draft.valid && !state.isMutating, loading = state.isMutating)
            }
        }
    }
}

private fun invoiceStatusTone(status: String): EduCoreTone = when (status.lowercase()) {
    "paid" -> EduCoreTone.Success
    "overdue" -> EduCoreTone.Danger
    "pending" -> EduCoreTone.Warning
    else -> EduCoreTone.Neutral
}
