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
import online.educoreng.educore.core.network.PlatformApi
import online.educoreng.educore.core.network.dto.PlatformGroupCreateRequestDto
import online.educoreng.educore.core.network.dto.PlatformGroupDetailDto
import online.educoreng.educore.core.network.dto.PlatformGroupMemberRequestDto
import retrofit2.HttpException

internal enum class PlatformGroupPendingAction { REMOVE_MEMBER, SET_LEAD }

internal data class PlatformGroupUiState(
    val groupId: Long? = null,
    val detail: PlatformGroupDetailDto? = null,
    val newGroupName: String = "",
    val newGroupDescription: String = "",
    val createOpen: Boolean = false,
    val pendingAction: PlatformGroupPendingAction? = null,
    val pendingTenantId: Long? = null,
    val isLoading: Boolean = false,
    val isMutating: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val createValid: Boolean get() = newGroupName.trim().length >= 2
}

@HiltViewModel
internal class PlatformGroupViewModel @Inject constructor(factory: ApiClientFactory) : ViewModel() {
    private val api = factory.create(PlatformApi::class.java)
    private val _uiState = MutableStateFlow(PlatformGroupUiState())
    val uiState: StateFlow<PlatformGroupUiState> = _uiState.asStateFlow()
    private var loadJob: Job? = null

    fun load(groupId: Long) {
        loadJob?.cancel()
        loadJob = viewModelScope.launch {
            _uiState.update { it.copy(groupId = groupId, isLoading = true, errorMessage = null, message = null) }
            try {
                _uiState.update { it.copy(detail = api.group(groupId), isLoading = false) }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isLoading = false, errorMessage = error.groupPlatformMessage()) }
            }
        }
    }

    fun openCreate() = _uiState.update {
        if (it.isMutating) it else it.copy(createOpen = true, newGroupName = "", newGroupDescription = "", errorMessage = null, message = null)
    }

    fun closeCreate() = _uiState.update { it.copy(createOpen = false, newGroupName = "", newGroupDescription = "") }

    fun setNewGroupName(value: String) = _uiState.update {
        if (it.isMutating) it else it.copy(newGroupName = value.take(120), errorMessage = null)
    }

    fun setNewGroupDescription(value: String) = _uiState.update {
        if (it.isMutating) it else it.copy(newGroupDescription = value.take(1000), errorMessage = null)
    }

    fun createGroup(onCreated: (Long) -> Unit) {
        val state = _uiState.value
        if (!state.createValid || state.isMutating) return
        mutate(refresh = false) {
            val response = api.createGroup(
                PlatformGroupCreateRequestDto(
                    name = state.newGroupName.trim(),
                    description = state.newGroupDescription.trim().takeIf(String::isNotBlank),
                )
            )
            val id = requireNotNull(response.id)
            onCreated(id)
            response.message
        }
    }

    fun addMember(tenantId: Long, role: String = "member") {
        val groupId = _uiState.value.groupId ?: return
        if (_uiState.value.isMutating) return
        mutate(refresh = true) {
            api.addGroupMember(groupId, PlatformGroupMemberRequestDto(tenantId, role)).message
        }
    }

    fun requestRemove(tenantId: Long) = _uiState.update {
        if (it.isMutating) it else it.copy(pendingAction = PlatformGroupPendingAction.REMOVE_MEMBER, pendingTenantId = tenantId)
    }

    fun requestLead(tenantId: Long) = _uiState.update {
        if (it.isMutating) it else it.copy(pendingAction = PlatformGroupPendingAction.SET_LEAD, pendingTenantId = tenantId)
    }

    fun dismissConfirmation() = _uiState.update { it.copy(pendingAction = null, pendingTenantId = null) }

    fun confirm() {
        val state = _uiState.value
        val groupId = state.groupId ?: return
        val tenantId = state.pendingTenantId ?: return
        val action = state.pendingAction ?: return
        if (state.isMutating) return

        mutate(refresh = true) {
            when (action) {
                PlatformGroupPendingAction.REMOVE_MEMBER -> api.removeGroupMember(groupId, tenantId).message
                PlatformGroupPendingAction.SET_LEAD -> api.setGroupLead(groupId, tenantId).message
            }
        }
    }

    private fun mutate(refresh: Boolean, action: suspend () -> String) {
        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null, message = null) }
            try {
                val message = action()
                val groupId = _uiState.value.groupId
                val detail = if (refresh && groupId != null) api.group(groupId) else _uiState.value.detail
                _uiState.update {
                    it.copy(
                        detail = detail,
                        createOpen = false,
                        newGroupName = "",
                        newGroupDescription = "",
                        pendingAction = null,
                        pendingTenantId = null,
                        isMutating = false,
                        message = message,
                    )
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isMutating = false, errorMessage = error.groupPlatformMessage()) }
            }
        }
    }
}

private fun Throwable.groupPlatformMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your platform session has expired. Sign in again."
        403 -> "Platform Super Admin access is required."
        404 -> "The school group or selected campus is no longer available."
        422 -> "The group change was rejected. A school may belong to only one group, and a protected lead campus cannot be removed."
        else -> "The platform service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank) ?: "Unable to reach the platform group service."
}
