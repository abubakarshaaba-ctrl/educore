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
import online.educoreng.educore.core.network.HealthApi
import online.educoreng.educore.core.network.dto.HealthDashboardDto
import online.educoreng.educore.core.network.dto.HealthDetailDto
import online.educoreng.educore.core.network.dto.HealthRecordDto
import online.educoreng.educore.core.network.dto.HealthRecordUpdateRequestDto
import online.educoreng.educore.core.network.dto.HealthStudentDto
import retrofit2.HttpException

internal enum class HealthField {
    BLOOD_GROUP,
    GENOTYPE,
    ALLERGIES,
    CHRONIC_CONDITIONS,
    CURRENT_MEDICATIONS,
    DISABILITY,
    EMERGENCY_CONTACT_NAME,
    EMERGENCY_CONTACT_PHONE,
    EMERGENCY_CONTACT_RELATIONSHIP,
    DOCTOR_NAME,
    DOCTOR_PHONE,
    NOTES,
}

internal data class HealthDraft(
    val bloodGroup: String = "",
    val genotype: String = "",
    val allergies: String = "",
    val chronicConditions: String = "",
    val currentMedications: String = "",
    val disability: String = "",
    val emergencyContactName: String = "",
    val emergencyContactPhone: String = "",
    val emergencyContactRelationship: String = "",
    val doctorName: String = "",
    val doctorPhone: String = "",
    val notes: String = "",
) {
    fun toRequest() = HealthRecordUpdateRequestDto(
        bloodGroup = bloodGroup.clean(),
        genotype = genotype.clean(),
        allergies = allergies.clean(),
        chronicConditions = chronicConditions.clean(),
        currentMedications = currentMedications.clean(),
        disability = disability.clean(),
        emergencyContactName = emergencyContactName.clean(),
        emergencyContactPhone = emergencyContactPhone.clean(),
        emergencyContactRelationship = emergencyContactRelationship.clean(),
        doctorName = doctorName.clean(),
        doctorPhone = doctorPhone.clean(),
        notes = notes.clean(),
    )

    companion object {
        fun from(record: HealthRecordDto) = HealthDraft(
            bloodGroup = record.bloodGroup.orEmpty(),
            genotype = record.genotype.orEmpty(),
            allergies = record.allergies.orEmpty(),
            chronicConditions = record.chronicConditions.orEmpty(),
            currentMedications = record.currentMedications.orEmpty(),
            disability = record.disability.orEmpty(),
            emergencyContactName = record.emergencyContactName.orEmpty(),
            emergencyContactPhone = record.emergencyContactPhone.orEmpty(),
            emergencyContactRelationship = record.emergencyContactRelationship.orEmpty(),
            doctorName = record.doctorName.orEmpty(),
            doctorPhone = record.doctorPhone.orEmpty(),
            notes = record.notes.orEmpty(),
        )
    }
}

internal data class HealthUiState(
    val dashboard: HealthDashboardDto? = null,
    val students: List<HealthStudentDto> = emptyList(),
    val detail: HealthDetailDto? = null,
    val draft: HealthDraft = HealthDraft(),
    val query: String = "",
    val isLoading: Boolean = false,
    val isLoadingMore: Boolean = false,
    val isLoadingDetail: Boolean = false,
    val isSaving: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val canManage: Boolean get() = detail?.capabilities?.manage ?: dashboard?.capabilities?.manage == true
    val hasMore: Boolean get() = dashboard?.meta?.hasMore == true
}

@HiltViewModel
internal class HealthViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api: HealthApi = factory.create(HealthApi::class.java)
    private val _uiState = MutableStateFlow(HealthUiState())
    val uiState: StateFlow<HealthUiState> = _uiState.asStateFlow()

    fun load() = loadPage(reset = true)

    fun setQuery(value: String) {
        _uiState.update { it.copy(query = value.take(120), errorMessage = null) }
    }

    fun search() = loadPage(reset = true)

    fun loadMore() = loadPage(reset = false)

    fun open(studentId: Long) {
        if (_uiState.value.isLoadingDetail) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoadingDetail = true, errorMessage = null, message = null) }
            runCatching { api.show(studentId) }
                .onSuccess { detail ->
                    _uiState.update {
                        it.copy(
                            detail = detail,
                            draft = HealthDraft.from(detail.record),
                            isLoadingDetail = false,
                        )
                    }
                }
                .onFailure { error ->
                    _uiState.update {
                        it.copy(isLoadingDetail = false, errorMessage = error.healthMessage())
                    }
                }
        }
    }

    fun closeDetail() = _uiState.update {
        it.copy(detail = null, draft = HealthDraft(), errorMessage = null, message = null)
    }

    fun updateField(field: HealthField, value: String) = _uiState.update { state ->
        val limited = value.take(2000)
        val next = when (field) {
            HealthField.BLOOD_GROUP -> state.draft.copy(bloodGroup = value.take(5))
            HealthField.GENOTYPE -> state.draft.copy(genotype = value.take(5))
            HealthField.ALLERGIES -> state.draft.copy(allergies = limited)
            HealthField.CHRONIC_CONDITIONS -> state.draft.copy(chronicConditions = limited)
            HealthField.CURRENT_MEDICATIONS -> state.draft.copy(currentMedications = limited)
            HealthField.DISABILITY -> state.draft.copy(disability = limited)
            HealthField.EMERGENCY_CONTACT_NAME -> state.draft.copy(emergencyContactName = limited)
            HealthField.EMERGENCY_CONTACT_PHONE -> state.draft.copy(emergencyContactPhone = value.take(30))
            HealthField.EMERGENCY_CONTACT_RELATIONSHIP -> state.draft.copy(emergencyContactRelationship = limited)
            HealthField.DOCTOR_NAME -> state.draft.copy(doctorName = limited)
            HealthField.DOCTOR_PHONE -> state.draft.copy(doctorPhone = value.take(30))
            HealthField.NOTES -> state.draft.copy(notes = limited)
        }
        state.copy(draft = next, errorMessage = null, message = null)
    }

    fun save() {
        val state = _uiState.value
        val detail = state.detail ?: return
        if (!state.canManage || state.isSaving) return

        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            runCatching { api.update(detail.student.id, state.draft.toRequest()) }
                .onSuccess { response ->
                    _uiState.update { current ->
                        current.copy(
                            isSaving = false,
                            detail = current.detail?.copy(record = response.record),
                            draft = HealthDraft.from(response.record),
                            message = response.message,
                        )
                    }
                    loadPage(reset = true, preserveDetail = true, preserveMessage = true)
                }
                .onFailure { error ->
                    _uiState.update { it.copy(isSaving = false, errorMessage = error.healthMessage()) }
                }
        }
    }

    private fun loadPage(
        reset: Boolean,
        preserveDetail: Boolean = false,
        preserveMessage: Boolean = false,
    ) {
        val current = _uiState.value
        if ((reset && current.isLoading) || (!reset && (current.isLoadingMore || !current.hasMore))) return
        val page = if (reset) 1 else (current.dashboard?.meta?.page ?: 1) + 1

        viewModelScope.launch {
            _uiState.update {
                it.copy(
                    isLoading = reset,
                    isLoadingMore = !reset,
                    errorMessage = null,
                    detail = if (preserveDetail) it.detail else it.detail,
                    message = if (preserveMessage) it.message else null,
                )
            }
            runCatching {
                api.dashboard(
                    search = _uiState.value.query.trim().ifBlank { null },
                    page = page,
                )
            }.onSuccess { dashboard ->
                _uiState.update { state ->
                    val students = if (reset) dashboard.students else (state.students + dashboard.students).distinctBy { it.id }
                    state.copy(
                        dashboard = dashboard,
                        students = students,
                        query = dashboard.selected.search,
                        isLoading = false,
                        isLoadingMore = false,
                    )
                }
            }.onFailure { error ->
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        isLoadingMore = false,
                        errorMessage = error.healthMessage(),
                    )
                }
            }
        }
    }
}

private fun String.clean(): String? = trim().ifBlank { null }

private fun Throwable.healthMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "Your account is not permitted to access or edit health records."
        404 -> "This student health record is no longer available."
        422 -> "Check the health record fields and try again."
        else -> "The health service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the health service. Check your connection and try again."
}
