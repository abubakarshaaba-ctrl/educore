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
import online.educoreng.educore.core.network.dto.PlatformGatewayDto
import online.educoreng.educore.core.network.dto.PlatformGatewayUpdateRequestDto
import online.educoreng.educore.core.network.dto.PlatformSettingsUpdateRequestDto
import retrofit2.HttpException

internal data class PlatformSettingsEditorState(
    val values: Map<String, String> = emptyMap(),
    val maintenanceMode: Boolean = false,
    val reason: String = "",
    val gateways: List<PlatformGatewayDto> = emptyList(),
    val selectedProvider: String? = null,
    val gatewayPublicKey: String = "",
    val gatewaySecretKey: String = "",
    val gatewayContractCode: String = "",
    val gatewayLive: Boolean = false,
    val isLoading: Boolean = false,
    val isMutating: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val settingsValid: Boolean
        get() = listOf(
            "platform_name", "support_email", "support_phone", "support_whatsapp",
            "support_website", "office_address", "grace_period_days", "default_sms_gateway", "sms_sender_id",
        ).all { values[it].orEmpty().isNotBlank() } && reason.trim().length >= 5

    val gatewayValid: Boolean get() = selectedProvider != null && reason.trim().length >= 5
}

@HiltViewModel
internal class PlatformSettingsViewModel @Inject constructor(factory: ApiClientFactory) : ViewModel() {
    private val api = factory.create(PlatformApi::class.java)
    private val _uiState = MutableStateFlow(PlatformSettingsEditorState())
    val uiState: StateFlow<PlatformSettingsEditorState> = _uiState.asStateFlow()

    fun loadSettings() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null, message = null) }
            try {
                val response = api.settings()
                val values = response.settings.associate { setting ->
                    setting.key to when (val value = setting.value) {
                        null -> ""
                        is Boolean -> if (value) "1" else "0"
                        else -> value.toString()
                    }
                }
                _uiState.update {
                    it.copy(
                        values = values,
                        maintenanceMode = values["maintenance_mode"] in setOf("1", "true"),
                        isLoading = false,
                    )
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isLoading = false, errorMessage = error.settingsPlatformMessage()) }
            }
        }
    }

    fun loadGateways() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null, message = null) }
            try {
                _uiState.update { it.copy(gateways = api.gateways().gateways, isLoading = false) }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isLoading = false, errorMessage = error.settingsPlatformMessage()) }
            }
        }
    }

    fun setValue(key: String, value: String) {
        if (key !in EDITABLE_KEYS || _uiState.value.isMutating) return
        _uiState.update { it.copy(values = it.values + (key to value.take(255)), errorMessage = null, message = null) }
    }

    fun setMaintenanceMode(value: Boolean) = _uiState.update {
        if (it.isMutating) it else it.copy(maintenanceMode = value, values = it.values + ("maintenance_mode" to if (value) "1" else "0"))
    }

    fun setReason(value: String) = _uiState.update {
        if (it.isMutating) it else it.copy(reason = value.take(500), errorMessage = null, message = null)
    }

    fun saveSettings() {
        val state = _uiState.value
        if (!state.settingsValid || state.isMutating) return
        val grace = state.values["grace_period_days"]?.toIntOrNull()
        if (grace == null || grace !in 0..90) {
            _uiState.update { it.copy(errorMessage = "Grace period must be between 0 and 90 days.") }
            return
        }

        val payload: Map<String, Any?> = mapOf(
            "platform_name" to state.values["platform_name"].orEmpty().trim(),
            "support_email" to state.values["support_email"].orEmpty().trim(),
            "support_phone" to state.values["support_phone"].orEmpty().trim(),
            "support_whatsapp" to state.values["support_whatsapp"].orEmpty().trim(),
            "support_website" to state.values["support_website"].orEmpty().trim(),
            "office_address" to state.values["office_address"].orEmpty().trim(),
            "grace_period_days" to grace,
            "bank_transfer_bank_name" to state.values["bank_transfer_bank_name"].orEmpty().trim().takeIf(String::isNotBlank),
            "bank_transfer_account_name" to state.values["bank_transfer_account_name"].orEmpty().trim().takeIf(String::isNotBlank),
            "bank_transfer_account_number" to state.values["bank_transfer_account_number"].orEmpty().trim().takeIf(String::isNotBlank),
            "default_sms_gateway" to state.values["default_sms_gateway"].orEmpty().trim(),
            "sms_sender_id" to state.values["sms_sender_id"].orEmpty().trim(),
            "maintenance_mode" to state.maintenanceMode,
        )
        mutate {
            api.updateSettings(PlatformSettingsUpdateRequestDto(payload, state.reason.trim())).message
        }
    }

    fun selectGateway(provider: String) {
        val gateway = _uiState.value.gateways.firstOrNull { it.provider == provider } ?: return
        _uiState.update {
            it.copy(
                selectedProvider = provider,
                gatewayPublicKey = "",
                gatewaySecretKey = "",
                gatewayContractCode = "",
                gatewayLive = gateway.live,
                reason = "",
                errorMessage = null,
                message = null,
            )
        }
    }

    fun clearGateway() = _uiState.update {
        it.copy(selectedProvider = null, gatewayPublicKey = "", gatewaySecretKey = "", gatewayContractCode = "", reason = "")
    }

    fun setGatewayPublic(value: String) = _uiState.update { if (it.isMutating) it else it.copy(gatewayPublicKey = value.take(255), errorMessage = null) }
    fun setGatewaySecret(value: String) = _uiState.update { if (it.isMutating) it else it.copy(gatewaySecretKey = value.take(255), errorMessage = null) }
    fun setGatewayContract(value: String) = _uiState.update { if (it.isMutating) it else it.copy(gatewayContractCode = value.take(100), errorMessage = null) }
    fun setGatewayLive(value: Boolean) = _uiState.update { if (it.isMutating) it else it.copy(gatewayLive = value, errorMessage = null) }

    fun saveGateway() {
        val state = _uiState.value
        val provider = state.selectedProvider ?: return
        if (!state.gatewayValid || state.isMutating) return
        mutate(refreshGateways = true) {
            api.updateGateway(
                provider,
                PlatformGatewayUpdateRequestDto(
                    publicKey = state.gatewayPublicKey.trim(),
                    secretKey = state.gatewaySecretKey.trim().takeIf(String::isNotBlank),
                    contractCode = state.gatewayContractCode.trim().takeIf(String::isNotBlank),
                    live = state.gatewayLive,
                    reason = state.reason.trim(),
                ),
            ).message
        }
    }

    private fun mutate(refreshGateways: Boolean = false, action: suspend () -> String) {
        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null, message = null) }
            try {
                val message = action()
                val gateways = if (refreshGateways) api.gateways().gateways else _uiState.value.gateways
                _uiState.update {
                    it.copy(
                        gateways = gateways,
                        selectedProvider = if (refreshGateways) null else it.selectedProvider,
                        gatewayPublicKey = if (refreshGateways) "" else it.gatewayPublicKey,
                        gatewaySecretKey = "",
                        gatewayContractCode = if (refreshGateways) "" else it.gatewayContractCode,
                        reason = "",
                        isMutating = false,
                        message = message,
                    )
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isMutating = false, errorMessage = error.settingsPlatformMessage()) }
            }
        }
    }

    private companion object {
        val EDITABLE_KEYS = setOf(
            "platform_name", "support_email", "support_phone", "support_whatsapp", "support_website", "office_address",
            "grace_period_days", "bank_transfer_bank_name", "bank_transfer_account_name", "bank_transfer_account_number",
            "default_sms_gateway", "sms_sender_id", "maintenance_mode",
        )
    }
}

private fun Throwable.settingsPlatformMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your platform session has expired. Sign in again."
        403 -> "Platform Super Admin access is required."
        422 -> "The platform rejected these settings. Review the required fields and credential format."
        else -> "The platform service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank) ?: "Unable to reach the platform settings service."
}
