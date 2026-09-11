package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.data.repository.ScoreWorkspaceRepository
import online.educoreng.educore.core.model.PublishedResults
import online.educoreng.educore.core.model.ScoreAssignments
import online.educoreng.educore.core.model.ScoreSheet
import online.educoreng.educore.core.model.SyncState
import online.educoreng.educore.sync.OfflineSyncCoordinator

data class ScoresUiState(
    val assignments: ScoreAssignments? = null,
    val sheet: ScoreSheet? = null,
    val publishedResults: PublishedResults? = null,
    val search: String = "",
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
)

@HiltViewModel
class ScoresViewModel @Inject constructor(
    private val repository: ScoreWorkspaceRepository,
    private val syncCoordinator: OfflineSyncCoordinator,
) : ViewModel() {
    private val _uiState = MutableStateFlow(ScoresUiState())
    val uiState: StateFlow<ScoresUiState> = _uiState.asStateFlow()
    private var draftJob: Job? = null

    fun loadAssignments() = viewModelScope.launch {
        _uiState.update { it.copy(isLoading = true, errorMessage = null, sheet = null) }
        when (val result = repository.loadAssignments()) {
            is AppResult.Success -> _uiState.update { it.copy(assignments = result.value, isLoading = false) }
            is AppResult.Failure -> _uiState.update { it.copy(isLoading = false, errorMessage = result.error.userMessage) }
        }
    }

    fun openSheet(classId: Long, subjectId: Long, termId: Long?) = viewModelScope.launch {
        _uiState.update { it.copy(isLoading = true, errorMessage = null, sheet = null) }
        when (val result = repository.loadSheet(classId, subjectId, termId)) {
            is AppResult.Success -> _uiState.update { it.copy(sheet = result.value, isLoading = false) }
            is AppResult.Failure -> _uiState.update { it.copy(isLoading = false, errorMessage = result.error.userMessage) }
        }
    }

    fun setSearch(value: String) = _uiState.update { it.copy(search = value) }

    fun updateScore(studentId: Long, assessmentId: Long, text: String) {
        val sheet = _uiState.value.sheet ?: return
        val value = text.trim().takeIf(String::isNotEmpty)?.toDoubleOrNull() ?: if (text.isBlank()) null else return
        draftJob?.cancel()
        draftJob = viewModelScope.launch {
            when (val result = repository.saveDraft(sheet, studentId, assessmentId, value)) {
                is AppResult.Success -> _uiState.update { it.copy(sheet = result.value, errorMessage = null) }
                is AppResult.Failure -> _uiState.update { it.copy(errorMessage = result.error.userMessage) }
            }
        }
    }

    fun submit() {
        val sheet = _uiState.value.sheet ?: return
        if (_uiState.value.isSaving) return
        viewModelScope.launch {
            draftJob?.join()
            _uiState.update { it.copy(isSaving = true, errorMessage = null) }
            when (val result = repository.submit(_uiState.value.sheet ?: sheet)) {
                is AppResult.Success -> _uiState.update {
                    if (result.value.syncState == SyncState.QUEUED) syncCoordinator.schedule()
                    it.copy(sheet = result.value, isSaving = false, message = if (result.value.syncState == SyncState.QUEUED) "Scores queued and will sync automatically." else "Scores saved successfully.")
                }
                is AppResult.Failure -> _uiState.update { it.copy(isSaving = false, errorMessage = result.error.userMessage) }
            }
        }
    }

    fun discardDraft() {
        val sheet = _uiState.value.sheet ?: return
        viewModelScope.launch {
            repository.discardDraft(sheet)
            openSheet(sheet.classId, sheet.subjectId, sheet.termId)
        }
    }

    fun loadResults(childId: Long? = null) = viewModelScope.launch {
        _uiState.update { it.copy(isLoading = true, errorMessage = null, publishedResults = null) }
        when (val result = repository.loadPublishedResults(childId)) {
            is AppResult.Success -> _uiState.update { it.copy(publishedResults = result.value, isLoading = false) }
            is AppResult.Failure -> _uiState.update { it.copy(isLoading = false, errorMessage = result.error.userMessage) }
        }
    }

    /**
     * Compatibility entry point for legacy, unreachable report-card routes.
     * Student results/report cards are intentionally unavailable in the mobile app.
     */
    fun loadStudentResults(classId: Long, studentId: Long) = viewModelScope.launch {
        _uiState.update {
            it.copy(
                isLoading = false,
                publishedResults = null,
                errorMessage = "Results and report cards are not available in the mobile app.",
            )
        }
    }

    fun consumeMessage() = _uiState.update { it.copy(message = null) }
}
