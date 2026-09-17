package online.educoreng.educore.presentation

import android.graphics.Bitmap
import android.graphics.Color
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Badge
import androidx.compose.material.icons.filled.CameraAlt
import androidx.compose.material.icons.filled.Download
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Save
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import coil.compose.AsyncImage
import com.google.zxing.BarcodeFormat
import com.google.zxing.MultiFormatWriter
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreProfileHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.SessionSnapshot
import online.educoreng.educore.core.network.dto.StaffIdCardDto

@Composable
internal fun StaffProfileScreen(
    session: SessionSnapshot,
    onBack: () -> Unit,
) {
    val viewModel: ProfileViewModel = hiltViewModel()
    val state by viewModel.uiState.collectAsStateWithLifecycle()
    val passportPicker = rememberLauncherForActivityResult(ActivityResultContracts.GetContent()) { uri ->
        uri?.let(viewModel::uploadPassport)
    }

    OpenDocumentEffect(state.document, viewModel::consumeDocument)

    if (state.isLoading && state.profile == null) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Loading your profile")
        return
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            Column(
                modifier = Modifier.fillMaxWidth().widthIn(max = 820.dp),
                verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
            ) {
                EduCorePageHeader(
                    title = "My Profile",
                    subtitle = "Manage your personal details, security and staff identity",
                    onBack = onBack,
                )

                EduCoreProfileHeader(
                    name = state.profile?.name ?: session.user.name,
                    role = state.profile?.role ?: session.user.roleLabel,
                    identifier = state.profile?.staffId ?: session.user.staffId ?: state.profile?.email ?: session.user.email,
                    modifier = Modifier.fillMaxWidth(),
                )

                state.errorMessage?.let { EduCoreErrorBanner(it) }
                state.message?.let { ProfileSuccessBanner(it) }
            }
        }

        item {
            ProfileCard("Passport photograph", Icons.Default.CameraAlt) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    PassportPreview(
                        photoUrl = state.profile?.passportUrl,
                        name = state.profile?.name ?: session.user.name,
                    )
                    Column(
                        modifier = Modifier.weight(1f),
                        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    ) {
                        Text(
                            if (state.profile?.hasPassport == true) "Passport on file" else "No passport uploaded",
                            style = MaterialTheme.typography.titleSmall,
                            color = EduCoreColors.Ink900,
                        )
                        Text(
                            "JPG, PNG or WebP · maximum 4 MB. This image is also used on your staff ID card.",
                            style = MaterialTheme.typography.bodySmall,
                            color = EduCoreColors.Slate600,
                        )
                        EduCoreSecondaryButton(
                            text = if (state.isUploadingPassport) "Uploading…" else if (state.profile?.hasPassport == true) "Replace passport" else "Upload passport",
                            onClick = { passportPicker.launch("image/*") },
                            enabled = !state.isUploadingPassport,
                        )
                    }
                }
            }
        }

        item {
            ProfileCard("Personal details", Icons.Default.Person) {
                OutlinedTextField(
                    value = state.name,
                    onValueChange = viewModel::setName,
                    label = { Text("Full name") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                )
                OutlinedTextField(
                    value = state.email,
                    onValueChange = viewModel::setEmail,
                    label = { Text("Email") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                )
                OutlinedTextField(
                    value = state.phone,
                    onValueChange = viewModel::setPhone,
                    label = { Text("Phone") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                )
                OutlinedTextField(
                    value = state.dateOfBirth,
                    onValueChange = viewModel::setDateOfBirth,
                    label = { Text("Date of birth") },
                    supportingText = { Text("YYYY-MM-DD") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                )
                OutlinedTextField(
                    value = state.gender,
                    onValueChange = viewModel::setGender,
                    label = { Text("Gender") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                )
                OutlinedTextField(
                    value = state.address,
                    onValueChange = viewModel::setAddress,
                    label = { Text("Address") },
                    minLines = 2,
                    maxLines = 4,
                    modifier = Modifier.fillMaxWidth(),
                )
                EduCorePrimaryButton(
                    text = if (state.isSaving) "Saving…" else "Save profile changes",
                    onClick = viewModel::saveProfile,
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !state.isSaving,
                    loading = state.isSaving,
                    leadingIcon = { Icon(Icons.Default.Save, contentDescription = null) },
                )
                Text(
                    "Staff ID, role, employment status and school assignment are controlled by authorized school administrators.",
                    style = MaterialTheme.typography.bodySmall,
                    color = EduCoreColors.Slate600,
                )
            }
        }

        item {
            ProfileCard("Change password", Icons.Default.Lock) {
                OutlinedTextField(
                    value = state.currentPassword,
                    onValueChange = viewModel::setCurrentPassword,
                    label = { Text("Current password") },
                    visualTransformation = PasswordVisualTransformation(),
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                )
                OutlinedTextField(
                    value = state.newPassword,
                    onValueChange = viewModel::setNewPassword,
                    label = { Text("New password") },
                    supportingText = { Text("Minimum 8 characters") },
                    visualTransformation = PasswordVisualTransformation(),
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                )
                OutlinedTextField(
                    value = state.confirmPassword,
                    onValueChange = viewModel::setConfirmPassword,
                    label = { Text("Confirm new password") },
                    visualTransformation = PasswordVisualTransformation(),
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                )
                EduCorePrimaryButton(
                    text = if (state.isChangingPassword) "Changing password…" else "Change password",
                    onClick = viewModel::changePassword,
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !state.isChangingPassword,
                    loading = state.isChangingPassword,
                    leadingIcon = { Icon(Icons.Default.Lock, contentDescription = null) },
                )
            }
        }

        state.idCard?.let { card ->
            item {
                ProfileCard("Staff ID card", Icons.Default.Badge) {
                    Text(
                        "View your current EduCore staff identity card below. The download uses the official school-issued front/back PDF layout.",
                        style = MaterialTheme.typography.bodySmall,
                        color = EduCoreColors.Slate600,
                    )
                    StaffIdCardPreview(card)
                    EduCorePrimaryButton(
                        text = if (state.isDownloadingIdCard) "Preparing ID card…" else "Download staff ID card PDF",
                        onClick = viewModel::downloadIdCard,
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !state.isDownloadingIdCard,
                        loading = state.isDownloadingIdCard,
                        leadingIcon = { Icon(Icons.Default.Download, contentDescription = null) },
                    )
                }
            }
        }

        item {
            ProfileCard("Account context", Icons.Default.Badge) {
                ReadOnlyProfileRow("Staff ID", state.profile?.staffId ?: session.user.staffId ?: "Not assigned")
                ReadOnlyProfileRow("Role", state.profile?.role ?: session.user.roleLabel)
                ReadOnlyProfileRow("School", session.school.name)
                ReadOnlyProfileRow("Academic session", session.academicPeriod.sessionName ?: "Not set")
                ReadOnlyProfileRow("Current term", session.academicPeriod.termName ?: "Not set")
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.End,
                ) {
                    EduCoreStatusBadge("Active", EduCoreTone.Success)
                }
            }
        }

        item { Spacer(Modifier.height(EduCoreSpacing.Md)) }
    }
}

@Composable
private fun ProfileCard(
    title: String,
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    content: @Composable Column.() -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth().widthIn(max = 820.dp),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            Row(
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Icon(icon, contentDescription = null, tint = EduCoreColors.Navy900)
                Text(title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold, color = EduCoreColors.Navy900)
            }
            HorizontalDivider(color = EduCoreColors.Line200)
            content()
        }
    }
}

@Composable
private fun PassportPreview(photoUrl: String?, name: String) {
    Box(
        modifier = Modifier
            .size(92.dp)
            .clip(RoundedCornerShape(14.dp))
            .background(EduCoreColors.SurfaceBlue50),
        contentAlignment = Alignment.Center,
    ) {
        if (!photoUrl.isNullOrBlank()) {
            AsyncImage(
                model = photoUrl,
                contentDescription = "Passport photograph",
                modifier = Modifier.fillMaxSize(),
                contentScale = ContentScale.Crop,
            )
        } else {
            Text(
                name.trim().take(1).uppercase().ifBlank { "?" },
                style = MaterialTheme.typography.headlineLarge,
                fontWeight = FontWeight.Bold,
                color = EduCoreColors.Navy900,
            )
        }
    }
}

@Composable
private fun ProfileSuccessBanner(message: String) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.SurfaceBlue50),
        border = BorderStroke(1.dp, EduCoreColors.Info200),
    ) {
        Text(
            message,
            modifier = Modifier.padding(EduCoreSpacing.Md),
            style = MaterialTheme.typography.bodyMedium,
            color = EduCoreColors.Navy900,
        )
    }
}

@Composable
private fun StaffIdCardPreview(card: StaffIdCardDto) {
    val qrBitmap = remember(card.qrPayload) { card.qrPayload?.let(::createQrBitmap) }

    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Gold600),
        shape = RoundedCornerShape(18.dp),
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            Text(
                card.school.name ?: "EduCore School",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = EduCoreColors.Navy900,
            )
            card.school.motto?.takeIf(String::isNotBlank)?.let {
                Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
            PassportPreview(card.photo, card.name)
            Text(card.name, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold, color = EduCoreColors.Navy900)
            Text((card.role ?: "Staff").uppercase(), style = MaterialTheme.typography.labelMedium, color = EduCoreColors.Gold600)
            HorizontalDivider(color = EduCoreColors.Line200)
            ReadOnlyProfileRow("Staff ID", card.staffId ?: "Not assigned")
            ReadOnlyProfileRow("Department", card.department ?: "Not recorded")
            ReadOnlyProfileRow("Phone", card.phone ?: "Not recorded")
            card.dateJoined?.let { ReadOnlyProfileRow("Date joined", it) }
            qrBitmap?.let {
                Image(
                    bitmap = it.asImageBitmap(),
                    contentDescription = "Staff ID QR code",
                    modifier = Modifier.size(132.dp).clip(RoundedCornerShape(10.dp)),
                )
                Text("Personal staff QR", style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Slate600)
            }
        }
    }
}

@Composable
private fun ReadOnlyProfileRow(label: String, value: String) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        verticalAlignment = Alignment.Top,
    ) {
        Text(
            label,
            modifier = Modifier.weight(0.38f),
            style = MaterialTheme.typography.bodySmall,
            color = EduCoreColors.Slate600,
        )
        Text(
            value,
            modifier = Modifier.weight(0.62f),
            style = MaterialTheme.typography.bodyMedium,
            color = EduCoreColors.Ink900,
        )
    }
}

private fun createQrBitmap(value: String): Bitmap? = runCatching {
    val matrix = MultiFormatWriter().encode(value, BarcodeFormat.QR_CODE, 512, 512)
    Bitmap.createBitmap(matrix.width, matrix.height, Bitmap.Config.ARGB_8888).apply {
        for (x in 0 until matrix.width) {
            for (y in 0 until matrix.height) {
                setPixel(x, y, if (matrix[x, y]) Color.BLACK else Color.WHITE)
            }
        }
    }
}.getOrNull()
