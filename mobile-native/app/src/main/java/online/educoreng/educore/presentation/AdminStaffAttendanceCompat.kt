package online.educoreng.educore.presentation

import androidx.activity.compose.BackHandler
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle

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
    val qrViewModel: AdminStaffAttendanceQrViewModel = hiltViewModel()
    val qrState by qrViewModel.uiState.collectAsStateWithLifecycle()
    var showSchoolQr by rememberSaveable { mutableStateOf(false) }

    BackHandler {
        if (showSchoolQr) {
            showSchoolQr = false
        } else {
            onBack()
        }
    }

    if (showSchoolQr) {
        AdminStaffAttendanceQrScreen(
            state = qrState,
            onRefresh = qrViewModel::load,
            onReset = qrViewModel::resetQr,
            onClose = { showSchoolQr = false },
        )
        return
    }

    Column(Modifier.fillMaxSize()) {
        OutlinedButton(
            onClick = {
                showSchoolQr = true
                qrViewModel.load()
            },
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 6.dp),
        ) {
            Text("School attendance QR")
        }

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
}
