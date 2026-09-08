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
import online.educoreng.educore.core.network.ExpensesApi
import online.educoreng.educore.core.network.dto.ExpenseDto
import online.educoreng.educore.core.network.dto.ExpenseRequestDto
import online.educoreng.educore.core.network.dto.ExpensesWorkspaceDto
import retrofit2.HttpException

internal enum class ExpenseField {
    TITLE, AMOUNT, DATE, PAYMENT_METHOD, REFERENCE, DESCRIPTION,
}

internal data class ExpenseDraft(
    val id: Long? = null,
    val title: String = "",
    val category: String = "other",
    val amount: String = "",
    val expenseDate: String = "",
    val paymentMethod: String = "",
    val reference: String = "",
    val sessionId: Long? = null,
    val termId: Long? = null,
    val description: String = "",
) {
    val amountValue: Double? get() = amount.trim().toDoubleOrNull()
    val valid: Boolean
        get() = title.isNotBlank() && expenseDate.isNotBlank() && amountValue?.let { it >= 1 } == true

    fun request() = ExpenseRequestDto(
        title = title.trim(),
        category = category,
        amount = amountValue ?: 0.0,
        expenseDate = expenseDate.trim(),
        paymentMethod = paymentMethod.trim().ifBlank { null },
        reference = reference.trim().ifBlank { null },
        termId = termId,
        sessionId = sessionId,
        description = description.trim().ifBlank { null },
    )

    companion object {
        fun from(expense: ExpenseDto) = ExpenseDraft(
            id = expense.id,
            title = expense.title,
            category = expense.category,
            amount = expense.amount.toString(),
            expenseDate = expense.expenseDate,
            paymentMethod = expense.paymentMethod.orEmpty(),
            reference = expense.reference.orEmpty(),
            sessionId = expense.sessionId,
            termId = expense.termId,
            description = expense.description.orEmpty(),
        )
    }
}

internal data class ExpensesUiState(
    val workspace: ExpensesWorkspaceDto? = null,
    val expenses: List<ExpenseDto> = emptyList(),
    val query: String = "",
    val category: String = "all",
    val editorOpen: Boolean = false,
    val draft: ExpenseDraft = ExpenseDraft(),
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
internal class ExpensesViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api = factory.create(ExpensesApi::class.java)
    private val _uiState = MutableStateFlow(ExpensesUiState())
    val uiState: StateFlow<ExpensesUiState> = _uiState.asStateFlow()

    fun load() = loadPage(reset = true)
    fun setQuery(value: String) = _uiState.update { it.copy(query = value.take(120), errorMessage = null) }
    fun search() = loadPage(reset = true)
    fun loadMore() = loadPage(reset = false)

    fun setCategory(value: String) {
        if (value == _uiState.value.category) return
        _uiState.update { it.copy(category = value, errorMessage = null) }
        loadPage(reset = true)
    }

    fun create() {
        val workspace = _uiState.value.workspace
        val currentTerm = workspace?.terms?.firstOrNull { it.isCurrent }
        val currentSession = workspace?.sessions?.firstOrNull { it.isCurrent }
        _uiState.update {
            it.copy(
                editorOpen = true,
                draft = ExpenseDraft(
                    sessionId = currentTerm?.sessionId ?: currentSession?.id,
                    termId = currentTerm?.id,
                ),
                errorMessage = null,
                message = null,
            )
        }
    }

    fun edit(expense: ExpenseDto) = _uiState.update {
        it.copy(editorOpen = true, draft = ExpenseDraft.from(expense), errorMessage = null, message = null)
    }

    fun closeEditor() = _uiState.update {
        it.copy(editorOpen = false, draft = ExpenseDraft(), errorMessage = null)
    }

    fun updateField(field: ExpenseField, value: String) = _uiState.update { state ->
        val next = when (field) {
            ExpenseField.TITLE -> state.draft.copy(title = value.take(150))
            ExpenseField.AMOUNT -> state.draft.copy(amount = value.filter { it.isDigit() || it == '.' }.take(18))
            ExpenseField.DATE -> state.draft.copy(expenseDate = value.take(10))
            ExpenseField.PAYMENT_METHOD -> state.draft.copy(paymentMethod = value.take(60))
            ExpenseField.REFERENCE -> state.draft.copy(reference = value.take(120))
            ExpenseField.DESCRIPTION -> state.draft.copy(description = value.take(4000))
        }
        state.copy(draft = next, errorMessage = null)
    }

    fun setDraftCategory(value: String) = _uiState.update {
        it.copy(draft = it.draft.copy(category = value), errorMessage = null)
    }

    fun setSession(value: Long?) = _uiState.update { state ->
        val termStillMatches = state.workspace?.terms?.firstOrNull { it.id == state.draft.termId }?.sessionId == value
        state.copy(
            draft = state.draft.copy(sessionId = value, termId = state.draft.termId.takeIf { termStillMatches }),
            errorMessage = null,
        )
    }

    fun setTerm(value: Long?) = _uiState.update { state ->
        val term = state.workspace?.terms?.firstOrNull { it.id == value }
        state.copy(
            draft = state.draft.copy(termId = value, sessionId = term?.sessionId ?: state.draft.sessionId),
            errorMessage = null,
        )
    }

    fun save() {
        val state = _uiState.value
        val draft = state.draft
        if (!state.canManage || !draft.valid || state.isSaving) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            try {
                val response = if (draft.id == null) api.create(draft.request()) else api.update(draft.id, draft.request())
                _uiState.update {
                    it.copy(
                        editorOpen = false,
                        draft = ExpenseDraft(),
                        isSaving = false,
                        message = response.message,
                    )
                }
                loadPage(reset = true, preserveMessage = true)
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isSaving = false, errorMessage = error.expensesMessage()) }
            }
        }
    }

    fun delete(expenseId: Long) {
        val state = _uiState.value
        if (!state.canManage || state.isSaving) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            try {
                val response = api.delete(expenseId)
                _uiState.update { it.copy(isSaving = false, message = response.message) }
                loadPage(reset = true, preserveMessage = true)
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isSaving = false, errorMessage = error.expensesMessage()) }
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
                    category = _uiState.value.category,
                    page = page,
                )
                _uiState.update { state ->
                    state.copy(
                        workspace = workspace,
                        expenses = if (reset) workspace.expenses else (state.expenses + workspace.expenses).distinctBy { it.id },
                        query = workspace.selected.search,
                        category = workspace.selected.category,
                        isLoading = false,
                        isLoadingMore = false,
                    )
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update {
                    it.copy(isLoading = false, isLoadingMore = false, errorMessage = error.expensesMessage())
                }
            }
        }
    }
}

private fun Throwable.expensesMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "Your account is not permitted to manage expenses."
        404 -> "This expense is no longer available."
        422 -> "Check the expense amount, date, category and academic period, then try again."
        else -> "The expenses service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the expenses service. Check your connection and try again."
}
