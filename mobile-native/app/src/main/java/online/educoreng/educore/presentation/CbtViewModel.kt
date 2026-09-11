package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
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
    val errorMessage: String? = CBT_REMOVED_MESSAGE,
    val questionImages: Map<Long, ByteArray> = emptyMap(),
) {
    val activeSection get() = attempt?.sections?.getOrNull(selectedSection)
    val units get() = activeSection?.let(::questionUnits).orEmpty()
    val activeUnit get() = units.getOrNull(selectedQuestion)
    val isFinal get() = true
}

/**
 * Compatibility-only view model.
 *
 * CBT is intentionally removed from the Android product. The class remains
 * temporarily because an older navigation graph still references its type;
 * none of these methods performs network/database work or starts an exam.
 */
@HiltViewModel
class CbtViewModel @Inject constructor() : ViewModel() {
    private val _uiState = MutableStateFlow(CbtUiState())
    val uiState: StateFlow<CbtUiState> = _uiState.asStateFlow()

    fun loadExams() = removed()
    fun openExam(examId: Long) = removed()
    fun begin() = removed()
    fun resume() = removed()
    fun refreshAttempt() = removed()
    fun selectSection(index: Int) = removed()
    fun selectQuestion(index: Int) = removed()
    fun previous() = removed()
    fun next() = removed()
    fun answer(questionId: Long, option: String) = removed()
    fun toggleFlag(questionId: Long) = removed()
    fun submit() = removed()
    fun recordFocusLoss() = removed()

    fun consumeMessage() = _uiState.update { it.copy(message = null) }

    private fun removed() {
        _uiState.update {
            CbtUiState(errorMessage = CBT_REMOVED_MESSAGE)
        }
    }
}

private const val CBT_REMOVED_MESSAGE = "CBT is not available in the EduCore Android app."

internal fun questionUnits(section: online.educoreng.educore.core.model.CbtSection): List<CbtQuestionUnit> {
    if (section.answerMode == "online") {
        return section.questions.filter { !it.instructionOnly }.map { CbtQuestionUnit(it.id, it.displayPath, listOf(it)) }
    }
    val byParent = section.questions.groupBy { it.parentId }
    fun descendants(question: CbtQuestion): List<CbtQuestion> = listOf(question) + byParent[question.id].orEmpty().flatMap(::descendants)
    return section.questions.filter { it.parentId == null }.map { CbtQuestionUnit(it.id, it.displayPath, descendants(it)) }
}
