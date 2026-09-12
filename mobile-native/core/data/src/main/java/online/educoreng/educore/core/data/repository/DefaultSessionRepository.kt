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
import kotlinx.coroutines.withTimeoutOrNull
import online.educoreng.educore.core.common.AppError
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.data.local.EduCoreDatabase
import online.educoreng.educore.core.data.local.toCache
import online.educoreng.educore.core.data.local.toDomain
import online.educoreng.educore.core.data.preferences.TenantContextStore
import online.educoreng.educore.core.model.AcademicPeriod
import online.educoreng.educore.core.model.ModuleDescriptor
import online.educoreng.educore.core.model.SchoolIdentity
import online.educoreng.educore.core.model.SessionSnapshot
import online.educoreng.educore.core.model.TenantAccess
import online.educoreng.educore.core.model.UserIdentity
import online.educoreng.educore.core.network.EduCoreApi
import online.educoreng.educore.core.network.dto.ForgotPasswordRequestDto
import online.educoreng.educore.core.network.dto.LoginRequestDto
import online.educoreng.educore.core.network.dto.LoginResponseDto
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
                database.sessionDao().observe(tenantKey).map { it?.toDomain()?.normalizedForClient() }
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

                // Authentication itself already returns enough trusted identity,
                // school and permission context to open the app safely. Persist a
                // provisional session before requesting the richer bootstrap so a
                // transient/bootstrap-only backend failure can never trap a valid
                // user on the sign-in screen.
                val provisional = result.value.toProvisionalSession().normalizedForClient()
                persist(provisional)

                when (val bootstrap = refresh()) {
                    is AppResult.Success -> bootstrap
                    is AppResult.Failure -> AppResult.Success(provisional)
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
                val snapshot = result.value.toDomain().normalizedForClient()
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
            withTimeoutOrNull(600L) {
                safeApiCall(moshi) { api.logout() }
            }
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

    private fun LoginResponseDto.toProvisionalSession(): SessionSnapshot {
        val permissionSet = permissions.toSet()
        val portal = user.portal.lowercase()
        val role = user.roleKey.lowercase()

        fun allowed(key: String): Boolean =
            "*" in permissionSet ||
                key in permissionSet ||
                permissionSet.any { it.startsWith("$key.") }

        val knownModules = listOf(
            ModuleDescriptor("students", "Students", "/students", "students"),
            ModuleDescriptor("staff", "Staff", "/staff", "staff"),
            ModuleDescriptor("classes", "Classes", "/classes", "classes"),
            ModuleDescriptor("subjects", "Subjects", "/subjects", "subjects"),
            ModuleDescriptor("curriculum", "Curriculum", "/curriculum", "curriculum"),
            ModuleDescriptor("academic-cycle", "Academic Sessions", "/academic-session", "academic-cycle"),
            ModuleDescriptor("attendance", "Student Attendance", "/attendance", "attendance"),
            ModuleDescriptor("skills", "Skill Ratings", "/skills", "skills"),
            ModuleDescriptor("scores", "Scores", "/scores", "scores"),
            ModuleDescriptor("timetable", "Timetable", "/timetable", "timetable"),
            ModuleDescriptor("reports", "Report Cards", "/reports", "reports"),
            ModuleDescriptor("fees", "Fees & Invoices", "/fees/invoices", "fees"),
            ModuleDescriptor("expenses", "Expenses", "/expenses", "expenses"),
            ModuleDescriptor("payroll", "Payroll", "/payroll", "payroll"),
            ModuleDescriptor("admissions", "Admissions", "/admissions", "admissions"),
            ModuleDescriptor("transfers", "Student Transfers", "/students/transfers", "transfers"),
            ModuleDescriptor("portal-accounts", "Portal Accounts", "/portal-accounts", "profile"),
            ModuleDescriptor("messages", "Messages", "/messages", "messages"),
            ModuleDescriptor("notifications.view", "Notifications", "/notifications", "notifications"),
            ModuleDescriptor("calendar.view", "Calendar", "/calendar", "calendar"),
            ModuleDescriptor("health", "Health Records", "/health", "health"),
            ModuleDescriptor("transport", "Transport", "/transport", "transport"),
            ModuleDescriptor("library", "Library", "/library", "library"),
            ModuleDescriptor("inventory", "Inventory", "/inventory", "inventory"),
            ModuleDescriptor("hostels", "Hostels", "/hostels", "hostels"),
            ModuleDescriptor("analytics", "Analytics", "/analytics", "analytics"),
            ModuleDescriptor("risk", "Risk Flags", "/risk", "risk"),
            ModuleDescriptor("exports", "Exports", "/exports", "exports"),
            ModuleDescriptor("lesson-planner", "Lesson Planner", "/lesson-planner", "lesson-planner"),
            ModuleDescriptor("academic-repository", "Academic Repository", "/academic-repository", "repository"),
        ).filter { allowed(it.key) }
            .toMutableList()

        if (portal in setOf("staff", "admin") && allowed("staff-attendance.self")) {
            knownModules += ModuleDescriptor(
                "staff-attendance.self",
                "My Attendance",
                "/staff-attendance/my",
                "staff-attendance",
            )
        }
        if (portal == "admin" && allowed("staff-attendance")) {
            knownModules += ModuleDescriptor(
                "staff-attendance.admin",
                "Staff Attendance",
                "/staff-attendance",
                "staff-attendance",
            )
        }
        knownModules += ModuleDescriptor("profile", "My Profile", "/profile", "profile")

        return SessionSnapshot(
            user = UserIdentity(
                id = user.id,
                name = user.name,
                email = user.email,
                staffId = user.staffId,
                roleKey = user.roleKey,
                roleLabel = user.role,
                roles = user.roles.ifEmpty { listOf(role).filter(String::isNotBlank) },
                portal = user.portal,
            ),
            school = SchoolIdentity(
                id = school.id,
                name = school.name,
                slug = school.slug,
                primaryColor = school.branding.primaryColor,
                accentColor = school.branding.accentColor,
                motto = school.branding.motto,
            ),
            academicPeriod = AcademicPeriod(null, null, null, null),
            access = TenantAccess(
                allowed = true,
                state = "allowed",
                message = "School account access is available.",
                severity = null,
                expiresAt = null,
            ),
            permissions = permissionSet,
            modules = knownModules.distinctBy { it.key.lowercase() },
            serverTime = null,
            contractVersion = 1,
            features = emptySet(),
            tokenExpiresAt = null,
        )
    }

    private fun SessionSnapshot.normalizedForClient(): SessionSnapshot {
        val granted = modules.distinctBy { it.key.lowercase() }
        if (user.portal == "admin" || user.portal == "platform") {
            return copy(modules = granted)
        }

        val hasSelfAttendance = granted.any { it.key.equals("staff-attendance.self", ignoreCase = true) }
        return copy(
            modules = granted.filterNot { module ->
                hasSelfAttendance && module.key.equals("staff-attendance", ignoreCase = true)
            }
        )
    }

    private fun AppError.asPostLoginBootstrapError(): AppError = when (this) {
        is AppError.Unauthenticated -> AppError.Unauthenticated(
            "Your account was verified, but the secure session could not be started. Please sign in again.",
        )
        is AppError.Forbidden,
        is AppError.SubscriptionRestricted,
        is AppError.Validation -> this
        is AppError.NetworkUnavailable -> AppError.NetworkUnavailable(
            "Your account was verified, but EduCore could not load your school workspace. Check your connection and try again.",
        )
        is AppError.Timeout -> AppError.Timeout(
            "Your account was verified, but loading your school workspace took too long. Please try again.",
        )
        is AppError.Server -> AppError.Server(
            userMessage = "Your account was verified, but EduCore could not load your school workspace. Please try again.",
            statusCode = statusCode,
            requestId = requestId,
        )
        is AppError.NotFound,
        is AppError.Conflict,
        is AppError.RateLimited,
        is AppError.Unexpected -> AppError.Unexpected(
            "Your account was verified, but EduCore could not finish loading your school workspace. Please try again.",
        )
    }

    private suspend fun clearLocalSession() {
        tokenVault.clear()
        tenantContextStore.clear()
        database.clearAllTables()
    }
}
