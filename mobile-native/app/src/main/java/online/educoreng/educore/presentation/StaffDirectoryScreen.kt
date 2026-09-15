package online.educoreng.educore.presentation

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import online.educoreng.educore.core.designsystem.component.EduCoreDropdownField
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreInfoBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreMetricCard
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSegmentedControl
import online.educoreng.educore.core.designsystem.component.EduCoreTextField
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.StaffDirectoryMemberDto

/**
 * Compatibility entry used by the existing More hub. It delegates account
 * creation actions to the same Hilt-scoped StaffDirectoryViewModel while
 * retaining the existing directory callbacks supplied by the hub.
 */
@Composable
internal fun StaffDirectoryScreen(
    state: StaffDirectoryUiState,
    @Suppress("UNUSED_PARAMETER") currentUserId: Long,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onFilter: (StaffDirectoryFilter) -> Unit,
    onRefresh: () -> Unit,
    onLoadMore: () -> Unit,
    onToggleActive: (StaffDirectoryMemberDto, Boolean) -> Unit,
) {
    val viewModel: StaffDirectoryViewModel = hiltViewModel()
    StaffDirectoryScreen(
        state = state,
        onBack = onBack,
        onQuery = onQuery,
        onFilter = onFilter,
        onRefresh = onRefresh,
        onLoadMore = onLoadMore,
        onToggleActive = onToggleActive,
        onStartCreate = viewModel::startCreate,
        onCloseCreate = viewModel::closeCreate,
        onCreateName = viewModel::setCreateName,
        onCreateEmail = viewModel::setCreateEmail,
        onCreatePhone = viewModel::setCreatePhone,
        onCreateRole = viewModel::setCreateRole,
        onCreatePassword = viewModel::setCreatePassword,
        onCreate = viewModel::createStaff,
    )
}

@Composable
internal fun StaffDirectoryScreen(
    state: StaffDirectoryUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onFilter: (StaffDirectoryFilter) -> Unit,
    onRefresh: () -> Unit,
    onLoadMore: () -> Unit,
    onToggleActive: (StaffDirectoryMemberDto, Boolean) -> Unit,
    onStartCreate: () -> Unit,
    onCloseCreate: () -> Unit,
    onCreateName: (String) -> Unit,
    onCreateEmail: (String) -> Unit,
    onCreatePhone: (String) -> Unit,
    onCreateRole: (String) -> Unit,
    onCreatePassword: (String) -> Unit,
    onCreate: () -> Unit,
) {
    if (state.isCreateOpen) {
        StaffCreateScreen(
            state,
            onCloseCreate,
            onCreateName,
            onCreateEmail,
            onCreatePhone,
            onCreateRole,
            onCreatePassword,
            onCreate,
        )
        return
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Staff directory",
                subtitle = when {
                    state.isLoading && state.members.isEmpty() -> "Loading authorised staff accounts"
                    state.filteredTotal == state.totalCount -> "${state.totalCount} staff records"
                    else -> "${state.filteredTotal} matching records"
                },
                onBack = onBack,
                actions = {
                    IconButton(onClick = onRefresh, enabled = !state.isLoading && !state.isLoadingMore) {
                        Icon(Icons.Default.Refresh, contentDescription = "Refresh staff directory")
                    }
                },
            )
        }

        item {
            EduCorePrimaryButton(
                text = "Add staff",
                onClick = onStartCreate,
                modifier = Modifier.fillMaxWidth(),
                leadingIcon = { Icon(Icons.Default.Add, contentDescription = null) },
            )
        }

        item { StaffDirectorySummary(state) }

        item {
            EduCoreSearchBar(
                value = state.query,
                onValueChange = onQuery,
                placeholder = "Search name, staff ID or role",
                enabled = !state.isLoading,
            )
        }

        item {
            val filters = StaffDirectoryFilter.entries
            EduCoreSegmentedControl(
                options = filters.map {
                    when (it) {
                        StaffDirectoryFilter.ALL -> "All"
                        StaffDirectoryFilter.ACTIVE -> "Active"
                        StaffDirectoryFilter.INACTIVE -> "Inactive"
                    }
                },
                selectedIndex = filters.indexOf(state.filter).coerceAtLeast(0),
                onSelected = { index -> filters.getOrNull(index)?.let(onFilter) },
                enabled = !state.isLoading,
            )
        }

        state.errorMessage?.let { message -> item { EduCoreErrorBanner(message) } }
        state.message?.let { message -> item { EduCoreInfoBanner(message, title = "Staff directory updated") } }

        when {
            state.isLoading && state.members.isEmpty() -> item {
                EduCoreLoadingState(message = "Loading staff directory")
            }
            state.visibleMembers.isEmpty() -> item {
                EduCoreEmptyState(
                    title = "No staff records found",
                    message = "Adjust the search or status filter and try again.",
                    actionLabel = if (state.query.isNotBlank()) "Clear search" else "Refresh",
                    onAction = if (state.query.isNotBlank()) ({ onQuery("") }) else onRefresh,
                )
            }
            else -> {
                items(state.visibleMembers, key = StaffDirectoryMemberDto::id) { member ->
                    StaffDirectoryRow(
                        member = member,
                        isSaving = state.savingMemberId == member.id,
                        mutationLocked = state.savingMemberId != null,
                        onToggleActive = { active -> onToggleActive(member, active) },
                    )
                }
                if (state.hasMore || state.isLoadingMore) {
                    item {
                        EduCoreSecondaryButton(
                            text = if (state.isLoadingMore) {
                                "Loading more…"
                            } else {
                                "Load more (${state.members.size} of ${state.filteredTotal})"
                            },
                            onClick = onLoadMore,
                            modifier = Modifier.fillMaxWidth(),
                            enabled = state.hasMore && !state.isLoadingMore,
                        )
                    }
                }
            }
        }
        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

@Composable
private fun StaffCreateScreen(
    state: StaffDirectoryUiState,
    onBack: () -> Unit,
    onName: (String) -> Unit,
    onEmail: (String) -> Unit,
    onPhone: (String) -> Unit,
    onRole: (String) -> Unit,
    onPassword: (String) -> Unit,
    onCreate: () -> Unit,
) {
    val draft = state.createDraft
    LazyColumn(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader("Add staff", "Create a school staff account", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item { StaffField("Full name *", draft.name, onName) }
        item { StaffField("Email address *", draft.email, onEmail) }
        item { StaffField("Phone", draft.phone, onPhone) }
        item { StaffRoleSelector(draft.role, onRole) }
        item { StaffField("Temporary password *", draft.password, onPassword) }
        item {
            Text(
                "Password must contain at least 8 characters. The new staff member can change it after signing in.",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }
        item {
            EduCorePrimaryButton(
                text = if (state.isCreating) "Creating account…" else "Create staff account",
                onClick = onCreate,
                modifier = Modifier.fillMaxWidth(),
                enabled = draft.valid && !state.isCreating,
                loading = state.isCreating,
            )
        }
    }
}

@Composable
private fun StaffField(label: String, value: String, onChange: (String) -> Unit) {
    EduCoreTextField(
        value = value,
        onValueChange = onChange,
        label = label,
        modifier = Modifier.fillMaxWidth(),
    )
}

@Composable
private fun StaffRoleSelector(selected: String, onSelect: (String) -> Unit) {
    val roles = StaffDirectoryViewModel.CREATE_ROLES
    val selectedRole = roles.firstOrNull { it.first == selected }
    EduCoreDropdownField(
        label = "Role *",
        options = roles,
        selected = selectedRole,
        optionLabel = { it.second },
        onSelected = { onSelect(it.first) },
        modifier = Modifier.fillMaxWidth(),
        placeholder = "Select staff role",
    )
}

@Composable
private fun StaffDirectorySummary(state: StaffDirectoryUiState) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
    ) {
        EduCoreMetricCard(
            label = "Staff",
            value = state.totalCount.toString(),
            modifier = Modifier.weight(1f),
            tone = EduCoreTone.Brand,
        )
        EduCoreMetricCard(
            label = "Active",
            value = state.activeCount.toString(),
            modifier = Modifier.weight(1f),
            tone = EduCoreTone.Success,
        )
        EduCoreMetricCard(
            label = "Inactive",
            value = state.inactiveCount.toString(),
            modifier = Modifier.weight(1f),
            tone = EduCoreTone.Neutral,
        )
    }
}

@Composable
private fun StaffDirectoryRow(
    member: StaffDirectoryMemberDto,
    isSaving: Boolean,
    mutationLocked: Boolean,
    onToggleActive: (Boolean) -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            Column(Modifier.weight(1f)) {
                Text(member.name, fontWeight = FontWeight.SemiBold, color = EduCoreColors.Ink900)
                Spacer(Modifier.height(EduCoreSpacing.Xs))
                Text(member.role, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                member.staffId?.takeIf(String::isNotBlank)?.let {
                    Text("Staff ID: $it", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                }
                if (isSaving) {
                    Text("Updating account…", style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Navy700)
                }
            }
            Column(horizontalAlignment = Alignment.End) {
                Text(
                    if (member.active) "Active" else "Inactive",
                    style = MaterialTheme.typography.labelMedium,
                    color = if (member.active) EduCoreColors.Success700 else EduCoreColors.Muted500,
                )
                Switch(
                    checked = member.active,
                    onCheckedChange = onToggleActive,
                    enabled = !mutationLocked,
                )
            }
        }
    }
}
