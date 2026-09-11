package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import online.educoreng.educore.core.network.dto.StaffCbtCapabilitiesDto
import online.educoreng.educore.core.network.dto.StaffCbtCountsDto
import online.educoreng.educore.core.network.dto.StaffCbtCreateOptionsDto
import online.educoreng.educore.core.network.dto.StaffCbtCreateRequestDto
import online.educoreng.educore.core.network.dto.StaffCbtExamDto

internal data class StaffCbtUiState(
    val capabilities: StaffCbtCapabilitiesDto = StaffCbtCapabilitiesDto(),
    val counts: StaffCbtCountsDto = StaffCbtCountsDto(),
    val exams: List<StaffCbtExamDto> = emptyList(),
    val selectedExam: StaffCbtExamDto? = null,
    val createOptions: StaffCbtCreateOptionsDto? = null,
    val createdExamId: Long? = null,
    val query: String = "",
    val status: String? = null,
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val isOptionsLoading: Boolean = false,
    val isCreating: Boolean = false,
    val errorMessage: String? = STAFF_CBT_REMOVED_MESSAGE,
    val message: String? = null,
)

/**
 * Compatibility-only staff CBT view model.
 *
 * CBT management is deliberately absent from the Android app. The legacy
 * presentation classes still compile while the final navigation-source cleanup
 * is completed, but this view model has no API client and cannot perform CBT
 * reads or mutations.
 */
@HiltViewModel
internal class StaffCbtViewModel @Inject constructor() : ViewModel() {
    private val _uiState = MutableStateFlow(StaffCbtUiState())
    val uiState: StateFlow<StaffCbtUiState> = _uiState.asStateFlow()

    fun setQuery(value: String) = _uiState.update { it.copy(query = value) }

    fun selectStatus(value: String?) {
        _uiState.update { it.copy(status = value, errorMessage = STAFF_CBT_REMOVED_MESSAGE) }
    }

    fun load() = removed()
    fun search() = removed()
    fun loadCreateOptions() = removed()
    fun createExam(request: StaffCbtCreateRequestDto) = removed()
    fun consumeCreatedExam() = _uiState.update { it.copy(createdExamId = null) }
    fun openExam(id: Long) = removed()
    fun publish() = removed()
    fun close() = removed()
    fun reschedule(start: String, end: String, duration: Int) = removed()
    fun consumeMessage() = _uiState.update { it.copy(message = null) }

    private fun removed() {
        _uiState.update {
            it.copy(
                isLoading = false,
                isSaving = false,
                isOptionsLoading = false,
                isCreating = false,
                selectedExam = null,
                createOptions = null,
                createdExamId = null,
                errorMessage = STAFF_CBT_REMOVED_MESSAGE,
            )
        }
    }
}

private const val STAFF_CBT_REMOVED_MESSAGE = "CBT management is not available in the EduCore Android app."
