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
import online.educoreng.educore.core.model.DownloadedDocument
import online.educoreng.educore.core.model.ParallelLifecycleWorkspace
import online.educoreng.educore.core.model.ParallelLifecycleStudentPage
import online.educoreng.educore.core.model.ParallelResultWorkspace
import online.educoreng.educore.core.model.ParallelStudentResultDetail
import online.educoreng.educore.core.model.ParallelPromotionPreview
import online.educoreng.educore.core.model.ParallelOperationsWorkspace
import online.educoreng.educore.core.model.ParallelAttendanceDraft

data class ParallelLifecycleUiState(
    val workspace: ParallelLifecycleWorkspace? = null,
    val promotionPreview: ParallelPromotionPreview? = null,
    val selectedCurriculumId: Long? = null,
    val selectedSessionId: Long? = null,
    val sourceSessionId: Long? = null,
    val targetSessionId: Long? = null,
    val promotionSourceClassIds: List<Long> = emptyList(),
    val studentPage: ParallelLifecycleStudentPage? = null,
    val studentConventionalClassArmId: Long? = null,
    val studentAssignmentStatus: String = "all",
    val studentLearnerStatus: String = "active",
    val studentGender: String? = null,
    val studentSearch: String = "",
    val studentPageNumber: Int = 1,
    val resultWorkspace: ParallelResultWorkspace? = null,
    val studentResultDetail: ParallelStudentResultDetail? = null,
    val operationsWorkspace: ParallelOperationsWorkspace? = null,
    val isLoading: Boolean = false,
    val isOperationsLoading: Boolean = false,
    val isResultLoading: Boolean = false,
    val isStudentLoading: Boolean = false,
    val isMutating: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
    val downloadedDocument: DownloadedDocument? = null,
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
                operationsWorkspace = null,
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
                operationsWorkspace = null,
            )
        }
        load(curriculumId, id)
    }

    fun loadOperations(
        classId: Long? = _uiState.value.operationsWorkspace?.selected?.classId,
        armId: Long? = _uiState.value.operationsWorkspace?.selected?.armId,
        termId: Long? = _uiState.value.operationsWorkspace?.selected?.termId,
        date: String? = _uiState.value.operationsWorkspace?.selected?.date,
    ) {
        val state = _uiState.value
        val curriculumId = state.selectedCurriculumId
            ?: return failLocal("Select a parallel curriculum first.")
        val sessionId = state.selectedSessionId
            ?: return failLocal("Select an academic session first.")

        loadOperationsContext(
            curriculumId = curriculumId,
            sessionId = sessionId,
            classId = classId,
            armId = armId,
            termId = termId,
            date = date,
        )
    }

    fun loadOperationsContext(
        curriculumId: Long? = null,
        sessionId: Long? = null,
        classId: Long? = null,
        armId: Long? = null,
        termId: Long? = null,
        date: String? = null,
    ) {
        viewModelScope.launch {
            _uiState.update { it.copy(isOperationsLoading = true, errorMessage = null) }
            when (
                val result = repository.loadOperations(
                    curriculumId = curriculumId,
                    sessionId = sessionId,
                    termId = termId,
                    classId = classId,
                    armId = armId,
                    date = date,
                )
            ) {
                is AppResult.Success -> {
                    val workspace = result.value
                    _uiState.update {
                        it.copy(
                            operationsWorkspace = workspace,
                            isOperationsLoading = false,
                        )
                    }
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(
                        isOperationsLoading = false,
                        errorMessage = result.error.userMessage,
                    )
                }
            }
        }
    }

    fun createTimetablePeriod(
        classId: Long,
        armId: Long,
        subjectId: Long,
        dayOfWeek: String,
        startTime: String,
        endTime: String,
        venue: String?,
    ) {
        val sessionId = _uiState.value.operationsWorkspace?.selected?.sessionId
            ?: _uiState.value.selectedSessionId
            ?: return failLocal("Select an academic session first.")
        val timePattern = Regex("^([01]\\d|2[0-3]):[0-5]\\d$")
        if (!timePattern.matches(startTime) || !timePattern.matches(endTime)) {
            return failLocal("Enter timetable times in HH:mm format.")
        }
        if (endTime <= startTime) return failLocal("End time must be later than start time.")

        mutateOperations {
            repository.createTimetablePeriod(
                classId = classId,
                armId = armId,
                subjectId = subjectId,
                sessionId = sessionId,
                dayOfWeek = dayOfWeek.lowercase(),
                startTime = startTime,
                endTime = endTime,
                venue = venue?.trim()?.takeIf(String::isNotBlank),
            )
        }
    }

    fun deleteTimetablePeriod(periodId: Long) {
        mutateOperations { repository.deleteTimetablePeriod(periodId) }
    }

    fun downloadAttendanceExport(format: String) {
        val operations = _uiState.value.operationsWorkspace
            ?: return failLocal("Load the parallel attendance workspace first.")
        if (!operations.capabilities.exportAttendance) {
            return failLocal("You do not have permission to export parallel attendance.")
        }
        val armId = operations.selected.armId
            ?: return failLocal("Select a parallel class arm.")
        val termId = operations.selected.termId
            ?: return failLocal("Select an academic term.")

        download {
            repository.downloadAttendanceExport(
                armId = armId,
                termId = termId,
                format = format,
            )
        }
    }

    fun saveParallelAttendance(
        records: List<ParallelAttendanceDraft>,
    ) {
        val operations = _uiState.value.operationsWorkspace
            ?: return failLocal("Load the parallel attendance sheet first.")
        val armId = operations.selected.armId
            ?: return failLocal("Select a parallel class arm.")
        val termId = operations.selected.termId
            ?: return failLocal("Select an academic term.")
        val sheet = operations.attendance
            ?: return failLocal("Load the parallel attendance sheet first.")
        if (records.isEmpty()) return failLocal("There are no learner attendance rows to save.")
        if (records.any { it.status !in setOf("present", "absent", "late", "excused") }) {
            return failLocal("Every learner must have a valid attendance status.")
        }

        mutateOperations {
            repository.saveParallelAttendance(
                armId = armId,
                termId = termId,
                date = sheet.date,
                version = sheet.version,
                records = records,
            )
        }
    }

    fun loadResults(
        classId: Long? = _uiState.value.resultWorkspace?.selectedClassId,
        termId: Long? = _uiState.value.resultWorkspace?.selectedTermId,
    ) {
        viewModelScope.launch {
            _uiState.update {
                it.copy(
                    isResultLoading = true,
                    errorMessage = null,
                    studentResultDetail = null,
                )
            }

            when (val result = repository.loadResults(classId, termId)) {
                is AppResult.Success -> _uiState.update {
                    it.copy(
                        resultWorkspace = result.value,
                        isResultLoading = false,
                    )
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(
                        isResultLoading = false,
                        errorMessage = result.error.userMessage,
                    )
                }
            }
        }
    }

    fun loadStudentResult(
        classId: Long,
        studentId: Long,
        termId: Long,
    ) {
        viewModelScope.launch {
            _uiState.update {
                it.copy(
                    isResultLoading = true,
                    errorMessage = null,
                    studentResultDetail = null,
                )
            }
            when (val result = repository.loadStudentResult(classId, studentId, termId)) {
                is AppResult.Success -> _uiState.update {
                    it.copy(
                        studentResultDetail = result.value,
                        isResultLoading = false,
                    )
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(
                        isResultLoading = false,
                        errorMessage = result.error.userMessage,
                    )
                }
            }
        }
    }

    fun closeStudentResult() {
        _uiState.update { it.copy(studentResultDetail = null) }
    }

    fun publishResult() {
        val report = _uiState.value.resultWorkspace?.report
            ?: return failLocal("Open a parallel result register first.")
        if (!report.canPublish) {
            return failLocal(
                report.blockers.firstOrNull()
                    ?: "Complete the result before publication."
            )
        }

        mutateResult {
            repository.publishResult(report.classId, report.termId)
        }
    }

    fun unpublishResult() {
        val report = _uiState.value.resultWorkspace?.report
            ?: return failLocal("Open a parallel result register first.")

        mutateResult {
            repository.unpublishResult(report.classId, report.termId)
        }
    }

    fun downloadResultExport(format: String) {
        val report = _uiState.value.resultWorkspace?.report
            ?: return failLocal("Open a parallel result register first.")
        download {
            repository.downloadResultExport(report.classId, report.termId, format)
        }
    }

    fun downloadStudentResultPdf() {
        val detail = _uiState.value.studentResultDetail
            ?: return failLocal("Open a learner result first.")
        download {
            repository.downloadStudentResultPdf(
                detail.classId,
                detail.studentId,
                detail.termId,
            )
        }
    }

    fun createProgramme(
        name: String,
        code: String?,
        defaultAssessmentTemplateId: Long?,
    ) {
        if (name.isBlank()) return failLocal("Enter the parallel programme name.")
        val templateId = defaultAssessmentTemplateId
            ?: return failLocal("Select the default assessment template.")

        mutate {
            repository.createProgramme(
                name.trim(),
                code?.trim()?.takeIf(String::isNotBlank),
                templateId,
            )
        }
    }

    fun updateProgramme(
        curriculumId: Long,
        name: String,
        code: String?,
        defaultAssessmentTemplateId: Long?,
    ) {
        if (name.isBlank()) return failLocal("Enter the parallel programme name.")
        val templateId = defaultAssessmentTemplateId
            ?: return failLocal("Select the default assessment template.")

        mutate {
            repository.updateProgramme(
                curriculumId,
                name.trim(),
                code?.trim()?.takeIf(String::isNotBlank),
                templateId,
            )
        }
    }

    fun createClass(
        curriculumId: Long,
        name: String,
        code: String?,
        assessmentTemplateId: Long?,
    ) {
        if (name.isBlank()) return failLocal("Enter the parallel class level name.")
        mutate {
            repository.createClass(
                curriculumId,
                name.trim(),
                code?.trim()?.takeIf(String::isNotBlank),
                assessmentTemplateId,
            )
        }
    }

    fun updateClass(
        classId: Long,
        name: String,
        code: String?,
        assessmentTemplateId: Long?,
    ) {
        if (name.isBlank()) return failLocal("Enter the parallel class level name.")
        mutate {
            repository.updateClass(
                classId,
                name.trim(),
                code?.trim()?.takeIf(String::isNotBlank),
                assessmentTemplateId,
            )
        }
    }

    fun createSubject(
        curriculumId: Long,
        name: String,
        code: String?,
    ) {
        if (name.isBlank()) return failLocal("Enter the parallel subject name.")
        mutate {
            repository.createSubject(
                curriculumId,
                name.trim(),
                code?.trim()?.takeIf(String::isNotBlank),
            )
        }
    }

    fun updateSubject(
        subjectId: Long,
        name: String,
        code: String?,
    ) {
        if (name.isBlank()) return failLocal("Enter the parallel subject name.")
        mutate {
            repository.updateSubject(
                subjectId,
                name.trim(),
                code?.trim()?.takeIf(String::isNotBlank),
            )
        }
    }

    fun saveClassSubject(
        classId: Long,
        subjectId: Long,
        teacherId: Long?,
    ) {
        mutate { repository.saveClassSubject(classId, subjectId, teacherId) }
    }

    fun removeClassSubject(assignmentId: Long) {
        mutate { repository.removeClassSubject(assignmentId) }
    }

    fun saveProgrammeGrade(
        gradeLetter: String,
        minScore: Double?,
        maxScore: Double?,
        remark: String?,
        isPassGrade: Boolean,
    ) {
        val curriculumId = _uiState.value.selectedCurriculumId
            ?: return failLocal("Select a parallel curriculum first.")
        if (gradeLetter.isBlank()) return failLocal("Enter a programme grade letter.")
        if (minScore == null || maxScore == null || minScore > maxScore) {
            return failLocal("Enter a valid minimum and maximum score.")
        }

        mutate {
            repository.saveProgrammeGrade(
                curriculumId,
                gradeLetter.trim(),
                minScore,
                maxScore,
                remark?.trim()?.takeIf(String::isNotBlank),
                isPassGrade,
            )
        }
    }

    fun deleteProgrammeGrade(gradeId: Long) {
        mutate { repository.deleteProgrammeGrade(gradeId) }
    }

    fun loadStudents(
        conventionalClassArmId: Long? = _uiState.value.studentConventionalClassArmId,
        assignmentStatus: String = _uiState.value.studentAssignmentStatus,
        learnerStatus: String = _uiState.value.studentLearnerStatus,
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
                    studentLearnerStatus = learnerStatus,
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
                    learnerStatus = learnerStatus,
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

    fun downloadStudentAssignmentTemplate() {
        download { repository.downloadStudentAssignmentTemplate() }
    }

    fun importStudentAssignments(
        filename: String,
        mimeType: String,
        bytes: ByteArray,
    ) {
        val state = _uiState.value
        val curriculumId = state.selectedCurriculumId
            ?: return failLocal("Select a parallel curriculum first.")
        val sessionId = state.selectedSessionId
            ?: return failLocal("Select an academic session first.")
        if (bytes.isEmpty()) return failLocal("The selected assignment file is empty.")
        if (bytes.size > 5 * 1024 * 1024) {
            return failLocal("The assignment file must not exceed 5 MB.")
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null) }
            when (
                val result = repository.importStudentAssignments(
                    curriculumId,
                    sessionId,
                    filename,
                    mimeType.ifBlank { "application/octet-stream" },
                    bytes,
                )
            ) {
                is AppResult.Success -> {
                    _uiState.update { it.copy(isMutating = false, message = result.value) }
                    load(curriculumId, sessionId)
                    loadStudents(
                        conventionalClassArmId = _uiState.value.studentConventionalClassArmId,
                        assignmentStatus = _uiState.value.studentAssignmentStatus,
                        learnerStatus = _uiState.value.studentLearnerStatus,
                        gender = _uiState.value.studentGender,
                        search = _uiState.value.studentSearch,
                        page = 1,
                    )
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isMutating = false, errorMessage = result.error.userMessage)
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
                        learnerStatus = _uiState.value.studentLearnerStatus,
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
                        learnerStatus = _uiState.value.studentLearnerStatus,
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

    fun previewPromotion(sourceClassIds: List<Long>) {
        val state = _uiState.value
        val curriculumId = state.selectedCurriculumId ?: return failLocal("Select a parallel curriculum first.")
        val sourceId = state.sourceSessionId ?: return failLocal("Select the source academic session.")
        val targetId = state.targetSessionId ?: return failLocal("Select the target academic session.")
        if (sourceId == targetId) return failLocal("Source and target sessions must be different.")
        if (sourceClassIds.isEmpty()) return failLocal("Select at least one class level for promotion.")

        val selected = sourceClassIds.distinct()

        viewModelScope.launch {
            _uiState.update {
                it.copy(
                    isMutating = true,
                    errorMessage = null,
                    promotionSourceClassIds = selected,
                )
            }
            when (
                val result = repository.previewPromotion(
                    curriculumId,
                    sourceId,
                    targetId,
                    selected,
                )
            ) {
                is AppResult.Success -> _uiState.update {
                    it.copy(
                        promotionPreview = result.value,
                        promotionSourceClassIds = result.value.sourceClassIds,
                        isMutating = false,
                    )
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
        val sourceClassIds = preview.sourceClassIds.ifEmpty { state.promotionSourceClassIds }
        if (sourceClassIds.isEmpty()) return failLocal("Select at least one class level for promotion.")

        mutate(
            action = {
                repository.executePromotion(
                    curriculumId,
                    sourceId,
                    targetId,
                    sourceClassIds,
                )
            },
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

    fun saveArmTeachingMode(
        armId: Long,
        mode: String,
        classTeacherId: Long?,
    ) {
        if (mode !in setOf("class_teacher", "subject_based")) {
            return failLocal("Choose a valid teaching assignment mode.")
        }
        if (mode == "class_teacher" && classTeacherId == null) {
            return failLocal("Select the teacher who will take all subjects in this class arm.")
        }

        mutate {
            repository.saveArmTeachingMode(
                armId = armId,
                mode = mode,
                classTeacherId = if (mode == "class_teacher") classTeacherId else null,
            )
        }
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
        sourceClassIds: List<Long>,
        destinationMode: String,
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
        if (sourceClassIds.isEmpty()) {
            return failLocal("Select at least one source class level.")
        }
        if (minimumAverage == null || maxFailedSubjects == null) {
            return failLocal("Enter the promotion average and failed-subject limit.")
        }
        if (destinationMode == "explicit" && sourceClassIds.size != 1) {
            return failLocal("A specific destination can only be used with one source class.")
        }
        if (destinationMode == "explicit" && destinationClassId == null) {
            return failLocal("Select the specific destination class.")
        }

        mutate {
            repository.savePromotionRule(
                curriculumId,
                sourceClassIds.distinct(),
                destinationMode,
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

    fun consumeDocument() {
        _uiState.update { it.copy(downloadedDocument = null) }
    }

    fun consumeError() {
        _uiState.update { it.copy(errorMessage = null) }
    }

    private fun download(
        action: suspend () -> AppResult<DownloadedDocument>,
    ) {
        if (_uiState.value.isMutating) return
        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null) }
            when (val result = action()) {
                is AppResult.Success -> _uiState.update {
                    it.copy(
                        isMutating = false,
                        downloadedDocument = result.value,
                        message = "Document downloaded.",
                    )
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isMutating = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    private fun mutateOperations(
        action: suspend () -> AppResult<String>,
    ) {
        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null) }
            when (val result = action()) {
                is AppResult.Success -> {
                    val selected = _uiState.value.operationsWorkspace?.selected
                    _uiState.update { it.copy(isMutating = false, message = result.value) }
                    loadOperations(
                        classId = selected?.classId,
                        armId = selected?.armId,
                        termId = selected?.termId,
                        date = selected?.date,
                    )
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isMutating = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    private fun mutateResult(
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
                            studentResultDetail = null,
                        )
                    }
                    loadResults(
                        _uiState.value.resultWorkspace?.selectedClassId,
                        _uiState.value.resultWorkspace?.selectedTermId,
                    )
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isMutating = false, errorMessage = result.error.userMessage)
                }
            }
        }
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
