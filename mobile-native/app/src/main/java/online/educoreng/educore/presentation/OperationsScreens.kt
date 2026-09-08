package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AccountBalance
import androidx.compose.material.icons.filled.Inventory2
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreMetricCard
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTabs
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.EduCoreWindowWidth
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.OperationsRecord

@Composable
internal fun OperationsScreen(
    state: OperationsUiState,
    width: EduCoreWindowWidth,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSection: (Int) -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.workspace == null) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading operations workspace")
    }
    val workspace = state.workspace ?: return EduCoreErrorState(
        message = state.errorMessage ?: "This workspace is unavailable.",
        modifier = Modifier.fillMaxSize(),
        onRetry = onRetry,
    )

    if (workspace.module.key.equals("fees", ignoreCase = true)) {
        FeesScreen(state = state, onBack = onBack, onQuery = onQuery, onSection = onSection)
        return
    }

    if (workspace.module.key.equals("payroll", ignoreCase = true)) {
        PayrollScreen(state = state, onBack = onBack, onQuery = onQuery)
        return
    }

    if (workspace.module.key.equals("expenses", ignoreCase = true)) {
        ExpensesScreen(state = state, onBack = onBack, onQuery = onQuery)
        return
    }

    if (workspace.module.key.equals("library", ignoreCase = true)) {
        val libraryManagementViewModel: LibraryManagementViewModel = hiltViewModel()
        val libraryManagementState by libraryManagementViewModel.uiState.collectAsStateWithLifecycle()
        LaunchedEffect(libraryManagementState.refreshVersion) {
            if (libraryManagementState.refreshVersion > 0) {
                onRetry()
                libraryManagementViewModel.consumeRefresh()
            }
        }
        LibraryScreen(
            state = state,
            management = libraryManagementState,
            onBack = onBack,
            onQuery = onQuery,
            onSection = onSection,
            onOpenIssue = libraryManagementViewModel::openIssue,
            onCloseIssue = libraryManagementViewModel::closeIssue,
            onBook = libraryManagementViewModel::selectBook,
            onBorrowerType = libraryManagementViewModel::selectBorrowerType,
            onBorrower = libraryManagementViewModel::selectBorrower,
            onDueDate = libraryManagementViewModel::setDueDate,
            onNotes = libraryManagementViewModel::setNotes,
            onIssue = libraryManagementViewModel::issue,
            onReturn = libraryManagementViewModel::returnLoan,
        )
        return
    }

    if (workspace.module.key.equals("transport", ignoreCase = true)) {
        val transportViewModel: TransportViewModel = hiltViewModel()
        val transportState by transportViewModel.uiState.collectAsStateWithLifecycle()
        LaunchedEffect(workspace.module.key) {
            if (transportState.dashboard == null && !transportState.isLoading) {
                transportViewModel.load()
            }
        }
        NativeTransportScreen(
            state = transportState,
            onBack = onBack,
            onQuery = transportViewModel::setQuery,
            onSearch = transportViewModel::search,
            onOpenManifest = transportViewModel::openManifest,
            onCloseManifest = transportViewModel::closeManifest,
            onOpenAssignment = transportViewModel::openAssignment,
            onCloseAssignment = transportViewModel::closeAssignment,
            onRoute = transportViewModel::selectRoute,
            onStudent = transportViewModel::selectStudent,
            onPickupStop = transportViewModel::setPickupStop,
            onDirection = transportViewModel::setDirection,
            onAssign = transportViewModel::assign,
            onUnassign = transportViewModel::unassign,
            onLoadMore = transportViewModel::loadMore,
            onRetry = transportViewModel::load,
        )
        return
    }

    if (workspace.module.key.equals("health", ignoreCase = true)) {
        val healthViewModel: HealthViewModel = hiltViewModel()
        val healthState by healthViewModel.uiState.collectAsStateWithLifecycle()
        LaunchedEffect(workspace.module.key) {
            if (healthState.dashboard == null && !healthState.isLoading) {
                healthViewModel.load()
            }
        }
        NativeHealthScreen(
            state = healthState,
            onBack = onBack,
            onQuery = healthViewModel::setQuery,
            onSearch = healthViewModel::search,
            onOpen = healthViewModel::open,
            onCloseDetail = healthViewModel::closeDetail,
            onField = healthViewModel::updateField,
            onSave = healthViewModel::save,
            onLoadMore = healthViewModel::loadMore,
            onRetry = healthViewModel::load,
        )
        return
    }

    if (workspace.module.key.equals("inventory", ignoreCase = true)) {
        val inventoryViewModel: InventoryViewModel = hiltViewModel()
        val inventoryState by inventoryViewModel.uiState.collectAsStateWithLifecycle()
        LaunchedEffect(workspace.module.key) {
            if (inventoryState.workspace == null && !inventoryState.isLoading) {
                inventoryViewModel.load()
            }
        }
        NativeInventoryScreen(
            state = inventoryState,
            onBack = onBack,
            onQuery = inventoryViewModel::setQuery,
            onSearch = inventoryViewModel::search,
            onStatusFilter = inventoryViewModel::setStatusFilter,
            onCreate = inventoryViewModel::create,
            onEdit = inventoryViewModel::edit,
            onCloseEditor = inventoryViewModel::closeEditor,
            onField = inventoryViewModel::updateField,
            onAssignedTo = inventoryViewModel::setAssignedTo,
            onCondition = inventoryViewModel::setCondition,
            onStatus = inventoryViewModel::setStatus,
            onSave = inventoryViewModel::save,
            onDelete = inventoryViewModel::delete,
            onLoadMore = inventoryViewModel::loadMore,
            onRetry = inventoryViewModel::load,
        )
        return
    }

    if (workspace.module.key.equals("hostels", ignoreCase = true)) {
        HostelsScreen(state = state, onBack = onBack, onQuery = onQuery, onSection = onSection)
        return
    }

    if (workspace.module.key.equals("subjects", ignoreCase = true)) {
        SubjectsScreen(state = state, onBack = onBack, onQuery = onQuery)
        return
    }

    if (workspace.module.key.equals("curriculum", ignoreCase = true)) {
        CurriculumScreen(state = state, onBack = onBack, onQuery = onQuery, onSection = onSection)
        return
    }

    if (workspace.module.key.equals("academic-cycle", ignoreCase = true)) {
        AcademicCycleScreen(state = state, onBack = onBack, onQuery = onQuery, onSection = onSection)
        return
    }

    if (workspace.module.key.equals("analytics", ignoreCase = true)) {
        AnalyticsScreen(state = state, onBack = onBack, onQuery = onQuery, onSection = onSection)
        return
    }

    if (workspace.module.key.equals("admissions", ignoreCase = true)) {
        val admissionsViewModel: AdmissionsViewModel = hiltViewModel()
        val admissionsState by admissionsViewModel.uiState.collectAsStateWithLifecycle()
        LaunchedEffect(workspace.module.key) {
            if (admissionsState.workspace == null && !admissionsState.isLoading) {
                admissionsViewModel.load()
            }
        }
        AdmissionsScreen(
            state = admissionsState,
            onBack = onBack,
            onQuery = admissionsViewModel::setSearch,
            onSearch = admissionsViewModel::search,
            onStatusFilter = admissionsViewModel::selectStatus,
            onOpen = admissionsViewModel::open,
            onCloseDetail = admissionsViewModel::closeDetail,
            onLoadMore = admissionsViewModel::loadMore,
            onStartCreate = admissionsViewModel::startCreate,
            onCloseCreate = admissionsViewModel::closeCreate,
            onCreateField = admissionsViewModel::updateCreate,
            onCreateGender = admissionsViewModel::selectCreateGender,
            onCreateClassLevel = admissionsViewModel::selectCreateClassLevel,
            onCreate = admissionsViewModel::create,
            onStatusDraft = admissionsViewModel::setStatusDraft,
            onClassArmDraft = admissionsViewModel::setClassArmDraft,
            onReviewNotes = admissionsViewModel::setReviewNotes,
            onSaveStatus = admissionsViewModel::saveStatus,
            onRetry = admissionsViewModel::load,
        )
        return
    }

    val section = workspace.sections.getOrNull(state.selectedSection)
    val records = remember(section?.records, state.query) { section?.records.orEmpty().filterOperations(state.query) }
    val metricColumns = when (width) {
        EduCoreWindowWidth.Compact -> 2
        EduCoreWindowWidth.Medium -> 3
        EduCoreWindowWidth.Expanded -> 4
    }
    val metricRows = remember(workspace.metrics, metricColumns) { workspace.metrics.chunked(metricColumns) }

    LazyColumn(
        modifier = Modifier.fillMaxSize().imePadding(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { OperationsHeader(workspace.module.title, workspace.module.description, onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        metricRows.forEachIndexed { rowIndex, metrics ->
            item(key = "metrics-$rowIndex") {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    metrics.forEach { metric ->
                        EduCoreMetricCard(
                            label = metric.label,
                            value = metric.value,
                            modifier = Modifier.weight(1f),
                            icon = if (metric.format == "currency") Icons.Default.AccountBalance else Icons.Default.Inventory2,
                            tone = metric.tone.toEduCoreTone(),
                        )
                    }
                    repeat(metricColumns - metrics.size) { Spacer(Modifier.weight(1f)) }
                }
            }
        }
        if (workspace.sections.size > 1) {
            item {
                EduCoreTabs(
                    labels = workspace.sections.map { "${it.title} (${it.count})" },
                    selectedIndex = state.selectedSection,
                    onSelected = onSection,
                )
            }
        }
        item { EduCoreSearchBar(state.query, onQuery, placeholder = "Search ${section?.title?.lowercase() ?: "records"}") }
        if (records.isEmpty()) {
            item {
                EduCoreEmptyState(
                    title = if (state.query.isBlank()) "No records yet" else "No matching records",
                    message = if (state.query.isBlank()) "Nothing has been recorded in this section." else "Try another search term.",
                )
            }
        }
        items(records, key = { "${section?.key}-${it.id}" }) { record -> OperationsRecordCard(record, width) }
    }
}

@Composable
private fun OperationsHeader(title: String, subtitle: String, onBack: () -> Unit) {
    EduCorePageHeader(title = title, subtitle = subtitle, onBack = onBack)
}

@Composable
private fun OperationsRecordCard(record: OperationsRecord, width: EduCoreWindowWidth) {
    Card(
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.SurfaceBlue50),
        border = BorderStroke(1.dp, EduCoreColors.Info200),
    ) {
        Column(
            Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
                Column(Modifier.weight(1f)) {
                    Text(record.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold, maxLines = 2, overflow = TextOverflow.Ellipsis)
                    record.subtitle?.let { Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600) }
                }
                record.status?.let { status ->
                    Spacer(Modifier.width(EduCoreSpacing.Sm))
                    EduCoreStatusBadge(status.replace('_', ' ').replaceFirstChar(Char::uppercase), status.toTone())
                }
            }
            if (record.fields.isNotEmpty()) {
                val fieldColumns = if (width == EduCoreWindowWidth.Compact) 1 else 2
                record.fields.chunked(fieldColumns).forEach { fields ->
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Lg)) {
                        fields.forEach { field ->
                            Column(Modifier.weight(1f)) {
                                Text(field.label.uppercase(), style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Muted500)
                                Text(field.value, style = MaterialTheme.typography.bodyMedium, color = EduCoreColors.Ink900)
                            }
                        }
                        repeat(fieldColumns - fields.size) { Spacer(Modifier.weight(1f)) }
                    }
                }
            }
        }
    }
}

private fun String.toEduCoreTone(): EduCoreTone = when (lowercase()) {
    "success" -> EduCoreTone.Success
    "warning" -> EduCoreTone.Warning
    "danger" -> EduCoreTone.Danger
    "blue", "info" -> EduCoreTone.Info
    "purple" -> EduCoreTone.Purple
    else -> EduCoreTone.Brand
}

private fun String.toTone(): EduCoreTone = when (lowercase()) {
    "active", "available", "admitted", "current", "paid", "success", "recorded" -> EduCoreTone.Success
    "pending", "attention", "partially paid", "partially_paid", "overdue", "issued" -> EduCoreTone.Warning
    "inactive", "closed" -> EduCoreTone.Neutral
    "rejected", "damaged", "lost", "full", "failed" -> EduCoreTone.Danger
    else -> EduCoreTone.Info
}

internal fun List<OperationsRecord>.filterOperations(query: String): List<OperationsRecord> = filter { record ->
    query.isBlank() || record.title.contains(query, true) ||
        record.subtitle.orEmpty().contains(query, true) ||
        record.status.orEmpty().contains(query, true) ||
        record.fields.any { it.label.contains(query, true) || it.value.contains(query, true) }
}
