package online.educoreng.educore.core.data.repository

import androidx.room.withTransaction
import com.squareup.moshi.Moshi
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flatMapLatest
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import online.educoreng.educore.core.common.AppError
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.data.local.EduCoreDatabase
import online.educoreng.educore.core.data.local.toCache
import online.educoreng.educore.core.data.local.toDomain
import online.educoreng.educore.core.data.preferences.TenantContextStore
import online.educoreng.educore.core.model.SessionSnapshot
import online.educoreng.educore.core.network.EduCoreApi
import online.educoreng.educore.core.network.dto.ForgotPasswordRequestDto
import online.educoreng.educore.core.network.dto.LoginRequestDto
import online.educoreng.educore.core.network.dto.PortalSessionRequestDto
import online.educoreng.educore.core.network.safeApiCall
import online.educoreng.educore.core.network.toDomain
import online.educoreng.educore.core.security.TokenVault

@OptIn(ExperimentalCoroutinesApi::class)
class DefaultSessionRepository(
    private val api: EduCoreApi,
    private val moshi: Moshi,
    private val tokenVault: TokenVault,
    private val database: EduCoreDatabase,
    private val tenantContextStore: TenantContextStore,
    private val nowEpochMs: () -> Long = System::currentTimeMillis,
) : SessionRepository {
    override val session: Flow<SessionSnapshot?> = tenantContextStore.activeTenantKey
        .flatMapLatest { tenantKey ->
            if (tenantKey == null) {
                flowOf(null)
            } else {
                database.sessionDao().observe(tenantKey).map { it?.toDomain() }
            }
        }

    override fun hasStoredToken(): Boolean = tokenVault.hasToken()

    override suspend fun login(
        loginId: String,
        password: String,
        deviceName: String,
    ): AppResult<SessionSnapshot> = withContext(Dispatchers.IO) {
        val normalizedLogin = loginId.trim()
        if (normalizedLogin.isBlank() || password.isBlank()) {
            return@withContext AppResult.Failure(
                AppError.Validation("Enter your login ID and password."),
            )
        }

        when (val result = safeApiCall(moshi) {
            api.login(LoginRequestDto(normalizedLogin, password, deviceName))
        }) {
            is AppResult.Failure -> result
            is AppResult.Success -> {
                tokenVault.save(result.value.token)
                when (val bootstrap = refresh()) {
                    is AppResult.Success -> bootstrap
                    is AppResult.Failure -> {
                        clearLocalSession()
                        bootstrap
                    }
                }
            }
        }
    }

    override suspend fun refresh(): AppResult<SessionSnapshot> = withContext(Dispatchers.IO) {
        if (!tokenVault.hasToken()) {
            return@withContext AppResult.Failure(AppError.Unauthenticated())
        }

        when (val result = safeApiCall(moshi) { api.bootstrap() }) {
            is AppResult.Failure -> result
            is AppResult.Success -> {
                val snapshot = result.value.toDomain()
                persist(snapshot)
                AppResult.Success(snapshot)
            }
        }
    }

    override suspend fun requestPasswordReset(email: String): AppResult<String> = withContext(Dispatchers.IO) {
        val normalizedEmail = email.trim().lowercase()
        if (normalizedEmail.isBlank()) {
            return@withContext AppResult.Failure(
                AppError.Validation(
                    userMessage = "Enter your email address.",
                    fieldErrors = mapOf("email" to listOf("Enter your email address.")),
                ),
            )
        }

        when (val result = safeApiCall(moshi) {
            api.forgotPassword(ForgotPasswordRequestDto(normalizedEmail))
        }) {
            is AppResult.Success -> AppResult.Success(result.value.message)
            is AppResult.Failure -> result
        }
    }

    override suspend fun createPortalSession(path: String): AppResult<String> = withContext(Dispatchers.IO) {
        if (!tokenVault.hasToken()) {
            return@withContext AppResult.Failure(AppError.Unauthenticated())
        }

        when (val result = safeApiCall(moshi) { api.portalSession(PortalSessionRequestDto(path)) }) {
            is AppResult.Success -> AppResult.Success(result.value.url)
            is AppResult.Failure -> result
        }
    }

    override suspend fun logout(): AppResult<Unit> = withContext(Dispatchers.IO) {
        if (tokenVault.hasToken()) {
            safeApiCall(moshi) { api.logout() }
        }
        clearLocalSession()
        AppResult.Success(Unit)
    }

    private suspend fun persist(snapshot: SessionSnapshot) {
        val cache = snapshot.toCache(nowEpochMs())
        database.withTransaction {
            database.sessionDao().replace(
                session = cache.session,
                roles = cache.roles,
                permissions = cache.permissions,
                features = cache.features,
                modules = cache.modules,
            )
        }
        tenantContextStore.setActiveTenant(snapshot.school.tenantKey)
    }

    private suspend fun clearLocalSession() {
        tokenVault.clear()
        tenantContextStore.clear()
        database.clearAllTables()
    }
}
