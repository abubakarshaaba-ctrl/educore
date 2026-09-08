package online.educoreng.educore.presentation

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.material.icons.Icons
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
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.unit.dp
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
import online.educoreng.educore.core.designsystem.component.EduCoreNavigationItem
import online.educoreng.educore.core.designsystem.component.EduCoreOfflineBanner
import online.educoreng.educore.core.designsystem.component.EduCoreTopAppBar
import online.educoreng.educore.core.designsystem.layout.EduCoreAdaptiveLayout
import online.educoreng.educore.core.designsystem.layout.EduCoreWindowWidth
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.ModuleDescriptor
import online.educoreng.educore.core.model.SessionSnapshot

/**
 * Staff shell using the approved August 2026 mobile reference as its root UI.
 * Authorisation remains server-owned: navigation is driven by session.modules,
 * session.permissions and per-class capabilities returned by the Laravel API.
 */
@Composable
internal fun StaffAuthorizedShowcaseShell(
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
    val academicContentViewModel: AcademicContentViewModel = hiltViewModel()
    val academicContentState by academicContentViewModel.uiState.collectAsStateWithLifecycle()
    val operationsViewModel: OperationsViewModel = hiltViewModel()
    val operationsState by operationsViewModel.uiState.collectAsStateWithLifecycle()
    val communicationViewModel: CommunicationViewModel = hiltViewModel()
    val communicationState by communicationViewModel.uiState.collectAsStateWithLifecycle()
    val scope = rememberCoroutineScope()

    val backStackEntry by navController.currentBackStackEntryAsState()
    val currentRoute = backStackEntry?.destination?.route ?: ShowcaseStaffTab.HOME.route
    val selectedRoot = showcaseSelectedRoot(currentRoute)

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
    LaunchedEffect(academicContentState.message) {
        academicContentState.message?.let {
            snackbarHostState.showSnackbar(it)
            academicContentViewModel.consumeMessage()
        }
    }
    LaunchedEffect(communicationState.message) {
        communicationState.message?.let {
            snackbarHostState.showSnackbar(it)
            communicationViewModel.consumeMessage()
        }
    }

    fun navigateRoot(tab: ShowcaseStaffTab) {
        navController.navigate(tab.route) {
            popUpTo(ShowcaseStaffTab.HOME.route) { saveState = true }
            launchSingleTop = true
            restoreState = true
        }
    }

    fun unavailable(module: ModuleDescriptor, detail: String = "is still being completed as a native workspace") {
        scope.launch {
            snackbarHostState.showSnackbar("${module.title} $detail. EduCore will not open an accidental browser fallback.")
        }
    }

    val onModuleClick: (ModuleDescriptor) -> Unit = { module ->
        when (module.key.lowercase()) {
            "classes", "students", "attendance" -> {
                classesViewModel.loadClasses()
                navigateRoot(ShowcaseStaffTab.CLASSES)
            }
            "staff-attendance", "staff-attendance.self" -> {
                classesViewModel.loadStaffAttendance()
                navController.navigate(ShowcaseStaffRoute.STAFF_ATTENDANCE) { launchSingleTop = true }
            }
            "scores", "scores.entry" -> {
                if (session.can("scores") || session.can("scores.entry")) {
                    scoresViewModel.loadAssignments()
                    navController.navigate(ShowcaseStaffRoute.SCORES) { launchSingleTop = true }
                } else unavailable(module, "is not permitted for this role")
            }
            "timetable" -> {
                scheduleViewModel.load()
                navigateRoot(ShowcaseStaffTab.TIMETABLE)
            }
            "academic-repository" -> {
                academicContentViewModel.loadRepository()
                navController.navigate(ShowcaseStaffRoute.REPOSITORY) { launchSingleTop = true }
            }
            "lesson-planner" -> {
                academicContentViewModel.loadLessons()
                navController.navigate(ShowcaseStaffRoute.LESSON_PLANS) { launchSingleTop = true }
            }
            "messages" -> {
                communicationViewModel.selectTab(CommunicationTab.MESSAGES.ordinal)
                communicationViewModel.loadAll()
                navigateRoot(ShowcaseStaffTab.INBOX)
            }
            "notifications.view", "announcements" -> {
                communicationViewModel.selectTab(CommunicationTab.NOTICES.ordinal)
                communicationViewModel.loadAll()
                navigateRoot(ShowcaseStaffTab.INBOX)
            }
            "calendar.view" -> {
                communicationViewModel.selectTab(CommunicationTab.EVENTS.ordinal)
                communicationViewModel.loadAll()
                navigateRoot(ShowcaseStaffTab.INBOX)
            }
            "profile" -> navController.navigate(ShowcaseStaffRoute.PROFILE) { launchSingleTop = true }
            in ShowcaseStaffRoute.GENERIC_OPERATIONS -> {
                operationsViewModel.load(module.key)
                navController.navigate("showcase/operations/${module.key}") { launchSingleTop = true }
            }
            "reports", "report-cards", "results" -> unavailable(module)
            "cbt", "cbt-exams", "examinations" -> unavailable(module)
            else -> when (ModulePresentationPolicy.presentationFor(module.key)) {
                ModulePresentation.NATIVE_GENERIC -> {
                    operationsViewModel.load(module.key)
                    navController.navigate("showcase/operations/${module.key}") { launchSingleTop = true }
                }
                else -> unavailable(module)
            }
        }
    }

    val rootItems = ShowcaseStaffTab.entries.map { tab ->
        EduCoreNavigationItem(
            key = tab.route,
            label = tab.label,
            icon = tab.icon,
            badgeCount = if (tab == ShowcaseStaffTab.INBOX) {
                communicationState.unreadNotifications + (communicationState.messagePage?.unreadCount ?: 0)
            } else 0,
        )
    }

    EduCoreAdaptiveLayout(Modifier.fillMaxSize()) { width ->
        val showRootTopBar = currentRoute in setOf(
            ShowcaseStaffTab.HOME.route,
            ShowcaseStaffTab.CLASSES.route,
            ShowcaseStaffTab.MORE.route,
        )
        Scaffold(
            containerColor = EduCoreColors.Page50,
            topBar = {
                if (showRootTopBar) {
                    Column {
                        EduCoreTopAppBar(
                            title = when (currentRoute) {
                                ShowcaseStaffTab.HOME.route -> "Home"
                                ShowcaseStaffTab.CLASSES.route -> "My Classes"
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
                        items = rootItems,
                        selectedKey = selectedRoot.route,
                        onSelect = { item ->
                            ShowcaseStaffTab.entries.firstOrNull { it.route == item.key }?.let(::navigateRoot)
                        },
                    )
                }
            },
        ) { padding ->
            Row(Modifier.fillMaxSize().padding(padding)) {
                if (width != EduCoreWindowWidth.Compact) {
                    NavigationRail(containerColor = EduCoreColors.White) {
                        ShowcaseStaffTab.entries.forEach { tab ->
                            val unread = if (tab == ShowcaseStaffTab.INBOX) {
                                communicationState.unreadNotifications + (communicationState.messagePage?.unreadCount ?: 0)
                            } else 0
                            NavigationRailItem(
                                selected = selectedRoot == tab,
                                onClick = { navigateRoot(tab) },
                                icon = {
                                    BadgedBox(
                                        badge = {
                                            if (unread > 0) Badge { Text(unread.coerceAtMost(99).toString()) }
                                        },
                                    ) { Icon(tab.icon, contentDescription = tab.label) }
                                },
                                label = { Text(tab.label) },
                            )
                        }
                    }
                }

                Box(Modifier.fillMaxSize(), contentAlignment = Alignment.TopCenter) {
                    NavHost(
                        navController = navController,
                        startDestination = ShowcaseStaffTab.HOME.route,
                        modifier = Modifier.fillMaxSize().widthIn(max = 1180.dp),
                    ) {
                        composable(ShowcaseStaffTab.HOME.route) {
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
                                dashboardHomeContent(session, dashboard, width, onModuleClick, onRefreshDashboard)
                            }
                        }
                        composable(ShowcaseStaffTab.CLASSES.route) {
                            LaunchedEffect(Unit) { classesViewModel.loadClasses() }
                            AugustClassesScreen(
                                session = session,
                                state = classesState,
                                width = width,
                                onSearch = classesViewModel::setClassSearch,
                                onOpenClass = { classId ->
                                    classesViewModel.openClass(classId)
                                    navController.navigate("showcase/classes/$classId")
                                },
                                onModuleClick = onModuleClick,
                                onRetry = classesViewModel::loadClasses,
                            )
                        }
                        composable(ShowcaseStaffTab.TIMETABLE.route) {
                            LaunchedEffect(Unit) { scheduleViewModel.load() }
                            ScheduleScreen(
                                state = scheduleState,
                                onBack = { navigateRoot(ShowcaseStaffTab.HOME) },
                                onSection = scheduleViewModel::selectSection,
                                onDay = scheduleViewModel::selectDay,
                                onRetry = scheduleViewModel::load,
                            )
                        }
                        composable(ShowcaseStaffTab.INBOX.route) {
                            LaunchedEffect(Unit) { communicationViewModel.loadAll() }
                            CommunicationCenterScreen(
                                state = communicationState,
                                onBack = { navigateRoot(ShowcaseStaffTab.HOME) },
                                onTab = communicationViewModel::selectTab,
                                onNoticeFilter = communicationViewModel::setNoticeFilter,
                                onMarkRead = communicationViewModel::markRead,
                                onMarkAllRead = communicationViewModel::markAllRead,
                                onOpenThread = { threadId ->
                                    communicationViewModel.openThread(threadId)
                                    navController.navigate("showcase/inbox/messages/$threadId")
                                },
                                onCompose = {
                                    communicationViewModel.prepareCompose()
                                    navController.navigate(ShowcaseStaffRoute.COMPOSE_MESSAGE)
                                },
                                onRetry = communicationViewModel::loadAll,
                            )
                        }
                        composable(ShowcaseStaffTab.MORE.route) {
                            AugustModulesHubScreen(session, width, onModuleClick, onLogout)
                        }

                        composable(
                            route = ShowcaseStaffRoute.CLASS_WORKSPACE,
                            arguments = listOf(navArgument("classId") { type = NavType.LongType }),
                        ) { entry ->
                            val classId = requireNotNull(entry.arguments).getLong("classId")
                            ClassWorkspaceScreen(
                                state = classesState,
                                onBack = navController::popBackStack,
                                onStudentSearch = classesViewModel::setStudentSearch,
                                onOpenStudent = { selectedClassId, studentId ->
                                    classesViewModel.openStudent(selectedClassId, studentId)
                                    navController.navigate("showcase/classes/$selectedClassId/students/$studentId")
                                },
                                onOpenAttendance = { selectedClassId ->
                                    classesViewModel.openAttendance(selectedClassId)
                                    navController.navigate("showcase/classes/$selectedClassId/attendance")
                                },
                                onOpenScores = { selectedClassId, subjectId ->
                                    val termId = session.academicPeriod.termId
                                    scoresViewModel.openSheet(selectedClassId, subjectId, termId)
                                    navController.navigate("showcase/scores/$selectedClassId/$subjectId/${termId ?: 0}")
                                },
                                onOpenSchedule = { selectedClassId ->
                                    scheduleViewModel.load(classId = selectedClassId)
                                    navController.navigate("showcase/schedule/$selectedClassId")
                                },
                                onLoadMoreStudents = classesViewModel::loadMoreStudents,
                                onRetry = { classesViewModel.openClass(classId) },
                            )
                        }
                        composable(
                            ShowcaseStaffRoute.STUDENT_PROFILE,
                            arguments = listOf(
                                navArgument("classId") { type = NavType.LongType },
                                navArgument("studentId") { type = NavType.LongType },
                            ),
                        ) { StudentProfileScreen(classesState, navController::popBackStack) }
                        composable(
                            ShowcaseStaffRoute.ATTENDANCE,
                            arguments = listOf(navArgument("classId") { type = NavType.LongType }),
                        ) {
                            AttendanceScreen(
                                classesState,
                                online,
                                navController::popBackStack,
                                classesViewModel::updateAttendanceStatus,
                                classesViewModel::markAllPresent,
                                classesViewModel::discardAttendanceDraft,
                                classesViewModel::submitAttendance,
                            )
                        }
                        composable(ShowcaseStaffRoute.STAFF_ATTENDANCE) {
                            StaffAttendanceScreen(
                                classesState,
                                online,
                                navController::popBackStack,
                                classesViewModel::loadStaffAttendance,
                                classesViewModel::clockIn,
                                classesViewModel::clockOut,
                            )
                        }
                        composable(ShowcaseStaffRoute.SCORES) {
                            ScoreAssignmentsScreen(
                                scoresState,
                                scoresViewModel::setSearch,
                                { assignment, termId ->
                                    scoresViewModel.openSheet(assignment.classId, assignment.subjectId, termId)
                                    navController.navigate("showcase/scores/${assignment.classId}/${assignment.subjectId}/${termId ?: 0}")
                                },
                                scoresViewModel::loadAssignments,
                            )
                        }
                        composable(
                            ShowcaseStaffRoute.SCORE_SHEET,
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
                                scoresState,
                                navController::popBackStack,
                                scoresViewModel::updateScore,
                                scoresViewModel::discardDraft,
                                scoresViewModel::submit,
                                { scoresViewModel.openSheet(classId, subjectId, termId) },
                            )
                        }
                        composable(
                            ShowcaseStaffRoute.SCHEDULE,
                            arguments = listOf(navArgument("classId") { type = NavType.LongType }),
                        ) { entry ->
                            val classId = requireNotNull(entry.arguments).getLong("classId").takeIf { it > 0 }
                            ScheduleScreen(
                                scheduleState,
                                navController::popBackStack,
                                scheduleViewModel::selectSection,
                                scheduleViewModel::selectDay,
                                { scheduleViewModel.load(classId = classId) },
                            )
                        }
                        composable(ShowcaseStaffRoute.REPOSITORY) {
                            AcademicRepositoryScreen(
                                academicContentState,
                                navController::popBackStack,
                                academicContentViewModel::setQuery,
                                academicContentViewModel::submitSearch,
                                academicContentViewModel::selectClass,
                                academicContentViewModel::selectTerm,
                                academicContentViewModel::selectSubject,
                                { resourceId ->
                                    academicContentViewModel.openResource(resourceId)
                                    navController.navigate("showcase/repository/$resourceId")
                                },
                                academicContentViewModel::loadMoreResources,
                                academicContentViewModel::loadRepository,
                                academicContentViewModel::consumeDocument,
                            )
                        }
                        composable(
                            ShowcaseStaffRoute.REPOSITORY_RESOURCE,
                            arguments = listOf(navArgument("resourceId") { type = NavType.LongType }),
                        ) { entry ->
                            val resourceId = requireNotNull(entry.arguments).getLong("resourceId")
                            AcademicResourceDetailScreen(
                                academicContentState,
                                navController::popBackStack,
                                academicContentViewModel::downloadResource,
                                { academicContentViewModel.openResource(resourceId) },
                                academicContentViewModel::consumeDocument,
                            )
                        }
                        composable(ShowcaseStaffRoute.LESSON_PLANS) {
                            LessonPlannerListScreen(
                                academicContentState,
                                navController::popBackStack,
                                {
                                    academicContentViewModel.newLesson()
                                    navController.navigate("showcase/lesson-plans/0")
                                },
                                { planId ->
                                    academicContentViewModel.openLesson(planId)
                                    navController.navigate("showcase/lesson-plans/$planId")
                                },
                                academicContentViewModel::loadLessons,
                            )
                        }
                        composable(
                            ShowcaseStaffRoute.LESSON_EDITOR,
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
                                onGenerateNote = academicContentViewModel::generateNote,
                                onUpdateNote = academicContentViewModel::updateNote,
                                onPublish = academicContentViewModel::publishLesson,
                                onDownloadPlan = { academicContentViewModel.downloadLesson(false) },
                                onDownloadNote = { academicContentViewModel.downloadLesson(true) },
                                onDocumentOpened = academicContentViewModel::consumeDocument,
                            )
                        }
                        composable(
                            ShowcaseStaffRoute.MESSAGE_THREAD,
                            arguments = listOf(navArgument("threadId") { type = NavType.LongType }),
                        ) { entry ->
                            val threadId = requireNotNull(entry.arguments).getLong("threadId")
                            MessageThreadScreen(
                                communicationState,
                                {
                                    communicationViewModel.loadMessages()
                                    navController.popBackStack()
                                },
                                communicationViewModel::setReplyBody,
                                communicationViewModel::reply,
                                communicationViewModel::loadAttachment,
                                communicationViewModel::clearAttachment,
                                communicationViewModel::download,
                                { communicationViewModel.openThread(threadId) },
                                communicationViewModel::consumeDocument,
                            )
                        }
                        composable(ShowcaseStaffRoute.COMPOSE_MESSAGE) {
                            ComposeMessageScreen(
                                communicationState,
                                navController::popBackStack,
                                communicationViewModel::selectRecipient,
                                communicationViewModel::setComposeSubject,
                                communicationViewModel::setComposeBody,
                                communicationViewModel::loadAttachment,
                                communicationViewModel::clearAttachment,
                                communicationViewModel::compose,
                                { threadId ->
                                    navController.navigate("showcase/inbox/messages/$threadId") {
                                        popUpTo(ShowcaseStaffRoute.COMPOSE_MESSAGE) { inclusive = true }
                                    }
                                },
                                communicationViewModel::prepareCompose,
                            )
                        }
                        composable(ShowcaseStaffRoute.PROFILE) {
                            StaffProfileScreen(session, navController::popBackStack)
                        }
                        composable(
                            ShowcaseStaffRoute.OPERATIONS,
                            arguments = listOf(navArgument("module") { type = NavType.StringType }),
                        ) { entry ->
                            val module = requireNotNull(entry.arguments).getString("module").orEmpty()
                            OperationsScreen(
                                operationsState,
                                width,
                                navController::popBackStack,
                                operationsViewModel::setQuery,
                                operationsViewModel::selectSection,
                                { operationsViewModel.load(module) },
                            )
                        }
                    }
                }
            }
        }
    }
}

private enum class ShowcaseStaffTab(val route: String, val label: String, val icon: ImageVector) {
    HOME("showcase/home", "Home", Icons.Default.Home),
    CLASSES("showcase/classes", "Classes", Icons.Default.School),
    TIMETABLE("showcase/timetable", "Timetable", Icons.Default.Schedule),
    INBOX("showcase/inbox", "Inbox", Icons.Default.Notifications),
    MORE("showcase/more", "More", Icons.Default.MoreHoriz),
}

private object ShowcaseStaffRoute {
    const val CLASS_WORKSPACE = "showcase/classes/{classId}"
    const val STUDENT_PROFILE = "showcase/classes/{classId}/students/{studentId}"
    const val ATTENDANCE = "showcase/classes/{classId}/attendance"
    const val STAFF_ATTENDANCE = "showcase/staff-attendance"
    const val SCORES = "showcase/scores"
    const val SCORE_SHEET = "showcase/scores/{classId}/{subjectId}/{termId}"
    const val SCHEDULE = "showcase/schedule/{classId}"
    const val REPOSITORY = "showcase/repository"
    const val REPOSITORY_RESOURCE = "showcase/repository/{resourceId}"
    const val LESSON_PLANS = "showcase/lesson-plans"
    const val LESSON_EDITOR = "showcase/lesson-plans/{lessonPlanId}"
    const val MESSAGE_THREAD = "showcase/inbox/messages/{threadId}"
    const val COMPOSE_MESSAGE = "showcase/inbox/compose"
    const val PROFILE = "showcase/profile"
    const val OPERATIONS = "showcase/operations/{module}"

    val GENERIC_OPERATIONS = setOf(
        "fees", "expenses", "payroll", "admissions", "library", "transport", "health", "inventory",
        "hostels", "subjects", "curriculum", "academic-cycle", "staff", "analytics", "exports",
    )
}

private fun showcaseSelectedRoot(route: String): ShowcaseStaffTab = when {
    route.startsWith("showcase/classes") || route.startsWith("showcase/scores") -> ShowcaseStaffTab.CLASSES
    route.startsWith("showcase/timetable") || route.startsWith("showcase/schedule") -> ShowcaseStaffTab.TIMETABLE
    route.startsWith("showcase/inbox") -> ShowcaseStaffTab.INBOX
    route.startsWith("showcase/more") || route.startsWith("showcase/operations") ||
        route.startsWith("showcase/staff-attendance") || route.startsWith("showcase/repository") ||
        route.startsWith("showcase/lesson-plans") || route.startsWith("showcase/profile") -> ShowcaseStaffTab.MORE
    else -> ShowcaseStaffTab.HOME
}
