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
 * CBT, staff directory, transfers, gradebook/remarks, risk intelligence and
 * data exports are hosted directly inside this native hub. These workspaces
 * never fall back to a browser.
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
    val exportsViewModel: ExportsViewModel = hiltViewModel()
    val exportsState by exportsViewModel.uiState.collectAsStateWithLifecycle()
    val riskViewModel: RiskViewModel = hiltViewModel()
    val riskState by riskViewModel.uiState.collectAsStateWithLifecycle()
    val transfersViewModel: TransfersViewModel = hiltViewModel()
    val transfersState by transfersViewModel.uiState.collectAsStateWithLifecycle()
    val gradebookViewModel: GradebookViewModel = hiltViewModel()
    val gradebookState by gradebookViewModel.uiState.collectAsStateWithLifecycle()

    var cbtOpen by rememberSaveable { mutableStateOf(false) }
    var cbtCreating by rememberSaveable { mutableStateOf(false) }
    var cbtExamId by rememberSaveable { mutableLongStateOf(0L) }
    var staffDirectoryOpen by rememberSaveable { mutableStateOf(false) }
    var exportsOpen by rememberSaveable { mutableStateOf(false) }
    var riskOpen by rememberSaveable { mutableStateOf(false) }
    var riskConfigOpen by rememberSaveable { mutableStateOf(false) }
    var riskFlagId by rememberSaveable { mutableLongStateOf(0L) }
    var transfersOpen by rememberSaveable { mutableStateOf(false) }
    var gradebookOpen by rememberSaveable { mutableStateOf(false) }

    LaunchedEffect(cbtState.createdExamId) {
        cbtState.createdExamId?.let { examId ->
            cbtCreating = false
            cbtExamId = examId
            cbtViewModel.consumeCreatedExam()
            cbtViewModel.openExam(examId)
        }
    }

    if (gradebookOpen) {
        GradebookScreen(
            state = gradebookState,
            onBack = { gradebookOpen = false },
            onClass = gradebookViewModel::selectClass,
            onTerm = gradebookViewModel::selectTerm,
            onEditFormRemark = gradebookViewModel::editFormTutorRemark,
            onEditPrincipalRemark = gradebookViewModel::editPrincipalRemark,
            onRemarkDraft = gradebookViewModel::updateRemarkDraft,
            onSaveRemark = gradebookViewModel::saveRemark,
            onDismissEditor = gradebookViewModel::dismissEditor,
            onRetry = gradebookViewModel::load,
        )
        return
    }

    if (transfersOpen) {
        TransfersScreen(
            state = transfersState,
            onBack = { transfersOpen = false },
            onTab = transfersViewModel::selectTab,
            onStudent = transfersViewModel::selectStudent,
            onDestination = transfersViewModel::selectDestination,
            onReason = transfersViewModel::updateReason,
            onRequest = transfersViewModel::requestTransfer,
            onApprove = transfersViewModel::requestApproval,
            onReject = transfersViewModel::requestRejection,
            onClassStudent = transfersViewModel::selectClassStudent,
            onClassDestination = transfersViewModel::selectClassDestination,
            onClassDate = transfersViewModel::updateClassEffectiveDate,
            onClassReason = transfersViewModel::updateClassReason,
            onClassRequest = transfersViewModel::requestClassTransfer,
            onClassApprove = transfersViewModel::requestClassApproval,
            onClassReject = transfersViewModel::requestClassRejection,
            onClassCancel = transfersViewModel::requestClassCancellation,
            onActionReason = transfersViewModel::updateActionReason,
            onConfirm = transfersViewModel::confirmAction,
            onCancelConfirm = transfersViewModel::cancelConfirmation,
            onRetry = transfersViewModel::load,
        )
        return
    }

    if (riskOpen) {
        when {
            riskConfigOpen -> {
                RiskConfigScreen(
                    state = riskState,
                    onBack = { riskConfigOpen = false },
                    onValue = riskViewModel::updateConfig,
                    onFeeRisk = riskViewModel::setIncludeFeeRisk,
                    onSave = riskViewModel::saveConfig,
                    onReset = riskViewModel::resetConfigDraft,
                )
            }
            riskFlagId > 0L -> {
                RiskDetailScreen(
                    state = riskState,
                    onBack = {
                        riskFlagId = 0L
                        riskViewModel.clearDetail()
                    },
                    onNote = riskViewModel::setInterventionNote,
                    onAcknowledge = riskViewModel::acknowledge,
                    onResolve = riskViewModel::resolve,
                    onRetry = { riskViewModel.openFlag(riskFlagId) },
                )
            }
            else -> {
                RiskDashboardScreen(
                    state = riskState,
                    onBack = { riskOpen = false },
                    onTerm = riskViewModel::selectTerm,
                    onStatus = riskViewModel::selectStatus,
                    onRiskLevel = riskViewModel::selectRiskLevel,
                    onOpen = { flagId ->
                        riskFlagId = flagId
                        riskViewModel.openFlag(flagId)
                    },
                    onLoadMore = riskViewModel::loadMore,
                    onCompute = riskViewModel::compute,
                    onConfig = {
                        riskViewModel.resetConfigDraft()
                        riskConfigOpen = true
                    },
                    onRetry = riskViewModel::load,
                )
            }
        }
        return
    }

    if (exportsOpen) {
        ExportsScreen(
            state = exportsState,
            onBack = { exportsOpen = false },
            onType = exportsViewModel::selectType,
            onClass = exportsViewModel::selectClass,
            onTerm = exportsViewModel::selectTerm,
            onSession = exportsViewModel::selectSession,
            onDownload = exportsViewModel::download,
            onRetry = exportsViewModel::load,
            onDocumentOpened = exportsViewModel::consumeDocument,
        )
        return
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
    val groups = remember(session.modules) { buildModuleHubGroups(session.modules) }

    fun openModule(module: ModuleDescriptor) {
        when (module.key.lowercase()) {
            "staff" -> {
                staffDirectoryOpen = true
                staffDirectoryViewModel.load()
            }
            "gradebook" -> {
                gradebookOpen = true
                gradebookViewModel.load()
            }
            "transfers" -> {
                transfersOpen = true
                transfersViewModel.load()
            }
            "exports" -> {
                exportsOpen = true
                exportsViewModel.load()
            }
            "risk" -> {
                riskFlagId = 0L
                riskConfigOpen = false
                riskOpen = true
                riskViewModel.clearDetail()
                riskViewModel.load()
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
                leadingIcon = { Icon(Icons.AutoMirrored.Filled.Logout, contentDescription = null) },
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
            supportingText = "Attendance, transfers, risk intelligence and school support services",
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

private fun moduleHubLabel(module: ModuleDescriptor): String = when (module.key.lowercase()) {
    "staff" -> "Staff Directory"
    "staff-attendance", "staff-attendance.self" -> "My Attendance"
    "academic-repository" -> "Repository"
    "lesson-planner" -> "Lesson Planner"
    "gradebook" -> "Gradebook & Remarks"
    "reports", "report-cards", "results" -> "Report Cards"
    "cbt", "cbt-exams", "examinations" -> "Examinations"
    "academic-cycle" -> "Sessions"
    "fees" -> "Fees & Payments"
    "transfers" -> "Student Transfers"
    "analytics" -> "Analytics"
    "risk" -> "Risk Flags"
    "exports" -> "Exports"
    else -> module.title
}

private val ROOT_WORKFLOW_KEYS = setOf(
    "dashboard", "classes", "students", "attendance", "scores", "scores.entry", "timetable",
    "student.timetable", "messages", "notifications.view", "announcements", "calendar.view",
)

private val CBT_MODULE_KEYS = setOf("cbt", "cbt-exams", "examinations")

private val ACADEMIC_KEYS = setOf(
    "subjects", "curriculum", "reports", "report-cards", "results", "gradebook", "cbt", "cbt-exams",
    "examinations", "lesson-planner", "academic-repository", "library",
)

private val OPERATION_KEYS = setOf(
    "staff", "staff-attendance", "staff-attendance.self", "academic-cycle", "fees", "expenses",
    "payroll", "admissions", "transfers", "transport", "health", "inventory", "hostels",
    "analytics", "risk", "exports",
)

private val ACCOUNT_KEYS = setOf("profile", "settings")

private fun moduleHubIcon(key: String): ImageVector = when {
    key.equals("profile", ignoreCase = true) -> Icons.Default.Person
    key.equals("staff", ignoreCase = true) -> EduCoreIcons.Students
    key.contains("transfer", ignoreCase = true) -> EduCoreIcons.Students
    key.contains("student", ignoreCase = true) -> EduCoreIcons.Students
    key.contains("class", ignoreCase = true) -> EduCoreIcons.Classes
    key.contains("subject", ignoreCase = true) || key.contains("curriculum", ignoreCase = true) -> EduCoreIcons.Subjects
    key.contains("attendance", ignoreCase = true) -> EduCoreIcons.Attendance
    key.contains("risk", ignoreCase = true) -> EduCoreIcons.Notices
    key.contains("gradebook", ignoreCase = true) || key.contains("score", ignoreCase = true) || key.contains("result", ignoreCase = true) || key.contains("report", ignoreCase = true) -> EduCoreIcons.Scores
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
