package online.educoreng.educore.presentation

import android.graphics.Bitmap
import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.Button
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.google.zxing.BarcodeFormat
import com.google.zxing.MultiFormatWriter
import com.google.zxing.common.BitMatrix
import com.squareup.moshi.Moshi
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
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
            _uiState.update { it.copy(isMutating = true, errorMessage = null) }
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

@Composable
internal fun AdminStaffAttendanceQrScreen(
    state: AdminStaffAttendanceQrUiState,
    onRefresh: () -> Unit,
    onReset: () -> Unit,
    onClose: () -> Unit,
) {
    LaunchedEffect(Unit) {
        if (state.qr == null && !state.isLoading) onRefresh()
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Column(Modifier.weight(1f)) {
                Text("School Attendance QR", style = MaterialTheme.typography.titleMedium, color = EduCoreColors.Navy900)
                Text("For staff clock-in at the school", style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Slate600)
            }
            IconButton(onClick = onRefresh, enabled = !state.isLoading && !state.isMutating) {
                Icon(Icons.Default.Refresh, contentDescription = "Refresh QR", tint = EduCoreColors.Navy900)
            }
        }

        state.errorMessage?.let { message ->
            EduCoreErrorState(message, Modifier.fillMaxWidth(), onRetry = onRefresh)
        }

        if (state.isLoading && state.qr == null) {
            EduCoreLoadingState(Modifier.fillMaxWidth(), "Loading attendance QR")
        } else {
            state.qr?.let { qr ->
                Surface(
                    modifier = Modifier.fillMaxWidth(),
                    color = EduCoreColors.White,
                    shape = MaterialTheme.shapes.large,
                    shadowElevation = 2.dp,
                ) {
                    Column(
                        modifier = Modifier.padding(EduCoreSpacing.Lg),
                        horizontalAlignment = Alignment.CenterHorizontally,
                        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                    ) {
                        qr.school?.takeIf(String::isNotBlank)?.let {
                            Text(it, style = MaterialTheme.typography.titleSmall, color = EduCoreColors.Ink900)
                        }
                        val bitmap = remember(qr.payload) { qrBitmap(qr.payload, 720) }
                        bitmap?.let {
                            Image(
                                bitmap = it.asImageBitmap(),
                                contentDescription = "School staff attendance QR",
                                modifier = Modifier.size(260.dp),
                            )
                        }
                        Text(
                            qr.note ?: "This QR stays valid until it is reset by a school administrator.",
                            style = MaterialTheme.typography.bodySmall,
                            color = EduCoreColors.Slate600,
                        )
                        qr.generatedAt?.let {
                            Text("Generated $it", style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Slate600)
                        }
                    }
                }
            }
        }

        state.message?.let {
            Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Navy900)
        }

        OutlinedButton(
            onClick = onReset,
            enabled = !state.isMutating,
            modifier = Modifier.fillMaxWidth(),
        ) {
            Text(if (state.isMutating) "Resetting…" else "Reset school QR")
        }

        Button(onClick = onClose, modifier = Modifier.fillMaxWidth()) {
            Text("Back to Staff Attendance")
        }
    }
}

private fun qrBitmap(payload: String, size: Int): Bitmap? = runCatching {
    val matrix: BitMatrix = MultiFormatWriter().encode(payload, BarcodeFormat.QR_CODE, size, size)
    Bitmap.createBitmap(size, size, Bitmap.Config.ARGB_8888).apply {
        for (y in 0 until size) {
            for (x in 0 until size) {
                setPixel(x, y, if (matrix[x, y]) android.graphics.Color.BLACK else android.graphics.Color.WHITE)
            }
        }
    }
}.getOrNull()
