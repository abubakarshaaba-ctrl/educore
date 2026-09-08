package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.SubjectsApi
import online.educoreng.educore.core.network.dto.SubjectAdminDto
import online.educoreng.educore.core.network.dto.SubjectMutationRequestDto
import online.educoreng.educore.core.network.dto.SubjectsWorkspaceDto
import retrofit2.HttpException

internal data class SubjectDraft(
    val id: Long? = null,
    val name: String = "",
    val code: String = "",
    val active: Boolean = true,
) {
    val valid: Boolean get() = name.isNotBlank() && code.length <= 10

    fun toRequest() = SubjectMutationRequestDto(
        name = name.trim(),
        code = code.trim().ifBlank { null },
        isActive = active,
    )

    companion object {
        fun from(subject: SubjectAdminDto) = SubjectDraft(
            id = subject.id,
            name = subject.name,
            code = subject.code.orEmpty(),
            active = subject.active,
        )
    }
}

internal data class SubjectsUiState(
    val workspace: SubjectsWorkspaceDto? = null,
    val subjects: List<SubjectAdminDto> = emptyList(),
    val query: String = "",
    val status: String = "all",
    val editorOpen: Boolean = false,
    val draft: SubjectDraft = SubjectDraft(),
    val deleteCandidate: SubjectAdminDto? = null,
    val isLoading: Boolean = false,
    val isLoadingMore: Boolean = false,
    val isSaving: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val canManage: Boolean get() = workspace?.capabilities?.manage == true
    val hasMore: Boolean get() = workspace?.meta?.hasMore == true
    val editing: Boolean get() = draft.id != null
}

@HiltViewModel
internal class SubjectsViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api: SubjectsApi = factory.create(SubjectsApi::class.java)
    private val _uiState = MutableStateFlow(SubjectsUiState())
    val uiState: StateFlow<SubjectsUiState> = _uiState.asStateFlow()

    fun load() = loadPage(reset = true)
    fun search() = loadPage(reset = true)
    fun loadMore() = loadPage(reset = false)

    fun setQuery(value: String) = _uiState.update {
        it.copy(query = value.take(120), errorMessage = null)
    }

    fun setStatus(value: String) {
        if (value == _uiState.value.status) return
        _uiState.update { it.copy(status = value, errorMessage = null) }
        loadPage(reset = true)
    }

    fun create() = _uiState.update {
        it.copy(
            editorOpen = true,
            draft = SubjectDraft(),
            deleteCandidate = null,
            errorMessage = null,
            message = null,
        )
    }

    fun edit(subject: SubjectAdminDto) = _uiState.update {
        it.copy(
            editorOpen = true,
            draft = SubjectDraft.from(subject),
            deleteCandidate = null,
            errorMessage = null,
            message = null,
        )
    }

    fun closeEditor() = _uiState.update {
        it.copy(editorOpen = false, draft = SubjectDraft(), deleteCandidate = null, errorMessage = null)
    }

    fun setName(value: String) = _uiState.update {
        it.copy(draft = it.draft.copy(name = value.take(100)), errorMessage = null)
    }

    fun setCode(value: String) = _uiState.update {
        it.copy(draft = it.draft.copy(code = value.take(10).uppercase()), errorMessage = null)
    }

    fun setActive(value: Boolean) = _uiState.update {
        it.copy(draft = it.draft.copy(active = value), errorMessage = null)
    }

    fun requestDelete(subject: SubjectAdminDto) {
        if (!_uiState.value.canManage || subject.references.total > 0) return
        _uiState.update { it.copy(deleteCandidate = subject, errorMessage = null, message = null) }
    }

    fun cancelDelete() = _uiState.update { it.copy(deleteCandidate = null) }

    fun save() {
        val state = _uiState.value
        if (!state.canManage || state.isSaving) return
        if (!state.draft.valid) {
            _uiState.update { it.copy(errorMessage = "Subject name is required and the code must not exceed 10 characters.") }
            return
        }
        val draft = state.draft

        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            try {
                val response = if (draft.id == null) {
                    api.create(draft.toRequest())
                } else {
                    api.update(draft.id, draft.toRequest())
                }
                _uiState.update {
                    it.copy(
                        editorOpen = false,
                        draft = SubjectDraft(),
                        isSaving = false,
                        message = response.message,
                    )
                }
                loadPage(reset = true, preserveMessage = true)
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isSaving = false, errorMessage = error.subjectsMessage()) }
            }
        }
    }

    fun confirmDelete() {
        val state = _uiState.value
        val subject = state.deleteCandidate ?: return
        if (!state.canManage || state.isSaving || subject.references.total > 0) return

        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            try {
                val response = api.delete(subject.id)
                _uiState.update {
                    it.copy(
                        deleteCandidate = null,
                        isSaving = false,
                        message = response.message,
                    )
                }
                loadPage(reset = true, preserveMessage = true)
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isSaving = false, errorMessage = error.subjectsMessage()) }
            }
        }
    }

    private fun loadPage(reset: Boolean, preserveMessage: Boolean = false) {
        val current = _uiState.value
        if ((reset && current.isLoading) || (!reset && (current.isLoadingMore || !current.hasMore))) return
        val page = if (reset) 1 else (current.workspace?.meta?.page ?: 1) + 1

        viewModelScope.launch {
            _uiState.update {
                it.copy(
                    isLoading = reset,
                    isLoadingMore = !reset,
                    errorMessage = null,
                    message = if (preserveMessage) it.message else null,
                )
            }
            try {
                val workspace = api.index(
                    search = _uiState.value.query.trim().ifBlank { null },
                    status = _uiState.value.status,
                    page = page,
                )
                _uiState.update { state ->
                    state.copy(
                        workspace = workspace,
                        subjects = if (reset) workspace.subjects else (state.subjects + workspace.subjects).distinctBy { it.id },
                        query = workspace.selected.search,
                        status = workspace.selected.status,
                        isLoading = false,
                        isLoadingMore = false,
                    )
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update {
                    it.copy(isLoading = false, isLoadingMore = false, errorMessage = error.subjectsMessage())
                }
            }
        }
    }
}

private fun Throwable.subjectsMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "Your account is not permitted to manage subjects."
        404 -> "This subject is no longer available."
        422 -> "Check the subject name/code. A duplicate may already exist, or the subject may still be in use."
        else -> "The subjects service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the subjects service. Check your connection and try again."
}
