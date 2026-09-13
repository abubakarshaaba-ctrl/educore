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
import online.educoreng.educore.core.network.AdmissionsApi
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.dto.AdmissionItemDto
import online.educoreng.educore.core.network.dto.AdmissionsWorkspaceDto
import online.educoreng.educore.core.network.dto.CreateAdmissionRequestDto
import online.educoreng.educore.core.network.dto.RecordAdmissionInterviewRequestDto
import online.educoreng.educore.core.network.dto.ScheduleAdmissionInterviewRequestDto
import online.educoreng.educore.core.network.dto.UpdateAdmissionStatusRequestDto
import retrofit2.HttpException

data class AdmissionCreateDraft(
    val firstName: String = "",
    val lastName: String = "",
    val otherNames: String = "",
    val dateOfBirth: String = "",
    val gender: String = "",
    val classLevelId: Long? = null,
    val guardianName: String = "",
    val guardianPhone: String = "",
    val guardianEmail: String = "",
    val guardianRelationship: String = "",
    val address: String = "",
    val notes: String = "",
) {
    val valid: Boolean
        get() = firstName.isNotBlank() && lastName.isNotBlank() && dateOfBirth.isNotBlank()
            && gender in setOf("male", "female") && guardianName.isNotBlank()
            && guardianPhone.isNotBlank() && guardianRelationship.isNotBlank()
}

enum class AdmissionCreateField {
    FIRST_NAME, LAST_NAME, OTHER_NAMES, DATE_OF_BIRTH, GUARDIAN_NAME,
    GUARDIAN_PHONE, GUARDIAN_EMAIL, GUARDIAN_RELATIONSHIP, ADDRESS, NOTES,
}

internal data class AdmissionsUiState(
    val workspace: AdmissionsWorkspaceDto? = null,
    val admissions: List<AdmissionItemDto> = emptyList(),
    val selectedAdmission: AdmissionItemDto? = null,
    val searchQuery: String = "",
    val selectedStatus: String = "all",
    val isLoading: Boolean = false,
    val isLoadingMore: Boolean = false,
    val isSaving: Boolean = false,
    val isCreateOpen: Boolean = false,
    val createDraft: AdmissionCreateDraft = AdmissionCreateDraft(),
    val statusDraft: String = "pending",
    val classArmDraft: Long? = null,
    val reviewNotesDraft: String = "",
    val interviewDateDraft: String = "",
    val interviewNotesDraft: String = "",
    val interviewScoreDraft: String = "",
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val hasMore: Boolean get() = workspace?.meta?.hasMore == true
    val filteredTotal: Int get() = workspace?.meta?.total ?: admissions.size
    val canCreate: Boolean get() = workspace?.capabilities?.create == true
    val canChangeStatus: Boolean get() = workspace?.capabilities?.changeStatus == true
    val canScheduleInterview: Boolean get() = workspace?.capabilities?.scheduleInterview == true
    val canRecordInterview: Boolean get() = workspace?.capabilities?.recordInterview == true
}

@HiltViewModel
internal class AdmissionsViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api: AdmissionsApi = factory.create(AdmissionsApi::class.java)
    private val _uiState = MutableStateFlow(AdmissionsUiState())
    val uiState: StateFlow<AdmissionsUiState> = _uiState.asStateFlow()

    fun load() = loadPage(reset = true)

    fun setSearch(value: String) { _uiState.update { it.copy(searchQuery = value.take(120), errorMessage = null) } }
    fun search() = loadPage(reset = true)

    fun selectStatus(status: String) {
        if (status == _uiState.value.selectedStatus) return
        _uiState.update { it.copy(selectedStatus = status, errorMessage = null) }
        loadPage(reset = true)
    }

    fun loadMore() = loadPage(reset = false)

    fun open(admission: AdmissionItemDto) {
        _uiState.update {
            it.copy(
                selectedAdmission = admission,
                statusDraft = admission.status,
                classArmDraft = null,
                reviewNotesDraft = admission.notes.orEmpty(),
                interviewDateDraft = admission.interviewDate.orEmpty(),
                interviewNotesDraft = admission.interviewNotes.orEmpty(),
                interviewScoreDraft = admission.interviewScore?.toString().orEmpty(),
                message = null,
                errorMessage = null,
            )
        }
        viewModelScope.launch {
            runCatching { api.show(admission.id) }.onSuccess { response ->
                _uiState.update { state ->
                    if (state.selectedAdmission?.id != response.admission.id) state else state.copy(
                        selectedAdmission = response.admission,
                        statusDraft = response.admission.status,
                        reviewNotesDraft = response.admission.notes.orEmpty(),
                        interviewDateDraft = response.admission.interviewDate.orEmpty(),
                        interviewNotesDraft = response.admission.interviewNotes.orEmpty(),
                        interviewScoreDraft = response.admission.interviewScore?.toString().orEmpty(),
                    )
                }
            }.onFailure { error ->
                _uiState.update { state ->
                    if (state.selectedAdmission?.id == admission.id) state.copy(errorMessage = error.admissionMessage()) else state
                }
            }
        }
    }

    fun closeDetail() = _uiState.update { it.copy(selectedAdmission = null, message = null, errorMessage = null) }
    fun startCreate() = _uiState.update { it.copy(isCreateOpen = true, createDraft = AdmissionCreateDraft(), errorMessage = null, message = null) }
    fun closeCreate() = _uiState.update { it.copy(isCreateOpen = false, errorMessage = null) }

    fun updateCreate(field: AdmissionCreateField, value: String) = _uiState.update { state ->
        val draft = when (field) {
            AdmissionCreateField.FIRST_NAME -> state.createDraft.copy(firstName = value)
            AdmissionCreateField.LAST_NAME -> state.createDraft.copy(lastName = value)
            AdmissionCreateField.OTHER_NAMES -> state.createDraft.copy(otherNames = value)
            AdmissionCreateField.DATE_OF_BIRTH -> state.createDraft.copy(dateOfBirth = value)
            AdmissionCreateField.GUARDIAN_NAME -> state.createDraft.copy(guardianName = value)
            AdmissionCreateField.GUARDIAN_PHONE -> state.createDraft.copy(guardianPhone = value)
            AdmissionCreateField.GUARDIAN_EMAIL -> state.createDraft.copy(guardianEmail = value)
            AdmissionCreateField.GUARDIAN_RELATIONSHIP -> state.createDraft.copy(guardianRelationship = value)
            AdmissionCreateField.ADDRESS -> state.createDraft.copy(address = value)
            AdmissionCreateField.NOTES -> state.createDraft.copy(notes = value)
        }
        state.copy(createDraft = draft, errorMessage = null)
    }

    fun selectCreateGender(value: String) = _uiState.update { it.copy(createDraft = it.createDraft.copy(gender = value), errorMessage = null) }
    fun selectCreateClassLevel(id: Long?) = _uiState.update { it.copy(createDraft = it.createDraft.copy(classLevelId = id), errorMessage = null) }
    fun setStatusDraft(value: String) = _uiState.update { it.copy(statusDraft = value, errorMessage = null) }
    fun setClassArmDraft(value: Long?) = _uiState.update { it.copy(classArmDraft = value, errorMessage = null) }
    fun setReviewNotes(value: String) = _uiState.update { it.copy(reviewNotesDraft = value.take(4000), errorMessage = null) }
    fun setInterviewDate(value: String) = _uiState.update { it.copy(interviewDateDraft = value.take(10), errorMessage = null) }
    fun setInterviewNotes(value: String) = _uiState.update { it.copy(interviewNotesDraft = value.take(4000), errorMessage = null) }
    fun setInterviewScore(value: String) = _uiState.update { it.copy(interviewScoreDraft = value.take(6), errorMessage = null) }

    fun create() {
        val state = _uiState.value
        val draft = state.createDraft
        if (!draft.valid || state.isSaving) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            runCatching {
                api.create(CreateAdmissionRequestDto(
                    firstName = draft.firstName.trim(), lastName = draft.lastName.trim(), otherNames = draft.otherNames.trim().ifBlank { null },
                    dateOfBirth = draft.dateOfBirth.trim(), gender = draft.gender, applyingForClassLevelId = draft.classLevelId,
                    guardianName = draft.guardianName.trim(), guardianPhone = draft.guardianPhone.trim(), guardianEmail = draft.guardianEmail.trim().ifBlank { null },
                    guardianRelationship = draft.guardianRelationship.trim(), address = draft.address.trim().ifBlank { null }, notes = draft.notes.trim().ifBlank { null },
                ))
            }.onSuccess { response ->
                _uiState.update { it.copy(isSaving = false, isCreateOpen = false, createDraft = AdmissionCreateDraft(), message = response.message) }
                loadPage(reset = true, preserveMessage = true)
            }.onFailure { error -> _uiState.update { it.copy(isSaving = false, errorMessage = error.admissionMessage()) } }
        }
    }

    fun saveStatus() {
        val state = _uiState.value
        val admission = state.selectedAdmission ?: return
        if (!state.canChangeStatus || state.isSaving) return
        if (state.statusDraft == "admitted" && admission.enrolledStudentId == null && state.classArmDraft == null) {
            _uiState.update { it.copy(errorMessage = "Choose the class arm for the admitted student before saving.") }
            return
        }
        viewModelScope.launch {
            mutate(admission.id) {
                api.updateStatus(admission.id, UpdateAdmissionStatusRequestDto(
                    status = state.statusDraft,
                    notes = state.reviewNotesDraft.trim().ifBlank { null },
                    classArmId = state.classArmDraft,
                )).let { it.message to it.admission }
            }
        }
    }

    fun scheduleInterview() {
        val state = _uiState.value
        val admission = state.selectedAdmission ?: return
        if (!state.canScheduleInterview || state.isSaving) return
        if (state.interviewDateDraft.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Enter the interview date in YYYY-MM-DD format.") }
            return
        }
        viewModelScope.launch {
            mutate(admission.id) {
                api.scheduleInterview(admission.id, ScheduleAdmissionInterviewRequestDto(
                    interviewDate = state.interviewDateDraft.trim(),
                    interviewNotes = state.interviewNotesDraft.trim().ifBlank { null },
                )).let { it.message to it.admission }
            }
        }
    }

    fun recordInterview() {
        val state = _uiState.value
        val admission = state.selectedAdmission ?: return
        if (!state.canRecordInterview || state.isSaving) return
        val score = state.interviewScoreDraft.toDoubleOrNull()
        if (score == null || score !in 0.0..100.0) {
            _uiState.update { it.copy(errorMessage = "Enter an interview score from 0 to 100.") }
            return
        }
        viewModelScope.launch {
            mutate(admission.id) {
                api.recordInterview(admission.id, RecordAdmissionInterviewRequestDto(
                    interviewScore = score,
                    interviewNotes = state.interviewNotesDraft.trim().ifBlank { null },
                )).let { it.message to it.admission }
            }
        }
    }

    fun sendOffer() {
        val admission = _uiState.value.selectedAdmission ?: return
        if (_uiState.value.isSaving || admission.status != "admitted") return
        viewModelScope.launch {
            mutate(admission.id) { api.sendOffer(admission.id).let { it.message to it.admission } }
        }
    }

    private suspend fun mutate(admissionId: Long, operation: suspend () -> Pair<String, AdmissionItemDto>) {
        _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
        runCatching { operation() }.onSuccess { (message, admission) ->
            _uiState.update { state -> state.copy(
                isSaving = false,
                selectedAdmission = admission,
                statusDraft = admission.status,
                reviewNotesDraft = admission.notes.orEmpty(),
                interviewDateDraft = admission.interviewDate.orEmpty(),
                interviewNotesDraft = admission.interviewNotes.orEmpty(),
                interviewScoreDraft = admission.interviewScore?.toString().orEmpty(),
                message = message,
            ) }
            loadPage(reset = true, preserveSelection = admissionId, preserveMessage = true)
        }.onFailure { error -> _uiState.update { it.copy(isSaving = false, errorMessage = error.admissionMessage()) } }
    }

    fun consumeMessage() = _uiState.update { it.copy(message = null) }

    private fun loadPage(reset: Boolean, preserveSelection: Long? = null, preserveMessage: Boolean = false) {
        val current = _uiState.value
        if ((reset && current.isLoading) || (!reset && (current.isLoadingMore || !current.hasMore))) return
        val page = if (reset) 1 else (current.workspace?.meta?.page ?: 1) + 1
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = reset, isLoadingMore = !reset, errorMessage = null, message = if (preserveMessage) it.message else null) }
            runCatching { api.index(status = _uiState.value.selectedStatus, search = _uiState.value.searchQuery.trim().ifBlank { null }, page = page) }
                .onSuccess { response ->
                    _uiState.update { state ->
                        val items = if (reset) response.admissions else (state.admissions + response.admissions).distinctBy { it.id }
                        val selectedId = preserveSelection ?: state.selectedAdmission?.id
                        val selected = selectedId?.let { id -> items.firstOrNull { it.id == id } ?: state.selectedAdmission }
                        state.copy(workspace = response, admissions = items, selectedAdmission = selected, selectedStatus = response.selected.status, isLoading = false, isLoadingMore = false)
                    }
                }
                .onFailure { error -> _uiState.update { it.copy(isLoading = false, isLoadingMore = false, errorMessage = error.admissionMessage()) } }
        }
    }
}

private fun Throwable.admissionMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "Your account is not permitted to manage admissions."
        404 -> "This admission application is no longer available."
        409 -> "The requested admission transition conflicts with the current record."
        422 -> "Check the application details, interview values, class selection, or paid student capacity and try again."
        else -> "The admissions service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank) ?: "Unable to load admissions. Check your connection and try again."
}
