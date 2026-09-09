package online.educoreng.educore.core.model

data class UserIdentity(
    val id: Long,
    val name: String,
    val email: String?,
    val staffId: String?,
    val roleKey: String,
    val roleLabel: String,
    val roles: List<String>,
    val portal: String,
)

data class SchoolIdentity(
    val id: Long?,
    val name: String,
    val slug: String,
    val primaryColor: String = "#071E45",
    val accentColor: String = "#15447F",
    val motto: String? = null,
) {
    val tenantKey: String = id?.toString() ?: "platform"
}

data class AcademicPeriod(
    val sessionId: Long?,
    val sessionName: String?,
    val termId: Long?,
    val termName: String?,
)

data class TenantAccess(
    val allowed: Boolean,
    val state: String,
    val message: String,
    val severity: String?,
    val expiresAt: String?,
)

data class ModuleDescriptor(
    val key: String,
    val title: String,
    val path: String,
    val icon: String,
)

data class SessionSnapshot(
    val user: UserIdentity,
    val school: SchoolIdentity,
    val academicPeriod: AcademicPeriod,
    val access: TenantAccess,
    val permissions: Set<String>,
    val modules: List<ModuleDescriptor>,
    val serverTime: String?,
    val contractVersion: Int = 1,
    val features: Set<String> = emptySet(),
    val tokenExpiresAt: String? = null,
) {
    fun can(permission: String): Boolean =
        permissions.contains("*") || permissions.contains(permission)

    fun hasModule(key: String): Boolean = modules.any { it.key == key }
}
