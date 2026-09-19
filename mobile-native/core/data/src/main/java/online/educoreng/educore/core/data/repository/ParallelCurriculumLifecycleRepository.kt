package online.educoreng.educore.core.data.repository

import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.ParallelLifecycleWorkspace
import online.educoreng.educore.core.model.ParallelPromotionPreview
import online.educoreng.educore.core.model.ParallelLifecycleStudentPage
import online.educoreng.educore.core.model.ParallelResultWorkspace
import online.educoreng.educore.core.model.ParallelStudentResultDetail

interface ParallelCurriculumLifecycleRepository {
    suspend fun load(
        curriculumId: Long? = null,
        sessionId: Long? = null,
    ): AppResult<ParallelLifecycleWorkspace>

    suspend fun loadResults(
        classId: Long? = null,
        termId: Long? = null,
    ): AppResult<ParallelResultWorkspace>

    suspend fun loadStudentResult(
        classId: Long,
        studentId: Long,
        termId: Long,
    ): AppResult<ParallelStudentResultDetail>

    suspend fun publishResult(
        classId: Long,
        termId: Long,
    ): AppResult<String>

    suspend fun unpublishResult(
        classId: Long,
        termId: Long,
    ): AppResult<String>

    suspend fun createProgramme(
        name: String,
        code: String?,
        defaultAssessmentTemplateId: Long,
    ): AppResult<String>

    suspend fun updateProgramme(
        curriculumId: Long,
        name: String,
        code: String?,
        defaultAssessmentTemplateId: Long,
    ): AppResult<String>

    suspend fun createClass(
        curriculumId: Long,
        name: String,
        code: String?,
        assessmentTemplateId: Long?,
    ): AppResult<String>

    suspend fun updateClass(
        classId: Long,
        name: String,
        code: String?,
        assessmentTemplateId: Long?,
    ): AppResult<String>

    suspend fun createSubject(
        curriculumId: Long,
        name: String,
        code: String?,
    ): AppResult<String>

    suspend fun updateSubject(
        subjectId: Long,
        name: String,
        code: String?,
    ): AppResult<String>

    suspend fun saveClassSubject(
        classId: Long,
        subjectId: Long,
        teacherId: Long?,
    ): AppResult<String>

    suspend fun removeClassSubject(assignmentId: Long): AppResult<String>

    suspend fun saveProgrammeGrade(
        curriculumId: Long,
        gradeLetter: String,
        minScore: Double,
        maxScore: Double,
        remark: String?,
        isPassGrade: Boolean,
    ): AppResult<String>

    suspend fun deleteProgrammeGrade(gradeId: Long): AppResult<String>

    suspend fun loadStudents(
        curriculumId: Long,
        sessionId: Long,
        conventionalClassArmId: Long? = null,
        assignmentStatus: String = "all",
        gender: String? = null,
        search: String? = null,
        page: Int = 1,
    ): AppResult<ParallelLifecycleStudentPage>

    suspend fun assignStudents(
        classId: Long,
        armId: Long,
        sessionId: Long,
        studentIds: List<Long>,
    ): AppResult<String>

    suspend fun removeStudent(enrolmentId: Long): AppResult<String>

    suspend fun previewPromotion(
        curriculumId: Long,
        sourceSessionId: Long,
        targetSessionId: Long,
        sourceClassIds: List<Long>,
    ): AppResult<ParallelPromotionPreview>

    suspend fun executePromotion(
        curriculumId: Long,
        sourceSessionId: Long,
        targetSessionId: Long,
        sourceClassIds: List<Long>,
    ): AppResult<String>

    suspend fun transfer(
        enrolmentId: Long,
        destinationClassId: Long,
        destinationArmId: Long,
        reason: String,
        effectiveDate: String? = null,
    ): AppResult<String>

    suspend fun createArm(
        classId: Long,
        name: String,
        code: String? = null,
        capacity: Int? = null,
    ): AppResult<String>

    suspend fun updateArm(
        armId: Long,
        name: String,
        code: String? = null,
        capacity: Int? = null,
    ): AppResult<String>

    suspend fun archiveArm(armId: Long): AppResult<String>

    suspend fun saveArmTeacher(
        armId: Long,
        subjectId: Long,
        teacherId: Long?,
    ): AppResult<String>

    suspend fun saveGrade(
        curriculumId: Long,
        classIds: List<Long>,
        gradeLetter: String,
        minScore: Double,
        maxScore: Double,
        remark: String? = null,
        isPassGrade: Boolean = true,
        gradePoint: Double? = null,
    ): AppResult<String>

    suspend fun deleteGrade(gradeId: Long): AppResult<String>

    suspend fun savePromotionRule(
        curriculumId: Long,
        sourceClassIds: List<Long>,
        destinationMode: String,
        destinationClassId: Long?,
        minimumAverage: Double,
        maxFailedSubjects: Int,
        requireCompleteResult: Boolean,
        failureAction: String,
        armStrategy: String,
        isTerminal: Boolean,
    ): AppResult<String>
}
