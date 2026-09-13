package online.educoreng.educore.presentation

import androidx.compose.foundation.background
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.StaffDirectoryMemberDto

/**
 * Compatibility entry used by the existing More hub. It delegates the new
 * account-creation actions to the same Hilt-scoped StaffDirectoryViewModel,
 * while retaining the existing directory callbacks supplied by the hub.
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
        StaffCreateScreen(state, onCloseCreate, onCreateName, onCreateEmail, onCreatePhone, onCreateRole, onCreatePassword, onCreate)
        return
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
                EduCorePageHeader("Staff directory", "Authorized account management", onBack = onBack, modifier = Modifier.weight(1f))
                IconButton(onClick = onRefresh, enabled = !state.isLoading && !state.isLoadingMore) {
                    Icon(Icons.Default.Refresh, contentDescription = "Refresh staff directory")
                }
            }
        }
        item { EduCorePrimaryButton("Add staff", onStartCreate, Modifier.fillMaxWidth(), leadingIcon = { Icon(Icons.Default.Add, null) }) }
        item { StaffDirectorySummary(state) }
        item {
            OutlinedTextField(
                value = state.query, onValueChange = onQuery, modifier = Modifier.fillMaxWidth(), singleLine = true,
                label = { Text("Search staff") }, placeholder = { Text("Name, staff ID or role") },
                leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) },
            )
        }
        item {
            Row(Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                StaffDirectoryFilter.entries.forEach { filter ->
                    FilterChip(
                        selected = state.filter == filter, onClick = { onFilter(filter) },
                        label = { Text(when (filter) { StaffDirectoryFilter.ALL -> "All"; StaffDirectoryFilter.ACTIVE -> "Active"; StaffDirectoryFilter.INACTIVE -> "Inactive" }) },
                    )
                }
            }
        }
        state.errorMessage?.let { message -> item { MessageCard(message, true, onRefresh) } }
        state.message?.let { message -> item { Text(message, style = MaterialTheme.typography.bodyMedium, color = MaterialTheme.colorScheme.primary) } }
        item {
            Text(
                when {
                    state.isLoading && state.members.isEmpty() -> "Loading directory…"
                    state.filteredTotal == state.totalCount -> "${state.totalCount} staff records"
                    else -> "${state.filteredTotal} matching records"
                },
                style = MaterialTheme.typography.labelLarge, color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }
        when {
            state.isLoading && state.members.isEmpty() -> item {
                Column(Modifier.fillMaxWidth().padding(vertical = 40.dp), horizontalAlignment = Alignment.CenterHorizontally) {
                    CircularProgressIndicator(); Spacer(Modifier.height(12.dp)); Text("Loading staff directory…")
                }
            }
            state.visibleMembers.isEmpty() -> item {
                Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)) {
                    Column(Modifier.padding(20.dp)) {
                        Text("No staff records found", fontWeight = FontWeight.SemiBold); Spacer(Modifier.height(4.dp))
                        Text("Adjust the search or status filter, then try again.", color = MaterialTheme.colorScheme.onSurfaceVariant)
                    }
                }
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
                if (state.hasMore || state.isLoadingMore) item {
                    OutlinedButton(onClick = onLoadMore, enabled = state.hasMore && !state.isLoadingMore, modifier = Modifier.fillMaxWidth()) {
                        if (state.isLoadingMore) {
                            CircularProgressIndicator(Modifier.width(18.dp).height(18.dp), strokeWidth = 2.dp); Spacer(Modifier.width(8.dp)); Text("Loading more…")
                        } else Text("Load more (${state.members.size} of ${state.filteredTotal})")
                    }
                }
            }
        }
        item { Spacer(Modifier.height(16.dp)) }
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
        state.errorMessage?.let { item { MessageCard(it, true, null) } }
        item { StaffField("Full name *", draft.name, onName) }
        item { StaffField("Email address *", draft.email, onEmail) }
        item { StaffField("Phone", draft.phone, onPhone) }
        item { StaffRoleSelector(draft.role, onRole) }
        item { StaffField("Temporary password *", draft.password, onPassword) }
        item { Text("Password must contain at least 8 characters. The new staff member can change it after signing in.", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant) }
        item {
            EduCorePrimaryButton(
                if (state.isCreating) "Creating account…" else "Create staff account",
                onCreate, Modifier.fillMaxWidth(), enabled = draft.valid && !state.isCreating, loading = state.isCreating,
            )
        }
    }
}

@Composable
private fun StaffField(label: String, value: String, onChange: (String) -> Unit) {
    OutlinedTextField(value, onChange, Modifier.fillMaxWidth(), label = { Text(label) }, singleLine = true)
}

@Composable
private fun StaffRoleSelector(selected: String, onSelect: (String) -> Unit) {
    var expanded by remember { mutableStateOf(false) }
    val roles = StaffDirectoryViewModel.CREATE_ROLES
    Column(Modifier.fillMaxWidth()) {
        Text("Role *", style = MaterialTheme.typography.labelLarge)
        Spacer(Modifier.height(4.dp))
        OutlinedButton({ expanded = true }, Modifier.fillMaxWidth()) {
            Text(roles.firstOrNull { it.first == selected }?.second ?: selected, Modifier.weight(1f))
        }
        DropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
            roles.forEach { (key, label) -> DropdownMenuItem(text = { Text(label) }, onClick = { expanded = false; onSelect(key) }) }
        }
    }
}

@Composable
private fun MessageCard(message: String, error: Boolean, retry: (() -> Unit)?) {
    Card(
        colors = CardDefaults.cardColors(containerColor = if (error) MaterialTheme.colorScheme.errorContainer else MaterialTheme.colorScheme.primaryContainer),
        modifier = Modifier.fillMaxWidth(),
    ) {
        Row(Modifier.fillMaxWidth().padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
            Text(message, Modifier.weight(1f), color = if (error) MaterialTheme.colorScheme.onErrorContainer else MaterialTheme.colorScheme.onPrimaryContainer)
            retry?.let { TextButton(onClick = it) { Text("Retry") } }
        }
    }
}

@Composable
private fun StaffDirectorySummary(state: StaffDirectoryUiState) {
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
        DirectoryMetric("Staff", state.totalCount.toString(), Modifier.weight(1f))
        DirectoryMetric("Active", state.activeCount.toString(), Modifier.weight(1f))
        DirectoryMetric("Inactive", state.inactiveCount.toString(), Modifier.weight(1f))
    }
}

@Composable
private fun DirectoryMetric(label: String, value: String, modifier: Modifier = Modifier) {
    Card(modifier, colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)) {
        Column(Modifier.padding(horizontal = 12.dp, vertical = 10.dp)) {
            Text(value, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
            Text(label, style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
    }
}

@Composable
private fun StaffDirectoryRow(
    member: StaffDirectoryMemberDto,
    isSaving: Boolean,
    mutationLocked: Boolean,
    onToggleActive: (Boolean) -> Unit,
) {
    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)) {
        Row(Modifier.fillMaxWidth().padding(14.dp), verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text(member.name, fontWeight = FontWeight.SemiBold); Spacer(Modifier.height(3.dp))
                Text(member.role, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                member.staffId?.takeIf(String::isNotBlank)?.let { Text("Staff ID: $it", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant) }
                if (isSaving) Text("Updating account…", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.primary)
            }
            Column(horizontalAlignment = Alignment.End) {
                Text(if (member.active) "Active" else "Inactive", style = MaterialTheme.typography.labelMedium, color = if (member.active) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurfaceVariant)
                Switch(checked = member.active, onCheckedChange = onToggleActive, enabled = !mutationLocked)
            }
        }
    }
}
