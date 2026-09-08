package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import java.time.LocalDate
import java.time.format.TextStyle
import java.util.Locale
import javax.inject.Inject
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.PayrollApi
import online.educoreng.educore.core.network.dto.PayrollDetailDto
import online.educoreng.educore.core.network.dto.PayrollGenerateRequestDto
import online.educoreng.educore.core.network.dto.PayrollPeriodDto
import online.educoreng.educore.core.network.dto.PayrollWorkspaceDto
import retrofit2.HttpException

internal data class PayrollUiState(
    val workspace: PayrollWorkspaceDto? = null,
    val periods: List<PayrollPeriodDto> = emptyList(),
    val detail: PayrollDetailDto? = null,
    val query: String = "",
    val status: String = "all",
    val generationOpen: Boolean = false,
    val generationTitle: String = "",
    val generationStart: String = "",
    val generationEnd: String = "",
    val isLoading: Boolean = false,
    val isLoadingMore: Boolean = false,
    val isLoadingDetail: Boolean = false,
    val isSaving: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val canManage: Boolean get() = workspace?.capabilities?.manage == true
    val hasMore: Boolean get() = workspace?.meta?.hasMore == true
}

@HiltViewModel
internal class PayrollViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api: PayrollApi = factory.create(PayrollApi::class.java)
    private val _uiState = MutableStateFlow(PayrollUiState())
    val uiState: StateFlow<PayrollUiState> = _uiState.asStateFlow()

    fun load() = loadPage(reset = true)
    fun setQuery(value: String) = _uiState.update { it.copy(query = value.take(120), errorMessage = null) }
    fun search() = loadPage(reset = true)
    fun loadMore() = loadPage(reset = false)

    fun setStatus(value: String) {
        if (value == _uiState.value.status) return
        _uiState.update { it.copy(status = value, errorMessage = null) }
        loadPage(reset = true)
    }

    fun openGeneration() {
        val today = LocalDate.now()
        val monthName = today.month.getDisplayName(TextStyle.FULL, Locale.getDefault())
        _uiState.update {
            it.copy(
                generationOpen = true,
                generationTitle = "$monthName ${today.year} Payroll",
                generationStart = today.withDayOfMonth(1).toString(),
                generationEnd = today.withDayOfMonth(today.lengthOfMonth()).toString(),
                errorMessage = null,
                message = null,
            )
        }
    }

    fun closeGeneration() = _uiState.update {
        it.copy(generationOpen = false, errorMessage = null)
    }

    fun setGenerationTitle(value: String) = _uiState.update {
        it.copy(generationTitle = value.take(150), errorMessage = null)
    }

    fun setGenerationStart(value: String) = _uiState.update {
        it.copy(generationStart = value.take(10), errorMessage = null)
    }

    fun setGenerationEnd(value: String) = _uiState.update {
        it.copy(generationEnd = value.take(10), errorMessage = null)
    }

    fun generate() {
        val state = _uiState.value
        if (!state.canManage || state.isSaving) return
        val title = state.generationTitle.trim()
        val start = state.generationStart.trim()
        val end = state.generationEnd.trim()
        val startDate = runCatching { LocalDate.parse(start) }.getOrNull()
        val endDate = runCatching { LocalDate.parse(end) }.getOrNull()

        val validationError = when {
            title.isBlank() -> "Enter a payroll title."
            startDate == null -> "Enter the start date as YYYY-MM-DD."
            endDate == null -> "Enter the end date as YYYY-MM-DD."
            endDate.isBefore(startDate) -> "Payroll end date cannot be before the start date."
            else -> null
        }
        if (validationError != null) {
            _uiState.update { it.copy(errorMessage = validationError) }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            try {
                val response = api.generate(
                    PayrollGenerateRequestDto(
                        title = title,
                        periodStart = start,
                        periodEnd = end,
                    )
                )
                val detail = api.show(response.period.id)
                val skipped = response.skippedStaff
                val successMessage = if (skipped.isEmpty()) {
                    response.message
                } else {
                    response.message + " Skipped: " + skipped.joinToString(", ")
                }
                _uiState.update { current ->
                    current.copy(
                        generationOpen = false,
                        isSaving = false,
                        detail = detail,
                        periods = listOf(response.period) + current.periods.filterNot { it.id == response.period.id },
                        message = successMessage,
                    )
                }
                loadPage(reset = true, preserveMessage = true, preserveDetail = true)
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isSaving = false, errorMessage = error.payrollMessage()) }
            }
        }
    }

    fun open(period: PayrollPeriodDto) {
        if (_uiState.value.isLoadingDetail) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoadingDetail = true, errorMessage = null, message = null) }
            try {
                val detail = api.show(period.id)
                _uiState.update { it.copy(detail = detail, isLoadingDetail = false) }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isLoadingDetail = false, errorMessage = error.payrollMessage()) }
            }
        }
    }

    fun closeDetail() = _uiState.update { it.copy(detail = null, errorMessage = null, message = null) }

    fun approve() = mutateDetail { periodId -> api.approve(periodId) }
    fun markPaid() = mutateDetail { periodId -> api.markPaid(periodId) }

    private fun mutateDetail(action: suspend (Long) -> online.educoreng.educore.core.network.dto.PayrollMutationResponseDto) {
        val state = _uiState.value
        val period = state.detail?.period ?: return
        if (!state.canManage || state.isSaving) return

        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            try {
                val response = action(period.id)
                val refreshedDetail = api.show(period.id)
                _uiState.update { current ->
                    current.copy(
                        isSaving = false,
                        detail = refreshedDetail,
                        periods = current.periods.map { if (it.id == response.period.id) response.period else it },
                        message = response.message,
                    )
                }
                loadPage(reset = true, preserveMessage = true, preserveDetail = true)
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isSaving = false, errorMessage = error.payrollMessage()) }
            }
        }
    }

    private fun loadPage(
        reset: Boolean,
        preserveMessage: Boolean = false,
        preserveDetail: Boolean = false,
    ) {
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
                    detail = if (preserveDetail) it.detail else null,
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
                        periods = if (reset) workspace.periods else (state.periods + workspace.periods).distinctBy { it.id },
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
                    it.copy(isLoading = false, isLoadingMore = false, errorMessage = error.payrollMessage())
                }
            }
        }
    }
}

private fun Throwable.payrollMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "Your account is not permitted to manage payroll."
        404 -> "This payroll period is no longer available."
        422 -> "Check the payroll dates and status. The selected date range may already have a payroll period."
        else -> "The payroll service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the payroll service. Check your connection and try again."
}
