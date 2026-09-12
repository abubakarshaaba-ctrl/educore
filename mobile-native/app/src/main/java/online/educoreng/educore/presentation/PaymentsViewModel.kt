package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.CheckoutResponseDto
import online.educoreng.educore.core.network.GatewayRequestDto
import online.educoreng.educore.core.network.MobilePaymentsApi
import online.educoreng.educore.core.network.ParentFeesWorkspaceDto
import online.educoreng.educore.core.network.SubscriptionInvoiceRequestDto
import online.educoreng.educore.core.network.SubscriptionWorkspaceDto
import online.educoreng.educore.core.network.VerifyPaymentRequestDto
import retrofit2.HttpException

data class PaymentsUiState(
    val subscription: SubscriptionWorkspaceDto? = null,
    val parentFees: ParentFeesWorkspaceDto? = null,
    val billingCycle: String = "termly",
    val anticipatedEnrollment: String = "",
    val selectedGateway: String? = null,
    val checkout: CheckoutResponseDto? = null,
    val isLoading: Boolean = false,
    val isMutating: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
)

@HiltViewModel
class PaymentsViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api = factory.create(MobilePaymentsApi::class.java)
    private val _uiState = MutableStateFlow(PaymentsUiState())
    val uiState: StateFlow<PaymentsUiState> = _uiState.asStateFlow()

    fun loadSubscription() = viewModelScope.launch {
        _uiState.update { it.copy(isLoading = true, errorMessage = null, message = null, checkout = null) }
        try {
            val workspace = api.subscription()
            _uiState.update {
                it.copy(
                    subscription = workspace,
                    anticipatedEnrollment = (workspace.tenant.studentsCapacity.coerceAtLeast(workspace.tenant.activeStudents ?: 0)).toString(),
                    selectedGateway = it.selectedGateway?.takeIf(workspace.gateways::contains) ?: workspace.gateways.firstOrNull(),
                    isLoading = false,
                )
            }
        } catch (cancelled: CancellationException) {
            throw cancelled
        } catch (error: Throwable) {
            _uiState.update { it.copy(isLoading = false, errorMessage = error.paymentMessage()) }
        }
    }

    fun loadParentFees(childId: Long? = null) = viewModelScope.launch {
        _uiState.update { it.copy(isLoading = true, errorMessage = null, message = null, checkout = null) }
        try {
            val workspace = api.parentFees(childId)
            _uiState.update { it.copy(parentFees = workspace, isLoading = false) }
        } catch (cancelled: CancellationException) {
            throw cancelled
        } catch (error: Throwable) {
            _uiState.update { it.copy(isLoading = false, errorMessage = error.paymentMessage()) }
        }
    }

    fun setBillingCycle(value: String) = _uiState.update { it.copy(billingCycle = value) }
    fun setAnticipatedEnrollment(value: String) = _uiState.update {
        it.copy(anticipatedEnrollment = value.filter(Char::isDigit).take(6), errorMessage = null)
    }
    fun selectGateway(value: String) = _uiState.update { it.copy(selectedGateway = value, errorMessage = null) }

    fun createSubscriptionInvoice() {
        val count = _uiState.value.anticipatedEnrollment.toIntOrNull()
        if (count == null || count < 1) {
            _uiState.update { it.copy(errorMessage = "Enter the anticipated student enrollment.") }
            return
        }
        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null, message = null, checkout = null) }
            try {
                val response = api.createSubscriptionInvoice(SubscriptionInvoiceRequestDto(_uiState.value.billingCycle, count))
                if (response.free) {
                    _uiState.update { it.copy(isMutating = false, message = response.message ?: "Free-tier subscription remains active.") }
                    loadSubscription()
                } else {
                    _uiState.update { it.copy(isMutating = false, message = "Subscription invoice generated.") }
                    loadSubscription()
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isMutating = false, errorMessage = error.paymentMessage()) }
            }
        }
    }

    fun startSubscriptionCheckout(invoiceId: Long) {
        val gateway = _uiState.value.selectedGateway ?: run {
            _uiState.update { it.copy(errorMessage = "No online subscription gateway is currently available.") }
            return
        }
        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null, message = null, checkout = null) }
            try {
                val checkout = api.subscriptionCheckout(invoiceId, GatewayRequestDto(gateway))
                _uiState.update { it.copy(isMutating = false, checkout = checkout, message = "Checkout is ready. Complete payment, then return and verify.") }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isMutating = false, errorMessage = error.paymentMessage()) }
            }
        }
    }

    fun startParentFeeCheckout(invoiceId: Long) = viewModelScope.launch {
        _uiState.update { it.copy(isMutating = true, errorMessage = null, message = null, checkout = null) }
        try {
            val checkout = api.parentFeeCheckout(invoiceId)
            _uiState.update { it.copy(isMutating = false, checkout = checkout, message = "Checkout is ready. Complete payment, then return and verify.") }
        } catch (cancelled: CancellationException) {
            throw cancelled
        } catch (error: Throwable) {
            _uiState.update { it.copy(isMutating = false, errorMessage = error.paymentMessage()) }
        }
    }

    fun verifySubscription() {
        val reference = _uiState.value.checkout?.reference ?: return
        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null) }
            try {
                val response = api.verifySubscription(VerifyPaymentRequestDto(reference))
                _uiState.update { it.copy(isMutating = false, checkout = null, message = response.message) }
                loadSubscription()
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isMutating = false, errorMessage = error.paymentMessage()) }
            }
        }
    }

    fun verifyParentFee() {
        val reference = _uiState.value.checkout?.reference ?: return
        val childId = _uiState.value.parentFees?.selectedChildId
        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null) }
            try {
                val response = api.verifyParentFee(VerifyPaymentRequestDto(reference))
                _uiState.update { it.copy(isMutating = false, checkout = null, message = response.message) }
                loadParentFees(childId)
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isMutating = false, errorMessage = error.paymentMessage()) }
            }
        }
    }

    fun consumeMessage() = _uiState.update { it.copy(message = null) }
}

private fun Throwable.paymentMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "This account is not authorised to make this payment."
        404 -> "The selected invoice or payment reference is unavailable."
        422 -> "The payment could not be confirmed yet. Check the invoice or try Verify again."
        503 -> "The payment gateway is temporarily unavailable. Try again later."
        else -> "The payment service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank) ?: "Unable to reach the payment service. Check your connection and try again."
}
