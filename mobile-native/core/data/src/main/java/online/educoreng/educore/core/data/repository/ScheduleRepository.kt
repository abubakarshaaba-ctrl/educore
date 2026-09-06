package online.educoreng.educore.core.data.repository

import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.ScheduleWorkspace

interface ScheduleRepository {
    suspend fun load(
        classId: Long? = null,
        childId: Long? = null,
        from: String? = null,
        to: String? = null,
    ): AppResult<ScheduleWorkspace>
}
