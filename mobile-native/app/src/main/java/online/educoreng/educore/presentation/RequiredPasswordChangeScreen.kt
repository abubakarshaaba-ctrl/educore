package online.educoreng.educore.presentation

import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import online.educoreng.educore.R
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.SessionSnapshot

@Composable
internal fun RequiredPasswordChangeScreen(
    session: SessionSnapshot,
    busy: Boolean,
    errorMessage: String?,
    onSubmit: (String, String, String) -> Unit,
    onLogout: () -> Unit,
) {
    var temporaryPassword by remember { mutableStateOf("") }
    var newPassword by remember { mutableStateOf("") }
    var confirmation by remember { mutableStateOf("") }

    LazyColumn(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(horizontal = EduCoreSpacing.Lg, vertical = EduCoreSpacing.Xl),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
    ) {
        item {
            Card(
                modifier = Modifier.fillMaxWidth().widthIn(max = 520.dp),
                colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
            ) {
                Column(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                ) {
                    Spacer(Modifier.height(EduCoreSpacing.Lg))
                    Image(
                        painter = painterResource(R.drawable.ic_educore_mark),
                        contentDescription = "EduCore",
                        modifier = Modifier.size(64.dp),
                    )
                    Text(
                        "Create your new password",
                        style = MaterialTheme.typography.headlineSmall,
                        fontWeight = FontWeight.Bold,
                        color = EduCoreColors.Navy900,
                        textAlign = TextAlign.Center,
                    )
                    Text(
                        "${session.user.name}, an administrator issued a temporary password for your account. Replace it before continuing to EduCore.",
                        style = MaterialTheme.typography.bodyMedium,
                        color = EduCoreColors.Slate600,
                        textAlign = TextAlign.Center,
                        modifier = Modifier.fillMaxWidth(),
                    )
                    errorMessage?.let { EduCoreErrorBanner(it) }
                    OutlinedTextField(
                        value = temporaryPassword,
                        onValueChange = { temporaryPassword = it.take(128) },
                        label = { Text("Temporary password") },
                        visualTransformation = PasswordVisualTransformation(),
                        singleLine = true,
                        enabled = !busy,
                        modifier = Modifier.fillMaxWidth(),
                    )
                    OutlinedTextField(
                        value = newPassword,
                        onValueChange = { newPassword = it.take(128) },
                        label = { Text("New password") },
                        supportingText = { Text("At least 10 characters with uppercase, lowercase and a number") },
                        visualTransformation = PasswordVisualTransformation(),
                        singleLine = true,
                        enabled = !busy,
                        modifier = Modifier.fillMaxWidth(),
                    )
                    OutlinedTextField(
                        value = confirmation,
                        onValueChange = { confirmation = it.take(128) },
                        label = { Text("Confirm new password") },
                        visualTransformation = PasswordVisualTransformation(),
                        singleLine = true,
                        enabled = !busy,
                        modifier = Modifier.fillMaxWidth(),
                    )
                    EduCorePrimaryButton(
                        text = if (busy) "Saving new password…" else "Save new password",
                        onClick = { onSubmit(temporaryPassword, newPassword, confirmation) },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !busy && temporaryPassword.isNotBlank() && newPassword.isNotBlank() && confirmation.isNotBlank(),
                        loading = busy,
                    )
                    Text(
                        "Your previous sessions were revoked when the recovery was issued. This temporary password cannot be used as a permanent password.",
                        style = MaterialTheme.typography.bodySmall,
                        color = EduCoreColors.Slate600,
                        textAlign = TextAlign.Center,
                    )
                    TextButton(onClick = onLogout, enabled = !busy) { Text("Sign out") }
                    Spacer(Modifier.height(EduCoreSpacing.Sm))
                }
            }
        }
    }
}
