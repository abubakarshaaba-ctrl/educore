package online.educoreng.educore.presentation

import androidx.compose.runtime.Composable
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.squareup.moshi.Moshi
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.network.AdminStaffAttendanceApi
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.dto.AdminStaffAttendanceQrDto
import online.educoreng.educore.core.network.safeApiCall

@HiltViewModel
class AdminStaffAttendanceQrViewModel @Inject constructor(
    factory: ApiClientFactory,
    moshi: Moshi,
) : ViewModel() {
    private val api = factory.create(AdminStaffAttendanceApi::class.java)
    private val parser = moshi
    private val _uiState = MutableStateFlow(AdminStaffAttendanceQrUiState())
    val uiState: StateFlow<AdminStaffAttendanceQrUiState> = _uiState.asStateFlow()

    fun load() {
        if (_uiState.value.isLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            when (val result = safeApiCall(parser) { api.qr() }) {
                is AppResult.Success -> _uiState.update {
                    it.copy(isLoading = false, qr = result.value, errorMessage = null)
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isLoading = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun resetQr() {
        if (_uiState.value.isMutating) return
        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null) }
            when (val result = safeApiCall(parser) { api.resetQr() }) {
                is AppResult.Success -> {
                    _uiState.update {
                        it.copy(
                            isMutating = false,
                            message = result.value.message ?: "School attendance QR reset.",
                        )
                    }
                    load()
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isMutating = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }
}

data class AdminStaffAttendanceQrUiState(
    val isLoading: Boolean = false,
    val isMutating: Boolean = false,
    val qr: AdminStaffAttendanceQrDto? = null,
    val errorMessage: String? = null,
    val message: String? = null,
)

/**
 * The historical route name is retained for source compatibility, but the
 * administrator entry point now opens the complete Staff Attendance workspace
 * (Daily, Monthly, Reviews and Settings) instead of a QR-only page.
 */
@Composable
internal fun AdminStaffAttendanceQrScreen(
    state: AdminStaffAttendanceQrUiState,
    onRefresh: () -> Unit,
    onReset: () -> Unit,
    onClose: () -> Unit,
) {
    // Keep parameters in the signature because AuthorizedShell owns the legacy
    // route contract. QR loading/reset remains available through the full admin
    // attendance settings workflow.
    @Suppress("UNUSED_VARIABLE")
    val compatibilityState = state
    @Suppress("UNUSED_VARIABLE")
    val compatibilityRefresh = onRefresh
    @Suppress("UNUSED_VARIABLE")
    val compatibilityReset = onReset

    AdminStaffAttendanceHubScreen(
        onBack = onClose,
        onOpenQr = {},
    )
}
