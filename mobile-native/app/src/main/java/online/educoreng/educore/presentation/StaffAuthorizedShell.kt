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
 * Phase-B staff shell.
 *
 * The staff experience is task-oriented instead of being a web-module launcher:
 * Home -> Classes -> Timetable -> Inbox -> More.
 * Existing native feature screens are reused so the overhaul does not discard the
 * API/repository work that is already production-tested.
 */
@Composable
internal fun StaffAuthorizedShell(
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
    val currentRoute = backStackEntry?.destination?.route ?: StaffRootTab.HOME.route
    val selectedRoot = selectedStaffRoot(currentRoute)

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
    LaunchedEffect(communicationState.message) {
        communicationState.message?.let { message ->
            snackbarHostState.showSnackbar(message)
            communicationViewModel.consumeMessage()
        }
    }

    val rootItems = StaffRootTab.entries.map { tab ->
        EduCoreNavigationItem(
            key = tab.route,
            label = tab.label,
            icon = tab.icon,
            badgeCount = if (tab == StaffRootTab.INBOX) {
                communicationState.unreadNotifications + (communicationState.messagePage?.unreadCount ?: 0)
            } else 0,
        )
    }

    fun navigateRoot(tab: StaffRootTab) {
        navController.navigate(tab.route) {
            popUpTo(StaffRootTab.HOME.route) { saveState = true }
            launchSingleTop = true
            restoreState = true
        }
    }

    fun unavailable(module: ModuleDescriptor, detail: String = "is being completed as a native EduCore workspace") {
        scope.launch {
            snackbarHostState.showSnackbar("${module.title} $detail. It will not open in the browser.")
        }
    }

    val onModuleClick: (ModuleDescriptor) -> Unit = { module ->
        when (module.key.lowercase()) {
            "classes", "students", "attendance" -> {
                classesViewModel.loadClasses()
                navigateRoot(StaffRootTab.CLASSES)
            }
            "staff-attendance", "staff-attendance.self" -> {
                classesViewModel.loadStaffAttendance()
                navController.navigate(StaffRoute.STAFF_ATTENDANCE) { launchSingleTop = true }
            }
            "scores", "scores.entry" -> {
                if (session.can("scores") || session.can("scores.entry")) {
                    scoresViewModel.loadAssignments()
                    navController.navigate(StaffRoute.SCORES) { launchSingleTop = true }
                } else {
                    unavailable(module, "is not available to this role")
                }
            }
            "timetable", "student.timetable" -> {
                scheduleViewModel.load()
                navigateRoot(StaffRootTab.TIMETABLE)
            }
            "academic-repository" -> {
                academicContentViewModel.loadRepository()
                navController.navigate(StaffRoute.REPOSITORY) { launchSingleTop = true }
            }
            "lesson-planner" -> {
                academicContentViewModel.loadLessons()
                navController.navigate(StaffRoute.LESSON_PLANS) { launchSingleTop = true }
            }
            "messages" -> {
                communicationViewModel.selectTab(CommunicationTab.MESSAGES.ordinal)
                communicationViewModel.loadAll()
                navigateRoot(StaffRootTab.INBOX)
            }
            "notifications.view", "announcements" -> {
                communicationViewModel.selectTab(CommunicationTab.NOTICES.ordinal)
                communicationViewModel.loadAll()
                navigateRoot(StaffRootTab.INBOX)
            }
            "calendar.view" -> {
                communicationViewModel.selectTab(CommunicationTab.EVENTS.ordinal)
                communicationViewModel.loadAll()
                navigateRoot(StaffRootTab.INBOX)
            }
            in StaffRoute.GENERIC_OPERATIONS -> {
                operationsViewModel.load(module.key)
                navController.navigate("staff/operations/${module.key}") { launchSingleTop = true }
            }
            "reports", "report-cards", "results" -> unavailable(module)
            "cbt", "cbt-exams", "examinations" -> unavailable(module)
            "profile" -> unavailable(module)
            else -> when (ModulePresentationPolicy.presentationFor(module.key)) {
                ModulePresentation.NATIVE_GENERIC -> {
                    operationsViewModel.load(module.key)
                    navController.navigate("staff/operations/${module.key}") { launchSingleTop = true }
                }
                else -> unavailable(module)
            }
        }
    }

    EduCoreAdaptiveLayout(Modifier.fillMaxSize()) { width ->
        val showShellTopBar = currentRoute in setOf(
            StaffRootTab.HOME.route,
            StaffRootTab.CLASSES.route,
            StaffRootTab.MORE.route,
        )

        Scaffold(
            containerColor = EduCoreColors.Page50,
            topBar = {
                if (showShellTopBar) {
                    Column {
                        EduCoreTopAppBar(
                            title = when (currentRoute) {
                                StaffRootTab.HOME.route -> "Home"
                                StaffRootTab.CLASSES.route -> "Classes"
                                else -> "More"
                            },
                            subtitle = listOfNotNull(
                                session.school.name,
                                session.academicPeriod.termName,
                            ).joinToString(" · "),
                            actions = {
                                IconButton(onClick = onRefresh, enabled = !busy && online) {
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
                            StaffRootTab.entries.firstOrNull { it.route == item.key }?.let(::navigateRoot)
                        },
                    )
                }
            },
        ) { contentPadding ->
            Row(Modifier.fillMaxSize().padding(contentPadding)) {
                if (width != EduCoreWindowWidth.Compact) {
                    NavigationRail(containerColor = EduCoreColors.White) {
                        StaffRootTab.entries.forEach { tab ->
                            val unread = if (tab == StaffRootTab.INBOX) {
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

                Box(
                    modifier = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.TopCenter,
                ) {
                    NavHost(
                        navController = navController,
                        startDestination = StaffRootTab.HOME.route,
                        modifier = Modifier.fillMaxSize().widthIn(max = 1180.dp),
                    ) {
                        composable(StaffRootTab.HOME.route) {
                            StaffHomeTab(
                                session = session,
                                dashboard = dashboard,
                                width = width,
                                onModuleClick = onModuleClick,
                                onRetry = onRefreshDashboard,
                            )
                        }
                        composable(StaffRootTab.CLASSES.route) {
                            LaunchedEffect(Unit) { classesViewModel.loadClasses() }
                            ClassesListScreen(
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
                        composable(StaffRootTab.TIMETABLE.route) {
                            LaunchedEffect(Unit) { scheduleViewModel.load() }
                            ScheduleScreen(
                                state = scheduleState,
                                onBack = { navigateRoot(StaffRootTab.HOME) },
                                onSection = scheduleViewModel::selectSection,
                                onDay = scheduleViewModel::selectDay,
                                onRetry = { scheduleViewModel.load() },
                            )
                        }
                        composable(StaffRootTab.INBOX.route) {
                            LaunchedEffect(Unit) { communicationViewModel.loadAll() }
                            CommunicationCenterScreen(
                                state = communicationState,
                                onBack = { navigateRoot(StaffRootTab.HOME) },
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
                                    navController.navigate(StaffRoute.COMPOSE_MESSAGE)
                                },
                                onRetry = communicationViewModel::loadAll,
                            )
                        }
                        composable(StaffRootTab.MORE.route) {
                            StaffMoreScreen(
                                session = session,
                                width = width,
                                onModuleClick = onModuleClick,
                                onLogout = onLogout,
                            )
                        }

                        composable(
                            route = StaffRoute.CLASS_WORKSPACE,
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
                            route = StaffRoute.STUDENT_PROFILE,
                            arguments = listOf(
                                navArgument("classId") { type = NavType.LongType },
                                navArgument("studentId") { type = NavType.LongType },
                            ),
                        ) {
                            StudentProfileScreen(classesState, navController::popBackStack)
                        }
                        composable(
                            route = StaffRoute.ATTENDANCE,
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
                        composable(StaffRoute.STAFF_ATTENDANCE) {
                            StaffAttendanceScreen(
                                state = classesState,
                                online = online,
                                onBack = navController::popBackStack,
                                onRefresh = classesViewModel::loadStaffAttendance,
                                onClockIn = classesViewModel::clockIn,
                                onClockOut = classesViewModel::clockOut,
                            )
                        }
                        composable(StaffRoute.SCORES) {
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
                            route = StaffRoute.SCORE_SHEET,
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
                        composable(
                            route = StaffRoute.SCHEDULE,
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
                        composable(StaffRoute.REPOSITORY) {
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
                                    navController.navigate("staff/repository/$resourceId")
                                },
                                onRetry = academicContentViewModel::loadRepository,
                                onDocumentOpened = academicContentViewModel::consumeDocument,
                            )
                        }
                        composable(
                            route = StaffRoute.REPOSITORY_RESOURCE,
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
                        composable(StaffRoute.LESSON_PLANS) {
                            LessonPlannerListScreen(
                                state = academicContentState,
                                onBack = navController::popBackStack,
                                onNew = {
                                    academicContentViewModel.newLesson()
                                    navController.navigate("staff/lesson-plans/0")
                                },
                                onOpen = { planId ->
                                    academicContentViewModel.openLesson(planId)
                                    navController.navigate("staff/lesson-plans/$planId")
                                },
                                onRetry = academicContentViewModel::loadLessons,
                            )
                        }
                        composable(
                            route = StaffRoute.LESSON_EDITOR,
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
                        composable(
                            route = StaffRoute.MESSAGE_THREAD,
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
                        composable(StaffRoute.COMPOSE_MESSAGE) {
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
                                        popUpTo(StaffRoute.COMPOSE_MESSAGE) { inclusive = true }
                                    }
                                },
                                onRetry = communicationViewModel::prepareCompose,
                            )
                        }
                        composable(
                            route = StaffRoute.OPERATIONS,
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
private fun StaffHomeTab(
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
        dashboardHomeContent(
            session = session,
            state = dashboard,
            width = width,
            onModuleClick = onModuleClick,
            onRetry = onRetry,
        )
    }
}

@Composable
private fun StaffMoreScreen(
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
    val modules = remember(session.modules) {
        session.modules
            .filterNot { it.key.lowercase() in StaffRoute.ROOT_OWNED_MODULES }
            .distinctBy { canonicalMoreKey(it.key) }
    }

    LazyVerticalGrid(
        columns = GridCells.Fixed(columns),
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = androidx.compose.foundation.layout.PaddingValues(eduCoreScreenPadding()),
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item(key = "profile", span = { GridItemSpan(maxLineSpan) }) {
            EduCoreProfileHeader(
                name = session.user.name,
                role = session.user.roleLabel,
                identifier = session.user.staffId ?: session.user.email,
                modifier = Modifier.fillMaxWidth(),
            )
        }
        item(key = "more-header", span = { GridItemSpan(maxLineSpan) }) {
            EduCoreSectionHeader(
                title = "More tools",
                supportingText = "Secondary modules available to your role",
            )
        }
        if (modules.isEmpty()) {
            item(span = { GridItemSpan(maxLineSpan) }) {
                EduCoreEmptyState(
                    title = "No additional tools",
                    message = "Your main workspaces are already available from the navigation bar.",
                )
            }
        } else {
            items(modules, key = ModuleDescriptor::key) { module ->
                val presentation = ModulePresentationPolicy.presentationFor(module.key)
                EduCoreModuleCard(
                    title = module.title,
                    subtitle = staffModuleSubtitle(module.key),
                    icon = staffModuleIcon(module.key),
                    badge = when {
                        module.key.lowercase() in StaffRoute.PHASE_C_MODULES -> "In progress"
                        presentation == ModulePresentation.NATIVE_GENERIC -> "Native"
                        else -> null
                    },
                    onClick = { onModuleClick(module) },
                    modifier = Modifier.fillMaxWidth(),
                )
            }
        }
        item(key = "logout", span = { GridItemSpan(maxLineSpan) }) {
            EduCorePrimaryButton(
                text = "Sign out",
                onClick = onLogout,
                modifier = Modifier.fillMaxWidth(),
                leadingIcon = { Icon(Icons.AutoMirrored.Filled.Logout, contentDescription = null) },
            )
        }
    }
}

private enum class StaffRootTab(
    val route: String,
    val label: String,
    val icon: ImageVector,
) {
    HOME("staff/home", "Home", Icons.Default.Home),
    CLASSES("staff/classes", "Classes", Icons.Default.School),
    TIMETABLE("staff/timetable", "Timetable", Icons.Default.Schedule),
    INBOX("staff/inbox", "Inbox", Icons.Default.Notifications),
    MORE("staff/more", "More", Icons.Default.MoreHoriz),
}

private object StaffRoute {
    const val CLASS_WORKSPACE = "staff/classes/{classId}"
    const val STUDENT_PROFILE = "staff/classes/{classId}/students/{studentId}"
    const val ATTENDANCE = "staff/classes/{classId}/attendance"
    const val STAFF_ATTENDANCE = "staff/staff-attendance"
    const val SCORES = "staff/scores"
    const val SCORE_SHEET = "staff/scores/{classId}/{subjectId}/{termId}"
    const val SCHEDULE = "staff/schedule/{classId}"
    const val REPOSITORY = "staff/repository"
    const val REPOSITORY_RESOURCE = "staff/repository/{resourceId}"
    const val LESSON_PLANS = "staff/lesson-plans"
    const val LESSON_EDITOR = "staff/lesson-plans/{lessonPlanId}"
    const val MESSAGE_THREAD = "staff/inbox/messages/{threadId}"
    const val COMPOSE_MESSAGE = "staff/inbox/compose"
    const val OPERATIONS = "staff/operations/{module}"

    val ROOT_OWNED_MODULES = setOf(
        "dashboard",
        "classes",
        "students",
        "attendance",
        "scores",
        "scores.entry",
        "timetable",
        "messages",
        "notifications.view",
        "announcements",
        "calendar.view",
    )

    val GENERIC_OPERATIONS = setOf(
        "fees",
        "expenses",
        "payroll",
        "admissions",
        "library",
        "transport",
        "health",
        "inventory",
        "hostels",
        "subjects",
        "curriculum",
        "academic-cycle",
    )

    val PHASE_C_MODULES = setOf(
        "reports",
        "report-cards",
        "results",
        "cbt",
        "cbt-exams",
        "examinations",
        "profile",
    )
}

private fun selectedStaffRoot(route: String): StaffRootTab = when {
    route.startsWith("staff/classes") || route.startsWith("staff/scores") -> StaffRootTab.CLASSES
    route.startsWith("staff/timetable") || route.startsWith("staff/schedule") -> StaffRootTab.TIMETABLE
    route.startsWith("staff/inbox") -> StaffRootTab.INBOX
    route.startsWith("staff/more") || route.startsWith("staff/operations") ||
        route.startsWith("staff/staff-attendance") || route.startsWith("staff/repository") ||
        route.startsWith("staff/lesson-plans") -> StaffRootTab.MORE
    else -> StaffRootTab.HOME
}

private fun canonicalMoreKey(key: String): String = when (key.lowercase()) {
    "staff-attendance.self" -> "staff-attendance"
    else -> key.lowercase()
}

private fun staffModuleSubtitle(key: String): String? = when (key.lowercase()) {
    "staff-attendance", "staff-attendance.self" -> "Clock in, clock out and review attendance"
    "reports", "report-cards", "results" -> "Generate and review student report cards"
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

private fun staffModuleIcon(key: String): ImageVector = when {
    key.contains("attendance", ignoreCase = true) -> EduCoreIcons.Attendance
    key.contains("report", ignoreCase = true) || key.contains("result", ignoreCase = true) ||
        key.contains("score", ignoreCase = true) -> EduCoreIcons.Scores
    key.contains("cbt", ignoreCase = true) || key.contains("exam", ignoreCase = true) -> EduCoreIcons.ExamDuties
    key.contains("lesson", ignoreCase = true) -> EduCoreIcons.LessonPlan
    key.contains("repository", ignoreCase = true) -> EduCoreIcons.Repository
    key.contains("subject", ignoreCase = true) || key.contains("curriculum", ignoreCase = true) -> EduCoreIcons.Subjects
    key.contains("fee", ignoreCase = true) || key.contains("payment", ignoreCase = true) -> EduCoreIcons.Payments
    key.contains("profile", ignoreCase = true) || key.contains("setting", ignoreCase = true) -> EduCoreIcons.Settings
    else -> EduCoreIcons.Modules
}
