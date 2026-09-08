package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.StaffCbtApi
import online.educoreng.educore.core.network.dto.StaffCbtCapabilitiesDto
import online.educoreng.educore.core.network.dto.StaffCbtCountsDto
import online.educoreng.educore.core.network.dto.StaffCbtExamDto
import online.educoreng.educore.core.network.dto.StaffCbtRescheduleRequestDto
import retrofit2.HttpException

internal data class StaffCbtUiState(
    val capabilities: StaffCbtCapabilitiesDto = StaffCbtCapabilitiesDto(),
    val counts: StaffCbtCountsDto = StaffCbtCountsDto(),
    val exams: List<StaffCbtExamDto> = emptyList(),
    val selectedExam: StaffCbtExamDto? = null,
    val query: String = "",
    val status: String? = null,
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
)

@HiltViewModel
internal class StaffCbtViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api: StaffCbtApi = factory.create(StaffCbtApi::class.java)
    private val _uiState = MutableStateFlow(StaffCbtUiState())
    val uiState: StateFlow<StaffCbtUiState> = _uiState.asStateFlow()

    fun setQuery(value: String) = _uiState.update { it.copy(query = value) }

    fun selectStatus(value: String?) {
        _uiState.update { it.copy(status = value) }
        load()
    }

    fun load() {
        if (_uiState.value.isLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            runCatching {
                api.exams(
                    status = _uiState.value.status,
                    query = _uiState.value.query.takeIf(String::isNotBlank),
                )
            }.onSuccess { response ->
                _uiState.update {
                    it.copy(
                        capabilities = response.capabilities,
                        counts = response.counts,
                        exams = response.exams,
                        isLoading = false,
                    )
                }
            }.onFailure(::failLoading)
        }
    }

    fun search() = load()

    fun openExam(id: Long) = viewModelScope.launch {
        _uiState.update { it.copy(isLoading = true, errorMessage = null, selectedExam = null) }
        runCatching { api.exam(id) }
            .onSuccess { response -> _uiState.update { it.copy(selectedExam = response.exam, isLoading = false) } }
            .onFailure(::failLoading)
    }

    fun publish() = mutate { exam -> api.publish(exam.id) }
    fun close() = mutate { exam -> api.close(exam.id) }

    fun reschedule(start: String, end: String, duration: Int) = mutate { exam ->
        api.reschedule(exam.id, StaffCbtRescheduleRequestDto(start, end, duration))
    }

    fun consumeMessage() = _uiState.update { it.copy(message = null) }

    private fun mutate(block: suspend (StaffCbtExamDto) -> online.educoreng.educore.core.network.dto.StaffCbtMutationResponseDto) {
        val exam = _uiState.value.selectedExam ?: return
        if (_uiState.value.isSaving) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null) }
            runCatching { block(exam) }
                .onSuccess { response ->
                    _uiState.update { state ->
                        state.copy(
                            selectedExam = response.exam,
                            exams = state.exams.map { if (it.id == response.exam.id) response.exam else it },
                            isSaving = false,
                            message = response.message,
                        )
                    }
                    load()
                }
                .onFailure { error -> _uiState.update { it.copy(isSaving = false, errorMessage = error.userMessage()) } }
        }
    }

    private fun failLoading(error: Throwable) {
        _uiState.update { it.copy(isLoading = false, errorMessage = error.userMessage()) }
    }
}

private fun Throwable.userMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "Your account is not permitted to manage this CBT workspace."
        404 -> "This CBT exam is no longer available."
        422 -> "The requested CBT action cannot be completed in the exam's current state."
        else -> "The CBT server returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank) ?: "Unable to reach the CBT service. Check your connection and try again."
}
