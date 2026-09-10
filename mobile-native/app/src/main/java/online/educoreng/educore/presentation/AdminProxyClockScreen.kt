package online.educoreng.educore.presentation

import android.app.DatePickerDialog
import android.app.TimePickerDialog
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Button
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.squareup.moshi.Moshi
import dagger.hilt.android.lifecycle.HiltViewModel
import java.time.LocalDate
import java.time.LocalTime
import java.time.format.DateTimeFormatter
import java.util.Locale
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.AdminStaffAttendanceApi
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.dto.AdminAttendanceProxyClockRequestDto
import online.educoreng.educore.core.network.dto.AdminStaffAttendanceRecordDto
import online.educoreng.educore.core.network.safeApiCall

@HiltViewModel
class AdminProxyClockViewModel @Inject constructor(
    factory: ApiClientFactory,
    moshi: Moshi,
) : ViewModel() {
    private val api = factory.create(AdminStaffAttendanceApi::class.java)
    private val parser = moshi
    private val _uiState = MutableStateFlow(AdminProxyClockUiState())
    val uiState: StateFlow<AdminProxyClockUiState> = _uiState.asStateFlow()

    fun submit(
        staffId: Long,
        date: String,
        clockIn: String,
        clockOut: String?,
        reason: String,
        status: String?,
    ) {
        if (_uiState.value.isSaving) return
        if (reason.trim().length < 5) {
            _uiState.update { it.copy(errorMessage = "Enter a reason of at least 5 characters for this proxy attendance.") }
            return
        }
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            when (val result = safeApiCall(parser) {
                api.proxyClock(
                    AdminAttendanceProxyClockRequestDto(
                        staffId = staffId,
                        date = date,
                        clockInTime = clockIn,
                        clockOutTime = clockOut?.takeIf(String::isNotBlank),
                        reason = reason.trim(),
                        status = status,
                        device = "android",
                    )
                )
            }) {
                is AppResult.Success -> _uiState.update {
                    it.copy(
                        isSaving = false,
                        message = result.value.message ?: "Attendance recorded by proxy.",
                        errorMessage = null,
                    )
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isSaving = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun clearMessage() = _uiState.update { it.copy(message = null, errorMessage = null) }
}

data class AdminProxyClockUiState(
    val isSaving: Boolean = false,
    val message: String? = null,
    val errorMessage: String? = null,
)

@Composable
internal fun AdminProxyClockScreen(
    state: AdminProxyClockUiState,
    staff: List<AdminStaffAttendanceRecordDto>,
    onSubmit: (Long, String, String, String?, String, String?) -> Unit,
    onClose: () -> Unit,
) {
    val context = LocalContext.current
    val today = remember { LocalDate.now() }
    val now = remember { LocalTime.now().withSecond(0).withNano(0) }
    var selectedStaffId by remember(staff) { mutableStateOf(staff.firstOrNull()?.userId) }
    var staffExpanded by remember { mutableStateOf(false) }
    var statusExpanded by remember { mutableStateOf(false) }
    var date by remember { mutableStateOf(today.toString()) }
    var clockIn by remember { mutableStateOf(now.format(DateTimeFormatter.ofPattern("HH:mm"))) }
    var clockOut by remember { mutableStateOf("") }
    var reason by remember { mutableStateOf("") }
    var status by remember { mutableStateOf<String?>(null) }

    val selectedStaff = staff.firstOrNull { it.userId == selectedStaffId }
    val valid = selectedStaffId != null && date.isNotBlank() && clockIn.isNotBlank() && reason.trim().length >= 5
    val displayDate = runCatching {
        LocalDate.parse(date).format(DateTimeFormatter.ofPattern("dd MMM yyyy", Locale.ENGLISH))
    }.getOrDefault(date)

    fun showTimePicker(currentValue: String, onPicked: (String) -> Unit) {
        val current = runCatching { LocalTime.parse(currentValue) }.getOrDefault(now)
        TimePickerDialog(
            context,
            { _, hour, minute -> onPicked(String.format(Locale.US, "%02d:%02d", hour, minute)) },
            current.hour,
            current.minute,
            true,
        ).show()
    }

    Column(
        modifier = Modifier.fillMaxSize().padding(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        EduCorePageHeader(
            title = "Clock in by proxy",
            subtitle = "Administrative exception · recorded with an audit trail",
            onBack = onClose,
        )

        state.errorMessage?.let { EduCoreErrorBanner(it) }
        state.message?.let {
            Text(it, color = EduCoreColors.Success700, style = MaterialTheme.typography.bodyMedium)
        }

        Text("Staff member", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
        Box(Modifier.fillMaxWidth()) {
            OutlinedButton(
                onClick = { staffExpanded = true },
                enabled = !state.isSaving && staff.isNotEmpty(),
                modifier = Modifier.fillMaxWidth(),
            ) {
                Text(selectedStaff?.staff ?: "Select staff", modifier = Modifier.weight(1f))
                Text("▾")
            }
            DropdownMenu(
                expanded = staffExpanded,
                onDismissRequest = { staffExpanded = false },
                modifier = Modifier.fillMaxWidth(.92f),
            ) {
                staff.forEach { row ->
                    DropdownMenuItem(
                        text = {
                            Column {
                                Text(row.staff ?: "Staff")
                                row.staffId?.takeIf(String::isNotBlank)?.let {
                                    Text(it, style = MaterialTheme.typography.labelSmall)
                                }
                            }
                        },
                        onClick = {
                            selectedStaffId = row.userId
                            staffExpanded = false
                        },
                    )
                }
            }
        }

        OutlinedButton(
            onClick = {
                val current = runCatching { LocalDate.parse(date) }.getOrDefault(today)
                DatePickerDialog(
                    context,
                    { _, year, month, day -> date = LocalDate.of(year, month + 1, day).toString() },
                    current.year,
                    current.monthValue - 1,
                    current.dayOfMonth,
                ).show()
            },
            enabled = !state.isSaving,
            modifier = Modifier.fillMaxWidth(),
        ) { Text("Attendance date · $displayDate") }

        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            OutlinedButton(
                onClick = { showTimePicker(clockIn) { clockIn = it } },
                enabled = !state.isSaving,
                modifier = Modifier.weight(1f),
            ) { Text("Clock in · $clockIn") }

            OutlinedButton(
                onClick = { showTimePicker(clockOut.ifBlank { clockIn }) { clockOut = it } },
                enabled = !state.isSaving,
                modifier = Modifier.weight(1f),
            ) { Text(if (clockOut.isBlank()) "Clock out · Optional" else "Clock out · $clockOut") }
        }

        if (clockOut.isNotBlank()) {
            OutlinedButton(
                onClick = { clockOut = "" },
                enabled = !state.isSaving,
                modifier = Modifier.fillMaxWidth(),
            ) { Text("Remove clock-out time") }
        }

        Text("Attendance status", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
        Box(Modifier.fillMaxWidth()) {
            OutlinedButton(
                onClick = { statusExpanded = true },
                enabled = !state.isSaving,
                modifier = Modifier.fillMaxWidth(),
            ) {
                Text(status?.replaceFirstChar(Char::uppercase) ?: "Automatic from clock-in time", modifier = Modifier.weight(1f))
                Text("▾")
            }
            DropdownMenu(
                expanded = statusExpanded,
                onDismissRequest = { statusExpanded = false },
                modifier = Modifier.fillMaxWidth(.92f),
            ) {
                DropdownMenuItem(
                    text = { Text("Automatic from clock-in time") },
                    onClick = { status = null; statusExpanded = false },
                )
                listOf("early", "present", "late", "absent").forEach { option ->
                    DropdownMenuItem(
                        text = { Text(option.replaceFirstChar(Char::uppercase)) },
                        onClick = { status = option; statusExpanded = false },
                    )
                }
            }
        }

        OutlinedTextField(
            value = reason,
            onValueChange = { reason = it.take(1000) },
            label = { Text("Reason") },
            supportingText = {
                Text(
                    if (reason.isNotEmpty() && reason.trim().length < 5) {
                        "Reason must contain at least 5 characters."
                    } else {
                        "Required. This is retained in the proxy attendance audit trail."
                    }
                )
            },
            isError = reason.isNotEmpty() && reason.trim().length < 5,
            minLines = 3,
            enabled = !state.isSaving,
            modifier = Modifier.fillMaxWidth(),
        )

        if (staff.isEmpty()) {
            Text(
                "No staff records are currently available. Refresh Staff Attendance and try again.",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }

        Button(
            onClick = {
                val id = selectedStaffId ?: return@Button
                onSubmit(id, date, clockIn, clockOut.ifBlank { null }, reason, status)
            },
            enabled = valid && !state.isSaving,
            modifier = Modifier.fillMaxWidth(),
        ) {
            Text(if (state.isSaving) "Saving…" else "Record by proxy")
        }
    }
}
