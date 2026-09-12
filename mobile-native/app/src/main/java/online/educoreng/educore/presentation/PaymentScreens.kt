package online.educoreng.educore.presentation

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
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalUriHandler
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import java.text.NumberFormat
import java.util.Locale
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.network.ParentFeeInvoiceDto
import online.educoreng.educore.core.network.SubscriptionInvoiceDto

@Composable
internal fun SubscriptionPaymentScreen(
    state: PaymentsUiState,
    onBack: () -> Unit,
    onBillingCycle: (String) -> Unit,
    onEnrollment: (String) -> Unit,
    onGateway: (String) -> Unit,
    onGenerateInvoice: () -> Unit,
    onPay: (Long) -> Unit,
    onVerify: () -> Unit,
    onRetry: () -> Unit,
) {
    val uriHandler = LocalUriHandler.current
    if (state.isLoading && state.subscription == null) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Loading subscription billing")
        return
    }
    val workspace = state.subscription
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item { EduCorePageHeader("Subscription & Billing", "Pay EduCore subscription securely", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        if (workspace == null) {
            item { EduCoreEmptyState("Subscription unavailable", "Reload the billing workspace.") }
            item { Button(onClick = onRetry) { Text("Retry") } }
            return@LazyColumn
        }
        item {
            Card(Modifier.fillMaxWidth()) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                    Text(workspace.tenant.name ?: "School subscription", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                    Text("Status: ${workspace.tenant.status.replaceFirstChar(Char::uppercase)}")
                    Text("Expires: ${workspace.tenant.subscriptionExpiresAt ?: "Not set"}")
                    Text("Active students: ${workspace.tenant.activeStudents ?: 0}")
                    Text("Capacity: ${workspace.tenant.studentsCapacity}")
                    Text("Rate: ${money(workspace.pricing.ratePerStudentPerTerm)} per student / term")
                }
            }
        }
        item { EduCoreSectionHeader("Generate invoice", "Choose a billing cycle and anticipated enrollment.") }
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                FilterChip(selected = state.billingCycle == "termly", onClick = { onBillingCycle("termly") }, label = { Text("Termly") })
                FilterChip(selected = state.billingCycle == "annual", onClick = { onBillingCycle("annual") }, label = { Text("Annual") })
            }
        }
        item {
            OutlinedTextField(
                value = state.anticipatedEnrollment,
                onValueChange = onEnrollment,
                label = { Text("Anticipated enrollment") },
                singleLine = true,
                modifier = Modifier.fillMaxWidth(),
            )
        }
        if (workspace.gateways.isNotEmpty()) {
            item { Text("Payment gateway", fontWeight = FontWeight.SemiBold) }
            item {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    workspace.gateways.forEach { gateway ->
                        FilterChip(
                            selected = state.selectedGateway == gateway,
                            onClick = { onGateway(gateway) },
                            label = { Text(gateway.replaceFirstChar(Char::uppercase)) },
                        )
                    }
                }
            }
        }
        item {
            Button(onClick = onGenerateInvoice, enabled = !state.isMutating, modifier = Modifier.fillMaxWidth()) {
                Text(if (state.isMutating) "Processing…" else "Generate subscription invoice")
            }
        }
        state.checkout?.let { checkout ->
            item {
                Card(Modifier.fillMaxWidth()) {
                    Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Text("Payment checkout", fontWeight = FontWeight.Bold)
                        Text("Reference: ${checkout.reference}")
                        Button(onClick = { uriHandler.openUri(checkout.checkoutUrl) }, modifier = Modifier.fillMaxWidth()) { Text("Open secure checkout") }
                        Button(onClick = onVerify, enabled = !state.isMutating, modifier = Modifier.fillMaxWidth()) { Text("Verify payment") }
                        Text("Complete payment in the secure gateway page, return to EduCore, then tap Verify payment.", style = MaterialTheme.typography.bodySmall)
                    }
                }
            }
        }
        item { EduCoreSectionHeader("Invoices", "Recent EduCore subscription invoices") }
        if (workspace.invoices.isEmpty()) item { EduCoreEmptyState("No subscription invoices", "Generate an invoice when you are ready to renew.") }
        items(workspace.invoices, key = { it.id }) { invoice ->
            SubscriptionInvoiceCard(invoice, workspace.gateways.isNotEmpty() && state.checkout == null, !state.isMutating) { onPay(invoice.id) }
        }
        item { Spacer(Modifier.height(12.dp)) }
    }
}

@Composable
internal fun ParentFeePaymentScreen(
    state: PaymentsUiState,
    onBack: () -> Unit,
    onChild: (Long) -> Unit,
    onPay: (Long) -> Unit,
    onVerify: () -> Unit,
    onRetry: () -> Unit,
) {
    val uriHandler = LocalUriHandler.current
    if (state.isLoading && state.parentFees == null) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Loading school fees")
        return
    }
    val workspace = state.parentFees
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item { EduCorePageHeader("Fees & Payments", "Pay a child's outstanding school fees securely", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        if (workspace == null) {
            item { EduCoreEmptyState("Fees unavailable", "Reload the parent fees workspace.") }
            item { Button(onClick = onRetry) { Text("Retry") } }
            return@LazyColumn
        }
        if (workspace.children.size > 1) {
            item { Text("Child", fontWeight = FontWeight.SemiBold) }
            item {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    workspace.children.forEach { child ->
                        FilterChip(
                            selected = workspace.selectedChildId == child.id,
                            onClick = { onChild(child.id) },
                            label = { Text(child.name) },
                        )
                    }
                }
            }
        }
        item {
            Card(Modifier.fillMaxWidth()) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                    Text("Online payment", fontWeight = FontWeight.Bold)
                    Text(workspace.gateway?.name?.replaceFirstChar(Char::uppercase) ?: "Not configured")
                    if (workspace.gateway == null) Text("Contact the school administrator to enable online fee payment.", style = MaterialTheme.typography.bodySmall)
                }
            }
        }
        state.checkout?.let { checkout ->
            item {
                Card(Modifier.fillMaxWidth()) {
                    Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Text("Payment checkout", fontWeight = FontWeight.Bold)
                        checkout.amount?.let { Text("Amount: ${money(it)}") }
                        Text("Reference: ${checkout.reference}")
                        Button(onClick = { uriHandler.openUri(checkout.checkoutUrl) }, modifier = Modifier.fillMaxWidth()) { Text("Open secure checkout") }
                        Button(onClick = onVerify, enabled = !state.isMutating, modifier = Modifier.fillMaxWidth()) { Text("Verify payment") }
                        Text("EduCore confirms payment directly with the gateway before updating the invoice.", style = MaterialTheme.typography.bodySmall)
                    }
                }
            }
        }
        item { EduCoreSectionHeader("Invoices", "Outstanding and recent fee invoices") }
        if (workspace.invoices.isEmpty()) item { EduCoreEmptyState("No fee invoices", "There are no invoices for this child.") }
        items(workspace.invoices, key = { it.id }) { invoice ->
            ParentFeeInvoiceCard(invoice, workspace.gateway != null && state.checkout == null, !state.isMutating) { onPay(invoice.id) }
        }
        item { TextButton(onClick = onRetry) { Text("Refresh") } }
    }
}

@Composable
private fun SubscriptionInvoiceCard(invoice: SubscriptionInvoiceDto, gatewayAvailable: Boolean, enabled: Boolean, onPay: () -> Unit) {
    Card(Modifier.fillMaxWidth()) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(5.dp)) {
            Text(invoice.number, fontWeight = FontWeight.Bold)
            Text("${invoice.billingCycle.replaceFirstChar(Char::uppercase)} · ${money(invoice.amount)}")
            Text("Status: ${invoice.status.replaceFirstChar(Char::uppercase)}")
            invoice.dueDate?.let { Text("Due: $it", style = MaterialTheme.typography.bodySmall) }
            if (invoice.status != "paid") {
                Button(onClick = onPay, enabled = gatewayAvailable && enabled, modifier = Modifier.fillMaxWidth()) { Text("Pay subscription") }
            }
        }
    }
}

@Composable
private fun ParentFeeInvoiceCard(invoice: ParentFeeInvoiceDto, gatewayAvailable: Boolean, enabled: Boolean, onPay: () -> Unit) {
    Card(Modifier.fillMaxWidth()) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(5.dp)) {
            Text(invoice.number, fontWeight = FontWeight.Bold)
            Text(listOfNotNull(invoice.term, invoice.session).joinToString(" · "))
            Text("Total: ${money(invoice.totalAmount)}")
            Text("Paid: ${money(invoice.amountPaid)}")
            Text("Balance: ${money(invoice.balance)}", fontWeight = FontWeight.SemiBold)
            Text("Status: ${invoice.status.replace('_', ' ').replaceFirstChar(Char::uppercase)}")
            if (invoice.balance > 0.0 && invoice.status != "paid") {
                Button(onClick = onPay, enabled = gatewayAvailable && enabled, modifier = Modifier.fillMaxWidth()) { Text("Pay balance") }
            }
        }
    }
}

private fun money(value: Double): String = NumberFormat.getCurrencyInstance(Locale("en", "NG")).format(value)
