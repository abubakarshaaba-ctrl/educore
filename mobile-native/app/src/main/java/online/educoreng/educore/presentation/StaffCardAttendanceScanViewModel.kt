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

    fun scanStaffCard(
        qrToken: String,
        latitude: Double? = null,
        longitude: Double? = null,
        accuracy: Double? = null,
    ) {
        submit(
            StaffCardAttendanceScanRequestDto(
                staffQrToken = qrToken.trim(),
                latitude = latitude,
                longitude = longitude,
                accuracy = accuracy,
                device = "android",
            )
        )
    }

    fun scanSchoolQrFallback(
        schoolQrToken: String,
        staffId: String,
        latitude: Double? = null,
        longitude: Double? = null,
        accuracy: Double? = null,
    ) {
        if (staffId.trim().isBlank()) {
            _uiState.update { it.copy(errorMessage = "Enter the Staff ID when using the school QR fallback.") }
            return
        }
        submit(
            StaffCardAttendanceScanRequestDto(
                schoolQrToken = schoolQrToken.trim(),
                staffId = staffId.trim(),
                latitude = latitude,
                longitude = longitude,
                accuracy = accuracy,
                device = "android",
            )
        )
    }

    /** Compatibility for current call sites while the UI is migrated. */
    fun scan(
        qrToken: String,
        latitude: Double? = null,
        longitude: Double? = null,
        accuracy: Double? = null,
    ) = scanStaffCard(qrToken, latitude, longitude, accuracy)

    private fun submit(request: StaffCardAttendanceScanRequestDto) {
        if (_uiState.value.isSaving) return
        val hasStaffCard = !request.staffQrToken.isNullOrBlank()
        val hasSchoolFallback = !request.schoolQrToken.isNullOrBlank() && !request.staffId.isNullOrBlank()
        if (!hasStaffCard && !hasSchoolFallback) return

        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, message = null, errorMessage = null) }
            when (val result = safeApiCall(moshi) { api.scan(request) }) {
                is AppResult.Success -> _uiState.update {
                    it.copy(
                        isSaving = false,
                        action = result.value.action,
                        scanMethod = result.value.scanMethod,
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
    val scanMethod: String? = null,
    val serverTimestamp: String? = null,
    val message: String? = null,
    val errorMessage: String? = null,
)
