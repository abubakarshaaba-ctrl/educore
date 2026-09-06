package online.educoreng.educore.core.network

import online.educoreng.educore.core.model.AcademicPeriod
import online.educoreng.educore.core.model.DashboardAction
import online.educoreng.educore.core.model.DashboardItem
import online.educoreng.educore.core.model.DashboardMetric
import online.educoreng.educore.core.model.DashboardSection
import online.educoreng.educore.core.model.DashboardSnapshot
import online.educoreng.educore.core.model.ModuleDescriptor
import online.educoreng.educore.core.model.SchoolIdentity
import online.educoreng.educore.core.model.SessionSnapshot
import online.educoreng.educore.core.model.TenantAccess
import online.educoreng.educore.core.model.UserIdentity
import online.educoreng.educore.core.network.dto.BootstrapResponseDto
import online.educoreng.educore.core.network.dto.DashboardResponseDto
import online.educoreng.educore.core.network.dto.ModuleDto
import online.educoreng.educore.core.network.dto.SchoolDto
import online.educoreng.educore.core.network.dto.UserDto

fun BootstrapResponseDto.toDomain(): SessionSnapshot = SessionSnapshot(
    user = user.toDomain(),
    school = school.toDomain(),
    academicPeriod = AcademicPeriod(
        sessionId = academic.session?.id,
        sessionName = academic.session?.name,
        termId = academic.term?.id,
        termName = academic.term?.name,
    ),
    access = TenantAccess(
        allowed = access.allowed,
        state = access.state,
        message = access.message,
        severity = access.severity,
        expiresAt = access.expiresAt,
    ),
    permissions = permissions.toSet(),
    modules = modules.map(ModuleDto::toDomain),
    serverTime = serverTime,
    contractVersion = contractVersion,
    features = features.toSet(),
    tokenExpiresAt = token.expiresAt,
)

fun UserDto.toDomain(): UserIdentity = UserIdentity(
    id = id,
    name = name,
    email = email,
    staffId = staffId,
    roleKey = roleKey,
    roleLabel = role,
    roles = roles,
    portal = portal,
)

fun SchoolDto.toDomain(): SchoolIdentity = SchoolIdentity(
    id = id,
    name = name,
    slug = slug,
    primaryColor = branding.primaryColor,
    accentColor = branding.accentColor,
    motto = branding.motto,
)

fun ModuleDto.toDomain(): ModuleDescriptor = ModuleDescriptor(
    key = key,
    title = title,
    path = path,
    icon = icon,
)

fun DashboardResponseDto.toDomain(
    cachedAtEpochMs: Long? = null,
    isFromCache: Boolean = false,
): DashboardSnapshot = DashboardSnapshot(
    scope = scope,
    roleKey = roleKey,
    generatedAt = generatedAt,
    metrics = metrics.map { metric ->
        DashboardMetric(
            key = metric.key,
            label = metric.label,
            displayValue = metric.displayValue,
            tone = metric.tone,
            moduleKey = metric.moduleKey,
        )
    },
    quickActions = quickActions.map { action ->
        DashboardAction(
            moduleKey = action.moduleKey,
            title = action.title,
            icon = action.icon,
            path = action.path,
        )
    },
    sections = sections.map { section ->
        DashboardSection(
            key = section.key,
            title = section.title,
            items = section.items.map { item ->
                DashboardItem(
                    id = item.id,
                    title = item.title,
                    subtitle = item.subtitle,
                    supportingText = item.supportingText,
                    status = item.status,
                    timestamp = item.timestamp,
                    moduleKey = item.moduleKey,
                )
            },
        )
    },
    contractVersion = contractVersion,
    cachedAtEpochMs = cachedAtEpochMs,
    isFromCache = isFromCache,
)
