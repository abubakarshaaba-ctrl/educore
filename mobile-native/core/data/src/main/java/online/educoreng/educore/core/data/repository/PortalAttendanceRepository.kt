package online.educoreng.educore.core.data.repository

import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.PortalAttendanceWorkspace

interface PortalAttendanceRepository {
    suspend fun load(
        childId: Long? = null,
        termId: Long? = null,
    ): AppResult<PortalAttendanceWorkspace>
}
