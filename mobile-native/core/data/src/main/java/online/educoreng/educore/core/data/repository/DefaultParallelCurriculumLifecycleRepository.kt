package online.educoreng.educore.core.data.repository

import android.content.Context
import com.squareup.moshi.Moshi
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody
import okhttp3.ResponseBody
import online.educoreng.educore.core.common.AppError
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.DownloadedDocument
import online.educoreng.educore.core.model.ParallelLifecycleWorkspace
import online.educoreng.educore.core.model.ParallelOperationsWorkspace
import online.educoreng.educore.core.model.ParallelAttendanceDraft
import online.educoreng.educore.core.model.ParallelPromotionPreview
import online.educoreng.educore.core.model.ParallelLifecycleStudentPage
import online.educoreng.educore.core.model.ParallelResultWorkspace
import online.educoreng.educore.core.model.ParallelStudentResultDetail
import online.educoreng.educore.core.network.EduCoreApi
import online.educoreng.educore.core.network.dto.ParallelArmMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelPeriodMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelAttendanceMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelAttendanceRecordRequestDto
import online.educoreng.educore.core.network.dto.ParallelArmTeacherMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelArmTeachingModeMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelGradeMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelPromotionRequestDto
import online.educoreng.educore.core.network.dto.ParallelStudentAssignmentRequestDto
import online.educoreng.educore.core.network.dto.ParallelProgrammeMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelClassMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelSubjectMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelClassSubjectMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelProgrammeGradeMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelResultPublicationRequestDto
import online.educoreng.educore.core.network.dto.ParallelPromotionRuleMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelTransferRequestDto
import online.educoreng.educore.core.network.dto.toDomain
import online.educoreng.educore.core.network.safeApiCall

class DefaultParallelCurriculumLifecycleRepository(
    private val context: Context,
    private val api: EduCoreApi,
    private val moshi: Moshi,
) : ParallelCurriculumLifecycleRepository {

    override suspend fun load(
        curriculumId: Long?,
        sessionId: Long?,
    ): AppResult<ParallelLifecycleWorkspace> = withContext(Dispatchers.IO) {
        when (val result = safeApiCall(moshi) { api.parallelLifecycle(curriculumId, sessionId) }) {
            is AppResult.Success -> AppResult.Success(result.value.toDomain())
            is AppResult.Failure -> result
        }
    }

    override suspend fun loadResults(
        classId: Long?,
        termId: Long?,
    ): AppResult<ParallelResultWorkspace> = withContext(Dispatchers.IO) {
        when (val result = safeApiCall(moshi) { api.parallelResults(classId, termId) }) {
            is AppResult.Success -> AppResult.Success(result.value.toDomain())
            is AppResult.Failure -> result
        }
    }

    override suspend fun loadOperations(
        curriculumId: Long?,
        sessionId: Long?,
        termId: Long?,
        classId: Long?,
        armId: Long?,
        date: String?,
    ): AppResult<ParallelOperationsWorkspace> = withContext(Dispatchers.IO) {
        when (
            val result = safeApiCall(moshi) {
                api.parallelOperations(curriculumId, sessionId, termId, classId, armId, date)
            }
        ) {
            is AppResult.Success -> AppResult.Success(result.value.toDomain())
            is AppResult.Failure -> result
        }
    }

    override suspend fun createTimetablePeriod(
        classId: Long,
        armId: Long,
        subjectId: Long,
        sessionId: Long,
        dayOfWeek: String,
        startTime: String,
        endTime: String,
        venue: String?,
    ): AppResult<String> = mutation {
        api.createParallelTimetablePeriod(
            ParallelPeriodMutationRequestDto(
                classId = classId,
                armId = armId,
                subjectId = subjectId,
                sessionId = sessionId,
                dayOfWeek = dayOfWeek,
                startTime = startTime,
                endTime = endTime,
                venue = venue,
            )
        ).message
    }

    override suspend fun deleteTimetablePeriod(periodId: Long): AppResult<String> = mutation {
        api.deleteParallelTimetablePeriod(periodId).message
    }

    override suspend fun saveParallelAttendance(
        armId: Long,
        termId: Long,
        date: String,
        version: String?,
        records: List<ParallelAttendanceDraft>,
    ): AppResult<String> = mutation {
        api.saveParallelAttendance(
            ParallelAttendanceMutationRequestDto(
                armId = armId,
                termId = termId,
                attendanceDate = date,
                version = version,
                records = records.map {
                    ParallelAttendanceRecordRequestDto(
                        enrolmentId = it.enrolmentId,
                        status = it.status,
                        remark = it.remark,
                    )
                },
            )
        ).message
    }

    override suspend fun downloadAttendanceExport(
        armId: Long,
        termId: Long,
        format: String,
        from: String?,
        to: String?,
    ): AppResult<DownloadedDocument> {
        val safeFormat = format.lowercase().takeIf { it == "pdf" || it == "csv" }
            ?: return AppResult.Failure(AppError.Unexpected("Unsupported attendance export format."))
        val mimeType = if (safeFormat == "pdf") "application/pdf" else "text/csv"

        return download(
            filename = "parallel-attendance-$armId-$termId.$safeFormat",
            mimeType = mimeType,
        ) {
            api.downloadParallelAttendanceExport(armId, termId, safeFormat, from, to)
        }
    }

    override suspend fun loadStudentResult(
        classId: Long,
        studentId: Long,
        termId: Long,
    ): AppResult<ParallelStudentResultDetail> = withContext(Dispatchers.IO) {
        when (
            val result = safeApiCall(moshi) {
                api.parallelStudentResult(classId, studentId, termId)
            }
        ) {
            is AppResult.Success -> AppResult.Success(result.value.toDomain())
            is AppResult.Failure -> result
        }
    }

    override suspend fun publishResult(
        classId: Long,
        termId: Long,
    ): AppResult<String> = mutation {
        api.publishParallelResult(
            ParallelResultPublicationRequestDto(classId, termId)
        ).message
    }

    override suspend fun unpublishResult(
        classId: Long,
        termId: Long,
    ): AppResult<String> = mutation {
        api.unpublishParallelResult(
            ParallelResultPublicationRequestDto(classId, termId)
        ).message
    }

    override suspend fun downloadResultExport(
        classId: Long,
        termId: Long,
        format: String,
    ): AppResult<DownloadedDocument> {
        val safeFormat = format.lowercase().takeIf { it == "pdf" || it == "csv" }
            ?: return AppResult.Failure(AppError.Unexpected("Unsupported result export format."))
        val mimeType = if (safeFormat == "pdf") "application/pdf" else "text/csv"

        return download(
            filename = "parallel-result-$classId-$termId.$safeFormat",
            mimeType = mimeType,
        ) {
            api.downloadParallelResultExport(classId, termId, safeFormat)
        }
    }

    override suspend fun downloadStudentResultPdf(
        classId: Long,
        studentId: Long,
        termId: Long,
    ): AppResult<DownloadedDocument> = download(
        filename = "parallel-student-result-$studentId-$termId.pdf",
        mimeType = "application/pdf",
    ) {
        api.downloadParallelStudentResultPdf(classId, studentId, termId)
    }

    override suspend fun createProgramme(
        name: String,
        code: String?,
        defaultAssessmentTemplateId: Long,
    ): AppResult<String> = mutation {
        api.createParallelProgramme(
            ParallelProgrammeMutationRequestDto(
                name = name,
                code = code,
                defaultAssessmentTemplateId = defaultAssessmentTemplateId,
            )
        ).message
    }

    override suspend fun updateProgramme(
        curriculumId: Long,
        name: String,
        code: String?,
        defaultAssessmentTemplateId: Long,
    ): AppResult<String> = mutation {
        api.updateParallelProgramme(
            curriculumId,
            ParallelProgrammeMutationRequestDto(
                name = name,
                code = code,
                defaultAssessmentTemplateId = defaultAssessmentTemplateId,
            )
        ).message
    }

    override suspend fun createClass(
        curriculumId: Long,
        name: String,
        code: String?,
        assessmentTemplateId: Long?,
    ): AppResult<String> = mutation {
        api.createParallelClass(
            ParallelClassMutationRequestDto(
                parallelCurriculumId = curriculumId,
                name = name,
                code = code,
                assessmentTemplateId = assessmentTemplateId,
            )
        ).message
    }

    override suspend fun updateClass(
        classId: Long,
        name: String,
        code: String?,
        assessmentTemplateId: Long?,
    ): AppResult<String> = mutation {
        api.updateParallelClass(
            classId,
            ParallelClassMutationRequestDto(
                name = name,
                code = code,
                assessmentTemplateId = assessmentTemplateId,
            )
        ).message
    }

    override suspend fun createSubject(
        curriculumId: Long,
        name: String,
        code: String?,
    ): AppResult<String> = mutation {
        api.createParallelSubject(
            ParallelSubjectMutationRequestDto(
                parallelCurriculumId = curriculumId,
                name = name,
                code = code,
            )
        ).message
    }

    override suspend fun updateSubject(
        subjectId: Long,
        name: String,
        code: String?,
    ): AppResult<String> = mutation {
        api.updateParallelSubject(
            subjectId,
            ParallelSubjectMutationRequestDto(
                name = name,
                code = code,
            )
        ).message
    }

    override suspend fun saveClassSubject(
        classId: Long,
        subjectId: Long,
        teacherId: Long?,
    ): AppResult<String> = mutation {
        api.saveParallelClassSubject(
            ParallelClassSubjectMutationRequestDto(
                parallelCurriculumClassId = classId,
                parallelCurriculumSubjectId = subjectId,
                teacherId = teacherId,
            )
        ).message
    }

    override suspend fun removeClassSubject(
        assignmentId: Long,
    ): AppResult<String> = mutation {
        api.removeParallelClassSubject(assignmentId).message
    }

    override suspend fun saveProgrammeGrade(
        curriculumId: Long,
        gradeLetter: String,
        minScore: Double,
        maxScore: Double,
        remark: String?,
        isPassGrade: Boolean,
    ): AppResult<String> = mutation {
        api.saveParallelProgrammeGrade(
            ParallelProgrammeGradeMutationRequestDto(
                parallelCurriculumId = curriculumId,
                gradeLetter = gradeLetter,
                minScore = minScore,
                maxScore = maxScore,
                remark = remark,
                isPassGrade = isPassGrade,
            )
        ).message
    }

    override suspend fun deleteProgrammeGrade(
        gradeId: Long,
    ): AppResult<String> = mutation {
        api.deleteParallelProgrammeGrade(gradeId).message
    }

    override suspend fun loadStudents(
        curriculumId: Long,
        sessionId: Long,
        conventionalClassArmId: Long?,
        assignmentStatus: String,
        learnerStatus: String,
        gender: String?,
        search: String?,
        page: Int,
    ): AppResult<ParallelLifecycleStudentPage> = withContext(Dispatchers.IO) {
        when (
            val result = safeApiCall(moshi) {
                api.parallelLifecycleStudents(
                    curriculumId = curriculumId,
                    sessionId = sessionId,
                    conventionalClassArmId = conventionalClassArmId,
                    assignmentStatus = assignmentStatus,
                    learnerStatus = learnerStatus,
                    gender = gender,
                    search = search,
                    page = page,
                )
            }
        ) {
            is AppResult.Success -> AppResult.Success(result.value.toDomain())
            is AppResult.Failure -> result
        }
    }

    override suspend fun downloadStudentAssignmentTemplate(): AppResult<DownloadedDocument> = download(
        filename = "parallel_curriculum_student_assignment_template.csv",
        mimeType = "text/csv",
    ) {
        api.downloadParallelStudentAssignmentTemplate()
    }

    override suspend fun importStudentAssignments(
        curriculumId: Long,
        sessionId: Long,
        filename: String,
        mimeType: String,
        bytes: ByteArray,
    ): AppResult<String> = mutation {
        val mediaType = mimeType.toMediaTypeOrNull() ?: "application/octet-stream".toMediaType()
        val filePart = MultipartBody.Part.createFormData(
            "assignment_file",
            filename,
            bytes.toRequestBody(mediaType),
        )
        api.importParallelStudentAssignments(
            curriculumId.toString().toRequestBody("text/plain".toMediaType()),
            sessionId.toString().toRequestBody("text/plain".toMediaType()),
            filePart,
        ).message
    }

    override suspend fun assignStudents(
        classId: Long,
        armId: Long,
        sessionId: Long,
        studentIds: List<Long>,
    ): AppResult<String> = mutation {
        api.assignParallelStudents(
            ParallelStudentAssignmentRequestDto(
                parallelCurriculumClassId = classId,
                parallelCurriculumClassArmId = armId,
                sessionId = sessionId,
                studentIds = studentIds,
            )
        ).message
    }

    override suspend fun removeStudent(enrolmentId: Long): AppResult<String> = mutation {
        api.removeParallelStudent(enrolmentId).message
    }

    override suspend fun previewPromotion(
        curriculumId: Long,
        sourceSessionId: Long,
        targetSessionId: Long,
        sourceClassIds: List<Long>,
    ): AppResult<ParallelPromotionPreview> = withContext(Dispatchers.IO) {
        when (
            val result = safeApiCall(moshi) {
                api.parallelPromotionPreview(
                    curriculumId,
                    sourceSessionId,
                    targetSessionId,
                    sourceClassIds,
                )
            }
        ) {
            is AppResult.Success -> AppResult.Success(result.value.toDomain())
            is AppResult.Failure -> result
        }
    }

    override suspend fun executePromotion(
        curriculumId: Long,
        sourceSessionId: Long,
        targetSessionId: Long,
        sourceClassIds: List<Long>,
    ): AppResult<String> = mutation {
        api.executeParallelPromotion(
            ParallelPromotionRequestDto(
                curriculumId,
                sourceSessionId,
                targetSessionId,
                sourceClassIds,
            )
        ).message
    }

    override suspend fun transfer(
        enrolmentId: Long,
        destinationClassId: Long,
        destinationArmId: Long,
        reason: String,
        effectiveDate: String?,
    ): AppResult<String> = mutation {
        api.parallelTransfer(
            ParallelTransferRequestDto(
                enrolmentId,
                destinationClassId,
                destinationArmId,
                reason,
                effectiveDate,
            )
        ).message
    }

    override suspend fun createArm(
        classId: Long,
        name: String,
        code: String?,
        capacity: Int?,
    ): AppResult<String> = mutation {
        api.createParallelArm(
            ParallelArmMutationRequestDto(
                parallelCurriculumClassId = classId,
                name = name,
                code = code,
                capacity = capacity,
            )
        ).message
    }

    override suspend fun updateArm(
        armId: Long,
        name: String,
        code: String?,
        capacity: Int?,
    ): AppResult<String> = mutation {
        api.updateParallelArm(
            armId,
            ParallelArmMutationRequestDto(
                name = name,
                code = code,
                capacity = capacity,
            )
        ).message
    }

    override suspend fun archiveArm(armId: Long): AppResult<String> = mutation {
        api.archiveParallelArm(armId).message
    }

    override suspend fun saveArmTeacher(
        armId: Long,
        subjectId: Long,
        teacherId: Long?,
    ): AppResult<String> = mutation {
        api.saveParallelArmTeacher(
            ParallelArmTeacherMutationRequestDto(
                parallelCurriculumClassArmId = armId,
                parallelCurriculumSubjectId = subjectId,
                teacherId = teacherId,
            )
        ).message
    }

    override suspend fun saveArmTeachingMode(
        armId: Long,
        mode: String,
        classTeacherId: Long?,
    ): AppResult<String> = mutation {
        api.saveParallelArmTeachingMode(
            ParallelArmTeachingModeMutationRequestDto(
                parallelCurriculumClassArmId = armId,
                teachingAssignmentMode = mode,
                classTeacherId = classTeacherId,
            )
        ).message
    }

    override suspend fun saveGrade(
        curriculumId: Long,
        classIds: List<Long>,
        gradeLetter: String,
        minScore: Double,
        maxScore: Double,
        remark: String?,
        isPassGrade: Boolean,
        gradePoint: Double?,
    ): AppResult<String> = mutation {
        api.createParallelGrade(
            ParallelGradeMutationRequestDto(
                parallelCurriculumId = curriculumId,
                classIds = classIds,
                gradeLetter = gradeLetter,
                minScore = minScore,
                maxScore = maxScore,
                remark = remark,
                isPassGrade = isPassGrade,
                gradePoint = gradePoint,
            )
        ).message
    }

    override suspend fun deleteGrade(gradeId: Long): AppResult<String> = mutation {
        api.deleteParallelGrade(gradeId).message
    }

    override suspend fun savePromotionRule(
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
    ): AppResult<String> = mutation {
        api.saveParallelPromotionRule(
            ParallelPromotionRuleMutationRequestDto(
                parallelCurriculumId = curriculumId,
                sourceClassIds = sourceClassIds,
                destinationMode = destinationMode,
                destinationClassId = destinationClassId,
                minimumAverage = minimumAverage,
                maxFailedSubjects = maxFailedSubjects,
                requireCompleteResult = requireCompleteResult,
                failureAction = failureAction,
                armStrategy = armStrategy,
                isTerminal = isTerminal,
            )
        ).message
    }

    private suspend fun download(
        filename: String,
        mimeType: String,
        remote: suspend () -> ResponseBody,
    ): AppResult<DownloadedDocument> = withContext(Dispatchers.IO) {
        when (val result = safeApiCall(moshi, remote)) {
            is AppResult.Success -> runCatching {
                saveDownloadedDocument(context, result.value, filename, mimeType)
            }.fold(
                onSuccess = { AppResult.Success(it) },
                onFailure = {
                    AppResult.Failure(
                        AppError.Unexpected("The result export could not be saved.", it)
                    )
                },
            )
            is AppResult.Failure -> result
        }
    }

    private suspend fun mutation(block: suspend () -> String): AppResult<String> =
        withContext(Dispatchers.IO) {
            safeApiCall(moshi) { MessageEnvelope(block()) }.let { result ->
                when (result) {
                    is AppResult.Success -> AppResult.Success(result.value.message)
                    is AppResult.Failure -> result
                }
            }
        }

    private data class MessageEnvelope(val message: String)
}
