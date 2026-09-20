package online.educoreng.educore.presentation

import android.graphics.BitmapFactory
import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.grid.GridItemSpan
import androidx.compose.foundation.lazy.grid.LazyGridScope
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.layout.ContentScale
import java.text.DateFormat
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Date
import java.util.Locale
import kotlin.math.ceil
import online.educoreng.educore.core.designsystem.component.EduCoreDashboardCard
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreMetricCard
import online.educoreng.educore.core.designsystem.component.EduCoreProfileHeader
import online.educoreng.educore.core.designsystem.component.EduCoreQuickAction
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.component.EduCoreWarningBanner
import online.educoreng.educore.core.designsystem.icon.EduCoreIcons
import online.educoreng.educore.core.designsystem.layout.EduCoreWindowWidth
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSizes
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.DashboardItem
import online.educoreng.educore.core.model.DashboardMetric
import online.educoreng.educore.core.model.ModuleDescriptor
import online.educoreng.educore.core.model.SessionSnapshot

internal fun LazyGridScope.dashboardHomeContent(
    session: SessionSnapshot,
    state: DashboardUiState,
    width: EduCoreWindowWidth,
    onModuleClick: (ModuleDescriptor) -> Unit,
    onRetry: () -> Unit,
) {
    item(key = "profile", span = { GridItemSpan(maxLineSpan) }) {
        val photoBitmap = remember(state.staffPhoto) {
            state.staffPhoto?.let { bytes ->
                runCatching { BitmapFactory.decodeByteArray(bytes, 0, bytes.size) }.getOrNull()
            }
        }
        EduCoreProfileHeader(
            name = "${timeGreeting(session.serverTime)}, ${session.user.name}",
            role = session.user.roleLabel,
            identifier = session.user.staffId ?: session.user.email,
            modifier = Modifier.fillMaxWidth(),
            avatar = photoBitmap?.let { bitmap ->
                {
                    Image(
                        bitmap = bitmap.asImageBitmap(),
                        contentDescription = "${session.user.name} profile photo",
                        modifier = Modifier
                            .size(EduCoreSizes.LargeAvatar)
                            .clip(MaterialTheme.shapes.large),
                        contentScale = ContentScale.Crop,
                    )
                }
            },
        )
    }

    if (session.school.id != null) {
        item(key = "subscription-status", span = { GridItemSpan(maxLineSpan) }) {
            SubscriptionCountdownTile(session = session)
        }
    }

    val snapshot = state.snapshot
    if (snapshot == null) {
        val dashboardError = state.errorMessage
        item(key = "dashboard-state", span = { GridItemSpan(maxLineSpan) }) {
            when {
                state.isLoading -> EduCoreLoadingState(message = "Loading your workspace")
                dashboardError != null -> EduCoreErrorState(
                    message = dashboardError,
                    title = "Dashboard unavailable",
                    onRetry = onRetry,
                )
                else -> EduCoreEmptyState(
                    title = "No dashboard data",
                    message = "Refresh to load your current EduCore workspace.",
                    actionLabel = "Refresh",
                    onAction = onRetry,
                )
            }
        }
        return
    }

    if (snapshot.isFromCache) {
        item(key = "cached-dashboard", span = { GridItemSpan(maxLineSpan) }) {
            EduCoreWarningBanner(
                title = "Saved dashboard",
                message = snapshot.cachedAtEpochMs?.let { "Last updated ${formatCacheTime(it)}" }
                    ?: "Live data could not be reached; saved information is shown.",
            )
        }
    }

    item(key = "overview-header", span = { GridItemSpan(maxLineSpan) }) {
        EduCoreSectionHeader(
            title = "Overview",
            supportingText = "Updated ${sourceTime(snapshot.generatedAt)}",
        )
    }
    items(snapshot.metrics, key = DashboardMetric::key) { metric ->
        EduCoreMetricCard(
            label = metric.label,
            value = metric.displayValue,
            icon = metricIcon(metric),
            tone = metric.tone.toEduCoreTone(),
            modifier = Modifier.fillMaxWidth(),
        )
    }

    if (snapshot.quickActions.isNotEmpty()) {
        item(key = "actions-header", span = { GridItemSpan(maxLineSpan) }) {
            EduCoreSectionHeader(
                title = "Quick actions",
                supportingText = "Shortcuts available to your account",
            )
        }
        items(snapshot.quickActions, key = { "action-${it.moduleKey}" }) { action ->
            val module = session.modules.firstOrNull { it.key == action.moduleKey }
            EduCoreQuickAction(
                label = action.title,
                icon = module?.let(::moduleIconForDashboard) ?: EduCoreIcons.Modules,
                enabled = module != null,
                onClick = { module?.let(onModuleClick) },
                modifier = Modifier.fillMaxWidth(),
            )
        }
    }

    snapshot.sections.forEach { section ->
        item(key = "section-${section.key}", span = { GridItemSpan(maxLineSpan) }) {
            DashboardSectionCard(
                title = section.title,
                items = section.items,
                modules = session.modules,
                onModuleClick = onModuleClick,
                compact = width == EduCoreWindowWidth.Compact,
            )
        }
    }
}

@Composable
private fun SubscriptionCountdownTile(session: SessionSnapshot) {
    val access = session.access
    val state = access.state.lowercase()
    val expiryEpochMs = parseIsoEpoch(access.expiresAt)
    val referenceEpochMs = parseIsoEpoch(session.serverTime) ?: System.currentTimeMillis()
    val remainingMs = expiryEpochMs?.minus(referenceEpochMs)
    val dayMs = 86_400_000.0

    val statusLabel = when (state) {
        "free" -> "Free plan"
        "expiring_soon" -> "Expiring soon"
        "grace" -> "Grace period"
        "expired" -> "Expired"
        "suspended" -> "Suspended"
        "inactive", "missing" -> "Unavailable"
        else -> "Active"
    }

    val tone = when (state) {
        "free", "allowed", "trial" -> EduCoreTone.Success
        "expiring_soon", "grace" -> EduCoreTone.Warning
        "expired", "suspended", "inactive", "missing" -> EduCoreTone.Danger
        else -> EduCoreTone.Neutral
    }

    val countdown = when {
        state == "free" -> "No expiry"
        expiryEpochMs == null -> statusLabel
        state == "grace" && remainingMs != null && remainingMs > 0 -> {
            val days = ceil(remainingMs / dayMs).toLong().coerceAtLeast(1L)
            if (days == 1L) "1 day grace remaining" else "$days days grace remaining"
        }
        remainingMs != null && remainingMs > 0 -> {
            val days = ceil(remainingMs / dayMs).toLong()
            if (days == 1L) "1 day remaining" else "$days days remaining"
        }
        remainingMs != null && remainingMs == 0L -> if (state == "grace") "Grace ends today" else "Expires today"
        else -> "Expired"
    }

    val dateLine = expiryEpochMs?.let {
        val label = if (state == "grace") "Grace ends" else "Expiry date"
        "$label: ${DateFormat.getDateInstance(DateFormat.MEDIUM).format(Date(it))}"
    } ?: when (state) {
        "free" -> "Your school currently has no subscription expiry date."
        else -> "Subscription expiry date is not available."
    }

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Column(
            modifier = Modifier.fillMaxWidth(),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                verticalAlignment = Alignment.Top,
            ) {
                Column(
                    modifier = Modifier.weight(1f),
                    verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs),
                ) {
                    Text(
                        text = "Subscription status",
                        style = MaterialTheme.typography.titleSmall,
                        color = EduCoreColors.Slate600,
                    )
                    Text(
                        text = countdown,
                        style = MaterialTheme.typography.headlineSmall,
                    )
                }
                EduCoreStatusBadge(statusLabel, tone)
            }
            Text(
                text = dateLine,
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Muted500,
            )
            if (access.message.isNotBlank() && state in setOf("expiring_soon", "grace", "expired")) {
                Text(
                    text = access.message,
                    style = MaterialTheme.typography.bodySmall,
                    color = EduCoreColors.Slate600,
                )
            }
        }
    }
}

@Composable
private fun DashboardSectionCard(
    title: String,
    items: List<DashboardItem>,
    modules: List<ModuleDescriptor>,
    onModuleClick: (ModuleDescriptor) -> Unit,
    compact: Boolean,
) {
    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        EduCoreSectionHeader(title = title, actionLabel = null)
        Column(
            modifier = Modifier.fillMaxWidth().padding(top = EduCoreSpacing.Sm),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            items.forEachIndexed { index, item ->
                val module = item.moduleKey?.let { key -> modules.firstOrNull { it.key == key } }
                Column(
                    modifier = Modifier.fillMaxWidth(),
                    verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs),
                ) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                        verticalAlignment = Alignment.Top,
                    ) {
                        Column(Modifier.weight(1f)) {
                            Text(item.title, style = MaterialTheme.typography.titleSmall)
                            item.subtitle?.let {
                                Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                            }
                            item.supportingText?.let {
                                Text(it, style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Muted500)
                            }
                            item.timestamp?.let {
                                Text(sourceTime(it), style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Muted500)
                            }
                        }
                        item.status?.let { status -> EduCoreStatusBadge(status, status.toStatusTone()) }
                    }
                    if (module != null && !compact) {
                        EduCoreQuickAction(
                            label = "Open ${module.title}",
                            icon = moduleIconForDashboard(module),
                            onClick = { onModuleClick(module) },
                        )
                    }
                }
                if (index != items.lastIndex) HorizontalDivider(color = EduCoreColors.Line200)
            }
        }
    }
}

private fun DashboardMetric.toIconKey(): String = moduleKey ?: key

private fun metricIcon(metric: DashboardMetric): ImageVector {
    val key = metric.toIconKey().lowercase()
    return when {
        key.contains("student") -> EduCoreIcons.Students
        key.contains("staff") || key.contains("school") -> EduCoreIcons.Teacher
        key.contains("attendance") -> EduCoreIcons.Attendance
        key.contains("revenue") || key.contains("billed") || key.contains("outstanding") || key.contains("expense") -> EduCoreIcons.Payments
        key.contains("exam") || key.contains("dut") -> EduCoreIcons.ExamDuties
        key.contains("class") -> EduCoreIcons.Classes
        key.contains("route") || key.contains("bus") -> EduCoreIcons.Schedule
        key.contains("alert") || key.contains("medication") -> EduCoreIcons.Notices
        key.contains("average") || key.contains("position") -> EduCoreIcons.Scores
        else -> EduCoreIcons.Dashboard
    }
}

private fun moduleIconForDashboard(module: ModuleDescriptor): ImageVector {
    val key = module.key.lowercase()
    return when {
        key.contains("student") -> EduCoreIcons.Students
        key.contains("class") -> EduCoreIcons.Classes
        key.contains("subject") || key.contains("curriculum") -> EduCoreIcons.Subjects
        key.contains("attendance") -> EduCoreIcons.Attendance
        key.contains("score") -> EduCoreIcons.Scores
        key.contains("timetable") || key.contains("schedule") -> EduCoreIcons.Schedule
        key.contains("lesson") -> EduCoreIcons.LessonPlan
        key.contains("repository") -> EduCoreIcons.Repository
        key.contains("payment") || key.contains("fee") || key.contains("billing") -> EduCoreIcons.Payments
        key.contains("notification") || key.contains("message") || key.contains("notice") -> EduCoreIcons.Notices
        key.contains("setting") -> EduCoreIcons.Settings
        else -> EduCoreIcons.Modules
    }
}

private fun String.toEduCoreTone(): EduCoreTone = when (lowercase()) {
    "brand" -> EduCoreTone.Brand
    "accent" -> EduCoreTone.Accent
    "success" -> EduCoreTone.Success
    "warning" -> EduCoreTone.Warning
    "danger" -> EduCoreTone.Danger
    "info" -> EduCoreTone.Info
    "purple" -> EduCoreTone.Purple
    else -> EduCoreTone.Neutral
}

private fun String.toStatusTone(): EduCoreTone = when (lowercase()) {
    "active", "published", "present", "paid" -> EduCoreTone.Success
    "inactive", "failed", "suspended", "attention" -> EduCoreTone.Danger
    "pending", "review", "duty", "late" -> EduCoreTone.Warning
    else -> EduCoreTone.Neutral
}

private fun timeGreeting(serverTime: String?): String {
    val calendar = Calendar.getInstance()
    parseIsoEpoch(serverTime)?.let { calendar.timeInMillis = it }
    return when (calendar.get(Calendar.HOUR_OF_DAY)) {
        in 5..11 -> "Good morning"
        in 12..16 -> "Good afternoon"
        else -> "Good evening"
    }
}

private fun parseIsoEpoch(value: String?): Long? {
    if (value.isNullOrBlank()) return null

    val withoutFraction = value.trim().replace(
        Regex("\\.\\d+(?=(Z|[+-]\\d{2}:\\d{2})$)"),
        "",
    )
    val normalized = when {
        withoutFraction.endsWith("Z") -> withoutFraction.dropLast(1) + "+0000"
        Regex("[+-]\\d{2}:\\d{2}$").containsMatchIn(withoutFraction) ->
            withoutFraction.dropLast(3) + withoutFraction.takeLast(2)
        else -> withoutFraction
    }

    return runCatching {
        SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssZ", Locale.US).apply {
            isLenient = false
        }.parse(normalized)?.time
    }.getOrNull()
}

private fun formatCacheTime(epochMs: Long): String =
    DateFormat.getDateTimeInstance(DateFormat.MEDIUM, DateFormat.SHORT).format(Date(epochMs))

private fun sourceTime(value: String): String = value
    .replace('T', ' ')
    .substringBefore('+')
    .removeSuffix("Z")
    .take(19)
