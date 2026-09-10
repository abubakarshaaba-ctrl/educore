package online.educoreng.educore.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Surface
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.squareup.moshi.Moshi
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.AdminStaffAttendanceApi
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.dto.AdminAttendanceManualRequestDto
import online.educoreng.educore.core.network.dto.AdminAttendanceProxyDecisionRequestDto
import online.educoreng.educore.core.network.dto.AdminAttendanceReviewRequestDto
import online.educoreng.educore.core.network.dto.AdminAttendanceSettingsRequestDto
import online.educoreng.educore.core.network.dto.AdminStaffAttendanceRecordDto
import online.educoreng.educore.core.network.dto.AdminStaffAttendanceReportDto
import online.educoreng.educore.core.network.dto.AdminStaffAttendanceResponseDto
import online.educoreng.educore.core.network.dto.AdminStaffOfflineQueueDto
import online.educoreng.educore.core.network.dto.AdminStaffProxyReviewQueueDto
import online.educoreng.educore.core.network.safeApiCall

@HiltViewModel
class AdminStaffAttendanceViewModel @Inject constructor(
    factory: ApiClientFactory,
    moshi: Moshi,
) : ViewModel() {
    private val api = factory.create(AdminStaffAttendanceApi::class.java)
    private val parser = moshi
    private val _uiState = MutableStateFlow(AdminStaffAttendanceUiState())
    val uiState: StateFlow<AdminStaffAttendanceUiState> = _uiState.asStateFlow()

    fun selectSection(section: AdminAttendanceSection) {
        _uiState.update { it.copy(section = section) }
        when (section) {
            AdminAttendanceSection.DAILY -> loadDaily()
            AdminAttendanceSection.MONTHLY -> loadReport()
            AdminAttendanceSection.REVIEWS -> loadReviews()
            AdminAttendanceSection.SETTINGS -> if (_uiState.value.snapshot == null) loadDaily()
        }
    }

    fun setDailyDate(value: String) = _uiState.update { it.copy(dailyDate = value) }
    fun setDailyQuery(value: String) = _uiState.update { it.copy(dailyQuery = value) }
    fun setDailyStatus(value: String?) = _uiState.update { it.copy(dailyStatus = value) }
    fun setReportMonth(value: String) = _uiState.update { it.copy(reportMonth = value.filter(Char::isDigit).take(2)) }
    fun setReportYear(value: String) = _uiState.update { it.copy(reportYear = value.filter(Char::isDigit).take(4)) }

    fun loadDaily() {
        if (_uiState.value.isLoading) return
        viewModelScope.launch {
            val current = _uiState.value
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            when (val result = safeApiCall(parser) {
                api.daily(
                    date = current.dailyDate.ifBlank { null },
                    status = current.dailyStatus,
                    query = current.dailyQuery.ifBlank { null },
                )
            }) {
                is AppResult.Success -> _uiState.update {
                    it.copy(
                        isLoading = false,
                        snapshot = result.value,
                        dailyDate = result.value.date,
                        errorMessage = null,
                    )
                }
                is AppResult.Failure -> fail(result.error.userMessage)
            }
        }
    }

    fun loadReport() {
        val month = _uiState.value.reportMonth.toIntOrNull()?.takeIf { it in 1..12 } ?: return
        val year = _uiState.value.reportYear.toIntOrNull()?.takeIf { it in 2000..2100 } ?: return
        if (_uiState.value.isLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            when (val result = safeApiCall(parser) { api.report(month, year) }) {
                is AppResult.Success -> _uiState.update { it.copy(isLoading = false, report = result.value, errorMessage = null) }
                is AppResult.Failure -> fail(result.error.userMessage)
            }
        }
    }

    fun loadReviews() {
        if (_uiState.value.isLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            val offline = safeApiCall(parser) { api.offlineQueue() }
            val proxy = safeApiCall(parser) { api.proxyReviews() }
            if (offline is AppResult.Failure) {
                fail(offline.error.userMessage)
                return@launch
            }
            if (proxy is AppResult.Failure) {
                fail(proxy.error.userMessage)
                return@launch
            }
            _uiState.update {
                it.copy(
                    isLoading = false,
                    offlineQueue = (offline as AppResult.Success).value,
                    proxyQueue = (proxy as AppResult.Success).value,
                    errorMessage = null,
                )
            }
        }
    }

    fun manualOverride(
        record: AdminStaffAttendanceRecordDto,
        status: String,
        clockIn: String?,
        clockOut: String?,
        notes: String?,
    ) {
        mutate {
            api.manualOverride(
                AdminAttendanceManualRequestDto(
                    userId = record.userId,
                    attendanceDate = _uiState.value.dailyDate.ifBlank { _uiState.value.snapshot?.date.orEmpty() },
                    status = status,
                    clockInTime = clockIn?.takeIf(String::isNotBlank),
                    clockOutTime = clockOut?.takeIf(String::isNotBlank),
                    notes = notes?.takeIf(String::isNotBlank),
                )
            ).message
        }
    }

    fun processOffline(id: Long, approve: Boolean) {
        mutate(refreshReviews = true) {
            api.processOffline(id, AdminAttendanceReviewRequestDto(if (approve) "approve" else "reject")).message
        }
    }

    fun decideProxy(id: Long, confirmed: Boolean) {
        mutate(refreshReviews = true) {
            api.decideProxy(id, AdminAttendanceProxyDecisionRequestDto(if (confirmed) "confirmed" else "flagged")).message
        }
    }

    fun saveSettings(
        resumption: String,
        grace: Int,
        closing: String,
        geoEnabled: Boolean,
        lat: Double?,
        lng: Double?,
        radius: Int?,
    ) {
        mutate {
            api.updateSettings(
                AdminAttendanceSettingsRequestDto(
                    resumptionTime = resumption,
                    graceMinutes = grace,
                    closingTime = closing,
                    geoEnabled = geoEnabled,
                    geoLat = lat,
                    geoLng = lng,
                    geoRadiusMeters = radius,
                )
            ).message
        }
    }

    fun resetQr() {
        mutate { api.resetQr().message }
    }

    fun consumeMessage() = _uiState.update { it.copy(message = null) }

    private fun mutate(refreshReviews: Boolean = false, block: suspend () -> String?) {
        if (_uiState.value.isMutating) return
        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null) }
            when (val result = safeApiCall(parser) { block() }) {
                is AppResult.Success -> {
                    _uiState.update { it.copy(isMutating = false, message = result.value ?: "Attendance updated.") }
                    if (refreshReviews) loadReviews() else loadDaily()
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isMutating = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    private fun fail(message: String) = _uiState.update { it.copy(isLoading = false, errorMessage = message) }
}

enum class AdminAttendanceSection(val label: String) {
    DAILY("Daily"), MONTHLY("Monthly"), REVIEWS("Reviews"), SETTINGS("Settings")
}

data class AdminStaffAttendanceUiState(
    val section: AdminAttendanceSection = AdminAttendanceSection.DAILY,
    val isLoading: Boolean = false,
    val isMutating: Boolean = false,
    val snapshot: AdminStaffAttendanceResponseDto? = null,
    val report: AdminStaffAttendanceReportDto? = null,
    val offlineQueue: AdminStaffOfflineQueueDto? = null,
    val proxyQueue: AdminStaffProxyReviewQueueDto? = null,
    val dailyDate: String = "",
    val dailyQuery: String = "",
    val dailyStatus: String? = null,
    val reportMonth: String = java.time.LocalDate.now().monthValue.toString(),
    val reportYear: String = java.time.LocalDate.now().year.toString(),
    val errorMessage: String? = null,
    val message: String? = null,
)

@Composable
internal fun AdminStaffAttendanceScreen(
    state: AdminStaffAttendanceUiState,
    onSection: (AdminAttendanceSection) -> Unit,
    onDailyDate: (String) -> Unit,
    onDailyQuery: (String) -> Unit,
    onDailyStatus: (String?) -> Unit,
    onReportMonth: (String) -> Unit,
    onReportYear: (String) -> Unit,
    onRefreshDaily: () -> Unit,
    onRefreshReport: () -> Unit,
    onRefreshReviews: () -> Unit,
    onManualOverride: (AdminStaffAttendanceRecordDto, String, String?, String?, String?) -> Unit,
    onProcessOffline: (Long, Boolean) -> Unit,
    onDecideProxy: (Long, Boolean) -> Unit,
    onSaveSettings: (String, Int, String, Boolean, Double?, Double?, Int?) -> Unit,
    onResetQr: () -> Unit,
) {
    var editing by remember { mutableStateOf<AdminStaffAttendanceRecordDto?>(null) }

    Column(Modifier.fillMaxSize()) {
        Surface(color = EduCoreColors.White, shadowElevation = 1.dp) {
            Column(
                modifier = Modifier.fillMaxWidth().padding(horizontal = eduCoreScreenPadding(), vertical = EduCoreSpacing.Sm),
                verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Column(Modifier.weight(1f)) {
                        Text("Staff Attendance", style = MaterialTheme.typography.titleMedium, color = EduCoreColors.Navy900)
                        Text("School-wide attendance control", style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Slate600)
                    }
                    IconButton(
                        enabled = !state.isLoading && !state.isMutating,
                        onClick = {
                            when (state.section) {
                                AdminAttendanceSection.DAILY, AdminAttendanceSection.SETTINGS -> onRefreshDaily()
                                AdminAttendanceSection.MONTHLY -> onRefreshReport()
                                AdminAttendanceSection.REVIEWS -> onRefreshReviews()
                            }
                        },
                    ) { Icon(Icons.Default.Refresh, contentDescription = "Refresh", tint = EduCoreColors.Navy900) }
                }
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(6.dp),
                ) {
                    AdminAttendanceSection.entries.forEach { section ->
                        FilterChip(
                            selected = state.section == section,
                            onClick = { onSection(section) },
                            label = { Text(section.label, style = MaterialTheme.typography.labelSmall) },
                        )
                    }
                }
            }
        }

        state.errorMessage?.let { message ->
            Surface(color = EduCoreColors.Danger100, modifier = Modifier.fillMaxWidth()) {
                Text(message, modifier = Modifier.padding(horizontal = eduCoreScreenPadding(), vertical = 8.dp), color = EduCoreColors.Danger700, style = MaterialTheme.typography.bodySmall)
            }
        }

        when (state.section) {
            AdminAttendanceSection.DAILY -> DailyAttendanceSection(state, onDailyDate, onDailyQuery, onDailyStatus, onRefreshDaily) { editing = it }
            AdminAttendanceSection.MONTHLY -> MonthlyAttendanceSection(state, onReportMonth, onReportYear, onRefreshReport)
            AdminAttendanceSection.REVIEWS -> ReviewsAttendanceSection(state, onProcessOffline, onDecideProxy)
            AdminAttendanceSection.SETTINGS -> AttendanceSettingsSection(state, onSaveSettings, onResetQr)
        }
    }

    editing?.let { record ->
        ManualAttendanceDialog(
            record = record,
            onDismiss = { editing = null },
            onSave = { status, clockIn, clockOut, notes ->
                editing = null
                onManualOverride(record, status, clockIn, clockOut, notes)
            },
        )
    }
}

@Composable
private fun DailyAttendanceSection(
    state: AdminStaffAttendanceUiState,
    onDate: (String) -> Unit,
    onQuery: (String) -> Unit,
    onStatus: (String?) -> Unit,
    onLoad: () -> Unit,
    onEdit: (AdminStaffAttendanceRecordDto) -> Unit,
) {
    val snapshot = state.snapshot
    if (state.isLoading && snapshot == null) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Loading staff attendance")
        return
    }
    if (snapshot == null) {
        EduCoreErrorState(state.errorMessage ?: "Staff attendance is unavailable.", Modifier.fillMaxSize(), onRetry = onLoad)
        return
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm), verticalAlignment = Alignment.CenterVertically) {
                OutlinedTextField(
                    value = state.dailyDate,
                    onValueChange = onDate,
                    label = { Text("Date") },
                    singleLine = true,
                    modifier = Modifier.weight(1f),
                )
                Button(onClick = onLoad, enabled = !state.isLoading) { Text("Load") }
            }
        }
        item {
            OutlinedTextField(
                value = state.dailyQuery,
                onValueChange = onQuery,
                label = { Text("Search staff") },
                singleLine = true,
                modifier = Modifier.fillMaxWidth(),
            )
        }
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                listOf(null to "All", "early" to "Early", "present" to "Present", "late" to "Late", "absent" to "Absent").forEach { (value, label) ->
                    FilterChip(selected = state.dailyStatus == value, onClick = { onStatus(value) }, label = { Text(label, style = MaterialTheme.typography.labelSmall) })
                }
            }
        }
        item {
            Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    AttendanceSummaryTile("Eligible", snapshot.summary.eligible, Modifier.weight(1f))
                    AttendanceSummaryTile("Clocked in", snapshot.summary.clockedIn, Modifier.weight(1f))
                    AttendanceSummaryTile("Absent", snapshot.summary.absent, Modifier.weight(1f))
                }
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    AttendanceSummaryTile("Early", snapshot.summary.early, Modifier.weight(1f))
                    AttendanceSummaryTile("Present", snapshot.summary.present, Modifier.weight(1f))
                    AttendanceSummaryTile("Late", snapshot.summary.late, Modifier.weight(1f))
                }
                if (snapshot.pending.offline + snapshot.pending.proxy > 0) {
                    Text("Pending review: ${snapshot.pending.offline} offline · ${snapshot.pending.proxy} proxy", style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Slate600)
                }
            }
        }
        if (snapshot.records.isEmpty()) {
            item { EduCoreEmptyState("No matching staff", "Change the date, status or search filter.") }
        } else {
            items(snapshot.records, key = { it.userId }) { record ->
                AttendanceRecordCard(record, onEdit)
            }
        }
    }
}

@Composable
private fun MonthlyAttendanceSection(
    state: AdminStaffAttendanceUiState,
    onMonth: (String) -> Unit,
    onYear: (String) -> Unit,
    onLoad: () -> Unit,
) {
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
    ) {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm), verticalAlignment = Alignment.CenterVertically) {
                OutlinedTextField(state.reportMonth, onMonth, label = { Text("Month") }, singleLine = true, modifier = Modifier.weight(.7f))
                OutlinedTextField(state.reportYear, onYear, label = { Text("Year") }, singleLine = true, modifier = Modifier.weight(1f))
                Button(onClick = onLoad, enabled = !state.isLoading) { Text("Load") }
            }
        }
        if (state.isLoading && state.report == null) {
            item { EduCoreLoadingState(Modifier.fillMaxWidth(), "Loading monthly report") }
        } else if (state.report == null) {
            item { EduCoreEmptyState("Monthly report", "Choose a month and load the report.") }
        } else if (state.report.staff.isEmpty()) {
            item { EduCoreEmptyState("No staff records", "No attendance records are available for this month.") }
        } else {
            item { Text("${state.report.workingDays.size} working days", style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Slate600) }
            items(state.report.staff, key = { it.id }) { row ->
                Surface(Modifier.fillMaxWidth(), color = EduCoreColors.White, shape = MaterialTheme.shapes.medium, shadowElevation = 1.dp) {
                    Column(Modifier.padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                        Text(row.name, style = MaterialTheme.typography.titleSmall, color = EduCoreColors.Ink900)
                        row.staffId?.let { Text(it, style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Slate600) }
                        Text("Early ${row.early} · Present ${row.present} · Late ${row.late} · Absent ${row.absent}", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                        Text("Punctuality ${row.punctuality}% · ${row.days} eligible days", style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Navy900)
                    }
                }
            }
        }
    }
}

@Composable
private fun ReviewsAttendanceSection(
    state: AdminStaffAttendanceUiState,
    onOffline: (Long, Boolean) -> Unit,
    onProxy: (Long, Boolean) -> Unit,
) {
    val offline = state.offlineQueue?.records.orEmpty()
    val proxy = state.proxyQueue?.records.orEmpty()
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
    ) {
        if (state.isLoading && state.offlineQueue == null && state.proxyQueue == null) {
            item { EduCoreLoadingState(Modifier.fillMaxWidth(), "Loading attendance reviews") }
        }
        item { Text("Offline queue (${offline.size})", style = MaterialTheme.typography.titleSmall, color = EduCoreColors.Navy900) }
        if (offline.isEmpty()) {
            item { Text("No pending offline records.", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600) }
        } else {
            items(offline, key = { "offline-${it.id}" }) { row ->
                Surface(Modifier.fillMaxWidth(), color = EduCoreColors.White, shape = MaterialTheme.shapes.medium, shadowElevation = 1.dp) {
                    Column(Modifier.padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                        Text(row.staff ?: "Staff", style = MaterialTheme.typography.titleSmall)
                        Text(listOfNotNull(row.attendanceDate, row.clockIn?.let { "In $it" }, row.clockedBy?.let { "By $it" }).joinToString(" · "), style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                        Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                            Button(onClick = { onOffline(row.id, true) }, enabled = !state.isMutating) { Text("Approve") }
                            OutlinedButton(onClick = { onOffline(row.id, false) }, enabled = !state.isMutating) { Text("Reject") }
                        }
                    }
                }
            }
        }
        item { Text("Proxy review (${proxy.size})", style = MaterialTheme.typography.titleSmall, color = EduCoreColors.Navy900, modifier = Modifier.padding(top = EduCoreSpacing.Md)) }
        if (proxy.isEmpty()) {
            item { Text("No pending proxy attendance.", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600) }
        } else {
            items(proxy, key = { "proxy-${it.id}" }) { row ->
                Surface(Modifier.fillMaxWidth(), color = EduCoreColors.White, shape = MaterialTheme.shapes.medium, shadowElevation = 1.dp) {
                    Column(Modifier.padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                        Text(row.staff ?: "Staff", style = MaterialTheme.typography.titleSmall)
                        Text(listOfNotNull(row.attendanceDate, row.clockIn?.let { "In $it" }, row.clockedInBy?.let { "By $it" }).joinToString(" · "), style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                        if (row.hasProxyPhoto || row.hasProfilePhoto) {
                            Text("Photo evidence ${if (row.hasProxyPhoto) "captured" else "unavailable"} · Profile photo ${if (row.hasProfilePhoto) "available" else "unavailable"}", style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Slate600)
                        }
                        Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                            Button(onClick = { onProxy(row.id, true) }, enabled = !state.isMutating) { Text("Confirm") }
                            OutlinedButton(onClick = { onProxy(row.id, false) }, enabled = !state.isMutating) { Text("Flag") }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun AttendanceSettingsSection(
    state: AdminStaffAttendanceUiState,
    onSave: (String, Int, String, Boolean, Double?, Double?, Int?) -> Unit,
    onResetQr: () -> Unit,
) {
    val settings = state.snapshot?.settings
    var resumption by remember(settings) { mutableStateOf(settings?.resumptionTime.orEmpty()) }
    var grace by remember(settings) { mutableStateOf(settings?.graceMinutes?.toString().orEmpty()) }
    var closing by remember(settings) { mutableStateOf(settings?.closingTime.orEmpty()) }
    var geoEnabled by remember(settings) { mutableStateOf(settings?.geoEnabled ?: false) }
    var lat by remember(settings) { mutableStateOf(settings?.geoLat?.toString().orEmpty()) }
    var lng by remember(settings) { mutableStateOf(settings?.geoLng?.toString().orEmpty()) }
    var radius by remember(settings) { mutableStateOf(settings?.geoRadiusMeters?.toString().orEmpty()) }
    var confirmReset by remember { mutableStateOf(false) }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
    ) {
        item { OutlinedTextField(resumption, { resumption = it }, label = { Text("Resumption time (HH:mm)") }, singleLine = true, modifier = Modifier.fillMaxWidth()) }
        item { OutlinedTextField(grace, { grace = it.filter(Char::isDigit).take(3) }, label = { Text("Grace minutes") }, singleLine = true, modifier = Modifier.fillMaxWidth()) }
        item { OutlinedTextField(closing, { closing = it }, label = { Text("Closing time (HH:mm)") }, singleLine = true, modifier = Modifier.fillMaxWidth()) }
        item {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.SpaceBetween) {
                Column(Modifier.weight(1f)) {
                    Text("Geofence", style = MaterialTheme.typography.titleSmall)
                    Text("Require attendance within school radius", style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Slate600)
                }
                Switch(checked = geoEnabled, onCheckedChange = { geoEnabled = it })
            }
        }
        if (geoEnabled) {
            item {
                Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    OutlinedTextField(lat, { lat = it }, label = { Text("Latitude") }, singleLine = true, modifier = Modifier.weight(1f))
                    OutlinedTextField(lng, { lng = it }, label = { Text("Longitude") }, singleLine = true, modifier = Modifier.weight(1f))
                }
            }
            item { OutlinedTextField(radius, { radius = it.filter(Char::isDigit).take(4) }, label = { Text("Radius (metres)") }, singleLine = true, modifier = Modifier.fillMaxWidth()) }
        }
        item {
            Button(
                onClick = {
                    onSave(
                        resumption,
                        grace.toIntOrNull() ?: 0,
                        closing,
                        geoEnabled,
                        lat.toDoubleOrNull(),
                        lng.toDoubleOrNull(),
                        radius.toIntOrNull(),
                    )
                },
                enabled = !state.isMutating && resumption.isNotBlank() && closing.isNotBlank(),
                modifier = Modifier.fillMaxWidth(),
            ) { Text("Save attendance settings") }
        }
        item {
            OutlinedButton(onClick = { confirmReset = true }, enabled = !state.isMutating, modifier = Modifier.fillMaxWidth()) {
                Text("Reset school attendance QR")
            }
        }
    }

    if (confirmReset) {
        AlertDialog(
            onDismissRequest = { confirmReset = false },
            title = { Text("Reset attendance QR?") },
            text = { Text("All previously printed school attendance QR copies will become invalid.") },
            confirmButton = {
                TextButton(onClick = { confirmReset = false; onResetQr() }) { Text("Reset") }
            },
            dismissButton = { TextButton(onClick = { confirmReset = false }) { Text("Cancel") } },
        )
    }
}

@Composable
private fun AttendanceRecordCard(record: AdminStaffAttendanceRecordDto, onEdit: (AdminStaffAttendanceRecordDto) -> Unit) {
    Surface(
        modifier = Modifier.fillMaxWidth(),
        color = EduCoreColors.White,
        shape = MaterialTheme.shapes.medium,
        shadowElevation = 1.dp,
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(2.dp)) {
                Text(record.staff ?: "Staff", style = MaterialTheme.typography.titleSmall, color = EduCoreColors.Ink900)
                record.staffId?.takeIf(String::isNotBlank)?.let { Text(it, style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Slate600) }
                val details = listOfNotNull(
                    record.clockIn?.let { "In $it" },
                    record.clockOut?.let { "Out $it" },
                    record.method?.let { "via $it" },
                ).joinToString(" · ")
                if (details.isNotBlank()) Text(details, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
            val status = record.status.orEmpty().ifBlank { "absent" }
            Column(horizontalAlignment = Alignment.End, verticalArrangement = Arrangement.spacedBy(4.dp)) {
                EduCoreStatusBadge(status.replaceFirstChar { it.uppercase() }, attendanceTone(status))
                TextButton(onClick = { onEdit(record) }) { Text("Correct", style = MaterialTheme.typography.labelSmall) }
            }
        }
    }
}

@Composable
private fun ManualAttendanceDialog(
    record: AdminStaffAttendanceRecordDto,
    onDismiss: () -> Unit,
    onSave: (String, String?, String?, String?) -> Unit,
) {
    var status by remember(record) { mutableStateOf(record.status ?: "present") }
    var clockIn by remember(record) { mutableStateOf(record.clockIn?.take(5).orEmpty()) }
    var clockOut by remember(record) { mutableStateOf(record.clockOut?.take(5).orEmpty()) }
    var notes by remember(record) { mutableStateOf(record.notes.orEmpty()) }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Correct attendance") },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                Text(record.staff ?: "Staff", style = MaterialTheme.typography.titleSmall)
                Row(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                    listOf("early", "present", "late", "absent").forEach { option ->
                        FilterChip(selected = status == option, onClick = { status = option }, label = { Text(option.replaceFirstChar { it.uppercase() }, style = MaterialTheme.typography.labelSmall) })
                    }
                }
                OutlinedTextField(clockIn, { clockIn = it }, label = { Text("Clock in HH:mm") }, singleLine = true)
                OutlinedTextField(clockOut, { clockOut = it }, label = { Text("Clock out HH:mm") }, singleLine = true)
                OutlinedTextField(notes, { notes = it.take(200) }, label = { Text("Note") })
            }
        },
        confirmButton = { TextButton(onClick = { onSave(status, clockIn, clockOut, notes) }) { Text("Save") } },
        dismissButton = { TextButton(onClick = onDismiss) { Text("Cancel") } },
    )
}

@Composable
private fun AttendanceSummaryTile(label: String, value: Int, modifier: Modifier = Modifier) {
    Surface(modifier = modifier, color = EduCoreColors.White, shape = MaterialTheme.shapes.medium, shadowElevation = 1.dp) {
        Column(Modifier.padding(EduCoreSpacing.Sm)) {
            Text(value.toString(), style = MaterialTheme.typography.titleMedium, color = EduCoreColors.Navy900)
            Text(label, style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Slate600)
        }
    }
}

private fun attendanceTone(status: String): EduCoreTone = when (status.lowercase()) {
    "early", "present" -> EduCoreTone.Success
    "late" -> EduCoreTone.Warning
    "absent" -> EduCoreTone.Danger
    else -> EduCoreTone.Neutral
}
