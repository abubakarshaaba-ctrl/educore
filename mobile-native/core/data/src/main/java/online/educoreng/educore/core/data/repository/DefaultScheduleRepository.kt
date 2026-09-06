package online.educoreng.educore.core.data.repository

import com.squareup.moshi.Moshi
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.withContext
import online.educoreng.educore.core.common.AppError
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.data.local.CachedScheduleEntity
import online.educoreng.educore.core.data.local.EduCoreDatabase
import online.educoreng.educore.core.data.preferences.TenantContextStore
import online.educoreng.educore.core.model.ScheduleWorkspace
import online.educoreng.educore.core.network.EduCoreApi
import online.educoreng.educore.core.network.dto.ScheduleResponseDto
import online.educoreng.educore.core.network.dto.toDomain
import online.educoreng.educore.core.network.safeApiCall

class DefaultScheduleRepository(
    private val api: EduCoreApi,
    private val moshi: Moshi,
    private val database: EduCoreDatabase,
    private val tenantContextStore: TenantContextStore,
    private val nowEpochMs: () -> Long = System::currentTimeMillis,
) : ScheduleRepository {
    private val adapter by lazy { moshi.adapter(ScheduleResponseDto::class.java) }

    override suspend fun load(classId: Long?, childId: Long?, from: String?, to: String?): AppResult<ScheduleWorkspace> =
        withContext(Dispatchers.IO) {
            val tenantKey = tenantContextStore.activeTenantKey.first()
                ?: return@withContext AppResult.Failure(AppError.Unauthenticated())
            val session = database.sessionDao().get(tenantKey)
                ?: return@withContext AppResult.Failure(AppError.Unauthenticated())
            val key = listOf("schedule", classId ?: "self", childId ?: "self", from.orEmpty(), to.orEmpty()).joinToString(":")

            when (val result = safeApiCall(moshi) { api.schedule(classId, childId, from, to) }) {
                is AppResult.Success -> {
                    database.scheduleDao().replace(
                        CachedScheduleEntity(tenantKey, session.userId, key, adapter.toJson(result.value), nowEpochMs()),
                    )
                    AppResult.Success(result.value.toDomain())
                }
                is AppResult.Failure -> {
                    val cached = database.scheduleDao().get(tenantKey, session.userId, key)
                        ?.let { runCatching { adapter.fromJson(it.payloadJson) }.getOrNull() }
                    cached?.let { AppResult.Success(it.toDomain(fromCache = true)) } ?: result
                }
            }
        }
}
