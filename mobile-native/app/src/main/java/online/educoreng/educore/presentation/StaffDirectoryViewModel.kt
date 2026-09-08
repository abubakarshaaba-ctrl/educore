package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.StaffAdminApi
import online.educoreng.educore.core.network.dto.StaffActiveUpdateRequestDto
import online.educoreng.educore.core.network.dto.StaffDirectoryMemberDto
import retrofit2.HttpException

internal enum class StaffDirectoryFilter {
    ALL,
    ACTIVE,
    INACTIVE,
}

internal data class StaffDirectoryUiState(
    val members: List<StaffDirectoryMemberDto> = emptyList(),
    val query: String = "",
    val filter: StaffDirectoryFilter = StaffDirectoryFilter.ALL,
    val totalCount: Int = 0,
    val activeCount: Int = 0,
    val inactiveCount: Int = 0,
    val page: Int = 1,
    val lastPage: Int = 1,
    val hasMore: Boolean = false,
    val isLoading: Boolean = false,
    val isLoadingMore: Boolean = false,
    val savingMemberId: Long? = null,
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

    fun load() = loadPage(reset = true)

    fun loadMore() {
        val state = _uiState.value
        if (!state.hasMore || state.isLoading || state.isLoadingMore) return
        loadPage(reset = false)
    }

    fun setQuery(value: String) {
        _uiState.update { it.copy(query = value) }
        searchJob?.cancel()
        searchJob = viewModelScope.launch {
            delay(350)
            loadPage(reset = true)
        }
    }

    fun setFilter(value: StaffDirectoryFilter) {
        if (_uiState.value.filter == value) return
        _uiState.update { it.copy(filter = value) }
        searchJob?.cancel()
        loadPage(reset = true)
    }

    private fun loadPage(reset: Boolean) {
        val snapshot = _uiState.value
        if (reset && snapshot.isLoading) return
        if (!reset && snapshot.isLoadingMore) return

        val targetPage = if (reset) 1 else snapshot.page + 1
        val query = snapshot.query.trim().takeIf(String::isNotBlank)
        val status = snapshot.filter.wireValue

        viewModelScope.launch {
            _uiState.update {
                if (reset) {
                    it.copy(isLoading = true, isLoadingMore = false, errorMessage = null)
                } else {
                    it.copy(isLoadingMore = true, errorMessage = null)
                }
            }

            runCatching {
                api.staff(
                    query = query,
                    status = status,
                    page = targetPage,
                    perPage = PAGE_SIZE,
                )
            }.onSuccess { response ->
                _uiState.update { state ->
                    val merged = if (reset) {
                        response.staff
                    } else {
                        (state.members + response.staff).distinctBy(StaffDirectoryMemberDto::id)
                    }
                    state.copy(
                        members = merged,
                        totalCount = response.counts.total,
                        activeCount = response.counts.active,
                        inactiveCount = response.counts.inactive,
                        page = response.meta.page,
                        lastPage = response.meta.lastPage,
                        hasMore = response.meta.hasMore,
                        isLoading = false,
                        isLoadingMore = false,
                    )
                }
            }.onFailure { error ->
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        isLoadingMore = false,
                        errorMessage = error.staffDirectoryMessage(),
                    )
                }
            }
        }
    }

    fun setActive(member: StaffDirectoryMemberDto, active: Boolean) {
        if (_uiState.value.savingMemberId != null || member.active == active) return
        viewModelScope.launch {
            _uiState.update { it.copy(savingMemberId = member.id, errorMessage = null, message = null) }
            runCatching {
                api.updateActiveState(member.id, StaffActiveUpdateRequestDto(isActive = active))
            }.onSuccess { response ->
                _uiState.update { state ->
                    state.copy(
                        members = state.members.map {
                            if (it.id == member.id) it.copy(active = response.active) else it
                        },
                        savingMemberId = null,
                        message = response.message,
                    )
                }
                loadPage(reset = true)
            }.onFailure { error ->
                _uiState.update {
                    it.copy(savingMemberId = null, errorMessage = error.staffDirectoryMessage())
                }
            }
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

    private companion object {
        const val PAGE_SIZE = 50
    }
}

private fun Throwable.staffDirectoryMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "Your account is not permitted to manage staff records."
        404 -> "This staff record is no longer available. Refresh the directory."
        422 -> "That staff-account change is not permitted."
        else -> "The staff service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the staff service. Check your connection and try again."
}
