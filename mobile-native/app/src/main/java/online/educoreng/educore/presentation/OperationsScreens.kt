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
import androidx.compose.material3.DatePicker
import androidx.compose.material3.DatePickerDialog
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExposedDropdownMenuBox
import androidx.compose.material3.ExposedDropdownMenuDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.rememberDatePickerState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import java.util.TimeZone
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreMetricCard
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTabs
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.EduCoreWindowWidth
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.OperationsRecord

@OptIn(ExperimentalMaterial3Api::class)
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
    val section = workspace.sections.getOrNull(state.selectedSection)
    val financeModule = workspace.module.key in setOf("fees", "expenses", "payroll")
    var selectedSession by remember(workspace.module.key, state.selectedSection) { mutableStateOf<String?>(null) }
    var selectedTerm by remember(workspace.module.key, state.selectedSection) { mutableStateOf<String?>(null) }
    var selectedClass by remember(workspace.module.key, state.selectedSection) { mutableStateOf<String?>(null) }
    var selectedDate by remember(workspace.module.key, state.selectedSection) { mutableStateOf<String?>(null) }
    var showDatePicker by remember { mutableStateOf(false) }

    val sourceRecords = section?.records.orEmpty()
    val sessionOptions = remember(sourceRecords) { sourceRecords.fieldValues("Session") }
    val termOptions = remember(sourceRecords) { sourceRecords.fieldValues("Term") }
    val classOptions = remember(sourceRecords) {
        (sourceRecords.fieldValues("Class Level") + sourceRecords.fieldValues("Class")).distinct().sorted()
    }
    val records = remember(sourceRecords, state.query, selectedSession, selectedTerm, selectedClass, selectedDate) {
        sourceRecords
            .filterOperations(state.query)
            .filter { record -> selectedSession == null || record.fieldValue("Session") == selectedSession }
            .filter { record -> selectedTerm == null || record.fieldValue("Term") == selectedTerm }
            .filter { record ->
                selectedClass == null || record.fieldValue("Class Level") == selectedClass || record.fieldValue("Class") == selectedClass
            }
            .filter { record -> selectedDate == null || record.matchesDate(selectedDate!!) }
    }
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
        if (financeModule) {
            item {
                Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    if (sessionOptions.isNotEmpty()) {
                        FinanceDropdown(
                            label = "Session",
                            options = sessionOptions,
                            selected = selectedSession,
                            onSelected = { selectedSession = it },
                        )
                    }
                    if (termOptions.isNotEmpty()) {
                        FinanceDropdown(
                            label = "Term",
                            options = termOptions,
                            selected = selectedTerm,
                            onSelected = { selectedTerm = it },
                        )
                    }
                    if (classOptions.isNotEmpty()) {
                        FinanceDropdown(
                            label = "Class Level",
                            options = classOptions,
                            selected = selectedClass,
                            onSelected = { selectedClass = it },
                        )
                    }
                    EduCoreSecondaryButton(
                        text = selectedDate?.let { "Date: $it" } ?: "Select date",
                        onClick = { showDatePicker = true },
                        modifier = Modifier.fillMaxWidth(),
                    )
                    if (selectedSession != null || selectedTerm != null || selectedClass != null || selectedDate != null) {
                        EduCoreSecondaryButton(
                            text = "Clear finance filters",
                            onClick = {
                                selectedSession = null
                                selectedTerm = null
                                selectedClass = null
                                selectedDate = null
                            },
                            modifier = Modifier.fillMaxWidth(),
                        )
                    }
                }
            }
        }
        item { EduCoreSearchBar(state.query, onQuery, placeholder = "Search ${section?.title?.lowercase() ?: "records"}") }
        if (records.isEmpty()) {
            item {
                val filtersClear = selectedSession == null && selectedTerm == null && selectedClass == null && selectedDate == null
                EduCoreEmptyState(
                    title = if (state.query.isBlank() && filtersClear) "No records yet" else "No matching records",
                    message = if (state.query.isBlank() && filtersClear) "Nothing has been recorded in this section." else "Change or clear the selected filters.",
                )
            }
        }
        items(records, key = { "${section?.key}-${it.id}" }) { record -> OperationsRecordCard(record, width) }
    }

    if (showDatePicker) {
        val pickerState = rememberDatePickerState()
        DatePickerDialog(
            onDismissRequest = { showDatePicker = false },
            confirmButton = {
                TextButton(onClick = {
                    pickerState.selectedDateMillis?.let { selectedDate = formatUtcDate(it) }
                    showDatePicker = false
                }) { Text("Apply") }
            },
            dismissButton = { TextButton(onClick = { showDatePicker = false }) { Text("Cancel") } },
        ) {
            DatePicker(state = pickerState)
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun FinanceDropdown(
    label: String,
    options: List<String>,
    selected: String?,
    onSelected: (String?) -> Unit,
) {
    var expanded by remember { mutableStateOf(false) }
    ExposedDropdownMenuBox(expanded = expanded, onExpandedChange = { expanded = !expanded }) {
        OutlinedTextField(
            value = selected ?: "All $label",
            onValueChange = {},
            readOnly = true,
            label = { Text(label) },
            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded) },
            modifier = Modifier.menuAnchor().fillMaxWidth(),
        )
        ExposedDropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
            DropdownMenuItem(
                text = { Text("All $label") },
                onClick = { onSelected(null); expanded = false },
            )
            options.forEach { option ->
                DropdownMenuItem(
                    text = { Text(option) },
                    onClick = { onSelected(option); expanded = false },
                )
            }
        }
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
        border = BorderStroke(0.7.dp, EduCoreColors.Info200),
    ) {
        Column(
            Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
                Column(Modifier.weight(1f)) {
                    Text(record.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Medium, maxLines = 2, overflow = TextOverflow.Ellipsis)
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

private fun List<OperationsRecord>.fieldValues(label: String): List<String> =
    mapNotNull { it.fieldValue(label) }
        .filter { it.isNotBlank() && it !in setOf("Not assigned", "Not set", "Not paid", "Pending") }
        .distinct()
        .sorted()

private fun OperationsRecord.fieldValue(label: String): String? =
    fields.firstOrNull { it.label.equals(label, ignoreCase = true) }?.value

private fun OperationsRecord.matchesDate(date: String): Boolean =
    fields.any { field -> field.label in DATE_FIELD_LABELS && field.value.take(10) == date }

private fun formatUtcDate(epochMillis: Long): String = SimpleDateFormat("yyyy-MM-dd", Locale.US).apply {
    timeZone = TimeZone.getTimeZone("UTC")
}.format(Date(epochMillis))

private val DATE_FIELD_LABELS = setOf("Due", "Date", "Payment date", "Paid at", "Applied", "Issued")

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
