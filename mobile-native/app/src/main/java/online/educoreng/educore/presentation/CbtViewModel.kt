package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update

data class CbtUiState(
    val message: String? = null,
    val errorMessage: String? = CBT_REMOVED_MESSAGE,
)

/**
 * Compatibility-only shell dependency.
 *
 * The Android CBT data model, repository and transport contracts are removed.
 * This type remains temporarily because an older parent navigation graph still
 * references it at compile time; every action is a fail-closed no-op.
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
        _uiState.update { it.copy(errorMessage = CBT_REMOVED_MESSAGE) }
    }
}

private const val CBT_REMOVED_MESSAGE = "CBT is not available in the EduCore Android app."
