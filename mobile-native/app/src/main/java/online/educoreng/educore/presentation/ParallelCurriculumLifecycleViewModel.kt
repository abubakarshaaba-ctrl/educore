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
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.data.repository.ParallelCurriculumLifecycleRepository
import online.educoreng.educore.core.model.ParallelLifecycleWorkspace
import online.educoreng.educore.core.model.ParallelLifecycleStudentPage
import online.educoreng.educore.core.model.ParallelPromotionPreview

data class ParallelLifecycleUiState(
    val workspace: ParallelLifecycleWorkspace? = null,
    val promotionPreview: ParallelPromotionPreview? = null,
    val selectedCurriculumId: Long? = null,
    val selectedSessionId: Long? = null,
    val sourceSessionId: Long? = null,
    val targetSessionId: Long? = null,
    val studentPage: ParallelLifecycleStudentPage? = null,
    val studentConventionalClassArmId: Long? = null,
    val studentAssignmentStatus: String = "all",
    val studentGender: String? = null,
    val studentSearch: String = "",
    val studentPageNumber: Int = 1,
    val isLoading: Boolean = false,
    val isStudentLoading: Boolean = false,
    val isMutating: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
)

@HiltViewModel
class ParallelCurriculumLifecycleViewModel @Inject constructor(
    private val repository: ParallelCurriculumLifecycleRepository,
) : ViewModel() {

    private val _uiState = MutableStateFlow(ParallelLifecycleUiState())
    val uiState: StateFlow<ParallelLifecycleUiState> = _uiState.asStateFlow()

    fun load(curriculumId: Long? = null, sessionId: Long? = null) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            when (val result = repository.load(curriculumId, sessionId)) {
                is AppResult.Success -> {
                    val workspace = result.value
                    _uiState.update {
                        it.copy(
                            workspace = workspace,
                            selectedCurriculumId = workspace.selectedCurriculumId,
                            selectedSessionId = workspace.selectedSessionId,
                            isLoading = false,
                        )
                    }
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isLoading = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun selectCurriculum(id: Long) {
        val sessionId = _uiState.value.selectedSessionId
        _uiState.update {
            it.copy(
                selectedCurriculumId = id,
                promotionPreview = null,
                studentPage = null,
                studentPageNumber = 1,
            )
        }
        load(id, sessionId)
    }

    fun selectSession(id: Long) {
        val curriculumId = _uiState.value.selectedCurriculumId
        _uiState.update {
            it.copy(
                selectedSessionId = id,
                promotionPreview = null,
                studentPage = null,
                studentPageNumber = 1,
            )
        }
        load(curriculumId, id)
    }

    fun loadStudents(
        conventionalClassArmId: Long? = _uiState.value.studentConventionalClassArmId,
        assignmentStatus: String = _uiState.value.studentAssignmentStatus,
        gender: String? = _uiState.value.studentGender,
        search: String = _uiState.value.studentSearch,
        page: Int = 1,
    ) {
        val curriculumId = _uiState.value.selectedCurriculumId
            ?: return failLocal("Select a parallel curriculum first.")
        val sessionId = _uiState.value.selectedSessionId
            ?: return failLocal("Select an academic session first.")

        viewModelScope.launch {
            _uiState.update {
                it.copy(
                    studentConventionalClassArmId = conventionalClassArmId,
                    studentAssignmentStatus = assignmentStatus,
                    studentGender = gender,
                    studentSearch = search,
                    studentPageNumber = page,
                    isStudentLoading = true,
                    errorMessage = null,
                )
            }

            when (
                val result = repository.loadStudents(
                    curriculumId = curriculumId,
                    sessionId = sessionId,
                    conventionalClassArmId = conventionalClassArmId,
                    assignmentStatus = assignmentStatus,
                    gender = gender,
                    search = search.trim().takeIf(String::isNotBlank),
                    page = page,
                )
            ) {
                is AppResult.Success -> _uiState.update {
                    it.copy(
                        studentPage = result.value,
                        studentPageNumber = result.value.pagination.currentPage,
                        isStudentLoading = false,
                    )
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(
                        isStudentLoading = false,
                        errorMessage = result.error.userMessage,
                    )
                }
            }
        }
    }

    fun assignStudents(
        classId: Long,
        armId: Long,
        studentIds: List<Long>,
    ) {
        val state = _uiState.value
        val sessionId = state.selectedSessionId
            ?: return failLocal("Select an academic session first.")
        if (studentIds.isEmpty()) return failLocal("Select at least one learner.")

        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null) }
            when (val result = repository.assignStudents(classId, armId, sessionId, studentIds.distinct())) {
                is AppResult.Success -> {
                    _uiState.update { it.copy(isMutating = false, message = result.value) }
                    load(_uiState.value.selectedCurriculumId, sessionId)
                    loadStudents(
                        conventionalClassArmId = _uiState.value.studentConventionalClassArmId,
                        assignmentStatus = _uiState.value.studentAssignmentStatus,
                        gender = _uiState.value.studentGender,
                        search = _uiState.value.studentSearch,
                        page = _uiState.value.studentPageNumber,
                    )
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isMutating = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun removeStudent(enrolmentId: Long) {
        val state = _uiState.value
        val sessionId = state.selectedSessionId
            ?: return failLocal("Select an academic session first.")

        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null) }
            when (val result = repository.removeStudent(enrolmentId)) {
                is AppResult.Success -> {
                    _uiState.update { it.copy(isMutating = false, message = result.value) }
                    load(_uiState.value.selectedCurriculumId, sessionId)
                    loadStudents(
                        conventionalClassArmId = _uiState.value.studentConventionalClassArmId,
                        assignmentStatus = _uiState.value.studentAssignmentStatus,
                        gender = _uiState.value.studentGender,
                        search = _uiState.value.studentSearch,
                        page = _uiState.value.studentPageNumber,
                    )
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isMutating = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun selectPromotionSessions(sourceId: Long?, targetId: Long?) {
        _uiState.update {
            it.copy(
                sourceSessionId = sourceId,
                targetSessionId = targetId,
                promotionPreview = null,
            )
        }
    }

    fun previewPromotion() {
        val state = _uiState.value
        val curriculumId = state.selectedCurriculumId ?: return failLocal("Select a parallel curriculum first.")
        val sourceId = state.sourceSessionId ?: return failLocal("Select the source academic session.")
        val targetId = state.targetSessionId ?: return failLocal("Select the target academic session.")
        if (sourceId == targetId) return failLocal("Source and target sessions must be different.")

        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null) }
            when (val result = repository.previewPromotion(curriculumId, sourceId, targetId)) {
                is AppResult.Success -> _uiState.update {
                    it.copy(promotionPreview = result.value, isMutating = false)
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isMutating = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun executePromotion() {
        val state = _uiState.value
        val curriculumId = state.selectedCurriculumId ?: return failLocal("Select a parallel curriculum first.")
        val sourceId = state.sourceSessionId ?: return failLocal("Select the source academic session.")
        val targetId = state.targetSessionId ?: return failLocal("Select the target academic session.")
        val preview = state.promotionPreview ?: return failLocal("Preview promotion before execution.")
        if (preview.counts.blocked > 0) return failLocal("Resolve all blocked learners before promotion.")

        mutate(
            action = { repository.executePromotion(curriculumId, sourceId, targetId) },
            reload = true,
            clearPreview = true,
        )
    }

    fun transfer(
        enrolmentId: Long,
        destinationClassId: Long,
        destinationArmId: Long,
        reason: String,
        effectiveDate: String?,
    ) {
        if (reason.isBlank()) return failLocal("Enter a reason for the transfer.")
        mutate {
            repository.transfer(
                enrolmentId,
                destinationClassId,
                destinationArmId,
                reason.trim(),
                effectiveDate?.takeIf(String::isNotBlank),
            )
        }
    }

    fun createArm(classId: Long, name: String, code: String?, capacity: Int?) {
        if (name.isBlank()) return failLocal("Enter the class arm name.")
        mutate {
            repository.createArm(classId, name.trim(), code?.trim()?.takeIf(String::isNotBlank), capacity)
        }
    }

    fun updateArm(armId: Long, name: String, code: String?, capacity: Int?) {
        if (name.isBlank()) return failLocal("Enter the class arm name.")
        mutate {
            repository.updateArm(armId, name.trim(), code?.trim()?.takeIf(String::isNotBlank), capacity)
        }
    }

    fun archiveArm(armId: Long) {
        mutate { repository.archiveArm(armId) }
    }

    fun saveArmTeacher(
        armId: Long,
        subjectId: Long,
        teacherId: Long?,
    ) {
        mutate { repository.saveArmTeacher(armId, subjectId, teacherId) }
    }

    fun saveGrade(
        classIds: List<Long>,
        gradeLetter: String,
        minScore: Double?,
        maxScore: Double?,
        remark: String?,
        isPassGrade: Boolean,
        gradePoint: Double?,
    ) {
        val curriculumId = _uiState.value.selectedCurriculumId
            ?: return failLocal("Select a parallel curriculum first.")
        if (classIds.isEmpty()) return failLocal("Select at least one class level.")
        if (gradeLetter.isBlank()) return failLocal("Enter a grade letter.")
        if (minScore == null || maxScore == null || minScore > maxScore) {
            return failLocal("Enter a valid minimum and maximum score.")
        }
        mutate {
            repository.saveGrade(
                curriculumId,
                classIds,
                gradeLetter.trim(),
                minScore,
                maxScore,
                remark?.trim()?.takeIf(String::isNotBlank),
                isPassGrade,
                gradePoint,
            )
        }
    }

    fun deleteGrade(gradeId: Long) {
        mutate { repository.deleteGrade(gradeId) }
    }

    fun savePromotionRule(
        sourceClassId: Long,
        destinationClassId: Long?,
        minimumAverage: Double?,
        maxFailedSubjects: Int?,
        requireCompleteResult: Boolean,
        failureAction: String,
        armStrategy: String,
        isTerminal: Boolean,
    ) {
        val curriculumId = _uiState.value.selectedCurriculumId
            ?: return failLocal("Select a parallel curriculum first.")
        if (minimumAverage == null || maxFailedSubjects == null) {
            return failLocal("Enter the promotion average and failed-subject limit.")
        }
        if (!isTerminal && destinationClassId == null) {
            return failLocal("Select the next class or mark this as a terminal class.")
        }

        mutate {
            repository.savePromotionRule(
                curriculumId,
                sourceClassId,
                destinationClassId,
                minimumAverage,
                maxFailedSubjects,
                requireCompleteResult,
                failureAction,
                armStrategy,
                isTerminal,
            )
        }
    }

    fun consumeMessage() {
        _uiState.update { it.copy(message = null) }
    }

    fun consumeError() {
        _uiState.update { it.copy(errorMessage = null) }
    }

    private fun mutate(
        reload: Boolean = true,
        clearPreview: Boolean = false,
        action: suspend () -> AppResult<String>,
    ) {
        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null) }
            when (val result = action()) {
                is AppResult.Success -> {
                    _uiState.update {
                        it.copy(
                            isMutating = false,
                            message = result.value,
                            promotionPreview = if (clearPreview) null else it.promotionPreview,
                        )
                    }
                    if (reload) load(_uiState.value.selectedCurriculumId, _uiState.value.selectedSessionId)
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isMutating = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    private fun failLocal(message: String) {
        _uiState.update { it.copy(errorMessage = message) }
    }
}
