package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.RiskApi
import online.educoreng.educore.core.network.dto.RiskComputeRequestDto
import online.educoreng.educore.core.network.dto.RiskConfigDto
import online.educoreng.educore.core.network.dto.RiskConfigUpdateRequestDto
import online.educoreng.educore.core.network.dto.RiskDetailResponseDto
import online.educoreng.educore.core.network.dto.RiskFlagDto
import online.educoreng.educore.core.network.dto.RiskInterventionRequestDto
import online.educoreng.educore.core.network.dto.RiskListResponseDto
import retrofit2.HttpException

internal enum class RiskConfigField {
    ACADEMIC_THRESHOLD,
    ATTENDANCE_THRESHOLD,
    SUBJECTS_FAILED_THRESHOLD,
    ACADEMIC_WEIGHT,
    ATTENDANCE_WEIGHT,
    FEE_WEIGHT,
}

internal data class RiskConfigDraft(
    val academicThreshold: String = "45",
    val attendanceThreshold: String = "75",
    val subjectsFailedThreshold: String = "2",
    val includeFeeRisk: Boolean = true,
    val academicWeight: String = "40",
    val attendanceWeight: String = "35",
    val feeWeight: String = "25",
) {
    val weightTotal: Int
        get() = listOf(academicWeight, attendanceWeight, feeWeight).sumOf { it.toIntOrNull() ?: 0 }
}

internal data class RiskUiState(
    val workspace: RiskListResponseDto? = null,
    val flags: List<RiskFlagDto> = emptyList(),
    val selectedTermId: Long? = null,
    val selectedStatus: String = "open",
    val selectedRiskLevel: String = "all",
    val page: Int = 1,
    val lastPage: Int = 1,
    val filteredTotal: Int = 0,
    val hasMore: Boolean = false,
    val detail: RiskDetailResponseDto? = null,
    val interventionNote: String = "",
    val configDraft: RiskConfigDraft = RiskConfigDraft(),
    val isLoading: Boolean = false,
    val isLoadingMore: Boolean = false,
    val isDetailLoading: Boolean = false,
    val isComputing: Boolean = false,
    val isMutating: Boolean = false,
    val isSavingConfig: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val canCompute: Boolean get() = workspace?.capabilities?.compute == true && selectedTermId != null
    val canAcknowledge: Boolean get() = workspace?.capabilities?.acknowledge == true && detail?.flag?.status == "open"
    val canResolve: Boolean get() = workspace?.capabilities?.resolve == true && detail?.flag?.status != "resolved"
    val canManageConfig: Boolean get() = workspace?.capabilities?.manageConfig == true
}

@HiltViewModel
internal class RiskViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api: RiskApi = factory.create(RiskApi::class.java)
    private val _uiState = MutableStateFlow(RiskUiState())
    val uiState: StateFlow<RiskUiState> = _uiState.asStateFlow()

    fun load() = loadPage(reset = true)

    fun loadMore() {
        val state = _uiState.value
        if (!state.hasMore || state.isLoading || state.isLoadingMore) return
        loadPage(reset = false)
    }

    fun selectTerm(termId: Long?) {
        if (_uiState.value.selectedTermId == termId) return
        _uiState.update { it.copy(selectedTermId = termId, detail = null, interventionNote = "") }
        loadPage(reset = true)
    }

    fun selectStatus(status: String) {
        if (_uiState.value.selectedStatus == status) return
        _uiState.update { it.copy(selectedStatus = status, detail = null, interventionNote = "") }
        loadPage(reset = true)
    }

    fun selectRiskLevel(level: String) {
        if (_uiState.value.selectedRiskLevel == level) return
        _uiState.update { it.copy(selectedRiskLevel = level, detail = null, interventionNote = "") }
        loadPage(reset = true)
    }

    private fun loadPage(reset: Boolean) {
        val state = _uiState.value
        if (reset && state.isLoading) return
        if (!reset && state.isLoadingMore) return
        val targetPage = if (reset) 1 else state.page + 1

        viewModelScope.launch {
            _uiState.update {
                if (reset) it.copy(isLoading = true, isLoadingMore = false, errorMessage = null)
                else it.copy(isLoadingMore = true, errorMessage = null)
            }
            runCatching {
                api.flags(
                    termId = state.selectedTermId,
                    status = state.selectedStatus,
                    riskLevel = state.selectedRiskLevel,
                    page = targetPage,
                    perPage = PAGE_SIZE,
                )
            }.onSuccess { response ->
                _uiState.update { current ->
                    current.copy(
                        workspace = response,
                        flags = if (reset) response.flags else (current.flags + response.flags).distinctBy(RiskFlagDto::id),
                        selectedTermId = response.selected.termId,
                        selectedStatus = response.selected.status,
                        selectedRiskLevel = response.selected.riskLevel,
                        page = response.meta.page,
                        lastPage = response.meta.lastPage,
                        filteredTotal = response.meta.total,
                        hasMore = response.meta.hasMore,
                        configDraft = if (current.workspace == null) response.config.toDraft() else current.configDraft,
                        isLoading = false,
                        isLoadingMore = false,
                    )
                }
            }.onFailure { error ->
                _uiState.update {
                    it.copy(isLoading = false, isLoadingMore = false, errorMessage = error.riskMessage())
                }
            }
        }
    }

    fun openFlag(flagId: Long) {
        if (_uiState.value.isDetailLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isDetailLoading = true, errorMessage = null, detail = null, interventionNote = "") }
            runCatching { api.flag(flagId) }
                .onSuccess { response ->
                    _uiState.update {
                        it.copy(
                            detail = response,
                            interventionNote = response.flag.interventionNote.orEmpty(),
                            isDetailLoading = false,
                        )
                    }
                }
                .onFailure { error ->
                    _uiState.update { it.copy(isDetailLoading = false, errorMessage = error.riskMessage()) }
                }
        }
    }

    fun clearDetail() = _uiState.update { it.copy(detail = null, interventionNote = "", errorMessage = null) }

    fun setInterventionNote(value: String) = _uiState.update {
        it.copy(interventionNote = value.take(MAX_INTERVENTION_LENGTH), errorMessage = null)
    }

    fun acknowledge() = mutateFlag(acknowledge = true)

    fun resolve() = mutateFlag(acknowledge = false)

    private fun mutateFlag(acknowledge: Boolean) {
        val state = _uiState.value
        val flag = state.detail?.flag ?: return
        if (state.isMutating) return
        if (acknowledge && !state.canAcknowledge) return
        if (!acknowledge && !state.canResolve) return

        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null, message = null) }
            val request = RiskInterventionRequestDto(state.interventionNote.trim().takeIf(String::isNotBlank))
            runCatching {
                if (acknowledge) api.acknowledge(flag.id, request) else api.resolve(flag.id, request)
            }.onSuccess { response ->
                _uiState.update { current ->
                    current.copy(
                        detail = current.detail?.copy(flag = response.flag),
                        interventionNote = response.flag.interventionNote.orEmpty(),
                        isMutating = false,
                        message = response.message,
                    )
                }
                loadPage(reset = true)
            }.onFailure { error ->
                _uiState.update { it.copy(isMutating = false, errorMessage = error.riskMessage()) }
            }
        }
    }

    fun compute() {
        val state = _uiState.value
        val termId = state.selectedTermId ?: return
        if (!state.canCompute || state.isComputing) return

        viewModelScope.launch {
            _uiState.update { it.copy(isComputing = true, errorMessage = null, message = null) }
            runCatching { api.compute(RiskComputeRequestDto(termId = termId)) }
                .onSuccess { response ->
                    _uiState.update { it.copy(isComputing = false, message = response.message) }
                    loadPage(reset = true)
                }
                .onFailure { error ->
                    _uiState.update { it.copy(isComputing = false, errorMessage = error.riskMessage()) }
                }
        }
    }

    fun updateConfig(field: RiskConfigField, value: String) = _uiState.update { state ->
        val draft = when (field) {
            RiskConfigField.ACADEMIC_THRESHOLD -> state.configDraft.copy(academicThreshold = value)
            RiskConfigField.ATTENDANCE_THRESHOLD -> state.configDraft.copy(attendanceThreshold = value)
            RiskConfigField.SUBJECTS_FAILED_THRESHOLD -> state.configDraft.copy(subjectsFailedThreshold = value)
            RiskConfigField.ACADEMIC_WEIGHT -> state.configDraft.copy(academicWeight = value)
            RiskConfigField.ATTENDANCE_WEIGHT -> state.configDraft.copy(attendanceWeight = value)
            RiskConfigField.FEE_WEIGHT -> state.configDraft.copy(feeWeight = value)
        }
        state.copy(configDraft = draft, errorMessage = null)
    }

    fun setIncludeFeeRisk(value: Boolean) = _uiState.update {
        it.copy(configDraft = it.configDraft.copy(includeFeeRisk = value), errorMessage = null)
    }

    fun resetConfigDraft() {
        val config = _uiState.value.workspace?.config ?: return
        _uiState.update { it.copy(configDraft = config.toDraft(), errorMessage = null) }
    }

    fun saveConfig() {
        val state = _uiState.value
        if (!state.canManageConfig || state.isSavingConfig) return
        val request = state.configDraft.toRequestOrNull()
        if (request == null) {
            _uiState.update { it.copy(errorMessage = "Enter valid thresholds and whole-number weights before saving.") }
            return
        }
        if (state.configDraft.weightTotal != 100) {
            _uiState.update { it.copy(errorMessage = "Academic, attendance and fee weights must total 100%.") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isSavingConfig = true, errorMessage = null, message = null) }
            runCatching { api.updateConfig(request) }
                .onSuccess { response ->
                    _uiState.update { current ->
                        current.copy(
                            workspace = current.workspace?.copy(config = response.config),
                            configDraft = response.config.toDraft(),
                            isSavingConfig = false,
                            message = response.message,
                        )
                    }
                }
                .onFailure { error ->
                    _uiState.update { it.copy(isSavingConfig = false, errorMessage = error.riskMessage()) }
                }
        }
    }

    fun consumeMessage() = _uiState.update { it.copy(message = null) }
    fun clearError() = _uiState.update { it.copy(errorMessage = null) }

    private companion object {
        const val PAGE_SIZE = 30
        const val MAX_INTERVENTION_LENGTH = 1000
    }
}

private fun RiskConfigDto.toDraft() = RiskConfigDraft(
    academicThreshold = academicThreshold.cleanNumber(),
    attendanceThreshold = attendanceThreshold.cleanNumber(),
    subjectsFailedThreshold = subjectsFailedThreshold.toString(),
    includeFeeRisk = includeFeeRisk,
    academicWeight = academicWeight.toString(),
    attendanceWeight = attendanceWeight.toString(),
    feeWeight = feeWeight.toString(),
)

private fun RiskConfigDraft.toRequestOrNull(): RiskConfigUpdateRequestDto? {
    val academicThreshold = academicThreshold.toDoubleOrNull() ?: return null
    val attendanceThreshold = attendanceThreshold.toDoubleOrNull() ?: return null
    val subjectsFailedThreshold = subjectsFailedThreshold.toIntOrNull() ?: return null
    val academicWeight = academicWeight.toIntOrNull() ?: return null
    val attendanceWeight = attendanceWeight.toIntOrNull() ?: return null
    val feeWeight = feeWeight.toIntOrNull() ?: return null

    if (academicThreshold !in 0.0..100.0 || attendanceThreshold !in 0.0..100.0) return null
    if (subjectsFailedThreshold !in 1..50) return null
    if (academicWeight !in 0..100 || attendanceWeight !in 0..100 || feeWeight !in 0..100) return null

    return RiskConfigUpdateRequestDto(
        academicThreshold = academicThreshold,
        attendanceThreshold = attendanceThreshold,
        subjectsFailedThreshold = subjectsFailedThreshold,
        includeFeeRisk = includeFeeRisk,
        academicWeight = academicWeight,
        attendanceWeight = attendanceWeight,
        feeWeight = feeWeight,
    )
}

private fun Double.cleanNumber(): String = if (this % 1.0 == 0.0) toInt().toString() else toString()

private fun Throwable.riskMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "Your account is not permitted to use student risk intelligence."
        404 -> "That risk record is no longer available. Refresh the workspace."
        409 -> "That risk flag changed before this action completed. Refresh and try again."
        422 -> "The risk request contains invalid thresholds, filters or intervention information."
        else -> "The risk service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the risk service. Check your connection and try again."
}
