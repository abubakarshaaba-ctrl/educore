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
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.LibraryBooks
import androidx.compose.material.icons.filled.MenuBook
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
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
internal fun LibraryScreen(
    state: OperationsUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSection: (Int) -> Unit,
) {
    val workspace = state.workspace ?: return
    val section = workspace.sections.getOrNull(state.selectedSection)
    val records = remember(section?.records, state.query) {
        section?.records.orEmpty().filterOperations(state.query)
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Library",
                subtitle = "Catalogue availability and active borrowing records",
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item { LibrarySummary(workspace) }

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
                query = state.query,
                onQueryChange = onQuery,
                placeholder = if (section?.key == "loans") "Search borrower, book or status" else "Search title, author, category or ISBN",
            )
        }

        if (records.isEmpty()) {
            item {
                EduCoreEmptyState(
                    title = if (state.query.isBlank()) "No library records" else "No matching library records",
                    message = if (section?.key == "loans") {
                        "Active and overdue loans will appear here."
                    } else {
                        "Books added to the school catalogue will appear here."
                    },
                )
            }
        } else if (section?.key == "loans") {
            items(records, key = { "loan-${it.id}" }) { LibraryLoanCard(it) }
        } else {
            items(records, key = { "book-${it.id}" }) { LibraryBookCard(it) }
        }

        item {
            Text(
                text = if (workspace.module.canManage) {
                    "Your account can manage library records. Issue/return actions remain server-controlled until the native mutation endpoints receive explicit tenant-boundary hardening."
                } else {
                    "This account has read-only library access."
                },
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }
    }
}

@Composable
private fun LibrarySummary(workspace: OperationsWorkspace) {
    val metrics = workspace.metrics.associateBy { it.key }
    Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            metrics["titles"]?.let {
                EduCoreMetricCard(
                    label = it.label,
                    value = it.value,
                    icon = Icons.Default.LibraryBooks,
                    modifier = Modifier.weight(1f),
                    tone = EduCoreTone.Brand,
                )
            }
            metrics["available"]?.let {
                EduCoreMetricCard(
                    label = it.label,
                    value = it.value,
                    icon = Icons.Default.MenuBook,
                    modifier = Modifier.weight(1f),
                    tone = EduCoreTone.Success,
                )
            }
        }
        metrics["loans"]?.let {
            EduCoreMetricCard(
                label = it.label,
                value = it.value,
                icon = Icons.Default.MenuBook,
                modifier = Modifier.fillMaxWidth(),
                tone = EduCoreTone.Warning,
            )
        }
    }
}

@Composable
private fun LibraryBookCard(book: OperationsRecord) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
            verticalAlignment = Alignment.Top,
        ) {
            Icon(Icons.Default.LibraryBooks, contentDescription = null, tint = MaterialTheme.colorScheme.primary)
            Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                Text(book.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                book.subtitle?.let {
                    Text(it, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                Text(
                    listOfNotNull(book.fieldValue("Category"), book.fieldValue("Location"), book.fieldValue("ISBN"))
                        .joinToString(" · "),
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis,
                )
                Text(
                    "Available: ${book.fieldValue("Available") ?: "Not recorded"}",
                    style = MaterialTheme.typography.bodyMedium,
                    fontWeight = FontWeight.Medium,
                )
            }
            book.status?.let {
                EduCoreStatusBadge(
                    text = it.replace('_', ' ').replaceFirstChar(Char::uppercase),
                    tone = if (it.equals("active", true)) EduCoreTone.Success else EduCoreTone.Neutral,
                )
            }
        }
    }
}

@Composable
private fun LibraryLoanCard(loan: OperationsRecord) {
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
                    Text(loan.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    loan.subtitle?.let {
                        Text(it, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    }
                }
                loan.status?.let {
                    EduCoreStatusBadge(
                        text = it.replace('_', ' ').replaceFirstChar(Char::uppercase),
                        tone = if (it.equals("overdue", true)) EduCoreTone.Danger else EduCoreTone.Warning,
                    )
                }
            }
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                LibraryValue("Issued", loan.fieldValue("Issued") ?: "—", Modifier.weight(1f))
                LibraryValue("Due", loan.fieldValue("Due") ?: "—", Modifier.weight(1f))
                LibraryValue("Fine", loan.fieldValue("Fine") ?: "—", Modifier.weight(1f))
            }
        }
    }
}

@Composable
private fun LibraryValue(label: String, value: String, modifier: Modifier = Modifier) {
    Column(modifier) {
        Text(label.uppercase(), style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Text(value, style = MaterialTheme.typography.bodyMedium, maxLines = 1, overflow = TextOverflow.Ellipsis)
    }
}

private fun OperationsRecord.fieldValue(label: String): String? =
    fields.firstOrNull { it.label.equals(label, ignoreCase = true) }?.value
