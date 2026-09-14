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
import online.educoreng.educore.core.data.repository.AcademicContentRepository
import online.educoreng.educore.core.model.GeneratedKnowledgeDocument
import online.educoreng.educore.core.model.KnowledgeCatalogue
import online.educoreng.educore.core.model.KnowledgeTopic

data class AcademicKnowledgeUiState(
    val catalogue: KnowledgeCatalogue? = null,
    val topic: KnowledgeTopic? = null,
    val document: GeneratedKnowledgeDocument? = null,
    val query: String = "",
    val readyOnly: Boolean = false,
    val isLoading: Boolean = false,
    val isLoadingMore: Boolean = false,
    val isSaving: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
)

@HiltViewModel
class AcademicKnowledgeViewModel @Inject constructor(
    private val repository: AcademicContentRepository,
) : ViewModel() {
    private val _uiState = MutableStateFlow(AcademicKnowledgeUiState())
    val uiState: StateFlow<AcademicKnowledgeUiState> = _uiState.asStateFlow()

    fun loadTopics() {
        if (_uiState.value.isLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, isLoadingMore = false, errorMessage = null, document = null) }
            when (val result = repository.knowledgeTopics(
                className = null,
                term = null,
                subject = null,
                query = _uiState.value.query.takeIf(String::isNotBlank),
                ready = true.takeIf { _uiState.value.readyOnly },
                page = 1,
            )) {
                is AppResult.Success -> _uiState.update { it.copy(catalogue = result.value, isLoading = false) }
                is AppResult.Failure -> _uiState.update { it.copy(isLoading = false, errorMessage = result.error.userMessage) }
            }
        }
    }

    fun loadMore() {
        val current = _uiState.value.catalogue ?: return
        if (_uiState.value.isLoading || _uiState.value.isLoadingMore || current.currentPage >= current.lastPage) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoadingMore = true, errorMessage = null) }
            when (val result = repository.knowledgeTopics(
                className = null,
                term = null,
                subject = null,
                query = _uiState.value.query.takeIf(String::isNotBlank),
                ready = true.takeIf { _uiState.value.readyOnly },
                page = current.currentPage + 1,
            )) {
                is AppResult.Success -> _uiState.update { state ->
                    state.copy(
                        catalogue = result.value.copy(
                            topics = (current.topics + result.value.topics).distinctBy { it.id },
                        ),
                        isLoadingMore = false,
                    )
                }
                is AppResult.Failure -> _uiState.update { it.copy(isLoadingMore = false, errorMessage = result.error.userMessage) }
            }
        }
    }

    fun setQuery(value: String) = _uiState.update { it.copy(query = value) }
    fun submitSearch() = loadTopics()

    fun setReadyOnly(value: Boolean) {
        _uiState.update { it.copy(readyOnly = value) }
        loadTopics()
    }

    fun openTopic(id: Long) = viewModelScope.launch {
        _uiState.update { it.copy(isLoading = true, errorMessage = null, topic = null, document = null) }
        when (val result = repository.knowledgeTopic(id)) {
            is AppResult.Success -> _uiState.update { it.copy(topic = result.value, isLoading = false) }
            is AppResult.Failure -> _uiState.update { it.copy(isLoading = false, errorMessage = result.error.userMessage) }
        }
    }

    fun generate(id: Long, type: String) = viewModelScope.launch {
        _uiState.update { it.copy(isLoading = true, errorMessage = null, document = null) }
        when (val result = repository.generateKnowledge(id, type)) {
            is AppResult.Success -> _uiState.update { it.copy(document = result.value, topic = result.value.topic, isLoading = false) }
            is AppResult.Failure -> _uiState.update { it.copy(isLoading = false, errorMessage = result.error.userMessage) }
        }
    }

    fun saveLessonPlan(id: Long) = save { repository.saveKnowledgeLessonPlan(id) }
    fun saveStudentNote(id: Long) = save { repository.saveKnowledgeStudentNote(id) }

    fun clearDocument() = _uiState.update { it.copy(document = null) }
    fun consumeMessage() = _uiState.update { it.copy(message = null) }

    private fun save(block: suspend () -> AppResult<online.educoreng.educore.core.model.KnowledgeMutationResult>) {
        if (_uiState.value.isSaving) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null) }
            when (val result = block()) {
                is AppResult.Success -> _uiState.update { it.copy(isSaving = false, message = result.value.message) }
                is AppResult.Failure -> _uiState.update { it.copy(isSaving = false, errorMessage = result.error.userMessage) }
            }
        }
    }
}
