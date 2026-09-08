package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.ReportsApi
import online.educoreng.educore.core.network.dto.ReportPublishRequestDto
import online.educoreng.educore.core.network.dto.ReportsWorkspaceDto
import retrofit2.HttpException

enum class ReportPublicationAction { PUBLISH, UNPUBLISH }

internal data class ReportsUiState(
    val workspace: ReportsWorkspaceDto? = null,
    val selectedClassId: Long? = null,
    val selectedTermId: Long? = null,
    val publicationNote: String = "",
    val confirmation: ReportPublicationAction? = null,
    val isLoading: Boolean = false,
    val isMutating: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val hasSelection: Boolean get() = selectedClassId != null && selectedTermId != null
    val canPublish: Boolean get() =
        workspace?.capabilities?.publish == true &&
            workspace.summary.computed > 0 &&
            workspace.summary.published.not() &&
            hasSelection && !isMutating
    val canUnpublish: Boolean get() =
        workspace?.capabilities?.unpublish == true &&
            workspace.summary.published && hasSelection && !isMutating
}

@HiltViewModel
internal class ReportsViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api: ReportsApi = factory.create(ReportsApi::class.java)
    private val _uiState = MutableStateFlow(ReportsUiState())
    val uiState: StateFlow<ReportsUiState> = _uiState.asStateFlow()
    private var loadJob: Job? = null

    fun load() {
        loadJob?.cancel()
        loadJob = viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            try {
                val initial = api.index()
                val classId = _uiState.value.selectedClassId
                    ?.takeIf { id -> initial.options.classArms.any { it.id == id } }
                    ?: initial.options.classArms.firstOrNull()?.id
                val termId = _uiState.value.selectedTermId
                    ?.takeIf { id -> initial.options.terms.any { it.id == id } }
                    ?: initial.options.terms.firstOrNull { it.isCurrent }?.id
                    ?: initial.options.terms.firstOrNull()?.id
                val workspace = if (classId != null && termId != null) {
                    api.index(classId, termId)
                } else {
                    initial
                }
                _uiState.update {
                    it.copy(
                        workspace = workspace,
                        selectedClassId = classId,
                        selectedTermId = termId,
                        publicationNote = workspace.publication?.note.orEmpty(),
                        isLoading = false,
                        errorMessage = null,
                    )
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isLoading = false, errorMessage = error.reportMessage()) }
            }
        }
    }

    fun selectClass(id: Long?) {
        _uiState.update {
            it.copy(selectedClassId = id, publicationNote = "", errorMessage = null, message = null)
        }
        reloadSelection()
    }

    fun selectTerm(id: Long?) {
        _uiState.update {
            it.copy(selectedTermId = id, publicationNote = "", errorMessage = null, message = null)
        }
        reloadSelection()
    }

    fun updatePublicationNote(value: String) = _uiState.update {
        it.copy(publicationNote = value.take(2000), errorMessage = null, message = null)
    }

    fun requestPublish() {
        if (_uiState.value.canPublish) {
            _uiState.update { it.copy(confirmation = ReportPublicationAction.PUBLISH) }
        }
    }

    fun requestUnpublish() {
        if (_uiState.value.canUnpublish) {
            _uiState.update { it.copy(confirmation = ReportPublicationAction.UNPUBLISH) }
        }
    }

    fun dismissConfirmation() = _uiState.update { it.copy(confirmation = null) }

    fun confirmPublicationAction() {
        val state = _uiState.value
        val action = state.confirmation ?: return
        val classId = state.selectedClassId ?: return
        val termId = state.selectedTermId ?: return
        if (state.isMutating) return

        viewModelScope.launch {
            _uiState.update { it.copy(confirmation = null, isMutating = true, errorMessage = null, message = null) }
            try {
                val request = ReportPublishRequestDto(
                    classArmId = classId,
                    termId = termId,
                    note = state.publicationNote.trim().takeIf(String::isNotBlank),
                )
                val response = when (action) {
                    ReportPublicationAction.PUBLISH -> api.publish(request)
                    ReportPublicationAction.UNPUBLISH -> api.unpublish(request)
                }
                val workspace = api.index(classId, termId)
                _uiState.update {
                    it.copy(
                        workspace = workspace,
                        publicationNote = workspace.publication?.note.orEmpty(),
                        isMutating = false,
                        errorMessage = null,
                        message = response.message,
                    )
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isMutating = false, errorMessage = error.reportMessage()) }
            }
        }
    }

    fun consumeMessage() = _uiState.update { it.copy(message = null) }

    private fun reloadSelection() {
        val state = _uiState.value
        val classId = state.selectedClassId
        val termId = state.selectedTermId
        loadJob?.cancel()
        if (classId == null || termId == null) return

        loadJob = viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            try {
                val workspace = api.index(classId, termId)
                _uiState.update {
                    it.copy(
                        workspace = workspace,
                        publicationNote = workspace.publication?.note.orEmpty(),
                        isLoading = false,
                        errorMessage = null,
                    )
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isLoading = false, errorMessage = error.reportMessage()) }
            }
        }
    }
}

private fun Throwable.reportMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "You do not have permission to manage report cards."
        404 -> "The selected class, term or report-card record is no longer available."
        409 -> "The report-card state changed while you were working. Reload and try again."
        422 -> "The requested report-card action is not valid yet. Check that reports have been computed."
        else -> "The Report Cards service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the Report Cards service. Check your connection and try again."
}
