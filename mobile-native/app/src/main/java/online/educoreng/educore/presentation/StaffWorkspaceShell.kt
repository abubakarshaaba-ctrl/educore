package online.educoreng.educore.presentation

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.GridItemSpan
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.MoreHoriz
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material.icons.filled.School
import androidx.compose.material3.Badge
import androidx.compose.material3.BadgedBox
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.NavigationRail
import androidx.compose.material3.NavigationRailItem
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import kotlinx.coroutines.launch
import online.educoreng.educore.core.designsystem.component.EduCoreBottomNavigation
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreModuleCard
import online.educoreng.educore.core.designsystem.component.EduCoreNavigationItem
import online.educoreng.educore.core.designsystem.component.EduCoreOfflineBanner
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreProfileHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreTopAppBar
import online.educoreng.educore.core.designsystem.icon.EduCoreIcons
import online.educoreng.educore.core.designsystem.layout.EduCoreAdaptiveLayout
import online.educoreng.educore.core.designsystem.layout.EduCoreWindowWidth
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.ModuleDescriptor
import online.educoreng.educore.core.model.SessionSnapshot

/**
 * Task-oriented staff shell for the EduCore mobile overhaul.
 *
 * No route in this shell falls back to an external browser. Staff workflows
 * either open a native screen or show an in-app "being completed" message.
 * Root navigation is also RBAC-aware: only server-granted workspaces are
 * surfaced, while Home and More remain universally available to staff.
 */
@Composable
internal fun StaffWorkspaceShell(
    session: SessionSnapshot,
    dashboard: DashboardUiState,
    online: Boolean,
    busy: Boolean,
    snackbarHostState: SnackbarHostState,
    onRefresh: () -> Unit,
    onRefreshDashboard: () -> Unit,
    onLogout: () -> Unit,
) {
    val navController = rememberNavController()
    val classesViewModel: ClassesViewModel = hiltViewModel()
    val classesState by classesViewModel.uiState.collectAsStateWithLifecycle()
    val scoresViewModel: ScoresViewModel = hiltViewModel()
    val scoresState by scoresViewModel.uiState.collectAsStateWithLifecycle()
    val scheduleViewModel: ScheduleViewModel = hiltViewModel()
    val scheduleState by scheduleViewModel.uiState.collectAsStateWithLifecycle()
    val academicViewModel: AcademicContentViewModel = hiltViewModel()
    val academicState by academicViewModel.uiState.collectAsStateWithLifecycle()
    val operationsViewModel: OperationsViewModel = hiltViewModel()
    val operationsState by operationsViewModel.uiState.collectAsStateWithLifecycle()
    val communicationViewModel: CommunicationViewModel = hiltViewModel()
    val communicationState by communicationViewModel.uiState.collectAsStateWithLifecycle()
    val skillsViewModel: SkillsViewModel = hiltViewModel()
    val skillsState by skillsViewModel.uiState.collectAsStateWithLifecycle()
    val adminAttendanceViewModel: AdminStaffAttendanceViewModel = hiltViewModel()
    val adminAttendanceState by adminAttendanceViewModel.uiState.collectAsStateWithLifecycle()
    val scope = rememberCoroutineScope()

    val backStackEntry by navController.currentBackStackEntryAsState()
    val route = backStackEntry?.destination?.route ?: StaffTab.HOME.route
    val availableTabs = remember(session.modules) { availableStaffTabs(session.modules) }
    val selectedRoot = rootFor(route).takeIf { it in availableTabs } ?: StaffTab.HOME

    LaunchedEffect(classesState.message) {
        classesState.message?.let {
            snackbarHostState.showSnackbar(it)
            classesViewModel.consumeMessage()
        }
    }
    LaunchedEffect(scoresState.message) {
        scoresState.message?.let {
            snackbarHostState.showSnackbar(it)
            scoresViewModel.consumeMessage()
        }
    }
    LaunchedEffect(academicState.message) {
        academicState.message?.let {
            snackbarHostState.showSnackbar(it)
            academicViewModel.consumeMessage()
        }
    }
    LaunchedEffect(communicationState.message) {
        communicationState.message?.let {
            snackbarHostState.showSnackbar(it)
            communicationViewModel.consumeMessage()
        }
    }
    LaunchedEffect(adminAttendanceState.message) {
        adminAttendanceState.message?.let {
            snackbarHostState.showSnackbar(it)
            adminAttendanceViewModel.consumeMessage()
        }
    }

    val navigationItems = availableTabs.map { tab ->
        EduCoreNavigationItem(
            key = tab.route,
            label = tab.label,
            icon = tab.icon,
            badgeCount = if (tab == StaffTab.INBOX) {
                communicationState.unreadNotifications + (communicationState.messagePage?.unreadCount ?: 0)
            } else 0,
        )
    }

    fun navigateRoot(tab: StaffTab) {
        if (tab !in availableTabs) return
        navController.navigate(tab.route) {
            popUpTo(StaffTab.HOME.route) { saveState = true }
            launchSingleTop = true
            restoreState = true
        }
    }

    fun nativePending(module: ModuleDescriptor) {
        scope.launch {
            snackbarHostState.showSnackbar(
                "${module.title} is being completed as a native EduCore workspace. It will not open in the browser."
            )
        }
    }

    val openModule: (ModuleDescriptor) -> Unit = { module ->
        when (module.key.lowercase()) {
            "classes", "students", "attendance" -> {
                classesViewModel.loadClasses()
                navigateRoot(StaffTab.CLASSES)
            }
            "staff-attendance" -> {
                if (session.user.portal.equals("admin", ignoreCase = true)) {
                    adminAttendanceViewModel.loadDaily()
                    navController.navigate(StaffRoutes.ADMIN_STAFF_ATTENDANCE)
                } else {
                    classesViewModel.loadStaffAttendance()
                    navController.navigate(StaffRoutes.STAFF_ATTENDANCE)
                }
            }
            "staff-attendance.self" -> {
                classesViewModel.loadStaffAttendance()
                navController.navigate(StaffRoutes.STAFF_ATTENDANCE)
            }
            "scores", "scores.entry" -> {
                scoresViewModel.loadAssignments()
                navController.navigate(StaffRoutes.SCORES)
            }
            "timetable" -> {
                scheduleViewModel.load()
                navigateRoot(StaffTab.TIMETABLE)
            }
            "reports" -> {
                operationsViewModel.load(module.key)
                navController.navigate("staff/operations/${module.key}")
            }
            "report-cards", "results" -> nativePending(module)
            "skills" -> {
                skillsViewModel.load()
                navController.navigate(StaffRoutes.SKILLS)
            }
            "lesson-planner" -> {
                academicViewModel.loadLessons()
                navController.navigate(StaffRoutes.LESSON_PLANS)
            }
            "academic-repository" -> {
                academicViewModel.loadRepository()
                navController.navigate(StaffRoutes.REPOSITORY)
            }
            "profile" -> navController.navigate(StaffRoutes.PROFILE)
            "messages" -> {
                communicationViewModel.selectTab(CommunicationTab.MESSAGES.ordinal)
                communicationViewModel.loadAll()
                navigateRoot(StaffTab.INBOX)
            }
            "notifications.view", "announcements" -> {
                communicationViewModel.selectTab(CommunicationTab.NOTICES.ordinal)
                communicationViewModel.loadAll()
                navigateRoot(StaffTab.INBOX)
            }
            "calendar.view" -> {
                communicationViewModel.selectTab(CommunicationTab.EVENTS.ordinal)
                communicationViewModel.loadAll()
                navigateRoot(StaffTab.INBOX)
            }
            in StaffRoutes.GENERIC_OPERATIONS -> {
                operationsViewModel.load(module.key)
                navController.navigate("staff/operations/${module.key}")
            }
            "cbt", "cbt-exams", "examinations" -> nativePending(module)
            else -> when (ModulePresentationPolicy.presentationFor(module.key)) {
                ModulePresentation.NATIVE_GENERIC -> {
                    operationsViewModel.load(module.key)
                    navController.navigate("staff/operations/${module.key}")
                }
                else -> nativePending(module)
            }
        }
    }

    EduCoreAdaptiveLayout(Modifier.fillMaxSize()) { width ->
        val rootTopBar = route in setOf(StaffTab.HOME.route, StaffTab.CLASSES.route, StaffTab.MORE.route)
        Scaffold(
            containerColor = EduCoreColors.Page50,
            topBar = {
                if (rootTopBar) {
                    Column {
                        EduCoreTopAppBar(
                            title = when (route) {
                                StaffTab.HOME.route -> "Home"
                                StaffTab.CLASSES.route -> "Classes"
                                else -> "More"
                            },
                            subtitle = listOfNotNull(session.school.name, session.academicPeriod.termName).joinToString(" · "),
                            actions = {
                                IconButton(onClick = onRefresh, enabled = online && !busy) {
                                    Icon(Icons.Default.Refresh, contentDescription = "Refresh")
                                }
                            },
                        )
                        if (!online) EduCoreOfflineBanner()
                    }
                } else if (!online) {
                    EduCoreOfflineBanner()
                }
            },
            bottomBar = {
                if (width == EduCoreWindowWidth.Compact) {
                    EduCoreBottomNavigation(
                        items = navigationItems,
                        selectedKey = selectedRoot.route,
                        onSelect = { item -> availableTabs.firstOrNull { it.route == item.key }?.let(::navigateRoot) },
                    )
                }
            },
        ) { padding ->
            Row(Modifier.fillMaxSize().padding(padding)) {
                if (width != EduCoreWindowWidth.Compact) {
                    NavigationRail(containerColor = EduCoreColors.White) {
                        availableTabs.forEach { tab ->
                            val unread = if (tab == StaffTab.INBOX) {
                                communicationState.unreadNotifications + (communicationState.messagePage?.unreadCount ?: 0)
                            } else 0
                            NavigationRailItem(
                                selected = selectedRoot == tab,
                                onClick = { navigateRoot(tab) },
                                icon = {
                                    BadgedBox(
                                        badge = {
                                            if (unread > 0) Badge {
                                                Text(unread.coerceAtMost(99).toString() + if (unread > 99) "+" else "")
                                            }
                                        },
                                    ) { Icon(tab.icon, contentDescription = tab.label) }
                                },
                                label = { Text(tab.label, maxLines = 1) },
                            )
                        }
                    }
                }

                Box(Modifier.fillMaxSize(), contentAlignment = Alignment.TopCenter) {
                    NavHost(
                        navController = navController,
                        startDestination = StaffTab.HOME.route,
                        modifier = Modifier.fillMaxSize().widthIn(max = 1180.dp),
                    ) {
                        composable(StaffTab.HOME.route) {
                            StaffDashboardRoot(session, dashboard, width, openModule, onRefreshDashboard)
                        }
                        composable(StaffTab.CLASSES.route) {
                            LaunchedEffect(Unit) { classesViewModel.loadClasses() }
                            ShowcaseClassesListScreen(
                                state = classesState,
                                width = width,
                                onSearch = classesViewModel::setClassSearch,
                                onOpenClass = { classId ->
                                    classesViewModel.openClass(classId)
                                    navController.navigate("staff/classes/$classId")
                                },
                                onRetry = classesViewModel::loadClasses,
                            )
                        }
                        composable(StaffTab.TIMETABLE.route) {
                            LaunchedEffect(Unit) { scheduleViewModel.load() }
                            ScheduleScreen(
                                state = scheduleState,
                                onBack = { navigateRoot(StaffTab.HOME) },
                                onSection = scheduleViewModel::selectSection,
                                onDay = scheduleViewModel::selectDay,
                                onRetry = { scheduleViewModel.load() },
                            )
                        }
                        composable(StaffTab.INBOX.route) {
                            LaunchedEffect(Unit) { communicationViewModel.loadAll() }
                            CommunicationCenterScreen(
                                state = communicationState,
                                onBack = { navigateRoot(StaffTab.HOME) },
                                onTab = communicationViewModel::selectTab,
                                onNoticeFilter = communicationViewModel::setNoticeFilter,
                                onMarkRead = communicationViewModel::markRead,
                                onMarkAllRead = communicationViewModel::markAllRead,
                                onOpenThread = { threadId ->
                                    communicationViewModel.openThread(threadId)
                                    navController.navigate("staff/inbox/messages/$threadId")
                                },
                                onCompose = {
                                    communicationViewModel.prepareCompose()
                                    navController.navigate(StaffRoutes.COMPOSE_MESSAGE)
                                },
                                onRetry = communicationViewModel::loadAll,
                            )
                        }
                        composable(StaffTab.MORE.route) {
                            StaffModulesHubScreen(session, width, openModule, onLogout)
                        }

                        composable(
                            route = StaffRoutes.CLASS_WORKSPACE,
                            arguments = listOf(navArgument("classId") { type = NavType.LongType }),
                        ) { entry ->
                            val classId = requireNotNull(entry.arguments).getLong("classId")
                            ClassWorkspaceScreen(
                                state = classesState,
                                onBack = navController::popBackStack,
                                onStudentSearch = classesViewModel::setStudentSearch,
                                onLoadMoreStudents = classesViewModel::loadMoreStudents,
                                onOpenStudent = { selectedClassId, studentId ->
                                    classesViewModel.openStudent(selectedClassId, studentId)
                                    navController.navigate("staff/classes/$selectedClassId/students/$studentId")
                                },
                                onOpenAttendance = { selectedClassId ->
                                    classesViewModel.openAttendance(selectedClassId)
                                    navController.navigate("staff/classes/$selectedClassId/attendance")
                                },
                                onOpenScores = { selectedClassId, subjectId ->
                                    val termId = session.academicPeriod.termId
                                    scoresViewModel.openSheet(selectedClassId, subjectId, termId)
                                    navController.navigate("staff/scores/$selectedClassId/$subjectId/${termId ?: 0}")
                                },
                                onOpenSchedule = { selectedClassId ->
                                    scheduleViewModel.load(classId = selectedClassId)
                                    navController.navigate("staff/schedule/$selectedClassId")
                                },
                                onRetry = { classesViewModel.openClass(classId) },
                            )
                        }
                        composable(
                            route = StaffRoutes.STUDENT_PROFILE,
                            arguments = listOf(
                                navArgument("classId") { type = NavType.LongType },
                                navArgument("studentId") { type = NavType.LongType },
                            ),
                        ) { StudentProfileScreen(classesState, navController::popBackStack) }
                        composable(
                            route = StaffRoutes.ATTENDANCE,
                            arguments = listOf(navArgument("classId") { type = NavType.LongType }),
                        ) {
                            AttendanceScreen(
                                state = classesState,
                                online = online,
                                onBack = navController::popBackStack,
                                onStatus = classesViewModel::updateAttendanceStatus,
                                onMarkAllPresent = classesViewModel::markAllPresent,
                                onDiscardDraft = classesViewModel::discardAttendanceDraft,
                                onSubmit = classesViewModel::submitAttendance,
                            )
                        }
                        composable(StaffRoutes.STAFF_ATTENDANCE) {
                            StaffAttendanceScreen(
                                state = classesState,
                                online = online,
                                onBack = navController::popBackStack,
                                onRefresh = classesViewModel::loadStaffAttendance,
                                onClockIn = classesViewModel::clockIn,
                                onClockOut = classesViewModel::clockOut,
                            )
                        }
                        composable(StaffRoutes.ADMIN_STAFF_ATTENDANCE) {
                            LaunchedEffect(Unit) { adminAttendanceViewModel.loadDaily() }
                            AdminStaffAttendanceScreen(
                                state = adminAttendanceState,
                                onSection = adminAttendanceViewModel::selectSection,
                                onDailyDate = adminAttendanceViewModel::setDailyDate,
                                onDailyQuery = adminAttendanceViewModel::setDailyQuery,
                                onDailyStatus = adminAttendanceViewModel::setDailyStatus,
                                onReportMonth = adminAttendanceViewModel::setReportMonth,
                                onReportYear = adminAttendanceViewModel::setReportYear,
                                onRefreshDaily = adminAttendanceViewModel::loadDaily,
                                onRefreshReport = adminAttendanceViewModel::loadReport,
                                onRefreshReviews = adminAttendanceViewModel::loadReviews,
                                onManualOverride = adminAttendanceViewModel::manualOverride,
                                onProcessOffline = adminAttendanceViewModel::processOffline,
                                onDecideProxy = adminAttendanceViewModel::decideProxy,
                                onSaveSettings = adminAttendanceViewModel::saveSettings,
                                onResetQr = adminAttendanceViewModel::resetQr,
                            )
                        }
                        composable(StaffRoutes.SCORES) {
                            ScoreAssignmentsScreen(
                                state = scoresState,
                                onSearch = scoresViewModel::setSearch,
                                onOpen = { assignment, termId ->
                                    scoresViewModel.openSheet(assignment.classId, assignment.subjectId, termId)
                                    navController.navigate("staff/scores/${assignment.classId}/${assignment.subjectId}/${termId ?: 0}")
                                },
                                onRetry = scoresViewModel::loadAssignments,
                            )
                        }
                        composable(
                            route = StaffRoutes.SCORE_SHEET,
                            arguments = listOf(
                                navArgument("classId") { type = NavType.LongType },
                                navArgument("subjectId") { type = NavType.LongType },
                                navArgument("termId") { type = NavType.LongType },
                            ),
                        ) { entry ->
                            val args = requireNotNull(entry.arguments)
                            val classId = args.getLong("classId")
                            val subjectId = args.getLong("subjectId")
                            val termId = args.getLong("termId").takeIf { it > 0 }
                            ScoreSheetScreen(
                                state = scoresState,
                                onBack = navController::popBackStack,
                                onValue = scoresViewModel::updateScore,
                                onDiscard = scoresViewModel::discardDraft,
                                onSubmit = scoresViewModel::submit,
                                onRetry = { scoresViewModel.openSheet(classId, subjectId, termId) },
                            )
                        }
                        composable(
                            route = StaffRoutes.SCHEDULE,
                            arguments = listOf(navArgument("classId") { type = NavType.LongType }),
                        ) { entry ->
                            val classId = requireNotNull(entry.arguments).getLong("classId").takeIf { it > 0 }
                            ScheduleScreen(
                                state = scheduleState,
                                onBack = navController::popBackStack,
                                onSection = scheduleViewModel::selectSection,
                                onDay = scheduleViewModel::selectDay,
                                onRetry = { scheduleViewModel.load(classId = classId) },
                            )
                        }

                        composable(StaffRoutes.SKILLS) {
                            LaunchedEffect(Unit) {
                                if (skillsState.workspace == null && !skillsState.isLoading) {
                                    skillsViewModel.load()
                                }
                            }
                            SkillsScreen(
                                state = skillsState,
                                onBack = {
                                    if (skillsState.inSheet) skillsViewModel.requestCloseSheet()
                                    else navController.popBackStack()
                                },
                                onClass = skillsViewModel::selectClass,
                                onTerm = skillsViewModel::selectTerm,
                                onOpenSheet = skillsViewModel::openSheet,
                                onCategory = skillsViewModel::setCategory,
                                onPreviousStudent = skillsViewModel::previousStudent,
                                onNextStudent = skillsViewModel::nextStudent,
                                onRate = skillsViewModel::rate,
                                onSave = skillsViewModel::save,
                                onRetry = {
                                    if (skillsState.inSheet) skillsViewModel.openSheet()
                                    else skillsViewModel.load()
                                },
                                onCancelDiscard = skillsViewModel::cancelDiscard,
                                onConfirmDiscard = skillsViewModel::confirmDiscard,
                            )
                        }

                        composable(StaffRoutes.PROFILE) {
                            StaffProfileScreen(session = session, onBack = navController::popBackStack)
                        }

                        composable(StaffRoutes.REPOSITORY) {
                            AcademicRepositoryScreen(
                                state = academicState,
                                onBack = navController::popBackStack,
                                onQuery = academicViewModel::setQuery,
                                onSearch = academicViewModel::submitSearch,
                                onClass = academicViewModel::selectClass,
                                onTerm = academicViewModel::selectTerm,
                                onSubject = academicViewModel::selectSubject,
                                onLoadMore = academicViewModel::loadMoreResources,
                                onOpen = { id ->
                                    academicViewModel.openResource(id)
                                    navController.navigate("staff/repository/$id")
                                },
                                onRetry = academicViewModel::loadRepository,
                                onDocumentOpened = academicViewModel::consumeDocument,
                            )
                        }
                        composable(
                            route = StaffRoutes.REPOSITORY_RESOURCE,
                            arguments = listOf(navArgument("resourceId") { type = NavType.LongType }),
                        ) { entry ->
                            val id = requireNotNull(entry.arguments).getLong("resourceId")
                            AcademicResourceDetailScreen(
                                state = academicState,
                                onBack = navController::popBackStack,
                                onDownload = academicViewModel::downloadResource,
                                onRetry = { academicViewModel.openResource(id) },
                                onDocumentOpened = academicViewModel::consumeDocument,
                            )
                        }
                        composable(StaffRoutes.LESSON_PLANS) {
                            LessonPlannerListScreen(
                                state = academicState,
                                onBack = navController::popBackStack,
                                onNew = {
                                    academicViewModel.newLesson()
                                    navController.navigate("staff/lesson-plans/0")
                                },
                                onOpen = { id ->
                                    academicViewModel.openLesson(id)
                                    navController.navigate("staff/lesson-plans/$id")
                                },
                                onRetry = academicViewModel::loadLessons,
                            )
                        }
                        composable(
                            route = StaffRoutes.LESSON_EDITOR,
                            arguments = listOf(navArgument("lessonPlanId") { type = NavType.LongType }),
                        ) {
                            LessonPlanEditorScreen(
                                state = academicState,
                                online = online,
                                onBack = {
                                    academicViewModel.loadLessons()
                                    navController.popBackStack()
                                },
                                onClass = academicViewModel::chooseClass,
                                onSubject = academicViewModel::chooseSubject,
                                onTerm = academicViewModel::chooseTerm,
                                onCurriculum = academicViewModel::chooseCurriculum,
                                onDelivery = academicViewModel::chooseDelivery,
                                onTopic = academicViewModel::updateTopic,
                                onSubtopic = academicViewModel::updateSubtopic,
                                onWeek = academicViewModel::updateWeek,
                                onDuration = academicViewModel::updateDuration,
                                onLessonNumber = academicViewModel::updateLessonNumber,
                                onLessonTime = academicViewModel::updateLessonTime,
                                onAverageAge = academicViewModel::updateAverageAge,
                                onSex = academicViewModel::updateSex,
                                onPlanDate = academicViewModel::updatePlanDate,
                                onSection = academicViewModel::updateSection,
                                onSave = academicViewModel::saveLesson,
                                onGenerate = academicViewModel::generateLesson,
                                onGenerateNote = { academicViewModel.generateNote() },
                                onUpdateNote = academicViewModel::updateNote,
                                onPublish = academicViewModel::publishLesson,
                                onDownloadPlan = { academicViewModel.downloadLesson(false) },
                                onDownloadNote = { academicViewModel.downloadLesson(true) },
                                onDocumentOpened = academicViewModel::consumeDocument,
                            )
                        }

                        composable(
                            route = StaffRoutes.MESSAGE_THREAD,
                            arguments = listOf(navArgument("threadId") { type = NavType.LongType }),
                        ) { entry ->
                            val id = requireNotNull(entry.arguments).getLong("threadId")
                            MessageThreadScreen(
                                state = communicationState,
                                onBack = {
                                    communicationViewModel.loadMessages()
                                    navController.popBackStack()
                                },
                                onReplyChange = communicationViewModel::setReplyBody,
                                onReply = communicationViewModel::reply,
                                onAttachmentPicked = communicationViewModel::loadAttachment,
                                onClearAttachment = communicationViewModel::clearAttachment,
                                onDownload = communicationViewModel::download,
                                onRetry = { communicationViewModel.openThread(id) },
                                onDocumentOpened = communicationViewModel::consumeDocument,
                            )
                        }
                        composable(StaffRoutes.COMPOSE_MESSAGE) {
                            ComposeMessageScreen(
                                state = communicationState,
                                onBack = navController::popBackStack,
                                onRecipient = communicationViewModel::selectRecipient,
                                onSubject = communicationViewModel::setComposeSubject,
                                onBody = communicationViewModel::setComposeBody,
                                onAttachmentPicked = communicationViewModel::loadAttachment,
                                onClearAttachment = communicationViewModel::clearAttachment,
                                onSend = communicationViewModel::compose,
                                onComposed = { threadId ->
                                    navController.navigate("staff/inbox/messages/$threadId") {
                                        popUpTo(StaffRoutes.COMPOSE_MESSAGE) { inclusive = true }
                                    }
                                },
                                onRetry = communicationViewModel::prepareCompose,
                            )
                        }
                        composable(
                            route = StaffRoutes.OPERATIONS,
                            arguments = listOf(navArgument("module") { type = NavType.StringType }),
                        ) { entry ->
                            val module = requireNotNull(entry.arguments).getString("module").orEmpty()
                            OperationsScreen(
                                state = operationsState,
                                width = width,
                                onBack = navController::popBackStack,
                                onQuery = operationsViewModel::setQuery,
                                onSection = operationsViewModel::selectSection,
                                onRetry = { operationsViewModel.load(module) },
                            )
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun StaffDashboardRoot(
    session: SessionSnapshot,
    dashboard: DashboardUiState,
    width: EduCoreWindowWidth,
    onModuleClick: (ModuleDescriptor) -> Unit,
    onRetry: () -> Unit,
) {
    val columns = when (width) {
        EduCoreWindowWidth.Compact -> 2
        EduCoreWindowWidth.Medium -> 3
        EduCoreWindowWidth.Expanded -> 4
    }
    LazyVerticalGrid(
        columns = GridCells.Fixed(columns),
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = androidx.compose.foundation.layout.PaddingValues(eduCoreScreenPadding()),
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        dashboardHomeContent(session, dashboard, width, onModuleClick, onRetry)
    }
}

@Composable
private fun StaffMoreRoot(
    session: SessionSnapshot,
    width: EduCoreWindowWidth,
    onModuleClick: (ModuleDescriptor) -> Unit,
    onLogout: () -> Unit,
) {
    val columns = when (width) {
        EduCoreWindowWidth.Compact -> 2
        EduCoreWindowWidth.Medium -> 3
        EduCoreWindowWidth.Expanded -> 4
    }
    val modules = remember(session.modules, session.user.portal) {
        session.modules
            .filterNot { it.key.lowercase() in StaffRoutes.ROOT_MODULES }
            .distinctBy {
                if (!session.user.portal.equals("admin", ignoreCase = true) && it.key.equals("staff-attendance.self", ignoreCase = true)) {
                    "staff-attendance"
                } else {
                    it.key.lowercase()
                }
            }
    }

    LazyVerticalGrid(
        columns = GridCells.Fixed(columns),
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = androidx.compose.foundation.layout.PaddingValues(eduCoreScreenPadding()),
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item(span = { GridItemSpan(maxLineSpan) }) {
            EduCoreProfileHeader(
                name = session.user.name,
                role = session.user.roleLabel,
                identifier = session.user.staffId ?: session.user.email,
            )
        }
        item(span = { GridItemSpan(maxLineSpan) }) {
            EduCoreSectionHeader("More tools", "Secondary workspaces available to your role")
        }
        if (modules.isEmpty()) {
            item(span = { GridItemSpan(maxLineSpan) }) {
                EduCoreEmptyState("No additional tools", "Your main workspaces are already in the navigation bar.")
            }
        } else {
            items(modules, key = ModuleDescriptor::key) { module ->
                EduCoreModuleCard(
                    title = module.title,
                    subtitle = staffSubtitle(module.key),
                    icon = staffIcon(module.key),
                    badge = if (module.key.lowercase() in setOf("cbt", "cbt-exams", "examinations")) "In progress" else null,
                    onClick = { onModuleClick(module) },
                    modifier = Modifier.fillMaxWidth(),
                )
            }
        }
        item(span = { GridItemSpan(maxLineSpan) }) {
            EduCorePrimaryButton(
                text = "Sign out",
                onClick = onLogout,
                modifier = Modifier.fillMaxWidth(),
                leadingIcon = { Icon(Icons.AutoMirrored.Filled.Logout, contentDescription = null) },
            )
        }
    }
}

private enum class StaffTab(val route: String, val label: String, val icon: ImageVector) {
    HOME("staff-v2/home", "Home", Icons.Default.Home),
    CLASSES("staff-v2/classes", "Classes", Icons.Default.School),
    TIMETABLE("staff-v2/timetable", "Timetable", Icons.Default.Schedule),
    INBOX("staff-v2/inbox", "Inbox", Icons.Default.Notifications),
    MORE("staff-v2/more", "More", Icons.Default.MoreHoriz),
}

private object StaffRoutes {
    const val CLASS_WORKSPACE = "staff/classes/{classId}"
    const val STUDENT_PROFILE = "staff/classes/{classId}/students/{studentId}"
    const val ATTENDANCE = "staff/classes/{classId}/attendance"
    const val STAFF_ATTENDANCE = "staff/staff-attendance"
    const val ADMIN_STAFF_ATTENDANCE = "staff/admin-staff-attendance"
    const val SCORES = "staff/scores"
    const val SCORE_SHEET = "staff/scores/{classId}/{subjectId}/{termId}"
    const val SCHEDULE = "staff/schedule/{classId}"
    const val SKILLS = "staff/skills"
    const val PROFILE = "staff/profile"
    const val REPOSITORY = "staff/repository"
    const val REPOSITORY_RESOURCE = "staff/repository/{resourceId}"
    const val LESSON_PLANS = "staff/lesson-plans"
    const val LESSON_EDITOR = "staff/lesson-plans/{lessonPlanId}"
    const val MESSAGE_THREAD = "staff/inbox/messages/{threadId}"
    const val COMPOSE_MESSAGE = "staff/inbox/compose"
    const val OPERATIONS = "staff/operations/{module}"

    val ROOT_MODULES = setOf(
        "dashboard", "classes", "students", "attendance", "scores", "scores.entry", "timetable",
        "messages", "notifications.view", "announcements", "calendar.view",
    )
    val CLASS_ROOT_MODULES = setOf("classes", "students", "attendance", "scores", "scores.entry")
    val INBOX_ROOT_MODULES = setOf("messages", "notifications.view", "announcements", "calendar.view")
    val GENERIC_OPERATIONS = setOf(
        "fees", "expenses", "payroll", "admissions", "library", "transport", "health",
        "inventory", "hostels", "subjects", "curriculum", "academic-cycle",
    )
}

private fun availableStaffTabs(modules: List<ModuleDescriptor>): List<StaffTab> {
    val keys = modules.map { it.key.lowercase() }.toSet()
    return StaffTab.entries.filter { tab ->
        when (tab) {
            StaffTab.HOME, StaffTab.MORE -> true
            StaffTab.CLASSES -> keys.any { it in StaffRoutes.CLASS_ROOT_MODULES }
            StaffTab.TIMETABLE -> "timetable" in keys
            StaffTab.INBOX -> keys.any { it in StaffRoutes.INBOX_ROOT_MODULES }
        }
    }
}

private fun rootFor(route: String): StaffTab = when {
    route.startsWith("staff-v2/classes") || route.startsWith("staff/classes") || route.startsWith("staff/scores") -> StaffTab.CLASSES
    route.startsWith("staff-v2/timetable") || route.startsWith("staff/schedule") -> StaffTab.TIMETABLE
    route.startsWith("staff-v2/inbox") || route.startsWith("staff/inbox") -> StaffTab.INBOX
    route.startsWith("staff-v2/more") || route.startsWith("staff/staff-attendance") || route.startsWith("staff/admin-staff-attendance") ||
        route.startsWith("staff/skills") || route.startsWith("staff/profile") ||
        route.startsWith("staff/repository") || route.startsWith("staff/lesson-plans") ||
        route.startsWith("staff/operations") -> StaffTab.MORE
    else -> StaffTab.HOME
}

private fun staffSubtitle(key: String): String? = when (key.lowercase()) {
    "staff-attendance" -> "School-wide attendance control"
    "staff-attendance.self" -> "Your clock-in, clock-out and history"
    "reports" -> "School operational reports"
    "report-cards", "results" -> "Published results are available to parent accounts only"
    "skills" -> "Behavioural and psychomotor ratings"
    "cbt", "cbt-exams", "examinations" -> "Manage computer-based examinations"
    "lesson-planner" -> "Create, generate and publish lesson plans"
    "academic-repository" -> "Notes, schemes and academic resources"
    "subjects" -> "Subjects available in your school"
    "curriculum" -> "Curriculum and syllabus resources"
    "academic-cycle" -> "Academic sessions and terms"
    "profile" -> "Your account and professional information"
    "library" -> "School library resources"
    "health" -> "Student health records"
    "transport" -> "School transport information"
    else -> null
}

private fun staffIcon(key: String): ImageVector = when {
    key.contains("attendance", true) -> EduCoreIcons.Attendance
    key.contains("report", true) || key.contains("result", true) || key.contains("score", true) -> EduCoreIcons.Scores
    key.contains("cbt", true) || key.contains("exam", true) -> EduCoreIcons.ExamDuties
    key.contains("lesson", true) -> EduCoreIcons.LessonPlan
    key.contains("repository", true) -> EduCoreIcons.Repository
    key.contains("subject", true) || key.contains("curriculum", true) -> EduCoreIcons.Subjects
    key.contains("fee", true) || key.contains("payment", true) -> EduCoreIcons.Payments
    key.contains("profile", true) || key.contains("setting", true) -> EduCoreIcons.Settings
    else -> EduCoreIcons.Modules
}
