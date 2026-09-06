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
import online.educoreng.educore.core.data.repository.OperationsRepository
import online.educoreng.educore.core.model.OperationsWorkspace

data class OperationsUiState(
    val moduleKey: String? = null,
    val workspace: OperationsWorkspace? = null,
    val selectedSection: Int = 0,
    val query: String = "",
    val isLoading: Boolean = false,
    val errorMessage: String? = null,
)

@HiltViewModel
class OperationsViewModel @Inject constructor(
    private val repository: OperationsRepository,
) : ViewModel() {
    private val _uiState = MutableStateFlow(OperationsUiState())
    val uiState: StateFlow<OperationsUiState> = _uiState.asStateFlow()

    fun load(module: String) = viewModelScope.launch {
        _uiState.update { state ->
            state.copy(
                moduleKey = module,
                workspace = state.workspace.takeIf { state.moduleKey == module },
                selectedSection = if (state.moduleKey == module) state.selectedSection else 0,
                query = if (state.moduleKey == module) state.query else "",
                isLoading = true,
                errorMessage = null,
            )
        }
        when (val result = repository.load(module)) {
            is AppResult.Success -> _uiState.update { state ->
                state.copy(
                    workspace = result.value,
                    selectedSection = state.selectedSection.coerceIn(0, result.value.sections.lastIndex.coerceAtLeast(0)),
                    isLoading = false,
                )
            }
            is AppResult.Failure -> _uiState.update { it.copy(isLoading = false, errorMessage = result.error.userMessage) }
        }
    }

    fun selectSection(index: Int) = _uiState.update { it.copy(selectedSection = index, query = "") }
    fun setQuery(query: String) = _uiState.update { it.copy(query = query) }
}
