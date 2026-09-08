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
    val isLoading: Boolean = false,
    val savingMemberId: Long? = null,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val visibleMembers: List<StaffDirectoryMemberDto>
        get() {
            val needle = query.trim()
            return members.filter { member ->
                val matchesStatus = when (filter) {
                    StaffDirectoryFilter.ALL -> true
                    StaffDirectoryFilter.ACTIVE -> member.active
                    StaffDirectoryFilter.INACTIVE -> !member.active
                }
                val matchesQuery = needle.isBlank() ||
                    member.name.contains(needle, ignoreCase = true) ||
                    member.staffId?.contains(needle, ignoreCase = true) == true ||
                    member.role.contains(needle, ignoreCase = true)
                matchesStatus && matchesQuery
            }
        }

    val activeCount: Int get() = members.count(StaffDirectoryMemberDto::active)
    val inactiveCount: Int get() = members.size - activeCount
}

@HiltViewModel
internal class StaffDirectoryViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api: StaffAdminApi = factory.create(StaffAdminApi::class.java)
    private val _uiState = MutableStateFlow(StaffDirectoryUiState())
    val uiState: StateFlow<StaffDirectoryUiState> = _uiState.asStateFlow()

    fun load() {
        if (_uiState.value.isLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            runCatching { api.staff() }
                .onSuccess { response ->
                    _uiState.update {
                        it.copy(
                            members = response.staff.sortedBy { member -> member.name.lowercase() },
                            isLoading = false,
                        )
                    }
                }
                .onFailure { error ->
                    _uiState.update { it.copy(isLoading = false, errorMessage = error.staffDirectoryMessage()) }
                }
        }
    }

    fun setQuery(value: String) = _uiState.update { it.copy(query = value) }

    fun setFilter(value: StaffDirectoryFilter) = _uiState.update { it.copy(filter = value) }

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
            }.onFailure { error ->
                _uiState.update {
                    it.copy(savingMemberId = null, errorMessage = error.staffDirectoryMessage())
                }
            }
        }
    }

    fun consumeMessage() = _uiState.update { it.copy(message = null) }
    fun clearError() = _uiState.update { it.copy(errorMessage = null) }
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
