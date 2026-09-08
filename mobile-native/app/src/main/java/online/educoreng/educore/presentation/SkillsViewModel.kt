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
import online.educoreng.educore.core.network.SkillsApi
import online.educoreng.educore.core.network.dto.SkillRatingMutationDto
import online.educoreng.educore.core.network.dto.SkillStudentMutationDto
import online.educoreng.educore.core.network.dto.SkillsSaveRequestDto
import online.educoreng.educore.core.network.dto.SkillsSheetDto
import online.educoreng.educore.core.network.dto.SkillsWorkspaceDto
import retrofit2.HttpException

enum class SkillCategory(val key: String, val label: String) {
    AFFECTIVE("affective", "Behavioural"),
    PSYCHOMOTOR("psychomotor", "Psychomotor"),
}

internal data class SkillRatingKey(val studentId: Long, val skillId: Long)

internal data class SkillsUiState(
    val workspace: SkillsWorkspaceDto? = null,
    val sheet: SkillsSheetDto? = null,
    val selectedClassId: Long? = null,
    val selectedTermId: Long? = null,
    val category: SkillCategory = SkillCategory.AFFECTIVE,
    val studentIndex: Int = 0,
    val baselineRatings: Map<SkillRatingKey, Int> = emptyMap(),
    val draftRatings: Map<SkillRatingKey, Int> = emptyMap(),
    val dirtyKeys: Set<SkillRatingKey> = emptySet(),
    val discardPending: Boolean = false,
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val inSheet: Boolean get() = sheet != null
    val canManage: Boolean get() = sheet?.capabilities?.manage ?: workspace?.capabilities?.manage == true
    val canOpenSheet: Boolean get() = selectedClassId != null && selectedTermId != null
    val dirty: Boolean get() = dirtyKeys.isNotEmpty()
    val currentStudent get() = sheet?.students?.getOrNull(studentIndex)
    val categorySkills get() = sheet?.skills.orEmpty().filter { it.category.equals(category.key, ignoreCase = true) }
    val studentCount: Int get() = sheet?.students?.size ?: 0
}

@HiltViewModel
internal class SkillsViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api: SkillsApi = factory.create(SkillsApi::class.java)
    private val _uiState = MutableStateFlow(SkillsUiState())
    val uiState: StateFlow<SkillsUiState> = _uiState.asStateFlow()

    fun load() {
        if (_uiState.value.isLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null, message = null) }
            try {
                val workspace = api.index()
                val currentTerm = workspace.terms.firstOrNull { it.current } ?: workspace.terms.firstOrNull()
                val selectedClass = _uiState.value.selectedClassId
                    ?.takeIf { id -> workspace.classes.any { it.id == id } }
                    ?: workspace.classes.singleOrNull()?.id
                    ?: workspace.classes.firstOrNull()?.id
                val selectedTerm = _uiState.value.selectedTermId
                    ?.takeIf { id -> workspace.terms.any { it.id == id } }
                    ?: currentTerm?.id
                _uiState.update {
                    it.copy(
                        workspace = workspace,
                        selectedClassId = selectedClass,
                        selectedTermId = selectedTerm,
                        isLoading = false,
                    )
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isLoading = false, errorMessage = error.skillsMessage()) }
            }
        }
    }

    fun selectClass(id: Long?) = _uiState.update {
        it.copy(selectedClassId = id, errorMessage = null, message = null)
    }

    fun selectTerm(id: Long?) = _uiState.update {
        it.copy(selectedTermId = id, errorMessage = null, message = null)
    }

    fun openSheet() {
        val state = _uiState.value
        val classId = state.selectedClassId ?: return
        val termId = state.selectedTermId ?: return
        if (state.isLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null, message = null) }
            try {
                val sheet = api.sheet(classId, termId)
                val ratings = buildMap {
                    sheet.students.forEach { student ->
                        student.ratings.forEach { rating ->
                            put(SkillRatingKey(student.id, rating.skillId), rating.rating)
                        }
                    }
                }
                val availableCategories = sheet.skills.map { it.category.lowercase() }.toSet()
                val category = when {
                    SkillCategory.AFFECTIVE.key in availableCategories -> SkillCategory.AFFECTIVE
                    SkillCategory.PSYCHOMOTOR.key in availableCategories -> SkillCategory.PSYCHOMOTOR
                    else -> _uiState.value.category
                }
                _uiState.update {
                    it.copy(
                        sheet = sheet,
                        baselineRatings = ratings,
                        draftRatings = ratings,
                        dirtyKeys = emptySet(),
                        category = category,
                        studentIndex = 0,
                        discardPending = false,
                        isLoading = false,
                    )
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isLoading = false, errorMessage = error.skillsMessage()) }
            }
        }
    }

    fun setCategory(category: SkillCategory) = _uiState.update {
        it.copy(category = category, errorMessage = null)
    }

    fun previousStudent() = _uiState.update {
        it.copy(studentIndex = (it.studentIndex - 1).coerceAtLeast(0))
    }

    fun nextStudent() = _uiState.update {
        val max = (it.studentCount - 1).coerceAtLeast(0)
        it.copy(studentIndex = (it.studentIndex + 1).coerceAtMost(max))
    }

    fun selectStudent(index: Int) = _uiState.update {
        val max = (it.studentCount - 1).coerceAtLeast(0)
        it.copy(studentIndex = index.coerceIn(0, max))
    }

    fun rate(skillId: Long, rating: Int) {
        val state = _uiState.value
        val studentId = state.currentStudent?.id ?: return
        if (rating !in 0..5 || !state.canManage) return
        val key = SkillRatingKey(studentId, skillId)
        val draft = state.draftRatings.toMutableMap().apply {
            if (rating == 0) remove(key) else put(key, rating)
        }
        val dirty = state.dirtyKeys.toMutableSet()
        if (draft[key] == state.baselineRatings[key]) dirty.remove(key) else dirty.add(key)
        _uiState.update {
            it.copy(
                draftRatings = draft,
                dirtyKeys = dirty,
                errorMessage = null,
                message = null,
            )
        }
    }

    fun save() {
        val state = _uiState.value
        val sheet = state.sheet ?: return
        if (!state.canManage || !state.dirty || state.isSaving) return

        val mutations = state.dirtyKeys
            .groupBy { it.studentId }
            .map { (studentId, keys) ->
                SkillStudentMutationDto(
                    studentId = studentId,
                    skills = keys.map { key ->
                        SkillRatingMutationDto(
                            skillId = key.skillId,
                            rating = state.draftRatings[key] ?: 0,
                        )
                    },
                )
            }

        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            try {
                val result = api.save(
                    SkillsSaveRequestDto(
                        classArmId = sheet.classInfo.id,
                        termId = sheet.term.id,
                        ratings = mutations,
                    )
                )
                _uiState.update {
                    it.copy(
                        baselineRatings = it.draftRatings,
                        dirtyKeys = emptySet(),
                        isSaving = false,
                        message = result.message,
                    )
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isSaving = false, errorMessage = error.skillsMessage()) }
            }
        }
    }

    fun requestCloseSheet() {
        val state = _uiState.value
        if (!state.inSheet) return
        if (state.dirty) {
            _uiState.update { it.copy(discardPending = true, errorMessage = null) }
        } else {
            closeSheet()
        }
    }

    fun cancelDiscard() = _uiState.update { it.copy(discardPending = false) }

    fun confirmDiscard() = closeSheet()

    private fun closeSheet() = _uiState.update {
        it.copy(
            sheet = null,
            baselineRatings = emptyMap(),
            draftRatings = emptyMap(),
            dirtyKeys = emptySet(),
            studentIndex = 0,
            discardPending = false,
            errorMessage = null,
            message = null,
        )
    }

    fun consumeMessage() = _uiState.update { it.copy(message = null) }
}

private fun Throwable.skillsMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "You do not have permission to rate skills for that class."
        404 -> "The selected class, term or rating sheet is unavailable."
        422 -> "The rating sheet contains invalid or stale student/skill data. Reload and try again."
        else -> "The Skills service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the Skills service. Check your connection and try again."
}
