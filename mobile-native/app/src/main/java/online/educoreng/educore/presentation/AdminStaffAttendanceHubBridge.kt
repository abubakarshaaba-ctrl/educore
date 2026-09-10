package online.educoreng.educore.presentation

import android.Manifest
import android.annotation.SuppressLint
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
import androidx.compose.material3.MaterialTheme
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
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

/** Compatibility alias retained for existing module-hub call sites. */
internal fun AdminStaffAttendanceViewModel.load() = loadDaily()

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
    var capturedLocation by remember { mutableStateOf<Location?>(null) }

    @Suppress("UNUSED_VARIABLE")
    val initialState = state

    fun hasLocationPermission(): Boolean =
        ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED ||
            ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED

    fun persistCapturedLocation(location: Location) {
        val settings = liveState.snapshot?.settings
        if (settings == null) {
            capturedLocation = location
            locationMessage = "Location captured. Loading attendance settings…"
            viewModel.loadDaily()
            return
        }
        val resumptionTime = settings.resumptionTime
        val closingTime = settings.closingTime
        if (resumptionTime.isNullOrBlank() || closingTime.isNullOrBlank()) {
            capturedLocation = location
            locationMessage = "Location captured, but attendance times must be configured before the school location can be saved."
            return
        }
        viewModel.saveSettings(
            resumption = resumptionTime,
            grace = settings.graceMinutes,
            closing = closingTime,
            geoEnabled = true,
            lat = location.latitude,
            lng = location.longitude,
            radius = settings.geoRadiusMeters?.takeIf { it > 0 } ?: 500,
        )
        capturedLocation = null
        val accuracy = if (location.hasAccuracy()) " · accuracy ±${location.accuracy.toInt()} m" else ""
        locationMessage = "Location captured: %.6f, %.6f%s".format(location.latitude, location.longitude, accuracy)
    }

    fun acceptLocation(location: Location?) {
        locating = false
        if (location == null) {
            locationMessage = "Unable to determine location. Ensure GPS or network location is available and try again."
            return
        }
        persistCapturedLocation(location)
    }

    @SuppressLint("MissingPermission")
    fun captureCurrentLocation() {
        if (!hasLocationPermission()) {
            locating = false
            locationMessage = "Location permission denied. Allow location access to capture the school coordinates."
            return
        }

        val manager = context.getSystemService(Context.LOCATION_SERVICE) as LocationManager
        val locationEnabled = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
            manager.isLocationEnabled
        } else {
            @Suppress("DEPRECATION")
            manager.isProviderEnabled(LocationManager.GPS_PROVIDER) || manager.isProviderEnabled(LocationManager.NETWORK_PROVIDER)
        }
        if (!locationEnabled) {
            locating = false
            locationMessage = "Location services disabled. Turn on device Location and try again."
            return
        }

        locating = true
        locationMessage = "Getting location…"
        runCatching {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
                val provider = when {
                    manager.isProviderEnabled(LocationManager.GPS_PROVIDER) -> LocationManager.GPS_PROVIDER
                    manager.isProviderEnabled(LocationManager.NETWORK_PROVIDER) -> LocationManager.NETWORK_PROVIDER
                    else -> null
                }
                if (provider == null) {
                    locating = false
                    locationMessage = "Network/GPS unavailable. Enable a location provider and try again."
                } else {
                    manager.getCurrentLocation(provider, null, context.mainExecutor, ::acceptLocation)
                }
            } else {
                @Suppress("DEPRECATION")
                val location = listOf(LocationManager.GPS_PROVIDER, LocationManager.NETWORK_PROVIDER)
                    .filter { runCatching { manager.isProviderEnabled(it) }.getOrDefault(false) }
                    .mapNotNull { provider -> runCatching { manager.getLastKnownLocation(provider) }.getOrNull() }
                    .maxByOrNull { it.time }
                acceptLocation(location)
            }
        }.onFailure {
            locating = false
            locationMessage = "Unable to determine location. Check GPS/network availability and try again."
        }
    }

    val locationPermission = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions(),
    ) { grants ->
        if (grants.values.any { it } && hasLocationPermission()) {
            captureCurrentLocation()
        } else {
            locating = false
            locationMessage = "Location permission denied. Allow location access to use the present school location."
        }
    }

    LaunchedEffect(Unit) {
        if (liveState.snapshot == null) viewModel.loadDaily()
    }

    LaunchedEffect(liveState.snapshot?.settings, capturedLocation) {
        val pending = capturedLocation ?: return@LaunchedEffect
        if (liveState.snapshot?.settings != null && !liveState.isMutating) {
            persistCapturedLocation(pending)
        }
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
                    if (hasLocationPermission()) {
                        captureCurrentLocation()
                    } else {
                        locating = true
                        locationMessage = "Requesting location permission…"
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
                Text(if (locating) "Getting location…" else "Use present location")
            }
            locationMessage?.let { message ->
                Text(
                    text = message,
                    style = MaterialTheme.typography.bodySmall,
                    color = if (message.startsWith("Location captured")) EduCoreColors.Success700 else MaterialTheme.colorScheme.onSurfaceVariant,
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
