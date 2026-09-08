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
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreMetricCard
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTabs
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.OperationsRecord
import online.educoreng.educore.core.model.OperationsWorkspace

@Composable
internal fun TransportScreen(
    state: OperationsUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSection: (Int) -> Unit,
) {
    val workspace = state.workspace ?: return
    val section = workspace.sections.getOrNull(state.selectedSection)
    val records = remember(section?.records, state.query) { section?.records.orEmpty().filterOperations(state.query) }

    DomainOperationsList(
        title = "Transport",
        subtitle = "Routes, vehicles, rider load and transport capacity",
        state = state,
        workspace = workspace,
        records = records,
        onBack = onBack,
        onQuery = onQuery,
        onSection = onSection,
        placeholder = if (section?.key == "vehicles") "Search plate number or vehicle model" else "Search route, vehicle or driver",
        emptyMessage = if (section?.key == "vehicles") "School vehicles will appear here." else "Configured transport routes will appear here.",
    ) { record ->
        if (section?.key == "vehicles") TransportVehicleCard(record) else TransportRouteCard(record)
    }
}

@Composable
internal fun HealthRecordsScreen(
    state: OperationsUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
) {
    val workspace = state.workspace ?: return
    val section = workspace.sections.firstOrNull()
    val records = remember(section?.records, state.query) { section?.records.orEmpty().filterOperations(state.query) }

    DomainOperationsList(
        title = "Health Records",
        subtitle = "Student health coverage and medication/allergy alerts",
        state = state,
        workspace = workspace,
        records = records,
        onBack = onBack,
        onQuery = onQuery,
        onSection = {},
        placeholder = "Search student, admission number or alert",
        emptyMessage = "Student health records will appear here.",
        showTabs = false,
    ) { record -> HealthRecordCard(record) }
}

@Composable
internal fun InventoryScreen(
    state: OperationsUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
) {
    val workspace = state.workspace ?: return
    val section = workspace.sections.firstOrNull()
    val records = remember(section?.records, state.query) { section?.records.orEmpty().filterOperations(state.query) }

    DomainOperationsList(
        title = "Inventory",
        subtitle = "School assets, assignment, condition and location",
        state = state,
        workspace = workspace,
        records = records,
        onBack = onBack,
        onQuery = onQuery,
        onSection = {},
        placeholder = "Search asset, category, location or serial number",
        emptyMessage = "Registered school assets will appear here.",
        showTabs = false,
    ) { record -> InventoryAssetCard(record) }
}

@Composable
internal fun HostelsScreen(
    state: OperationsUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSection: (Int) -> Unit,
) {
    val workspace = state.workspace ?: return
    val section = workspace.sections.getOrNull(state.selectedSection)
    val records = remember(section?.records, state.query) { section?.records.orEmpty().filterOperations(state.query) }

    DomainOperationsList(
        title = "Hostels",
        subtitle = "Boarding capacity, wardens and active student allocations",
        state = state,
        workspace = workspace,
        records = records,
        onBack = onBack,
        onQuery = onQuery,
        onSection = onSection,
        placeholder = if (section?.key == "allocations") "Search student, hostel or room" else "Search hostel or warden",
        emptyMessage = if (section?.key == "allocations") "Active boarding allocations will appear here." else "Configured hostels will appear here.",
    ) { record ->
        if (section?.key == "allocations") HostelAllocationCard(record) else HostelCard(record)
    }
}

@Composable
private fun DomainOperationsList(
    title: String,
    subtitle: String,
    state: OperationsUiState,
    workspace: OperationsWorkspace,
    records: List<OperationsRecord>,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSection: (Int) -> Unit,
    placeholder: String,
    emptyMessage: String,
    showTabs: Boolean = true,
    card: @Composable (OperationsRecord) -> Unit,
) {
    val metrics = workspace.metrics
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader(title = title, subtitle = subtitle, onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }

        if (metrics.isNotEmpty()) {
            metrics.chunked(2).forEachIndexed { index, rowMetrics ->
                item(key = "domain-metrics-$index") {
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                        rowMetrics.forEach { metric ->
                            EduCoreMetricCard(
                                label = metric.label,
                                value = metric.value,
                                modifier = Modifier.weight(1f),
                                tone = metric.tone.domainTone(),
                            )
                        }
                        if (rowMetrics.size == 1) androidx.compose.foundation.layout.Spacer(Modifier.weight(1f))
                    }
                }
            }
        }

        if (showTabs && workspace.sections.size > 1) {
            item {
                EduCoreTabs(
                    labels = workspace.sections.map { "${it.title} (${it.count})" },
                    selectedIndex = state.selectedSection,
                    onSelected = onSection,
                )
            }
        }

        item { EduCoreSearchBar(query = state.query, onQueryChange = onQuery, placeholder = placeholder) }

        if (records.isEmpty()) {
            item {
                EduCoreEmptyState(
                    title = if (state.query.isBlank()) "No records yet" else "No matching records",
                    message = if (state.query.isBlank()) emptyMessage else "Try another search term.",
                )
            }
        } else {
            items(records, key = { "${workspace.module.key}-${it.id}" }) { card(it) }
        }

        item {
            Text(
                text = if (workspace.module.canManage) {
                    "Your account has management access. Mutations remain server-controlled until the corresponding native write contract is tenant-boundary reviewed."
                } else {
                    "This account has read-only access to this workspace."
                },
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }
    }
}

@Composable
private fun TransportRouteCard(record: OperationsRecord) {
    DomainCard(record) {
        DomainFieldGrid(
            listOf(
                "Riders" to record.domainField("Riders"),
                "Capacity" to record.domainField("Capacity"),
                "Driver" to record.domainField("Driver"),
                "Fare" to record.domainField("Fare"),
            )
        )
    }
}

@Composable
private fun TransportVehicleCard(record: OperationsRecord) {
    DomainCard(record) {
        DomainFieldGrid(listOf("Capacity" to record.domainField("Capacity"), "Year" to record.domainField("Year")))
    }
}

@Composable
private fun HealthRecordCard(record: OperationsRecord) {
    DomainCard(record) {
        DomainFieldGrid(
            listOf(
                "Blood group" to record.domainField("Blood group"),
                "Genotype" to record.domainField("Genotype"),
                "Allergies" to record.domainField("Allergies"),
                "Medication" to record.domainField("Medication"),
            )
        )
    }
}

@Composable
private fun InventoryAssetCard(record: OperationsRecord) {
    DomainCard(record) {
        DomainFieldGrid(
            listOf(
                "Condition" to record.domainField("Condition"),
                "Location" to record.domainField("Location"),
                "Assigned to" to record.domainField("Assigned to"),
                "Serial" to record.domainField("Serial"),
            )
        )
    }
}

@Composable
private fun HostelCard(record: OperationsRecord) {
    DomainCard(record) {
        DomainFieldGrid(
            listOf(
                "Occupied" to record.domainField("Occupied"),
                "Rooms" to record.domainField("Rooms"),
                "Warden" to record.domainField("Warden"),
            )
        )
    }
}

@Composable
private fun HostelAllocationCard(record: OperationsRecord) {
    DomainCard(record) {
        DomainFieldGrid(
            listOf(
                "Hostel" to record.domainField("Hostel"),
                "Room" to record.domainField("Room"),
                "Allocated" to record.domainField("Allocated"),
            )
        )
    }
}

@Composable
private fun DomainCard(record: OperationsRecord, content: @Composable () -> Unit) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                verticalAlignment = Alignment.Top,
            ) {
                Column(Modifier.weight(1f)) {
                    Text(record.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    record.subtitle?.let {
                        Text(it, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    }
                }
                record.status?.let {
                    EduCoreStatusBadge(
                        text = it.replace('_', ' ').replaceFirstChar(Char::uppercase),
                        tone = it.domainStatusTone(),
                    )
                }
            }
            content()
        }
    }
}

@Composable
private fun DomainFieldGrid(fields: List<Pair<String, String?>>) {
    fields.filter { !it.second.isNullOrBlank() }.chunked(2).forEach { rowFields ->
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Lg)) {
            rowFields.forEach { (label, value) ->
                Column(Modifier.weight(1f)) {
                    Text(label.uppercase(), style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Text(value.orEmpty(), style = MaterialTheme.typography.bodyMedium, maxLines = 2, overflow = TextOverflow.Ellipsis)
                }
            }
            if (rowFields.size == 1) androidx.compose.foundation.layout.Spacer(Modifier.weight(1f))
        }
    }
}

private fun OperationsRecord.domainField(label: String): String? =
    fields.firstOrNull { it.label.equals(label, ignoreCase = true) }?.value

private fun String.domainTone(): EduCoreTone = when (lowercase()) {
    "success" -> EduCoreTone.Success
    "warning" -> EduCoreTone.Warning
    "danger" -> EduCoreTone.Danger
    "blue", "info" -> EduCoreTone.Info
    "purple" -> EduCoreTone.Purple
    else -> EduCoreTone.Brand
}

private fun String.domainStatusTone(): EduCoreTone = when (lowercase()) {
    "active", "available", "recorded" -> EduCoreTone.Success
    "attention", "overdue", "repair" -> EduCoreTone.Warning
    "damaged", "lost", "full" -> EduCoreTone.Danger
    "inactive", "closed" -> EduCoreTone.Neutral
    else -> EduCoreTone.Info
}
