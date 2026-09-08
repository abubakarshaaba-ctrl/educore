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
import androidx.compose.foundation.lazy.grid.LazyGridScope
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
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Badge
import androidx.compose.material3.BadgedBox
import androidx.compose.material3.NavigationRail
import androidx.compose.material3.NavigationRailItem
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.unit.dp
import androidx.navigation.NavGraph.Companion.findStartDestination
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import kotlinx.coroutines.launch
import online.educoreng.educore.core.designsystem.component.EduCoreBottomNavigation
import online.educoreng.educore.core.designsystem.component.EduCoreConfirmationDialog
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreModuleCard
import online.educoreng.educore.core.designsystem.component.EduCoreNavigationItem
import online.educoreng.educore.core.designsystem.component.EduCoreOfflineBanner
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
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
import online.educoreng.educore.notification.NotificationDeepLinkStore

@Composable
internal fun AuthorizedShell(
    session: SessionSnapshot,
    dashboard: DashboardUiState,
    online: Boolean,
    busy: Boolean,
    snackbarHostState: SnackbarHostState,
    onRefresh: () -> Unit,
    onRefreshDashboard: () -> Unit,
    onOpenWebModule: (String) -> Unit,
    onLogout: () -> Unit,
) {
    val navController = rememberNavController()
    val classesViewModel: ClassesViewModel = hiltViewModel()
    val classesState by classesViewModel.uiState.collectAsStateWithLifecycle()
    val scoresViewModel: ScoresViewModel = hiltViewModel()
    val scoresState by scoresViewModel.uiState.collectAsStateWithLifecycle()
    val scheduleViewModel: ScheduleViewModel = hiltViewModel()
    val scheduleState by scheduleViewModel.uiState.collectAsStateWithLifecycle()
    val academicContentViewModel: AcademicContentViewModel = hiltViewModel()
    val academicContentState by academicContentViewModel.uiState.collectAsStateWithLifecycle()
    val cbtViewModel: CbtViewModel = hiltViewModel()
    val cbtState by cbtViewModel.uiState.collectAsStateWithLifecycle()
    val operationsViewModel: OperationsViewModel = hiltViewModel()
    val operationsState by operationsViewModel.uiState.collectAsStateWithLifecycle()
    val communicationViewModel: CommunicationViewModel = hiltViewModel()
    val communicationState by communicationViewModel.uiState.collectAsStateWithLifecycle()
    val portalAttendanceViewModel: PortalAttendanceViewModel = hiltViewModel()
    val portalAttendanceState by portalAttendanceViewModel.uiState.collectAsStateWithLifecycle()
    val pendingDeepLink by NotificationDeepLinkStore.pending.collectAsStateWithLifecycle()
    val tabs = remember(session) { ShellNavigationPolicy.tabs(session) }
    val backStackEntry by navController.currentBackStackEntryAsState()
    val currentRoute = backStackEntry?.destination?.route ?: ShellTabId.HOME.route
    val currentTab = tabs.firstOrNull { it.id.route == currentRoute } ?: tabs.first()
    val currentTitle = when {
        currentRoute == NativeRoute.CLASSES -> "Classes"
        currentRoute == NativeRoute.CLASS_WORKSPACE -> "Class Workspace"
        currentRoute == NativeRoute.STUDENT_PROFILE -> "Student Profile"
        currentRoute == NativeRoute.ATTENDANCE -> "Student Attendance"
        currentRoute == NativeRoute.PORTAL_ATTENDANCE -> if (session.user.portal == "parent") "Child Attendance" else "My Attendance"
        currentRoute == NativeRoute.STAFF_ATTENDANCE -> "Staff Attendance"
        currentRoute == NativeRoute.SCORES -> "Score Entry"
        currentRoute == NativeRoute.SCORE_SHEET -> "Score Sheet"
        currentRoute == NativeRoute.RESULTS -> "Published Results"
        currentRoute == NativeRoute.SCHEDULE -> "Schedule"
        currentRoute == NativeRoute.REPOSITORY -> "Academic Repository"
        currentRoute == NativeRoute.REPOSITORY_RESOURCE -> "Repository Resource"
        currentRoute == NativeRoute.LESSON_PLANS -> "Lesson Planner"
        currentRoute == NativeRoute.LESSON_EDITOR -> "Lesson Plan"
        currentRoute == NativeRoute.CBT_EXAMS -> "CBT Examinations"
        currentRoute == NativeRoute.CBT_PREFLIGHT -> "Examination Access"
        currentRoute == NativeRoute.CBT_ATTEMPT -> "Secure Examination"
        currentRoute == NativeRoute.MESSAGE_THREAD -> "Conversation"
        currentRoute == NativeRoute.COMPOSE_MESSAGE -> "New Message"
        currentRoute == NativeRoute.OPERATIONS -> operationsState.workspace?.module?.title ?: "School Operations"
        else -> currentTab.label
    }
    val secureAttempt = currentRoute == NativeRoute.CBT_ATTEMPT
    val selectedNavigationRoute = if (currentRoute.startsWith("native/communications/")) ShellTabId.INBOX.route else currentRoute
    val scope = rememberCoroutineScope()
    var confirmLogout by remember { mutableStateOf(false) }

    LaunchedEffect(classesState.message) {
        classesState.message?.let { message ->
            snackbarHostState.showSnackbar(message)
            classesViewModel.consumeMessage()
        }
    }
    LaunchedEffect(scoresState.message) {
        scoresState.message?.let { message ->
            snackbarHostState.showSnackbar(message)
            scoresViewModel.consumeMessage()
        }
    }
    LaunchedEffect(academicContentState.message) {
        academicContentState.message?.let { message ->
            snackbarHostState.showSnackbar(message)
            academicContentViewModel.consumeMessage()
        }
    }
    LaunchedEffect(cbtState.message) {
        cbtState.message?.let { message ->
            snackbarHostState.showSnackbar(message)
            cbtViewModel.consumeMessage()
        }
    }
    LaunchedEffect(communicationState.message) {
        communicationState.message?.let { message ->
            snackbarHostState.showSnackbar(message)
            communicationViewModel.consumeMessage()
        }
    }
    LaunchedEffect(pendingDeepLink, currentRoute, session.modules) {
        val target = pendingDeepLink ?: return@LaunchedEffect
        if (secureAttempt) return@LaunchedEffect
        val moduleKeys = session.modules.map(ModuleDescriptor::key).toSet()
        val handled = when (target.type) {
            "announcement" -> moduleKeys.any { it in NativeRoute.NOTIFICATION_MODULES }.also { allowed ->
                if (allowed) {
                    communicationViewModel.selectTab(CommunicationTab.NOTICES.ordinal)
                    navController.navigate(ShellTabId.INBOX.route) { launchSingleTop = true }
                }
            }
            "message_thread" -> moduleKeys.any { it in NativeRoute.MESSAGE_MODULES }.also { allowed ->
                val threadId = target.id?.toLongOrNull()
                if (allowed && threadId != null) {
                    communicationViewModel.openThread(threadId)
                    navController.navigate("native/communications/messages/$threadId") { launchSingleTop = true }
                }
            }
            "calendar_event" -> moduleKeys.any { it in NativeRoute.CALENDAR_MODULES }.also { allowed ->
                if (allowed) {
                    communicationViewModel.selectTab(CommunicationTab.EVENTS.ordinal)
                    navController.navigate(ShellTabId.INBOX.route) { launchSingleTop = true }
                }
            }
            else -> false
        }
        NotificationDeepLinkStore.consume()
        if (!handled) snackbarHostState.showSnackbar("This update is not available for your current account.")
    }

    val navigationItems = tabs.map { tab ->
        EduCoreNavigationItem(
            key = tab.id.route,
            label = tab.label,
            icon = tabIcon(tab.id),
            badgeCount = if (tab.id == ShellTabId.INBOX) {
                communicationState.unreadNotifications + (communicationState.messagePage?.unreadCount ?: 0)
            } else 0,
        )
    }

    fun navigate(tab: ShellTab) {
        navController.navigate(tab.id.route) {
            popUpTo(navController.graph.findStartDestination().id) { saveState = true }
            launchSingleTop = true
            restoreState = true
        }
    }

    EduCoreAdaptiveLayout(Modifier.fillMaxSize()) { width ->
        Scaffold(
            containerColor = EduCoreColors.Page50,
            topBar = {
                Column {
                    EduCoreTopAppBar(
                        title = currentTitle,
                        subtitle = listOfNotNull(
                            session.school.name,
                            session.academicPeriod.termName,
                        ).joinToString(" · "),
                        actions = {
                            if (!secureAttempt) {
                                IconButton(onClick = onRefresh, enabled = !busy && online) {
                                    Icon(Icons.Default.Refresh, contentDescription = "Refresh workspace")
                                }
                            }
                        },
                    )
                    if (!online) EduCoreOfflineBanner()
                }
            },
            bottomBar = {
                if (width == EduCoreWindowWidth.Compact && !secureAttempt) {
                    EduCoreBottomNavigation(
                        items = navigationItems,
                        selectedKey = selectedNavigationRoute,
                        onSelect = { item ->
                            tabs.firstOrNull { it.id.route == item.key }?.let(::navigate)
                        },
                    )
                }
            },
        ) { contentPadding ->
            Row(
                modifier = Modifier.fillMaxSize().padding(contentPadding),
            ) {
                if (width != EduCoreWindowWidth.Compact && !secureAttempt) {
                    NavigationRail(containerColor = EduCoreColors.White) {
                        tabs.forEach { tab ->
                            NavigationRailItem(
                                selected = selectedNavigationRoute == tab.id.route,
                                onClick = { navigate(tab) },
                                icon = {
                                    val unread = if (tab.id == ShellTabId.INBOX) {
                                        communicationState.unreadNotifications + (communicationState.messagePage?.unreadCount ?: 0)
                                    } else 0
                                    BadgedBox(badge = { if (unread > 0) Badge { Text(unread.coerceAtMost(99).toString() + if (unread > 99) "+" else "") } }) {
                                        Icon(tabIcon(tab.id), contentDescription = tab.label)
                                    }
                                },
                                label = { Text(tab.label, maxLines = 1) },
                            )
                        }
                    }
                }
                Box(
                    modifier = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.TopCenter,
                ) {
                    NavHost(
                        navController = navController,
                        startDestination = ShellTabId.HOME.route,
                        modifier = Modifier.fillMaxSize().widthIn(max = 1180.dp),
                    ) {
                        tabs.forEach { tab ->
                            composable(tab.id.route) {
                                if (tab.id == ShellTabId.INBOX) {
                                    LaunchedEffect(Unit) { communicationViewModel.loadAll() }
                                    CommunicationCenterScreen(
                                        state = communicationState,
                                        onBack = { navigate(tabs.first()) },
                                        onTab = communicationViewModel::selectTab,
                                        onNoticeFilter = communicationViewModel::setNoticeFilter,
                                        onMarkRead = communicationViewModel::markRead,
                                        onMarkAllRead = communicationViewModel::markAllRead,
                                        onOpenThread = { threadId ->
                                            communicationViewModel.openThread(threadId)
                                            navController.navigate("native/communications/messages/$threadId")
                                        },
                                        onCompose = {
                                            communicationViewModel.prepareCompose()
                                            navController.navigate(NativeRoute.COMPOSE_MESSAGE)
                                        },
                                        onRetry = communicationViewModel::loadAll,
                                    )
                                } else ShellTabScreen(
                                    tab = tab,
                                    session = session,
                                    width = width,
                                    dashboard = dashboard,
                                    onRefreshDashboard = onRefreshDashboard,
                                    onModuleClick = { module ->
                                        when (module.key) {
                                            "classes", "students", "attendance" -> {
                                                classesViewModel.loadClasses()
                                                navController.navigate(NativeRoute.CLASSES) { launchSingleTop = true }
                                            }
                                            "student.attendance", "parent.attendance" -> {
                                                portalAttendanceViewModel.load(session.user.portal)
                                                navController.navigate(NativeRoute.PORTAL_ATTENDANCE) { launchSingleTop = true }
                                            }
                                            "staff-attendance", "staff-attendance.self" -> {
                                                classesViewModel.loadStaffAttendance()
                                                navController.navigate(NativeRoute.STAFF_ATTENDANCE) { launchSingleTop = true }
                                            }
                                            "scores", "scores.entry" -> {
                                                if (session.can("scores") || session.can("scores.entry")) {
                                                    scoresViewModel.loadAssignments()
                                                    navController.navigate(NativeRoute.SCORES) { launchSingleTop = true }
                                                } else {
                                                    onOpenWebModule(module.path)
                                                }
                                            }
                                            "timetable", "student.timetable" -> {
                                                scheduleViewModel.load()
                                                navController.navigate("native/schedule/0") { launchSingleTop = true }
                                            }
                                            "student.exams", "cbt", "cbt-exams", "examinations" -> {
                                                if (session.user.portal == "student") {
                                                    cbtViewModel.loadExams()
                                                    navController.navigate(NativeRoute.CBT_EXAMS) { launchSingleTop = true }
                                                } else {
                                                    onOpenWebModule(module.path)
                                                }
                                            }
                                            "results", "report-cards", "student.results", "parent.results" -> {
                                                if (session.user.portal == "student" || session.user.portal == "parent") {
                                                    scoresViewModel.loadResults()
                                                    navController.navigate(NativeRoute.RESULTS) { launchSingleTop = true }
                                                } else {
                                                    onOpenWebModule(module.path)
                                                }
                                            }
                                            "academic-repository" -> {
                                                academicContentViewModel.loadRepository()
                                                navController.navigate(NativeRoute.REPOSITORY) { launchSingleTop = true }
                                            }
                                            "lesson-planner" -> {
                                                academicContentViewModel.loadLessons()
                                                navController.navigate(NativeRoute.LESSON_PLANS) { launchSingleTop = true }
                                            }
                                            in NativeRoute.COMMUNICATION_MODULES -> {
                                                communicationViewModel.selectTab(
                                                    when {
                                                        module.key.contains("message") -> CommunicationTab.MESSAGES.ordinal
                                                        module.key.contains("calendar") -> CommunicationTab.EVENTS.ordinal
                                                        else -> CommunicationTab.NOTICES.ordinal
                                                    },
                                                )
                                                navController.navigate(ShellTabId.INBOX.route) { launchSingleTop = true }
                                            }
                                            in NativeRoute.OPERATIONS_MODULES -> {
                                                operationsViewModel.load(module.key)
                                                navController.navigate("native/operations/${module.key}") { launchSingleTop = true }
                                            }
                                            else -> onOpenWebModule(module.path)
                                        }
                                    },
                                    onLogout = { confirmLogout = true },
                                )
                            }
                        }
                        composable(NativeRoute.CLASSES) {
                            ClassesListScreen(
                                state = classesState,
                                width = width,
                                onSearch = classesViewModel::setClassSearch,
                                onOpenClass = { classId ->
                                    classesViewModel.openClass(classId)
                                    navController.navigate("native/classes/$classId")
                                },
                                onRetry = classesViewModel::loadClasses,
                            )
                        }
                        composable(
                            route = NativeRoute.CLASS_WORKSPACE,
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
                                    navController.navigate("native/classes/$selectedClassId/students/$studentId")
                                },
                                onOpenAttendance = { selectedClassId ->
                                    classesViewModel.openAttendance(selectedClassId)
                                    navController.navigate("native/classes/$selectedClassId/attendance")
                                },
                                onOpenScores = { selectedClassId, subjectId ->
                                    val termId = session.academicPeriod.termId
                                    scoresViewModel.openSheet(selectedClassId, subjectId, termId)
                                    navController.navigate("native/scores/$selectedClassId/$subjectId/${termId ?: 0}")
                                },
                                onOpenSchedule = { selectedClassId ->
                                    scheduleViewModel.load(classId = selectedClassId)
                                    navController.navigate("native/schedule/$selectedClassId")
                                },
                                onRetry = { classesViewModel.openClass(classId) },
                            )
                        }
                        composable(
                            route = NativeRoute.STUDENT_PROFILE,
                            arguments = listOf(
                                navArgument("classId") { type = NavType.LongType },
                                navArgument("studentId") { type = NavType.LongType },
                            ),
                        ) {
                            StudentProfileScreen(classesState, navController::popBackStack)
                        }
                        composable(
                            route = NativeRoute.ATTENDANCE,
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
                        composable(NativeRoute.PORTAL_ATTENDANCE) {
                            PortalAttendanceScreen(
                                state = portalAttendanceState,
                                onBack = navController::popBackStack,
                                onChild = portalAttendanceViewModel::selectChild,
                                onTerm = portalAttendanceViewModel::selectTerm,
                                onRetry = portalAttendanceViewModel::retry,
                            )
                        }
                        composable(NativeRoute.STAFF_ATTENDANCE) {
                            StaffAttendanceScreen(
                                state = classesState,
                                online = online,
                                onBack = navController::popBackStack,
                                onRefresh = classesViewModel::loadStaffAttendance,
                                onClockIn = classesViewModel::clockIn,
                                onClockOut = classesViewModel::clockOut,
                            )
                        }
                        composable(NativeRoute.SCORES) {
                            ScoreAssignmentsScreen(
                                state = scoresState,
                                onSearch = scoresViewModel::setSearch,
                                onOpen = { assignment, termId ->
                                    scoresViewModel.openSheet(assignment.classId, assignment.subjectId, termId)
                                    navController.navigate("native/scores/${assignment.classId}/${assignment.subjectId}/${termId ?: 0}")
                                },
                                onRetry = scoresViewModel::loadAssignments,
                            )
                        }
                        composable(
                            route = NativeRoute.SCORE_SHEET,
                            arguments = listOf(
                                navArgument("classId") { type = NavType.LongType },
                                navArgument("subjectId") { type = NavType.LongType },
                                navArgument("termId") { type = NavType.LongType },
                            ),
                        ) { entry ->
                            val arguments = requireNotNull(entry.arguments)
                            val classId = arguments.getLong("classId")
                            val subjectId = arguments.getLong("subjectId")
                            val termId = arguments.getLong("termId").takeIf { it > 0 }
                            ScoreSheetScreen(
                                state = scoresState,
                                onBack = navController::popBackStack,
                                onValue = scoresViewModel::updateScore,
                                onDiscard = scoresViewModel::discardDraft,
                                onSubmit = scoresViewModel::submit,
                                onRetry = { scoresViewModel.openSheet(classId, subjectId, termId) },
                            )
                        }
                        composable(NativeRoute.RESULTS) {
                            PublishedResultsScreen(
                                state = scoresState,
                                onBack = navController::popBackStack,
                                onRetry = { scoresViewModel.loadResults() },
                            )
                        }
                        composable(
                            route = NativeRoute.SCHEDULE,
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
                        composable(NativeRoute.REPOSITORY) {
                            AcademicRepositoryScreen(
                                state = academicContentState,
                                onBack = navController::popBackStack,
                                onQuery = academicContentViewModel::setQuery,
                                onSearch = academicContentViewModel::submitSearch,
                                onClass = academicContentViewModel::selectClass,
                                onTerm = academicContentViewModel::selectTerm,
                                onSubject = academicContentViewModel::selectSubject,
                                onLoadMore = academicContentViewModel::loadMoreResources,
                                onOpen = { resourceId ->
                                    academicContentViewModel.openResource(resourceId)
                                    navController.navigate("native/repository/$resourceId")
                                },
                                onRetry = academicContentViewModel::loadRepository,
                                onDocumentOpened = academicContentViewModel::consumeDocument,
                            )
                        }
                        composable(
                            route = NativeRoute.REPOSITORY_RESOURCE,
                            arguments = listOf(navArgument("resourceId") { type = NavType.LongType }),
                        ) { entry ->
                            val resourceId = requireNotNull(entry.arguments).getLong("resourceId")
                            AcademicResourceDetailScreen(
                                state = academicContentState,
                                onBack = navController::popBackStack,
                                onDownload = academicContentViewModel::downloadResource,
                                onRetry = { academicContentViewModel.openResource(resourceId) },
                                onDocumentOpened = academicContentViewModel::consumeDocument,
                            )
                        }
                        composable(NativeRoute.LESSON_PLANS) {
                            LessonPlannerListScreen(
                                state = academicContentState,
                                onBack = navController::popBackStack,
                                onNew = {
                                    academicContentViewModel.newLesson()
                                    navController.navigate("native/lesson-plans/0")
                                },
                                onOpen = { planId ->
                                    academicContentViewModel.openLesson(planId)
                                    navController.navigate("native/lesson-plans/$planId")
                                },
                                onRetry = academicContentViewModel::loadLessons,
                            )
                        }
                        composable(
                            route = NativeRoute.LESSON_EDITOR,
                            arguments = listOf(navArgument("lessonPlanId") { type = NavType.LongType }),
                        ) {
                            LessonPlanEditorScreen(
                                state = academicContentState,
                                online = online,
                                onBack = {
                                    academicContentViewModel.loadLessons()
                                    navController.popBackStack()
                                },
                                onClass = academicContentViewModel::chooseClass,
                                onSubject = academicContentViewModel::chooseSubject,
                                onTerm = academicContentViewModel::chooseTerm,
                                onCurriculum = academicContentViewModel::chooseCurriculum,
                                onDelivery = academicContentViewModel::chooseDelivery,
                                onTopic = academicContentViewModel::updateTopic,
                                onSubtopic = academicContentViewModel::updateSubtopic,
                                onWeek = academicContentViewModel::updateWeek,
                                onDuration = academicContentViewModel::updateDuration,
                                onLessonNumber = academicContentViewModel::updateLessonNumber,
                                onLessonTime = academicContentViewModel::updateLessonTime,
                                onAverageAge = academicContentViewModel::updateAverageAge,
                                onSex = academicContentViewModel::updateSex,
                                onPlanDate = academicContentViewModel::updatePlanDate,
                                onSection = academicContentViewModel::updateSection,
                                onSave = academicContentViewModel::saveLesson,
                                onGenerate = academicContentViewModel::generateLesson,
                                onGenerateNote = { academicContentViewModel.generateNote() },
                                onUpdateNote = academicContentViewModel::updateNote,
                                onPublish = academicContentViewModel::publishLesson,
                                onDownloadPlan = { academicContentViewModel.downloadLesson(false) },
                                onDownloadNote = { academicContentViewModel.downloadLesson(true) },
                                onDocumentOpened = academicContentViewModel::consumeDocument,
                            )
                        }
                        composable(NativeRoute.CBT_EXAMS) {
                            CbtExamsScreen(
                                state = cbtState,
                                onBack = navController::popBackStack,
                                onOpen = { examId ->
                                    cbtViewModel.openExam(examId)
                                    navController.navigate("native/cbt/exams/$examId")
                                },
                                onRetry = cbtViewModel::loadExams,
                            )
                        }
                        composable(
                            route = NativeRoute.CBT_PREFLIGHT,
                            arguments = listOf(navArgument("examId") { type = NavType.LongType }),
                        ) { entry ->
                            val examId = requireNotNull(entry.arguments).getLong("examId")
                            CbtPreflightScreen(
                                state = cbtState,
                                online = online,
                                onBack = navController::popBackStack,
                                onBegin = {
                                    cbtViewModel.begin()
                                    navController.navigate(NativeRoute.CBT_ATTEMPT)
                                },
                                onResume = {
                                    cbtViewModel.resume()
                                    navController.navigate(NativeRoute.CBT_ATTEMPT)
                                },
                                onRetry = { cbtViewModel.openExam(examId) },
                            )
                        }
                        composable(NativeRoute.CBT_ATTEMPT) {
                            CbtAttemptScreen(
                                state = cbtState,
                                online = online,
                                onSection = cbtViewModel::selectSection,
                                onQuestion = cbtViewModel::selectQuestion,
                                onPrevious = cbtViewModel::previous,
                                onNext = cbtViewModel::next,
                                onAnswer = cbtViewModel::answer,
                                onFlag = cbtViewModel::toggleFlag,
                                onSubmit = cbtViewModel::submit,
                                onFocusLost = cbtViewModel::recordFocusLoss,
                                onRetry = cbtViewModel::refreshAttempt,
                                onExit = {
                                    cbtViewModel.loadExams()
                                    navController.popBackStack(NativeRoute.CBT_EXAMS, false)
                                },
                            )
                        }
                        composable(
                            route = NativeRoute.MESSAGE_THREAD,
                            arguments = listOf(navArgument("threadId") { type = NavType.LongType }),
                        ) { entry ->
                            val threadId = requireNotNull(entry.arguments).getLong("threadId")
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
                                onRetry = { communicationViewModel.openThread(threadId) },
                                onDocumentOpened = communicationViewModel::consumeDocument,
                            )
                        }
                        composable(NativeRoute.COMPOSE_MESSAGE) {
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
                                    navController.navigate("native/communications/messages/$threadId") {
                                        popUpTo(NativeRoute.COMPOSE_MESSAGE) { inclusive = true }
                                    }
                                },
                                onRetry = communicationViewModel::prepareCompose,
                            )
                        }
                        composable(
                            route = NativeRoute.OPERATIONS,
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

    EduCoreConfirmationDialog(
        visible = confirmLogout,
        title = "Sign out?",
        message = "Your encrypted session and tenant-scoped offline cache will be removed from this device.",
        confirmLabel = "Sign out",
        onConfirm = {
            confirmLogout = false
            onLogout()
        },
        onDismiss = { confirmLogout = false },
        destructive = true,
    )
}

private object NativeRoute {
    const val CLASSES = "native/classes"
    const val CLASS_WORKSPACE = "native/classes/{classId}"
    const val STUDENT_PROFILE = "native/classes/{classId}/students/{studentId}"
    const val ATTENDANCE = "native/classes/{classId}/attendance"
    const val PORTAL_ATTENDANCE = "native/portal-attendance"
    const val STAFF_ATTENDANCE = "native/staff-attendance"
    const val SCORES = "native/scores"
    const val SCORE_SHEET = "native/scores/{classId}/{subjectId}/{termId}"
    const val RESULTS = "native/results"
    const val SCHEDULE = "native/schedule/{classId}"
    const val REPOSITORY = "native/repository"
    const val REPOSITORY_RESOURCE = "native/repository/{resourceId}"
    const val LESSON_PLANS = "native/lesson-plans"
    const val LESSON_EDITOR = "native/lesson-plans/{lessonPlanId}"
    const val CBT_EXAMS = "native/cbt/exams"
    const val CBT_PREFLIGHT = "native/cbt/exams/{examId}"
    const val CBT_ATTEMPT = "native/cbt/attempt"
    const val MESSAGE_THREAD = "native/communications/messages/{threadId}"
    const val COMPOSE_MESSAGE = "native/communications/compose"
    const val OPERATIONS = "native/operations/{module}"
    val COMMUNICATION_MODULES = setOf(
        "messages", "parent.messages", "student.messages", "notifications.view",
        "parent.notifications", "student.notifications", "calendar.view", "parent.calendar",
        "student.calendar", "announcements",
    )
    val MESSAGE_MODULES = setOf("messages", "parent.messages", "student.messages")
    val NOTIFICATION_MODULES = setOf("notifications.view", "parent.notifications", "student.notifications", "announcements")
    val CALENDAR_MODULES = setOf("calendar.view", "parent.calendar", "student.calendar")
    val OPERATIONS_MODULES = setOf(
        "fees", "parent.fees", "expenses", "payroll", "admissions", "library", "transport",
        "health", "inventory", "hostels", "subjects", "curriculum", "academic-cycle",
    )
}

@Composable
private fun ShellTabScreen(
    tab: ShellTab,
    session: SessionSnapshot,
    width: EduCoreWindowWidth,
    dashboard: DashboardUiState,
    onRefreshDashboard: () -> Unit,
    onModuleClick: (ModuleDescriptor) -> Unit,
    onLogout: () -> Unit,
) {
    val columns = when (width) {
        EduCoreWindowWidth.Compact -> 2
        EduCoreWindowWidth.Medium -> 3
        EduCoreWindowWidth.Expanded -> 4
    }
    val modules = ShellNavigationPolicy.modulesFor(tab.id, session)

    LazyVerticalGrid(
        columns = GridCells.Fixed(columns),
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = androidx.compose.foundation.layout.PaddingValues(eduCoreScreenPadding()),
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        when (tab.id) {
            ShellTabId.HOME -> dashboardHomeContent(
                session = session,
                state = dashboard,
                width = width,
                onModuleClick = onModuleClick,
                onRetry = onRefreshDashboard,
            )
            ShellTabId.MORE -> moduleHubContent(session, onModuleClick, onLogout)
            else -> moduleSection(tab, modules, onModuleClick)
        }
    }
}

private fun LazyGridScope.moduleSection(
    tab: ShellTab,
    modules: List<ModuleDescriptor>,
    onModuleClick: (ModuleDescriptor) -> Unit,
) {
    item(span = { GridItemSpan(maxLineSpan) }) {
        EduCoreSectionHeader(
            title = tab.label,
            supportingText = "Only modules permitted by the server are shown.",
        )
    }
    if (modules.isEmpty()) {
        item(span = { GridItemSpan(maxLineSpan) }) {
            EduCoreEmptyState(
                title = "Nothing available here",
                message = "Your account does not currently have a module in this section.",
            )
        }
    } else {
        items(modules, key = ModuleDescriptor::key) { module -> ModuleCard(module, onModuleClick) }
    }
}

private fun LazyGridScope.moduleHubContent(
    session: SessionSnapshot,
    onModuleClick: (ModuleDescriptor) -> Unit,
    onLogout: () -> Unit,
) {
    item(span = { GridItemSpan(maxLineSpan) }) {
        EduCoreSectionHeader(
            title = "All modules",
            supportingText = "Organized from your current role and permissions",
        )
    }
    ShellNavigationPolicy.groupedModules(session).forEach { (group, modules) ->
        item(key = "header-${group.name}", span = { GridItemSpan(maxLineSpan) }) {
            EduCoreSectionHeader(group.label)
        }
        items(modules, key = ModuleDescriptor::key) { module -> ModuleCard(module, onModuleClick) }
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

@Composable
private fun ModuleCard(
    module: ModuleDescriptor,
    onModuleClick: (ModuleDescriptor) -> Unit,
) {
    EduCoreModuleCard(
        title = module.title,
        subtitle = module.key,
        icon = moduleIcon(module),
        onClick = { onModuleClick(module) },
        modifier = Modifier.fillMaxWidth(),
    )
}

private fun tabIcon(id: ShellTabId): ImageVector = when (id) {
    ShellTabId.HOME -> Icons.Default.Home
    ShellTabId.PRIMARY -> Icons.Default.School
    ShellTabId.SECONDARY -> Icons.Default.Schedule
    ShellTabId.INBOX -> Icons.Default.Notifications
    ShellTabId.MORE -> Icons.Default.MoreHoriz
}

private fun moduleIcon(module: ModuleDescriptor): ImageVector {
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
