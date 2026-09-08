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
import online.educoreng.educore.core.network.FeesApi
import online.educoreng.educore.core.network.dto.FeesGenerateRequestDto
import online.educoreng.educore.core.network.dto.FeesInvoiceDto
import online.educoreng.educore.core.network.dto.FeesPaymentRequestDto
import online.educoreng.educore.core.network.dto.FeesWorkspaceDto
import retrofit2.HttpException

enum class FeesTab { INVOICES, PAYMENTS }
enum class FeesGateway(val key: String, val label: String) {
    CASH("cash", "Cash"),
    BANK_TRANSFER("bank_transfer", "Bank transfer"),
    PAYSTACK("paystack", "Paystack"),
    MONNIFY("monnify", "Monnify"),
}

data class FeesPaymentDraft(
    val invoice: FeesInvoiceDto? = null,
    val amount: String = "",
    val paidByName: String = "",
    val paidByPhone: String = "",
    val gateway: FeesGateway = FeesGateway.CASH,
) {
    val amountValue: Double? get() = amount.trim().toDoubleOrNull()
    val valid: Boolean
        get() = invoice != null && paidByName.isNotBlank() && amountValue?.let { it > 0 && it <= invoice.balance } == true
}

data class FeesGenerationDraft(
    val termId: Long? = null,
    val classLevelId: Long? = null,
) {
    val valid: Boolean get() = termId != null && classLevelId != null
}

data class FeesUiState(
    val workspace: FeesWorkspaceDto? = null,
    val invoices: List<FeesInvoiceDto> = emptyList(),
    val query: String = "",
    val status: String = "all",
    val tab: FeesTab = FeesTab.INVOICES,
    val paymentOpen: Boolean = false,
    val payment: FeesPaymentDraft = FeesPaymentDraft(),
    val generationOpen: Boolean = false,
    val generation: FeesGenerationDraft = FeesGenerationDraft(),
    val isLoading: Boolean = false,
    val isLoadingMore: Boolean = false,
    val isSaving: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val canManage: Boolean get() = workspace?.capabilities?.manage == true
    val hasMore: Boolean get() = workspace?.meta?.hasMore == true
}

@HiltViewModel
class FeesViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api = factory.create(FeesApi::class.java)
    private val _uiState = MutableStateFlow(FeesUiState())
    val uiState: StateFlow<FeesUiState> = _uiState.asStateFlow()

    fun load() = loadPage(reset = true)

    fun setQuery(value: String) = _uiState.update { it.copy(query = value.take(120), errorMessage = null) }
    fun search() = loadPage(reset = true)
    fun loadMore() = loadPage(reset = false)

    fun setStatus(value: String) {
        if (value == _uiState.value.status) return
        _uiState.update { it.copy(status = value, errorMessage = null) }
        loadPage(reset = true)
    }

    fun setTab(tab: FeesTab) = _uiState.update { it.copy(tab = tab, errorMessage = null) }

    fun openPayment(invoice: FeesInvoiceDto) {
        if (!_uiState.value.canManage || invoice.balance <= 0) return
        _uiState.update {
            it.copy(
                paymentOpen = true,
                generationOpen = false,
                payment = FeesPaymentDraft(invoice = invoice),
                errorMessage = null,
                message = null,
            )
        }
    }

    fun closePayment() = _uiState.update {
        it.copy(paymentOpen = false, payment = FeesPaymentDraft(), errorMessage = null)
    }

    fun setPaymentAmount(value: String) = _uiState.update {
        val cleaned = value.filter { char -> char.isDigit() || char == '.' }.take(18)
        it.copy(payment = it.payment.copy(amount = cleaned), errorMessage = null)
    }

    fun setPaidByName(value: String) = _uiState.update {
        it.copy(payment = it.payment.copy(paidByName = value.take(150)), errorMessage = null)
    }

    fun setPaidByPhone(value: String) = _uiState.update {
        it.copy(payment = it.payment.copy(paidByPhone = value.take(30)), errorMessage = null)
    }

    fun setGateway(value: FeesGateway) = _uiState.update {
        it.copy(payment = it.payment.copy(gateway = value), errorMessage = null)
    }

    fun savePayment() {
        val state = _uiState.value
        val draft = state.payment
        val invoice = draft.invoice ?: return
        val amount = draft.amountValue ?: return
        if (!state.canManage || !draft.valid || state.isSaving) return

        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            try {
                val response = api.recordPayment(
                    invoice = invoice.id,
                    body = FeesPaymentRequestDto(
                        amount = amount,
                        paidByName = draft.paidByName.trim(),
                        paidByPhone = draft.paidByPhone.trim().ifBlank { null },
                        gateway = draft.gateway.key,
                    ),
                )
                _uiState.update {
                    it.copy(
                        isSaving = false,
                        paymentOpen = false,
                        payment = FeesPaymentDraft(),
                        message = response.message,
                        tab = FeesTab.INVOICES,
                    )
                }
                loadPage(reset = true, preserveMessage = true)
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isSaving = false, errorMessage = error.feesMessage()) }
            }
        }
    }

    fun openGeneration() {
        if (!_uiState.value.canManage) return
        _uiState.update {
            it.copy(
                generationOpen = true,
                paymentOpen = false,
                generation = FeesGenerationDraft(),
                errorMessage = null,
                message = null,
            )
        }
    }

    fun closeGeneration() = _uiState.update {
        it.copy(generationOpen = false, generation = FeesGenerationDraft(), errorMessage = null)
    }

    fun setGenerationTerm(value: Long?) = _uiState.update {
        it.copy(generation = it.generation.copy(termId = value), errorMessage = null)
    }

    fun setGenerationClass(value: Long?) = _uiState.update {
        it.copy(generation = it.generation.copy(classLevelId = value), errorMessage = null)
    }

    fun generateBills() {
        val state = _uiState.value
        val draft = state.generation
        if (!state.canManage || !draft.valid || state.isSaving) return
        val termId = draft.termId ?: return
        val classLevelId = draft.classLevelId ?: return

        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            try {
                val response = api.generate(FeesGenerateRequestDto(termId = termId, classLevelId = classLevelId))
                _uiState.update {
                    it.copy(
                        isSaving = false,
                        generationOpen = false,
                        generation = FeesGenerationDraft(),
                        message = response.message,
                        tab = FeesTab.INVOICES,
                    )
                }
                loadPage(reset = true, preserveMessage = true)
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isSaving = false, errorMessage = error.feesMessage()) }
            }
        }
    }

    private fun loadPage(reset: Boolean, preserveMessage: Boolean = false) {
        val current = _uiState.value
        if ((reset && current.isLoading) || (!reset && (current.isLoadingMore || !current.hasMore))) return
        val page = if (reset) 1 else (current.workspace?.meta?.page ?: 1) + 1

        viewModelScope.launch {
            _uiState.update {
                it.copy(
                    isLoading = reset,
                    isLoadingMore = !reset,
                    errorMessage = null,
                    message = if (preserveMessage) it.message else null,
                )
            }
            try {
                val workspace = api.index(
                    search = _uiState.value.query.trim().ifBlank { null },
                    status = _uiState.value.status,
                    page = page,
                )
                _uiState.update { state ->
                    state.copy(
                        workspace = workspace,
                        invoices = if (reset) workspace.invoices else (state.invoices + workspace.invoices).distinctBy { it.id },
                        query = workspace.selected.search,
                        status = workspace.selected.status,
                        isLoading = false,
                        isLoadingMore = false,
                    )
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update {
                    it.copy(isLoading = false, isLoadingMore = false, errorMessage = error.feesMessage())
                }
            }
        }
    }
}

private fun Throwable.feesMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "Your account is not permitted to manage school fees."
        404 -> "This invoice is no longer available."
        422 -> "Check the finance details and outstanding balance, then try again."
        else -> "The fees service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the fees service. Check your connection and try again."
}
