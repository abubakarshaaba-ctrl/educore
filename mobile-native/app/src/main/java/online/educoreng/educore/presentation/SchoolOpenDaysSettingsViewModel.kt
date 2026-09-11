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
import online.educoreng.educore.core.network.AdminStaffAttendanceApi
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.dto.AdminAttendanceSettingsRequestDto
import online.educoreng.educore.core.network.dto.AdminStaffAttendanceSettingsDto
import online.educoreng.educore.core.network.safeApiCall

@HiltViewModel
class SchoolOpenDaysSettingsViewModel @Inject constructor(
    factory: ApiClientFactory,
    moshi: Moshi,
) : ViewModel() {
    private val api = factory.create(AdminStaffAttendanceApi::class.java)
    private val parser = moshi
    private val _uiState = MutableStateFlow(SchoolOpenDaysSettingsUiState())
    val uiState: StateFlow<SchoolOpenDaysSettingsUiState> = _uiState.asStateFlow()

    init {
        load()
    }

    fun load() {
        if (_uiState.value.isLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            when (val result = safeApiCall(parser) { api.settings() }) {
                is AppResult.Success -> _uiState.update {
                    it.copy(
                        isLoading = false,
                        settings = result.value.settings,
                        selectedDays = normalizeDays(result.value.settings.schoolOpenDays),
                        errorMessage = null,
                    )
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isLoading = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun toggle(day: String) {
        if (_uiState.value.isSaving) return
        val normalized = day.lowercase()
        _uiState.update { state ->
            val selected = state.selectedDays.toMutableSet()
            if (normalized in selected) {
                if (selected.size > 1) selected.remove(normalized)
            } else {
                selected.add(normalized)
            }
            state.copy(selectedDays = SCHOOL_DAYS.filter(selected::contains), message = null, errorMessage = null)
        }
    }

    fun save() {
        if (_uiState.value.isSaving) return
        val settings = _uiState.value.settings ?: run {
            _uiState.update { it.copy(errorMessage = "Attendance settings are not loaded yet.") }
            return
        }
        val days = _uiState.value.selectedDays
        if (days.isEmpty()) {
            _uiState.update { it.copy(errorMessage = "Select at least one school opening day.") }
            return
        }
        val resumption = settings.resumptionTime?.take(5)
        val closing = settings.closingTime?.take(5)
        val lat = settings.geoLat
        val lng = settings.geoLng
        val radius = settings.geoRadiusMeters
        if (resumption.isNullOrBlank() || closing.isNullOrBlank() || lat == null || lng == null || radius == null || radius <= 0) {
            _uiState.update {
                it.copy(errorMessage = "Complete the attendance time and school location settings before saving school opening days.")
            }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, message = null, errorMessage = null) }
            when (val result = safeApiCall(parser) {
                api.updateSettings(
                    AdminAttendanceSettingsRequestDto(
                        resumptionTime = resumption,
                        graceMinutes = settings.graceMinutes,
                        closingTime = closing,
                        geoEnabled = settings.geoEnabled,
                        geoLat = lat,
                        geoLng = lng,
                        geoRadiusMeters = radius,
                        schoolOpenDays = days,
                    )
                )
            }) {
                is AppResult.Success -> {
                    val updated = result.value.settings ?: settings.copy(schoolOpenDays = days)
                    _uiState.update {
                        it.copy(
                            isSaving = false,
                            settings = updated,
                            selectedDays = normalizeDays(updated.schoolOpenDays),
                            message = result.value.message ?: "School opening days saved.",
                            errorMessage = null,
                        )
                    }
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isSaving = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun clearMessage() = _uiState.update { it.copy(message = null, errorMessage = null) }

    private fun normalizeDays(days: List<String>): List<String> {
        val selected = days.map(String::lowercase).toSet()
        return SCHOOL_DAYS.filter(selected::contains).ifEmpty { SCHOOL_DAYS.take(5) }
    }

    companion object {
        val SCHOOL_DAYS = listOf("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday")
    }
}

data class SchoolOpenDaysSettingsUiState(
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val settings: AdminStaffAttendanceSettingsDto? = null,
    val selectedDays: List<String> = SchoolOpenDaysSettingsViewModel.SCHOOL_DAYS.take(5),
    val message: String? = null,
    val errorMessage: String? = null,
)
