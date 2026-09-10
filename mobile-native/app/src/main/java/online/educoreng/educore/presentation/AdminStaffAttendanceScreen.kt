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
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
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
import online.educoreng.educore.core.network.dto.AdminStaffAttendanceResponseDto
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

    fun load() {
        if (_uiState.value.isLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            when (val result = safeApiCall(parser) { api.daily() }) {
                is AppResult.Success -> _uiState.update {
                    it.copy(isLoading = false, snapshot = result.value, errorMessage = null)
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isLoading = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }
}

data class AdminStaffAttendanceUiState(
    val isLoading: Boolean = false,
    val snapshot: AdminStaffAttendanceResponseDto? = null,
    val errorMessage: String? = null,
)

@Composable
internal fun AdminStaffAttendanceScreen(
    state: AdminStaffAttendanceUiState,
    onBack: () -> Unit,
    onRefresh: () -> Unit,
) {
    val snapshot = state.snapshot
    if (state.isLoading && snapshot == null) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Loading staff attendance")
        return
    }
    if (snapshot == null) {
        EduCoreErrorState(
            message = state.errorMessage ?: "Staff attendance is unavailable.",
            modifier = Modifier.fillMaxSize(),
            onRetry = onRefresh,
        )
        return
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                IconButton(onClick = onBack) {
                    Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back", tint = EduCoreColors.Navy900)
                }
                Column(Modifier.weight(1f)) {
                    Text("Staff Attendance", style = MaterialTheme.typography.titleLarge, color = EduCoreColors.Navy900)
                    Text(snapshot.date, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                }
                IconButton(onClick = onRefresh, enabled = !state.isLoading) {
                    Icon(Icons.Default.Refresh, contentDescription = "Refresh", tint = EduCoreColors.Navy900)
                }
            }
        }

        state.errorMessage?.let { message ->
            item {
                Surface(
                    color = EduCoreColors.Danger100,
                    shape = MaterialTheme.shapes.medium,
                ) {
                    Text(
                        message,
                        modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
                        color = EduCoreColors.Danger700,
                        style = MaterialTheme.typography.bodySmall,
                    )
                }
            }
        }

        item {
            Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                Text("Today's summary", style = MaterialTheme.typography.titleMedium, color = EduCoreColors.Navy900)
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                ) {
                    AttendanceSummaryTile("Eligible", snapshot.summary.eligible, Modifier.weight(1f))
                    AttendanceSummaryTile("Clocked in", snapshot.summary.clockedIn, Modifier.weight(1f))
                }
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                ) {
                    AttendanceSummaryTile("Early", snapshot.summary.early, Modifier.weight(1f))
                    AttendanceSummaryTile("Late", snapshot.summary.late, Modifier.weight(1f))
                    AttendanceSummaryTile("Absent", snapshot.summary.absent, Modifier.weight(1f))
                }
            }
        }

        item {
            Text("Attendance records", style = MaterialTheme.typography.titleMedium, color = EduCoreColors.Navy900)
        }

        if (snapshot.records.isEmpty()) {
            item {
                EduCoreEmptyState(
                    title = "No attendance records",
                    message = "No staff member has clocked in for this date yet.",
                )
            }
        } else {
            items(snapshot.records, key = { it.id }) { record ->
                Surface(
                    modifier = Modifier.fillMaxWidth(),
                    color = EduCoreColors.White,
                    shape = MaterialTheme.shapes.large,
                    shadowElevation = 1.dp,
                ) {
                    Row(
                        modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                    ) {
                        Column(Modifier.weight(1f)) {
                            Text(record.staff ?: "Staff", style = MaterialTheme.typography.titleSmall, color = EduCoreColors.Ink900)
                            record.staffId?.takeIf(String::isNotBlank)?.let {
                                Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                            }
                            val times = listOfNotNull(
                                record.clockIn?.let { "In $it" },
                                record.clockOut?.let { "Out $it" },
                            ).joinToString(" · ")
                            if (times.isNotBlank()) {
                                Text(times, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                            }
                        }
                        val status = record.status.orEmpty().ifBlank { "present" }
                        EduCoreStatusBadge(status.replaceFirstChar { it.uppercase() }, attendanceTone(status))
                    }
                }
            }
        }
    }
}

@Composable
private fun AttendanceSummaryTile(label: String, value: Int, modifier: Modifier = Modifier) {
    Surface(
        modifier = modifier,
        color = EduCoreColors.White,
        shape = MaterialTheme.shapes.large,
        shadowElevation = 1.dp,
    ) {
        Column(Modifier.padding(EduCoreSpacing.Md)) {
            Text(value.toString(), style = MaterialTheme.typography.titleLarge, color = EduCoreColors.Navy900)
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
