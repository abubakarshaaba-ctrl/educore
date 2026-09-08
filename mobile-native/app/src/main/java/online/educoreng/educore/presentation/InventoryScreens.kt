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
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import online.educoreng.educore.core.designsystem.component.EduCoreDangerButton
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreMetricCard
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSegmentedControl
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTextField
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.InventoryAssetDto
import online.educoreng.educore.core.network.dto.InventoryStaffDto

@Composable
internal fun NativeInventoryScreen(
    state: InventoryUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onStatusFilter: (String) -> Unit,
    onCreate: () -> Unit,
    onEdit: (InventoryAssetDto) -> Unit,
    onCloseEditor: () -> Unit,
    onField: (InventoryField, String) -> Unit,
    onAssignedTo: (Long?) -> Unit,
    onCondition: (String) -> Unit,
    onStatus: (String) -> Unit,
    onSave: () -> Unit,
    onDelete: (Long) -> Unit,
    onLoadMore: () -> Unit,
    onRetry: () -> Unit,
) {
    if (state.editorOpen) {
        InventoryEditorScreen(
            state = state,
            onBack = onCloseEditor,
            onField = onField,
            onAssignedTo = onAssignedTo,
            onCondition = onCondition,
            onStatus = onStatus,
            onSave = onSave,
            onDelete = onDelete,
        )
        return
    }

    if (state.isLoading && state.workspace == null) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Loading inventory")
        return
    }

    val workspace = state.workspace
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader("Inventory", "School assets, condition, location and assignment", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let { item { Text(it, color = EduCoreColors.Success700) } }

        workspace?.let { data ->
            item {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreMetricCard("Assets", data.metrics.total.toString(), Modifier.weight(1f), tone = EduCoreTone.Brand)
                    EduCoreMetricCard("In use", data.metrics.inUse.toString(), Modifier.weight(1f), tone = EduCoreTone.Success)
                }
            }
            item {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreMetricCard("Under repair", data.metrics.underRepair.toString(), Modifier.weight(1f), tone = EduCoreTone.Warning)
                    EduCoreMetricCard("Damaged", data.metrics.damaged.toString(), Modifier.weight(1f), tone = EduCoreTone.Danger)
                }
            }
        }

        if (state.canManage) {
            item { EduCorePrimaryButton("Add asset", onCreate, Modifier.fillMaxWidth()) }
        }

        item { EduCoreSearchBar(state.query, onQuery, placeholder = "Search asset, serial, category or location") }
        item { EduCoreSecondaryButton("Search inventory", onSearch, Modifier.fillMaxWidth(), enabled = !state.isLoading) }

        workspace?.let { data ->
            val filterKeys = listOf("all") + data.statusOptions.map { it.key }
            val labels = listOf("All") + data.statusOptions.map { it.label }
            item {
                EduCoreSegmentedControl(
                    options = labels,
                    selectedIndex = filterKeys.indexOf(state.statusFilter).coerceAtLeast(0),
                    onSelected = { onStatusFilter(filterKeys[it]) },
                    enabled = !state.isLoading,
                )
            }
        }

        if (state.assets.isEmpty() && !state.isLoading) {
            item { EduCoreEmptyState("No inventory assets", if (state.query.isBlank()) "Registered school assets will appear here." else "Try another search term or status.") }
        } else {
            items(state.assets, key = InventoryAssetDto::id) { asset ->
                InventoryAssetCard(asset, state.canManage, onEdit)
            }
        }

        if (state.hasMore) {
            item { EduCoreSecondaryButton(if (state.isLoadingMore) "Loading…" else "Load more", onLoadMore, Modifier.fillMaxWidth(), enabled = !state.isLoadingMore) }
        }
        if (state.errorMessage != null && workspace == null) {
            item { EduCoreSecondaryButton("Retry", onRetry, Modifier.fillMaxWidth()) }
        }
    }
}

@Composable
private fun InventoryAssetCard(asset: InventoryAssetDto, canManage: Boolean, onEdit: (InventoryAssetDto) -> Unit) {
    Card(
        onClick = { if (canManage) onEdit(asset) },
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
                Column(Modifier.weight(1f)) {
                    Text(asset.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    Text(asset.category ?: "Uncategorised", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                EduCoreStatusBadge(asset.status.replace('_', ' ').replaceFirstChar(Char::uppercase), asset.status.inventoryTone())
            }
            Text("Condition: ${asset.condition.replaceFirstChar(Char::uppercase)}", style = MaterialTheme.typography.bodyMedium)
            Text(
                listOfNotNull(asset.location, asset.assignedToName, asset.serialNumber).joinToString(" · ").ifBlank { "No location, assignee or serial recorded" },
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }
    }
}

@Composable
private fun InventoryEditorScreen(
    state: InventoryUiState,
    onBack: () -> Unit,
    onField: (InventoryField, String) -> Unit,
    onAssignedTo: (Long?) -> Unit,
    onCondition: (String) -> Unit,
    onStatus: (String) -> Unit,
    onSave: () -> Unit,
    onDelete: (Long) -> Unit,
) {
    val workspace = state.workspace ?: return
    var staffQuery by rememberSaveable { mutableStateOf("") }
    var confirmDelete by rememberSaveable { mutableStateOf(false) }
    val filteredStaff = remember(workspace.staff, staffQuery) {
        workspace.staff.filter { member ->
            staffQuery.isBlank() || member.name.contains(staffQuery, true) || member.staffId.orEmpty().contains(staffQuery, true)
        }.take(50)
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader(if (state.editing) "Edit Asset" else "Add Asset", "Inventory identification, assignment and lifecycle", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }

        item { InventoryInput("Asset name", state.draft.name, InventoryField.NAME, onField) }
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                InventoryInput("Category", state.draft.category, InventoryField.CATEGORY, onField, Modifier.weight(1f))
                InventoryInput("Serial number", state.draft.serialNumber, InventoryField.SERIAL_NUMBER, onField, Modifier.weight(1f))
            }
        }
        item { InventoryInput("Location", state.draft.location, InventoryField.LOCATION, onField) }

        item { Text("Condition", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold) }
        item {
            EduCoreSegmentedControl(
                options = workspace.conditionOptions.map { it.label },
                selectedIndex = workspace.conditionOptions.indexOfFirst { it.key == state.draft.condition }.coerceAtLeast(0),
                onSelected = { onCondition(workspace.conditionOptions[it].key) },
                enabled = !state.isSaving,
            )
        }
        item { Text("Status", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold) }
        item {
            EduCoreSegmentedControl(
                options = workspace.statusOptions.map { it.label },
                selectedIndex = workspace.statusOptions.indexOfFirst { it.key == state.draft.status }.coerceAtLeast(0),
                onSelected = { onStatus(workspace.statusOptions[it].key) },
                enabled = !state.isSaving,
            )
        }

        item { Text("Assigned staff", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold) }
        item { EduCoreSecondaryButton("Leave unassigned", { onAssignedTo(null) }, Modifier.fillMaxWidth(), enabled = !state.isSaving) }
        item { EduCoreSearchBar(staffQuery, { staffQuery = it }, placeholder = "Search active staff") }
        items(filteredStaff, key = InventoryStaffDto::id) { member ->
            InventoryStaffCard(member, state.draft.assignedTo == member.id, { onAssignedTo(member.id) })
        }

        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                InventoryInput("Purchase date", state.draft.purchaseDate, InventoryField.PURCHASE_DATE, onField, Modifier.weight(1f), "YYYY-MM-DD")
                InventoryInput("Purchase cost", state.draft.purchaseCost, InventoryField.PURCHASE_COST, onField, Modifier.weight(1f))
            }
        }
        item { InventoryInput("Notes", state.draft.notes, InventoryField.NOTES, onField, multiline = true) }

        item {
            EduCorePrimaryButton(
                text = if (state.editing) "Save asset changes" else "Add asset",
                onClick = onSave,
                modifier = Modifier.fillMaxWidth(),
                enabled = state.draft.valid && !state.isSaving,
                loading = state.isSaving,
            )
        }

        state.draft.id?.let { assetId ->
            if (!confirmDelete) {
                item {
                    EduCoreDangerButton(
                        text = "Delete asset",
                        onClick = { confirmDelete = true },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !state.isSaving,
                    )
                }
            } else {
                item { Text("Delete this asset permanently?", color = MaterialTheme.colorScheme.error, fontWeight = FontWeight.SemiBold) }
                item {
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                        EduCoreSecondaryButton("Cancel", { confirmDelete = false }, Modifier.weight(1f), enabled = !state.isSaving)
                        EduCoreDangerButton("Confirm delete", { onDelete(assetId) }, Modifier.weight(1f), enabled = !state.isSaving)
                    }
                }
            }
        }
    }
}

@Composable
private fun InventoryStaffCard(member: InventoryStaffDto, selected: Boolean, onClick: () -> Unit) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = if (selected) EduCoreColors.Info100 else EduCoreColors.White),
        border = BorderStroke(1.dp, if (selected) EduCoreColors.Info700 else EduCoreColors.Line200),
    ) {
        Row(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text(member.name, fontWeight = FontWeight.Medium)
                member.staffId?.let { Text(it, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant) }
            }
            if (selected) EduCoreStatusBadge("Assigned", EduCoreTone.Info)
        }
    }
}

@Composable
private fun InventoryInput(
    label: String,
    value: String,
    field: InventoryField,
    onField: (InventoryField, String) -> Unit,
    modifier: Modifier = Modifier.fillMaxWidth(),
    supportingText: String? = null,
    multiline: Boolean = false,
) {
    EduCoreTextField(
        value = value,
        onValueChange = { onField(field, it) },
        label = label,
        modifier = modifier,
        supportingText = supportingText,
        singleLine = !multiline,
    )
}

private fun String.inventoryTone(): EduCoreTone = when (lowercase()) {
    "in_use" -> EduCoreTone.Success
    "in_storage" -> EduCoreTone.Info
    "under_repair" -> EduCoreTone.Warning
    "disposed" -> EduCoreTone.Neutral
    else -> EduCoreTone.Info
}
