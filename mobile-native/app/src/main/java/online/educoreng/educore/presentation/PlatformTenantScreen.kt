package online.educoreng.educore.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import online.educoreng.educore.core.designsystem.component.EduCoreConfirmationDialog
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

@Composable
internal fun PlatformTenantScreen(
    state: PlatformTenantUiState,
    onBack: () -> Unit,
    onReason: (String) -> Unit,
    onMonths: (Int) -> Unit,
    onStatus: (String) -> Unit,
    onExtend: () -> Unit,
    onConfirm: () -> Unit,
    onDismissConfirmation: () -> Unit,
    onRetry: () -> Unit,
) {
    Column(Modifier.fillMaxSize()) {
        EduCorePageHeader(
            title = state.detail?.tenant?.name ?: "School Management",
            subtitle = "Platform lifecycle and subscription controls",
            onBack = onBack,
        )

        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = androidx.compose.foundation.layout.PaddingValues(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
            state.message?.let { message ->
                item {
                    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                        Text(message, modifier = Modifier.padding(EduCoreSpacing.Lg), color = EduCoreColors.Navy900)
                    }
                }
            }
            if (state.isLoading) item { EduCoreLoadingState(message = "Loading school account") }

            state.detail?.let { detail ->
                val tenant = detail.tenant
                item {
                    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                Text(tenant.name, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold, modifier = Modifier.weight(1f))
                                EduCoreStatusBadge(tenant.status.replace('_', ' '), tenant.status.schoolTone())
                            }
                            Text(tenant.slug, style = MaterialTheme.typography.bodySmall)
                            Text("${tenant.students} students · ${tenant.users} users")
                            Text(tenant.plan ?: "Pricing tier unavailable", style = MaterialTheme.typography.bodySmall)
                            tenant.email?.let { Text(it, style = MaterialTheme.typography.bodySmall) }
                            tenant.phone?.let { Text(it, style = MaterialTheme.typography.bodySmall) }
                            tenant.address?.let { Text(it, style = MaterialTheme.typography.bodySmall) }
                        }
                    }
                }

                item { EduCoreSectionHeader("School administrators", "Current tenant administrator accounts") }
                detail.admins.forEach { admin ->
                    item(key = "admin-${admin.id}") {
                        Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg)) {
                                Text(admin.name, fontWeight = FontWeight.Bold)
                                admin.email?.let { Text(it, style = MaterialTheme.typography.bodySmall) }
                                EduCoreStatusBadge(if (admin.active) "Active" else "Inactive", if (admin.active) EduCoreTone.Success else EduCoreTone.Warning)
                            }
                        }
                    }
                }

                item { EduCoreSectionHeader("Change account status", "A reason is required and the server remains authoritative") }
                item {
                    OutlinedTextField(
                        value = state.reason,
                        onValueChange = onReason,
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text("Reason for lifecycle change") },
                        minLines = 2,
                        maxLines = 4,
                        enabled = !state.isMutating,
                    )
                }
                item {
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                        listOf("active", "pending", "suspended", "subscription_expired").forEach { status ->
                            FilterChip(
                                selected = tenant.status == status,
                                enabled = !state.isMutating,
                                onClick = { onStatus(status) },
                                label = { Text(status.replace('_', ' ')) },
                            )
                        }
                    }
                }

                item { EduCoreSectionHeader("Subscription", if (detail.subscription.isFree) "Free plan — no expiry" else "Paid subscription lifecycle") }
                item {
                    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                            Text(detail.subscription.expiresAt?.let { "Expires $it" } ?: "No subscription expiry")
                            if (detail.subscription.canExtend) {
                                Text("Extension period", fontWeight = FontWeight.SemiBold)
                                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                                    detail.subscription.allowedMonths.forEach { months ->
                                        FilterChip(
                                            selected = state.extensionMonths == months,
                                            enabled = !state.isMutating,
                                            onClick = { onMonths(months) },
                                            label = { Text("$months mo") },
                                        )
                                    }
                                }
                                EduCorePrimaryButton(
                                    text = if (state.isMutating) "Applying…" else "Extend subscription",
                                    onClick = onExtend,
                                    modifier = Modifier.fillMaxWidth(),
                                    enabled = state.reasonValid && !state.isMutating,
                                )
                            } else {
                                Text("Free-plan schools are not assigned a paid-subscription expiry.", style = MaterialTheme.typography.bodySmall)
                            }
                        }
                    }
                }
            }

            if (!state.isLoading && state.detail == null) {
                item {
                    EduCorePrimaryButton(
                        text = "Retry",
                        onClick = onRetry,
                        modifier = Modifier.fillMaxWidth(),
                    )
                }
            }
        }
    }

    val pending = state.pendingAction
    EduCoreConfirmationDialog(
        visible = pending != null,
        title = when (pending) {
            PlatformTenantPendingAction.STATUS -> "Change school status?"
            PlatformTenantPendingAction.EXTEND -> "Extend subscription?"
            null -> "Confirm change"
        },
        message = when (pending) {
            PlatformTenantPendingAction.STATUS -> "This will change the school's platform access state to ${state.pendingStatus?.replace('_', ' ')}. The action will be audited."
            PlatformTenantPendingAction.EXTEND -> "This will extend the paid subscription by ${state.extensionMonths} month(s) and reactivate the school. The action will be audited."
            null -> ""
        },
        confirmLabel = "Confirm",
        onConfirm = onConfirm,
        onDismiss = onDismissConfirmation,
        destructive = pending == PlatformTenantPendingAction.STATUS && state.pendingStatus != "active",
    )
}

private fun String.schoolTone(): EduCoreTone = when (this) {
    "active" -> EduCoreTone.Success
    "pending" -> EduCoreTone.Warning
    "suspended", "subscription_expired" -> EduCoreTone.Danger
    else -> EduCoreTone.Neutral
}
