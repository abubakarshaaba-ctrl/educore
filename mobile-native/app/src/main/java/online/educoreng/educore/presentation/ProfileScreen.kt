package online.educoreng.educore.presentation

import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Badge
import androidx.compose.material.icons.filled.PhotoCamera
import androidx.compose.material.icons.filled.ReceiptLong
import androidx.compose.material.icons.filled.Save
import androidx.compose.material.icons.filled.Security
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.ui.unit.dp
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import coil.compose.AsyncImage
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreProfileHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.SessionSnapshot

@Composable
internal fun ProfileScreen(
    session: SessionSnapshot,
    onBack: () -> Unit,
) {
    val profileViewModel: ProfileSelfServiceViewModel = hiltViewModel()
    val profileState by profileViewModel.uiState.collectAsStateWithLifecycle()
    val payslipViewModel: StaffPayslipViewModel = hiltViewModel()
    val payslipState by payslipViewModel.uiState.collectAsStateWithLifecycle()

    var payslipsOpen by remember { mutableStateOf(false) }
    var idCardOpen by remember { mutableStateOf(false) }

    if (idCardOpen) {
        StaffIdCardScreen(
            state = profileState,
            onBack = { idCardOpen = false },
            onLoad = { profileViewModel.loadStaffIdCard(force = true) },
            onDownload = profileViewModel::downloadStaffIdCard,
            onDocumentOpened = profileViewModel::consumeDocument,
        )
        return
    }

    if (payslipsOpen) {
        StaffPayslipScreen(
            state = payslipState,
            onBack = {
                if (payslipState.selectedSummary != null) payslipViewModel.closeDetail() else payslipsOpen = false
            },
            onOpen = payslipViewModel::open,
            onDownload = payslipViewModel::download,
            onRetry = payslipViewModel::load,
            onDocumentOpened = payslipViewModel::consumeDocument,
        )
        return
    }

    val profile = profileState.profile
    var name by rememberSaveable { mutableStateOf("") }
    var email by rememberSaveable { mutableStateOf("") }
    var phone by rememberSaveable { mutableStateOf("") }
    var dateOfBirth by rememberSaveable { mutableStateOf("") }
    var gender by rememberSaveable { mutableStateOf("") }
    var address by rememberSaveable { mutableStateOf("") }
    var currentPassword by rememberSaveable { mutableStateOf("") }
    var newPassword by rememberSaveable { mutableStateOf("") }
    var passwordConfirmation by rememberSaveable { mutableStateOf("") }

    LaunchedEffect(profile?.id, profile?.passportVersion) {
        profile?.let {
            name = it.name
            email = it.email.orEmpty()
            phone = it.phone.orEmpty()
            dateOfBirth = it.dateOfBirth.orEmpty()
            gender = it.gender.orEmpty()
            address = it.address.orEmpty()
        }
    }

    val passportPicker = rememberLauncherForActivityResult(ActivityResultContracts.GetContent()) { uri ->
        uri?.let(profileViewModel::uploadPassport)
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "My Profile",
                subtitle = "Manage your EduCore account, passport and staff identity",
                onBack = onBack,
            )
        }

        if (profileState.isLoading && profile == null) {
            item { EduCoreLoadingState(message = "Loading profile") }
        }
        profileState.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        profileState.message?.let { message ->
            item {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    colors = CardDefaults.cardColors(containerColor = EduCoreColors.Success50),
                    border = BorderStroke(1.dp, EduCoreColors.Success200),
                ) {
                    Text(message, modifier = Modifier.padding(EduCoreSpacing.Md), style = MaterialTheme.typography.bodyMedium, color = EduCoreColors.Success700)
                }
            }
        }

        item {
            EduCoreProfileHeader(
                name = profile?.name ?: session.user.name,
                role = profile?.role ?: session.user.roleLabel,
                identifier = profile?.staffId ?: session.user.staffId ?: profile?.email ?: session.user.email,
                modifier = Modifier.fillMaxWidth(),
            )
        }

        item {
            ProfileSectionCard("Passport photograph") {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    if (profile?.hasPassport == true && !profile.passportUrl.isNullOrBlank()) {
                        AsyncImage(
                            model = profile.passportUrl,
                            contentDescription = "Passport photograph",
                            modifier = Modifier.size(88.dp).clip(RoundedCornerShape(12.dp)),
                            contentScale = ContentScale.Crop,
                        )
                    } else {
                        Card(
                            modifier = Modifier.size(88.dp),
                            shape = RoundedCornerShape(12.dp),
                            colors = CardDefaults.cardColors(containerColor = EduCoreColors.SurfaceBlue50),
                        ) {
                            Column(Modifier.fillMaxSize(), horizontalAlignment = Alignment.CenterHorizontally, verticalArrangement = Arrangement.Center) {
                                Icon(Icons.Default.PhotoCamera, contentDescription = null, tint = EduCoreColors.Navy700)
                            }
                        }
                    }
                    Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                        Text("Use a clear passport-style photograph. JPG, PNG or WebP; maximum 4 MB.", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate700)
                        EduCoreSecondaryButton(
                            text = if (profileState.isUploadingPassport) "Uploading…" else if (profile?.hasPassport == true) "Replace passport" else "Upload passport",
                            onClick = { passportPicker.launch("image/*") },
                            enabled = !profileState.isUploadingPassport,
                            leadingIcon = { Icon(Icons.Default.PhotoCamera, contentDescription = null) },
                        )
                    }
                }
            }
        }

        item {
            ProfileSectionCard("Personal details") {
                OutlinedTextField(value = name, onValueChange = { name = it }, label = { Text("Full name") }, modifier = Modifier.fillMaxWidth(), singleLine = true)
                OutlinedTextField(value = email, onValueChange = { email = it }, label = { Text("Email") }, modifier = Modifier.fillMaxWidth(), singleLine = true, keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email))
                OutlinedTextField(value = phone, onValueChange = { phone = it }, label = { Text("Phone") }, modifier = Modifier.fillMaxWidth(), singleLine = true, keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone))
                OutlinedTextField(value = dateOfBirth, onValueChange = { dateOfBirth = it }, label = { Text("Date of birth") }, supportingText = { Text("YYYY-MM-DD") }, modifier = Modifier.fillMaxWidth(), singleLine = true)
                OutlinedTextField(value = gender, onValueChange = { gender = it }, label = { Text("Gender") }, modifier = Modifier.fillMaxWidth(), singleLine = true)
                OutlinedTextField(value = address, onValueChange = { address = it }, label = { Text("Address") }, modifier = Modifier.fillMaxWidth(), minLines = 2, maxLines = 4)
                EduCorePrimaryButton(
                    text = if (profileState.isSavingProfile) "Saving…" else "Save profile changes",
                    onClick = { profileViewModel.saveProfile(name, email, phone, dateOfBirth, gender, address) },
                    modifier = Modifier.fillMaxWidth(),
                    enabled = name.isNotBlank() && email.isNotBlank() && !profileState.isSavingProfile,
                    loading = profileState.isSavingProfile,
                    leadingIcon = { Icon(Icons.Default.Save, contentDescription = null) },
                )
                Text("Role, staff ID, tenant and permissions remain controlled by the school administrator.", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
        }

        item {
            ProfileSectionCard("Password & security") {
                OutlinedTextField(
                    value = currentPassword,
                    onValueChange = { currentPassword = it },
                    label = { Text("Current password") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    visualTransformation = PasswordVisualTransformation(),
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
                )
                OutlinedTextField(
                    value = newPassword,
                    onValueChange = { newPassword = it },
                    label = { Text("New password") },
                    supportingText = { Text("Minimum 8 characters") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    visualTransformation = PasswordVisualTransformation(),
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
                )
                OutlinedTextField(
                    value = passwordConfirmation,
                    onValueChange = { passwordConfirmation = it },
                    label = { Text("Confirm new password") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    visualTransformation = PasswordVisualTransformation(),
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
                )
                EduCorePrimaryButton(
                    text = if (profileState.isChangingPassword) "Updating…" else "Change password",
                    onClick = { profileViewModel.changePassword(currentPassword, newPassword, passwordConfirmation) },
                    modifier = Modifier.fillMaxWidth(),
                    enabled = currentPassword.isNotBlank() && newPassword.isNotBlank() && passwordConfirmation.isNotBlank() && !profileState.isChangingPassword,
                    loading = profileState.isChangingPassword,
                    leadingIcon = { Icon(Icons.Default.Security, contentDescription = null) },
                )
            }
        }

        if (session.user.portal in setOf("staff", "admin")) {
            item {
                ProfileSectionCard("Staff services") {
                    StaffServiceRow(
                        icon = { Icon(Icons.Default.Badge, contentDescription = null, tint = EduCoreColors.Gold600) },
                        title = "Staff ID card",
                        description = "View your school-issued identity card and download the official PDF.",
                        buttonText = "View card",
                        onClick = {
                            idCardOpen = true
                            profileViewModel.loadStaffIdCard()
                        },
                    )
                    StaffServiceRow(
                        icon = { Icon(Icons.Default.ReceiptLong, contentDescription = null, tint = EduCoreColors.Gold600) },
                        title = "Monthly payslips",
                        description = "View issued payroll statements and PDF downloads.",
                        buttonText = "Open",
                        onClick = {
                            payslipsOpen = true
                            payslipViewModel.load()
                        },
                    )
                }
            }
        }

        item {
            ProfileSectionCard("School") {
                ProfileRow("School", session.school.name)
                ProfileRow("Academic session", session.academicPeriod.sessionName ?: "Not configured")
                ProfileRow("Current term", session.academicPeriod.termName ?: "Not configured")
                ProfileRow("Role", profile?.role ?: session.user.roleLabel)
                ProfileRow("Staff ID", profile?.staffId ?: session.user.staffId ?: "Not assigned")
            }
        }
    }
}

@Composable
private fun ProfileSectionCard(
    title: String,
    content: @Composable () -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            Text(title, style = MaterialTheme.typography.titleSmall)
            content()
        }
    }
}

@Composable
private fun StaffServiceRow(
    icon: @Composable () -> Unit,
    title: String,
    description: String,
    buttonText: String,
    onClick: () -> Unit,
) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        icon()
        Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
            Text(title, style = MaterialTheme.typography.titleSmall, color = EduCoreColors.Ink900)
            Text(description, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
        }
        EduCoreSecondaryButton(text = buttonText, onClick = onClick)
    }
}

@Composable
private fun ProfileRow(label: String, value: String) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        Text(
            text = label,
            modifier = Modifier.weight(0.42f),
            style = MaterialTheme.typography.bodySmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
        Text(
            text = value,
            modifier = Modifier.weight(0.58f),
            style = MaterialTheme.typography.bodyMedium,
        )
    }
}
