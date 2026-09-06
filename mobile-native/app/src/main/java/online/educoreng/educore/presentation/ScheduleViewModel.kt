package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.data.repository.ScheduleRepository
import online.educoreng.educore.core.model.ScheduleWorkspace

data class ScheduleUiState(
    val workspace: ScheduleWorkspace? = null,
    val selectedSection: Int = 0,
    val selectedDay: String = SimpleDateFormat("EEEE", Locale.US).format(Date()),
    val isLoading: Boolean = false,
    val errorMessage: String? = null,
)

@HiltViewModel
class ScheduleViewModel @Inject constructor(
    private val repository: ScheduleRepository,
) : ViewModel() {
    private val _uiState = MutableStateFlow(ScheduleUiState())
    val uiState: StateFlow<ScheduleUiState> = _uiState.asStateFlow()

    fun load(classId: Long? = null, childId: Long? = null) = viewModelScope.launch {
        _uiState.update { it.copy(isLoading = true, errorMessage = null, workspace = null) }
        when (val result = repository.load(classId = classId, childId = childId)) {
            is AppResult.Success -> _uiState.update { state ->
                val day = result.value.week.firstOrNull { it.day == state.selectedDay }?.day
                    ?: result.value.week.firstOrNull()?.day.orEmpty()
                state.copy(workspace = result.value, selectedDay = day, isLoading = false)
            }
            is AppResult.Failure -> _uiState.update { it.copy(isLoading = false, errorMessage = result.error.userMessage) }
        }
    }

    fun selectSection(index: Int) = _uiState.update { it.copy(selectedSection = index) }
    fun selectDay(day: String) = _uiState.update { it.copy(selectedDay = day) }
}
