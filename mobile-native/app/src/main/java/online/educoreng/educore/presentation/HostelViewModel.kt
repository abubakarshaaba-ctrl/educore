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
import online.educoreng.educore.core.network.HostelApi
import online.educoreng.educore.core.network.dto.CreateHostelRequestDto
import online.educoreng.educore.core.network.dto.CreateHostelRoomRequestDto
import online.educoreng.educore.core.network.dto.HostelAllocationDto
import online.educoreng.educore.core.network.dto.HostelAllocationRequestDto
import online.educoreng.educore.core.network.dto.HostelDto
import online.educoreng.educore.core.network.dto.HostelRoomDto
import online.educoreng.educore.core.network.dto.HostelWorkspaceDto
import retrofit2.HttpException

internal enum class HostelEditorMode { NONE, HOSTEL, ROOM, ALLOCATION }

internal data class HostelUiState(
    val workspace: HostelWorkspaceDto? = null,
    val allocations: List<HostelAllocationDto> = emptyList(),
    val query: String = "",
    val studentQuery: String = "",
    val editorMode: HostelEditorMode = HostelEditorMode.NONE,
    val hostelName: String = "",
    val hostelGender: String = "mixed",
    val hostelCapacity: String = "",
    val wardenId: Long? = null,
    val roomHostelId: Long? = null,
    val roomNumber: String = "",
    val roomCapacity: String = "",
    val allocationHostelId: Long? = null,
    val allocationRoomId: Long? = null,
    val allocationStudentId: Long? = null,
    val isLoading: Boolean = false,
    val isLoadingMore: Boolean = false,
    val isSaving: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val canManage: Boolean get() = workspace?.capabilities?.manage == true
    val hasMore: Boolean get() = workspace?.meta?.hasMore == true
    val hostels: List<HostelDto> get() = workspace?.hostels.orEmpty()
    val selectedHostel: HostelDto? get() = hostels.firstOrNull { it.id == allocationHostelId }
    val availableRooms: List<HostelRoomDto> get() = selectedHostel?.rooms.orEmpty().filterNot { it.full }
    val validHostelDraft: Boolean
        get() = hostelName.isNotBlank() && (hostelCapacity.toIntOrNull() ?: 0) > 0
    val validRoomDraft: Boolean
        get() = roomHostelId != null && roomNumber.isNotBlank() && (roomCapacity.toIntOrNull() ?: 0) > 0
    val validAllocationDraft: Boolean
        get() = allocationHostelId != null && allocationRoomId != null && allocationStudentId != null
}

@HiltViewModel
internal class HostelViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api: HostelApi = factory.create(HostelApi::class.java)
    private val _uiState = MutableStateFlow(HostelUiState())
    val uiState: StateFlow<HostelUiState> = _uiState.asStateFlow()

    fun load() = loadPage(reset = true)
    fun setQuery(value: String) = _uiState.update { it.copy(query = value.take(120), errorMessage = null) }
    fun search() = loadPage(reset = true)
    fun loadMore() = loadPage(reset = false)

    fun openCreateHostel() = _uiState.update {
        it.copy(
            editorMode = HostelEditorMode.HOSTEL,
            hostelName = "",
            hostelGender = "mixed",
            hostelCapacity = "",
            wardenId = null,
            errorMessage = null,
            message = null,
        )
    }

    fun openCreateRoom(hostelId: Long) = _uiState.update {
        it.copy(
            editorMode = HostelEditorMode.ROOM,
            roomHostelId = hostelId,
            roomNumber = "",
            roomCapacity = "",
            errorMessage = null,
            message = null,
        )
    }

    fun openAllocation(hostelId: Long? = null) = _uiState.update { state ->
        state.copy(
            editorMode = HostelEditorMode.ALLOCATION,
            allocationHostelId = hostelId,
            allocationRoomId = null,
            allocationStudentId = null,
            studentQuery = "",
            errorMessage = null,
            message = null,
        )
    }

    fun closeEditor() = _uiState.update {
        it.copy(
            editorMode = HostelEditorMode.NONE,
            roomHostelId = null,
            allocationHostelId = null,
            allocationRoomId = null,
            allocationStudentId = null,
            errorMessage = null,
        )
    }

    fun setHostelName(value: String) = _uiState.update { it.copy(hostelName = value.take(120), errorMessage = null) }
    fun setHostelGender(value: String) {
        if (value !in setOf("male", "female", "mixed")) return
        _uiState.update { it.copy(hostelGender = value, errorMessage = null) }
    }
    fun setHostelCapacity(value: String) = _uiState.update { it.copy(hostelCapacity = value.filter(Char::isDigit).take(5), errorMessage = null) }
    fun setWarden(value: Long?) = _uiState.update { it.copy(wardenId = value, errorMessage = null) }
    fun setRoomNumber(value: String) = _uiState.update { it.copy(roomNumber = value.take(30), errorMessage = null) }
    fun setRoomCapacity(value: String) = _uiState.update { it.copy(roomCapacity = value.filter(Char::isDigit).take(4), errorMessage = null) }

    fun setStudentQuery(value: String) = _uiState.update { it.copy(studentQuery = value.take(120), errorMessage = null) }
    fun searchStudents() = loadPage(reset = true, preserveEditor = true)
    fun selectAllocationHostel(id: Long?) = _uiState.update { state ->
        state.copy(allocationHostelId = id, allocationRoomId = null, errorMessage = null)
    }
    fun selectAllocationRoom(id: Long?) = _uiState.update { it.copy(allocationRoomId = id, errorMessage = null) }
    fun selectAllocationStudent(id: Long?) = _uiState.update { it.copy(allocationStudentId = id, errorMessage = null) }

    fun createHostel() {
        val state = _uiState.value
        val capacity = state.hostelCapacity.toIntOrNull() ?: return
        if (!state.canManage || !state.validHostelDraft || state.isSaving) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            runCatching {
                api.createHostel(
                    CreateHostelRequestDto(
                        name = state.hostelName.trim(),
                        gender = state.hostelGender,
                        capacity = capacity,
                        wardenId = state.wardenId,
                    )
                )
            }.onSuccess { response ->
                _uiState.update { it.copy(isSaving = false, editorMode = HostelEditorMode.NONE, message = response.message) }
                loadPage(reset = true, preserveMessage = true)
            }.onFailure { error ->
                _uiState.update { it.copy(isSaving = false, errorMessage = error.hostelMessage()) }
            }
        }
    }

    fun createRoom() {
        val state = _uiState.value
        val hostelId = state.roomHostelId ?: return
        val capacity = state.roomCapacity.toIntOrNull() ?: return
        if (!state.canManage || !state.validRoomDraft || state.isSaving) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            runCatching {
                api.createRoom(
                    hostelId,
                    CreateHostelRoomRequestDto(roomNumber = state.roomNumber.trim(), capacity = capacity),
                )
            }.onSuccess { response ->
                _uiState.update { it.copy(isSaving = false, editorMode = HostelEditorMode.NONE, message = response.message) }
                loadPage(reset = true, preserveMessage = true)
            }.onFailure { error ->
                _uiState.update { it.copy(isSaving = false, errorMessage = error.hostelMessage()) }
            }
        }
    }

    fun allocate() {
        val state = _uiState.value
        val hostelId = state.allocationHostelId ?: return
        val roomId = state.allocationRoomId ?: return
        val studentId = state.allocationStudentId ?: return
        if (!state.canManage || !state.validAllocationDraft || state.isSaving) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            runCatching {
                api.allocate(HostelAllocationRequestDto(studentId, hostelId, roomId))
            }.onSuccess { response ->
                _uiState.update {
                    it.copy(
                        isSaving = false,
                        editorMode = HostelEditorMode.NONE,
                        allocationHostelId = null,
                        allocationRoomId = null,
                        allocationStudentId = null,
                        message = response.message,
                    )
                }
                loadPage(reset = true, preserveMessage = true)
            }.onFailure { error ->
                _uiState.update { it.copy(isSaving = false, errorMessage = error.hostelMessage()) }
            }
        }
    }

    fun vacate(allocationId: Long) {
        val state = _uiState.value
        if (!state.canManage || state.isSaving) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            runCatching { api.vacate(allocationId) }
                .onSuccess { response ->
                    _uiState.update { it.copy(isSaving = false, message = response.message) }
                    loadPage(reset = true, preserveMessage = true)
                }
                .onFailure { error ->
                    _uiState.update { it.copy(isSaving = false, errorMessage = error.hostelMessage()) }
                }
        }
    }

    private fun loadPage(
        reset: Boolean,
        preserveEditor: Boolean = false,
        preserveMessage: Boolean = false,
    ) {
        val current = _uiState.value
        if ((reset && current.isLoading) || (!reset && (current.isLoadingMore || !current.hasMore))) return
        val page = if (reset) 1 else (current.workspace?.meta?.page ?: 1) + 1
        viewModelScope.launch {
            _uiState.update {
                it.copy(
                    isLoading = reset,
                    isLoadingMore = !reset,
                    errorMessage = null,
                    editorMode = if (preserveEditor) it.editorMode else it.editorMode,
                    message = if (preserveMessage) it.message else null,
                )
            }
            runCatching {
                api.index(
                    search = _uiState.value.query.trim().ifBlank { null },
                    studentSearch = _uiState.value.studentQuery.trim().ifBlank { null },
                    page = page,
                )
            }.onSuccess { workspace ->
                _uiState.update { state ->
                    state.copy(
                        workspace = workspace,
                        allocations = if (reset) workspace.allocations else (state.allocations + workspace.allocations).distinctBy { it.id },
                        query = workspace.selected.search,
                        studentQuery = workspace.selected.studentSearch,
                        isLoading = false,
                        isLoadingMore = false,
                    )
                }
            }.onFailure { error ->
                _uiState.update { it.copy(isLoading = false, isLoadingMore = false, errorMessage = error.hostelMessage()) }
            }
        }
    }
}

private fun Throwable.hostelMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "Your account is not permitted to manage hostels."
        404 -> "The selected hostel, room, student or allocation is no longer available."
        409 -> "The student is already allocated or the selected hostel/room is full."
        422 -> "Check the hostel, room, capacity and student selections, then try again."
        else -> "The hostel service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the hostel service. Check your connection and try again."
}
