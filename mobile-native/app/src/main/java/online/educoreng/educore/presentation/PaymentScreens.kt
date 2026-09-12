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
import online.educoreng.educore.core.network.SchoolFeePaymentDto
import online.educoreng.educore.core.network.SubscriptionInvoiceDto
import online.educoreng.educore.core.network.SubscriptionPaymentDto

@Composable
internal fun SubscriptionPaymentScreen(
    state: PaymentsUiState,
    onBack: () -> Unit,
    onBillingCycle: (String) -> Unit,
    onEnrollment: (String) -> Unit,
    onGateway: (String) -> Unit,
    onBankReference: (String) -> Unit,
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
        item { EduCorePageHeader("Subscription & Billing", "School-admin subscription payment", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let { message ->
            item {
                Card(Modifier.fillMaxWidth()) {
                    Text(message, modifier = Modifier.padding(14.dp), style = MaterialTheme.typography.bodyMedium)
                }
            }
        }
        if (workspace == null) {
            item { EduCoreEmptyState("Subscription unavailable", "Reload the billing workspace.") }
            item { Button(onClick = onRetry) { Text("Retry") } }
            return@LazyColumn
        }

        item {
            Card(Modifier.fillMaxWidth()) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                    Text(workspace.tenant.name ?: "School subscription", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                    Text("Status: ${workspace.tenant.status.displayStatus()}")
                    Text("Plan: ${if (workspace.tenant.isFreeTier) "Free tier" else "Paid tier"}")
                    Text("Expires: ${workspace.tenant.subscriptionExpiresAt ?: "Not set"}")
                    workspace.tenant.daysRemaining?.let { Text("Days remaining: $it") }
                    Text("Current students: ${workspace.tenant.activeStudents ?: 0}")
                    Text("Paid capacity: ${workspace.tenant.studentsCapacity}")
                    Text("Rate: ${money(workspace.pricing.ratePerStudentPerTerm)} per student / term")
                }
            }
        }

        workspace.outstandingInvoice?.let { invoice ->
            item {
                Card(Modifier.fillMaxWidth()) {
                    Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(5.dp)) {
                        Text("Outstanding invoice", fontWeight = FontWeight.Bold)
                        Text("${invoice.number} · ${money(invoice.amount)}")
                        Text("${invoice.billingCycle.displayStatus()} · ${invoice.paymentStatus?.displayStatus() ?: invoice.status.displayStatus()}")
                        if (invoice.studentCount > 0) Text("Capacity requested: ${invoice.studentCount}")
                    }
                }
            }
        }

        item { EduCoreSectionHeader("Generate or update invoice", "Choose the billing cycle and anticipated enrolment. Current active enrolment remains the minimum capacity.") }
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
                label = { Text("Anticipated enrolment") },
                singleLine = true,
                modifier = Modifier.fillMaxWidth(),
            )
        }
        item {
            Button(onClick = onGenerateInvoice, enabled = !state.isMutating, modifier = Modifier.fillMaxWidth()) {
                Text(if (state.isMutating) "Processing…" else "Generate / refresh invoice")
            }
        }

        if (workspace.gateways.isNotEmpty()) {
            item { EduCoreSectionHeader("Payment method", "Only methods configured by EduCore are shown. Gateway verification is performed on the server.") }
            item {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    workspace.gateways.filter { it.available }.forEach { gateway ->
                        FilterChip(
                            selected = state.selectedGateway == gateway.name,
                            onClick = { onGateway(gateway.name) },
                            label = { Text(gateway.name.gatewayLabel()) },
                        )
                    }
                }
            }
        }

        val bank = workspace.gateways.firstOrNull { it.name == "bank_transfer" && it.available }
        if (state.selectedGateway == "bank_transfer" && bank != null) {
            item {
                Card(Modifier.fillMaxWidth()) {
                    Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(5.dp)) {
                        Text("Bank transfer", fontWeight = FontWeight.Bold)
                        bank.bankName?.let { Text("Bank: $it") }
                        bank.accountName?.let { Text("Account name: $it") }
                        bank.accountNumber?.let { Text("Account number: $it") }
                        Text("Transfer the exact invoice amount, then enter the bank reference below. Submission does not activate the subscription until EduCore verifies the transfer.", style = MaterialTheme.typography.bodySmall)
                    }
                }
            }
            item {
                OutlinedTextField(
                    value = state.bankTransferReference,
                    onValueChange = onBankReference,
                    label = { Text("Transfer reference") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                )
            }
        }

        state.checkout?.let { checkout ->
            checkout.checkoutUrl?.let { checkoutUrl ->
                item {
                    Card(Modifier.fillMaxWidth()) {
                        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                            Text("Secure checkout", fontWeight = FontWeight.Bold)
                            checkout.amount?.let { Text("Amount: ${money(it)}") }
                            Text("Reference: ${checkout.reference}")
                            Button(onClick = { uriHandler.openUri(checkoutUrl) }, modifier = Modifier.fillMaxWidth()) { Text("Open secure checkout") }
                            Button(onClick = onVerify, enabled = !state.isMutating, modifier = Modifier.fillMaxWidth()) {
                                Text(if (state.isMutating) "Verifying…" else "Verify payment")
                            }
                            Text("The subscription changes only after EduCore verifies the transaction directly with the gateway.", style = MaterialTheme.typography.bodySmall)
                        }
                    }
                }
            }
        }

        item { EduCoreSectionHeader("Invoices", "Recent EduCore subscription invoices") }
        if (workspace.invoices.isEmpty()) item { EduCoreEmptyState("No subscription invoices", "Generate an invoice when you are ready to renew.") }
        items(workspace.invoices, key = { it.id }) { invoice ->
            val awaitingBankVerification = invoice.paymentStatus == "awaiting_verification"
            SubscriptionInvoiceCard(
                invoice = invoice,
                paymentMethod = state.selectedGateway,
                paymentAvailable = workspace.gateways.any { it.available } && state.checkout == null && !awaitingBankVerification,
                enabled = !state.isMutating,
                onPay = { onPay(invoice.id) },
            )
        }

        item { EduCoreSectionHeader("Payment history", "Verified platform subscription payments") }
        if (workspace.payments.isEmpty()) item { EduCoreEmptyState("No verified payments", "Verified subscription payments will appear here.") }
        items(workspace.payments, key = { it.id }) { payment -> SubscriptionPaymentCard(payment) }
        item { TextButton(onClick = onRetry) { Text("Refresh subscription status") } }
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
        item { EduCorePageHeader("School Fees", "Pay linked children's invoices securely", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let { message ->
            item {
                Card(Modifier.fillMaxWidth()) {
                    Text(message, modifier = Modifier.padding(14.dp), style = MaterialTheme.typography.bodyMedium)
                }
            }
        }
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
                    Text(workspace.gateway?.name?.gatewayLabel() ?: "Not configured")
                    if (workspace.gateway == null) {
                        Text("Contact the school administrator to enable online fee payment.", style = MaterialTheme.typography.bodySmall)
                    } else {
                        Text("The outstanding amount is calculated by the server. EduCore credits the invoice only after provider verification.", style = MaterialTheme.typography.bodySmall)
                    }
                }
            }
        }

        state.checkout?.let { checkout ->
            checkout.checkoutUrl?.let { checkoutUrl ->
                item {
                    Card(Modifier.fillMaxWidth()) {
                        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                            Text("Secure checkout", fontWeight = FontWeight.Bold)
                            checkout.amount?.let { Text("Amount: ${money(it)}") }
                            Text("Reference: ${checkout.reference}")
                            Button(onClick = { uriHandler.openUri(checkoutUrl) }, modifier = Modifier.fillMaxWidth()) { Text("Open secure checkout") }
                            Button(onClick = onVerify, enabled = !state.isMutating, modifier = Modifier.fillMaxWidth()) {
                                Text(if (state.isMutating) "Verifying…" else "Verify payment")
                            }
                            Text("After returning from the gateway, verify here to refresh the server-confirmed invoice balance.", style = MaterialTheme.typography.bodySmall)
                        }
                    }
                }
            }
        }

        item { EduCoreSectionHeader("Invoices", "Outstanding and recent fee invoices") }
        if (workspace.invoices.isEmpty()) item { EduCoreEmptyState("No fee invoices", "There are no invoices for this child.") }
        items(workspace.invoices, key = { it.id }) { invoice ->
            ParentFeeInvoiceCard(invoice, workspace.gateway?.available == true && state.checkout == null, !state.isMutating) { onPay(invoice.id) }
        }

        item { EduCoreSectionHeader("Payment history", "Recent successful school-fee transactions for linked children") }
        if (workspace.payments.isEmpty()) item { EduCoreEmptyState("No successful payments", "Verified payments will appear here.") }
        items(workspace.payments, key = { it.id }) { payment -> ParentFeePaymentCard(payment) }

        item { TextButton(onClick = onRetry) { Text("Refresh fees and payments") } }
    }
}

@Composable
private fun SubscriptionInvoiceCard(
    invoice: SubscriptionInvoiceDto,
    paymentMethod: String?,
    paymentAvailable: Boolean,
    enabled: Boolean,
    onPay: () -> Unit,
) {
    Card(Modifier.fillMaxWidth()) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(5.dp)) {
            Text(invoice.number, fontWeight = FontWeight.Bold)
            Text("${invoice.billingCycle.displayStatus()} · ${money(invoice.amount)}")
            if (invoice.studentCount > 0) Text("Student capacity: ${invoice.studentCount}")
            Text("Status: ${(invoice.paymentStatus ?: invoice.status).displayStatus()}")
            invoice.dueDate?.let { Text("Due: $it", style = MaterialTheme.typography.bodySmall) }
            invoice.paymentReference?.let { Text("Reference: $it", style = MaterialTheme.typography.bodySmall) }
            if (invoice.status != "paid" && invoice.paymentStatus != "awaiting_verification") {
                Button(onClick = onPay, enabled = paymentAvailable && enabled, modifier = Modifier.fillMaxWidth()) {
                    Text(if (paymentMethod == "bank_transfer") "Submit transfer reference" else "Pay subscription")
                }
            }
            if (invoice.paymentStatus == "awaiting_verification") {
                Text("Bank transfer submitted — awaiting platform verification.", style = MaterialTheme.typography.bodySmall)
            }
        }
    }
}

@Composable
private fun SubscriptionPaymentCard(payment: SubscriptionPaymentDto) {
    Card(Modifier.fillMaxWidth()) {
        Column(Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
            Text(money(payment.amount), fontWeight = FontWeight.Bold)
            Text("${payment.paymentMethod?.gatewayLabel() ?: "Payment"} · ${payment.status.displayStatus()}")
            Text("Reference: ${payment.reference}", style = MaterialTheme.typography.bodySmall)
            payment.paidAt?.let { Text(it, style = MaterialTheme.typography.bodySmall) }
        }
    }
}

@Composable
private fun ParentFeeInvoiceCard(invoice: ParentFeeInvoiceDto, gatewayAvailable: Boolean, enabled: Boolean, onPay: () -> Unit) {
    Card(Modifier.fillMaxWidth()) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(5.dp)) {
            Text(invoice.number, fontWeight = FontWeight.Bold)
            invoice.studentName?.let { Text(it) }
            Text(listOfNotNull(invoice.term, invoice.session).joinToString(" · "))
            Text("Total: ${money(invoice.totalAmount)}")
            Text("Paid: ${money(invoice.amountPaid)}")
            Text("Outstanding: ${money(invoice.balance)}", fontWeight = FontWeight.SemiBold)
            Text("Status: ${invoice.status.displayStatus()}")
            if (invoice.canPay && invoice.balance > 0.0 && invoice.status != "paid") {
                Button(onClick = onPay, enabled = gatewayAvailable && enabled, modifier = Modifier.fillMaxWidth()) { Text("Pay fees") }
            } else if (invoice.status == "paid") {
                Text("Paid", fontWeight = FontWeight.SemiBold)
            }
        }
    }
}

@Composable
private fun ParentFeePaymentCard(payment: SchoolFeePaymentDto) {
    Card(Modifier.fillMaxWidth()) {
        Column(Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
            Text(money(payment.amount), fontWeight = FontWeight.Bold)
            Text("${payment.gateway.gatewayLabel()} · ${payment.status.displayStatus()}")
            Text("Reference: ${payment.reference}", style = MaterialTheme.typography.bodySmall)
            payment.paidAt?.let { Text(it, style = MaterialTheme.typography.bodySmall) }
        }
    }
}

private fun String.displayStatus(): String = replace('_', ' ').replace('-', ' ').split(' ')
    .filter(String::isNotBlank)
    .joinToString(" ") { it.replaceFirstChar(Char::uppercase) }

private fun String.gatewayLabel(): String = when (lowercase()) {
    "paystack", "paystack_online" -> "Paystack"
    "monnify", "monnify_online" -> "Monnify"
    "flutterwave" -> "Flutterwave"
    "bank_transfer" -> "Bank Transfer"
    else -> displayStatus()
}

private fun money(value: Double): String = NumberFormat.getCurrencyInstance(Locale("en", "NG")).format(value)
