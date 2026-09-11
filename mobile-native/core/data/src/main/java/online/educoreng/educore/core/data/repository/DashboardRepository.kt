package online.educoreng.educore.core.data.repository

import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.DashboardSnapshot

interface DashboardRepository {
    suspend fun load(): AppResult<DashboardSnapshot>
    suspend fun loadStaffPhoto(): AppResult<ByteArray?>
}
