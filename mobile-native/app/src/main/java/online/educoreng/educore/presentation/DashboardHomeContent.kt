package online.educoreng.educore.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import java.text.DateFormat
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Date
import java.util.Locale
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreQuickAction
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
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

internal fun androidx.compose.foundation.lazy.grid.LazyGridScope.dashboardHomeContent(
    session: SessionSnapshot,
    state: DashboardUiState,
    width: EduCoreWindowWidth,
    onModuleClick: (ModuleDescriptor) -> Unit,
    onRetry: () -> Unit,
) {
    item(key = "personal-welcome", span = { androidx.compose.foundation.lazy.grid.GridItemSpan(maxLineSpan) }) {
        DashboardWelcome(session)
    }

    val snapshot = state.snapshot
    if (snapshot == null) {
        val dashboardError = state.errorMessage
        item(key = "dashboard-state", span = { androidx.compose.foundation.lazy.grid.GridItemSpan(maxLineSpan) }) {
            when {
                state.isLoading -> EduCoreLoadingState(message = "Loading dashboard")
                dashboardError != null -> EduCoreErrorState(
                    message = dashboardError,
                    title = "Dashboard unavailable",
                    onRetry = onRetry,
                )
                else -> EduCoreEmptyState(
                    title = "No dashboard data",
                    message = "Refresh to try again.",
                    actionLabel = "Refresh",
                    onAction = onRetry,
                )
            }
        }
        return
    }

    if (snapshot.isFromCache) {
        item(key = "cached-dashboard", span = { androidx.compose.foundation.lazy.grid.GridItemSpan(maxLineSpan) }) {
            EduCoreWarningBanner(
                title = "Saved data",
                message = snapshot.cachedAtEpochMs?.let { "Updated ${formatCacheTime(it)}" } ?: "Live data unavailable.",
            )
        }
    }

    item(key = "overview-header", span = { androidx.compose.foundation.lazy.grid.GridItemSpan(maxLineSpan) }) {
        EduCoreSectionHeader(title = "Overview")
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
        item(key = "actions-header", span = { androidx.compose.foundation.lazy.grid.GridItemSpan(maxLineSpan) }) {
            EduCoreSectionHeader(title = "Quick links")
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
        item(key = "section-${section.key}", span = { androidx.compose.foundation.lazy.grid.GridItemSpan(maxLineSpan) }) {
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
private fun DashboardWelcome(session: SessionSnapshot) {
    val firstName = session.user.name.trim().substringBefore(' ').ifBlank { "there" }
    val now = Calendar.getInstance()
    val greeting = when (now.get(Calendar.HOUR_OF_DAY)) {
        in 5..11 -> "Good morning"
        in 12..16 -> "Good afternoon"
        else -> "Good evening"
    }
    val date = SimpleDateFormat("EEE, d MMM", Locale.getDefault()).format(now.time)
    val academic = listOfNotNull(session.academicPeriod.sessionName, session.academicPeriod.termName)
        .filter(String::isNotBlank)
        .joinToString(" · ")

    Column(
        modifier = Modifier.fillMaxWidth().padding(vertical = EduCoreSpacing.Xs),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xxs),
    ) {
        Text(
            text = "$greeting, $firstName",
            style = MaterialTheme.typography.titleLarge,
            color = EduCoreColors.Navy900,
        )
        Text(
            text = listOf(date, academic).filter(String::isNotBlank).joinToString(" · "),
            style = MaterialTheme.typography.bodySmall,
            color = EduCoreColors.Slate600,
        )
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
    "brand", "accent", "purple" -> EduCoreTone.Brand
    "success" -> EduCoreTone.Success
    "warning" -> EduCoreTone.Warning
    "danger" -> EduCoreTone.Danger
    "info" -> EduCoreTone.Info
    else -> EduCoreTone.Neutral
}

private fun String.toStatusTone(): EduCoreTone = when (lowercase()) {
    "active", "published", "present", "paid", "complete", "completed" -> EduCoreTone.Success
    "inactive", "failed", "suspended", "attention", "absent" -> EduCoreTone.Danger
    "pending", "review", "duty", "late" -> EduCoreTone.Warning
    else -> EduCoreTone.Neutral
}

private fun formatCacheTime(epochMs: Long): String =
    DateFormat.getDateTimeInstance(DateFormat.MEDIUM, DateFormat.SHORT).format(Date(epochMs))

private fun sourceTime(value: String): String = value
    .replace('T', ' ')
    .substringBefore('+')
    .removeSuffix("Z")
    .take(19)
