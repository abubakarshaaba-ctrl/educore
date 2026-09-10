package online.educoreng.educore.core.data.repository

import com.squareup.moshi.Moshi
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.withContext
import online.educoreng.educore.core.common.AppError
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.data.local.CachedDashboardEntity
import online.educoreng.educore.core.data.local.EduCoreDatabase
import online.educoreng.educore.core.data.preferences.TenantContextStore
import online.educoreng.educore.core.model.DashboardSnapshot
import online.educoreng.educore.core.network.EduCoreApi
import online.educoreng.educore.core.network.dto.DashboardResponseDto
import online.educoreng.educore.core.network.safeApiCall
import online.educoreng.educore.core.network.toDomain

class DefaultDashboardRepository(
    private val api: EduCoreApi,
    private val moshi: Moshi,
    private val database: EduCoreDatabase,
    private val tenantContextStore: TenantContextStore,
    private val nowEpochMs: () -> Long = System::currentTimeMillis,
) : DashboardRepository {
    private val adapter by lazy { moshi.adapter(DashboardResponseDto::class.java) }

    override suspend fun load(): AppResult<DashboardSnapshot> = withContext(Dispatchers.IO) {
        val tenantKey = tenantContextStore.activeTenantKey.first()
            ?: return@withContext AppResult.Failure(AppError.Unauthenticated())
        val userId = database.sessionDao().get(tenantKey)?.userId
            ?: return@withContext AppResult.Failure(AppError.Unauthenticated())

        when (val result = safeApiCall(moshi) { api.dashboard() }) {
            is AppResult.Success -> {
                val cachedAt = nowEpochMs()
                database.dashboardDao().replace(
                    CachedDashboardEntity(
                        tenantKey = tenantKey,
                        userId = userId,
                        scope = result.value.scope,
                        payloadJson = adapter.toJson(result.value),
                        sourceGeneratedAt = result.value.generatedAt,
                        cachedAtEpochMs = cachedAt,
                    ),
                )
                AppResult.Success(result.value.toDomain(cachedAtEpochMs = cachedAt))
            }
            is AppResult.Failure -> {
                val cached = database.dashboardDao().get(tenantKey, userId)
                val dto = cached?.let { runCatching { adapter.fromJson(it.payloadJson) }.getOrNull() }
                if (cached != null && dto != null) {
                    AppResult.Success(
                        dto.toDomain(
                            cachedAtEpochMs = cached.cachedAtEpochMs,
                            isFromCache = true,
                        ),
                    )
                } else {
                    result
                }
            }
        }
    }

    override suspend fun loadProfilePhoto(): AppResult<ByteArray?> = withContext(Dispatchers.IO) {
        // The photo is a non-critical dashboard enhancement. A missing or
        // temporarily unavailable photo must never block the authenticated app.
        when (val result = safeApiCall(moshi) { api.profilePhoto() }) {
            is AppResult.Success -> AppResult.Success(result.value.use { body -> body.bytes() })
            is AppResult.Failure -> AppResult.Success(null)
        }
    }
}
