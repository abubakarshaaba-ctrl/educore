package online.educoreng.educore.presentation

import androidx.activity.compose.BackHandler
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.weight
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

/** Compatibility alias retained for the More hub call site. */
internal fun AdminStaffAttendanceViewModel.load() = loadDaily()

/**
 * Compatibility host used by the More hub. It keeps the entire administrator
 * attendance workspace native while the routed staff shell can continue to
 * call the lower-level screen directly.
 */
@Composable
internal fun AdminStaffAttendanceScreen(
    state: AdminStaffAttendanceUiState,
    onBack: () -> Unit,
    onRefresh: () -> Unit,
) {
    val viewModel: AdminStaffAttendanceViewModel = hiltViewModel()
    val qrViewModel: AdminStaffAttendanceQrViewModel = hiltViewModel()
    val qrState by qrViewModel.uiState.collectAsStateWithLifecycle()
    var qrOpen by remember { mutableStateOf(false) }

    BackHandler {
        if (qrOpen) qrOpen = false else onBack()
    }

    if (qrOpen) {
        AdminStaffAttendanceQrScreen(
            state = qrState,
            onRefresh = qrViewModel::load,
            onReset = qrViewModel::resetQr,
            onClose = { qrOpen = false },
        )
        return
    }

    Column(
        modifier = Modifier.fillMaxSize(),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs),
    ) {
        OutlinedButton(
            onClick = {
                qrOpen = true
                qrViewModel.load()
            },
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = eduCoreScreenPadding(), vertical = EduCoreSpacing.Xs),
        ) {
            Text("School attendance QR")
        }

        Box(Modifier.weight(1f)) {
            AdminStaffAttendanceScreen(
                state = state,
                onSection = viewModel::selectSection,
                onDailyDate = viewModel::setDailyDate,
                onDailyQuery = viewModel::setDailyQuery,
                onDailyStatus = viewModel::setDailyStatus,
                onReportMonth = viewModel::setReportMonth,
                onReportYear = viewModel::setReportYear,
                onRefreshDaily = viewModel::loadDaily,
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
}
