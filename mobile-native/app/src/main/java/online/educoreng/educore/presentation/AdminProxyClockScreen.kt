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

    fun submit(staffId: Long, date: String, clockIn: String, reason: String) {
        if (_uiState.value.isSaving) return
        if (reason.trim().length < 3) {
            _uiState.update { it.copy(errorMessage = "Enter the reason for clocking in on behalf of this staff member.") }
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
                        reason = reason.trim(),
                        device = "android",
                    )
                )
            }) {
                is AppResult.Success -> _uiState.update {
                    it.copy(
                        isSaving = false,
                        message = result.value.message ?: "Proxy clock-in recorded.",
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
    onSubmit: (Long, String, String, String) -> Unit,
    onClose: () -> Unit,
) {
    val context = LocalContext.current
    val today = remember { LocalDate.now() }
    val now = remember { LocalTime.now().withSecond(0).withNano(0) }
    var selectedStaffId by remember(staff) { mutableStateOf(staff.firstOrNull()?.userId) }
    var staffExpanded by remember { mutableStateOf(false) }
    var date by remember { mutableStateOf(today.toString()) }
    var clockIn by remember { mutableStateOf(now.format(DateTimeFormatter.ofPattern("HH:mm"))) }
    var reason by remember { mutableStateOf("") }

    val selectedStaff = staff.firstOrNull { it.userId == selectedStaffId }
    val valid = selectedStaffId != null && date.isNotBlank() && clockIn.isNotBlank() && reason.trim().length >= 3

    Column(
        modifier = Modifier.fillMaxSize().padding(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        EduCorePageHeader(
            title = "Clock in by proxy",
            subtitle = "Record attendance on behalf of a staff member",
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

        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
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
                modifier = Modifier.weight(1f),
            ) { Text("Date: $date") }

            OutlinedButton(
                onClick = {
                    val current = runCatching { LocalTime.parse(clockIn) }.getOrDefault(now)
                    TimePickerDialog(
                        context,
                        { _, hour, minute -> clockIn = "%02d:%02d".format(hour, minute) },
                        current.hour,
                        current.minute,
                        true,
                    ).show()
                },
                enabled = !state.isSaving,
                modifier = Modifier.weight(1f),
            ) { Text("Time: $clockIn") }
        }

        OutlinedTextField(
            value = reason,
            onValueChange = { reason = it.take(250) },
            label = { Text("Reason") },
            supportingText = { Text("Required for audit trail") },
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
                onSubmit(id, date, clockIn, reason)
            },
            enabled = valid && !state.isSaving,
            modifier = Modifier.fillMaxWidth(),
        ) {
            Text(if (state.isSaving) "Saving…" else "Clock in by proxy")
        }
    }
}
