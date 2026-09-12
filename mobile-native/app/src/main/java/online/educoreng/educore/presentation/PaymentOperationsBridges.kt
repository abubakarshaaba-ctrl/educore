package online.educoreng.educore.presentation

import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle

@Composable
internal fun SubscriptionPaymentOperationsScreen(onBack: () -> Unit) {
    val viewModel: PaymentsViewModel = hiltViewModel()
    val state by viewModel.uiState.collectAsStateWithLifecycle()
    LaunchedEffect(Unit) { viewModel.loadSubscription() }
    SubscriptionPaymentScreen(
        state = state,
        onBack = onBack,
        onBillingCycle = viewModel::setBillingCycle,
        onEnrollment = viewModel::setAnticipatedEnrollment,
        onGateway = viewModel::selectGateway,
        onGenerateInvoice = viewModel::createSubscriptionInvoice,
        onPay = viewModel::startSubscriptionCheckout,
        onVerify = viewModel::verifySubscription,
        onRetry = { viewModel.loadSubscription() },
    )
}

@Composable
internal fun ParentFeePaymentOperationsScreen(onBack: () -> Unit) {
    val viewModel: PaymentsViewModel = hiltViewModel()
    val state by viewModel.uiState.collectAsStateWithLifecycle()
    LaunchedEffect(Unit) { viewModel.loadParentFees() }
    ParentFeePaymentScreen(
        state = state,
        onBack = onBack,
        onChild = { childId -> viewModel.loadParentFees(childId) },
        onPay = { invoiceId -> viewModel.startParentFeeCheckout(invoiceId) },
        onVerify = viewModel::verifyParentFee,
        onRetry = { viewModel.loadParentFees(state.parentFees?.selectedChildId) },
    )
}
