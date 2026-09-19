package online.educoreng.educore.core.data.repository

import com.squareup.moshi.Moshi
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.ParallelLifecycleWorkspace
import online.educoreng.educore.core.model.ParallelPromotionPreview
import online.educoreng.educore.core.network.EduCoreApi
import online.educoreng.educore.core.network.dto.ParallelArmMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelArmTeacherMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelGradeMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelPromotionRequestDto
import online.educoreng.educore.core.network.dto.ParallelPromotionRuleMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelTransferRequestDto
import online.educoreng.educore.core.network.dto.toDomain
import online.educoreng.educore.core.network.safeApiCall

class DefaultParallelCurriculumLifecycleRepository(
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

    override suspend fun previewPromotion(
        curriculumId: Long,
        sourceSessionId: Long,
        targetSessionId: Long,
    ): AppResult<ParallelPromotionPreview> = withContext(Dispatchers.IO) {
        when (
            val result = safeApiCall(moshi) {
                api.parallelPromotionPreview(curriculumId, sourceSessionId, targetSessionId)
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
    ): AppResult<String> = mutation {
        api.executeParallelPromotion(
            ParallelPromotionRequestDto(curriculumId, sourceSessionId, targetSessionId)
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
        sourceClassId: Long,
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
                sourceClassId = sourceClassId,
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
