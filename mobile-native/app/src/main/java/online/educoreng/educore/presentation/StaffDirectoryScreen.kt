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
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
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
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.StaffDirectoryMemberDto

/**
 * Native, directory-safe staff workspace.
 *
 * The app intentionally renders only the narrow staff projection supplied by
 * the mobile administrator staff endpoint. Payroll, banking, contact and
 * credential data never enter this screen's state.
 */
@Composable
internal fun StaffDirectoryScreen(
    state: StaffDirectoryUiState,
    currentUserId: Long,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onFilter: (StaffDirectoryFilter) -> Unit,
    onRefresh: () -> Unit,
    onLoadMore: () -> Unit,
    onToggleActive: (StaffDirectoryMemberDto, Boolean) -> Unit,
) {
    LazyColumn(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item(key = "staff-header") {
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.Top,
            ) {
                EduCorePageHeader(
                    title = "Staff directory",
                    subtitle = "Authorized account management",
                    onBack = onBack,
                    modifier = Modifier.weight(1f),
                )
                IconButton(onClick = onRefresh, enabled = !state.isLoading && !state.isLoadingMore) {
                    Icon(Icons.Default.Refresh, contentDescription = "Refresh staff directory")
                }
            }
        }

        item(key = "staff-summary") {
            StaffDirectorySummary(state)
        }

        item(key = "staff-search") {
            OutlinedTextField(
                value = state.query,
                onValueChange = onQuery,
                modifier = Modifier.fillMaxWidth(),
                singleLine = true,
                label = { Text("Search staff") },
                placeholder = { Text("Name, staff ID or role") },
                leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) },
            )
        }

        item(key = "staff-filters") {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .horizontalScroll(rememberScrollState()),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
            ) {
                StaffDirectoryFilter.entries.forEach { filter ->
                    FilterChip(
                        selected = state.filter == filter,
                        onClick = { onFilter(filter) },
                        label = {
                            Text(
                                when (filter) {
                                    StaffDirectoryFilter.ALL -> "All"
                                    StaffDirectoryFilter.ACTIVE -> "Active"
                                    StaffDirectoryFilter.INACTIVE -> "Inactive"
                                },
                            )
                        },
                    )
                }
            }
        }

        state.errorMessage?.let { message ->
            item(key = "staff-error") {
                Card(
                    colors = CardDefaults.cardColors(
                        containerColor = MaterialTheme.colorScheme.errorContainer,
                    ),
                    modifier = Modifier.fillMaxWidth(),
                ) {
                    Row(
                        modifier = Modifier.fillMaxWidth().padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Text(
                            text = message,
                            modifier = Modifier.weight(1f),
                            color = MaterialTheme.colorScheme.onErrorContainer,
                        )
                        TextButton(onClick = onRefresh) { Text("Retry") }
                    }
                }
            }
        }

        state.message?.let { message ->
            item(key = "staff-message") {
                Text(
                    text = message,
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.primary,
                )
            }
        }

        item(key = "staff-result-count") {
            Text(
                text = when {
                    state.isLoading && state.members.isEmpty() -> "Loading directory…"
                    state.filteredTotal == state.totalCount -> "${state.totalCount} staff records"
                    else -> "${state.filteredTotal} matching records"
                },
                style = MaterialTheme.typography.labelLarge,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }

        when {
            state.isLoading && state.members.isEmpty() -> {
                item(key = "staff-loading") {
                    Column(
                        modifier = Modifier.fillMaxWidth().padding(vertical = 40.dp),
                        horizontalAlignment = Alignment.CenterHorizontally,
                    ) {
                        CircularProgressIndicator()
                        Spacer(Modifier.height(12.dp))
                        Text("Loading staff directory…")
                    }
                }
            }

            state.visibleMembers.isEmpty() -> {
                item(key = "staff-empty") {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    ) {
                        Column(Modifier.padding(20.dp)) {
                            Text("No staff records found", fontWeight = FontWeight.SemiBold)
                            Spacer(Modifier.height(4.dp))
                            Text(
                                "Adjust the search or status filter, then try again.",
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                        }
                    }
                }
            }

            else -> {
                items(state.visibleMembers, key = StaffDirectoryMemberDto::id) { member ->
                    StaffDirectoryRow(
                        member = member,
                        isCurrentUser = member.id == currentUserId,
                        isSaving = state.savingMemberId == member.id,
                        mutationLocked = state.savingMemberId != null,
                        onToggleActive = { active -> onToggleActive(member, active) },
                    )
                }

                if (state.hasMore || state.isLoadingMore) {
                    item(key = "staff-load-more") {
                        OutlinedButton(
                            onClick = onLoadMore,
                            enabled = state.hasMore && !state.isLoadingMore,
                            modifier = Modifier.fillMaxWidth(),
                        ) {
                            if (state.isLoadingMore) {
                                CircularProgressIndicator(
                                    modifier = Modifier.width(18.dp).height(18.dp),
                                    strokeWidth = 2.dp,
                                )
                                Spacer(Modifier.width(8.dp))
                                Text("Loading more…")
                            } else {
                                Text("Load more (${state.members.size} of ${state.filteredTotal})")
                            }
                        }
                    }
                }
            }
        }

        item(key = "staff-bottom-space") {
            Spacer(Modifier.height(16.dp))
        }
    }
}

@Composable
private fun StaffDirectorySummary(state: StaffDirectoryUiState) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        DirectoryMetric("Staff", state.totalCount.toString(), Modifier.weight(1f))
        DirectoryMetric("Active", state.activeCount.toString(), Modifier.weight(1f))
        DirectoryMetric("Inactive", state.inactiveCount.toString(), Modifier.weight(1f))
    }
}

@Composable
private fun DirectoryMetric(label: String, value: String, modifier: Modifier = Modifier) {
    Card(
        modifier = modifier,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
    ) {
        Column(Modifier.padding(horizontal = 12.dp, vertical = 10.dp)) {
            Text(value, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
            Text(label, style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
    }
}

@Composable
private fun StaffDirectoryRow(
    member: StaffDirectoryMemberDto,
    isCurrentUser: Boolean,
    isSaving: Boolean,
    mutationLocked: Boolean,
    onToggleActive: (Boolean) -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(14.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Column(Modifier.weight(1f)) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text(member.name, fontWeight = FontWeight.SemiBold)
                    if (isCurrentUser) {
                        Spacer(Modifier.width(6.dp))
                        Text(
                            "You",
                            style = MaterialTheme.typography.labelSmall,
                            color = MaterialTheme.colorScheme.primary,
                        )
                    }
                }
                Spacer(Modifier.height(3.dp))
                Text(
                    member.role,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                member.staffId?.takeIf(String::isNotBlank)?.let { staffId ->
                    Text(
                        "Staff ID: $staffId",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                if (isSaving) {
                    Text(
                        "Updating account…",
                        style = MaterialTheme.typography.labelSmall,
                        color = MaterialTheme.colorScheme.primary,
                    )
                }
            }

            Column(horizontalAlignment = Alignment.End) {
                Text(
                    if (member.active) "Active" else "Inactive",
                    style = MaterialTheme.typography.labelMedium,
                    color = if (member.active) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurfaceVariant,
                )
                Switch(
                    checked = member.active,
                    onCheckedChange = onToggleActive,
                    enabled = !isCurrentUser && !mutationLocked,
                )
            }
        }
    }
}
