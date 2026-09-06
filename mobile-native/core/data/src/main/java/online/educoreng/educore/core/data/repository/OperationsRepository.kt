package online.educoreng.educore.core.data.repository

import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.OperationsWorkspace

interface OperationsRepository {
    suspend fun load(module: String): AppResult<OperationsWorkspace>
}
