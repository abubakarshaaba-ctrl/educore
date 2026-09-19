package online.educoreng.educore.core.data.repository

import com.squareup.moshi.Moshi
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.PortalAttendanceWorkspace
import online.educoreng.educore.core.network.EduCoreApi
import online.educoreng.educore.core.network.dto.toDomain
import online.educoreng.educore.core.network.safeApiCall

class DefaultPortalAttendanceRepository(
    private val api: EduCoreApi,
    private val moshi: Moshi,
) : PortalAttendanceRepository {
    override suspend fun load(
        childId: Long?,
        termId: Long?,
    ): AppResult<PortalAttendanceWorkspace> = withContext(Dispatchers.IO) {
        when (val result = safeApiCall(moshi) { api.portalAttendance(childId, termId) }) {
            is AppResult.Success -> AppResult.Success(result.value.toDomain())
            is AppResult.Failure -> result
        }
    }
}
