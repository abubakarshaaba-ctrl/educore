package online.educoreng.educore.core.data.local

import online.educoreng.educore.core.model.AcademicPeriod
import online.educoreng.educore.core.model.ModuleDescriptor
import online.educoreng.educore.core.model.SchoolIdentity
import online.educoreng.educore.core.model.SessionSnapshot
import online.educoreng.educore.core.model.TenantAccess
import online.educoreng.educore.core.model.UserIdentity

fun SessionSnapshot.toCache(nowEpochMs: Long): CachedSessionAggregate {
    val tenantKey = school.tenantKey
    return CachedSessionAggregate(
        session = CachedSessionEntity(
            tenantKey = tenantKey,
            userId = user.id,
            userName = user.name,
            userEmail = user.email,
            staffId = user.staffId,
            roleKey = user.roleKey,
            roleLabel = user.roleLabel,
            portal = user.portal,
            schoolId = school.id,
            schoolName = school.name,
            schoolSlug = school.slug,
            schoolPrimaryColor = school.primaryColor,
            schoolAccentColor = school.accentColor,
            schoolMotto = school.motto,
            sessionId = academicPeriod.sessionId,
            sessionName = academicPeriod.sessionName,
            termId = academicPeriod.termId,
            termName = academicPeriod.termName,
            accessAllowed = access.allowed,
            accessState = access.state,
            accessMessage = access.message,
            accessSeverity = access.severity,
            accessExpiresAt = access.expiresAt,
            serverTime = serverTime,
            contractVersion = contractVersion,
            tokenExpiresAt = tokenExpiresAt,
            cachedAtEpochMs = nowEpochMs,
        ),
        roles = user.roles.map { CachedRoleEntity(tenantKey, it) },
        permissions = permissions.map { CachedPermissionEntity(tenantKey, it) },
        features = features.map { CachedFeatureEntity(tenantKey, it) },
        modules = modules.mapIndexed { index, module ->
            CachedModuleEntity(
                tenantKey = tenantKey,
                key = module.key,
                title = module.title,
                path = module.path,
                icon = module.icon,
                sortOrder = index,
            )
        },
    )
}

fun CachedSessionAggregate.toDomain(): SessionSnapshot = SessionSnapshot(
    user = UserIdentity(
        id = session.userId,
        name = session.userName,
        email = session.userEmail,
        staffId = session.staffId,
        roleKey = session.roleKey,
        roleLabel = session.roleLabel,
        roles = roles.map(CachedRoleEntity::name).sorted(),
        portal = session.portal,
    ),
    school = SchoolIdentity(
        id = session.schoolId,
        name = session.schoolName,
        slug = session.schoolSlug,
        primaryColor = session.schoolPrimaryColor,
        accentColor = session.schoolAccentColor,
        motto = session.schoolMotto,
    ),
    academicPeriod = AcademicPeriod(
        sessionId = session.sessionId,
        sessionName = session.sessionName,
        termId = session.termId,
        termName = session.termName,
    ),
    access = TenantAccess(
        allowed = session.accessAllowed,
        state = session.accessState,
        message = session.accessMessage,
        severity = session.accessSeverity,
        expiresAt = session.accessExpiresAt,
    ),
    permissions = permissions.map(CachedPermissionEntity::name).toSet(),
    features = features.map(CachedFeatureEntity::name).toSet(),
    modules = modules.sortedBy(CachedModuleEntity::sortOrder).map { module ->
        ModuleDescriptor(module.key, module.title, module.path, module.icon)
    },
    serverTime = session.serverTime,
    contractVersion = session.contractVersion,
    tokenExpiresAt = session.tokenExpiresAt,
)
