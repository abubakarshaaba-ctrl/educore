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
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import online.educoreng.educore.core.designsystem.component.EduCoreConfirmationDialog
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

@Composable
internal fun PlatformAgentManagementScreen(
    state: PlatformAgentUiState,
    onBack: () -> Unit,
    onNew: () -> Unit,
    onEdit: (online.educoreng.educore.core.network.dto.PlatformAgentDto) -> Unit,
    onCloseEditor: () -> Unit,
    onName: (String) -> Unit,
    onEmail: (String) -> Unit,
    onPhone: (String) -> Unit,
    onStateName: (String) -> Unit,
    onCommission: (String) -> Unit,
    onReason: (String) -> Unit,
    onSave: () -> Unit,
    onRequestDeactivate: (online.educoreng.educore.core.network.dto.PlatformAgentDto) -> Unit,
    onActivate: (online.educoreng.educore.core.network.dto.PlatformAgentDto) -> Unit,
    onConfirmDeactivate: () -> Unit,
    onDismissDeactivate: () -> Unit,
    onRetry: () -> Unit,
) {
    Column(Modifier.fillMaxSize()) {
        EduCorePageHeader("Platform Agents", "Referral network administration", onBack = onBack)
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = androidx.compose.foundation.layout.PaddingValues(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
            state.message?.let { item { AgentInfoCard(it) } }
            if (state.isLoading) item { EduCoreLoadingState(message = "Loading platform agents") }

            item {
                if (!state.editorOpen) {
                    EduCorePrimaryButton("Register platform agent", onNew, Modifier.fillMaxWidth())
                } else {
                    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                            Text(if (state.editingId == null) "New agent" else "Edit agent", fontWeight = FontWeight.Bold)
                            OutlinedTextField(state.name, onName, Modifier.fillMaxWidth(), label = { Text("Name") }, singleLine = true)
                            OutlinedTextField(
                                state.email,
                                onEmail,
                                Modifier.fillMaxWidth(),
                                label = { Text("Email") },
                                singleLine = true,
                                enabled = state.editingId == null && !state.isMutating,
                            )
                            OutlinedTextField(state.phone, onPhone, Modifier.fillMaxWidth(), label = { Text("Phone") }, singleLine = true)
                            OutlinedTextField(state.stateName, onStateName, Modifier.fillMaxWidth(), label = { Text("State / territory") }, singleLine = true)
                            OutlinedTextField(state.commissionRate, onCommission, Modifier.fillMaxWidth(), label = { Text("Commission rate (%)") }, singleLine = true)
                            OutlinedTextField(
                                state.reason,
                                onReason,
                                Modifier.fillMaxWidth(),
                                label = { Text("Reason for agent change") },
                                minLines = 2,
                            )
                            EduCorePrimaryButton(
                                if (state.isMutating) "Saving…" else "Save agent",
                                onSave,
                                Modifier.fillMaxWidth(),
                                enabled = state.valid && !state.isMutating,
                            )
                            EduCoreSecondaryButton("Cancel", onCloseEditor, Modifier.fillMaxWidth(), enabled = !state.isMutating)
                        }
                    }
                }
            }

            if (!state.isLoading && state.agents.isEmpty()) {
                item { EduCoreEmptyState("No platform agents", "Register an agent to begin referral tracking.") }
            }
            items(state.agents, key = { it.id }) { agent ->
                Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                    Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            Text(agent.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, modifier = Modifier.weight(1f))
                            EduCoreStatusBadge(if (agent.active) "Active" else "Inactive", if (agent.active) EduCoreTone.Success else EduCoreTone.Warning)
                        }
                        Text(agent.email, style = MaterialTheme.typography.bodySmall)
                        Text("${agent.referrals} referrals · ${agent.commissionRate}% commission")
                        Text("Earned NGN ${agent.earned} · Paid NGN ${agent.paid}", style = MaterialTheme.typography.bodySmall)
                        Text("Referral code: ${agent.referralCode}", style = MaterialTheme.typography.bodySmall)
                        EduCoreSecondaryButton("Edit agent", { onEdit(agent) }, Modifier.fillMaxWidth(), enabled = !state.isMutating)
                        if (agent.active) {
                            EduCoreSecondaryButton("Deactivate agent", { onRequestDeactivate(agent) }, Modifier.fillMaxWidth(), enabled = !state.isMutating)
                        } else {
                            EduCorePrimaryButton("Reactivate agent", { onActivate(agent) }, Modifier.fillMaxWidth(), enabled = !state.isMutating)
                        }
                    }
                }
            }

            if (!state.isLoading && state.errorMessage != null && state.agents.isEmpty()) {
                item { EduCorePrimaryButton("Retry", onRetry, Modifier.fillMaxWidth()) }
            }
        }
    }

    EduCoreConfirmationDialog(
        visible = state.pendingDeactivateId != null,
        title = "Deactivate platform agent?",
        message = "The agent will no longer be treated as active. Existing referral and earnings history will be retained. Enter a reason before confirming.",
        confirmLabel = "Deactivate",
        onConfirm = onConfirmDeactivate,
        onDismiss = onDismissDeactivate,
        destructive = true,
    )
}

@Composable
private fun AgentInfoCard(message: String) {
    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
        Text(message, modifier = Modifier.padding(EduCoreSpacing.Lg), color = EduCoreColors.Navy900)
    }
}
