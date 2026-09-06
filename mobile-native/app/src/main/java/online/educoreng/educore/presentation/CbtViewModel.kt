package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import java.util.UUID
import javax.inject.Inject
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.common.AppError
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.data.repository.CbtRepository
import online.educoreng.educore.core.model.CbtAttempt
import online.educoreng.educore.core.model.CbtPreflight
import online.educoreng.educore.core.model.CbtQuestion

data class CbtQuestionUnit(
    val id: Long,
    val label: String,
    val questions: List<CbtQuestion>,
)

data class CbtUiState(
    val exams: List<CbtPreflight> = emptyList(),
    val preflight: CbtPreflight? = null,
    val attempt: CbtAttempt? = null,
    val selectedSection: Int = 0,
    val selectedQuestion: Int = 0,
    val answers: Map<Long, String> = emptyMap(),
    val flagged: Set<Long> = emptySet(),
    val remainingSeconds: Int = 0,
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val isSubmitting: Boolean = false,
    val message: String? = null,
    val errorMessage: String? = null,
    val questionImages: Map<Long, ByteArray> = emptyMap(),
) {
    val activeSection get() = attempt?.sections?.getOrNull(selectedSection)
    val units get() = activeSection?.let(::questionUnits).orEmpty()
    val activeUnit get() = units.getOrNull(selectedQuestion)
    val isFinal get() = attempt?.session?.status?.let { it != "in_progress" } == true
}

@HiltViewModel
class CbtViewModel @Inject constructor(
    private val repository: CbtRepository,
) : ViewModel() {
    private val _uiState = MutableStateFlow(CbtUiState())
    val uiState: StateFlow<CbtUiState> = _uiState.asStateFlow()
    private var saveJob: Job? = null
    private var timerJob: Job? = null
    private val imageJobs = mutableMapOf<Long, Job>()

    fun loadExams() = viewModelScope.launch {
        _uiState.update { CbtUiState(isLoading = true) }
        when (val result = repository.exams()) {
            is AppResult.Success -> _uiState.update { it.copy(exams = result.value, isLoading = false) }
            is AppResult.Failure -> _uiState.update { it.copy(isLoading = false, errorMessage = result.error.userMessage) }
        }
    }

    fun openExam(examId: Long) = viewModelScope.launch {
        _uiState.update {
            it.copy(
                preflight = null,
                attempt = null,
                answers = emptyMap(),
                flagged = emptySet(),
                questionImages = emptyMap(),
                isLoading = true,
                errorMessage = null,
            )
        }
        when (val result = repository.preflight(examId)) {
            is AppResult.Success -> _uiState.update { it.copy(preflight = result.value, isLoading = false) }
            is AppResult.Failure -> _uiState.update { it.copy(isLoading = false, errorMessage = result.error.userMessage) }
        }
    }

    fun begin() {
        val exam = _uiState.value.preflight?.exam ?: return
        loadAttempt { repository.begin(exam.id, uuid()) }
    }

    fun resume() {
        val sessionId = _uiState.value.preflight?.activeSessionId ?: return
        loadAttempt { repository.attempt(sessionId) }
    }

    fun refreshAttempt() {
        val state = _uiState.value
        val sessionId = state.attempt?.session?.id ?: state.preflight?.activeSessionId
        if (sessionId != null) loadAttempt { repository.attempt(sessionId) } else begin()
    }

    fun selectSection(index: Int) {
        _uiState.update {
            it.copy(selectedSection = index.coerceIn(0, (it.attempt?.sections?.lastIndex ?: 0).coerceAtLeast(0)), selectedQuestion = 0)
        }
        loadActiveImage()
    }

    fun selectQuestion(index: Int) {
        _uiState.update {
            it.copy(selectedQuestion = index.coerceIn(0, (it.units.lastIndex).coerceAtLeast(0)))
        }
        loadActiveImage()
    }

    fun previous() = selectQuestion(_uiState.value.selectedQuestion - 1)
    fun next() = selectQuestion(_uiState.value.selectedQuestion + 1)

    fun answer(questionId: Long, option: String) {
        _uiState.update { it.copy(answers = it.answers + (questionId to option.lowercase())) }
        scheduleSave()
    }

    fun toggleFlag(questionId: Long) {
        _uiState.update {
            val flagged = if (questionId in it.flagged) it.flagged - questionId else it.flagged + questionId
            it.copy(flagged = flagged)
        }
        scheduleSave()
    }

    fun submit() = viewModelScope.launch {
        val state = _uiState.value
        val session = state.attempt?.session ?: return@launch
        if (state.isSubmitting || session.status != "in_progress") return@launch
        saveJob?.cancel()
        _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
        when (val result = repository.submit(session.id, uuid(), state.answers)) {
            is AppResult.Success -> applyAttempt(result.value, message = "Examination submitted successfully.")
            is AppResult.Failure -> _uiState.update { it.copy(isSubmitting = false, errorMessage = result.error.userMessage) }
        }
    }

    fun recordFocusLoss() = viewModelScope.launch {
        val state = _uiState.value
        val session = state.attempt?.session ?: return@launch
        if (session.status != "in_progress") return@launch
        when (val result = repository.integrity(session.id, uuid(), "focus_lost", state.answers)) {
            is AppResult.Success -> {
                if (result.value.submitted) refreshAttempt()
                else _uiState.update { it.copy(message = "Focus-loss event recorded.") }
            }
            is AppResult.Failure -> _uiState.update { it.copy(errorMessage = result.error.userMessage) }
        }
    }

    fun consumeMessage() = _uiState.update { it.copy(message = null) }

    private fun loadAttempt(block: suspend () -> AppResult<CbtAttempt>) = viewModelScope.launch {
        _uiState.update { it.copy(isLoading = true, errorMessage = null) }
        when (val result = block()) {
            is AppResult.Success -> applyAttempt(result.value)
            is AppResult.Failure -> _uiState.update { it.copy(isLoading = false, errorMessage = result.error.userMessage) }
        }
    }

    private fun applyAttempt(attempt: CbtAttempt, message: String? = null) {
        val current = _uiState.value
        _uiState.update {
            it.copy(
                attempt = attempt,
                answers = attempt.session.answers,
                flagged = attempt.session.flaggedQuestions,
                remainingSeconds = attempt.session.remainingSeconds,
                selectedSection = current.selectedSection.coerceIn(0, attempt.sections.lastIndex.coerceAtLeast(0)),
                selectedQuestion = 0,
                isLoading = false,
                isSaving = false,
                isSubmitting = false,
                message = message,
                errorMessage = null,
            )
        }
        startTimer()
        loadActiveImage()
    }

    private fun scheduleSave() {
        saveJob?.cancel()
        saveJob = viewModelScope.launch {
            delay(350)
            val state = _uiState.value
            val session = state.attempt?.session ?: return@launch
            if (session.status != "in_progress") return@launch
            _uiState.update { it.copy(isSaving = true) }
            when (val result = repository.save(session.id, uuid(), session.version, state.answers, state.flagged)) {
                is AppResult.Success -> applyAttemptKeepingPosition(result.value)
                is AppResult.Failure -> {
                    _uiState.update { it.copy(isSaving = false, errorMessage = result.error.userMessage) }
                    if (result.error is AppError.Conflict) refreshAttempt()
                }
            }
        }
    }

    private fun applyAttemptKeepingPosition(attempt: CbtAttempt) {
        _uiState.update {
            it.copy(
                attempt = attempt,
                answers = attempt.session.answers,
                flagged = attempt.session.flaggedQuestions,
                remainingSeconds = attempt.session.remainingSeconds,
                isSaving = false,
                errorMessage = null,
            )
        }
    }

    private fun startTimer() {
        timerJob?.cancel()
        if (_uiState.value.attempt?.session?.status != "in_progress") return
        timerJob = viewModelScope.launch {
            while (_uiState.value.remainingSeconds > 0) {
                delay(1_000)
                _uiState.update { it.copy(remainingSeconds = (it.remainingSeconds - 1).coerceAtLeast(0)) }
            }
            if (_uiState.value.attempt?.session?.status == "in_progress") submit()
        }
    }

    private fun loadActiveImage() {
        val state = _uiState.value
        val sessionId = state.attempt?.session?.id ?: return
        val activeQuestions = state.activeUnit?.questions.orEmpty().filter { it.hasImage }
        val activeIds = activeQuestions.mapTo(mutableSetOf()) { it.id }
        imageJobs.filterKeys { it !in activeIds }.values.forEach { it.cancel() }
        imageJobs.keys.retainAll(activeIds)
        _uiState.update { current -> current.copy(questionImages = current.questionImages.filterKeys { it in activeIds }) }

        activeQuestions.filter { it.id !in state.questionImages && it.id !in imageJobs }.forEach { question ->
            imageJobs[question.id] = viewModelScope.launch {
                when (val result = repository.questionImage(sessionId, question.id)) {
                    is AppResult.Success -> _uiState.update { current ->
                        if (current.activeUnit?.questions?.any { it.id == question.id } == true) {
                            current.copy(questionImages = current.questionImages + (question.id to result.value))
                        } else {
                            current
                        }
                    }
                    is AppResult.Failure -> Unit
                }
                imageJobs.remove(question.id)
            }
        }
    }

    private fun uuid() = UUID.randomUUID().toString()
}

internal fun questionUnits(section: online.educoreng.educore.core.model.CbtSection): List<CbtQuestionUnit> {
    if (section.answerMode == "online") {
        return section.questions.filter { !it.instructionOnly }.map { CbtQuestionUnit(it.id, it.displayPath, listOf(it)) }
    }
    val byParent = section.questions.groupBy { it.parentId }
    fun descendants(question: CbtQuestion): List<CbtQuestion> = listOf(question) + byParent[question.id].orEmpty().flatMap(::descendants)
    return section.questions.filter { it.parentId == null }.map { CbtQuestionUnit(it.id, it.displayPath, descendants(it)) }
}
