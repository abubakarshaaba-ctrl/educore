package online.educoreng.educore.presentation

import android.Manifest
import android.annotation.SuppressLint
import android.content.pm.PackageManager
import android.graphics.Bitmap
import android.net.Uri
import android.util.Base64
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Badge
import androidx.compose.material.icons.filled.CalendarToday
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.QrCodeScanner
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.core.content.ContextCompat
import com.google.android.gms.location.CurrentLocationRequest
import com.google.android.gms.location.LocationServices
import com.google.android.gms.location.Priority
import com.google.android.gms.tasks.CancellationTokenSource
import com.journeyapps.barcodescanner.ScanContract
import com.journeyapps.barcodescanner.ScanOptions
import java.io.ByteArrayOutputStream
import java.util.Locale
import online.educoreng.educore.PortraitCaptureActivity
import online.educoreng.educore.core.designsystem.component.EduCoreDashboardCard
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreInfoBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.component.EduCoreWarningBanner
import online.educoreng.educore.core.designsystem.layout.EduCoreWindowWidth
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.ClassSummary
import online.educoreng.educore.core.model.ProxyAttendanceColleague

@Composable
internal fun StudentAttendanceClassPickerScreen(
    state: ClassesUiState,
    width: EduCoreWindowWidth,
    onBack: () -> Unit,
    onSearch: (String) -> Unit,
    onOpenAttendance: (Long) -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoadingClasses && state.catalogue == null) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Loading attendance classes")
        return
    }
    val catalogue = state.catalogue
    if (catalogue == null) {
        EduCoreErrorState(
            state.errorMessage ?: "Student attendance classes are unavailable.",
            Modifier.fillMaxSize(),
            onRetry = onRetry,
        )
        return
    }

    val eligible = remember(catalogue.classes, state.classSearch) {
        catalogue.classes.filter { item ->
            item.capabilities.markAttendance && (
                state.classSearch.isBlank() ||
                    item.name.contains(state.classSearch, ignoreCase = true) ||
                    item.formTutorName.orEmpty().contains(state.classSearch, ignoreCase = true)
                )
        }
    }
    val columns = when (width) {
        EduCoreWindowWidth.Compact -> 1
        EduCoreWindowWidth.Medium -> 2
        EduCoreWindowWidth.Expanded -> 3
    }

    Column(Modifier.fillMaxSize().padding(eduCoreScreenPadding())) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            IconButton(onClick = onBack, modifier = Modifier.size(40.dp)) {
                Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
            }
            Column(Modifier.weight(1f)) {
                Text("Student attendance", style = MaterialTheme.typography.titleLarge, color = EduCoreColors.Ink900)
                Text("Select a class to mark attendance", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
        }
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        if (catalogue.isFromCache) {
            EduCoreWarningBanner("Showing the most recent class list saved on this device.")
            Spacer(Modifier.height(EduCoreSpacing.Sm))
        }
        EduCoreSearchBar(
            value = state.classSearch,
            onValueChange = onSearch,
            placeholder = "Search attendance class",
        )
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        if (eligible.isEmpty()) {
            EduCoreEmptyState(
                title = if (state.classSearch.isBlank()) "No attendance classes" else "No class found",
                message = if (state.classSearch.isBlank()) {
                    "No class currently grants you permission to mark student attendance."
                } else {
                    "Try another class name."
                },
            )
        } else {
            LazyVerticalGrid(
                columns = GridCells.Fixed(columns),
                modifier = Modifier.weight(1f),
                contentPadding = PaddingValues(vertical = EduCoreSpacing.Sm),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                items(eligible, key = ClassSummary::id) { classSummary ->
                    Card(
                        onClick = { onOpenAttendance(classSummary.id) },
                        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                        border = BorderStroke(1.dp, EduCoreColors.Line200),
                    ) {
                        Row(
                            Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
                            verticalAlignment = Alignment.CenterVertically,
                        ) {
                            Surface(
                                modifier = Modifier.size(38.dp),
                                shape = MaterialTheme.shapes.medium,
                                color = EduCoreColors.Info100,
                                contentColor = EduCoreColors.Navy900,
                            ) {
                                Box(contentAlignment = Alignment.Center) {
                                    Icon(Icons.Default.CheckCircle, contentDescription = null, modifier = Modifier.size(20.dp))
                                }
                            }
                            Spacer(Modifier.width(EduCoreSpacing.Sm))
                            Column(Modifier.weight(1f)) {
                                Text(classSummary.name, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
                                Text(
                                    "${classSummary.studentCount} students${classSummary.formTutorName?.let { " · $it" }.orEmpty()}",
                                    style = MaterialTheme.typography.bodySmall,
                                    color = EduCoreColors.Slate600,
                                    maxLines = 1,
                                    overflow = TextOverflow.Ellipsis,
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
internal fun CompactStaffAttendanceScreen(
    state: ClassesUiState,
    online: Boolean,
    onBack: () -> Unit,
    onRefresh: () -> Unit,
    onClockIn: (String, Double?, Double?) -> Unit,
    onClockOut: () -> Unit,
    onProxySearch: (String) -> Unit,
    onLoadProxyColleagues: () -> Unit,
    onProxyClockIn: (Long, String, String, Double?, Double?) -> Unit,
) {
    var pendingToken by remember { mutableStateOf<String?>(null) }
    var scanError by remember { mutableStateOf<String?>(null) }
    var proxyMode by remember { mutableStateOf(false) }
    var selectedProxy by remember { mutableStateOf<ProxyAttendanceColleague?>(null) }
    var pendingProxyToken by remember { mutableStateOf<String?>(null) }
    var proxyLatitude by remember { mutableStateOf<Double?>(null) }
    var proxyLongitude by remember { mutableStateOf<Double?>(null) }
    val snapshot = state.staffAttendance
    val context = LocalContext.current
    val locationClient = remember(context) { LocationServices.getFusedLocationProviderClient(context) }

    fun submitScannedToken(token: String) {
        pendingToken = null
        if (snapshot?.geoEnabled != true) {
            onClockIn(token, null, null)
            return
        }
        if (ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED) {
            currentAttendanceLocation(locationClient) { latitude, longitude, error ->
                if (error != null) scanError = error else onClockIn(token, latitude, longitude)
            }
        } else {
            pendingToken = token
        }
    }

    val locationPermission = rememberLauncherForActivityResult(ActivityResultContracts.RequestPermission()) { granted ->
        val token = pendingToken
        if (!granted || token == null) {
            scanError = "Location permission is required by this school for attendance verification."
            pendingToken = null
        } else {
            currentAttendanceLocation(locationClient) { latitude, longitude, error ->
                pendingToken = null
                if (error != null) scanError = error else onClockIn(token, latitude, longitude)
            }
        }
    }

    val schoolQrScanner = rememberLauncherForActivityResult(ScanContract()) { result ->
        val token = result.contents
        if (token.isNullOrBlank()) return@rememberLauncherForActivityResult
        if (snapshot?.geoEnabled == true &&
            ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED
        ) {
            pendingToken = token
            locationPermission.launch(Manifest.permission.ACCESS_FINE_LOCATION)
        } else {
            submitScannedToken(token)
        }
    }

    val proxyCamera = rememberLauncherForActivityResult(ActivityResultContracts.TakePicturePreview()) { bitmap ->
        val colleague = selectedProxy
        val token = pendingProxyToken
        if (bitmap == null) {
            scanError = "Live photo capture was cancelled. Proxy clock-in was not submitted."
            return@rememberLauncherForActivityResult
        }
        if (colleague == null || token.isNullOrBlank()) {
            scanError = "Proxy attendance session expired. Select the colleague and scan the school QR again."
            return@rememberLauncherForActivityResult
        }
        onProxyClockIn(
            colleague.id,
            token,
            bitmap.toCompactAttendancePhotoDataUrl(),
            proxyLatitude,
            proxyLongitude,
        )
        pendingProxyToken = null
        proxyLatitude = null
        proxyLongitude = null
        selectedProxy = null
        proxyMode = false
    }

    val proxyCameraPermission = rememberLauncherForActivityResult(ActivityResultContracts.RequestPermission()) { granted ->
        if (granted) proxyCamera.launch(null) else scanError = "Camera permission is required for the colleague's live attendance photo."
    }

    fun captureProxyPhoto() {
        if (ContextCompat.checkSelfPermission(context, Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED) {
            proxyCamera.launch(null)
        } else {
            proxyCameraPermission.launch(Manifest.permission.CAMERA)
        }
    }

    val proxyLocationPermission = rememberLauncherForActivityResult(ActivityResultContracts.RequestPermission()) { granted ->
        val token = pendingProxyToken
        if (!granted || token == null) {
            scanError = "Location permission is required by this school for proxy attendance verification."
            pendingProxyToken = null
        } else {
            currentAttendanceLocation(locationClient) { latitude, longitude, error ->
                if (error != null) {
                    scanError = error
                    pendingProxyToken = null
                } else {
                    proxyLatitude = latitude
                    proxyLongitude = longitude
                    captureProxyPhoto()
                }
            }
        }
    }

    fun handleProxySchoolToken(token: String) {
        pendingProxyToken = token
        proxyLatitude = null
        proxyLongitude = null
        if (snapshot?.geoEnabled != true) {
            captureProxyPhoto()
            return
        }
        if (ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED) {
            currentAttendanceLocation(locationClient) { latitude, longitude, error ->
                if (error != null) {
                    scanError = error
                    pendingProxyToken = null
                } else {
                    proxyLatitude = latitude
                    proxyLongitude = longitude
                    captureProxyPhoto()
                }
            }
        } else {
            proxyLocationPermission.launch(Manifest.permission.ACCESS_FINE_LOCATION)
        }
    }

    val proxySchoolQrScanner = rememberLauncherForActivityResult(ScanContract()) { result ->
        val token = result.contents
        if (!token.isNullOrBlank()) handleProxySchoolToken(token)
    }

    val staffIdQrScanner = rememberLauncherForActivityResult(ScanContract()) { result ->
        val payload = result.contents
        if (payload.isNullOrBlank()) return@rememberLauncherForActivityResult
        val colleague = state.proxyColleagues.firstOrNull { it.matchesStaffIdQr(payload) }
        if (colleague == null) {
            scanError = "This staff ID QR does not match an eligible unclocked colleague. Refresh the staff list or select the colleague manually."
        } else {
            selectedProxy = colleague
            scanError = null
        }
    }

    if (state.isLoadingWorkspace && snapshot == null) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Loading attendance")
        return
    }
    if (snapshot == null) {
        EduCoreErrorState(state.errorMessage ?: "Staff attendance is unavailable.", Modifier.fillMaxSize(), onRetry = onRefresh)
        return
    }

    val clockedIn = snapshot.today?.clockIn != null
    val clockedOut = snapshot.today?.clockOut != null

    LazyColumn(
        modifier = Modifier.fillMaxSize().imePadding(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
    ) {
        item {
            Row(verticalAlignment = Alignment.CenterVertically) {
                IconButton(onClick = onBack, modifier = Modifier.size(40.dp)) {
                    Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                }
                Column(Modifier.weight(1f)) {
                    Text("My attendance", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.SemiBold)
                    Text("${snapshot.month}/${snapshot.year}", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                }
            }
        }
        state.errorMessage?.let { error -> item { EduCoreErrorBanner(error) } }
        scanError?.let { error -> item { EduCoreErrorBanner(error) } }
        if (!online) item {
            EduCoreWarningBanner("QR, geofence and proxy clock-in require a live server connection. No unverified staff clock-in is queued.")
        }
        item {
            Surface(
                color = EduCoreColors.Navy900,
                contentColor = Color.White,
                shape = MaterialTheme.shapes.medium,
            ) {
                Row(
                    Modifier.fillMaxWidth().padding(horizontal = EduCoreSpacing.Md, vertical = EduCoreSpacing.Sm),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Column(Modifier.weight(1f)) {
                        Text("TODAY", color = EduCoreColors.Gold400, style = MaterialTheme.typography.labelSmall)
                        Text(
                            when {
                                !clockedIn -> "Not clocked in"
                                clockedOut -> "Completed"
                                else -> "Clocked in"
                            },
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.SemiBold,
                        )
                    }
                    snapshot.today?.let { today ->
                        Text(
                            "${today.clockIn.orEmpty()}${today.clockOut?.let { " · $it" }.orEmpty()}",
                            style = MaterialTheme.typography.bodySmall,
                            color = Color.White.copy(alpha = .86f),
                        )
                    }
                }
            }
        }
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                CompactAttendanceMetric("Present", snapshot.counts.present, EduCoreTone.Success, Modifier.weight(1f))
                CompactAttendanceMetric("Late", snapshot.counts.late, EduCoreTone.Warning, Modifier.weight(1f))
                CompactAttendanceMetric("Absent", snapshot.counts.absent, EduCoreTone.Danger, Modifier.weight(1f))
            }
        }
        if (!clockedIn) item {
            EduCoreDashboardCard {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(Icons.Default.QrCodeScanner, null, tint = EduCoreColors.Navy900, modifier = Modifier.size(22.dp))
                    Spacer(Modifier.width(EduCoreSpacing.Sm))
                    Column(Modifier.weight(1f)) {
                        Text("School QR", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
                        Text(
                            if (snapshot.geoEnabled) "Location check: ${snapshot.geoRadiusMeters} m radius" else "Scan the school's attendance QR",
                            style = MaterialTheme.typography.bodySmall,
                            color = EduCoreColors.Slate600,
                        )
                    }
                }
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                EduCorePrimaryButton(
                    text = "Scan QR & clock in",
                    onClick = {
                        scanError = null
                        schoolQrScanner.launch(attendanceQrOptions("Scan the school attendance QR"))
                    },
                    modifier = Modifier.fillMaxWidth(),
                    enabled = online,
                    loading = state.isSaving,
                )
            }
        } else if (!clockedOut) item {
            EduCorePrimaryButton(
                text = "Clock out",
                onClick = onClockOut,
                modifier = Modifier.fillMaxWidth(),
                enabled = online,
                loading = state.isSaving,
            )
        }
        item {
            EduCoreSecondaryButton(
                text = if (proxyMode) "Close colleague clock-in" else "Clock in for a colleague",
                onClick = {
                    proxyMode = !proxyMode
                    scanError = null
                    selectedProxy = null
                    if (proxyMode) onLoadProxyColleagues()
                },
                modifier = Modifier.fillMaxWidth(),
                enabled = online && !state.isSaving,
            )
        }
        if (proxyMode) {
            item {
                EduCoreInfoBanner(
                    title = "Verified proxy clock-in",
                    message = "Select the colleague manually or scan the QR on their staff ID card. The school QR, geofence (when enabled), and a live photo are still required before submission.",
                )
            }
            item {
                EduCoreDashboardCard {
                    Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                        EduCoreSecondaryButton(
                            text = "Scan staff ID QR",
                            onClick = {
                                scanError = null
                                onLoadProxyColleagues()
                                staffIdQrScanner.launch(attendanceQrOptions("Scan the colleague's staff ID QR"))
                            },
                            modifier = Modifier.weight(1f),
                            enabled = online && !state.isSaving,
                            leadingIcon = { Icon(Icons.Default.Badge, null) },
                        )
                    }
                    Spacer(Modifier.height(EduCoreSpacing.Sm))
                    EduCoreSearchBar(
                        value = state.proxySearch,
                        onValueChange = onProxySearch,
                        placeholder = "Or search staff name / ID",
                    )
                    Spacer(Modifier.height(EduCoreSpacing.Sm))
                    when {
                        state.isLoadingProxy -> Text("Loading eligible colleagues…", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                        state.proxyColleagues.isEmpty() -> Text("No eligible unclocked staff found.", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                        else -> Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                            state.proxyColleagues.take(10).forEach { colleague ->
                                EduCoreSecondaryButton(
                                    text = buildString {
                                        if (selectedProxy?.id == colleague.id) append("Selected · ")
                                        append(colleague.name)
                                        if (colleague.staffId.isNotBlank()) append(" · ${colleague.staffId}")
                                    },
                                    onClick = { selectedProxy = colleague },
                                    modifier = Modifier.fillMaxWidth(),
                                    enabled = !state.isSaving,
                                )
                            }
                        }
                    }
                    selectedProxy?.let { colleague ->
                        Spacer(Modifier.height(EduCoreSpacing.Sm))
                        Text(
                            "Selected: ${colleague.name}${if (colleague.staffId.isNotBlank()) " · ${colleague.staffId}" else ""}",
                            style = MaterialTheme.typography.bodySmall,
                            color = EduCoreColors.Slate700,
                        )
                        Spacer(Modifier.height(EduCoreSpacing.Sm))
                        EduCorePrimaryButton(
                            text = "Scan school QR & take live photo",
                            onClick = {
                                scanError = null
                                proxySchoolQrScanner.launch(attendanceQrOptions("Scan the SCHOOL attendance QR for ${colleague.name}"))
                            },
                            modifier = Modifier.fillMaxWidth(),
                            enabled = online && !state.isSaving,
                            loading = state.isSaving,
                        )
                    }
                }
            }
        }
        items(snapshot.records.take(31), key = { it.date }) { record ->
            Card(
                colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                border = BorderStroke(1.dp, EduCoreColors.Line200),
            ) {
                Row(
                    Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Icon(Icons.Default.CalendarToday, null, tint = EduCoreColors.Navy900, modifier = Modifier.size(20.dp))
                    Spacer(Modifier.width(EduCoreSpacing.Sm))
                    Column(Modifier.weight(1f)) {
                        Text(record.date, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
                        Text("${record.clockIn.orEmpty()} — ${record.clockOut ?: "Open"}", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                    }
                    EduCoreStatusBadge(record.status.replace('_', ' ').replaceFirstChar { it.uppercase() }, record.status.compactAttendanceTone())
                }
            }
        }
    }
}

@Composable
private fun CompactAttendanceMetric(label: String, value: Int, tone: EduCoreTone, modifier: Modifier = Modifier) {
    val background = when (tone) {
        EduCoreTone.Success -> EduCoreColors.Success100
        EduCoreTone.Warning -> EduCoreColors.Warning100
        EduCoreTone.Danger -> EduCoreColors.Danger100
        else -> EduCoreColors.Info100
    }
    Surface(modifier = modifier, shape = MaterialTheme.shapes.medium, color = background) {
        Column(Modifier.padding(EduCoreSpacing.Sm)) {
            Text(value.toString(), style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold, color = EduCoreColors.Ink900)
            Text(label, style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Slate600)
        }
    }
}

private fun attendanceQrOptions(prompt: String): ScanOptions = ScanOptions()
    .setDesiredBarcodeFormats(ScanOptions.QR_CODE)
    .setPrompt(prompt)
    .setBeepEnabled(false)
    .setCaptureActivity(PortraitCaptureActivity::class.java)
    .setOrientationLocked(true)

private fun ProxyAttendanceColleague.matchesStaffIdQr(payload: String): Boolean {
    if (staffId.isBlank()) return false
    val expected = staffId.trim().lowercase(Locale.ROOT)
    val raw = payload.trim()
    val candidates = linkedSetOf(raw)
    runCatching {
        val uri = Uri.parse(raw)
        listOf("staff_id", "staffId", "staff", "employee_id", "employeeId", "id").forEach { key ->
            uri.getQueryParameter(key)?.let(candidates::add)
        }
        uri.lastPathSegment?.let(candidates::add)
    }
    raw.split('|', ':', ';', ',', '/', '\\').filter { it.isNotBlank() }.forEach(candidates::add)
    return candidates.any { candidate -> candidate.trim().lowercase(Locale.ROOT) == expected }
}

private fun String.compactAttendanceTone(): EduCoreTone = when (lowercase(Locale.ROOT)) {
    "early", "present" -> EduCoreTone.Success
    "late" -> EduCoreTone.Warning
    "absent" -> EduCoreTone.Danger
    else -> EduCoreTone.Neutral
}

private fun Bitmap.toCompactAttendancePhotoDataUrl(): String {
    val output = ByteArrayOutputStream()
    compress(Bitmap.CompressFormat.JPEG, 82, output)
    return "data:image/jpeg;base64," + Base64.encodeToString(output.toByteArray(), Base64.NO_WRAP)
}

@SuppressLint("MissingPermission")
private fun currentAttendanceLocation(
    client: com.google.android.gms.location.FusedLocationProviderClient,
    result: (Double?, Double?, String?) -> Unit,
) {
    val request = CurrentLocationRequest.Builder()
        .setPriority(Priority.PRIORITY_HIGH_ACCURACY)
        .setMaxUpdateAgeMillis(0)
        .setDurationMillis(20_000)
        .build()
    client.getCurrentLocation(request, CancellationTokenSource().token)
        .addOnSuccessListener { location ->
            if (location == null) {
                result(null, null, "Your current location is unavailable. Turn on location services and try again.")
            } else if (location.accuracy > 100f) {
                result(null, null, "GPS accuracy is ${location.accuracy.toInt()} m. Move into an open area and try again.")
            } else {
                result(location.latitude, location.longitude, null)
            }
        }
        .addOnFailureListener { error ->
            result(null, null, error.localizedMessage ?: "Location verification failed.")
        }
}
