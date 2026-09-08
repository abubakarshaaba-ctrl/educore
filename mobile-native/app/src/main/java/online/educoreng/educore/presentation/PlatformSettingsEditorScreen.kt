package online.educoreng.educore.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.foundation.text.KeyboardOptions
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

private val PLATFORM_FIELDS = listOf(
    "platform_name" to "Platform name",
    "support_email" to "Support email",
    "support_phone" to "Support phone",
    "support_whatsapp" to "Support WhatsApp",
    "support_website" to "Support website",
    "office_address" to "Office address",
    "grace_period_days" to "Grace period (days)",
    "bank_transfer_bank_name" to "Bank transfer bank",
    "bank_transfer_account_name" to "Bank transfer account name",
    "bank_transfer_account_number" to "Bank transfer account number",
    "default_sms_gateway" to "Default SMS gateway",
    "sms_sender_id" to "SMS sender ID",
)

@Composable
internal fun PlatformSettingsEditorScreen(
    state: PlatformSettingsEditorState,
    onBack: () -> Unit,
    onValue: (String, String) -> Unit,
    onMaintenance: (Boolean) -> Unit,
    onReason: (String) -> Unit,
    onSave: () -> Unit,
    onRetry: () -> Unit,
) {
    Column(Modifier.fillMaxSize()) {
        EduCorePageHeader("Platform Settings", "Operational configuration", onBack = onBack)
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = androidx.compose.foundation.layout.PaddingValues(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
            state.message?.let { item { SettingsInfoCard(it) } }
            if (state.isLoading) item { EduCoreLoadingState(message = "Loading platform settings") }
            if (!state.isLoading && state.values.isEmpty()) item { EduCoreEmptyState("Settings unavailable", "Refresh the platform settings contract.") }

            if (state.values.isNotEmpty()) {
                item { EduCoreSectionHeader("Operations", "Only the approved platform settings are editable") }
                PLATFORM_FIELDS.forEach { (key, label) ->
                    item(key = key) {
                        OutlinedTextField(
                            value = state.values[key].orEmpty(),
                            onValueChange = { onValue(key, it) },
                            modifier = Modifier.fillMaxWidth(),
                            label = { Text(label) },
                            enabled = !state.isMutating,
                            singleLine = key != "office_address",
                            minLines = if (key == "office_address") 2 else 1,
                        )
                    }
                }
                item {
                    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                        Row(
                            Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically,
                        ) {
                            Column(Modifier.weight(1f)) {
                                Text("Maintenance mode", fontWeight = FontWeight.Bold)
                                Text("Restricts normal platform access while maintenance is active.", style = MaterialTheme.typography.bodySmall)
                            }
                            Switch(checked = state.maintenanceMode, onCheckedChange = onMaintenance, enabled = !state.isMutating)
                        }
                    }
                }
                item {
                    OutlinedTextField(
                        value = state.reason,
                        onValueChange = onReason,
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text("Reason for settings change") },
                        minLines = 2,
                        enabled = !state.isMutating,
                    )
                }
                item {
                    EduCorePrimaryButton(
                        text = if (state.isMutating) "Saving…" else "Save platform settings",
                        onClick = onSave,
                        modifier = Modifier.fillMaxWidth(),
                        enabled = state.settingsValid && !state.isMutating,
                    )
                }
            }

            if (!state.isLoading && state.values.isEmpty()) {
                item { EduCorePrimaryButton("Retry", onRetry, Modifier.fillMaxWidth()) }
            }
        }
    }
}

@Composable
internal fun PlatformGatewayEditorScreen(
    state: PlatformSettingsEditorState,
    onBack: () -> Unit,
    onSelect: (String) -> Unit,
    onClear: () -> Unit,
    onPublic: (String) -> Unit,
    onSecret: (String) -> Unit,
    onContract: (String) -> Unit,
    onLive: (Boolean) -> Unit,
    onReason: (String) -> Unit,
    onSave: () -> Unit,
    onRetry: () -> Unit,
) {
    Column(Modifier.fillMaxSize()) {
        EduCorePageHeader("Payment Gateways", "Write-only credential management", onBack = onBack)
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = androidx.compose.foundation.layout.PaddingValues(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
            state.message?.let { item { SettingsInfoCard(it) } }
            if (state.isLoading) item { EduCoreLoadingState(message = "Loading payment gateways") }

            if (state.selectedProvider == null) {
                item { EduCoreSectionHeader("Choose gateway", "Secret credentials are never downloaded to this device") }
                if (!state.isLoading && state.gateways.isEmpty()) item { EduCoreEmptyState("No gateway contract", "Refresh gateway status from the platform.") }
                items(state.gateways, key = { it.provider }) { gateway ->
                    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                Text(gateway.provider.replaceFirstChar(Char::uppercase), fontWeight = FontWeight.Bold)
                                EduCoreStatusBadge(
                                    if (gateway.configured) "Configured" else "Incomplete",
                                    if (gateway.configured) EduCoreTone.Success else EduCoreTone.Warning,
                                )
                            }
                            Text(if (gateway.live) "LIVE mode" else "Test mode", style = MaterialTheme.typography.bodySmall)
                            gateway.publicIdentifier?.let { Text("Public identifier: $it", style = MaterialTheme.typography.bodySmall) }
                            gateway.contractCode?.let { Text("Contract: $it", style = MaterialTheme.typography.bodySmall) }
                            Text("Secret credential: ${if (gateway.secretConfigured) "configured" else "missing"}", style = MaterialTheme.typography.bodySmall)
                            EduCorePrimaryButton("Configure ${gateway.provider}", { onSelect(gateway.provider) }, Modifier.fillMaxWidth())
                        }
                    }
                }
                if (!state.isLoading && state.gateways.isEmpty()) item { EduCorePrimaryButton("Retry", onRetry, Modifier.fillMaxWidth()) }
            } else {
                val current = state.gateways.firstOrNull { it.provider == state.selectedProvider }
                item {
                    EduCoreSectionHeader(
                        state.selectedProvider.replaceFirstChar(Char::uppercase),
                        "Leave an existing identifier or secret blank to preserve the server-side value",
                    )
                }
                item {
                    OutlinedTextField(
                        value = state.gatewayPublicKey,
                        onValueChange = onPublic,
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text("Replacement public identifier") },
                        placeholder = { Text(current?.publicIdentifier ?: "Required for first-time configuration") },
                        enabled = !state.isMutating,
                    )
                }
                item {
                    OutlinedTextField(
                        value = state.gatewaySecretKey,
                        onValueChange = onSecret,
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text("Replacement secret credential") },
                        placeholder = { Text(if (current?.secretConfigured == true) "Leave blank to preserve existing secret" else "Required for first-time configuration") },
                        enabled = !state.isMutating,
                        singleLine = true,
                        visualTransformation = PasswordVisualTransformation(),
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
                    )
                }
                if (state.selectedProvider == "monnify") {
                    item {
                        OutlinedTextField(
                            value = state.gatewayContractCode,
                            onValueChange = onContract,
                            modifier = Modifier.fillMaxWidth(),
                            label = { Text("Replacement contract code") },
                            placeholder = { Text(current?.contractCode ?: "Required for first-time configuration") },
                            enabled = !state.isMutating,
                        )
                    }
                }
                item {
                    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                        Row(
                            Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically,
                        ) {
                            Column(Modifier.weight(1f)) {
                                Text("LIVE mode", fontWeight = FontWeight.Bold)
                                Text("Enable only after test credentials and callbacks are verified.", style = MaterialTheme.typography.bodySmall)
                            }
                            Switch(checked = state.gatewayLive, onCheckedChange = onLive, enabled = !state.isMutating)
                        }
                    }
                }
                item {
                    OutlinedTextField(
                        value = state.reason,
                        onValueChange = onReason,
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text("Reason for gateway change") },
                        minLines = 2,
                        enabled = !state.isMutating,
                    )
                }
                item {
                    EduCorePrimaryButton(
                        text = if (state.isMutating) "Saving…" else "Save gateway configuration",
                        onClick = onSave,
                        modifier = Modifier.fillMaxWidth(),
                        enabled = state.gatewayValid && !state.isMutating,
                    )
                }
                item { EduCoreSecondaryButton("Choose another gateway", onClear, Modifier.fillMaxWidth()) }
            }
        }
    }
}

@Composable
private fun SettingsInfoCard(message: String) {
    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
        Text(message, modifier = Modifier.padding(EduCoreSpacing.Lg), color = EduCoreColors.Navy900)
    }
}
