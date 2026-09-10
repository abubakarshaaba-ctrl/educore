package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AccountBalanceWallet
import androidx.compose.material.icons.filled.Download
import androidx.compose.material.icons.filled.ReceiptLong
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import java.util.Locale
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreMetricCard
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.StaffPayslipDetailDto
import online.educoreng.educore.core.network.dto.StaffPayslipSummaryDto

@Composable
internal fun StaffPayslipScreen(
    state: StaffPayslipUiState,
    onBack: () -> Unit,
    onOpen: (StaffPayslipSummaryDto) -> Unit,
    onDownload: () -> Unit,
    onRetry: () -> Unit,
    onDocumentOpened: () -> Unit,
) {
    OpenDocumentEffect(state.document, onDocumentOpened)
    val detail = state.selected
    val selectedSummary = state.selectedSummary
    if (state.isLoading && state.items.isEmpty() && detail == null) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Loading your payslips")
        return
    }

    if (selectedSummary != null) {
        StaffPayslipDetail(
            state = state,
            detail = detail,
            onBack = onBack,
            onDownload = onDownload,
            onRetry = { onOpen(selectedSummary) },
        )
        return
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader("My Payslips", "Monthly staff payroll statements", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item {
            Card(
                colors = CardDefaults.cardColors(containerColor = EduCoreColors.Navy900),
                border = BorderStroke(1.dp, EduCoreColors.Navy700),
            ) {
                Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    Icon(Icons.Default.AccountBalanceWallet, contentDescription = null, tint = EduCoreColors.Gold400)
                    Text("Your monthly pay records", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold, color = EduCoreColors.White)
                    Text("Open any issued month to review earnings, deductions and net pay, or download the official PDF.", color = EduCoreColors.Line200)
                }
            }
        }
        if (state.items.isEmpty()) {
            item {
                EduCoreEmptyState(
                    title = "No payslips issued yet",
                    message = "Payslips appear here after payroll for a month is processed and released by your school.",
                    actionLabel = if (state.errorMessage != null) "Retry" else null,
                    onAction = if (state.errorMessage != null) onRetry else null,
                )
            }
        } else {
            item { EduCoreSectionHeader("Payslip history", "Newest issued payroll periods first") }
            items(state.items, key = { "payslip-${it.id}" }) { item -> PayslipSummaryCard(item) { onOpen(item) } }
        }
        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

@Composable
private fun PayslipSummaryCard(item: StaffPayslipSummaryDto, onOpen: () -> Unit) {
    Card(
        onClick = onOpen,
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line300),
    ) {
        Row(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
            Icon(Icons.Default.ReceiptLong, contentDescription = null, tint = EduCoreColors.Navy700)
            Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                Text(item.periodTitle, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = EduCoreColors.Ink900)
                Text("Gross ${money(item.grossPay)} · Net ${money(item.netPay)}", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate700)
            }
            EduCoreStatusBadge(item.status?.replace('_', ' ')?.replaceFirstChar(Char::uppercase) ?: "Issued", item.status.payslipTone())
        }
    }
}

@Composable
private fun StaffPayslipDetail(
    state: StaffPayslipUiState,
    detail: StaffPayslipDetailDto?,
    onBack: () -> Unit,
    onDownload: () -> Unit,
    onRetry: () -> Unit,
) {
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader(state.selectedSummary?.periodTitle ?: "Payslip", "Staff payroll statement", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        if (state.isLoading && detail == null) {
            item { EduCoreLoadingState(message = "Loading payslip") }
        } else if (detail == null) {
            item {
                EduCoreEmptyState(
                    title = "Unable to load payslip",
                    message = state.errorMessage ?: "The payslip is unavailable.",
                    actionLabel = "Retry",
                    onAction = onRetry,
                )
            }
        } else {
            item {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreMetricCard("Gross pay", money(detail.earnings.grossPay), Modifier.weight(1f), icon = Icons.Default.AccountBalanceWallet, tone = EduCoreTone.Brand)
                    EduCoreMetricCard("Net pay", money(detail.netPay), Modifier.weight(1f), icon = Icons.Default.AccountBalanceWallet, tone = EduCoreTone.Success)
                }
            }
            item { PayslipBreakdownCard("Earnings", earningsRows(detail)) }
            item { PayslipBreakdownCard("Deductions", deductionRows(detail)) }
            detail.bank?.let { bank ->
                if (!bank.name.isNullOrBlank() || !bank.account.isNullOrBlank()) {
                    item { PayslipBreakdownCard("Payment account", listOf("Bank" to (bank.name ?: "Not recorded"), "Account" to (bank.account ?: "Not recorded"))) }
                }
            }
            item {
                EduCorePrimaryButton(
                    text = if (state.isDownloading) "Preparing PDF…" else "Download official payslip PDF",
                    onClick = onDownload,
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !state.isDownloading,
                    loading = state.isDownloading,
                    leadingIcon = { Icon(Icons.Default.Download, contentDescription = null) },
                )
            }
        }
        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

@Composable
private fun PayslipBreakdownCard(title: String, rows: List<Pair<String, String>>) {
    Card(colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line300)) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Text(title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = EduCoreColors.Navy900)
            rows.forEach { (label, value) ->
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    Text(label, color = EduCoreColors.Slate700, modifier = Modifier.weight(1f))
                    Text(value, color = EduCoreColors.Ink900, fontWeight = FontWeight.SemiBold)
                }
            }
        }
    }
}

private fun earningsRows(item: StaffPayslipDetailDto) = listOf(
    "Basic salary" to money(item.earnings.basicSalary),
    "Housing allowance" to money(item.earnings.housingAllowance),
    "Transport allowance" to money(item.earnings.transportAllowance),
    "Other allowances" to money(item.earnings.otherAllowances),
    "Gross pay" to money(item.earnings.grossPay),
)

private fun deductionRows(item: StaffPayslipDetailDto) = listOf(
    "Tax" to money(item.deductions.taxDeduction),
    "Pension" to money(item.deductions.pensionDeduction),
    "Other deductions" to money(item.deductions.otherDeductions),
    "Total deductions" to money(item.deductions.totalDeductions),
)

private fun money(value: Double): String = String.format(Locale.US, "₦%,.2f", value)
private fun String?.payslipTone(): EduCoreTone = when (this?.lowercase()) {
    "paid" -> EduCoreTone.Success
    "pending" -> EduCoreTone.Warning
    else -> EduCoreTone.Brand
}
