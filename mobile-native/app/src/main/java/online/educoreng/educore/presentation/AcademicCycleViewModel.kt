package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.network.AcademicCycleApi
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.dto.AcademicCycleReadinessDto
import online.educoreng.educore.core.network.dto.AcademicCycleWorkspaceDto
import online.educoreng.educore.core.network.dto.AcademicSessionAdminDto
import online.educoreng.educore.core.network.dto.AcademicSessionCreateRequestDto
import online.educoreng.educore.core.network.dto.AcademicSessionUpdateRequestDto
import online.educoreng.educore.core.network.dto.AcademicTermAdminDto
import online.educoreng.educore.core.network.dto.AcademicTermCreateRequestDto
import online.educoreng.educore.core.network.dto.AcademicTermUpdateRequestDto
import retrofit2.HttpException

enum class AcademicCycleTab { SESSIONS, TERMS }
enum class AcademicCycleEditor { NONE, SESSION, TERM }
enum class AcademicCycleActionKind {
    ACTIVATE_SESSION, CLOSE_SESSION, DELETE_SESSION,
    ACTIVATE_TERM, CLOSE_TERM, DELETE_TERM,
}

internal data class AcademicSessionDraft(
    val id: Long? = null,
    val name: String = "",
    val activate: Boolean = false,
) {
    val valid: Boolean get() = name.isNotBlank()

    companion object {
        fun from(session: AcademicSessionAdminDto) = AcademicSessionDraft(id = session.id, name = session.name)
    }
}

internal data class AcademicTermDraft(
    val id: Long? = null,
    val sessionId: Long? = null,
    val name: String = "",
    val startDate: String = "",
    val endDate: String = "",
    val nextTermBegins: String = "",
    val activate: Boolean = false,
) {
    val valid: Boolean
        get() = sessionId != null && name.isNotBlank() && startDate.matches(ISO_DATE) && endDate.matches(ISO_DATE) &&
            (nextTermBegins.isBlank() || nextTermBegins.matches(ISO_DATE))

    companion object {
        private val ISO_DATE = Regex("\\d{4}-\\d{2}-\\d{2}")

        fun from(term: AcademicTermAdminDto) = AcademicTermDraft(
            id = term.id,
            sessionId = term.sessionId,
            name = term.name,
            startDate = term.startDate.orEmpty(),
            endDate = term.endDate.orEmpty(),
            nextTermBegins = term.nextTermBegins.orEmpty(),
        )
    }
}

private val ISO_DATE = Regex("\\d{4}-\\d{2}-\\d{2}")

internal data class AcademicCycleAction(
    val kind: AcademicCycleActionKind,
    val id: Long,
    val title: String,
    val message: String,
    val readiness: AcademicCycleReadinessDto? = null,
)

internal data class AcademicCycleUiState(
    val workspace: AcademicCycleWorkspaceDto? = null,
    val tab: AcademicCycleTab = AcademicCycleTab.SESSIONS,
    val editor: AcademicCycleEditor = AcademicCycleEditor.NONE,
    val sessionDraft: AcademicSessionDraft = AcademicSessionDraft(),
    val termDraft: AcademicTermDraft = AcademicTermDraft(),
    val pendingAction: AcademicCycleAction? = null,
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val isCheckingReadiness: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val canManage: Boolean get() = workspace?.capabilities?.manage == true
    val editingSession: Boolean get() = sessionDraft.id != null
    val editingTerm: Boolean get() = termDraft.id != null
}

@HiltViewModel
internal class AcademicCycleViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api: AcademicCycleApi = factory.create(AcademicCycleApi::class.java)
    private val _uiState = MutableStateFlow(AcademicCycleUiState())
    val uiState: StateFlow<AcademicCycleUiState> = _uiState.asStateFlow()

    fun load() = loadWorkspace()
    fun setTab(tab: AcademicCycleTab) = _uiState.update { it.copy(tab = tab, errorMessage = null, message = null) }

    fun createSession() = _uiState.update {
        it.copy(editor = AcademicCycleEditor.SESSION, sessionDraft = AcademicSessionDraft(), errorMessage = null, message = null)
    }

    fun editSession(session: AcademicSessionAdminDto) = _uiState.update {
        it.copy(editor = AcademicCycleEditor.SESSION, sessionDraft = AcademicSessionDraft.from(session), errorMessage = null, message = null)
    }

    fun setSessionName(value: String) = _uiState.update {
        it.copy(sessionDraft = it.sessionDraft.copy(name = value.take(100)), errorMessage = null)
    }

    fun setSessionActivate(value: Boolean) = _uiState.update {
        it.copy(sessionDraft = it.sessionDraft.copy(activate = value), errorMessage = null)
    }

    fun saveSession() {
        val state = _uiState.value
        val draft = state.sessionDraft
        if (!state.canManage || state.isSaving || !draft.valid) return
        mutate {
            if (draft.id == null) {
                api.createSession(AcademicSessionCreateRequestDto(draft.name.trim(), draft.activate)).message
            } else {
                api.updateSession(draft.id, AcademicSessionUpdateRequestDto(draft.name.trim())).message
            }
        }
    }

    fun createTerm() {
        val currentSessionId = _uiState.value.workspace?.current?.sessionId
        _uiState.update {
            it.copy(
                editor = AcademicCycleEditor.TERM,
                termDraft = AcademicTermDraft(sessionId = currentSessionId),
                errorMessage = null,
                message = null,
            )
        }
    }

    fun editTerm(term: AcademicTermAdminDto) = _uiState.update {
        it.copy(editor = AcademicCycleEditor.TERM, termDraft = AcademicTermDraft.from(term), errorMessage = null, message = null)
    }

    fun setTermSession(id: Long?) = _uiState.update { it.copy(termDraft = it.termDraft.copy(sessionId = id), errorMessage = null) }
    fun setTermName(value: String) = _uiState.update { it.copy(termDraft = it.termDraft.copy(name = value.take(100)), errorMessage = null) }
    fun setTermStart(value: String) = _uiState.update { it.copy(termDraft = it.termDraft.copy(startDate = value.take(10)), errorMessage = null) }
    fun setTermEnd(value: String) = _uiState.update { it.copy(termDraft = it.termDraft.copy(endDate = value.take(10)), errorMessage = null) }
    fun setNextTerm(value: String) = _uiState.update { it.copy(termDraft = it.termDraft.copy(nextTermBegins = value.take(10)), errorMessage = null) }
    fun setTermActivate(value: Boolean) = _uiState.update { it.copy(termDraft = it.termDraft.copy(activate = value), errorMessage = null) }

    fun saveTerm() {
        val state = _uiState.value
        val draft = state.termDraft
        if (!state.canManage || state.isSaving || !draft.valid) {
            if (!draft.valid) _uiState.update { it.copy(errorMessage = "Select a session and enter valid YYYY-MM-DD dates before saving the term.") }
            return
        }
        mutate {
            if (draft.id == null) {
                api.createTerm(
                    AcademicTermCreateRequestDto(
                        sessionId = requireNotNull(draft.sessionId),
                        name = draft.name.trim(),
                        startDate = draft.startDate,
                        endDate = draft.endDate,
                        nextTermBegins = draft.nextTermBegins.ifBlank { null },
                        activate = draft.activate,
                    )
                ).message
            } else {
                api.updateTerm(
                    draft.id,
                    AcademicTermUpdateRequestDto(
                        name = draft.name.trim(),
                        startDate = draft.startDate,
                        endDate = draft.endDate,
                        nextTermBegins = draft.nextTermBegins.ifBlank { null },
                    )
                ).message
            }
        }
    }

    fun closeEditor() = _uiState.update {
        it.copy(
            editor = AcademicCycleEditor.NONE,
            sessionDraft = AcademicSessionDraft(),
            termDraft = AcademicTermDraft(),
            errorMessage = null,
        )
    }

    fun requestActivateSession(session: AcademicSessionAdminDto) {
        if (session.current) return
        setPending(
            AcademicCycleActionKind.ACTIVATE_SESSION,
            session.id,
            "Activate ${session.name}?",
            "This makes the session current and deactivates any other current session for this school.",
        )
    }

    fun requestCloseSession(session: AcademicSessionAdminDto) {
        if (!session.current) return
        checkReadiness(
            isSession = true,
            id = session.id,
            title = "Close ${session.name}?",
            message = "EduCore will close this session only when the academic lifecycle readiness checks allow it.",
        )
    }

    fun requestDeleteSession(session: AcademicSessionAdminDto) {
        if (session.current || session.termCount > 0) return
        setPending(
            AcademicCycleActionKind.DELETE_SESSION,
            session.id,
            "Delete ${session.name}?",
            "Only a non-current session with no terms can be deleted.",
        )
    }

    fun requestActivateTerm(term: AcademicTermAdminDto) {
        if (term.current) return
        setPending(
            AcademicCycleActionKind.ACTIVATE_TERM,
            term.id,
            "Activate ${term.name}?",
            "Only a term belonging to the current academic session can be activated. Other current terms will be deactivated.",
        )
    }

    fun requestCloseTerm(term: AcademicTermAdminDto) {
        if (!term.current) return
        checkReadiness(
            isSession = false,
            id = term.id,
            title = "Close ${term.name}?",
            message = "EduCore will check CBT sessions, transfers, enrolments and other lifecycle blockers before closure.",
        )
    }

    fun requestDeleteTerm(term: AcademicTermAdminDto) {
        if (term.current) return
        setPending(
            AcademicCycleActionKind.DELETE_TERM,
            term.id,
            "Delete ${term.name}?",
            "A term with score records cannot be deleted. The server will enforce this safeguard.",
        )
    }

    fun cancelAction() = _uiState.update { it.copy(pendingAction = null, errorMessage = null) }

    fun confirmAction() {
        val state = _uiState.value
        val action = state.pendingAction ?: return
        if (!state.canManage || state.isSaving) return
        if ((action.kind == AcademicCycleActionKind.CLOSE_SESSION || action.kind == AcademicCycleActionKind.CLOSE_TERM) && action.readiness?.allowed != true) {
            _uiState.update { it.copy(errorMessage = "Resolve the blocking lifecycle items before attempting closure.") }
            return
        }

        mutate {
            when (action.kind) {
                AcademicCycleActionKind.ACTIVATE_SESSION -> api.activateSession(action.id).message
                AcademicCycleActionKind.CLOSE_SESSION -> api.closeSession(action.id).message
                AcademicCycleActionKind.DELETE_SESSION -> api.deleteSession(action.id).message
                AcademicCycleActionKind.ACTIVATE_TERM -> api.activateTerm(action.id).message
                AcademicCycleActionKind.CLOSE_TERM -> api.closeTerm(action.id).message
                AcademicCycleActionKind.DELETE_TERM -> api.deleteTerm(action.id).message
            }
        }
    }

    private fun setPending(kind: AcademicCycleActionKind, id: Long, title: String, message: String) {
        _uiState.update {
            it.copy(pendingAction = AcademicCycleAction(kind, id, title, message), errorMessage = null, message = null)
        }
    }

    private fun checkReadiness(isSession: Boolean, id: Long, title: String, message: String) {
        val state = _uiState.value
        if (!state.canManage || state.isCheckingReadiness) return
        viewModelScope.launch {
            _uiState.update { it.copy(isCheckingReadiness = true, errorMessage = null, message = null) }
            try {
                val readiness = if (isSession) api.sessionReadiness(id) else api.termReadiness(id)
                _uiState.update {
                    it.copy(
                        isCheckingReadiness = false,
                        pendingAction = AcademicCycleAction(
                            kind = if (isSession) AcademicCycleActionKind.CLOSE_SESSION else AcademicCycleActionKind.CLOSE_TERM,
                            id = id,
                            title = title,
                            message = message,
                            readiness = readiness,
                        ),
                    )
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isCheckingReadiness = false, errorMessage = error.academicCycleMessage()) }
            }
        }
    }

    private fun mutate(action: suspend () -> String) {
        if (_uiState.value.isSaving) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            try {
                val message = action()
                _uiState.update {
                    it.copy(
                        isSaving = false,
                        editor = AcademicCycleEditor.NONE,
                        sessionDraft = AcademicSessionDraft(),
                        termDraft = AcademicTermDraft(),
                        pendingAction = null,
                        message = message,
                    )
                }
                loadWorkspace(preserveMessage = true)
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isSaving = false, errorMessage = error.academicCycleMessage()) }
            }
        }
    }

    private fun loadWorkspace(preserveMessage: Boolean = false) {
        if (_uiState.value.isLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null, message = if (preserveMessage) it.message else null) }
            try {
                val workspace = api.index()
                _uiState.update { it.copy(workspace = workspace, isLoading = false) }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isLoading = false, errorMessage = error.academicCycleMessage()) }
            }
        }
    }
}

private fun Throwable.academicCycleMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "Your account does not have academic-cycle management permission."
        404 -> "The selected academic-cycle record is not available for this school."
        422 -> "The academic lifecycle rules rejected this change. Review the dates, current state and closure blockers."
        else -> "The academic-cycle service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the academic-cycle service. Check your connection and try again."
}
