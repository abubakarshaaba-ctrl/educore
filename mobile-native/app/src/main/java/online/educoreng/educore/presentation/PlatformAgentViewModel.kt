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
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.PlatformApi
import online.educoreng.educore.core.network.dto.PlatformAgentCreateRequestDto
import online.educoreng.educore.core.network.dto.PlatformAgentDto
import online.educoreng.educore.core.network.dto.PlatformAgentUpdateRequestDto
import retrofit2.HttpException

internal data class PlatformAgentUiState(
    val agents: List<PlatformAgentDto> = emptyList(),
    val editorOpen: Boolean = false,
    val editingId: Long? = null,
    val name: String = "",
    val email: String = "",
    val phone: String = "",
    val stateName: String = "",
    val commissionRate: String = "10",
    val active: Boolean = true,
    val reason: String = "",
    val pendingDeactivateId: Long? = null,
    val isLoading: Boolean = false,
    val isMutating: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val valid: Boolean
        get() = name.trim().length >= 2 &&
            (editingId != null || email.contains('@')) &&
            commissionRate.toDoubleOrNull()?.let { it in 1.0..50.0 } == true &&
            reason.trim().length >= 5
}

@HiltViewModel
internal class PlatformAgentViewModel @Inject constructor(factory: ApiClientFactory) : ViewModel() {
    private val api = factory.create(PlatformApi::class.java)
    private val _uiState = MutableStateFlow(PlatformAgentUiState())
    val uiState: StateFlow<PlatformAgentUiState> = _uiState.asStateFlow()

    fun load() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null, message = null) }
            try {
                _uiState.update { it.copy(agents = api.agents().agents, isLoading = false) }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isLoading = false, errorMessage = error.agentPlatformMessage()) }
            }
        }
    }

    fun newAgent() = _uiState.update {
        if (it.isMutating) it else PlatformAgentUiState(agents = it.agents, editorOpen = true)
    }

    fun edit(agent: PlatformAgentDto) = _uiState.update {
        if (it.isMutating) it else it.copy(
            editorOpen = true,
            editingId = agent.id,
            name = agent.name,
            email = agent.email,
            phone = agent.phone.orEmpty(),
            stateName = agent.state.orEmpty(),
            commissionRate = agent.commissionRate.toString(),
            active = agent.active,
            reason = "",
            errorMessage = null,
            message = null,
        )
    }

    fun closeEditor() = _uiState.update {
        it.copy(editorOpen = false, editingId = null, name = "", email = "", phone = "", stateName = "", commissionRate = "10", active = true, reason = "")
    }

    fun setName(value: String) = editField { copy(name = value.take(150)) }
    fun setEmail(value: String) = editField { copy(email = value.take(180)) }
    fun setPhone(value: String) = editField { copy(phone = value.take(30)) }
    fun setStateName(value: String) = editField { copy(stateName = value.take(100)) }
    fun setCommissionRate(value: String) = editField { copy(commissionRate = value.take(6)) }
    fun setReason(value: String) = editField { copy(reason = value.take(500)) }

    fun save() {
        val state = _uiState.value
        if (!state.valid || state.isMutating) return
        val rate = state.commissionRate.toDoubleOrNull() ?: return
        mutate {
            if (state.editingId == null) {
                api.createAgent(
                    PlatformAgentCreateRequestDto(
                        name = state.name.trim(),
                        email = state.email.trim(),
                        phone = state.phone.trim().takeIf(String::isNotBlank),
                        state = state.stateName.trim().takeIf(String::isNotBlank),
                        commissionRate = rate,
                        reason = state.reason.trim(),
                    )
                ).message
            } else {
                api.updateAgent(
                    state.editingId,
                    PlatformAgentUpdateRequestDto(
                        name = state.name.trim(),
                        phone = state.phone.trim().takeIf(String::isNotBlank),
                        state = state.stateName.trim().takeIf(String::isNotBlank),
                        commissionRate = rate,
                        reason = state.reason.trim(),
                    )
                ).message
            }
        }
    }

    fun requestDeactivate(agent: PlatformAgentDto) {
        if (!agent.active || _uiState.value.isMutating) return
        _uiState.update { it.copy(pendingDeactivateId = agent.id, reason = "", errorMessage = null, message = null) }
    }

    fun dismissDeactivate() = _uiState.update { it.copy(pendingDeactivateId = null, reason = "") }

    fun confirmDeactivate() {
        val state = _uiState.value
        val id = state.pendingDeactivateId ?: return
        if (state.reason.trim().length < 5 || state.isMutating) return
        mutate {
            api.updateAgent(id, PlatformAgentUpdateRequestDto(isActive = false, reason = state.reason.trim())).message
        }
    }

    fun activate(agent: PlatformAgentDto) {
        val reason = "Reactivate platform agent account"
        if (agent.active || _uiState.value.isMutating) return
        mutate { api.updateAgent(agent.id, PlatformAgentUpdateRequestDto(isActive = true, reason = reason)).message }
    }

    private fun editField(block: PlatformAgentUiState.() -> PlatformAgentUiState) = _uiState.update {
        if (it.isMutating) it else it.block().copy(errorMessage = null, message = null)
    }

    private fun mutate(action: suspend () -> String) {
        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null, message = null) }
            try {
                val message = action()
                val agents = api.agents().agents
                _uiState.update {
                    PlatformAgentUiState(agents = agents, isMutating = false, message = message)
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isMutating = false, errorMessage = error.agentPlatformMessage()) }
            }
        }
    }
}

private fun Throwable.agentPlatformMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your platform session has expired. Sign in again."
        403 -> "Platform Super Admin access is required."
        422 -> "The agent change was rejected. Check the email, commission rate and reason."
        else -> "The platform service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank) ?: "Unable to reach the platform agent service."
}
