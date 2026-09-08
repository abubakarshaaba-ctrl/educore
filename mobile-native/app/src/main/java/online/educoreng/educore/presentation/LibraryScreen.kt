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
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreMetricCard
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSegmentedControl
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTabs
import online.educoreng.educore.core.designsystem.component.EduCoreTextField
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.OperationsRecord
import online.educoreng.educore.core.model.OperationsWorkspace
import online.educoreng.educore.core.network.dto.LibraryBookOptionDto
import online.educoreng.educore.core.network.dto.LibraryBorrowerOptionDto

@Composable
internal fun LibraryScreen(
    state: OperationsUiState,
    management: LibraryManagementUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSection: (Int) -> Unit,
    onOpenIssue: () -> Unit,
    onCloseIssue: () -> Unit,
    onBook: (Long?) -> Unit,
    onBorrowerType: (LibraryBorrowerType) -> Unit,
    onBorrower: (Long?) -> Unit,
    onDueDate: (String) -> Unit,
    onNotes: (String) -> Unit,
    onIssue: () -> Unit,
    onReturn: (String) -> Unit,
) {
    val workspace = state.workspace ?: return

    if (management.issueOpen) {
        LibraryIssueScreen(
            management = management,
            onBack = onCloseIssue,
            onBook = onBook,
            onBorrowerType = onBorrowerType,
            onBorrower = onBorrower,
            onDueDate = onDueDate,
            onNotes = onNotes,
            onIssue = onIssue,
        )
        return
    }

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
        management.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        management.message?.let {
            item {
                Text(
                    text = it,
                    style = MaterialTheme.typography.bodyMedium,
                    color = EduCoreColors.Success700,
                )
            }
        }
        item { LibrarySummary(workspace) }

        if (workspace.module.canManage) {
            item {
                EduCorePrimaryButton(
                    text = "Issue book",
                    onClick = onOpenIssue,
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !management.saving,
                )
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
            items(records, key = { "loan-${it.id}" }) {
                LibraryLoanCard(
                    loan = it,
                    canManage = workspace.module.canManage,
                    busy = management.saving,
                    onReturn = onReturn,
                )
            }
        } else {
            items(records, key = { "book-${it.id}" }) { LibraryBookCard(it) }
        }

        if (!workspace.module.canManage) {
            item {
                Text(
                    text = "This account has read-only library access.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
        }
    }
}

@Composable
private fun LibraryIssueScreen(
    management: LibraryManagementUiState,
    onBack: () -> Unit,
    onBook: (Long?) -> Unit,
    onBorrowerType: (LibraryBorrowerType) -> Unit,
    onBorrower: (Long?) -> Unit,
    onDueDate: (String) -> Unit,
    onNotes: (String) -> Unit,
    onIssue: () -> Unit,
) {
    var bookQuery by rememberSaveable { mutableStateOf("") }
    var borrowerQuery by rememberSaveable { mutableStateOf("") }
    val filteredBooks = remember(management.books, bookQuery) {
        management.books.filter {
            bookQuery.isBlank() || it.title.contains(bookQuery, true) || it.author.orEmpty().contains(bookQuery, true)
        }.take(40)
    }
    val filteredBorrowers = remember(management.borrowerOptions, borrowerQuery) {
        management.borrowerOptions.filter {
            borrowerQuery.isBlank() || it.name.contains(borrowerQuery, true) || it.reference.orEmpty().contains(borrowerQuery, true)
        }.take(60)
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Issue Book",
                subtitle = "Select one available title and one borrower",
                onBack = onBack,
            )
        }
        management.errorMessage?.let { item { EduCoreErrorBanner(it) } }

        item {
            Text("Book", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
        }
        item {
            EduCoreSearchBar(
                value = bookQuery,
                onValueChange = { bookQuery = it },
                placeholder = "Search available books",
                enabled = !management.loadingOptions && !management.saving,
            )
        }
        if (management.loadingOptions && management.books.isEmpty()) {
            item { Text("Loading available books…", color = MaterialTheme.colorScheme.onSurfaceVariant) }
        } else if (filteredBooks.isEmpty()) {
            item { EduCoreEmptyState("No available books", "No matching title currently has an available copy.") }
        } else {
            items(filteredBooks, key = { "issue-book-${it.id}" }) { book ->
                LibraryBookOptionCard(
                    book = book,
                    selected = management.selectedBookId == book.id,
                    onClick = { onBook(book.id) },
                )
            }
        }

        item {
            Text("Borrower", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
        }
        item {
            EduCoreSegmentedControl(
                options = listOf("Student", "Staff"),
                selectedIndex = if (management.borrowerType == LibraryBorrowerType.STUDENT) 0 else 1,
                onSelected = {
                    borrowerQuery = ""
                    onBorrowerType(if (it == 0) LibraryBorrowerType.STUDENT else LibraryBorrowerType.STAFF)
                },
                enabled = !management.saving,
            )
        }
        item {
            EduCoreSearchBar(
                value = borrowerQuery,
                onValueChange = { borrowerQuery = it },
                placeholder = if (management.borrowerType == LibraryBorrowerType.STUDENT) "Search student or admission number" else "Search staff name or ID",
                enabled = !management.loadingOptions && !management.saving,
            )
        }
        if (!management.loadingOptions && filteredBorrowers.isEmpty()) {
            item { EduCoreEmptyState("No borrowers", "No matching active borrower is available.") }
        } else {
            items(filteredBorrowers, key = { "issue-borrower-${management.borrowerType}-${it.id}" }) { borrower ->
                LibraryBorrowerCard(
                    borrower = borrower,
                    selected = management.selectedBorrowerId == borrower.id,
                    onClick = { onBorrower(borrower.id) },
                )
            }
        }

        item {
            EduCoreTextField(
                value = management.dueDate,
                onValueChange = onDueDate,
                label = "Due date",
                modifier = Modifier.fillMaxWidth(),
                supportingText = "Use YYYY-MM-DD. The due date must be after today.",
                enabled = !management.saving,
            )
        }
        item {
            EduCoreTextField(
                value = management.notes,
                onValueChange = onNotes,
                label = "Notes (optional)",
                modifier = Modifier.fillMaxWidth(),
                singleLine = false,
                enabled = !management.saving,
            )
        }
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreSecondaryButton(
                    text = "Cancel",
                    onClick = onBack,
                    modifier = Modifier.weight(1f),
                    enabled = !management.saving,
                )
                EduCorePrimaryButton(
                    text = "Issue book",
                    onClick = onIssue,
                    modifier = Modifier.weight(1f),
                    enabled = management.canIssue,
                    loading = management.saving,
                )
            }
        }
    }
}

@Composable
private fun LibraryBookOptionCard(book: LibraryBookOptionDto, selected: Boolean, onClick: () -> Unit) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = if (selected) EduCoreColors.Info100 else EduCoreColors.White),
        border = BorderStroke(1.dp, if (selected) EduCoreColors.Info700 else EduCoreColors.Line200),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Column(Modifier.weight(1f)) {
                Text(book.title, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
                Text(book.author ?: "Unknown author", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            EduCoreStatusBadge("${book.availableCopies} available", if (selected) EduCoreTone.Info else EduCoreTone.Success)
        }
    }
}

@Composable
private fun LibraryBorrowerCard(borrower: LibraryBorrowerOptionDto, selected: Boolean, onClick: () -> Unit) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = if (selected) EduCoreColors.Info100 else EduCoreColors.White),
        border = BorderStroke(1.dp, if (selected) EduCoreColors.Info700 else EduCoreColors.Line200),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Column(Modifier.weight(1f)) {
                Text(borrower.name, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Medium)
                borrower.reference?.let { Text(it, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant) }
            }
            if (selected) EduCoreStatusBadge("Selected", EduCoreTone.Info)
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
private fun LibraryLoanCard(
    loan: OperationsRecord,
    canManage: Boolean,
    busy: Boolean,
    onReturn: (String) -> Unit,
) {
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
            if (canManage && loan.status in setOf("issued", "overdue")) {
                EduCoreSecondaryButton(
                    text = "Mark returned",
                    onClick = { onReturn(loan.id) },
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !busy,
                )
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
