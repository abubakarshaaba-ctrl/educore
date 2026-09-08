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
import online.educoreng.educore.core.network.CurriculumApi
import online.educoreng.educore.core.network.dto.CurriculumRuleCreateRequestDto
import online.educoreng.educore.core.network.dto.CurriculumRuleDto
import online.educoreng.educore.core.network.dto.CurriculumRuleUpdateRequestDto
import online.educoreng.educore.core.network.dto.CurriculumTrackDto
import online.educoreng.educore.core.network.dto.CurriculumTrackRequestDto
import online.educoreng.educore.core.network.dto.CurriculumWorkspaceDto
import retrofit2.HttpException

enum class CurriculumTab { TRACKS, RULES }
enum class CurriculumEditor { NONE, TRACK, RULE }

internal data class CurriculumTrackDraft(
    val id: Long? = null,
    val name: String = "",
    val section: String = "general",
    val active: Boolean = true,
) {
    val valid: Boolean get() = name.isNotBlank() && section.isNotBlank()

    companion object {
        fun from(track: CurriculumTrackDto) = CurriculumTrackDraft(
            id = track.id,
            name = track.name,
            section = track.section,
            active = track.active,
        )
    }
}

internal data class CurriculumRuleDraft(
    val id: Long? = null,
    val classLevelId: Long? = null,
    val trackId: Long? = null,
    val subjectId: Long? = null,
    val status: String = "compulsory",
    val electiveGroup: String = "",
    val minRequired: String = "",
    val maxAllowed: String = "",
    val active: Boolean = true,
) {
    val minValue: Int? get() = minRequired.trim().takeIf(String::isNotBlank)?.toIntOrNull()
    val maxValue: Int? get() = maxAllowed.trim().takeIf(String::isNotBlank)?.toIntOrNull()
    val numericValid: Boolean
        get() = (minRequired.isBlank() || minValue != null) &&
            (maxAllowed.isBlank() || maxValue != null) &&
            (minValue == null || maxValue == null || minValue!! <= maxValue!!)
    val valid: Boolean
        get() = classLevelId != null && subjectId != null && status.isNotBlank() && numericValid

    companion object {
        fun from(rule: CurriculumRuleDto) = CurriculumRuleDraft(
            id = rule.id,
            classLevelId = rule.classLevelId,
            trackId = rule.trackId,
            subjectId = rule.subjectId,
            status = rule.status,
            electiveGroup = rule.electiveGroup.orEmpty(),
            minRequired = rule.minRequired?.toString().orEmpty(),
            maxAllowed = rule.maxAllowed?.toString().orEmpty(),
            active = rule.active,
        )
    }
}

internal data class CurriculumUiState(
    val workspace: CurriculumWorkspaceDto? = null,
    val tab: CurriculumTab = CurriculumTab.TRACKS,
    val query: String = "",
    val levelId: Long? = null,
    val trackId: Long? = null,
    val status: String = "all",
    val editor: CurriculumEditor = CurriculumEditor.NONE,
    val trackDraft: CurriculumTrackDraft = CurriculumTrackDraft(),
    val ruleDraft: CurriculumRuleDraft = CurriculumRuleDraft(),
    val deleteTrackCandidate: CurriculumTrackDto? = null,
    val deleteRuleCandidate: CurriculumRuleDto? = null,
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val canManage: Boolean get() = workspace?.capabilities?.manage == true
    val editingTrack: Boolean get() = trackDraft.id != null
    val editingRule: Boolean get() = ruleDraft.id != null
}

@HiltViewModel
internal class CurriculumViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api: CurriculumApi = factory.create(CurriculumApi::class.java)
    private val _uiState = MutableStateFlow(CurriculumUiState())
    val uiState: StateFlow<CurriculumUiState> = _uiState.asStateFlow()

    fun load() = loadWorkspace()
    fun setTab(tab: CurriculumTab) = _uiState.update { it.copy(tab = tab, errorMessage = null, message = null) }
    fun setQuery(value: String) = _uiState.update { it.copy(query = value.take(120), errorMessage = null) }
    fun search() = loadWorkspace()

    fun setLevelFilter(id: Long?) {
        _uiState.update { it.copy(levelId = id, errorMessage = null) }
        loadWorkspace()
    }

    fun setTrackFilter(id: Long?) {
        _uiState.update { it.copy(trackId = id, errorMessage = null) }
        loadWorkspace()
    }

    fun setStatusFilter(status: String) {
        _uiState.update { it.copy(status = status, errorMessage = null) }
        loadWorkspace()
    }

    fun createTrack() = _uiState.update {
        it.copy(editor = CurriculumEditor.TRACK, trackDraft = CurriculumTrackDraft(), errorMessage = null, message = null)
    }

    fun editTrack(track: CurriculumTrackDto) {
        if (!track.manageable) return
        _uiState.update {
            it.copy(editor = CurriculumEditor.TRACK, trackDraft = CurriculumTrackDraft.from(track), errorMessage = null, message = null)
        }
    }

    fun setTrackName(value: String) = _uiState.update {
        it.copy(trackDraft = it.trackDraft.copy(name = value.take(80)), errorMessage = null)
    }
    fun setTrackSection(value: String) = _uiState.update {
        it.copy(trackDraft = it.trackDraft.copy(section = value), errorMessage = null)
    }
    fun setTrackActive(value: Boolean) = _uiState.update {
        it.copy(trackDraft = it.trackDraft.copy(active = value), errorMessage = null)
    }

    fun saveTrack() {
        val state = _uiState.value
        if (!state.canManage || state.isSaving || !state.trackDraft.valid) return
        val draft = state.trackDraft
        val body = CurriculumTrackRequestDto(draft.name.trim(), draft.section, draft.active)
        mutate {
            if (draft.id == null) api.createTrack(body).message else api.updateTrack(draft.id, body).message
        }
    }

    fun requestDeleteTrack(track: CurriculumTrackDto) {
        if (!track.manageable || track.references.total > 0) return
        _uiState.update { it.copy(deleteTrackCandidate = track, errorMessage = null, message = null) }
    }
    fun cancelDeleteTrack() = _uiState.update { it.copy(deleteTrackCandidate = null) }
    fun confirmDeleteTrack() {
        val id = _uiState.value.deleteTrackCandidate?.id ?: return
        mutate { api.deleteTrack(id).message }
    }

    fun createRule() = _uiState.update {
        it.copy(editor = CurriculumEditor.RULE, ruleDraft = CurriculumRuleDraft(), errorMessage = null, message = null)
    }

    fun editRule(rule: CurriculumRuleDto) = _uiState.update {
        it.copy(editor = CurriculumEditor.RULE, ruleDraft = CurriculumRuleDraft.from(rule), errorMessage = null, message = null)
    }

    fun setRuleLevel(id: Long?) = _uiState.update { it.copy(ruleDraft = it.ruleDraft.copy(classLevelId = id), errorMessage = null) }
    fun setRuleTrack(id: Long?) = _uiState.update { it.copy(ruleDraft = it.ruleDraft.copy(trackId = id), errorMessage = null) }
    fun setRuleSubject(id: Long?) = _uiState.update { it.copy(ruleDraft = it.ruleDraft.copy(subjectId = id), errorMessage = null) }
    fun setRuleStatus(value: String) = _uiState.update { it.copy(ruleDraft = it.ruleDraft.copy(status = value), errorMessage = null) }
    fun setRuleGroup(value: String) = _uiState.update { it.copy(ruleDraft = it.ruleDraft.copy(electiveGroup = value.take(60)), errorMessage = null) }
    fun setRuleMin(value: String) = _uiState.update { it.copy(ruleDraft = it.ruleDraft.copy(minRequired = value.filter(Char::isDigit).take(3)), errorMessage = null) }
    fun setRuleMax(value: String) = _uiState.update { it.copy(ruleDraft = it.ruleDraft.copy(maxAllowed = value.filter(Char::isDigit).take(3)), errorMessage = null) }
    fun setRuleActive(value: Boolean) = _uiState.update { it.copy(ruleDraft = it.ruleDraft.copy(active = value), errorMessage = null) }

    fun saveRule() {
        val state = _uiState.value
        val draft = state.ruleDraft
        if (!state.canManage || state.isSaving || !draft.valid) {
            if (!draft.numericValid) _uiState.update { it.copy(errorMessage = "Minimum and maximum must be valid numbers, and maximum cannot be below minimum.") }
            return
        }
        mutate {
            if (draft.id == null) {
                api.createRule(
                    CurriculumRuleCreateRequestDto(
                        classLevelId = requireNotNull(draft.classLevelId),
                        academicTrackId = draft.trackId,
                        subjectId = requireNotNull(draft.subjectId),
                        subjectStatus = draft.status,
                        electiveGroup = draft.electiveGroup.trim().ifBlank { null },
                        minRequired = draft.minValue,
                        maxAllowed = draft.maxValue,
                    )
                ).message
            } else {
                api.updateRule(
                    draft.id,
                    CurriculumRuleUpdateRequestDto(
                        subjectStatus = draft.status,
                        electiveGroup = draft.electiveGroup.trim().ifBlank { null },
                        minRequired = draft.minValue,
                        maxAllowed = draft.maxValue,
                        isActive = draft.active,
                    )
                ).message
            }
        }
    }

    fun requestDeleteRule(rule: CurriculumRuleDto) = _uiState.update {
        it.copy(deleteRuleCandidate = rule, errorMessage = null, message = null)
    }
    fun cancelDeleteRule() = _uiState.update { it.copy(deleteRuleCandidate = null) }
    fun confirmDeleteRule() {
        val id = _uiState.value.deleteRuleCandidate?.id ?: return
        mutate { api.deleteRule(id).message }
    }

    fun closeEditor() = _uiState.update {
        it.copy(
            editor = CurriculumEditor.NONE,
            trackDraft = CurriculumTrackDraft(),
            ruleDraft = CurriculumRuleDraft(),
            errorMessage = null,
        )
    }

    private fun mutate(action: suspend () -> String) {
        if (_uiState.value.isSaving) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            try {
                val message = action()
                _uiState.update {
                    it.copy(
                        isSaving = false,
                        editor = CurriculumEditor.NONE,
                        trackDraft = CurriculumTrackDraft(),
                        ruleDraft = CurriculumRuleDraft(),
                        deleteTrackCandidate = null,
                        deleteRuleCandidate = null,
                        message = message,
                    )
                }
                loadWorkspace(preserveMessage = true)
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isSaving = false, errorMessage = error.curriculumMessage()) }
            }
        }
    }

    private fun loadWorkspace(preserveMessage: Boolean = false) {
        if (_uiState.value.isLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null, message = if (preserveMessage) it.message else null) }
            try {
                val state = _uiState.value
                val workspace = api.index(
                    search = state.query.trim().ifBlank { null },
                    levelId = state.levelId,
                    trackId = state.trackId,
                    status = state.status,
                )
                _uiState.update {
                    it.copy(
                        workspace = workspace,
                        query = workspace.selected.search,
                        levelId = workspace.selected.levelId,
                        trackId = workspace.selected.trackId,
                        status = workspace.selected.status,
                        isLoading = false,
                    )
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isLoading = false, errorMessage = error.curriculumMessage()) }
            }
        }
    }
}

private fun Throwable.curriculumMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "Your account does not have curriculum management permission."
        404 -> "The selected curriculum record is unavailable for this school."
        422 -> "Check the curriculum details, duplicate rules and selection limits, then try again."
        else -> "The curriculum service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the curriculum service. Check your connection and try again."
}
