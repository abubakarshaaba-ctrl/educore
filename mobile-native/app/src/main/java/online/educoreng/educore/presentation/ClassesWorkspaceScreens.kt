package online.educoreng.educore.presentation

import android.Manifest
import android.annotation.SuppressLint
import android.content.pm.PackageManager
import android.graphics.Bitmap
import android.util.Base64
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.horizontalScroll
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
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.CalendarToday
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.CloudDone
import androidx.compose.material.icons.filled.Groups
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material.icons.filled.School
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
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.core.content.ContextCompat
import com.google.android.gms.location.LocationServices
import com.google.android.gms.location.Priority
import com.google.android.gms.location.CurrentLocationRequest
import com.google.android.gms.tasks.CancellationTokenSource
import com.journeyapps.barcodescanner.ScanContract
import com.journeyapps.barcodescanner.ScanOptions
import java.io.ByteArrayOutputStream
import online.educoreng.educore.core.designsystem.component.EduCoreDashboardCard
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreFilterChip
import online.educoreng.educore.core.designsystem.component.EduCoreInfoBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreMetricCard
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
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
import online.educoreng.educore.core.model.AttendanceSheet
import online.educoreng.educore.core.model.AttendanceStatus
import online.educoreng.educore.core.model.SyncState
import online.educoreng.educore.core.model.ClassSummary
import online.educoreng.educore.PortraitCaptureActivity
import online.educoreng.educore.core.model.ProxyAttendanceColleague
import online.educoreng.educore.core.model.StudentSummary

@Composable
internal fun ClassesListScreen(
    state: ClassesUiState,
    width: EduCoreWindowWidth,
    onSearch: (String) -> Unit,
    onOpenClass: (Long) -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoadingClasses && state.catalogue == null) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Loading your classes")
        return
    }
    val catalogueError = state.errorMessage
    if (state.catalogue == null && catalogueError != null) {
        EduCoreErrorState(catalogueError, Modifier.fillMaxSize(), onRetry = onRetry)
        return
    }
    val catalogue = state.catalogue ?: return
    val classes = remember(catalogue.classes, state.classSearch) {
        catalogue.classes.filter { item ->
            state.classSearch.isBlank() || listOf(
                item.name,
                item.formTutorName.orEmpty(),
                item.subjects.joinToString(" ") { it.name },
            ).any { it.contains(state.classSearch, ignoreCase = true) }
        }
    }
    val columns = when (width) {
        EduCoreWindowWidth.Compact -> 1
        EduCoreWindowWidth.Medium -> 2
        EduCoreWindowWidth.Expanded -> 3
    }

    Column(Modifier.fillMaxSize().padding(eduCoreScreenPadding())) {
        Text("My classes", style = MaterialTheme.typography.headlineSmall, color = EduCoreColors.Ink900)
        Text(
            "Classes available from your current school assignment",
            style = MaterialTheme.typography.bodyMedium,
            color = EduCoreColors.Slate600,
        )
        Spacer(Modifier.height(EduCoreSpacing.Md))
        if (catalogue.isFromCache) {
            EduCoreWarningBanner("Showing the most recent class list saved on this device.")
            Spacer(Modifier.height(EduCoreSpacing.Md))
        }
        EduCoreSearchBar(
            value = state.classSearch,
            onValueChange = onSearch,
            placeholder = "Search class, subject or form tutor",
        )
        Spacer(Modifier.height(EduCoreSpacing.Md))
        if (classes.isEmpty()) {
            EduCoreEmptyState(
                title = if (state.classSearch.isBlank()) "No assigned classes" else "No classes found",
                message = if (state.classSearch.isBlank()) {
                    "Your school has not assigned a class workspace to this account."
                } else {
                    "Try another class, subject or teacher name."
                },
            )
        } else {
            LazyVerticalGrid(
                columns = GridCells.Fixed(columns),
                modifier = Modifier.weight(1f),
                contentPadding = PaddingValues(vertical = EduCoreSpacing.Sm),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
            ) {
                items(classes, key = ClassSummary::id) { classSummary ->
                    ClassWorkspaceCard(classSummary, onOpenClass)
                }
            }
        }
    }
}

@Composable
private fun ClassWorkspaceCard(classSummary: ClassSummary, onOpenClass: (Long) -> Unit) {
    Card(
        onClick = { onOpenClass(classSummary.id) },
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(
            Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Surface(
                    shape = MaterialTheme.shapes.medium,
                    color = EduCoreColors.Info100,
                    contentColor = EduCoreColors.Navy900,
                ) {
                    Icon(Icons.Default.School, null, Modifier.padding(EduCoreSpacing.Md))
                }
                Spacer(Modifier.width(EduCoreSpacing.Md))
                Column(Modifier.weight(1f)) {
                    Text(classSummary.name, style = MaterialTheme.typography.titleMedium)
                    Text(
                        classSummary.roles.joinToString(" · ") { it.replace('_', ' ').roleLabel() },
                        style = MaterialTheme.typography.bodySmall,
                        color = EduCoreColors.Slate600,
                    )
                }
                EduCoreStatusBadge("${classSummary.studentCount} students", EduCoreTone.Info)
            }
            if (classSummary.subjects.isNotEmpty()) {
                Text(
                    classSummary.subjects.joinToString(" • ") { it.name },
                    style = MaterialTheme.typography.bodyMedium,
                    color = EduCoreColors.Slate700,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis,
                )
            }
            classSummary.formTutorName?.let {
                Text("Form tutor: $it", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Muted500)
            }
        }
    }
}

@Composable
internal fun ClassWorkspaceScreen(
    state: ClassesUiState,
    onBack: () -> Unit,
    onStudentSearch: (String) -> Unit,
    onOpenStudent: (Long, Long) -> Unit,
    onOpenAttendance: (Long) -> Unit,
    onOpenScores: (Long, Long) -> Unit,
    onOpenSchedule: (Long) -> Unit,
    onLoadMoreStudents: () -> Unit,
    onRetry: () -> Unit,
) {
    val workspace = state.classStudents
    if (state.isLoadingWorkspace && workspace == null) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Opening class workspace")
        return
    }
    if (workspace == null) {
        EduCoreErrorState(state.errorMessage ?: "Class workspace is unavailable.", Modifier.fillMaxSize(), onRetry = onRetry)
        return
    }
    val filteredStudents = remember(workspace.students, state.studentSearch) {
        workspace.students.filter { student ->
            state.studentSearch.isBlank() || student.name.contains(state.studentSearch, true) ||
                student.admissionNumber.contains(state.studentSearch, true)
        }
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize().imePadding(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { FeatureBackHeader("Class workspace", workspace.classSummary.name, onBack) }
        if (workspace.isFromCache) item {
            EduCoreWarningBanner("Showing students from the most recent device cache.")
        }
        state.errorMessage?.let { error -> item { EduCoreErrorBanner(error) } }
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                EduCoreMetricCard(
                    label = "Students",
                    value = workspace.classSummary.studentCount.toString(),
                    icon = Icons.Default.Groups,
                    modifier = Modifier.weight(1f),
                )
                EduCoreMetricCard(
                    label = "Subjects",
                    value = workspace.classSummary.subjects.size.toString(),
                    icon = Icons.Default.School,
                    modifier = Modifier.weight(1f),
                )
            }
        }
        if (workspace.classSummary.capabilities.markAttendance) item {
            EduCorePrimaryButton(
                text = "Take attendance",
                onClick = { onOpenAttendance(workspace.classSummary.id) },
                modifier = Modifier.fillMaxWidth(),
                leadingIcon = { Icon(Icons.Default.CheckCircle, null) },
            )
        }
        if (workspace.classSummary.capabilities.enterScores && workspace.classSummary.subjects.isNotEmpty()) item {
            Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                Text("Score entry", style = MaterialTheme.typography.titleMedium)
                workspace.classSummary.subjects.forEach { subject ->
                    EduCoreSecondaryButton(
                        text = "Open ${subject.name} score sheet",
                        onClick = { onOpenScores(workspace.classSummary.id, subject.id) },
                        modifier = Modifier.fillMaxWidth(),
                    )
                }
            }
        }
        if (workspace.classSummary.roles.contains("form_tutor")) item {
            EduCoreSecondaryButton(
                text = "Open class timetable",
                onClick = { onOpenSchedule(workspace.classSummary.id) },
                modifier = Modifier.fillMaxWidth(),
            )
        }
        item {
            EduCoreSearchBar(
                value = state.studentSearch,
                onValueChange = onStudentSearch,
                placeholder = "Search student or admission number",
            )
        }
        if (filteredStudents.isEmpty()) {
            item {
                EduCoreEmptyState("No students found", "Try another name or admission number.")
            }
        } else {
            items(filteredStudents, key = StudentSummary::id) { student ->
                StudentRow(student) { onOpenStudent(workspace.classSummary.id, student.id) }
            }
            if (workspace.currentPage < workspace.lastPage) {
                item {
                    EduCoreSecondaryButton(
                        text = if (state.isLoadingMoreStudents) "Loading more…" else "Load more (${workspace.students.size} of ${workspace.total})",
                        onClick = onLoadMoreStudents,
                        enabled = !state.isLoadingMoreStudents,
                        modifier = Modifier.fillMaxWidth(),
                    )
                }
            }
        }
    }
}

@Composable
private fun StudentRow(student: StudentSummary, onClick: () -> Unit) {
    Card(
        onClick = onClick,
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Row(
            Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Surface(
                modifier = Modifier.size(44.dp),
                shape = MaterialTheme.shapes.medium,
                color = EduCoreColors.Navy900,
                contentColor = Color.White,
            ) {
                Box(contentAlignment = Alignment.Center) {
                    Text(student.initials, style = MaterialTheme.typography.labelLarge)
                }
            }
            Spacer(Modifier.width(EduCoreSpacing.Md))
            Column(Modifier.weight(1f)) {
                Text(student.name, style = MaterialTheme.typography.titleSmall)
                Text(student.admissionNumber, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
            student.gender?.let { EduCoreStatusBadge(it.roleLabel(), EduCoreTone.Neutral) }
        }
    }
}

@Composable
internal fun StudentProfileScreen(state: ClassesUiState, onBack: () -> Unit) {
    val profile = state.studentProfile
    if (state.isLoadingWorkspace && profile == null) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Loading student profile")
        return
    }
    if (profile == null) {
        EduCoreErrorState(state.errorMessage ?: "Student profile is unavailable.", Modifier.fillMaxSize())
        return
    }
    LazyColumn(
        Modifier.fillMaxSize().imePadding(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { FeatureBackHeader("Student profile", profile.className, onBack) }
        item {
            EduCoreDashboardCard {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Surface(
                        modifier = Modifier.size(64.dp),
                        shape = MaterialTheme.shapes.large,
                        color = EduCoreColors.Navy900,
                        contentColor = Color.White,
                    ) {
                        Box(contentAlignment = Alignment.Center) {
                            Text(profile.student.initials, style = MaterialTheme.typography.titleLarge)
                        }
                    }
                    Spacer(Modifier.width(EduCoreSpacing.Lg))
                    Column {
                        Text(profile.student.name, style = MaterialTheme.typography.titleLarge)
                        Text(profile.student.admissionNumber, color = EduCoreColors.Slate600)
                        EduCoreStatusBadge(profile.status.roleLabel(), EduCoreTone.Success)
                    }
                }
            }
        }
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                EduCoreMetricCard(
                    "Attendance",
                    "${profile.attendance.rate}%",
                    Modifier.weight(1f),
                    Icons.Default.CheckCircle,
                    "${profile.attendance.present} present",
                    EduCoreTone.Success,
                )
                EduCoreMetricCard(
                    "Late / absent",
                    "${profile.attendance.late} / ${profile.attendance.absent}",
                    Modifier.weight(1f),
                    Icons.Default.Schedule,
                    "Current term",
                    EduCoreTone.Warning,
                )
            }
        }
        item {
            EduCoreDashboardCard {
                ProfileLine("Class", profile.className)
                ProfileLine("Gender", profile.student.gender?.roleLabel() ?: "Not recorded")
                ProfileLine("Date of birth", profile.dateOfBirth ?: "Not recorded")
                ProfileLine("Admission date", profile.admissionDate ?: "Not recorded")
            }
        }
    }
}

@Composable
private fun ProfileLine(label: String, value: String) {
    Row(Modifier.fillMaxWidth().padding(vertical = EduCoreSpacing.Sm)) {
        Text(label, Modifier.weight(1f), color = EduCoreColors.Slate600)
        Text(value, fontWeight = FontWeight.SemiBold)
    }
}

@Composable
internal fun AttendanceScreen(
    state: ClassesUiState,
    online: Boolean,
    onBack: () -> Unit,
    onStatus: (Long, AttendanceStatus) -> Unit,
    onMarkAllPresent: () -> Unit,
    onDiscardDraft: () -> Unit,
    onSubmit: () -> Unit,
) {
    val sheet = state.attendanceSheet
    if (state.isLoadingWorkspace && sheet == null) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Preparing attendance sheet")
        return
    }
    if (sheet == null) {
        EduCoreErrorState(state.errorMessage ?: "Attendance sheet is unavailable.", Modifier.fillMaxSize())
        return
    }
    val incompleteCount = sheet.students.count { it.status == null }
    val complete = incompleteCount == 0
    LazyColumn(
        modifier = Modifier.fillMaxSize().imePadding(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { FeatureBackHeader("Student attendance", "${sheet.className} · ${sheet.date}", onBack) }
        if (sheet.isDraftStale) item {
            EduCoreWarningBanner(
                message = "The server sheet changed after this draft was started. Discard the draft and reload before saving.",
                title = "Draft requires review",
            )
        } else if (sheet.hasLocalDraft) item {
            EduCoreInfoBanner("Draft saved safely on this device. Submission remains explicit.", title = "Local draft")
        }
        if (!complete && !sheet.isDraftStale) item {
            EduCoreInfoBanner(
                message = "$incompleteCount ${if (incompleteCount == 1) "student still needs" else "students still need"} an attendance status. Mark every student Present, Absent, Late or Excused before submitting.",
                title = "Attendance incomplete",
            )
        }
        if (sheet.syncState != SyncState.NONE) item {
            val message = sheet.syncMessage ?: when (sheet.syncState) {
                SyncState.QUEUED, SyncState.SYNCING -> "Waiting for a stable connection. This submission will retry automatically."
                SyncState.CONFLICT -> "The server record changed. Reload and review before submitting again."
                SyncState.FAILED -> "Automatic sync stopped. Review the error and retry explicitly."
                SyncState.NONE -> ""
            }
            if (sheet.syncState == SyncState.QUEUED || sheet.syncState == SyncState.SYNCING) EduCoreInfoBanner(message, title = "Sync pending") else EduCoreWarningBanner(message, title = "Sync needs attention")
        }
        if (!online) item {
            EduCoreInfoBanner(
                message = "You can finish marking this sheet offline. When every student has a status, tap Queue attendance. EduCore will send it automatically when the connection returns.",
                title = "Offline mode",
            )
        }
        state.errorMessage?.let { error -> item { EduCoreErrorBanner(error) } }
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                EduCoreSecondaryButton("Mark all present", onMarkAllPresent, Modifier.weight(1f), !sheet.isDraftStale)
                if (sheet.hasLocalDraft) {
                    EduCoreSecondaryButton("Discard draft", onDiscardDraft, Modifier.weight(1f))
                }
            }
        }
        items(sheet.students, key = { it.student.id }) { row ->
            AttendanceStudentCard(row.student, row.status, sheet.syncState == SyncState.NONE) { status -> onStatus(row.student.id, status) }
        }
        item {
            EduCorePrimaryButton(
                text = when {
                    sheet.syncState == SyncState.FAILED -> "Retry sync"
                    !complete -> "Mark $incompleteCount remaining"
                    online -> "Save attendance"
                    else -> "Queue attendance"
                },
                onClick = onSubmit,
                modifier = Modifier.fillMaxWidth(),
                enabled = complete && !sheet.isDraftStale && sheet.syncState != SyncState.QUEUED && sheet.syncState != SyncState.SYNCING && sheet.syncState != SyncState.CONFLICT,
                loading = state.isSaving,
                leadingIcon = { Icon(Icons.Default.CloudDone, null) },
            )
        }
    }
}

@Composable
private fun AttendanceStudentCard(
    student: StudentSummary,
    status: AttendanceStatus?,
    enabled: Boolean,
    onStatus: (AttendanceStatus) -> Unit,
) {
    EduCoreDashboardCard {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text(student.name, style = MaterialTheme.typography.titleSmall)
                Text(student.admissionNumber, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
            if (status != null) EduCoreStatusBadge(status.wireValue.roleLabel(), status.tone())
        }
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        Row(
            Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            AttendanceStatus.entries.forEach { option ->
                EduCoreFilterChip(
                    label = option.wireValue.roleLabel(),
                    selected = status == option,
                    onClick = { onStatus(option) },
                    enabled = enabled,
                )
            }
        }
    }
}

@Composable
internal fun StaffAttendanceScreen(
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
        if (!snapshot?.geoEnabled.orFalse()) {
            onClockIn(token, null, null)
            return
        }
        if (ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED) {
            currentLocation(locationClient) { latitude, longitude, error ->
                if (error != null) scanError = error else onClockIn(token, latitude, longitude)
            }
        } else {
            pendingToken = token
        }
    }

    val locationPermission = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestPermission(),
    ) { granted ->
        val token = pendingToken
        if (!granted || token == null) {
            scanError = "Location permission is required by this school for attendance verification."
            pendingToken = null
        } else {
            currentLocation(locationClient) { latitude, longitude, error ->
                pendingToken = null
                if (error != null) scanError = error else onClockIn(token, latitude, longitude)
            }
        }
    }
    val qrScanner = rememberLauncherForActivityResult(ScanContract()) { result ->
        val token = result.contents
        if (token.isNullOrBlank()) return@rememberLauncherForActivityResult
        if (snapshot?.geoEnabled == true &&
            ContextCompat.checkSelfPermission(
                context,
                Manifest.permission.ACCESS_FINE_LOCATION,
            ) != PackageManager.PERMISSION_GRANTED
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
            bitmap.toAttendancePhotoDataUrl(),
            proxyLatitude,
            proxyLongitude,
        )
        pendingProxyToken = null
        proxyLatitude = null
        proxyLongitude = null
        selectedProxy = null
        proxyMode = false
    }
    val proxyCameraPermission = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestPermission(),
    ) { granted ->
        if (granted) proxyCamera.launch(null) else scanError = "Camera permission is required for the colleague's live attendance photo."
    }
    fun captureProxyPhoto() {
        if (ContextCompat.checkSelfPermission(context, Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED) {
            proxyCamera.launch(null)
        } else {
            proxyCameraPermission.launch(Manifest.permission.CAMERA)
        }
    }
    val proxyLocationPermission = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestPermission(),
    ) { granted ->
        val token = pendingProxyToken
        if (!granted || token == null) {
            scanError = "Location permission is required by this school for proxy attendance verification."
            pendingProxyToken = null
        } else {
            currentLocation(locationClient) { latitude, longitude, error ->
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
    fun handleProxyToken(token: String) {
        pendingProxyToken = token
        proxyLatitude = null
        proxyLongitude = null
        if (snapshot?.geoEnabled != true) {
            captureProxyPhoto()
            return
        }
        if (ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED) {
            currentLocation(locationClient) { latitude, longitude, error ->
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
    val proxyQrScanner = rememberLauncherForActivityResult(ScanContract()) { result ->
        val token = result.contents
        if (token.isNullOrBlank()) return@rememberLauncherForActivityResult
        handleProxyToken(token)
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
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { FeatureBackHeader("My attendance", "${snapshot.month}/${snapshot.year}", onBack) }
        state.errorMessage?.let { error -> item { EduCoreErrorBanner(error) } }
        scanError?.let { error -> item { EduCoreErrorBanner(error) } }
        if (!online) item {
            EduCoreWarningBanner("QR, geofence and proxy clock-in require a live server connection. No unverified staff clock-in is queued.")
        }
        item {
            Surface(
                color = EduCoreColors.Navy900,
                contentColor = Color.White,
                shape = MaterialTheme.shapes.large,
            ) {
                Column(Modifier.padding(EduCoreSpacing.Xxl)) {
                    Text("TODAY", color = EduCoreColors.Gold400, style = MaterialTheme.typography.labelLarge)
                    Text(
                        when {
                            !clockedIn -> "Not clocked in yet"
                            clockedOut -> "Completed for today"
                            else -> "Clocked in"
                        },
                        style = MaterialTheme.typography.headlineSmall,
                    )
                    snapshot.today?.let { today ->
                        Text("In ${today.clockIn.orEmpty()}${today.clockOut?.let { " · Out $it" }.orEmpty()}")
                    }
                }
            }
        }
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                EduCoreMetricCard("Present", snapshot.counts.present.toString(), Modifier.weight(1f), tone = EduCoreTone.Success)
                EduCoreMetricCard("Late", snapshot.counts.late.toString(), Modifier.weight(1f), tone = EduCoreTone.Warning)
                EduCoreMetricCard("Absent", snapshot.counts.absent.toString(), Modifier.weight(1f), tone = EduCoreTone.Danger)
            }
        }
        if (!clockedIn) item {
            EduCoreDashboardCard {
                Text("School QR", style = MaterialTheme.typography.titleMedium)
                Text(
                    if (snapshot.geoEnabled) "Location verification is required within ${snapshot.geoRadiusMeters} m." else "Scan the school display or your staff ID QR.",
                    style = MaterialTheme.typography.bodySmall,
                    color = EduCoreColors.Slate600,
                )
                Spacer(Modifier.height(EduCoreSpacing.Md))
                EduCorePrimaryButton(
                    text = "Scan QR and clock in",
                    onClick = {
                        scanError = null
                        qrScanner.launch(
                            ScanOptions()
                                .setDesiredBarcodeFormats(ScanOptions.QR_CODE)
                                .setPrompt("Scan the school attendance QR")
                                .setBeepEnabled(false)
                                .setCaptureActivity(PortraitCaptureActivity::class.java)
                                .setOrientationLocked(true),
                        )
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
                    title = "Proxy attendance verification",
                    message = "Select the colleague who is physically present. EduCore will require the school QR, any enabled school geofence, and a live photo captured now. The attendance record identifies who performed the proxy clock-in.",
                )
            }
            item {
                EduCoreDashboardCard {
                    Text("Select colleague", style = MaterialTheme.typography.titleMedium)
                    Spacer(Modifier.height(EduCoreSpacing.Sm))
                    EduCoreSearchBar(
                        value = state.proxySearch,
                        onValueChange = onProxySearch,
                        placeholder = "Search staff name",
                    )
                    Spacer(Modifier.height(EduCoreSpacing.Md))
                    when {
                        state.isLoadingProxy -> Text("Loading eligible colleagues…", color = EduCoreColors.Slate600)
                        state.proxyColleagues.isEmpty() -> Text("No eligible unclocked staff found.", color = EduCoreColors.Slate600)
                        else -> Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                            state.proxyColleagues.take(12).forEach { colleague ->
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
                        Spacer(Modifier.height(EduCoreSpacing.Md))
                        EduCorePrimaryButton(
                            text = "Scan school QR & capture ${colleague.name}'s photo",
                            onClick = {
                                scanError = null
                                proxyQrScanner.launch(
                                    ScanOptions()
                                        .setDesiredBarcodeFormats(ScanOptions.QR_CODE)
                                        .setPrompt("Scan the SCHOOL attendance QR for ${colleague.name}")
                                        .setBeepEnabled(false)
                                        .setCaptureActivity(PortraitCaptureActivity::class.java)
                                        .setOrientationLocked(true),
                                )
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
            EduCoreDashboardCard {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(Icons.Default.CalendarToday, null, tint = EduCoreColors.Navy900)
                    Spacer(Modifier.width(EduCoreSpacing.Md))
                    Column(Modifier.weight(1f)) {
                        Text(record.date, style = MaterialTheme.typography.titleSmall)
                        Text("${record.clockIn.orEmpty()} — ${record.clockOut ?: "Open"}", color = EduCoreColors.Slate600)
                    }
                    EduCoreStatusBadge(record.status.roleLabel(), record.status.statusTone())
                }
            }
        }
    }
}

@Composable
private fun FeatureBackHeader(title: String, subtitle: String, onBack: () -> Unit) {
    EduCorePageHeader(title = title, subtitle = subtitle, onBack = onBack)
}

private fun AttendanceStatus.tone(): EduCoreTone = when (this) {
    AttendanceStatus.PRESENT -> EduCoreTone.Success
    AttendanceStatus.LATE, AttendanceStatus.EXCUSED -> EduCoreTone.Warning
    AttendanceStatus.ABSENT -> EduCoreTone.Danger
}

private fun String.statusTone(): EduCoreTone = when (lowercase()) {
    "early", "present" -> EduCoreTone.Success
    "late" -> EduCoreTone.Warning
    "absent" -> EduCoreTone.Danger
    else -> EduCoreTone.Neutral
}

private fun String.roleLabel(): String = replace('_', ' ')
    .trim()
    .split(Regex("\\s+"))
    .joinToString(" ") { word -> word.replaceFirstChar { it.uppercase() } }

private fun Boolean?.orFalse(): Boolean = this == true

private fun Bitmap.toAttendancePhotoDataUrl(): String {
    val output = ByteArrayOutputStream()
    compress(Bitmap.CompressFormat.JPEG, 82, output)
    return "data:image/jpeg;base64," + Base64.encodeToString(output.toByteArray(), Base64.NO_WRAP)
}

@SuppressLint("MissingPermission")
private fun currentLocation(
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
