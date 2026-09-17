package online.educoreng.educore.presentation

import android.graphics.Bitmap
import android.graphics.Color
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Badge
import androidx.compose.material.icons.filled.Download
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.foundation.lazy.LazyColumn
import coil.compose.AsyncImage
import com.google.zxing.BarcodeFormat
import com.google.zxing.MultiFormatWriter
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.StaffIdCardDto

@Composable
internal fun StaffIdCardScreen(
    state: ProfileSelfServiceUiState,
    onBack: () -> Unit,
    onLoad: () -> Unit,
    onDownload: () -> Unit,
    onDocumentOpened: () -> Unit,
) {
    OpenDocumentEffect(state.document, onDocumentOpened)
    LaunchedEffect(Unit) { onLoad() }

    LazyColumn(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Staff ID Card",
                subtitle = "View and download your official school identity card",
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }

        if (state.isLoadingCard && state.idCard == null) {
            item { EduCoreLoadingState(message = "Loading staff ID card") }
        } else {
            state.idCard?.let { card ->
                item { StaffIdentityCard(card) }
                item {
                    EduCorePrimaryButton(
                        text = if (state.isDownloadingCard) "Preparing PDF…" else "Download official ID card PDF",
                        onClick = onDownload,
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !state.isDownloadingCard,
                        loading = state.isDownloadingCard,
                        leadingIcon = { Icon(Icons.Default.Download, contentDescription = null) },
                    )
                }
                item {
                    EduCoreSecondaryButton(
                        text = "Refresh card",
                        onClick = onLoad,
                        modifier = Modifier.fillMaxWidth(),
                        leadingIcon = { Icon(Icons.Default.Refresh, contentDescription = null) },
                    )
                }
            } ?: if (!state.isLoadingCard) {
                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                        border = BorderStroke(1.dp, EduCoreColors.Line300),
                    ) {
                        Column(
                            Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                        ) {
                            Icon(Icons.Default.Badge, contentDescription = null, tint = EduCoreColors.Navy700)
                            Text("ID card unavailable", style = MaterialTheme.typography.titleMedium)
                            Text(
                                "Your school staff identity card could not be loaded. Refresh after confirming that your account is an active staff account.",
                                style = MaterialTheme.typography.bodySmall,
                                textAlign = TextAlign.Center,
                                color = EduCoreColors.Slate700,
                            )
                            EduCoreSecondaryButton(text = "Retry", onClick = onLoad)
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun StaffIdentityCard(card: StaffIdCardDto) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(18.dp),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line300),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
    ) {
        Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
            Column(
                modifier = Modifier.fillMaxWidth().background(EduCoreColors.Navy900).padding(EduCoreSpacing.Lg),
                horizontalAlignment = Alignment.CenterHorizontally,
            ) {
                Text(
                    text = card.school.name ?: "EduCore School",
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Bold,
                    color = EduCoreColors.White,
                    textAlign = TextAlign.Center,
                )
                card.school.motto?.takeIf(String::isNotBlank)?.let {
                    Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Gold200, textAlign = TextAlign.Center)
                }
                Text("STAFF IDENTITY CARD", style = MaterialTheme.typography.labelMedium, color = EduCoreColors.Gold200)
            }

            Column(
                modifier = Modifier.fillMaxWidth().padding(horizontal = EduCoreSpacing.Lg),
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                if (card.hasPhoto && !card.photo.isNullOrBlank()) {
                    AsyncImage(
                        model = card.photo,
                        contentDescription = "Staff passport",
                        modifier = Modifier.size(112.dp).clip(RoundedCornerShape(12.dp)),
                        contentScale = ContentScale.Crop,
                    )
                } else {
                    Box(
                        modifier = Modifier.size(112.dp).clip(RoundedCornerShape(12.dp)).background(EduCoreColors.SurfaceBlue50),
                        contentAlignment = Alignment.Center,
                    ) {
                        Icon(Icons.Default.Badge, contentDescription = null, tint = EduCoreColors.Navy700, modifier = Modifier.size(48.dp))
                    }
                }
                Text(card.name, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold, color = EduCoreColors.Ink900, textAlign = TextAlign.Center)
                Text(card.role ?: "Staff", style = MaterialTheme.typography.titleSmall, color = EduCoreColors.Navy700)
                card.department?.takeIf(String::isNotBlank)?.let { Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate700) }
            }

            Column(
                modifier = Modifier.fillMaxWidth().padding(horizontal = EduCoreSpacing.Lg),
                verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                IdCardRow("Staff ID", card.staffId ?: "Not assigned")
                card.dateJoined?.let { IdCardRow("Date joined", it) }
                card.email?.let { IdCardRow("Email", it) }
                card.phone?.let { IdCardRow("Phone", it) }
            }

            card.qrPayload?.takeIf(String::isNotBlank)?.let { payload ->
                Column(
                    modifier = Modifier.fillMaxWidth().padding(horizontal = EduCoreSpacing.Lg),
                    horizontalAlignment = Alignment.CenterHorizontally,
                ) {
                    Image(
                        bitmap = rememberQrBitmap(payload).asImageBitmap(),
                        contentDescription = "Staff verification QR code",
                        modifier = Modifier.size(108.dp),
                    )
                    Text("Scan to verify staff identity", style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Slate600)
                }
            }

            Column(
                modifier = Modifier.fillMaxWidth().background(EduCoreColors.SurfaceBlue50).padding(EduCoreSpacing.Md),
                horizontalAlignment = Alignment.CenterHorizontally,
            ) {
                card.school.address?.takeIf(String::isNotBlank)?.let { Text(it, style = MaterialTheme.typography.labelSmall, textAlign = TextAlign.Center, color = EduCoreColors.Slate700) }
                card.school.website?.takeIf(String::isNotBlank)?.let { Text(it, style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Navy700) }
            }
        }
    }
}

@Composable
private fun IdCardRow(label: String, value: String) {
    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
        Text(label, modifier = Modifier.weight(0.35f), style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
        Text(value, modifier = Modifier.weight(0.65f), style = MaterialTheme.typography.bodyMedium, color = EduCoreColors.Ink900)
    }
}

@Composable
private fun rememberQrBitmap(payload: String): Bitmap = remember(payload) {
    val matrix = MultiFormatWriter().encode(payload, BarcodeFormat.QR_CODE, 512, 512)
    Bitmap.createBitmap(512, 512, Bitmap.Config.ARGB_8888).apply {
        for (x in 0 until 512) {
            for (y in 0 until 512) {
                setPixel(x, y, if (matrix[x, y]) Color.BLACK else Color.WHITE)
            }
        }
    }
}
