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
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.data.repository.PortalAttendanceRepository
import online.educoreng.educore.core.model.PortalAttendanceWorkspace

data class PortalAttendanceUiState(
    val workspace: PortalAttendanceWorkspace? = null,
    val isLoading: Boolean = false,
    val errorMessage: String? = null,
)

@HiltViewModel
class PortalAttendanceViewModel @Inject constructor(
    private val repository: PortalAttendanceRepository,
) : ViewModel() {
    private val _uiState = MutableStateFlow(PortalAttendanceUiState())
    val uiState: StateFlow<PortalAttendanceUiState> = _uiState.asStateFlow()

    fun load(
        childId: Long? = _uiState.value.workspace?.student?.id,
        termId: Long? = _uiState.value.workspace?.selectedTermId,
    ) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            when (val result = repository.load(childId, termId)) {
                is AppResult.Success -> _uiState.update {
                    it.copy(workspace = result.value, isLoading = false)
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isLoading = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun selectChild(childId: Long) {
        load(childId = childId, termId = _uiState.value.workspace?.selectedTermId)
    }

    fun selectTerm(termId: Long) {
        load(childId = _uiState.value.workspace?.student?.id, termId = termId)
    }
}
