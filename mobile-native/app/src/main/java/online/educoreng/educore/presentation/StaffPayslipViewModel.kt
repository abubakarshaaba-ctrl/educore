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
import online.educoreng.educore.core.data.repository.StaffPayslipRepository
import online.educoreng.educore.core.model.DownloadedDocument
import online.educoreng.educore.core.network.dto.StaffPayslipDetailDto
import online.educoreng.educore.core.network.dto.StaffPayslipSummaryDto

data class StaffPayslipUiState(
    val items: List<StaffPayslipSummaryDto> = emptyList(),
    val selected: StaffPayslipDetailDto? = null,
    val selectedSummary: StaffPayslipSummaryDto? = null,
    val isLoading: Boolean = false,
    val isDownloading: Boolean = false,
    val errorMessage: String? = null,
    val document: DownloadedDocument? = null,
)

@HiltViewModel
class StaffPayslipViewModel @Inject constructor(
    private val repository: StaffPayslipRepository,
) : ViewModel() {
    private val _uiState = MutableStateFlow(StaffPayslipUiState())
    val uiState: StateFlow<StaffPayslipUiState> = _uiState.asStateFlow()

    fun load() {
        if (_uiState.value.isLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null, selected = null, selectedSummary = null) }
            when (val result = repository.list()) {
                is AppResult.Success -> _uiState.update { it.copy(items = result.value, isLoading = false) }
                is AppResult.Failure -> _uiState.update { it.copy(isLoading = false, errorMessage = result.error.userMessage) }
            }
        }
    }

    fun open(item: StaffPayslipSummaryDto) {
        if (_uiState.value.isLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null, selectedSummary = item, selected = null) }
            when (val result = repository.detail(item.id)) {
                is AppResult.Success -> _uiState.update { it.copy(selected = result.value, isLoading = false) }
                is AppResult.Failure -> _uiState.update { it.copy(isLoading = false, errorMessage = result.error.userMessage) }
            }
        }
    }

    fun closeDetail() = _uiState.update { it.copy(selected = null, selectedSummary = null, errorMessage = null) }

    fun download() {
        val summary = _uiState.value.selectedSummary ?: return
        if (_uiState.value.isDownloading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isDownloading = true, errorMessage = null) }
            when (val result = repository.downloadPdf(summary.id, summary.periodTitle)) {
                is AppResult.Success -> _uiState.update { it.copy(isDownloading = false, document = result.value) }
                is AppResult.Failure -> _uiState.update { it.copy(isDownloading = false, errorMessage = result.error.userMessage) }
            }
        }
    }

    fun consumeDocument() = _uiState.update { it.copy(document = null) }
}
