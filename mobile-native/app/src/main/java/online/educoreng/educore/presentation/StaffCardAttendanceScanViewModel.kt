package online.educoreng.educore.presentation

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
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.StaffCardAttendanceApi
import online.educoreng.educore.core.network.dto.StaffCardAttendanceScanRequestDto
import online.educoreng.educore.core.network.safeApiCall

@HiltViewModel
class StaffCardAttendanceScanViewModel @Inject constructor(
    factory: ApiClientFactory,
    private val moshi: Moshi,
) : ViewModel() {
    private val api = factory.create(StaffCardAttendanceApi::class.java)
    private val _uiState = MutableStateFlow(StaffCardAttendanceScanUiState())
    val uiState: StateFlow<StaffCardAttendanceScanUiState> = _uiState.asStateFlow()

    fun scan(
        qrToken: String,
        latitude: Double? = null,
        longitude: Double? = null,
        accuracy: Double? = null,
    ) {
        if (_uiState.value.isSaving || qrToken.isBlank()) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, message = null, errorMessage = null) }
            when (val result = safeApiCall(moshi) {
                api.scan(
                    StaffCardAttendanceScanRequestDto(
                        staffQrToken = qrToken.trim(),
                        latitude = latitude,
                        longitude = longitude,
                        accuracy = accuracy,
                        device = "android",
                    )
                )
            }) {
                is AppResult.Success -> _uiState.update {
                    it.copy(
                        isSaving = false,
                        action = result.value.action,
                        serverTimestamp = result.value.serverTimestamp,
                        message = result.value.message,
                        errorMessage = null,
                    )
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isSaving = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun clearMessage() = _uiState.update { it.copy(message = null, errorMessage = null) }
}

data class StaffCardAttendanceScanUiState(
    val isSaving: Boolean = false,
    val action: String? = null,
    val serverTimestamp: String? = null,
    val message: String? = null,
    val errorMessage: String? = null,
)
