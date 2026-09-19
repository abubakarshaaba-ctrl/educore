package online.educoreng.educore.core.data.repository

import com.squareup.moshi.Moshi
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.withContext
import online.educoreng.educore.core.common.AppError
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.data.local.CachedPortalAttendanceEntity
import online.educoreng.educore.core.data.local.EduCoreDatabase
import online.educoreng.educore.core.data.preferences.TenantContextStore
import online.educoreng.educore.core.model.PortalAttendanceWorkspace
import online.educoreng.educore.core.network.EduCoreApi
import online.educoreng.educore.core.network.dto.PortalAttendanceResponseDto
import online.educoreng.educore.core.network.dto.toDomain
import online.educoreng.educore.core.network.safeApiCall

class DefaultPortalAttendanceRepository(
    private val api: EduCoreApi,
    private val moshi: Moshi,
    private val database: EduCoreDatabase,
    private val tenantContextStore: TenantContextStore,
    private val nowEpochMs: () -> Long = System::currentTimeMillis,
) : PortalAttendanceRepository {
    private val adapter by lazy { moshi.adapter(PortalAttendanceResponseDto::class.java) }

    override suspend fun load(
        childId: Long?,
        termId: Long?,
    ): AppResult<PortalAttendanceWorkspace> = withContext(Dispatchers.IO) {
        val tenantKey = tenantContextStore.activeTenantKey.first()
            ?: return@withContext AppResult.Failure(AppError.Unauthenticated())
        val session = database.sessionDao().get(tenantKey)
            ?: return@withContext AppResult.Failure(AppError.Unauthenticated())
        val cacheKey = listOf(
            "portal-attendance",
            childId ?: "self",
            termId ?: "current",
        ).joinToString(":")

        when (val result = safeApiCall(moshi) { api.portalAttendance(childId, termId) }) {
            is AppResult.Success -> {
                database.portalAttendanceDao().replace(
                    CachedPortalAttendanceEntity(
                        tenantKey = tenantKey,
                        userId = session.userId,
                        cacheKey = cacheKey,
                        payloadJson = adapter.toJson(result.value),
                        cachedAtEpochMs = nowEpochMs(),
                    ),
                )
                AppResult.Success(result.value.toDomain())
            }
            is AppResult.Failure -> {
                val cached = database.portalAttendanceDao()
                    .get(tenantKey, session.userId, cacheKey)
                    ?.let { runCatching { adapter.fromJson(it.payloadJson) }.getOrNull() }

                cached?.let { AppResult.Success(it.toDomain(fromCache = true)) } ?: result
            }
        }
    }
}
