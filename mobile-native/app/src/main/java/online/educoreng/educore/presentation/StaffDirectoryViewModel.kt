package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.StaffAdminApi
import online.educoreng.educore.core.network.dto.CreateStaffAccountRequestDto
import online.educoreng.educore.core.network.dto.StaffActiveUpdateRequestDto
import online.educoreng.educore.core.network.dto.StaffDirectoryMemberDto
import retrofit2.HttpException

internal enum class StaffDirectoryFilter { ALL, ACTIVE, INACTIVE }

internal data class StaffCreateDraft(
    val name: String = "",
    val email: String = "",
    val phone: String = "",
    val role: String = "teacher",
    val password: String = "",
) {
    val valid: Boolean
        get() = name.isNotBlank() && email.contains('@') && role.isNotBlank() && password.length >= 8
}

internal data class StaffDirectoryUiState(
    val members: List<StaffDirectoryMemberDto> = emptyList(),
    val query: String = "",
    val filter: StaffDirectoryFilter = StaffDirectoryFilter.ALL,
    val totalCount: Int = 0,
    val activeCount: Int = 0,
    val inactiveCount: Int = 0,
    val filteredTotal: Int = 0,
    val page: Int = 1,
    val lastPage: Int = 1,
    val hasMore: Boolean = false,
    val isLoading: Boolean = false,
    val isLoadingMore: Boolean = false,
    val savingMemberId: Long? = null,
    val isCreateOpen: Boolean = false,
    val isCreating: Boolean = false,
    val createDraft: StaffCreateDraft = StaffCreateDraft(),
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val visibleMembers: List<StaffDirectoryMemberDto> get() = members
}

@HiltViewModel
internal class StaffDirectoryViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api: StaffAdminApi = factory.create(StaffAdminApi::class.java)
    private val _uiState = MutableStateFlow(StaffDirectoryUiState())
    val uiState: StateFlow<StaffDirectoryUiState> = _uiState.asStateFlow()
    private var searchJob: Job? = null
    private var requestJob: Job? = null

    fun load() = loadPage(reset = true)
    fun loadMore() {
        val state = _uiState.value
        if (!state.hasMore || state.isLoading || state.isLoadingMore || requestJob?.isActive == true) return
        loadPage(reset = false)
    }

    fun setQuery(value: String) {
        _uiState.update { it.copy(query = value.take(120)) }
        searchJob?.cancel()
        searchJob = viewModelScope.launch { delay(350); loadPage(reset = true) }
    }

    fun setFilter(value: StaffDirectoryFilter) {
        if (_uiState.value.filter == value) return
        _uiState.update { it.copy(filter = value) }
        searchJob?.cancel()
        loadPage(reset = true)
    }

    fun startCreate() = _uiState.update { it.copy(isCreateOpen = true, createDraft = StaffCreateDraft(), errorMessage = null, message = null) }
    fun closeCreate() = _uiState.update { it.copy(isCreateOpen = false, errorMessage = null) }
    fun setCreateName(value: String) = updateDraft { copy(name = value.take(160)) }
    fun setCreateEmail(value: String) = updateDraft { copy(email = value.take(160)) }
    fun setCreatePhone(value: String) = updateDraft { copy(phone = value.take(40)) }
    fun setCreateRole(value: String) = updateDraft { copy(role = value) }
    fun setCreatePassword(value: String) = updateDraft { copy(password = value.take(128)) }

    fun createStaff() {
        val draft = _uiState.value.createDraft
        if (!draft.valid || _uiState.value.isCreating) return
        viewModelScope.launch {
            _uiState.update { it.copy(isCreating = true, errorMessage = null, message = null) }
            runCatching {
                api.createStaff(CreateStaffAccountRequestDto(
                    name = draft.name.trim(), email = draft.email.trim(), role = draft.role,
                    password = draft.password, phone = draft.phone.trim().ifBlank { null },
                ))
            }.onSuccess { response ->
                _uiState.update { it.copy(isCreating = false, isCreateOpen = false, createDraft = StaffCreateDraft(), message = response.message) }
                loadPage(reset = true)
            }.onFailure { error ->
                _uiState.update { it.copy(isCreating = false, errorMessage = error.staffDirectoryMessage()) }
            }
        }
    }

    private fun updateDraft(block: StaffCreateDraft.() -> StaffCreateDraft) {
        _uiState.update { it.copy(createDraft = it.createDraft.block(), errorMessage = null) }
    }

    private fun loadPage(reset: Boolean) {
        val snapshot = _uiState.value
        if (!reset && (snapshot.isLoadingMore || requestJob?.isActive == true)) return
        if (reset) requestJob?.cancel()
        val targetPage = if (reset) 1 else snapshot.page + 1
        val query = snapshot.query.trim().takeIf(String::isNotBlank)
        val status = snapshot.filter.wireValue

        requestJob = viewModelScope.launch {
            _uiState.update { if (reset) it.copy(isLoading = true, isLoadingMore = false, errorMessage = null) else it.copy(isLoadingMore = true, errorMessage = null) }
            runCatching { api.staff(query = query, status = status, page = targetPage, perPage = PAGE_SIZE) }
                .onSuccess { response ->
                    _uiState.update { state ->
                        val merged = if (reset) response.staff else (state.members + response.staff).distinctBy(StaffDirectoryMemberDto::id)
                        state.copy(
                            members = merged,
                            totalCount = response.counts.total.takeIf { it > 0 } ?: if (reset) response.meta.total else state.totalCount,
                            activeCount = response.counts.active,
                            inactiveCount = response.counts.inactive,
                            filteredTotal = response.meta.total.takeIf { it > 0 } ?: merged.size,
                            page = response.meta.page,
                            lastPage = response.meta.lastPage,
                            hasMore = response.meta.hasMore,
                            isLoading = false,
                            isLoadingMore = false,
                        )
                    }
                }
                .onFailure { error ->
                    if (error is CancellationException) throw error
                    _uiState.update { it.copy(isLoading = false, isLoadingMore = false, errorMessage = error.staffDirectoryMessage()) }
                }
        }
    }

    fun setActive(member: StaffDirectoryMemberDto, active: Boolean) {
        if (_uiState.value.savingMemberId != null || member.active == active) return
        viewModelScope.launch {
            _uiState.update { it.copy(savingMemberId = member.id, errorMessage = null, message = null) }
            runCatching { api.updateActiveState(member.id, StaffActiveUpdateRequestDto(isActive = active)) }
                .onSuccess { response ->
                    _uiState.update { state -> state.copy(members = state.members.map { if (it.id == member.id) it.copy(active = response.active) else it }, savingMemberId = null, message = response.message) }
                    loadPage(reset = true)
                }
                .onFailure { error -> _uiState.update { it.copy(savingMemberId = null, errorMessage = error.staffDirectoryMessage()) } }
        }
    }

    fun consumeMessage() = _uiState.update { it.copy(message = null) }
    fun clearError() = _uiState.update { it.copy(errorMessage = null) }

    private val StaffDirectoryFilter.wireValue: String
        get() = when (this) {
            StaffDirectoryFilter.ALL -> "all"
            StaffDirectoryFilter.ACTIVE -> "active"
            StaffDirectoryFilter.INACTIVE -> "inactive"
        }

    companion object {
        const val PAGE_SIZE = 50
        val CREATE_ROLES = listOf(
            "teacher" to "Teacher", "subject_teacher" to "Subject Teacher", "class_teacher" to "Class Teacher",
            "hod" to "Head of Department", "principal" to "Principal", "vice_principal" to "Vice Principal",
            "academic_administrator" to "Academic Administrator", "accountant" to "Accountant", "bursar" to "Bursar",
            "admission_officer" to "Admission Officer", "transport_officer" to "Transport Officer", "health_officer" to "Health Officer",
            "librarian" to "Librarian", "admin" to "School Administrator",
        )
    }
}

private fun Throwable.staffDirectoryMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "Your account is not permitted to manage staff records."
        404 -> "This staff record is no longer available. Refresh the directory."
        422 -> "Check the staff details. The email may already exist or a field may be invalid."
        else -> "The staff service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank) ?: "Unable to reach the staff service. Check your connection and try again."
}
