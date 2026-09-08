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
import online.educoreng.educore.core.network.dto.PlatformTenantDetailDto
import online.educoreng.educore.core.network.dto.PlatformTenantExtendRequestDto
import online.educoreng.educore.core.network.dto.PlatformTenantUpdateRequestDto
import retrofit2.HttpException

internal enum class PlatformTenantPendingAction { STATUS, EXTEND }

internal data class PlatformTenantUiState(
    val tenantId: Long? = null,
    val detail: PlatformTenantDetailDto? = null,
    val reason: String = "",
    val extensionMonths: Int = 3,
    val pendingAction: PlatformTenantPendingAction? = null,
    val pendingStatus: String? = null,
    val isLoading: Boolean = false,
    val isMutating: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val reasonValid: Boolean get() = reason.trim().length >= 5
}

@HiltViewModel
internal class PlatformTenantViewModel @Inject constructor(factory: ApiClientFactory) : ViewModel() {
    private val api = factory.create(PlatformApi::class.java)
    private val _uiState = MutableStateFlow(PlatformTenantUiState())
    val uiState: StateFlow<PlatformTenantUiState> = _uiState.asStateFlow()
    private var loadJob: Job? = null

    fun load(tenantId: Long) {
        loadJob?.cancel()
        loadJob = viewModelScope.launch {
            _uiState.update { it.copy(tenantId = tenantId, isLoading = true, errorMessage = null, message = null) }
            try {
                val detail = api.tenant(tenantId)
                _uiState.update { it.copy(detail = detail, isLoading = false) }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isLoading = false, errorMessage = error.tenantPlatformMessage()) }
            }
        }
    }

    fun setReason(value: String) = _uiState.update {
        if (it.isMutating) it else it.copy(reason = value.take(500), errorMessage = null, message = null)
    }

    fun setExtensionMonths(value: Int) {
        val allowed = _uiState.value.detail?.subscription?.allowedMonths.orEmpty()
        if (value !in allowed) return
        _uiState.update { if (it.isMutating) it else it.copy(extensionMonths = value, errorMessage = null) }
    }

    fun requestStatus(status: String) {
        if (status !in setOf("active", "pending", "suspended", "subscription_expired")) return
        val state = _uiState.value
        if (!state.reasonValid || state.isMutating || state.detail?.tenant?.status == status) return
        _uiState.update { it.copy(pendingAction = PlatformTenantPendingAction.STATUS, pendingStatus = status) }
    }

    fun requestExtension() {
        val state = _uiState.value
        if (!state.reasonValid || state.isMutating || state.detail?.subscription?.canExtend != true) return
        _uiState.update { it.copy(pendingAction = PlatformTenantPendingAction.EXTEND, pendingStatus = null) }
    }

    fun dismissConfirmation() = _uiState.update { it.copy(pendingAction = null, pendingStatus = null) }

    fun confirm() {
        val state = _uiState.value
        val tenantId = state.tenantId ?: return
        val action = state.pendingAction ?: return
        if (!state.reasonValid || state.isMutating) return

        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null, message = null) }
            try {
                val response = when (action) {
                    PlatformTenantPendingAction.STATUS -> api.updateTenant(
                        tenantId,
                        PlatformTenantUpdateRequestDto(
                            status = requireNotNull(state.pendingStatus),
                            reason = state.reason.trim(),
                        ),
                    )
                    PlatformTenantPendingAction.EXTEND -> api.extendTenant(
                        tenantId,
                        PlatformTenantExtendRequestDto(
                            months = state.extensionMonths,
                            reason = state.reason.trim(),
                        ),
                    )
                }
                val detail = api.tenant(tenantId)
                _uiState.update {
                    it.copy(
                        detail = detail,
                        reason = "",
                        pendingAction = null,
                        pendingStatus = null,
                        isMutating = false,
                        message = response.message,
                    )
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isMutating = false, errorMessage = error.tenantPlatformMessage()) }
            }
        }
    }
}

private fun Throwable.tenantPlatformMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your platform session has expired. Sign in again."
        403 -> "Platform Super Admin access is required."
        404 -> "This school is no longer available."
        422 -> "The school lifecycle change was rejected. Review the reason, status and subscription state."
        else -> "The platform service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the platform service."
}
