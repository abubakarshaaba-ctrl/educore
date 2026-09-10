package online.educoreng.educore.presentation

import android.Manifest
import android.content.Context
import android.content.pm.PackageManager
import android.location.Location
import android.location.LocationManager
import android.os.Build
import androidx.activity.compose.BackHandler
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.core.content.ContextCompat
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

/** Compatibility alias retained for existing module-hub call sites. */
internal fun AdminStaffAttendanceViewModel.load() = loadDaily()

/**
 * Full native administrator attendance workspace.
 *
 * This host deliberately owns one route-scoped attendance ViewModel so the
 * daily report, monthly report, reviews, settings, geofence capture and QR
 * controls all mutate the same observable state.
 */
@Composable
internal fun AdminStaffAttendanceScreen(
    state: AdminStaffAttendanceUiState,
    onBack: () -> Unit,
    onRefresh: () -> Unit,
) {
    val context = LocalContext.current
    val viewModel: AdminStaffAttendanceViewModel = hiltViewModel()
    val liveState by viewModel.uiState.collectAsStateWithLifecycle()
    val qrViewModel: AdminStaffAttendanceQrViewModel = hiltViewModel()
    val qrState by qrViewModel.uiState.collectAsStateWithLifecycle()
    var qrOpen by remember { mutableStateOf(false) }
    var locating by remember { mutableStateOf(false) }
    var locationMessage by remember { mutableStateOf<String?>(null) }

    // The argument is retained for source compatibility with existing callers.
    // Once this routed host is mounted, its route-scoped state is authoritative.
    @Suppress("UNUSED_VARIABLE")
    val initialState = state

    fun saveLocation(location: Location?) {
        locating = false
        if (location == null) {
            locationMessage = "Current location could not be determined. Turn on Location and try again."
            return
        }
        val settings = liveState.snapshot?.settings
        if (settings == null) {
            locationMessage = "Load attendance settings before setting the school location."
            viewModel.loadDaily()
            return
        }
        val resumptionTime = settings.resumptionTime
        val closingTime = settings.closingTime
        if (resumptionTime.isNullOrBlank() || closingTime.isNullOrBlank()) {
            locationMessage = "Set valid resumption and closing times before capturing the school location."
            return
        }
        viewModel.saveSettings(
            resumption = resumptionTime,
            grace = settings.graceMinutes,
            closing = closingTime,
            geoEnabled = true,
            lat = location.latitude,
            lng = location.longitude,
            radius = settings.geoRadiusMeters ?: 500,
        )
        locationMessage = "School location captured: %.6f, %.6f".format(location.latitude, location.longitude)
    }

    fun captureCurrentLocation() {
        val manager = context.getSystemService(Context.LOCATION_SERVICE) as LocationManager
        locating = true
        locationMessage = null
        runCatching {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
                val provider = when {
                    manager.isProviderEnabled(LocationManager.GPS_PROVIDER) -> LocationManager.GPS_PROVIDER
                    manager.isProviderEnabled(LocationManager.NETWORK_PROVIDER) -> LocationManager.NETWORK_PROVIDER
                    else -> null
                }
                if (provider == null) {
                    saveLocation(null)
                } else {
                    manager.getCurrentLocation(provider, null, context.mainExecutor, ::saveLocation)
                }
            } else {
                @Suppress("DEPRECATION")
                val location = listOf(LocationManager.GPS_PROVIDER, LocationManager.NETWORK_PROVIDER)
                    .mapNotNull { provider -> runCatching { manager.getLastKnownLocation(provider) }.getOrNull() }
                    .maxByOrNull { it.time }
                saveLocation(location)
            }
        }.onFailure {
            locating = false
            locationMessage = "Unable to read the device location. Check Location permission and try again."
        }
    }

    val locationPermission = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions(),
    ) { grants ->
        if (grants.values.any { it }) captureCurrentLocation()
        else locationMessage = "Location permission is required to capture the school coordinates."
    }

    LaunchedEffect(Unit) {
        if (liveState.snapshot == null) viewModel.loadDaily()
    }

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

        if (liveState.section == AdminAttendanceSection.SETTINGS) {
            OutlinedButton(
                onClick = {
                    val fine = ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED
                    val coarse = ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED
                    if (fine || coarse) {
                        captureCurrentLocation()
                    } else {
                        locationPermission.launch(
                            arrayOf(
                                Manifest.permission.ACCESS_FINE_LOCATION,
                                Manifest.permission.ACCESS_COARSE_LOCATION,
                            )
                        )
                    }
                },
                enabled = !locating && !liveState.isMutating,
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = eduCoreScreenPadding()),
            ) {
                Text(if (locating) "Getting present location…" else "Use present location")
            }
            locationMessage?.let { message ->
                Text(
                    text = message,
                    modifier = Modifier.padding(horizontal = eduCoreScreenPadding()),
                )
            }
        }

        Box(Modifier.weight(1f)) {
            AdminStaffAttendanceScreen(
                state = liveState,
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
