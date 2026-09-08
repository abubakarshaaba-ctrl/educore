package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
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
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
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
internal fun SubjectsScreen(
    state: OperationsUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
) {
    val viewModel: SubjectsViewModel = hiltViewModel()
    val subjectsState by viewModel.uiState.collectAsStateWithLifecycle()

    LaunchedEffect(state.workspace?.module?.key) {
        if (subjectsState.workspace == null && !subjectsState.isLoading) {
            viewModel.load()
        }
    }

    NativeSubjectsScreen(
        state = subjectsState,
        onBack = onBack,
        onQuery = viewModel::setQuery,
        onSearch = viewModel::search,
        onStatus = viewModel::setStatus,
        onCreate = viewModel::create,
        onEdit = viewModel::edit,
        onCloseEditor = viewModel::closeEditor,
        onName = viewModel::setName,
        onCode = viewModel::setCode,
        onActive = viewModel::setActive,
        onSave = viewModel::save,
        onRequestDelete = viewModel::requestDelete,
        onCancelDelete = viewModel::cancelDelete,
        onConfirmDelete = viewModel::confirmDelete,
        onLoadMore = viewModel::loadMore,
        onRetry = viewModel::load,
    )
}

@Composable
internal fun CurriculumScreen(
    state: OperationsUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSection: (Int) -> Unit,
) {
    val viewModel: CurriculumViewModel = hiltViewModel()
    val curriculumState by viewModel.uiState.collectAsStateWithLifecycle()

    LaunchedEffect(state.workspace?.module?.key) {
        if (curriculumState.workspace == null && !curriculumState.isLoading) {
            viewModel.load()
        }
    }

    NativeCurriculumScreen(
        state = curriculumState,
        onBack = onBack,
        onTab = viewModel::setTab,
        onQuery = viewModel::setQuery,
        onSearch = viewModel::search,
        onLevelFilter = viewModel::setLevelFilter,
        onTrackFilter = viewModel::setTrackFilter,
        onStatusFilter = viewModel::setStatusFilter,
        onCreateTrack = viewModel::createTrack,
        onEditTrack = viewModel::editTrack,
        onTrackName = viewModel::setTrackName,
        onTrackSection = viewModel::setTrackSection,
        onTrackActive = viewModel::setTrackActive,
        onSaveTrack = viewModel::saveTrack,
        onRequestDeleteTrack = viewModel::requestDeleteTrack,
        onCancelDeleteTrack = viewModel::cancelDeleteTrack,
        onConfirmDeleteTrack = viewModel::confirmDeleteTrack,
        onCreateRule = viewModel::createRule,
        onEditRule = viewModel::editRule,
        onRuleLevel = viewModel::setRuleLevel,
        onRuleTrack = viewModel::setRuleTrack,
        onRuleSubject = viewModel::setRuleSubject,
        onRuleStatus = viewModel::setRuleStatus,
        onRuleGroup = viewModel::setRuleGroup,
        onRuleMin = viewModel::setRuleMin,
        onRuleMax = viewModel::setRuleMax,
        onRuleActive = viewModel::setRuleActive,
        onSaveRule = viewModel::saveRule,
        onRequestDeleteRule = viewModel::requestDeleteRule,
        onCancelDeleteRule = viewModel::cancelDeleteRule,
        onConfirmDeleteRule = viewModel::confirmDeleteRule,
        onCloseEditor = viewModel::closeEditor,
        onRetry = viewModel::load,
    )
}

@Composable
internal fun AcademicCycleScreen(
    state: OperationsUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSection: (Int) -> Unit,
) {
    val viewModel: AcademicCycleViewModel = hiltViewModel()
    val cycleState by viewModel.uiState.collectAsStateWithLifecycle()

    LaunchedEffect(state.workspace?.module?.key) {
        if (cycleState.workspace == null && !cycleState.isLoading) {
            viewModel.load()
        }
    }

    NativeAcademicCycleScreen(
        state = cycleState,
        onBack = onBack,
        onTab = viewModel::setTab,
        onCreateSession = viewModel::createSession,
        onEditSession = viewModel::editSession,
        onSessionName = viewModel::setSessionName,
        onSessionActivate = viewModel::setSessionActivate,
        onSaveSession = viewModel::saveSession,
        onCreateTerm = viewModel::createTerm,
        onEditTerm = viewModel::editTerm,
        onTermSession = viewModel::setTermSession,
        onTermName = viewModel::setTermName,
        onTermStart = viewModel::setTermStart,
        onTermEnd = viewModel::setTermEnd,
        onNextTerm = viewModel::setNextTerm,
        onTermActivate = viewModel::setTermActivate,
        onSaveTerm = viewModel::saveTerm,
        onCloseEditor = viewModel::closeEditor,
        onActivateSession = viewModel::requestActivateSession,
        onCloseSession = viewModel::requestCloseSession,
        onDeleteSession = viewModel::requestDeleteSession,
        onActivateTerm = viewModel::requestActivateTerm,
        onCloseTerm = viewModel::requestCloseTerm,
        onDeleteTerm = viewModel::requestDeleteTerm,
        onCancelAction = viewModel::cancelAction,
        onConfirmAction = viewModel::confirmAction,
        onRetry = viewModel::load,
    )
}

@Composable
internal fun AnalyticsScreen(
    state: OperationsUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSection: (Int) -> Unit,
) {
    val workspace = state.workspace ?: return
    val section = workspace.sections.getOrNull(state.selectedSection)
    val records = remember(section?.records, state.query) { section?.records.orEmpty().filterOperations(state.query) }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Analytics",
                subtitle = "School performance, attendance, enrolment and authorised finance intelligence",
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }

        workspace.metrics.chunked(2).forEachIndexed { index, rowMetrics ->
            item(key = "analytics-metrics-$index") {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    rowMetrics.forEach { metric ->
                        EduCoreMetricCard(
                            label = metric.label,
                            value = metric.value,
                            modifier = Modifier.weight(1f),
                            tone = metric.tone.academicTone(),
                        )
                    }
                    if (rowMetrics.size == 1) Spacer(Modifier.weight(1f))
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

        item {
            EduCoreSearchBar(
                value = state.query,
                onValueChange = onQuery,
                placeholder = when (section?.key) {
                    "classes" -> "Search class performance"
                    "subjects" -> "Search subject performance"
                    "enrollment" -> "Search enrolment month"
                    "finance" -> "Search finance overview"
                    else -> "Search analytics"
                },
            )
        }

        if (records.isEmpty()) {
            item {
                EduCoreEmptyState(
                    title = if (state.query.isBlank()) "No analytics data" else "No matching analytics",
                    message = if (state.query.isBlank()) "Data will appear as school activity is recorded." else "Try another search term.",
                )
            }
        } else {
            items(records, key = { "analytics-${section?.key}-${it.id}" }) { record ->
                AnalyticsRecordCard(record)
            }
        }
    }
}

@Composable
private fun AcademicListShell(
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
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader(title = title, subtitle = subtitle, onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }

        workspace.metrics.chunked(2).forEachIndexed { index, rowMetrics ->
            item(key = "academic-metrics-$index") {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    rowMetrics.forEach { metric ->
                        EduCoreMetricCard(
                            label = metric.label,
                            value = metric.value,
                            modifier = Modifier.weight(1f),
                            tone = metric.tone.academicTone(),
                        )
                    }
                    if (rowMetrics.size == 1) Spacer(Modifier.weight(1f))
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

        item { EduCoreSearchBar(value = state.query, onValueChange = onQuery, placeholder = placeholder) }

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
                    "Your account has management access. Native write actions will be enabled only where the server exposes a tenant-scoped mutation contract."
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
private fun AcademicSessionCard(record: OperationsRecord) {
    AcademicRecordCard(record) { }
}

@Composable
private fun AcademicTermCard(record: OperationsRecord) {
    AcademicRecordCard(record) {
        AcademicFieldGrid(
            listOf(
                "Starts" to record.academicField("Starts"),
                "Ends" to record.academicField("Ends"),
                "Next term" to record.academicField("Next term"),
            )
        )
    }
}

@Composable
private fun AnalyticsRecordCard(record: OperationsRecord) {
    AcademicRecordCard(record) {
        AcademicFieldGrid(record.fields.map { it.label to it.value })
    }
}

@Composable
private fun AcademicRecordCard(record: OperationsRecord, content: @Composable () -> Unit) {
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
                        tone = it.academicStatusTone(),
                    )
                }
            }
            content()
        }
    }
}

@Composable
private fun AcademicFieldGrid(fields: List<Pair<String, String?>>) {
    fields.filter { !it.second.isNullOrBlank() }.chunked(2).forEach { rowFields ->
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Lg)) {
            rowFields.forEach { (label, value) ->
                Column(Modifier.weight(1f)) {
                    Text(label.uppercase(), style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Text(value.orEmpty(), style = MaterialTheme.typography.bodyMedium, maxLines = 2, overflow = TextOverflow.Ellipsis)
                }
            }
            if (rowFields.size == 1) Spacer(Modifier.weight(1f))
        }
    }
}

private fun OperationsRecord.academicField(label: String): String? =
    fields.firstOrNull { it.label.equals(label, ignoreCase = true) }?.value

private fun String.academicTone(): EduCoreTone = when (lowercase()) {
    "success" -> EduCoreTone.Success
    "warning" -> EduCoreTone.Warning
    "danger" -> EduCoreTone.Danger
    "blue", "info" -> EduCoreTone.Info
    "purple" -> EduCoreTone.Purple
    else -> EduCoreTone.Brand
}

private fun String.academicStatusTone(): EduCoreTone = when (lowercase()) {
    "active", "current", "core", "elective" -> EduCoreTone.Success
    "inactive", "closed", "not_offered" -> EduCoreTone.Neutral
    "optional" -> EduCoreTone.Info
    else -> EduCoreTone.Info
}
