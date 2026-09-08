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
import online.educoreng.educore.core.network.dto.PlatformAgentsDto
import online.educoreng.educore.core.network.dto.PlatformAnalyticsDto
import online.educoreng.educore.core.network.dto.PlatformBillingDto
import online.educoreng.educore.core.network.dto.PlatformBroadcastCreateRequestDto
import online.educoreng.educore.core.network.dto.PlatformBroadcastsDto
import online.educoreng.educore.core.network.dto.PlatformDashboardDto
import online.educoreng.educore.core.network.dto.PlatformGatewaysDto
import online.educoreng.educore.core.network.dto.PlatformGroupsDto
import online.educoreng.educore.core.network.dto.PlatformPlansDto
import online.educoreng.educore.core.network.dto.PlatformSettingsDto
import online.educoreng.educore.core.network.dto.PlatformSupportDto
import online.educoreng.educore.core.network.dto.PlatformSupportReplyRequestDto
import online.educoreng.educore.core.network.dto.PlatformTenantsDto
import retrofit2.HttpException

enum class PlatformSection {
    OVERVIEW,
    SCHOOLS,
    BILLING,
    PLANS,
    AGENTS,
    ANALYTICS,
    GROUPS,
    SUPPORT,
    BROADCASTS,
    SETTINGS,
    GATEWAYS,
}

enum class PlatformPendingAction { CLOSE_SUPPORT, EXPIRE_BROADCAST }

internal data class PlatformUiState(
    val section: PlatformSection = PlatformSection.OVERVIEW,
    val dashboard: PlatformDashboardDto? = null,
    val tenants: PlatformTenantsDto? = null,
    val billing: PlatformBillingDto? = null,
    val plans: PlatformPlansDto? = null,
    val agents: PlatformAgentsDto? = null,
    val analytics: PlatformAnalyticsDto? = null,
    val groups: PlatformGroupsDto? = null,
    val support: PlatformSupportDto? = null,
    val broadcasts: PlatformBroadcastsDto? = null,
    val settings: PlatformSettingsDto? = null,
    val gateways: PlatformGatewaysDto? = null,
    val search: String = "",
    val status: String? = null,
    val replyTicketId: Long? = null,
    val replyDraft: String = "",
    val broadcastEditorOpen: Boolean = false,
    val broadcastTitle: String = "",
    val broadcastBody: String = "",
    val broadcastTarget: String = "all",
    val broadcastExpiresAt: String = "",
    val pendingAction: PlatformPendingAction? = null,
    val pendingActionId: Long? = null,
    val isLoading: Boolean = false,
    val isMutating: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val replyValid: Boolean get() = replyTicketId != null && replyDraft.trim().length >= 2
    val broadcastValid: Boolean get() = broadcastTitle.isNotBlank() && broadcastBody.isNotBlank()
}

@HiltViewModel
internal class PlatformViewModel @Inject constructor(factory: ApiClientFactory) : ViewModel() {
    private val api = factory.create(PlatformApi::class.java)
    private val _uiState = MutableStateFlow(PlatformUiState())
    val uiState: StateFlow<PlatformUiState> = _uiState.asStateFlow()
    private var loadJob: Job? = null

    fun load(section: PlatformSection = _uiState.value.section) {
        loadJob?.cancel()
        loadJob = viewModelScope.launch {
            _uiState.update { it.copy(section = section, isLoading = true, errorMessage = null) }
            try {
                when (section) {
                    PlatformSection.OVERVIEW -> _uiState.update { it.copy(dashboard = api.dashboard()) }
                    PlatformSection.SCHOOLS -> {
                        val state = _uiState.value
                        _uiState.update {
                            it.copy(tenants = api.tenants(state.search.trim().takeIf(String::isNotBlank), state.status))
                        }
                    }
                    PlatformSection.BILLING -> _uiState.update { it.copy(billing = api.billing()) }
                    PlatformSection.PLANS -> _uiState.update { it.copy(plans = api.plans()) }
                    PlatformSection.AGENTS -> _uiState.update { it.copy(agents = api.agents()) }
                    PlatformSection.ANALYTICS -> _uiState.update { it.copy(analytics = api.analytics()) }
                    PlatformSection.GROUPS -> _uiState.update { it.copy(groups = api.groups()) }
                    PlatformSection.SUPPORT -> _uiState.update { it.copy(support = api.support()) }
                    PlatformSection.BROADCASTS -> _uiState.update { it.copy(broadcasts = api.broadcasts()) }
                    PlatformSection.SETTINGS -> _uiState.update { it.copy(settings = api.settings()) }
                    PlatformSection.GATEWAYS -> _uiState.update { it.copy(gateways = api.gateways()) }
                }
                _uiState.update { it.copy(isLoading = false) }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isLoading = false, errorMessage = error.platformMessage()) }
            }
        }
    }

    fun selectSection(section: PlatformSection) {
        _uiState.update {
            it.copy(
                replyTicketId = null,
                replyDraft = "",
                broadcastEditorOpen = false,
                pendingAction = null,
                pendingActionId = null,
                message = null,
            )
        }
        load(section)
    }

    fun setSearch(value: String) {
        _uiState.update { it.copy(search = value.take(120), errorMessage = null) }
    }

    fun searchSchools() = load(PlatformSection.SCHOOLS)

    fun setStatus(value: String?) {
        _uiState.update { it.copy(status = value, errorMessage = null) }
        load(PlatformSection.SCHOOLS)
    }

    fun editReply(ticketId: Long, existing: String? = null) {
        if (_uiState.value.isMutating) return
        _uiState.update {
            it.copy(replyTicketId = ticketId, replyDraft = existing.orEmpty(), errorMessage = null, message = null)
        }
    }

    fun setReplyDraft(value: String) {
        _uiState.update { if (it.isMutating) it else it.copy(replyDraft = value.take(3000), errorMessage = null) }
    }

    fun cancelReply() = _uiState.update { it.copy(replyTicketId = null, replyDraft = "", errorMessage = null) }

    fun sendReply() {
        val state = _uiState.value
        val ticketId = state.replyTicketId ?: return
        if (!state.replyValid || state.isMutating) return
        mutate(PlatformSection.SUPPORT) {
            api.replySupport(ticketId, PlatformSupportReplyRequestDto(state.replyDraft.trim())).message
        }
    }

    fun requestCloseSupport(ticketId: Long) = _uiState.update {
        if (it.isMutating) it else it.copy(
            pendingAction = PlatformPendingAction.CLOSE_SUPPORT,
            pendingActionId = ticketId,
            errorMessage = null,
            message = null,
        )
    }

    fun openBroadcastEditor() = _uiState.update {
        if (it.isMutating) it else it.copy(
            broadcastEditorOpen = true,
            broadcastTitle = "",
            broadcastBody = "",
            broadcastTarget = "all",
            broadcastExpiresAt = "",
            errorMessage = null,
            message = null,
        )
    }

    fun closeBroadcastEditor() = _uiState.update {
        it.copy(broadcastEditorOpen = false, broadcastTitle = "", broadcastBody = "", broadcastExpiresAt = "", errorMessage = null)
    }

    fun setBroadcastTitle(value: String) = _uiState.update {
        if (it.isMutating) it else it.copy(broadcastTitle = value.take(150), errorMessage = null)
    }

    fun setBroadcastBody(value: String) = _uiState.update {
        if (it.isMutating) it else it.copy(broadcastBody = value.take(5000), errorMessage = null)
    }

    fun setBroadcastTarget(value: String) {
        if (value !in setOf("all", "active", "trial", "expired")) return
        _uiState.update { if (it.isMutating) it else it.copy(broadcastTarget = value, errorMessage = null) }
    }

    fun setBroadcastExpiresAt(value: String) = _uiState.update {
        if (it.isMutating) it else it.copy(broadcastExpiresAt = value.take(25), errorMessage = null)
    }

    fun createBroadcast() {
        val state = _uiState.value
        if (!state.broadcastValid || state.isMutating) return
        mutate(PlatformSection.BROADCASTS) {
            api.createBroadcast(
                PlatformBroadcastCreateRequestDto(
                    title = state.broadcastTitle.trim(),
                    body = state.broadcastBody.trim(),
                    target = state.broadcastTarget,
                    expiresAt = state.broadcastExpiresAt.trim().takeIf(String::isNotBlank),
                )
            ).message
        }
    }

    fun requestExpireBroadcast(id: Long) = _uiState.update {
        if (it.isMutating) it else it.copy(
            pendingAction = PlatformPendingAction.EXPIRE_BROADCAST,
            pendingActionId = id,
            errorMessage = null,
            message = null,
        )
    }

    fun dismissPendingAction() = _uiState.update { it.copy(pendingAction = null, pendingActionId = null) }

    fun confirmPendingAction() {
        val state = _uiState.value
        val action = state.pendingAction ?: return
        val id = state.pendingActionId ?: return
        if (state.isMutating) return
        val section = when (action) {
            PlatformPendingAction.CLOSE_SUPPORT -> PlatformSection.SUPPORT
            PlatformPendingAction.EXPIRE_BROADCAST -> PlatformSection.BROADCASTS
        }
        mutate(section) {
            when (action) {
                PlatformPendingAction.CLOSE_SUPPORT -> api.closeSupport(id).message
                PlatformPendingAction.EXPIRE_BROADCAST -> api.expireBroadcast(id).message
            }
        }
    }

    private fun mutate(section: PlatformSection, action: suspend () -> String) {
        loadJob?.cancel()
        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null, message = null) }
            try {
                val message = action()
                val refreshed = when (section) {
                    PlatformSection.SUPPORT -> api.support()
                    PlatformSection.BROADCASTS -> api.broadcasts()
                    else -> null
                }
                _uiState.update {
                    it.copy(
                        support = if (section == PlatformSection.SUPPORT) refreshed as PlatformSupportDto else it.support,
                        broadcasts = if (section == PlatformSection.BROADCASTS) refreshed as PlatformBroadcastsDto else it.broadcasts,
                        replyTicketId = null,
                        replyDraft = "",
                        broadcastEditorOpen = false,
                        broadcastTitle = "",
                        broadcastBody = "",
                        broadcastExpiresAt = "",
                        pendingAction = null,
                        pendingActionId = null,
                        isMutating = false,
                        message = message,
                    )
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isMutating = false, errorMessage = error.platformMessage()) }
            }
        }
    }
}

private fun Throwable.platformMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your platform session has expired. Sign in again."
        403 -> "Platform Super Admin access is required."
        404 -> "The requested platform record is no longer available."
        422 -> "The platform rejected this change. Review the current state and submitted values."
        else -> "The platform service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the EduCore platform service. Check your connection and try again."
}
