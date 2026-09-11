package online.educoreng.educore.presentation

import android.Manifest
import android.annotation.SuppressLint
import android.content.pm.PackageManager
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.core.content.ContextCompat
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.google.android.gms.location.CurrentLocationRequest
import com.google.android.gms.location.LocationServices
import com.google.android.gms.location.Priority
import com.google.android.gms.tasks.CancellationTokenSource
import com.journeyapps.barcodescanner.ScanContract
import com.journeyapps.barcodescanner.ScanOptions
import online.educoreng.educore.PortraitCaptureActivity
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreInfoBanner
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

/**
 * Fallback real-time attendance flow for a colleague whose staff ID card is unavailable.
 * The school QR identifies the tenant; the target Staff ID identifies the colleague.
 * The server remains authoritative for tenant checks, geofence checks and timestamping.
 */
@Composable
internal fun StaffSchoolQrProxyAttendanceScreen(
    geoEnabled: Boolean,
    online: Boolean,
    onClose: () -> Unit,
) {
    val context = LocalContext.current
    val locationClient = remember(context) { LocationServices.getFusedLocationProviderClient(context) }
    val viewModel: StaffCardAttendanceScanViewModel = hiltViewModel()
    val state by viewModel.uiState.collectAsStateWithLifecycle()
    var staffId by remember { mutableStateOf("") }
    var schoolQrToken by remember { mutableStateOf<String?>(null) }
    var localError by remember { mutableStateOf<String?>(null) }
    var waitingForLocation by remember { mutableStateOf(false) }

    fun submitWithLocation(latitude: Double?, longitude: Double?, accuracy: Double?) {
        val token = schoolQrToken ?: return
        val id = staffId.trim()
        if (id.isBlank()) {
            localError = "Enter the Staff ID of the staff member whose attendance is being recorded."
            return
        }
        viewModel.scanSchoolQrFallback(token, id, latitude, longitude, accuracy)
    }

    @SuppressLint("MissingPermission")
    fun captureLocationAndSubmit() {
        val request = CurrentLocationRequest.Builder()
            .setPriority(Priority.PRIORITY_HIGH_ACCURACY)
            .setMaxUpdateAgeMillis(0)
            .setDurationMillis(20_000)
            .build()
        waitingForLocation = true
        locationClient.getCurrentLocation(request, CancellationTokenSource().token)
            .addOnSuccessListener { location ->
                waitingForLocation = false
                when {
                    location == null -> localError = "Location unavailable. Try again."
                    location.accuracy > 100f -> localError = "GPS accuracy is ${location.accuracy.toInt()} m. Try again in an open area."
                    else -> submitWithLocation(location.latitude, location.longitude, location.accuracy.toDouble())
                }
            }
            .addOnFailureListener { error ->
                waitingForLocation = false
                localError = error.localizedMessage ?: "Location verification failed."
            }
    }

    val locationPermission = rememberLauncherForActivityResult(ActivityResultContracts.RequestPermission()) { granted ->
        if (granted) captureLocationAndSubmit()
        else localError = "Location permission is required for this school's attendance verification."
    }

    val schoolQrScanner = rememberLauncherForActivityResult(ScanContract()) { result ->
        val token = result.contents
        if (!token.isNullOrBlank()) {
            schoolQrToken = token
            localError = null
            viewModel.clearMessage()
        }
    }

    Column(
        modifier = Modifier.fillMaxSize().padding(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        EduCorePageHeader(
            title = "Clock in by proxy",
            subtitle = "School QR fallback when the staff ID card is unavailable",
            onBack = onClose,
        )

        if (!online) {
            EduCoreErrorBanner("A live connection is required because EduCore records the server's real-time attendance timestamp.")
        }
        localError?.let { EduCoreErrorBanner(it) }
        state.errorMessage?.let { EduCoreErrorBanner(it) }
        state.message?.let { EduCoreInfoBanner(it) }

        OutlinedTextField(
            value = staffId,
            onValueChange = { staffId = it.take(40) },
            label = { Text("Staff ID") },
            supportingText = { Text("Enter the Staff ID printed/assigned to the target staff account.") },
            singleLine = true,
            enabled = online && !state.isSaving && !waitingForLocation,
            modifier = Modifier.fillMaxWidth(),
        )

        EduCorePrimaryButton(
            text = if (schoolQrToken == null) "Scan school attendance QR" else "School QR captured · scan again",
            onClick = {
                localError = null
                schoolQrScanner.launch(
                    ScanOptions()
                        .setDesiredBarcodeFormats(ScanOptions.QR_CODE)
                        .setPrompt("Scan the school attendance QR")
                        .setBeepEnabled(false)
                        .setCaptureActivity(PortraitCaptureActivity::class.java)
                        .setOrientationLocked(true),
                )
            },
            modifier = Modifier.fillMaxWidth(),
            enabled = online && !state.isSaving && !waitingForLocation,
        )

        EduCorePrimaryButton(
            text = when {
                state.isSaving -> "Recording attendance…"
                waitingForLocation -> "Getting location…"
                else -> "Record real-time attendance"
            },
            onClick = {
                localError = null
                if (schoolQrToken.isNullOrBlank()) {
                    localError = "Scan the school attendance QR first."
                    return@EduCorePrimaryButton
                }
                if (staffId.trim().isBlank()) {
                    localError = "Enter the target staff member's Staff ID."
                    return@EduCorePrimaryButton
                }
                if (!geoEnabled) {
                    submitWithLocation(null, null, null)
                } else if (ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED) {
                    captureLocationAndSubmit()
                } else {
                    locationPermission.launch(Manifest.permission.ACCESS_FINE_LOCATION)
                }
            },
            modifier = Modifier.fillMaxWidth(),
            enabled = online && !state.isSaving && !waitingForLocation,
        )

        Text(
            "The school QR validates the school. The Staff ID identifies the colleague. EduCore then records the current server time and automatically treats the first scan as clock-in and the next as clock-out.",
        )
    }
}
