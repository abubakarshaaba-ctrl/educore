package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.PortalAttendanceApi
import online.educoreng.educore.core.network.dto.PortalAttendanceResponseDto
import retrofit2.HttpException

internal data class PortalAttendanceUiState(
    val workspace: PortalAttendanceResponseDto? = null,
    val portal: String = "student",
    val selectedChildId: Long? = null,
    val selectedTermId: Long? = null,
    val isLoading: Boolean = false,
    val errorMessage: String? = null,
) {
    val selectedChildName: String get() = workspace?.children
        ?.firstOrNull { it.id == selectedChildId }?.name ?: "Select child"

    val selectedTermName: String get() = workspace?.terms
        ?.firstOrNull { it.id == selectedTermId }
        ?.let { listOfNotNull(it.name, it.session).joinToString(" · ") }
        ?: "Select term"
}

@HiltViewModel
internal class PortalAttendanceViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api: PortalAttendanceApi = factory.create(PortalAttendanceApi::class.java)
    private val _uiState = MutableStateFlow(PortalAttendanceUiState())
    val uiState: StateFlow<PortalAttendanceUiState> = _uiState.asStateFlow()
    private var loadJob: Job? = null

    fun load(portal: String) {
        val normalized = if (portal == "parent") "parent" else "student"
        loadJob?.cancel()
        _uiState.update {
            it.copy(
                portal = normalized,
                selectedChildId = if (normalized == "parent") it.selectedChildId else null,
                isLoading = true,
                errorMessage = null,
            )
        }
        loadJob = viewModelScope.launch {
            request(
                portal = normalized,
                childId = _uiState.value.selectedChildId,
                termId = _uiState.value.selectedTermId,
                allowServerDefaults = true,
            )
        }
    }

    fun selectChild(childId: Long?) {
        if (_uiState.value.portal != "parent" || childId == _uiState.value.selectedChildId) return
        _uiState.update { it.copy(selectedChildId = childId, errorMessage = null) }
        reloadSelection()
    }

    fun selectTerm(termId: Long?) {
        if (termId == _uiState.value.selectedTermId) return
        _uiState.update { it.copy(selectedTermId = termId, errorMessage = null) }
        reloadSelection()
    }

    fun retry() = load(_uiState.value.portal)

    private fun reloadSelection() {
        val state = _uiState.value
        loadJob?.cancel()
        loadJob = viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            request(
                portal = state.portal,
                childId = state.selectedChildId,
                termId = state.selectedTermId,
                allowServerDefaults = false,
            )
        }
    }

    private suspend fun request(
        portal: String,
        childId: Long?,
        termId: Long?,
        allowServerDefaults: Boolean,
    ) {
        try {
            val workspace = if (portal == "parent") {
                api.parentAttendance(childId = childId, termId = termId)
            } else {
                api.studentAttendance(termId = termId)
            }
            _uiState.update { state ->
                state.copy(
                    workspace = workspace,
                    portal = workspace.portal,
                    selectedChildId = if (workspace.portal == "parent") {
                        workspace.student?.id ?: if (allowServerDefaults) state.selectedChildId else childId
                    } else null,
                    selectedTermId = workspace.selectedTermId,
                    isLoading = false,
                    errorMessage = null,
                )
            }
        } catch (cancelled: CancellationException) {
            throw cancelled
        } catch (error: Throwable) {
            _uiState.update { it.copy(isLoading = false, errorMessage = error.portalAttendanceMessage()) }
        }
    }
}

private fun Throwable.portalAttendanceMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "This attendance record is not available to your account."
        404 -> "The attendance record is no longer available."
        422 -> "The selected academic term is not available for this school."
        else -> "The Attendance service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach Attendance. Check your connection and try again."
}
