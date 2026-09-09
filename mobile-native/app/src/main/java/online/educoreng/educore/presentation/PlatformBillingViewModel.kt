package online.educoreng.educore.presentation

import android.content.Context
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import online.educoreng.educore.core.data.repository.saveDownloadedDocument
import online.educoreng.educore.core.model.DownloadedDocument
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.PlatformBillingApi
import online.educoreng.educore.core.network.dto.PlatformInvoiceCreateRequestDto
import online.educoreng.educore.core.network.dto.PlatformInvoiceDto
import online.educoreng.educore.core.network.dto.PlatformInvoiceSettleRequestDto
import online.educoreng.educore.core.network.dto.PlatformInvoicesDto
import retrofit2.HttpException

internal data class PlatformInvoiceDraft(
    val tenantId: Long? = null,
    val billingCycle: String = "termly",
    val capacity: String = "",
    val dueDate: String = "",
    val notes: String = "",
) {
    val capacityValue: Int? get() = capacity.toIntOrNull()
    val valid: Boolean get() = tenantId != null && capacityValue != null && capacityValue!! > 0 && dueDate.matches(Regex("\\d{4}-\\d{2}-\\d{2}"))
}

internal data class PlatformSettlementDraft(
    val invoice: PlatformInvoiceDto? = null,
    val method: String = "bank_transfer",
    val reference: String = "",
) {
    val valid: Boolean get() = invoice != null && method in setOf("bank_transfer", "card", "cash", "pos", "other")
}

internal data class PlatformBillingUiState(
    val workspace: PlatformInvoicesDto? = null,
    val status: String = "all",
    val tenantId: Long? = null,
    val invoiceEditorOpen: Boolean = false,
    val invoiceDraft: PlatformInvoiceDraft = PlatformInvoiceDraft(),
    val settlementDraft: PlatformSettlementDraft = PlatformSettlementDraft(),
    val isLoading: Boolean = false,
    val isMutating: Boolean = false,
    val downloadingInvoiceId: Long? = null,
    val document: DownloadedDocument? = null,
    val errorMessage: String? = null,
    val message: String? = null,
)

@HiltViewModel
internal class PlatformBillingViewModel @Inject constructor(
    factory: ApiClientFactory,
    @ApplicationContext private val context: Context,
) : ViewModel() {
    private val api = factory.create(PlatformBillingApi::class.java)
    private val _uiState = MutableStateFlow(PlatformBillingUiState())
    val uiState: StateFlow<PlatformBillingUiState> = _uiState.asStateFlow()
    private var loadJob: Job? = null

    fun load() {
        loadJob?.cancel()
        loadJob = viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            try {
                val state = _uiState.value
                _uiState.update { it.copy(workspace = api.invoices(state.status, state.tenantId), isLoading = false) }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isLoading = false, errorMessage = error.platformBillingMessage()) }
            }
        }
    }

    fun setStatus(value: String) {
        if (value !in setOf("all", "pending", "paid", "overdue", "cancelled")) return
        _uiState.update { it.copy(status = value) }
        load()
    }

    fun setTenant(id: Long?) {
        _uiState.update { it.copy(tenantId = id) }
        load()
    }

    fun openInvoiceEditor() = _uiState.update {
        if (it.isMutating) it else it.copy(invoiceEditorOpen = true, invoiceDraft = PlatformInvoiceDraft(), errorMessage = null, message = null)
    }
    fun closeInvoiceEditor() = _uiState.update { it.copy(invoiceEditorOpen = false, invoiceDraft = PlatformInvoiceDraft()) }
    fun setInvoiceTenant(id: Long?) = _uiState.update { it.copy(invoiceDraft = it.invoiceDraft.copy(tenantId = id)) }
    fun setCycle(value: String) { if (value in setOf("termly", "annual")) _uiState.update { it.copy(invoiceDraft = it.invoiceDraft.copy(billingCycle = value)) } }
    fun setCapacity(value: String) = _uiState.update { it.copy(invoiceDraft = it.invoiceDraft.copy(capacity = value.filter(Char::isDigit).take(7))) }
    fun setDueDate(value: String) = _uiState.update { it.copy(invoiceDraft = it.invoiceDraft.copy(dueDate = value.take(10))) }
    fun setNotes(value: String) = _uiState.update { it.copy(invoiceDraft = it.invoiceDraft.copy(notes = value.take(1000))) }

    fun createInvoice() {
        val state = _uiState.value
        val draft = state.invoiceDraft
        if (!draft.valid || state.isMutating) return
        mutate {
            api.createInvoice(
                PlatformInvoiceCreateRequestDto(
                    tenantId = requireNotNull(draft.tenantId),
                    billingCycle = draft.billingCycle,
                    capacity = requireNotNull(draft.capacityValue),
                    dueDate = draft.dueDate,
                    notes = draft.notes.trim().takeIf(String::isNotBlank),
                )
            ).message
        }
    }

    fun downloadInvoice(invoice: PlatformInvoiceDto) {
        val state = _uiState.value
        if (state.downloadingInvoiceId != null || state.isMutating) return
        viewModelScope.launch {
            _uiState.update { it.copy(downloadingInvoiceId = invoice.id, document = null, errorMessage = null, message = null) }
            try {
                val body = api.invoicePdf(invoice.id)
                val document = withContext(Dispatchers.IO) {
                    saveDownloadedDocument(
                        context = context,
                        body = body,
                        requestedName = "EduCore-Invoice-${safeInvoicePart(invoice.invoiceNumber)}.pdf",
                        requestedMimeType = "application/pdf",
                    )
                }
                _uiState.update {
                    it.copy(
                        downloadingInvoiceId = null,
                        document = document,
                        message = "${invoice.invoiceNumber} downloaded successfully.",
                    )
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(downloadingInvoiceId = null, errorMessage = error.platformBillingMessage()) }
            }
        }
    }

    fun consumeDocument() = _uiState.update { it.copy(document = null) }

    fun requestSettlement(invoice: PlatformInvoiceDto) {
        if (_uiState.value.isMutating || invoice.status == "paid") return
        _uiState.update { it.copy(settlementDraft = PlatformSettlementDraft(invoice = invoice), errorMessage = null, message = null) }
    }
    fun cancelSettlement() = _uiState.update { it.copy(settlementDraft = PlatformSettlementDraft()) }
    fun setSettlementMethod(value: String) {
        if (value in setOf("bank_transfer", "card", "cash", "pos", "other")) _uiState.update { it.copy(settlementDraft = it.settlementDraft.copy(method = value)) }
    }
    fun setSettlementReference(value: String) = _uiState.update { it.copy(settlementDraft = it.settlementDraft.copy(reference = value.take(100))) }

    fun confirmSettlement() {
        val state = _uiState.value
        val draft = state.settlementDraft
        val invoice = draft.invoice ?: return
        if (!draft.valid || state.isMutating) return
        mutate {
            api.settleInvoice(
                invoice.id,
                PlatformInvoiceSettleRequestDto(draft.method, draft.reference.trim().takeIf(String::isNotBlank)),
            ).message
        }
    }

    private fun mutate(action: suspend () -> String) {
        loadJob?.cancel()
        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null, message = null) }
            try {
                val message = action()
                val state = _uiState.value
                val workspace = api.invoices(state.status, state.tenantId)
                _uiState.update {
                    it.copy(
                        workspace = workspace,
                        invoiceEditorOpen = false,
                        invoiceDraft = PlatformInvoiceDraft(),
                        settlementDraft = PlatformSettlementDraft(),
                        isMutating = false,
                        message = message,
                    )
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isMutating = false, errorMessage = error.platformBillingMessage()) }
            }
        }
    }

    private fun safeInvoicePart(value: String): String = value.replace(Regex("[^A-Za-z0-9_-]+"), "-").trim('-').take(60)
}

private fun Throwable.platformBillingMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your platform session has expired. Sign in again."
        403 -> "Platform Super Admin access is required."
        404 -> "The selected invoice or school is no longer available."
        422 -> "The billing change was rejected. Check the enrollment capacity, payment reference and invoice state."
        else -> "The billing service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank) ?: "Unable to reach the platform billing service."
}
