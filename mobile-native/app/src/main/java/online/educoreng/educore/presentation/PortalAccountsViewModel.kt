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
import online.educoreng.educore.core.network.PortalAccountsApi
import online.educoreng.educore.core.network.dto.PortalAccountCreateRequestDto
import online.educoreng.educore.core.network.dto.PortalAccountsWorkspaceDto
import online.educoreng.educore.core.network.dto.PortalPasswordResetRequestDto
import retrofit2.HttpException

enum class PortalAccountsTab { STUDENTS, PARENTS }
enum class PortalAccountEditorMode { NONE, CREATE_STUDENT, CREATE_PARENT, RESET_PASSWORD }
enum class PortalAccountPendingAction { TOGGLE_ACCESS, BULK_STUDENTS }

internal data class PortalAccountsUiState(
    val workspace: PortalAccountsWorkspaceDto? = null,
    val tab: PortalAccountsTab = PortalAccountsTab.STUDENTS,
    val query: String = "",
    val editorMode: PortalAccountEditorMode = PortalAccountEditorMode.NONE,
    val editorTargetId: Long? = null,
    val editorTargetName: String = "",
    val emailDraft: String = "",
    val passwordDraft: String = "",
    val pendingAction: PortalAccountPendingAction? = null,
    val pendingUserId: Long? = null,
    val pendingTargetName: String = "",
    val pendingCurrentlyActive: Boolean = false,
    val isLoading: Boolean = false,
    val isMutating: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val canManage: Boolean get() = workspace?.capabilities?.manage == true
    val editorOpen: Boolean get() = editorMode != PortalAccountEditorMode.NONE
    val createValid: Boolean
        get() = emailDraft.contains('@') && emailDraft.length <= 180 && passwordDraft.length >= 8
    val resetValid: Boolean get() = passwordDraft.length >= 8
}

@HiltViewModel
internal class PortalAccountsViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api = factory.create(PortalAccountsApi::class.java)
    private val _uiState = MutableStateFlow(PortalAccountsUiState())
    val uiState: StateFlow<PortalAccountsUiState> = _uiState.asStateFlow()
    private var loadJob: Job? = null

    fun load() {
        loadJob?.cancel()
        loadJob = viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            try {
                val workspace = api.index()
                _uiState.update { it.copy(workspace = workspace, isLoading = false) }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isLoading = false, errorMessage = error.portalAccountsMessage()) }
            }
        }
    }

    fun setTab(tab: PortalAccountsTab) {
        _uiState.update {
            it.copy(
                tab = tab,
                query = "",
                editorMode = PortalAccountEditorMode.NONE,
                editorTargetId = null,
                editorTargetName = "",
                emailDraft = "",
                passwordDraft = "",
                pendingAction = null,
                pendingUserId = null,
                message = null,
                errorMessage = null,
            )
        }
    }

    fun setQuery(value: String) = _uiState.update { it.copy(query = value.take(120), errorMessage = null) }

    fun createStudent(id: Long, name: String, suggestedEmail: String?) = openCreate(
        PortalAccountEditorMode.CREATE_STUDENT, id, name, suggestedEmail,
    )

    fun createParent(id: Long, name: String, suggestedEmail: String?) = openCreate(
        PortalAccountEditorMode.CREATE_PARENT, id, name, suggestedEmail,
    )

    fun resetPassword(userId: Long, name: String) {
        if (!_uiState.value.canManage || _uiState.value.isMutating) return
        _uiState.update {
            it.copy(
                editorMode = PortalAccountEditorMode.RESET_PASSWORD,
                editorTargetId = userId,
                editorTargetName = name,
                emailDraft = "",
                passwordDraft = "",
                errorMessage = null,
                message = null,
            )
        }
    }

    fun setEmail(value: String) = _uiState.update {
        if (it.isMutating) it else it.copy(emailDraft = value.take(180), errorMessage = null)
    }

    fun setPassword(value: String) = _uiState.update {
        if (it.isMutating) it else it.copy(passwordDraft = value.take(255), errorMessage = null)
    }

    fun closeEditor() = _uiState.update {
        it.copy(
            editorMode = PortalAccountEditorMode.NONE,
            editorTargetId = null,
            editorTargetName = "",
            emailDraft = "",
            passwordDraft = "",
            errorMessage = null,
        )
    }

    fun saveEditor() {
        val state = _uiState.value
        val target = state.editorTargetId ?: return
        if (!state.canManage || state.isMutating) return
        when (state.editorMode) {
            PortalAccountEditorMode.CREATE_STUDENT -> {
                if (!state.createValid) return
                mutate {
                    api.createStudent(target, PortalAccountCreateRequestDto(state.emailDraft.trim(), state.passwordDraft)).message
                }
            }
            PortalAccountEditorMode.CREATE_PARENT -> {
                if (!state.createValid) return
                mutate {
                    api.createGuardian(target, PortalAccountCreateRequestDto(state.emailDraft.trim(), state.passwordDraft)).message
                }
            }
            PortalAccountEditorMode.RESET_PASSWORD -> {
                if (!state.resetValid) return
                mutate {
                    api.resetPassword(target, PortalPasswordResetRequestDto(state.passwordDraft)).message
                }
            }
            PortalAccountEditorMode.NONE -> Unit
        }
    }

    fun requestToggle(userId: Long, name: String, currentlyActive: Boolean) {
        if (!_uiState.value.canManage || _uiState.value.isMutating) return
        _uiState.update {
            it.copy(
                pendingAction = PortalAccountPendingAction.TOGGLE_ACCESS,
                pendingUserId = userId,
                pendingTargetName = name,
                pendingCurrentlyActive = currentlyActive,
                errorMessage = null,
                message = null,
            )
        }
    }

    fun requestBulkStudents() {
        if (!_uiState.value.canManage || _uiState.value.isMutating) return
        _uiState.update {
            it.copy(
                pendingAction = PortalAccountPendingAction.BULK_STUDENTS,
                pendingUserId = null,
                pendingTargetName = "Student portal accounts",
                pendingCurrentlyActive = false,
                errorMessage = null,
                message = null,
            )
        }
    }

    fun cancelPendingAction() = _uiState.update {
        it.copy(
            pendingAction = null,
            pendingUserId = null,
            pendingTargetName = "",
            pendingCurrentlyActive = false,
        )
    }

    fun confirmPendingAction() {
        val state = _uiState.value
        if (!state.canManage || state.isMutating) return
        when (state.pendingAction) {
            PortalAccountPendingAction.TOGGLE_ACCESS -> {
                val userId = state.pendingUserId ?: return
                mutate { api.toggle(userId).message }
            }
            PortalAccountPendingAction.BULK_STUDENTS -> mutate { api.bulkStudents().message }
            null -> Unit
        }
    }

    private fun openCreate(
        mode: PortalAccountEditorMode,
        id: Long,
        name: String,
        suggestedEmail: String?,
    ) {
        if (!_uiState.value.canManage || _uiState.value.isMutating) return
        _uiState.update {
            it.copy(
                editorMode = mode,
                editorTargetId = id,
                editorTargetName = name,
                emailDraft = suggestedEmail.orEmpty(),
                passwordDraft = "",
                errorMessage = null,
                message = null,
            )
        }
    }

    private fun mutate(action: suspend () -> String) {
        loadJob?.cancel()
        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null, message = null) }
            try {
                val message = action()
                val workspace = api.index()
                _uiState.update {
                    it.copy(
                        workspace = workspace,
                        editorMode = PortalAccountEditorMode.NONE,
                        editorTargetId = null,
                        editorTargetName = "",
                        emailDraft = "",
                        passwordDraft = "",
                        pendingAction = null,
                        pendingUserId = null,
                        pendingTargetName = "",
                        pendingCurrentlyActive = false,
                        isMutating = false,
                        message = message,
                    )
                }
            } catch (cancelled: CancellationException) {
                _uiState.update { it.copy(passwordDraft = "", isMutating = false) }
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update {
                    it.copy(
                        passwordDraft = "",
                        isMutating = false,
                        errorMessage = error.portalAccountsMessage(),
                    )
                }
            }
        }
    }
}

private fun Throwable.portalAccountsMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "Your account does not have portal-account management permission."
        404 -> "The selected student, parent or portal account is unavailable for this school."
        422 -> "The portal-account change was rejected. Check the email, password and current account state."
        else -> "The portal-account service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the portal-account service. Check your connection and try again."
}
