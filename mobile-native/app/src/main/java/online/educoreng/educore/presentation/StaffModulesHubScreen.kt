package online.educoreng.educore.presentation

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.GridItemSpan
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Person
import androidx.compose.material3.FloatingActionButton
import androidx.compose.material3.Icon
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableLongStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseTile
import online.educoreng.educore.core.designsystem.icon.EduCoreIcons
import online.educoreng.educore.core.designsystem.layout.EduCoreWindowWidth
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.ModuleDescriptor
import online.educoreng.educore.core.model.SessionSnapshot

/**
 * Grouped secondary-workspace hub for staff.
 *
 * RBAC rule: every tile originates from SessionSnapshot.modules. Root workflows
 * already represented by Home, Classes, Timetable and Inbox are intentionally
 * removed here so More does not become a second, confusing navigation system.
 *
 * CBT and the administrator staff directory are hosted directly inside this
 * native hub while the surrounding staff navigation is progressively migrated
 * to explicit feature routes. Neither permitted workspace falls back to a
 * browser or an "in progress" placeholder.
 */
@Composable
internal fun StaffModulesHubScreen(
    session: SessionSnapshot,
    width: EduCoreWindowWidth,
    onModuleClick: (ModuleDescriptor) -> Unit,
    onLogout: () -> Unit,
) {
    val cbtViewModel: StaffCbtViewModel = hiltViewModel()
    val cbtState by cbtViewModel.uiState.collectAsStateWithLifecycle()
    val staffDirectoryViewModel: StaffDirectoryViewModel = hiltViewModel()
    val staffDirectoryState by staffDirectoryViewModel.uiState.collectAsStateWithLifecycle()

    var cbtOpen by rememberSaveable { mutableStateOf(false) }
    var cbtCreating by rememberSaveable { mutableStateOf(false) }
    var cbtExamId by rememberSaveable { mutableLongStateOf(0L) }
    var staffDirectoryOpen by rememberSaveable { mutableStateOf(false) }

    LaunchedEffect(cbtState.createdExamId) {
        cbtState.createdExamId?.let { examId ->
            cbtCreating = false
            cbtExamId = examId
            cbtViewModel.consumeCreatedExam()
            cbtViewModel.openExam(examId)
        }
    }

    if (staffDirectoryOpen) {
        StaffDirectoryScreen(
            state = staffDirectoryState,
            currentUserId = session.user.id,
            onBack = { staffDirectoryOpen = false },
            onQuery = staffDirectoryViewModel::setQuery,
            onFilter = staffDirectoryViewModel::setFilter,
            onRefresh = staffDirectoryViewModel::load,
            onLoadMore = staffDirectoryViewModel::loadMore,
            onToggleActive = staffDirectoryViewModel::setActive,
        )
        return
    }

    if (cbtOpen) {
        when {
            cbtCreating -> {
                StaffCbtCreateScreen(
                    state = cbtState,
                    onBack = { cbtCreating = false },
                    onCreate = cbtViewModel::createExam,
                    onRetry = cbtViewModel::loadCreateOptions,
                )
            }
            cbtExamId > 0L -> {
                StaffCbtDetailScreen(
                    state = cbtState,
                    onBack = {
                        cbtExamId = 0L
                        cbtViewModel.load()
                    },
                    onPublish = cbtViewModel::publish,
                    onClose = cbtViewModel::close,
                    onReschedule = cbtViewModel::reschedule,
                    onRetry = { cbtViewModel.openExam(cbtExamId) },
                )
            }
            else -> {
                Box(Modifier.fillMaxSize()) {
                    StaffCbtListScreen(
                        state = cbtState,
                        onBack = { cbtOpen = false },
                        onQuery = cbtViewModel::setQuery,
                        onSearch = cbtViewModel::search,
                        onStatus = cbtViewModel::selectStatus,
                        onOpen = { examId ->
                            cbtExamId = examId
                            cbtViewModel.openExam(examId)
                        },
                        onRetry = cbtViewModel::load,
                    )
                    if (cbtState.capabilities.createExam) {
                        FloatingActionButton(
                            onClick = {
                                cbtCreating = true
                                cbtViewModel.loadCreateOptions()
                            },
                            modifier = Modifier
                                .align(Alignment.BottomEnd)
                                .padding(eduCoreScreenPadding()),
                            containerColor = EduCoreColors.Gold500,
                            contentColor = EduCoreColors.Navy900,
                        ) {
                            Icon(Icons.Default.Add, contentDescription = "Create examination")
                        }
                    }
                }
            }
        }
        return
    }

    val columns = when (width) {
        EduCoreWindowWidth.Compact -> 3
        EduCoreWindowWidth.Medium -> 4
        EduCoreWindowWidth.Expanded -> 6
    }
    val groups = remember(session.modules) {
        buildModuleHubGroups(session.modules)
    }

    fun openModule(module: ModuleDescriptor) {
        when (module.key.lowercase()) {
            "staff" -> {
                staffDirectoryOpen = true
                staffDirectoryViewModel.load()
            }
            in CBT_MODULE_KEYS -> {
                cbtExamId = 0L
                cbtCreating = false
                cbtOpen = true
                cbtViewModel.load()
            }
            else -> onModuleClick(module)
        }
    }

    LazyVerticalGrid(
        columns = GridCells.Fixed(columns),
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = androidx.compose.foundation.layout.PaddingValues(eduCoreScreenPadding()),
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
    ) {
        item(key = "hub-hero", span = { GridItemSpan(maxLineSpan) }) {
            EduCoreShowcaseHero(
                eyebrow = "MORE",
                title = "Secondary tools, kept out of your way.",
                subtitle = "Only additional workspaces granted to ${session.user.roleLabel} are shown here. Core teaching and communication tasks stay in the main navigation.",
            )
        }

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
                        label = moduleHubLabel(module),
                        icon = moduleHubIcon(module.key),
                        onClick = { openModule(module) },
                        modifier = Modifier.fillMaxWidth(),
                    )
                }
            }
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
        .filterNot { it.key.lowercase() in ROOT_WORKFLOW_KEYS }
        .sortedBy { it.title.lowercase() }

    // If both attendance aliases arrive, expose only the self-service entry.
    val deduped = normalized
        .groupBy { canonicalHubKey(it.key) }
        .mapNotNull { (_, candidates) ->
            candidates.firstOrNull { it.key.equals("staff-attendance.self", ignoreCase = true) }
                ?: candidates.firstOrNull()
        }

    fun group(keys: Set<String>) = deduped.filter { it.key.lowercase() in keys }
    val known = ACADEMIC_KEYS + OPERATION_KEYS + ACCOUNT_KEYS
    val other = deduped.filter { it.key.lowercase() !in known }

    return listOf(
        ModuleHubGroup(
            key = "academics",
            title = "Academics",
            supportingText = "Planning, resources, reporting and examinations",
            modules = group(ACADEMIC_KEYS),
        ),
        ModuleHubGroup(
            key = "operations",
            title = "Operations",
            supportingText = "Your attendance and school support services",
            modules = group(OPERATION_KEYS) + other,
        ),
        ModuleHubGroup(
            key = "account",
            title = "Account",
            supportingText = "Profile and account tools granted to your role",
            modules = group(ACCOUNT_KEYS),
        ),
    )
}

private fun canonicalHubKey(key: String): String = when (key.lowercase()) {
    "staff-attendance.self" -> "staff-attendance"
    else -> key.lowercase()
}

/** Compact labels are intentional: phones use three concise tiles per row. */
private fun moduleHubLabel(module: ModuleDescriptor): String = when (module.key.lowercase()) {
    "staff" -> "Staff Directory"
    "staff-attendance", "staff-attendance.self" -> "My Attendance"
    "academic-repository" -> "Repository"
    "lesson-planner" -> "Lesson Planner"
    "reports", "report-cards", "results" -> "Report Cards"
    "cbt", "cbt-exams", "examinations" -> "Examinations"
    "academic-cycle" -> "Sessions"
    "fees" -> "Fees & Payments"
    else -> module.title
}

/** Workspaces already represented by the task-oriented root navigation. */
private val ROOT_WORKFLOW_KEYS = setOf(
    "dashboard",
    "classes",
    "students",
    "attendance",
    "scores",
    "scores.entry",
    "timetable",
    "student.timetable",
    "messages",
    "notifications.view",
    "announcements",
    "calendar.view",
)

private val CBT_MODULE_KEYS = setOf("cbt", "cbt-exams", "examinations")

private val ACADEMIC_KEYS = setOf(
    "subjects",
    "curriculum",
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

private val ACCOUNT_KEYS = setOf(
    "profile",
    "settings",
)

private fun moduleHubIcon(key: String): ImageVector = when {
    key.equals("profile", ignoreCase = true) -> Icons.Default.Person
    key.equals("staff", ignoreCase = true) -> EduCoreIcons.Students
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
