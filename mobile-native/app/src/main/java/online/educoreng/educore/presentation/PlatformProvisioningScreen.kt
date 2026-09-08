package online.educoreng.educore.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.foundation.text.KeyboardOptions
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

@Composable
internal fun PlatformProvisioningScreen(
    state: PlatformProvisioningUiState,
    onBack: () -> Unit,
    onSchoolName: (String) -> Unit,
    onSlug: (String) -> Unit,
    onSubdomain: (String) -> Unit,
    onSchoolEmail: (String) -> Unit,
    onPhone: (String) -> Unit,
    onAddress: (String) -> Unit,
    onAdminName: (String) -> Unit,
    onAdminEmail: (String) -> Unit,
    onAdminPassword: (String) -> Unit,
    onEmploymentStartDate: (String) -> Unit,
    onSubmit: () -> Unit,
) {
    Column(Modifier.fillMaxSize()) {
        EduCorePageHeader(
            title = "Provision School",
            subtitle = "Create tenant identity and primary administrator",
            onBack = onBack,
        )
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = androidx.compose.foundation.layout.PaddingValues(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            state.errorMessage?.let { item { EduCoreErrorBanner(it) } }

            item { EduCoreSectionHeader("School identity", "The slug/subdomain must be unique across EduCore") }
            item { Field(state.schoolName, onSchoolName, "School name") }
            item { Field(state.slug, onSlug, "School slug") }
            item { Field(state.subdomain, onSubdomain, "Subdomain (optional)") }
            item { Field(state.schoolEmail, onSchoolEmail, "School email", KeyboardType.Email) }
            item { Field(state.phone, onPhone, "School phone (optional)", KeyboardType.Phone) }
            item {
                OutlinedTextField(
                    value = state.address,
                    onValueChange = onAddress,
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("School address (optional)") },
                    minLines = 2,
                    maxLines = 4,
                    enabled = !state.isSubmitting,
                )
            }

            item { EduCoreSectionHeader("Primary administrator", "A staff administrator and employment lifecycle record are created with the school") }
            item { Field(state.adminName, onAdminName, "Administrator name") }
            item { Field(state.adminEmail, onAdminEmail, "Administrator email", KeyboardType.Email) }
            item {
                OutlinedTextField(
                    value = state.adminPassword,
                    onValueChange = onAdminPassword,
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Initial administrator password") },
                    singleLine = true,
                    enabled = !state.isSubmitting,
                    visualTransformation = PasswordVisualTransformation(),
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
                )
            }
            item { Field(state.employmentStartDate, onEmploymentStartDate, "Employment start date (YYYY-MM-DD)") }

            item {
                Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                    Text(
                        "Provisioning creates the school and primary administrator atomically, applies EduCore defaults, records platform audit events, and then sends the welcome notification. Academic calendar, classes, subjects and grading still require onboarding before normal operations.",
                        modifier = Modifier.padding(EduCoreSpacing.Lg),
                    )
                }
            }
            item {
                EduCorePrimaryButton(
                    text = if (state.isSubmitting) "Provisioning…" else "Provision school",
                    onClick = onSubmit,
                    modifier = Modifier.fillMaxWidth(),
                    enabled = state.valid && !state.isSubmitting,
                )
            }
        }
    }
}

@Composable
private fun Field(
    value: String,
    onValue: (String) -> Unit,
    label: String,
    keyboardType: KeyboardType = KeyboardType.Text,
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValue,
        modifier = Modifier.fillMaxWidth(),
        label = { Text(label) },
        singleLine = true,
        keyboardOptions = KeyboardOptions(keyboardType = keyboardType),
    )
}
