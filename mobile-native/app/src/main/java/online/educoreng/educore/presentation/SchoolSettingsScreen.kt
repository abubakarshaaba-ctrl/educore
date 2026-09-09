package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTextField
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

@Composable
internal fun SchoolSettingsScreen(
    state: SchoolSettingsUiState,
    onBack: () -> Unit,
    onName: (String) -> Unit,
    onMotto: (String) -> Unit,
    onAddress: (String) -> Unit,
    onPhone: (String) -> Unit,
    onEmail: (String) -> Unit,
    onWebsite: (String) -> Unit,
    onEstablishedYear: (String) -> Unit,
    onProprietor: (String) -> Unit,
    onSlogan: (String) -> Unit,
    onSave: () -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.workspace == null) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading school settings")
    }
    val workspace = state.workspace ?: return EduCoreErrorState(
        message = state.errorMessage ?: "School settings are unavailable.",
        modifier = Modifier.fillMaxSize(),
        onRetry = onRetry,
    )

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "School Settings",
                subtitle = "Identity, contact details and general profile",
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let { item { Text(it, color = EduCoreColors.Success700) } }
        item {
            Card(
                modifier = Modifier.fillMaxWidth(),
                colors = CardDefaults.cardColors(containerColor = EduCoreColors.SurfaceBlue50),
                border = BorderStroke(1.dp, EduCoreColors.Info200),
            ) {
                Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    Text("Brand assets", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                        EduCoreStatusBadge(if (workspace.school.logoConfigured) "Logo configured" else "No logo", if (workspace.school.logoConfigured) EduCoreTone.Success else EduCoreTone.Neutral)
                        EduCoreStatusBadge(if (workspace.school.authorizedSignatureConfigured) "Signature configured" else "No signature", if (workspace.school.authorizedSignatureConfigured) EduCoreTone.Success else EduCoreTone.Neutral)
                    }
                    Text(
                        "Logo and authorized-signature replacement remain on the hardened file-upload workflow until the mobile multipart contract is fully validated.",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
            }
        }
        item { EduCoreTextField(state.name, onName, "School name", Modifier.fillMaxWidth(), enabled = state.canManage && !state.isSaving) }
        item { EduCoreTextField(state.motto, onMotto, "Motto", Modifier.fillMaxWidth(), enabled = state.canManage && !state.isSaving) }
        item { EduCoreTextField(state.address, onAddress, "Address", Modifier.fillMaxWidth(), enabled = state.canManage && !state.isSaving) }
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreTextField(state.phone, onPhone, "Phone", Modifier.weight(1f), enabled = state.canManage && !state.isSaving)
                EduCoreTextField(state.email, onEmail, "Email", Modifier.weight(1f), enabled = state.canManage && !state.isSaving)
            }
        }
        item { EduCoreTextField(state.website, onWebsite, "Website", Modifier.fillMaxWidth(), enabled = state.canManage && !state.isSaving, supportingText = "Use a full http:// or https:// address") }
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreTextField(state.establishedYear, onEstablishedYear, "Established year", Modifier.weight(1f), enabled = state.canManage && !state.isSaving)
                EduCoreTextField(state.proprietor, onProprietor, "Proprietor", Modifier.weight(1f), enabled = state.canManage && !state.isSaving)
            }
        }
        item { EduCoreTextField(state.slogan, onSlogan, "Slogan", Modifier.fillMaxWidth(), enabled = state.canManage && !state.isSaving) }
        if (state.canManage) {
            item {
                EduCorePrimaryButton(
                    text = "Save school settings",
                    onClick = onSave,
                    modifier = Modifier.fillMaxWidth(),
                    enabled = state.valid && !state.isSaving,
                    loading = state.isSaving,
                )
            }
        }
    }
}
