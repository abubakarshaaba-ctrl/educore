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
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableLongStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
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

@Composable
internal fun StaffModulesHubScreen(
    session: SessionSnapshot,
    width: EduCoreWindowWidth,
    onModuleClick: (ModuleDescriptor) -> Unit,
    onLogout: () -> Unit,
) {
    val staffDirectoryViewModel: StaffDirectoryViewModel = hiltViewModel()
    val staffDirectoryState by staffDirectoryViewModel.uiState.collectAsStateWithLifecycle()
    val adminStudentViewModel: AdminStudentDirectoryViewModel = hiltViewModel()
    val adminStudentState by adminStudentViewModel.uiState.collectAsStateWithLifecycle()
    val adminAttendanceViewModel: AdminStaffAttendanceViewModel = hiltViewModel()
    val adminAttendanceState by adminAttendanceViewModel.uiState.collectAsStateWithLifecycle()
    val exportsViewModel: ExportsViewModel = hiltViewModel()
    val exportsState by exportsViewModel.uiState.collectAsStateWithLifecycle()
    val riskViewModel: RiskViewModel = hiltViewModel()
    val riskState by riskViewModel.uiState.collectAsStateWithLifecycle()
    val transfersViewModel: TransfersViewModel = hiltViewModel()
    val transfersState by transfersViewModel.uiState.collectAsStateWithLifecycle()
    val gradebookViewModel: GradebookViewModel = hiltViewModel()
    val gradebookState by gradebookViewModel.uiState.collectAsStateWithLifecycle()
    val reportsViewModel: ReportsViewModel = hiltViewModel()
    val reportsState by reportsViewModel.uiState.collectAsStateWithLifecycle()
    val portalAccountsViewModel: PortalAccountsViewModel = hiltViewModel()
    val portalAccountsState by portalAccountsViewModel.uiState.collectAsStateWithLifecycle()
    val schoolSettingsViewModel: SchoolSettingsViewModel = hiltViewModel()
    val schoolSettingsState by schoolSettingsViewModel.uiState.collectAsStateWithLifecycle()
    val skillsViewModel: SkillsViewModel = hiltViewModel()
    val skillsState by skillsViewModel.uiState.collectAsStateWithLifecycle()

    var staffDirectoryOpen by rememberSaveable { mutableStateOf(false) }
    var studentDirectoryOpen by rememberSaveable { mutableStateOf(false) }
    var adminAttendanceOpen by rememberSaveable { mutableStateOf(false) }
    var exportsOpen by rememberSaveable { mutableStateOf(false) }
    var riskOpen by rememberSaveable { mutableStateOf(false) }
    var riskConfigOpen by rememberSaveable { mutableStateOf(false) }
    var riskFlagId by rememberSaveable { mutableLongStateOf(0L) }
    var transfersOpen by rememberSaveable { mutableStateOf(false) }
    var gradebookOpen by rememberSaveable { mutableStateOf(false) }
    var reportsOpen by rememberSaveable { mutableStateOf(false) }
    var profileOpen by rememberSaveable { mutableStateOf(false) }
    var portalAccountsOpen by rememberSaveable { mutableStateOf(false) }
    var schoolSettingsOpen by rememberSaveable { mutableStateOf(false) }
    var skillsOpen by rememberSaveable { mutableStateOf(false) }

    if (profileOpen) {
        ProfileScreen(session = session, onBack = { profileOpen = false })
        return
    }

    if (portalAccountsOpen) {
        PortalAccountsScreen(
            state = portalAccountsState,
            onBack = { portalAccountsOpen = false },
            onTab = portalAccountsViewModel::setTab,
            onQuery = portalAccountsViewModel::setQuery,
            onCreateStudent = portalAccountsViewModel::createStudent,
            onCreateParent = portalAccountsViewModel::createParent,
            onResetPassword = portalAccountsViewModel::resetPassword,
            onToggle = portalAccountsViewModel::requestToggle,
            onBulkStudents = portalAccountsViewModel::requestBulkStudents,
            onEmail = portalAccountsViewModel::setEmail,
            onPassword = portalAccountsViewModel::setPassword,
            onSaveEditor = portalAccountsViewModel::saveEditor,
            onCloseEditor = portalAccountsViewModel::closeEditor,
            onConfirm = portalAccountsViewModel::confirmPendingAction,
            onCancelConfirm = portalAccountsViewModel::cancelConfirmation,
            onRetry = portalAccountsViewModel::load,
        )
        return
    }

    if (schoolSettingsOpen) {
        SchoolSettingsScreen(
            state = schoolSettingsState,
            onBack = { schoolSettingsOpen = false },
            onName = schoolSettingsViewModel::setName,
            onMotto = schoolSettingsViewModel::setMotto,
            onAddress = schoolSettingsViewModel::setAddress,
            onPhone = schoolSettingsViewModel::setPhone,
            onEmail = schoolSettingsViewModel::setEmail,
            onWebsite = schoolSettingsViewModel::setWebsite,
            onEstablishedYear = schoolSettingsViewModel::setEstablishedYear,
            onProprietor = schoolSettingsViewModel::setProprietor,
            onSlogan = schoolSettingsViewModel::setSlogan,
            onSave = schoolSettingsViewModel::save,
            onRetry = schoolSettingsViewModel::load,
        )
        return
    }

    if (skillsOpen) {
        SkillsScreen(
            state = skillsState,
            onBack = {
                if (skillsState.inSheet) skillsViewModel.requestCloseSheet() else skillsOpen = false
            },
            onClass = skillsViewModel::selectClass,
            onTerm = skillsViewModel::selectTerm,
            onOpenSheet = skillsViewModel::openSheet,
            onCategory = skillsViewModel::setCategory,
            onPreviousStudent = skillsViewModel::previousStudent,
            onNextStudent = skillsViewModel::nextStudent,
            onRate = skillsViewModel::rate,
            onSave = skillsViewModel::save,
            onRetry = skillsViewModel::load,
            onCancelDiscard = skillsViewModel::cancelDiscard,
            onConfirmDiscard = {
                skillsViewModel.confirmDiscard()
                if (!skillsState.inSheet) skillsOpen = false
            },
        )
        return
    }

    if (reportsOpen) {
        ReportsScreen(
            state = reportsState,
            onBack = { reportsOpen = false },
            onClass = reportsViewModel::selectClass,
            onTerm = reportsViewModel::selectTerm,
            onNote = reportsViewModel::updatePublicationNote,
            onCompute = reportsViewModel::requestCompute,
            onPublish = reportsViewModel::requestPublish,
            onUnpublish = reportsViewModel::requestUnpublish,
            onConfirm = reportsViewModel::confirmManagementAction,
            onDismissConfirmation = reportsViewModel::dismissConfirmation,
            onDownload = reportsViewModel::downloadPdf,
            onDocumentOpened = reportsViewModel::consumeDocument,
            onRetry = reportsViewModel::load,
        )
        return
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
            riskConfigOpen -> RiskConfigScreen(
                state = riskState,
                onBack = { riskConfigOpen = false },
                onValue = riskViewModel::updateConfig,
                onFeeRisk = riskViewModel::setIncludeFeeRisk,
                onSave = riskViewModel::saveConfig,
                onReset = riskViewModel::resetConfigDraft,
            )
            riskFlagId > 0L -> RiskDetailScreen(
                state = riskState,
                onBack = { riskFlagId = 0L; riskViewModel.clearDetail() },
                onNote = riskViewModel::setInterventionNote,
                onAcknowledge = riskViewModel::acknowledge,
                onResolve = riskViewModel::resolve,
                onRetry = { riskViewModel.openFlag(riskFlagId) },
            )
            else -> RiskDashboardScreen(
                state = riskState,
                onBack = { riskOpen = false },
                onTerm = riskViewModel::selectTerm,
                onStatus = riskViewModel::selectStatus,
                onRiskLevel = riskViewModel::selectRiskLevel,
                onOpen = { flagId -> riskFlagId = flagId; riskViewModel.openFlag(flagId) },
                onLoadMore = riskViewModel::loadMore,
                onCompute = riskViewModel::compute,
                onConfig = { riskViewModel.resetConfigDraft(); riskConfigOpen = true },
                onRetry = riskViewModel::load,
            )
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

    if (adminAttendanceOpen) {
        AdminStaffAttendanceScreen(
            state = adminAttendanceState,
            onBack = { adminAttendanceOpen = false },
            onRefresh = adminAttendanceViewModel::load,
        )
        return
    }

    if (studentDirectoryOpen) {
        AdminStudentDirectoryScreen(
            state = adminStudentState,
            onBack = { studentDirectoryOpen = false },
            onRefresh = adminStudentViewModel::load,
        )
        return
    }

    if (staffDirectoryOpen) {
        StaffDirectoryScreen(
            state = staffDirectoryState,
            onBack = { staffDirectoryOpen = false },
            onQuery = staffDirectoryViewModel::setQuery,
            onFilter = staffDirectoryViewModel::setFilter,
            onRefresh = staffDirectoryViewModel::load,
            onLoadMore = staffDirectoryViewModel::loadMore,
            onToggleActive = staffDirectoryViewModel::setActive,
            onStartCreate = staffDirectoryViewModel::startCreate,
            onCloseCreate = staffDirectoryViewModel::closeCreate,
            onCreateName = staffDirectoryViewModel::setCreateName,
            onCreateEmail = staffDirectoryViewModel::setCreateEmail,
            onCreatePhone = staffDirectoryViewModel::setCreatePhone,
            onCreateRole = staffDirectoryViewModel::setCreateRole,
            onCreatePassword = staffDirectoryViewModel::setCreatePassword,
            onCreate = staffDirectoryViewModel::createStaff,
        )
        return
    }

    val columns = when (width) {
        EduCoreWindowWidth.Compact -> 3
        EduCoreWindowWidth.Medium -> 4
        EduCoreWindowWidth.Expanded -> 6
    }
    val visibleModules = remember(session) { ShellNavigationPolicy.visibleModules(session) }
    val groups = remember(visibleModules, session.user.portal) {
        buildModuleHubGroups(visibleModules, session.user.portal)
    }

    fun openModule(module: ModuleDescriptor) {
        when (module.key.lowercase()) {
            "profile" -> profileOpen = true
            "portal-accounts" -> { portalAccountsOpen = true; portalAccountsViewModel.load() }
            "settings" -> { schoolSettingsOpen = true; schoolSettingsViewModel.load() }
            "skills" -> { skillsOpen = true; skillsViewModel.load() }
            "staff" -> { staffDirectoryOpen = true; staffDirectoryViewModel.load() }
            "students" -> if (session.user.portal == "admin") {
                studentDirectoryOpen = true
                adminStudentViewModel.load()
            } else onModuleClick(module)
            "staff-attendance.admin" -> {
                adminAttendanceOpen = true
                adminAttendanceViewModel.load()
            }
            "staff-attendance.self" -> onModuleClick(module)
            "gradebook" -> { gradebookOpen = true; gradebookViewModel.load() }
            "reports", "report-cards" -> { reportsOpen = true; reportsViewModel.load() }
            "transfers" -> { transfersOpen = true; transfersViewModel.load() }
            "exports" -> { exportsOpen = true; exportsViewModel.load() }
            "risk" -> {
                riskFlagId = 0L
                riskConfigOpen = false
                riskOpen = true
                riskViewModel.clearDetail()
                riskViewModel.load()
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
        if (groups.all { it.modules.isEmpty() }) {
            item(key = "hub-empty", span = { GridItemSpan(maxLineSpan) }) {
                EduCoreEmptyState("No additional modules", "No additional tools are available.")
            }
        } else {
            groups.filter { it.modules.isNotEmpty() }.forEach { group ->
                item(key = "hub-heading-${group.key}", span = { GridItemSpan(maxLineSpan) }) {
                    EduCoreSectionHeader(group.title)
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
    val modules: List<ModuleDescriptor>,
)

private fun buildModuleHubGroups(modules: List<ModuleDescriptor>, portal: String): List<ModuleHubGroup> {
    val normalized = modules
        .filterNot { module -> module.key.lowercase() in rootWorkflowKeysFor(portal) }
        .sortedBy { it.title.lowercase() }
    val deduped = normalized
        .groupBy { module ->
            val key = module.key.lowercase()
            if (portal == "admin" && key in ADMIN_DISTINCT_ATTENDANCE_KEYS) key else canonicalHubKey(key)
        }
        .mapNotNull { (_, candidates) ->
            if (portal == "admin") {
                candidates.firstOrNull()
            } else {
                candidates.firstOrNull { it.key.equals("staff-attendance.self", ignoreCase = true) }
                    ?: candidates.firstOrNull()
            }
        }
    fun group(keys: Set<String>) = deduped.filter { it.key.lowercase() in keys }
    val known = ACADEMIC_KEYS + OPERATION_KEYS + ACCOUNT_KEYS
    val other = deduped.filter { it.key.lowercase() !in known }
    val operations = (group(OPERATION_KEYS) + other).distinctBy { it.key.lowercase() }
    return listOf(
        ModuleHubGroup("academics", "Academics", group(ACADEMIC_KEYS)),
        ModuleHubGroup("operations", "Operations", operations),
        ModuleHubGroup("account", "Account", group(ACCOUNT_KEYS)),
    )
}

private fun rootWorkflowKeysFor(portal: String): Set<String> =
    if (portal == "admin") ROOT_WORKFLOW_KEYS - setOf("students") else ROOT_WORKFLOW_KEYS

private fun canonicalHubKey(key: String): String = when (key.lowercase()) {
    "staff-attendance.self" -> "staff-attendance"
    else -> key.lowercase()
}

private fun moduleHubLabel(module: ModuleDescriptor): String = when (module.key.lowercase()) {
    "staff" -> "Staff Directory"
    "students" -> "Student Directory"
    "staff-attendance.admin" -> "Staff Attendance"
    "staff-attendance.self" -> "My Attendance"
    "academic-repository" -> "Repository"
    "lesson-planner" -> "Lesson Planner"
    "gradebook" -> "Gradebook"
    "reports", "report-cards", "results" -> "Report Cards"
    "academic-cycle" -> "Sessions"
    "fees" -> "Fees"
    "transfers" -> "Transfers"
    "portal-accounts" -> "Portal Accounts"
    "analytics" -> "Analytics"
    "risk" -> "Risk Flags"
    "exports" -> "Exports"
    else -> module.title
}

private val ROOT_WORKFLOW_KEYS = setOf(
    "dashboard", "classes", "students", "attendance", "scores", "scores.entry", "timetable",
    "student.timetable", "messages", "notifications.view", "announcements", "calendar.view",
)
private val ADMIN_DISTINCT_ATTENDANCE_KEYS = setOf("staff-attendance.admin", "staff-attendance.self")
private val ACADEMIC_KEYS = setOf(
    "subjects", "curriculum", "reports", "report-cards", "results", "gradebook",
    "lesson-planner", "academic-repository", "library", "skills",
)
private val OPERATION_KEYS = setOf(
    "staff", "students", "staff-attendance.admin", "staff-attendance.self", "academic-cycle", "fees", "expenses", "payroll",
    "admissions", "transfers", "transport", "health", "inventory", "hostels", "analytics", "risk", "exports",
)
private val ACCOUNT_KEYS = setOf("profile", "portal-accounts", "settings")

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
    key.contains("timetable", ignoreCase = true) || key.contains("exam-dut", ignoreCase = true) || key.contains("supervision", ignoreCase = true) -> EduCoreIcons.ExamDuties
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
