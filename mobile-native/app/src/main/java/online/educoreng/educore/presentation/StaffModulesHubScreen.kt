package online.educoreng.educore.presentation

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.GridItemSpan
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Person
import androidx.compose.material3.Icon
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseTile
import online.educoreng.educore.core.designsystem.icon.EduCoreIcons
import online.educoreng.educore.core.designsystem.layout.EduCoreWindowWidth
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.ModuleDescriptor
import online.educoreng.educore.core.model.SessionSnapshot

/**
 * Grouped More / Modules Hub based on the approved August mobile concept.
 *
 * RBAC rule: every tile originates from SessionSnapshot.modules. The screen
 * never constructs a module that the server did not grant to the account.
 */
@Composable
internal fun StaffModulesHubScreen(
    session: SessionSnapshot,
    width: EduCoreWindowWidth,
    onModuleClick: (ModuleDescriptor) -> Unit,
    onLogout: () -> Unit,
) {
    val columns = when (width) {
        EduCoreWindowWidth.Compact -> 3
        EduCoreWindowWidth.Medium -> 4
        EduCoreWindowWidth.Expanded -> 6
    }
    val groups = remember(session.modules) {
        buildModuleHubGroups(session.modules)
    }

    LazyVerticalGrid(
        columns = GridCells.Fixed(columns),
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = androidx.compose.foundation.layout.PaddingValues(eduCoreScreenPadding()),
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
    ) {
        if (groups.all { it.modules.isEmpty() }) {
            item(key = "hub-empty", span = { GridItemSpan(maxLineSpan) }) {
                EduCoreEmptyState(
                    title = "No additional modules",
                    message = "Your available workspaces are already accessible from the main navigation.",
                )
            }
        } else {
            groups.filter { it.modules.isNotEmpty() }.forEach { group ->
                item(key = "hub-heading-${group.key}", span = { GridItemSpan(maxLineSpan) }) {
                    EduCoreSectionHeader(
                        title = group.title,
                        supportingText = group.supportingText,
                    )
                }
                items(group.modules, key = { "hub-${group.key}-${it.key}" }) { module ->
                    EduCoreShowcaseTile(
                        label = module.title,
                        icon = moduleHubIcon(module.key),
                        onClick = { onModuleClick(module) },
                        modifier = Modifier.fillMaxWidth(),
                    )
                }
            }
        }

        item(key = "hub-account-heading", span = { GridItemSpan(maxLineSpan) }) {
            EduCoreSectionHeader(
                title = "Account",
                supportingText = "Account actions for this device",
            )
        }
        item(key = "hub-signout", span = { GridItemSpan(maxLineSpan) }) {
            EduCorePrimaryButton(
                text = "Sign out",
                onClick = onLogout,
                modifier = Modifier.fillMaxWidth(),
                leadingIcon = {
                    Icon(Icons.AutoMirrored.Filled.Logout, contentDescription = null)
                },
            )
        }
    }
}

private data class ModuleHubGroup(
    val key: String,
    val title: String,
    val supportingText: String,
    val modules: List<ModuleDescriptor>,
)

private fun buildModuleHubGroups(modules: List<ModuleDescriptor>): List<ModuleHubGroup> {
    val normalized = modules
        .filterNot {
            it.key.equals("dashboard", ignoreCase = true) ||
                it.key.equals("timetable", ignoreCase = true)
        }
        .sortedBy { it.title.lowercase() }

    // If both attendance aliases arrive, expose only the self-service entry.
    val deduped = normalized
        .groupBy { canonicalHubKey(it.key) }
        .mapNotNull { (_, candidates) ->
            candidates.firstOrNull { it.key.equals("staff-attendance.self", ignoreCase = true) }
                ?: candidates.firstOrNull()
        }

    fun group(keys: Set<String>) = deduped.filter { it.key.lowercase() in keys }
    val known = ACADEMIC_KEYS + OPERATION_KEYS + COMMUNICATION_KEYS + ACCOUNT_KEYS
    val other = deduped.filter { it.key.lowercase() !in known }

    return listOf(
        ModuleHubGroup(
            key = "academics",
            title = "Academics",
            supportingText = "Teaching, learning and assessment",
            modules = group(ACADEMIC_KEYS),
        ),
        ModuleHubGroup(
            key = "operations",
            title = "Operations",
            supportingText = "School administration and support services",
            modules = group(OPERATION_KEYS) + other,
        ),
        ModuleHubGroup(
            key = "communication",
            title = "Communication",
            supportingText = "Messages, notices and school events",
            modules = group(COMMUNICATION_KEYS),
        ),
        ModuleHubGroup(
            key = "profile",
            title = "Profile & Settings",
            supportingText = "Only account modules granted by your school are shown",
            modules = group(ACCOUNT_KEYS),
        ),
    )
}

private fun canonicalHubKey(key: String): String = when (key.lowercase()) {
    "staff-attendance.self" -> "staff-attendance"
    else -> key.lowercase()
}

private val ACADEMIC_KEYS = setOf(
    "students",
    "classes",
    "subjects",
    "curriculum",
    "attendance",
    "scores",
    "scores.entry",
    "reports",
    "report-cards",
    "results",
    "cbt",
    "cbt-exams",
    "examinations",
    "lesson-planner",
    "academic-repository",
    "library",
)

private val OPERATION_KEYS = setOf(
    "staff",
    "staff-attendance",
    "staff-attendance.self",
    "academic-cycle",
    "fees",
    "expenses",
    "payroll",
    "admissions",
    "transport",
    "health",
    "inventory",
    "hostels",
    "analytics",
    "exports",
)

private val COMMUNICATION_KEYS = setOf(
    "messages",
    "notifications.view",
    "announcements",
    "calendar.view",
)

private val ACCOUNT_KEYS = setOf(
    "profile",
    "settings",
)

private fun moduleHubIcon(key: String): ImageVector = when {
    key.equals("profile", ignoreCase = true) -> Icons.Default.Person
    key.contains("student", ignoreCase = true) -> EduCoreIcons.Students
    key.contains("class", ignoreCase = true) -> EduCoreIcons.Classes
    key.contains("subject", ignoreCase = true) || key.contains("curriculum", ignoreCase = true) -> EduCoreIcons.Subjects
    key.contains("attendance", ignoreCase = true) -> EduCoreIcons.Attendance
    key.contains("score", ignoreCase = true) || key.contains("result", ignoreCase = true) || key.contains("report", ignoreCase = true) -> EduCoreIcons.Scores
    key.contains("cbt", ignoreCase = true) || key.contains("exam", ignoreCase = true) -> EduCoreIcons.ExamDuties
    key.contains("lesson", ignoreCase = true) -> EduCoreIcons.LessonPlan
    key.contains("repository", ignoreCase = true) -> EduCoreIcons.Repository
    key.contains("fee", ignoreCase = true) || key.contains("payment", ignoreCase = true) || key.contains("payroll", ignoreCase = true) -> EduCoreIcons.Payments
    key.contains("message", ignoreCase = true) -> EduCoreIcons.Notices
    key.contains("notification", ignoreCase = true) || key.contains("announcement", ignoreCase = true) -> Icons.Default.Notifications
    key.contains("calendar", ignoreCase = true) -> EduCoreIcons.Calendar
    key.contains("setting", ignoreCase = true) -> EduCoreIcons.Settings
    key.contains("transport", ignoreCase = true) -> EduCoreIcons.Schedule
    else -> EduCoreIcons.Modules
}
