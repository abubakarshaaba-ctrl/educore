package online.educoreng.educore.presentation

import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.google.zxing.BarcodeFormat
import com.journeyapps.barcodescanner.BarcodeEncoder
import com.squareup.moshi.Moshi
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.AdminStaffAttendanceApi
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.dto.AdminStaffAttendanceQrDto
import online.educoreng.educore.core.network.safeApiCall

@HiltViewModel
class AdminStaffAttendanceQrViewModel @Inject constructor(
    factory: ApiClientFactory,
    moshi: Moshi,
) : ViewModel() {
    private val api = factory.create(AdminStaffAttendanceApi::class.java)
    private val parser = moshi
    private val _uiState = MutableStateFlow(AdminStaffAttendanceQrUiState())
    val uiState: StateFlow<AdminStaffAttendanceQrUiState> = _uiState.asStateFlow()

    fun load() {
        if (_uiState.value.isLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            when (val result = safeApiCall(parser) { api.qr() }) {
                is AppResult.Success -> _uiState.update {
                    it.copy(isLoading = false, qr = result.value, errorMessage = null)
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isLoading = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun resetQr() {
        if (_uiState.value.isMutating) return
        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null, message = null) }
            when (val result = safeApiCall(parser) { api.resetQr() }) {
                is AppResult.Success -> {
                    _uiState.update {
                        it.copy(
                            isMutating = false,
                            message = result.value.message ?: "School attendance QR reset.",
                        )
                    }
                    load()
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isMutating = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }
}

data class AdminStaffAttendanceQrUiState(
    val isLoading: Boolean = false,
    val isMutating: Boolean = false,
    val qr: AdminStaffAttendanceQrDto? = null,
    val errorMessage: String? = null,
    val message: String? = null,
)

/** Native administrator screen for viewing and rotating the school attendance QR. */
@Composable
internal fun AdminStaffAttendanceQrScreen(
    state: AdminStaffAttendanceQrUiState,
    onRefresh: () -> Unit,
    onReset: () -> Unit,
    onClose: () -> Unit,
) {
    val qrBitmap = remember(state.qr?.payload) {
        state.qr?.payload?.takeIf { it.isNotBlank() }?.let { payload ->
            runCatching {
                BarcodeEncoder().encodeBitmap(payload, BarcodeFormat.QR_CODE, 720, 720)
            }.getOrNull()
        }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(EduCoreSpacing.Lg),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = "School attendance QR",
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.SemiBold,
                )
                Text(
                    text = "Staff scan this code when school QR verification is required.",
                    style = MaterialTheme.typography.bodySmall,
                    color = EduCoreColors.Slate600,
                )
            }
            OutlinedButton(onClick = onClose) { Text("Close") }
        }

        state.errorMessage?.let { message ->
            Card(
                modifier = Modifier.fillMaxWidth(),
                colors = CardDefaults.cardColors(containerColor = EduCoreColors.Danger100),
            ) {
                Text(
                    text = message,
                    modifier = Modifier.padding(EduCoreSpacing.Md),
                    color = EduCoreColors.Danger700,
                    style = MaterialTheme.typography.bodyMedium,
                )
            }
        }

        state.message?.let { message ->
            Card(
                modifier = Modifier.fillMaxWidth(),
                colors = CardDefaults.cardColors(containerColor = EduCoreColors.Success100),
            ) {
                Text(
                    text = message,
                    modifier = Modifier.padding(EduCoreSpacing.Md),
                    color = EduCoreColors.Success700,
                    style = MaterialTheme.typography.bodyMedium,
                )
            }
        }

        when {
            state.isLoading && state.qr == null -> {
                Spacer(Modifier.height(EduCoreSpacing.Xl))
                CircularProgressIndicator(modifier = Modifier.align(Alignment.CenterHorizontally))
                Text(
                    text = "Loading school attendance QR…",
                    modifier = Modifier.align(Alignment.CenterHorizontally),
                    style = MaterialTheme.typography.bodyMedium,
                )
            }

            state.qr != null -> {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                ) {
                    Column(
                        modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
                        horizontalAlignment = Alignment.CenterHorizontally,
                        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    ) {
                        if (qrBitmap != null) {
                            Image(
                                bitmap = qrBitmap.asImageBitmap(),
                                contentDescription = "School attendance QR code",
                                modifier = Modifier.size(280.dp),
                            )
                        } else {
                            Text(
                                text = "QR image could not be generated. Refresh the code.",
                                color = EduCoreColors.Danger700,
                            )
                        }
                        state.qr.school?.takeIf { it.isNotBlank() }?.let {
                            Text(it, style = MaterialTheme.typography.titleMedium)
                        }
                        Text(
                            text = "Type: ${state.qr.type}",
                            style = MaterialTheme.typography.bodySmall,
                            color = EduCoreColors.Slate600,
                        )
                        state.qr.generatedAt?.takeIf { it.isNotBlank() }?.let {
                            Text(
                                text = "Generated: $it",
                                style = MaterialTheme.typography.bodySmall,
                                color = EduCoreColors.Slate600,
                            )
                        }
                        state.qr.note?.takeIf { it.isNotBlank() }?.let {
                            Text(
                                text = it,
                                style = MaterialTheme.typography.bodySmall,
                                color = EduCoreColors.Slate600,
                            )
                        }
                    }
                }
            }

            else -> Text(
                text = "No school attendance QR is currently available.",
                style = MaterialTheme.typography.bodyMedium,
                color = EduCoreColors.Slate600,
            )
        }

        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            OutlinedButton(
                onClick = onRefresh,
                enabled = !state.isLoading && !state.isMutating,
                modifier = Modifier.weight(1f),
            ) {
                Text(if (state.isLoading) "Refreshing…" else "Refresh")
            }
            Button(
                onClick = onReset,
                enabled = !state.isLoading && !state.isMutating,
                modifier = Modifier.weight(1f),
            ) {
                Text(if (state.isMutating) "Resetting…" else "Reset QR")
            }
        }
    }
}
