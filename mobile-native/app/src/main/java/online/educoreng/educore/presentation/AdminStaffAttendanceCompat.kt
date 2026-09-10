package online.educoreng.educore.presentation

import androidx.activity.compose.BackHandler
import androidx.compose.runtime.Composable
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel

/**
 * Compatibility bridge for the module hub while the school-admin attendance
 * workspace is also available as a dedicated navigation destination.
 */
fun AdminStaffAttendanceViewModel.load() = loadDaily()

@Composable
internal fun AdminStaffAttendanceScreen(
    state: AdminStaffAttendanceUiState,
    onBack: () -> Unit,
    onRefresh: () -> Unit,
) {
    val viewModel: AdminStaffAttendanceViewModel = hiltViewModel()
    BackHandler(onBack = onBack)

    AdminStaffAttendanceScreen(
        state = state,
        onSection = viewModel::selectSection,
        onDailyDate = viewModel::setDailyDate,
        onDailyQuery = viewModel::setDailyQuery,
        onDailyStatus = viewModel::setDailyStatus,
        onReportMonth = viewModel::setReportMonth,
        onReportYear = viewModel::setReportYear,
        onRefreshDaily = onRefresh,
        onRefreshReport = viewModel::loadReport,
        onRefreshReviews = viewModel::loadReviews,
        onManualOverride = viewModel::manualOverride,
        onProcessOffline = viewModel::processOffline,
        onDecideProxy = viewModel::decideProxy,
        onSaveSettings = viewModel::saveSettings,
        onResetQr = viewModel::resetQr,
    )
}
