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
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.AccountBalance
import androidx.compose.material.icons.filled.Inventory2
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreMetricCard
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
