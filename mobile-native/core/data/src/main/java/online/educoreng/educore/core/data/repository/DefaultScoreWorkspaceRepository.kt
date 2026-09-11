package online.educoreng.educore.core.data.repository

import com.squareup.moshi.Moshi
import java.util.UUID
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.withContext
import online.educoreng.educore.core.common.AppError
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.data.local.CachedScoreContractEntity
import online.educoreng.educore.core.data.local.EduCoreDatabase
import online.educoreng.educore.core.data.local.ScoreDraftEntity
import online.educoreng.educore.core.data.local.SyncOperationEntity
import online.educoreng.educore.core.data.preferences.TenantContextStore
import online.educoreng.educore.core.model.PublishedResults
import online.educoreng.educore.core.model.ScoreAssignments
import online.educoreng.educore.core.model.ScoreSheet
import online.educoreng.educore.core.model.SyncState
import online.educoreng.educore.core.network.EduCoreApi
import online.educoreng.educore.core.network.dto.PublishedResultsResponseDto
import online.educoreng.educore.core.network.dto.SaveScoresRequestDto
import online.educoreng.educore.core.network.dto.ScoreAssignmentsResponseDto
import online.educoreng.educore.core.network.dto.ScoreSheetResponseDto
import online.educoreng.educore.core.network.dto.toDomain
import online.educoreng.educore.core.network.safeApiCall

class DefaultScoreWorkspaceRepository(
    private val api: EduCoreApi,
    private val moshi: Moshi,
    private val database: EduCoreDatabase,
    private val tenantContextStore: TenantContextStore,
    private val nowEpochMs: () -> Long = System::currentTimeMillis,
) : ScoreWorkspaceRepository {
    private val assignmentsAdapter by lazy { moshi.adapter(ScoreAssignmentsResponseDto::class.java) }
    private val sheetAdapter by lazy { moshi.adapter(ScoreSheetResponseDto::class.java) }
    private val resultsAdapter by lazy { moshi.adapter(PublishedResultsResponseDto::class.java) }
    private val saveRequestAdapter by lazy { moshi.adapter(SaveScoresRequestDto::class.java) }

    override suspend fun loadAssignments(): AppResult<ScoreAssignments> = withContext(Dispatchers.IO) {
        val scope = scope() ?: return@withContext AppResult.Failure(AppError.Unauthenticated())
        when (val result = safeApiCall(moshi) { api.scoreAssignments() }) {
            is AppResult.Success -> {
                cache(scope, ASSIGNMENTS_KEY, assignmentsAdapter.toJson(result.value))
                AppResult.Success(result.value.toDomain())
            }
            is AppResult.Failure -> cached(scope, ASSIGNMENTS_KEY, assignmentsAdapter)?.let {
                AppResult.Success(it.toDomain(fromCache = true))
            } ?: result
        }
    }

    override suspend fun loadSheet(classId: Long, subjectId: Long, termId: Long?): AppResult<ScoreSheet> =
        withContext(Dispatchers.IO) {
            val scope = scope() ?: return@withContext AppResult.Failure(AppError.Unauthenticated())
            val key = sheetKey(classId, subjectId, termId)
            val dto = when (val result = safeApiCall(moshi) { api.scoreSheet(classId, subjectId, termId) }) {
                is AppResult.Success -> {
                    cache(scope, key, sheetAdapter.toJson(result.value))
                    result.value
                }
                is AppResult.Failure -> cached(scope, key, sheetAdapter) ?: return@withContext result
            }
            val drafts = database.scoreWorkspaceDao().drafts(scope.tenantKey, scope.userId, classId, subjectId, dto.term.id)
            val values = drafts.associate { (it.studentId to it.assessmentId) to it.value }
            val sheet = dto.toDomain(values, drafts.firstOrNull()?.serverVersion?.let { it != dto.version } == true)
            val operation = database.syncOperationDao().get(scope.tenantKey, scope.userId, scoreKey(classId, subjectId, dto.term.id))
            val syncState = when (operation?.state) {
                "conflict" -> SyncState.CONFLICT
                "failed" -> SyncState.FAILED
                "syncing" -> SyncState.SYNCING
                null -> SyncState.NONE
                else -> SyncState.QUEUED
            }
            AppResult.Success(sheet.copy(syncState = syncState, syncMessage = operation?.lastError))
        }

    override suspend fun saveDraft(
        sheet: ScoreSheet,
        studentId: Long,
        assessmentId: Long,
        value: Double?,
    ): AppResult<ScoreSheet> = withContext(Dispatchers.IO) {
        val scope = scope() ?: return@withContext AppResult.Failure(AppError.Unauthenticated())
        if (sheet.locked || sheet.students.firstOrNull { it.id == studentId }?.scores?.get(assessmentId)?.locked == true) {
            return@withContext AppResult.Failure(AppError.Forbidden("This score is locked and cannot be edited."))
        }
        val assessment = sheet.assessments.firstOrNull { it.id == assessmentId }
            ?: return@withContext AppResult.Failure(AppError.Validation("This assessment is unavailable."))
        val maximum = if (assessment.isSplit) assessment.theoryMaximum ?: 0.0 else assessment.maximum
        if (value != null && (value < 0 || value > maximum)) {
            return@withContext AppResult.Failure(AppError.Validation("Enter a score between 0 and $maximum."))
        }
        database.scoreWorkspaceDao().saveDraft(
            ScoreDraftEntity(
                scope.tenantKey,
                scope.userId,
                sheet.classId,
                sheet.subjectId,
                sheet.termId,
                studentId,
                assessmentId,
                value,
                sheet.version,
                nowEpochMs(),
            ),
        )
        AppResult.Success(sheet.withValue(studentId, assessmentId, value).copy(hasLocalDraft = true))
    }

    override suspend fun discardDraft(sheet: ScoreSheet): AppResult<Unit> = withContext(Dispatchers.IO) {
        val scope = scope() ?: return@withContext AppResult.Failure(AppError.Unauthenticated())
        database.scoreWorkspaceDao().deleteDrafts(scope.tenantKey, scope.userId, sheet.classId, sheet.subjectId, sheet.termId)
        database.syncOperationDao().delete(scope.tenantKey, scope.userId, scoreKey(sheet.classId, sheet.subjectId, sheet.termId))
        AppResult.Success(Unit)
    }

    override suspend fun submit(sheet: ScoreSheet): AppResult<ScoreSheet> = withContext(Dispatchers.IO) {
        if (sheet.isDraftStale) {
            return@withContext AppResult.Failure(AppError.Conflict("This score draft is stale. Reload before saving."))
        }
        if (sheet.locked) {
            return@withContext AppResult.Failure(AppError.Forbidden(sheet.lockReason ?: "These scores are locked."))
        }
        val scope = scope() ?: return@withContext AppResult.Failure(AppError.Unauthenticated())
        val drafts = database.scoreWorkspaceDao().drafts(scope.tenantKey, scope.userId, sheet.classId, sheet.subjectId, sheet.termId)
        if (drafts.isEmpty()) {
            return@withContext AppResult.Failure(AppError.Validation("No score changes are waiting to be saved."))
        }
        val payload = drafts.groupBy(ScoreDraftEntity::studentId)
            .mapKeys { it.key.toString() }
            .mapValues { (_, rows) -> rows.associate { it.assessmentId.toString() to it.value } }
        val key = scoreKey(sheet.classId, sheet.subjectId, sheet.termId)
        val existing = database.syncOperationDao().get(scope.tenantKey, scope.userId, key)
        if (existing?.state == "conflict") {
            return@withContext AppResult.Failure(AppError.Conflict(existing.lastError ?: "Reload this score sheet before retrying."))
        }
        val fresh = SaveScoresRequestDto(
            sheet.classId,
            sheet.subjectId,
            sheet.termId,
            sheet.version,
            UUID.randomUUID().toString(),
            payload,
        )
        val operation = existing ?: SyncOperationEntity(
            scope.tenantKey,
            scope.userId,
            key,
            SCORE_KIND,
            fresh.requestId,
            saveRequestAdapter.toJson(fresh),
            "pending",
            0,
            null,
            nowEpochMs(),
            nowEpochMs(),
        ).also { database.syncOperationDao().upsert(it) }
        val request = saveRequestAdapter.fromJson(operation.payloadJson)
            ?: return@withContext AppResult.Failure(AppError.Unexpected("The saved score request could not be read."))
        when (val result = safeApiCall(moshi) { api.saveScores(request) }) {
            is AppResult.Success -> {
                database.scoreWorkspaceDao().deleteDrafts(scope.tenantKey, scope.userId, sheet.classId, sheet.subjectId, sheet.termId)
                database.syncOperationDao().delete(scope.tenantKey, scope.userId, key)
                loadSheet(sheet.classId, sheet.subjectId, sheet.termId)
            }
            is AppResult.Failure -> when (result.error) {
                is AppError.NetworkUnavailable,
                is AppError.Timeout,
                is AppError.Server,
                is AppError.RateLimited,
                -> {
                    database.syncOperationDao().upsert(
                        operation.copy(
                            state = "pending",
                            attemptCount = operation.attemptCount + 1,
                            lastError = result.error.userMessage,
                            updatedAtEpochMs = nowEpochMs(),
                        ),
                    )
                    AppResult.Success(
                        sheet.copy(
                            syncState = SyncState.QUEUED,
                            syncMessage = "Queued securely. EduCore will retry when the connection is stable.",
                        ),
                    )
                }
                is AppError.Conflict -> {
                    database.syncOperationDao().upsert(
                        operation.copy(
                            state = "conflict",
                            lastError = result.error.userMessage,
                            updatedAtEpochMs = nowEpochMs(),
                        ),
                    )
                    result
                }
                else -> {
                    database.syncOperationDao().upsert(
                        operation.copy(
                            state = "failed",
                            lastError = result.error.userMessage,
                            updatedAtEpochMs = nowEpochMs(),
                        ),
                    )
                    result
                }
            }
        }
    }

    override suspend fun syncPendingScores(): Boolean = withContext(Dispatchers.IO) {
        val scope = scope() ?: return@withContext false
        var retry = false
        database.syncOperationDao().actionable(scope.tenantKey, scope.userId, SCORE_KIND).forEach { operation ->
            val request = saveRequestAdapter.fromJson(operation.payloadJson) ?: return@forEach
            when (val result = safeApiCall(moshi) { api.saveScores(request) }) {
                is AppResult.Success -> {
                    database.scoreWorkspaceDao().deleteDrafts(
                        scope.tenantKey,
                        scope.userId,
                        request.classId,
                        request.subjectId,
                        request.termId,
                    )
                    database.syncOperationDao().delete(scope.tenantKey, scope.userId, operation.operationKey)
                }
                is AppResult.Failure -> {
                    val transient = result.error is AppError.NetworkUnavailable ||
                        result.error is AppError.Timeout ||
                        result.error is AppError.Server ||
                        result.error is AppError.RateLimited
                    val state = when {
                        result.error is AppError.Conflict -> "conflict"
                        transient -> "pending"
                        else -> "failed"
                    }
                    database.syncOperationDao().upsert(
                        operation.copy(
                            state = state,
                            attemptCount = operation.attemptCount + 1,
                            lastError = result.error.userMessage,
                            updatedAtEpochMs = nowEpochMs(),
                        ),
                    )
                    retry = retry || transient
                }
            }
        }
        retry
    }

    override suspend fun loadPublishedResults(childId: Long?): AppResult<PublishedResults> = withContext(Dispatchers.IO) {
        val scope = scope() ?: return@withContext AppResult.Failure(AppError.Unauthenticated())
        val key = "results:${childId ?: "self"}"
        val response = if (scope.roleKey == "parent") {
            safeApiCall(moshi) { api.parentResults(childId) }
        } else {
            safeApiCall(moshi) { api.studentResults() }
        }
        when (val result = response) {
            is AppResult.Success -> {
                cache(scope, key, resultsAdapter.toJson(result.value))
                AppResult.Success(result.value.toDomain())
            }
            is AppResult.Failure -> cached(scope, key, resultsAdapter)?.let {
                AppResult.Success(it.toDomain())
            } ?: result
        }
    }

    private suspend fun scope(): Scope? {
        val tenantKey = tenantContextStore.activeTenantKey.first() ?: return null
        val session = database.sessionDao().get(tenantKey) ?: return null
        return Scope(tenantKey, session.userId, session.roleKey)
    }

    private suspend fun cache(scope: Scope, key: String, json: String) {
        database.scoreWorkspaceDao().replaceCache(
            CachedScoreContractEntity(scope.tenantKey, scope.userId, key, json, nowEpochMs()),
        )
    }

    private suspend fun <T> cached(
        scope: Scope,
        key: String,
        adapter: com.squareup.moshi.JsonAdapter<T>,
    ): T? = database.scoreWorkspaceDao()
        .cache(scope.tenantKey, scope.userId, key)
        ?.payloadJson
        ?.let { runCatching { adapter.fromJson(it) }.getOrNull() }

    private fun sheetKey(classId: Long, subjectId: Long, termId: Long?) =
        "sheet:$classId:$subjectId:${termId ?: "current"}"

    private fun scoreKey(classId: Long, subjectId: Long, termId: Long) =
        "scores:$classId:$subjectId:$termId"

    private fun ScoreSheet.withValue(studentId: Long, assessmentId: Long, value: Double?) = copy(
        students = students.map { student ->
            if (student.id != studentId) {
                student
            } else {
                student.copy(
                    scores = student.scores +
                        (assessmentId to requireNotNull(student.scores[assessmentId]).copy(value = value)),
                )
            }
        },
    )

    private data class Scope(
        val tenantKey: String,
        val userId: Long,
        val roleKey: String,
    )

    private companion object {
        const val ASSIGNMENTS_KEY = "assignments"
        const val SCORE_KIND = "scores"
    }
}
