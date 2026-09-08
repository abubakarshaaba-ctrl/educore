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
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.PlatformGroupDto

@Composable
internal fun PlatformGroupDirectoryScreen(
    groups: List<PlatformGroupDto>,
    state: PlatformGroupUiState,
    onBack: () -> Unit,
    onOpen: (Long) -> Unit,
    onOpenCreate: () -> Unit,
    onCloseCreate: () -> Unit,
    onName: (String) -> Unit,
    onDescription: (String) -> Unit,
    onCreate: () -> Unit,
) {
    Column(Modifier.fillMaxSize()) {
        EduCorePageHeader("Manage School Groups", "Chains and shared-subscription campuses", onBack = onBack)
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = androidx.compose.foundation.layout.PaddingValues(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
            state.message?.let { item { InfoCard(it) } }
            item {
                if (!state.createOpen) {
                    EduCorePrimaryButton("Create school group", onOpenCreate, Modifier.fillMaxWidth())
                } else {
                    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                            Text("New school group", fontWeight = FontWeight.Bold)
                            OutlinedTextField(
                                value = state.newGroupName,
                                onValueChange = onName,
                                modifier = Modifier.fillMaxWidth(),
                                label = { Text("Group name") },
                                singleLine = true,
                            )
                            OutlinedTextField(
                                value = state.newGroupDescription,
                                onValueChange = onDescription,
                                modifier = Modifier.fillMaxWidth(),
                                label = { Text("Description (optional)") },
                                minLines = 2,
                            )
                            EduCorePrimaryButton(
                                text = if (state.isMutating) "Creating…" else "Create group",
                                onClick = onCreate,
                                modifier = Modifier.fillMaxWidth(),
                                enabled = state.createValid && !state.isMutating,
                            )
                            EduCoreSecondaryButton("Cancel", onCloseCreate, Modifier.fillMaxWidth())
                        }
                    }
                }
            }

            if (groups.isEmpty()) {
                item { EduCoreEmptyState("No school groups", "Create the first group to manage a school chain.") }
            }
            items(groups, key = { it.id }) { group ->
                Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                    Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                        Text(group.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                        Text("${group.memberCount} member school(s)")
                        group.description?.let { Text(it, style = MaterialTheme.typography.bodySmall) }
                        EduCorePrimaryButton("Manage group", { onOpen(group.id) }, Modifier.fillMaxWidth())
                    }
                }
            }
        }
    }
}

@Composable
internal fun PlatformGroupDetailScreen(
    state: PlatformGroupUiState,
    onBack: () -> Unit,
    onAddMember: (Long) -> Unit,
    onRequestLead: (Long) -> Unit,
    onRequestRemove: (Long) -> Unit,
    onConfirm: () -> Unit,
    onDismissConfirmation: () -> Unit,
    onRetry: () -> Unit,
) {
    Column(Modifier.fillMaxSize()) {
        EduCorePageHeader(
            state.detail?.group?.name ?: "School Group",
            "Membership and lead-campus controls",
            onBack = onBack,
        )
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = androidx.compose.foundation.layout.PaddingValues(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
            state.message?.let { item { InfoCard(it) } }
            if (state.isLoading) item { EduCoreLoadingState(message = "Loading school group") }

            state.detail?.let { detail ->
                item { EduCoreSectionHeader("Member campuses", "Only one lead campus governs the shared subscription") }
                if (detail.members.isEmpty()) item { EduCoreEmptyState("No members", "Add a school to this group below.") }
                items(detail.members, key = { it.tenantId }) { member ->
                    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                Text(member.name, fontWeight = FontWeight.Bold, modifier = Modifier.weight(1f))
                                EduCoreStatusBadge(
                                    if (member.role == "lead") "Lead" else "Member",
                                    if (member.role == "lead") EduCoreTone.Success else EduCoreTone.Neutral,
                                )
                            }
                            Text(member.slug, style = MaterialTheme.typography.bodySmall)
                            Text(member.status.replace('_', ' '), style = MaterialTheme.typography.bodySmall)
                            if (member.role != "lead") {
                                EduCoreSecondaryButton(
                                    text = "Make lead campus",
                                    onClick = { onRequestLead(member.tenantId) },
                                    modifier = Modifier.fillMaxWidth(),
                                )
                            }
                            EduCoreSecondaryButton(
                                text = "Remove from group",
                                onClick = { onRequestRemove(member.tenantId) },
                                modifier = Modifier.fillMaxWidth(),
                            )
                        }
                    }
                }

                item { EduCoreSectionHeader("Available schools", "Only schools not already assigned to another group are shown") }
                if (detail.availableSchools.isEmpty()) item { EduCoreEmptyState("No ungrouped schools", "Every available school is already assigned to a group.") }
                items(detail.availableSchools, key = { "available-${it.id}" }) { school ->
                    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                            Text(school.name, fontWeight = FontWeight.Bold)
                            Text("${school.slug} · ${school.status.replace('_', ' ')}", style = MaterialTheme.typography.bodySmall)
                            EduCorePrimaryButton(
                                text = "Add as member",
                                onClick = { onAddMember(school.id) },
                                modifier = Modifier.fillMaxWidth(),
                                enabled = !state.isMutating,
                            )
                        }
                    }
                }
            }

            if (!state.isLoading && state.detail == null) {
                item { EduCorePrimaryButton("Retry", onRetry, Modifier.fillMaxWidth()) }
            }
        }
    }

    EduCoreConfirmationDialog(
        visible = state.pendingAction != null,
        title = when (state.pendingAction) {
            PlatformGroupPendingAction.REMOVE_MEMBER -> "Remove school from group?"
            PlatformGroupPendingAction.SET_LEAD -> "Change lead campus?"
            null -> "Confirm group change"
        },
        message = when (state.pendingAction) {
            PlatformGroupPendingAction.REMOVE_MEMBER -> "This removes the campus from the school group. A protected lead campus cannot be removed until another lead is chosen."
            PlatformGroupPendingAction.SET_LEAD -> "This campus will become the group's lead. Its subscription will govern the group and the current lead will be demoted."
            null -> ""
        },
        confirmLabel = "Confirm",
        onConfirm = onConfirm,
        onDismiss = onDismissConfirmation,
        destructive = state.pendingAction == PlatformGroupPendingAction.REMOVE_MEMBER,
    )
}

@Composable
private fun InfoCard(message: String) {
    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
        Text(message, modifier = Modifier.padding(EduCoreSpacing.Lg), color = EduCoreColors.Navy900)
    }
}
