package online.educoreng.educore.presentation

import androidx.compose.runtime.Composable
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import online.educoreng.educore.core.data.repository.ScoreWorkspaceRepository

/**
 * Temporary source-compatibility adapters for stale, unreachable mobile routes.
 *
 * CBT and report/result modules are excluded by ModulePresentationPolicy. These
 * adapters keep older shell code compilable while the remaining dead routes are
 * removed incrementally, without re-exposing removed modules to users.
 */
internal suspend fun ScoreWorkspaceRepository.loadStudentResults(
    classId: Long,
    studentId: Long,
) = loadPublishedResults(studentId)

internal fun ClassesViewModel.clearClassSelection() = Unit

@Composable
internal fun CommunicationCenterScreen(
    state: CommunicationUiState,
    onBack: () -> Unit,
    onTab: (Int) -> Unit,
    onNoticeFilter: (String) -> Unit,
    onMarkRead: (Long) -> Unit,
    onMarkAllRead: () -> Unit,
    onOpenThread: (Long) -> Unit,
    onCompose: () -> Unit,
    onRetry: () -> Unit,
) {
    CommunicationCenterScreen(
        state = state,
        canCreateEvent = false,
        onBack = onBack,
        onTab = onTab,
        onNoticeFilter = onNoticeFilter,
        onMarkRead = onMarkRead,
        onMarkAllRead = onMarkAllRead,
        onOpenThread = onOpenThread,
        onCompose = onCompose,
        onEventTitle = {},
        onEventDescription = {},
        onEventStartDate = {},
        onEventEndDate = {},
        onEventAudience = {},
        onCreateEvent = {},
        onRetry = onRetry,
    )
}

@Composable
internal fun StaffAttendanceScreen(
    state: ClassesUiState,
    online: Boolean,
    onBack: () -> Unit,
    onRefresh: () -> Unit,
    onClockIn: (String, Double?, Double?) -> Unit,
    onClockOut: () -> Unit,
) {
    val classesViewModel: ClassesViewModel = hiltViewModel()
    StaffAttendanceScreen(
        state = state,
        online = online,
        onBack = onBack,
        onRefresh = onRefresh,
        onClockIn = onClockIn,
        onClockOut = onClockOut,
        onProxySearch = classesViewModel::setProxySearch,
        onLoadProxyColleagues = { classesViewModel.loadProxyColleagues() },
        onProxyClockIn = classesViewModel::proxyClockIn,
    )
}
