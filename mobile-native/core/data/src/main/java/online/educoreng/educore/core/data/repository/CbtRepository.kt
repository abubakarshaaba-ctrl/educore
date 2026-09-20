package online.educoreng.educore.core.data.repository

import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.CbtAttempt
import online.educoreng.educore.core.model.CbtIntegrityResult
import online.educoreng.educore.core.model.CbtPreflight

interface CbtRepository {
    suspend fun exams(): AppResult<List<CbtPreflight>>
    suspend fun preflight(examId: Long): AppResult<CbtPreflight>
    suspend fun begin(examId: Long, requestId: String): AppResult<CbtAttempt>
    suspend fun attempt(sessionId: Long): AppResult<CbtAttempt>
    suspend fun save(sessionId: Long, requestId: String, version: String, answers: Map<Long, String>, flagged: Set<Long>): AppResult<CbtAttempt>
    suspend fun integrity(sessionId: Long, eventId: String, eventType: String, answers: Map<Long, String>): AppResult<CbtIntegrityResult>
    suspend fun submit(sessionId: Long, requestId: String, answers: Map<Long, String>): AppResult<CbtAttempt>
    suspend fun questionImage(sessionId: Long, questionId: Long): AppResult<ByteArray>
}
