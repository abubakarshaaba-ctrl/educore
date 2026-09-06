package online.educoreng.educore.core.data.repository

import kotlinx.coroutines.flow.Flow
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.SessionSnapshot

interface SessionRepository {
    val session: Flow<SessionSnapshot?>

    fun hasStoredToken(): Boolean

    suspend fun login(
        loginId: String,
        password: String,
        deviceName: String,
    ): AppResult<SessionSnapshot>

    suspend fun refresh(): AppResult<SessionSnapshot>

    suspend fun requestPasswordReset(email: String): AppResult<String>

    suspend fun logout(): AppResult<Unit>
}
