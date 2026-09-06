package online.educoreng.educore.core.data.repository

import com.squareup.moshi.Moshi
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.OperationsWorkspace
import online.educoreng.educore.core.network.EduCoreApi
import online.educoreng.educore.core.network.dto.toDomain
import online.educoreng.educore.core.network.safeApiCall

class DefaultOperationsRepository(
    private val api: EduCoreApi,
    private val moshi: Moshi,
) : OperationsRepository {
    override suspend fun load(module: String): AppResult<OperationsWorkspace> = withContext(Dispatchers.IO) {
        safeApiCall(moshi) { api.operations(module).toDomain() }
    }
}
