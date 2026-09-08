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
import online.educoreng.educore.core.network.TransportApi
import online.educoreng.educore.core.network.dto.TransportAssignmentRequestDto
import online.educoreng.educore.core.network.dto.TransportDashboardDto
import online.educoreng.educore.core.network.dto.TransportManifestDto
import online.educoreng.educore.core.network.dto.TransportRouteDto
import online.educoreng.educore.core.network.dto.TransportStudentDto
import retrofit2.HttpException

internal data class TransportUiState(
    val dashboard: TransportDashboardDto? = null,
    val unassigned: List<TransportStudentDto> = emptyList(),
    val manifest: TransportManifestDto? = null,
    val query: String = "",
    val selectedRouteId: Long? = null,
    val selectedStudentId: Long? = null,
    val pickupStop: String = "",
    val direction: String = "both",
    val assignmentOpen: Boolean = false,
    val isLoading: Boolean = false,
    val isLoadingMore: Boolean = false,
    val isLoadingManifest: Boolean = false,
    val isSaving: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val canManage: Boolean get() = manifest?.capabilities?.manage ?: dashboard?.capabilities?.manage == true
    val hasMore: Boolean get() = dashboard?.meta?.hasMore == true
    val activeRoutes: List<TransportRouteDto> get() = dashboard?.routes.orEmpty().filter { it.active }
    val canAssign: Boolean get() = canManage && selectedRouteId != null && selectedStudentId != null && !isSaving
}

@HiltViewModel
internal class TransportViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api: TransportApi = factory.create(TransportApi::class.java)
    private val _uiState = MutableStateFlow(TransportUiState())
    val uiState: StateFlow<TransportUiState> = _uiState.asStateFlow()

    fun load() = loadPage(reset = true)
    fun setQuery(value: String) = _uiState.update { it.copy(query = value.take(120), errorMessage = null) }
    fun search() = loadPage(reset = true)
    fun loadMore() = loadPage(reset = false)

    fun openManifest(routeId: Long) {
        if (_uiState.value.isLoadingManifest) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoadingManifest = true, errorMessage = null, message = null) }
            runCatching { api.manifest(routeId) }
                .onSuccess { manifest ->
                    _uiState.update { it.copy(manifest = manifest, selectedRouteId = routeId, isLoadingManifest = false) }
                }
                .onFailure { error ->
                    _uiState.update { it.copy(isLoadingManifest = false, errorMessage = error.transportMessage()) }
                }
        }
    }

    fun closeManifest() = _uiState.update { it.copy(manifest = null, selectedRouteId = null, message = null, errorMessage = null) }

    fun openAssignment(routeId: Long? = null) = _uiState.update {
        it.copy(
            assignmentOpen = true,
            selectedRouteId = routeId ?: it.selectedRouteId,
            selectedStudentId = null,
            pickupStop = "",
            direction = "both",
            errorMessage = null,
            message = null,
        )
    }

    fun closeAssignment() = _uiState.update {
        it.copy(assignmentOpen = false, selectedStudentId = null, pickupStop = "", direction = "both", errorMessage = null)
    }

    fun selectRoute(id: Long?) = _uiState.update { it.copy(selectedRouteId = id, errorMessage = null) }
    fun selectStudent(id: Long?) = _uiState.update { it.copy(selectedStudentId = id, errorMessage = null) }
    fun setPickupStop(value: String) = _uiState.update { it.copy(pickupStop = value.take(150), errorMessage = null) }
    fun setDirection(value: String) {
        if (value !in setOf("both", "morning", "evening")) return
        _uiState.update { it.copy(direction = value, errorMessage = null) }
    }

    fun assign() {
        val state = _uiState.value
        val routeId = state.selectedRouteId ?: return
        val studentId = state.selectedStudentId ?: return
        if (!state.canManage || state.isSaving) return

        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            runCatching {
                api.assign(
                    TransportAssignmentRequestDto(
                        studentId = studentId,
                        routeId = routeId,
                        pickupStop = state.pickupStop.trim().ifBlank { null },
                        direction = state.direction,
                    )
                )
            }.onSuccess { response ->
                _uiState.update {
                    it.copy(
                        isSaving = false,
                        assignmentOpen = false,
                        selectedStudentId = null,
                        pickupStop = "",
                        direction = "both",
                        message = response.message,
                    )
                }
                refreshAfterMutation(routeId)
            }.onFailure { error ->
                _uiState.update { it.copy(isSaving = false, errorMessage = error.transportMessage()) }
            }
        }
    }

    fun unassign(studentId: Long) {
        val state = _uiState.value
        val routeId = state.manifest?.route?.id ?: state.selectedRouteId
        if (!state.canManage || state.isSaving) return

        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            runCatching { api.unassign(studentId) }
                .onSuccess { response ->
                    _uiState.update { it.copy(isSaving = false, message = response.message) }
                    refreshAfterMutation(routeId)
                }
                .onFailure { error ->
                    _uiState.update { it.copy(isSaving = false, errorMessage = error.transportMessage()) }
                }
        }
    }

    private fun refreshAfterMutation(routeId: Long?) {
        loadPage(reset = true, preserveMessage = true)
        routeId?.let(::openManifest)
    }

    private fun loadPage(reset: Boolean, preserveMessage: Boolean = false) {
        val current = _uiState.value
        if ((reset && current.isLoading) || (!reset && (current.isLoadingMore || !current.hasMore))) return
        val page = if (reset) 1 else (current.dashboard?.meta?.page ?: 1) + 1

        viewModelScope.launch {
            _uiState.update {
                it.copy(
                    isLoading = reset,
                    isLoadingMore = !reset,
                    errorMessage = null,
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
                    state.copy(
                        dashboard = dashboard,
                        unassigned = if (reset) dashboard.unassignedStudents else (state.unassigned + dashboard.unassignedStudents).distinctBy { it.id },
                        query = dashboard.selected.search,
                        isLoading = false,
                        isLoadingMore = false,
                    )
                }
            }.onFailure { error ->
                _uiState.update {
                    it.copy(isLoading = false, isLoadingMore = false, errorMessage = error.transportMessage())
                }
            }
        }
    }
}

private fun Throwable.transportMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "Your account is not permitted to manage transport."
        404 -> "The requested route, student, or assignment is no longer available."
        422 -> "Check the route, student, pickup stop and direction, then try again."
        else -> "The transport service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the transport service. Check your connection and try again."
}
