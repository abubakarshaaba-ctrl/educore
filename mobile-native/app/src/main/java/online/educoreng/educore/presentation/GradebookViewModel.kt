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
import online.educoreng.educore.core.network.GradebookApi
import online.educoreng.educore.core.network.dto.GradebookRemarkRequestDto
import online.educoreng.educore.core.network.dto.GradebookStudentRowDto
import online.educoreng.educore.core.network.dto.GradebookWorkspaceDto
import retrofit2.HttpException

internal enum class GradebookRemarkField(val apiKey: String, val label: String) {
    FORM_TUTOR("form_tutor_remark", "Form tutor remark"),
    PRINCIPAL("principal_remark", "Principal remark"),
}

internal data class GradebookRemarkEditor(
    val summaryId: Long,
    val studentName: String,
    val field: GradebookRemarkField,
    val draft: String,
)

internal data class GradebookUiState(
    val workspace: GradebookWorkspaceDto? = null,
    val selectedClassId: Long? = null,
    val selectedTermId: Long? = null,
    val editor: GradebookRemarkEditor? = null,
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val selectedClassName: String get() =
        workspace?.options?.classArms?.firstOrNull { it.id == selectedClassId }?.name ?: "Select class"

    val selectedTermName: String get() = workspace?.options?.terms
        ?.firstOrNull { it.id == selectedTermId }
        ?.let { term -> listOfNotNull(term.name, term.session).joinToString(" · ") }
        ?: "Select term"

    val canSaveRemark: Boolean get() = editor != null && !isSaving && editor.draft.length <= 2000
}

@HiltViewModel
internal class GradebookViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api: GradebookApi = factory.create(GradebookApi::class.java)
    private val _uiState = MutableStateFlow(GradebookUiState())
    val uiState: StateFlow<GradebookUiState> = _uiState.asStateFlow()
    private var loadJob: Job? = null

    fun load() {
        if (loadJob?.isActive == true) return
        launchWorkspaceLoad(
            classId = _uiState.value.selectedClassId,
            termId = _uiState.value.selectedTermId,
            allowAutoSelection = true,
        )
    }

    fun selectClass(id: Long?) {
        val state = _uiState.value
        if (id == state.selectedClassId) return
        _uiState.update { it.copy(selectedClassId = id, editor = null, message = null, errorMessage = null) }
        launchWorkspaceLoad(id, _uiState.value.selectedTermId, allowAutoSelection = false)
    }

    fun selectTerm(id: Long?) {
        val state = _uiState.value
        if (id == state.selectedTermId) return
        _uiState.update { it.copy(selectedTermId = id, editor = null, message = null, errorMessage = null) }
        launchWorkspaceLoad(_uiState.value.selectedClassId, id, allowAutoSelection = false)
    }

    fun editFormTutorRemark(row: GradebookStudentRowDto) {
        val summary = row.summary ?: return
        if (_uiState.value.workspace?.capabilities?.editFormTutorRemark != true) return
        _uiState.update {
            it.copy(
                editor = GradebookRemarkEditor(
                    summaryId = summary.id,
                    studentName = row.student.name,
                    field = GradebookRemarkField.FORM_TUTOR,
                    draft = summary.formTutorRemark.orEmpty(),
                ),
                errorMessage = null,
                message = null,
            )
        }
    }

    fun editPrincipalRemark(row: GradebookStudentRowDto) {
        val summary = row.summary ?: return
        if (_uiState.value.workspace?.capabilities?.editPrincipalRemark != true) return
        _uiState.update {
            it.copy(
                editor = GradebookRemarkEditor(
                    summaryId = summary.id,
                    studentName = row.student.name,
                    field = GradebookRemarkField.PRINCIPAL,
                    draft = summary.principalRemark.orEmpty(),
                ),
                errorMessage = null,
                message = null,
            )
        }
    }

    fun updateRemarkDraft(value: String) = _uiState.update { state ->
        state.copy(editor = state.editor?.copy(draft = value.take(2000)), errorMessage = null)
    }

    fun dismissEditor() = _uiState.update { it.copy(editor = null) }

    fun saveRemark() {
        val editor = _uiState.value.editor ?: return
        if (_uiState.value.isSaving) return

        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            try {
                val response = api.updateRemark(
                    editor.summaryId,
                    GradebookRemarkRequestDto(
                        field = editor.field.apiKey,
                        remark = editor.draft.trim().takeIf(String::isNotBlank),
                    ),
                )
                _uiState.update { it.copy(isSaving = false, editor = null, message = response.message) }
                loadJob?.cancel()
                loadWorkspace(
                    _uiState.value.selectedClassId,
                    _uiState.value.selectedTermId,
                    allowAutoSelection = false,
                    preserveMessage = true,
                )
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isSaving = false, errorMessage = error.gradebookMessage()) }
            }
        }
    }

    fun consumeMessage() = _uiState.update { it.copy(message = null) }

    private fun launchWorkspaceLoad(
        classId: Long?,
        termId: Long?,
        allowAutoSelection: Boolean,
    ) {
        loadJob?.cancel()
        loadJob = viewModelScope.launch {
            loadWorkspace(classId, termId, allowAutoSelection)
        }
    }

    private suspend fun loadWorkspace(
        classId: Long?,
        termId: Long?,
        allowAutoSelection: Boolean,
        preserveMessage: Boolean = false,
    ) {
        _uiState.update { it.copy(isLoading = true, errorMessage = null) }
        try {
            var resolvedClassId = classId
            var resolvedTermId = termId
            var workspace = api.index(resolvedClassId, resolvedTermId)

            if (allowAutoSelection) {
                if (resolvedClassId == null) {
                    resolvedClassId = workspace.options.classArms.firstOrNull()?.id
                }
                if (resolvedTermId == null) {
                    resolvedTermId = workspace.options.terms.firstOrNull { it.isCurrent }?.id
                        ?: workspace.options.terms.firstOrNull()?.id
                }
                if (resolvedClassId != null && resolvedTermId != null &&
                    (workspace.selected.classArmId != resolvedClassId || workspace.selected.termId != resolvedTermId)) {
                    workspace = api.index(resolvedClassId, resolvedTermId)
                }
            }

            _uiState.update { state ->
                state.copy(
                    workspace = workspace,
                    selectedClassId = resolvedClassId,
                    selectedTermId = resolvedTermId,
                    isLoading = false,
                    errorMessage = null,
                    message = if (preserveMessage) state.message else null,
                )
            }
        } catch (cancelled: CancellationException) {
            throw cancelled
        } catch (error: Throwable) {
            _uiState.update { it.copy(isLoading = false, errorMessage = error.gradebookMessage()) }
        }
    }
}

private fun Throwable.gradebookMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "You do not have permission to access that class or edit that remark."
        404 -> "The class, term or report summary is no longer available."
        422 -> "The selected gradebook data is not valid. Reload and try again."
        else -> "The Gradebook service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the Gradebook service. Check your connection and try again."
}
