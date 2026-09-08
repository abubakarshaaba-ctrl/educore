package online.educoreng.educore.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.grid.GridItemSpan
import androidx.compose.foundation.lazy.grid.LazyGridScope
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Person
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import java.text.DateFormat
import java.util.Calendar
import java.util.Date
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreQuickAction
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseSectionCard
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseStat
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.component.EduCoreWarningBanner
import online.educoreng.educore.core.designsystem.icon.EduCoreIcons
import online.educoreng.educore.core.designsystem.layout.EduCoreWindowWidth
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.DashboardItem
import online.educoreng.educore.core.model.DashboardMetric
import online.educoreng.educore.core.model.ModuleDescriptor
import online.educoreng.educore.core.model.SessionSnapshot

/**
 * Home dashboard matching the approved August mobile concept.
 * Data remains entirely server-driven: this layer changes presentation, not
 * authorisation. Metrics/actions/modules only appear when the bootstrap and
 * dashboard contracts expose them to the authenticated role.
 */
internal fun LazyGridScope.dashboardHomeContent(
    session: SessionSnapshot,
    state: DashboardUiState,
    width: EduCoreWindowWidth,
    onModuleClick: (ModuleDescriptor) -> Unit,
    onRetry: () -> Unit,
) {
    item(key = "hero", span = { GridItemSpan(maxLineSpan) }) {
        EduCoreShowcaseHero(
            eyebrow = daypartGreeting(),
            title = session.user.name,
            subtitle = listOfNotNull(
                session.user.roleLabel,
                session.user.staffId,
            ).joinToString(" · "),
            trailing = {
                Surface(
                    modifier = Modifier.size(46.dp),
                    shape = CircleShape,
                    color = EduCoreColors.Gold100,
                    contentColor = EduCoreColors.Navy900,
                ) {
                    Box(contentAlignment = Alignment.Center) {
                        Icon(Icons.Default.Person, contentDescription = null, modifier = Modifier.size(23.dp))
                    }
                }
            },
        ) {
            Surface(
                shape = MaterialTheme.shapes.medium,
                color = Color.White.copy(alpha = 0.10f),
                contentColor = Color.White,
            ) {
                Text(
                    text = listOfNotNull(
                        session.academicPeriod.sessionName,
                        session.academicPeriod.termName,
                    ).joinToString(" · ").ifBlank { session.school.name },
                    modifier = Modifier.padding(horizontal = EduCoreSpacing.Md, vertical = EduCoreSpacing.Sm),
                    style = MaterialTheme.typography.labelMedium,
                )
            }
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
        EduCoreShowcaseStat(
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
                title = "Quick links",
                supportingText = "Your most useful actions",
            )
        }
        items(snapshot.quickActions, key = { "action-${it.moduleKey}" }) { action ->
            val module = session.modules.firstOrNull { it.key.equals(action.moduleKey, ignoreCase = true) }
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
private fun DashboardSectionCard(
    title: String,
    items: List<DashboardItem>,
    modules: List<ModuleDescriptor>,
    onModuleClick: (ModuleDescriptor) -> Unit,
    compact: Boolean,
) {
    EduCoreShowcaseSectionCard {
        EduCoreSectionHeader(title = title, actionLabel = null)
        Column(
            modifier = Modifier.fillMaxWidth().padding(top = EduCoreSpacing.Sm),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            items.forEachIndexed { index, item ->
                val module = item.moduleKey?.let { key -> modules.firstOrNull { it.key.equals(key, true) } }
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
                            Text(item.title, style = MaterialTheme.typography.titleSmall, color = EduCoreColors.Ink900)
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
        key.contains("score") || key.contains("result") || key.contains("report") -> EduCoreIcons.Scores
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
    "purple" -> EduCoreTone.Brand
    else -> EduCoreTone.Neutral
}

private fun String.toStatusTone(): EduCoreTone = when (lowercase()) {
    "active", "published", "present", "paid", "complete", "completed" -> EduCoreTone.Success
    "inactive", "failed", "suspended", "attention", "absent" -> EduCoreTone.Danger
    "pending", "review", "duty", "late" -> EduCoreTone.Warning
    else -> EduCoreTone.Neutral
}

private fun daypartGreeting(): String = when (Calendar.getInstance().get(Calendar.HOUR_OF_DAY)) {
    in 0..11 -> "Good morning"
    in 12..16 -> "Good afternoon"
    else -> "Good evening"
}

private fun formatCacheTime(epochMs: Long): String =
    DateFormat.getDateTimeInstance(DateFormat.MEDIUM, DateFormat.SHORT).format(Date(epochMs))

private fun sourceTime(value: String): String = value
    .replace('T', ' ')
    .substringBefore('+')
    .removeSuffix("Z")
    .take(19)
