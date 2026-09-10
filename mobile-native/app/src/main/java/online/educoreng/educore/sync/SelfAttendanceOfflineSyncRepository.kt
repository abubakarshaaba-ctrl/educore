package online.educoreng.educore.sync

import com.squareup.moshi.Moshi
import java.time.LocalDate
import java.time.OffsetDateTime
import java.util.UUID
import javax.inject.Inject
import javax.inject.Singleton
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.withContext
import online.educoreng.educore.core.common.AppError
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.data.local.EduCoreDatabase
import online.educoreng.educore.core.data.local.SyncOperationEntity
import online.educoreng.educore.core.data.preferences.TenantContextStore
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.SelfAttendanceOfflineApi
import online.educoreng.educore.core.network.dto.AdminAttendanceOfflineSyncRequestDto
import online.educoreng.educore.core.network.safeApiCall

@Singleton
class SelfAttendanceOfflineSyncRepository @Inject constructor(
    factory: ApiClientFactory,
    private val moshi: Moshi,
    private val database: EduCoreDatabase,
    private val tenantContextStore: TenantContextStore,
) {
    private val api = factory.create(SelfAttendanceOfflineApi::class.java)
    private val adapter = moshi.adapter(AdminAttendanceOfflineSyncRequestDto::class.java)

    suspend fun queue(
        action: String,
        qrToken: String? = null,
        latitude: Double? = null,
        longitude: Double? = null,
        accuracy: Double? = null,
    ): AppResult<String> = withContext(Dispatchers.IO) {
        val scope = scope() ?: return@withContext AppResult.Failure(AppError.Unauthenticated())
        val date = LocalDate.now().toString()

        val existing = database.syncOperationDao()
            .actionable(scope.tenantKey, scope.userId, KIND)
            .firstOrNull { operation ->
                val request = runCatching { adapter.fromJson(operation.payloadJson) }.getOrNull()
                request?.action == action && request.attendanceDate == date
            }
        if (existing != null) {
            return@withContext AppResult.Success("Attendance action is already queued and will sync automatically.")
        }

        val requestId = UUID.randomUUID().toString()
        val request = AdminAttendanceOfflineSyncRequestDto(
            clientUuid = requestId,
            staffId = scope.userId,
            action = action,
            attendanceDate = date,
            localTimestamp = OffsetDateTime.now().toString(),
            latitude = latitude,
            longitude = longitude,
            accuracy = accuracy,
            qrToken = qrToken?.takeIf(String::isNotBlank),
        )
        val now = System.currentTimeMillis()
        database.syncOperationDao().upsert(
            SyncOperationEntity(
                tenantKey = scope.tenantKey,
                userId = scope.userId,
                operationKey = "self-attendance:$action:$date:$requestId",
                kind = KIND,
                requestId = requestId,
                payloadJson = adapter.toJson(request),
                state = "pending",
                attemptCount = 0,
                lastError = null,
                createdAtEpochMs = now,
                updatedAtEpochMs = now,
            )
        )
        AppResult.Success("Attendance queued and will sync automatically when connectivity returns.")
    }

    suspend fun syncPending(): Boolean = withContext(Dispatchers.IO) {
        val scope = scope() ?: return@withContext false
        var shouldRetry = false

        database.syncOperationDao().actionable(scope.tenantKey, scope.userId, KIND).forEach { operation ->
            val request = runCatching { adapter.fromJson(operation.payloadJson) }.getOrNull()
            if (request == null) {
                database.syncOperationDao().upsert(
                    operation.copy(
                        state = "rejected",
                        lastError = "Queued attendance payload could not be read.",
                        updatedAtEpochMs = System.currentTimeMillis(),
                    )
                )
                return@forEach
            }

            database.syncOperationDao().upsert(
                operation.copy(state = "syncing", updatedAtEpochMs = System.currentTimeMillis())
            )

            when (val result = safeApiCall(moshi) { api.sync(request) }) {
                is AppResult.Success -> {
                    database.syncOperationDao().upsert(
                        operation.copy(
                            state = "synced",
                            attemptCount = operation.attemptCount + 1,
                            lastError = null,
                            updatedAtEpochMs = System.currentTimeMillis(),
                        )
                    )
                }
                is AppResult.Failure -> {
                    val transient = result.error is AppError.NetworkUnavailable ||
                        result.error is AppError.Timeout ||
                        result.error is AppError.Server ||
                        result.error is AppError.RateLimited
                    val nextState = if (transient) "pending" else "rejected"
                    database.syncOperationDao().upsert(
                        operation.copy(
                            state = nextState,
                            attemptCount = operation.attemptCount + 1,
                            lastError = result.error.userMessage,
                            updatedAtEpochMs = System.currentTimeMillis(),
                        )
                    )
                    shouldRetry = shouldRetry || transient
                }
            }
        }

        shouldRetry
    }

    suspend fun pendingStatus(): List<SelfAttendanceQueuedStatus> = withContext(Dispatchers.IO) {
        val scope = scope() ?: return@withContext emptyList()
        database.syncOperationDao().actionable(scope.tenantKey, scope.userId, KIND).mapNotNull { operation ->
            val request = runCatching { adapter.fromJson(operation.payloadJson) }.getOrNull() ?: return@mapNotNull null
            SelfAttendanceQueuedStatus(
                clientUuid = request.clientUuid,
                action = request.action,
                attendanceDate = request.attendanceDate,
                localTimestamp = request.localTimestamp,
                state = operation.state,
                rejectionReason = operation.lastError,
            )
        }
    }

    private suspend fun scope(): UserScope? {
        val tenantKey = tenantContextStore.activeTenantKey.first() ?: return null
        val userId = database.sessionDao().get(tenantKey)?.userId ?: return null
        return UserScope(tenantKey, userId)
    }

    private data class UserScope(val tenantKey: String, val userId: Long)

    private companion object {
        const val KIND = "staff_attendance_self"
    }
}

data class SelfAttendanceQueuedStatus(
    val clientUuid: String,
    val action: String,
    val attendanceDate: String,
    val localTimestamp: String,
    val state: String,
    val rejectionReason: String?,
)
