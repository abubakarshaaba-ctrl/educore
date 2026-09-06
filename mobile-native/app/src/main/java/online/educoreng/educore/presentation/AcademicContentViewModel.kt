package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.data.repository.AcademicContentRepository
import online.educoreng.educore.core.model.DownloadedDocument
import online.educoreng.educore.core.model.LessonPlan
import online.educoreng.educore.core.model.LessonPlanDraft
import online.educoreng.educore.core.model.LessonPlannerOptions
import online.educoreng.educore.core.model.RepositoryCatalogue
import online.educoreng.educore.core.model.RepositoryHierarchy
import online.educoreng.educore.core.model.RepositoryResourceDetail

data class AcademicContentUiState(
    val hierarchy: RepositoryHierarchy? = null,
    val catalogue: RepositoryCatalogue? = null,
    val resource: RepositoryResourceDetail? = null,
    val options: LessonPlannerOptions? = null,
    val lessonPlans: List<LessonPlan> = emptyList(),
    val lessonPlan: LessonPlan? = null,
    val draft: LessonPlanDraft? = null,
    val selectedClass: String? = null,
    val selectedTerm: String? = null,
    val selectedSubject: String? = null,
    val query: String = "",
    val isLoading: Boolean = false,
    val isLoadingMore: Boolean = false,
    val isSaving: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
    val downloadedDocument: DownloadedDocument? = null,
)

@HiltViewModel
class AcademicContentViewModel @Inject constructor(
    private val repository: AcademicContentRepository,
) : ViewModel() {
    private val _uiState = MutableStateFlow(AcademicContentUiState())
    val uiState: StateFlow<AcademicContentUiState> = _uiState.asStateFlow()
    private var draftJob: Job? = null
    private var repositoryJob: Job? = null

    fun loadRepository() {
        repositoryJob?.cancel()
        repositoryJob = viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, isLoadingMore = false, errorMessage = null, resource = null) }
            val hierarchy = repository.hierarchy()
            val resources = repository.resources(
                _uiState.value.selectedClass, _uiState.value.selectedTerm, _uiState.value.selectedSubject,
                _uiState.value.query.takeIf(String::isNotBlank), page = 1,
            )
            if (hierarchy is AppResult.Success && resources is AppResult.Success) {
                _uiState.update { it.copy(hierarchy = hierarchy.value, catalogue = resources.value, isLoading = false) }
            } else {
                val error = (hierarchy as? AppResult.Failure)?.error ?: (resources as? AppResult.Failure)?.error
                _uiState.update { it.copy(isLoading = false, errorMessage = error?.userMessage) }
            }
        }
    }

    fun loadMoreResources() {
        val current = _uiState.value.catalogue ?: return
        if (_uiState.value.isLoading || _uiState.value.isLoadingMore || current.currentPage >= current.lastPage) return
        repositoryJob = viewModelScope.launch {
            _uiState.update { it.copy(isLoadingMore = true, errorMessage = null) }
            when (val result = repository.resources(
                _uiState.value.selectedClass,
                _uiState.value.selectedTerm,
                _uiState.value.selectedSubject,
                _uiState.value.query.takeIf(String::isNotBlank),
                page = current.currentPage + 1,
            )) {
                is AppResult.Success -> _uiState.update { state ->
                    state.copy(
                        catalogue = result.value.copy(
                            resources = (current.resources + result.value.resources).distinctBy { it.id },
                            isFromCache = current.isFromCache || result.value.isFromCache,
                        ),
                        isLoadingMore = false,
                    )
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isLoadingMore = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun selectClass(value: String?) {
        _uiState.update { it.copy(selectedClass = value, selectedTerm = null, selectedSubject = null) }
        loadRepository()
    }

    fun selectTerm(value: String?) {
        _uiState.update { it.copy(selectedTerm = value, selectedSubject = null) }
        loadRepository()
    }

    fun selectSubject(value: String?) {
        _uiState.update { it.copy(selectedSubject = value) }
        loadRepository()
    }

    fun setQuery(value: String) = _uiState.update { it.copy(query = value) }
    fun submitSearch() = loadRepository()

    fun openResource(id: Long) = viewModelScope.launch {
        _uiState.update { it.copy(isLoading = true, errorMessage = null, resource = null) }
        when (val result = repository.resource(id)) {
            is AppResult.Success -> _uiState.update { it.copy(resource = result.value, isLoading = false) }
            is AppResult.Failure -> _uiState.update { it.copy(isLoading = false, errorMessage = result.error.userMessage) }
        }
    }

    fun downloadResource() {
        val item = _uiState.value.resource?.resource ?: return
        download { repository.downloadResource(item) }
    }

    fun loadLessons() = viewModelScope.launch {
        _uiState.update { it.copy(isLoading = true, errorMessage = null, lessonPlan = null, draft = null) }
        val options = repository.lessonOptions()
        val plans = repository.lessonPlans()
        if (options is AppResult.Success && plans is AppResult.Success) {
            _uiState.update { it.copy(options = options.value, lessonPlans = plans.value, isLoading = false) }
        } else {
            val error = (options as? AppResult.Failure)?.error ?: (plans as? AppResult.Failure)?.error
            _uiState.update { it.copy(isLoading = false, errorMessage = error?.userMessage) }
        }
    }

    fun newLesson() = viewModelScope.launch {
        _uiState.update { it.copy(isLoading = true, errorMessage = null, lessonPlan = null, draft = null) }
        val options = _uiState.value.options ?: when (val loaded = repository.lessonOptions()) {
            is AppResult.Success -> loaded.value
            is AppResult.Failure -> {
                _uiState.update { it.copy(isLoading = false, errorMessage = loaded.error.userMessage) }
                return@launch
            }
        }
        val cached = repository.localDraft("new")
        val first = options.assignments.firstOrNull()
        val initial = cached ?: LessonPlanDraft(
            classArmId = first?.classArmId,
            classLevelId = first?.classLevelId,
            subjectId = first?.subjects?.firstOrNull()?.id,
            termId = options.terms.firstOrNull { it.isCurrent }?.id ?: options.terms.firstOrNull()?.id,
        )
        _uiState.update { it.copy(options = options, draft = initial, lessonPlan = null, isLoading = false, errorMessage = null) }
    }

    fun openLesson(id: Long) = viewModelScope.launch {
        _uiState.update { it.copy(isLoading = true, errorMessage = null, lessonPlan = null, draft = null) }
        when (val result = repository.lessonPlan(id)) {
            is AppResult.Success -> {
                val cached = repository.localDraft("plan:$id")
                _uiState.update { it.copy(lessonPlan = result.value, draft = cached ?: result.value.toDraft(), isLoading = false) }
            }
            is AppResult.Failure -> _uiState.update { it.copy(isLoading = false, errorMessage = result.error.userMessage) }
        }
    }

    fun chooseClass(classArmId: Long) {
        val assignment = _uiState.value.options?.assignments?.firstOrNull { it.classArmId == classArmId } ?: return
        updateDraft { it.copy(classArmId = classArmId, classLevelId = assignment.classLevelId, subjectId = assignment.subjects.firstOrNull()?.id) }
    }

    fun chooseSubject(subjectId: Long) = updateDraft { it.copy(subjectId = subjectId) }
    fun chooseTerm(termId: Long) = updateDraft { it.copy(termId = termId) }
    fun chooseCurriculum(value: String) = updateDraft { it.copy(curriculumType = value) }
    fun chooseDelivery(value: String) = updateDraft { it.copy(deliveryType = value) }
    fun updateTopic(value: String) = updateDraft { it.copy(topic = value) }
    fun updateSubtopic(value: String) = updateDraft { it.copy(subtopic = value) }
    fun updateWeek(value: String) = updateDraft { it.copy(weekNumber = value.filter(Char::isDigit).take(2)) }
    fun updateDuration(value: String) = updateDraft { it.copy(durationMinutes = value.filter(Char::isDigit).take(3)) }
    fun updatePlanDate(value: String) = updateDraft { it.copy(planDate = value) }
    fun updateLessonNumber(value: String) = updateDraft { it.copy(lessonNumber = value) }
    fun updateLessonTime(value: String) = updateDraft { it.copy(lessonTime = value) }
    fun updateAverageAge(value: String) = updateDraft { it.copy(averageAge = value) }
    fun updateSex(value: String) = updateDraft { it.copy(sex = value) }
    fun updateSection(key: String, value: String) = updateDraft { it.copy(sections = it.sections + (key to value)) }

    fun saveLesson() {
        val draft = _uiState.value.draft ?: return
        if (draft.topic.isBlank() || draft.classArmId == null || draft.subjectId == null || draft.durationMinutes.toIntOrNull() == null) {
            _uiState.update { it.copy(errorMessage = "Select a class and subject, then enter the topic and duration.") }
            return
        }
        mutate { repository.saveLesson(draft) }
    }

    fun generateLesson() = _uiState.value.lessonPlan?.let { plan -> mutate { repository.generateLesson(plan) } }
    fun generateNote(depth: String = "standard") = _uiState.value.lessonPlan?.let { plan -> mutate { repository.generateNote(plan, depth) } }
    fun publishLesson() = _uiState.value.lessonPlan?.let { plan -> mutate { repository.publishLesson(plan) } }

    fun updateNote(text: String, status: String) {
        val plan = _uiState.value.lessonPlan ?: return
        if (text.length < 100) {
            _uiState.update { it.copy(errorMessage = "The student note must contain at least 100 characters.") }
            return
        }
        mutate { repository.updateNote(plan, text, status) }
    }

    fun downloadLesson(note: Boolean) {
        val plan = _uiState.value.lessonPlan ?: return
        download { repository.downloadLessonPdf(plan, note) }
    }

    fun consumeMessage() = _uiState.update { it.copy(message = null) }
    fun consumeDocument() = _uiState.update { it.copy(downloadedDocument = null) }

    private fun updateDraft(transform: (LessonPlanDraft) -> LessonPlanDraft) {
        val current = _uiState.value.draft ?: return
        val updated = transform(current).copy(updatedAtEpochMs = System.currentTimeMillis())
        _uiState.update { it.copy(draft = updated, errorMessage = null) }
        draftJob?.cancel()
        draftJob = viewModelScope.launch {
            delay(250)
            repository.saveLocalDraft(updated.remoteId?.let { "plan:$it" } ?: "new", updated)
        }
    }

    private fun mutate(block: suspend () -> AppResult<LessonPlan>) {
        if (_uiState.value.isSaving) return
        viewModelScope.launch {
            draftJob?.join()
            _uiState.update { it.copy(isSaving = true, errorMessage = null) }
            when (val result = block()) {
                is AppResult.Success -> {
                    repository.discardLocalDraft(result.value.id.let { "plan:$it" })
                    repository.discardLocalDraft("new")
                    _uiState.update {
                        it.copy(lessonPlan = result.value, draft = result.value.toDraft(), isSaving = false, message = "Lesson plan updated.")
                    }
                }
                is AppResult.Failure -> _uiState.update { it.copy(isSaving = false, errorMessage = result.error.userMessage) }
            }
        }
    }

    private fun download(block: suspend () -> AppResult<DownloadedDocument>) {
        if (_uiState.value.isSaving) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null) }
            when (val result = block()) {
                is AppResult.Success -> _uiState.update { it.copy(isSaving = false, downloadedDocument = result.value, message = "Document downloaded.") }
                is AppResult.Failure -> _uiState.update { it.copy(isSaving = false, errorMessage = result.error.userMessage) }
            }
        }
    }
}

private fun LessonPlan.toDraft() = LessonPlanDraft(
    remoteId = id, version = version, classArmId = classArm?.id, classLevelId = classLevel?.id, subjectId = subject?.id,
    termId = term?.id, curriculumType = curriculumType, curriculumLevelId = curriculumLevelId, deliveryType = deliveryType,
    topic = topic, subtopic = subtopic.orEmpty(), weekNumber = weekNumber?.toString().orEmpty(), lessonNumber = lessonNumber.orEmpty(),
    lessonTime = lessonTime.orEmpty(), averageAge = averageAge.orEmpty(), sex = sex ?: "Mixed", planDate = planDate.orEmpty(),
    durationMinutes = durationMinutes.toString(), status = status, sections = sections.associate { it.key to it.content.orEmpty() },
)
