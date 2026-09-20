package online.educoreng.educore.core.data.repository

import com.squareup.moshi.Moshi
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.CbtAttempt
import online.educoreng.educore.core.model.CbtIntegrityResult
import online.educoreng.educore.core.model.CbtPreflight
import online.educoreng.educore.core.network.EduCoreApi
import online.educoreng.educore.core.network.dto.CbtBeginRequestDto
import online.educoreng.educore.core.network.dto.CbtIntegrityRequestDto
import online.educoreng.educore.core.network.dto.CbtSaveRequestDto
import online.educoreng.educore.core.network.dto.CbtSubmitRequestDto
import online.educoreng.educore.core.network.dto.toDomain
import online.educoreng.educore.core.network.safeApiCall

class DefaultCbtRepository(
    private val api: EduCoreApi,
    private val moshi: Moshi,
) : CbtRepository {
    override suspend fun exams(): AppResult<List<CbtPreflight>> = network {
        api.cbtExams().exams.map { it.toDomain() }
    }

    override suspend fun preflight(examId: Long): AppResult<CbtPreflight> = network {
        api.cbtPreflight(examId).toDomain()
    }

    override suspend fun begin(examId: Long, requestId: String): AppResult<CbtAttempt> = network {
        api.beginCbt(examId, CbtBeginRequestDto(requestId)).toDomain()
    }

    override suspend fun attempt(sessionId: Long): AppResult<CbtAttempt> = network {
        api.cbtAttempt(sessionId).toDomain()
    }

    override suspend fun save(
        sessionId: Long,
        requestId: String,
        version: String,
        answers: Map<Long, String>,
        flagged: Set<Long>,
    ): AppResult<CbtAttempt> = network {
        api.saveCbt(
            sessionId,
            CbtSaveRequestDto(requestId, version, answers.stringKeys(), flagged.toList()),
        ).toDomain()
    }

    override suspend fun integrity(
        sessionId: Long,
        eventId: String,
        eventType: String,
        answers: Map<Long, String>,
    ): AppResult<CbtIntegrityResult> = network {
        api.recordCbtIntegrity(
            sessionId,
            CbtIntegrityRequestDto(eventId, eventType, answers = answers.stringKeys()),
        ).toDomain()
    }

    override suspend fun submit(
        sessionId: Long,
        requestId: String,
        answers: Map<Long, String>,
    ): AppResult<CbtAttempt> = network {
        api.submitCbt(sessionId, CbtSubmitRequestDto(requestId, answers.stringKeys())).toDomain()
    }

    override suspend fun questionImage(sessionId: Long, questionId: Long): AppResult<ByteArray> = network {
        api.cbtQuestionImage(sessionId, questionId).readByteArrayBounded(MAX_CBT_IMAGE_BYTES)
    }

    private suspend fun <T> network(block: suspend () -> T): AppResult<T> = withContext(Dispatchers.IO) {
        safeApiCall(moshi, block)
    }

    private fun Map<Long, String>.stringKeys() = entries.associate { (key, value) -> key.toString() to value }

    private companion object {
        const val MAX_CBT_IMAGE_BYTES = 8 * 1024 * 1024
    }
}
