package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Assessment
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Save
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreInfoBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseSectionCard
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.component.EduCoreWarningBanner
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.PublishedResult
import online.educoreng.educore.core.model.ScoreAssignment
import online.educoreng.educore.core.model.ScoreSheet
import online.educoreng.educore.core.model.SyncState

@Composable
internal fun ScoreAssignmentsScreen(
    state: ScoresUiState,
    onSearch: (String) -> Unit,
    onOpen: (ScoreAssignment, Long?) -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.assignments == null) return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading score workspaces")
    val assignments = state.assignments ?: return EduCoreErrorState(
        state.errorMessage ?: "Score workspaces are unavailable.",
        Modifier.fillMaxSize(),
        onRetry = onRetry,
    )
    val filtered = remember(assignments.assignments, state.search) {
        assignments.assignments.filter {
            state.search.isBlank() || it.className.contains(state.search, true) || it.subjectName.contains(state.search, true)
        }
    }
    LazyColumn(
        Modifier.fillMaxSize().imePadding(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Score Entry",
                subtitle = listOfNotNull(assignments.termName, assignments.sessionName).joinToString(" · "),
            )
        }
        if (assignments.isFromCache) item { EduCoreWarningBanner("Showing saved assignments while EduCore reconnects.") }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item { EduCoreSearchBar(state.search, onSearch, placeholder = "Search class or subject") }
        if (filtered.isEmpty()) item {
            EduCoreEmptyState("No score workspaces", "No current teaching assignment is available for score entry.")
        }
        items(filtered, key = { "${it.classId}:${it.subjectId}" }) { assignment ->
            Card(
                onClick = { onOpen(assignment, assignments.termId) },
                colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                border = BorderStroke(1.dp, EduCoreColors.Line200),
                shape = MaterialTheme.shapes.large,
            ) {
                Row(
                    Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Surface(shape = MaterialTheme.shapes.medium, color = EduCoreColors.Gold50) {
                        Icon(
                            Icons.Default.Assessment,
                            null,
                            Modifier.padding(EduCoreSpacing.Md),
                            tint = EduCoreColors.Navy900,
                        )
                    }
                    Spacer(Modifier.width(EduCoreSpacing.Md))
                    Column(Modifier.weight(1f)) {
                        Text(assignment.subjectName, style = MaterialTheme.typography.titleMedium)
                        Text(assignment.className, color = EduCoreColors.Slate600)
                    }
                    EduCoreStatusBadge("Open", EduCoreTone.Accent)
                }
            }
        }
    }
}

/**
 * Compact score grid modelled on the approved August concept. Assessment
 * columns scroll horizontally inside each student card so the page remains
 * readable on small Android devices without opening a web table.
 */
@Composable
internal fun ScoreSheetScreen(
    state: ScoresUiState,
    onBack: () -> Unit,
    onValue: (Long, Long, String) -> Unit,
    onDiscard: () -> Unit,
    onSubmit: () -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.sheet == null) return EduCoreLoadingState(Modifier.fillMaxSize(), "Opening score sheet")
    val sheet = state.sheet ?: return EduCoreErrorState(
        state.errorMessage ?: "The score sheet is unavailable.",
        Modifier.fillMaxSize(),
        onRetry = onRetry,
    )
    var studentQuery by remember { mutableStateOf("") }
    val students = remember(sheet.students, studentQuery) {
        sheet.students.filter {
            studentQuery.isBlank() || it.name.contains(studentQuery, true) || it.admissionNumber.contains(studentQuery, true)
        }
    }

    Column(Modifier.fillMaxSize().imePadding()) {
        LazyColumn(
            modifier = Modifier.weight(1f),
            contentPadding = PaddingValues(eduCoreScreenPadding()),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            item { ScoreHeader(sheet, onBack) }
            if (sheet.locked) item { EduCoreWarningBanner(sheet.lockReason ?: "This score sheet is locked.") }
            if (sheet.isDraftStale) item {
                EduCoreWarningBanner("This device draft is older than the server sheet. Discard it and reload before saving.")
            }
            if (sheet.syncState != SyncState.NONE) item {
                val message = sheet.syncMessage ?: "This explicit submission is waiting to synchronize."
                if (sheet.syncState == SyncState.QUEUED || sheet.syncState == SyncState.SYNCING) {
                    EduCoreInfoBanner(message, title = "Sync pending")
                } else {
                    EduCoreWarningBanner(message, title = "Sync needs attention")
                }
            }
            state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
            item {
                EduCoreSearchBar(
                    value = studentQuery,
                    onValueChange = { studentQuery = it },
                    placeholder = "Search students",
                )
            }
            if (students.isEmpty()) item {
                EduCoreEmptyState(
                    if (sheet.students.isEmpty()) "No active students" else "No students found",
                    if (sheet.students.isEmpty()) "This class has no active students available for score entry." else "Try another name or admission number.",
                )
            }
            items(students, key = { it.id }) { student ->
                EduCoreShowcaseSectionCard {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Column(Modifier.weight(1f)) {
                            Text(student.name, style = MaterialTheme.typography.titleMedium, color = EduCoreColors.Ink900)
                            Text(student.admissionNumber, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                        }
                    }
                    Spacer(Modifier.height(EduCoreSpacing.Md))
                    Row(
                        modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    ) {
                        sheet.assessments.forEach { assessment ->
                            val cell = student.scores[assessment.id]
                            Surface(
                                modifier = Modifier.width(88.dp),
                                shape = MaterialTheme.shapes.medium,
                                color = EduCoreColors.Page50,
                                border = BorderStroke(1.dp, EduCoreColors.Line200),
                            ) {
                                Column(
                                    Modifier.padding(EduCoreSpacing.Sm),
                                    horizontalAlignment = Alignment.CenterHorizontally,
                                    verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs),
                                ) {
                                    Text(
                                        assessment.name,
                                        style = MaterialTheme.typography.labelSmall,
                                        fontWeight = FontWeight.SemiBold,
                                        maxLines = 1,
                                    )
                                    Text(
                                        "/${if (assessment.isSplit) assessment.theoryMaximum ?: 0.0 else assessment.maximum}".replace(".0", ""),
                                        style = MaterialTheme.typography.labelSmall,
                                        color = EduCoreColors.Muted500,
                                    )
                                    OutlinedTextField(
                                        value = cell?.value?.formatScore().orEmpty(),
                                        onValueChange = { onValue(student.id, assessment.id, it) },
                                        enabled = !sheet.locked && cell?.locked != true && sheet.syncState == SyncState.NONE,
                                        modifier = Modifier.width(70.dp),
                                        singleLine = true,
                                        keyboardOptions = KeyboardOptions(
                                            keyboardType = KeyboardType.Decimal,
                                            imeAction = ImeAction.Done,
                                        ),
                                        trailingIcon = if (cell?.locked == true) ({
                                            Icon(Icons.Default.Lock, null)
                                        }) else null,
                                    )
                                }
                            }
                        }
                    }
                }
            }
            item { Spacer(Modifier.height(EduCoreSpacing.Md)) }
        }

        if (sheet.hasLocalDraft) {
            Surface(
                color = EduCoreColors.White,
                shadowElevation = 8.dp,
                border = BorderStroke(1.dp, EduCoreColors.Line200),
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    androidx.compose.material3.TextButton(
                        onClick = onDiscard,
                        enabled = !state.isSaving,
                    ) { Text("Discard", color = EduCoreColors.Slate700) }
                    EduCorePrimaryButton(
                        text = if (sheet.syncState == SyncState.FAILED) "Retry sync" else "Save scores",
                        onClick = onSubmit,
                        modifier = Modifier.weight(1f),
                        enabled = !state.isSaving && !sheet.locked &&
                            sheet.syncState != SyncState.QUEUED &&
                            sheet.syncState != SyncState.SYNCING &&
                            sheet.syncState != SyncState.CONFLICT,
                        loading = state.isSaving,
                        leadingIcon = { Icon(Icons.Default.Save, contentDescription = null) },
                    )
                }
            }
        }
    }
}

@Composable
private fun ScoreHeader(sheet: ScoreSheet, onBack: () -> Unit) {
    EduCorePageHeader(
        title = sheet.subjectName,
        subtitle = "${sheet.className} · ${sheet.termName}",
        onBack = onBack,
    )
}

@Composable
internal fun PublishedResultsScreen(state: ScoresUiState, onBack: () -> Unit, onRetry: () -> Unit) {
    if (state.isLoading && state.publishedResults == null) return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading published results")
    val results = state.publishedResults ?: return EduCoreErrorState(
        state.errorMessage ?: "Published results are unavailable.",
        Modifier.fillMaxSize(),
        onRetry = onRetry,
    )
    LazyColumn(
        Modifier.fillMaxSize().imePadding(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                "Published results",
                listOfNotNull(results.studentName, results.admissionNumber, results.className).joinToString(" · "),
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        if (results.results.isEmpty()) item {
            EduCoreEmptyState("No published results", "Your school has not published a report card for this account yet.")
        }
        items(results.results, key = PublishedResult::id) { result ->
            Card(
                colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                border = BorderStroke(1.dp, EduCoreColors.Line200),
                shape = MaterialTheme.shapes.large,
            ) {
                Column(
                    Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
                    verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                ) {
                    Row {
                        Column(Modifier.weight(1f)) {
                            Text(result.term.orEmpty(), style = MaterialTheme.typography.titleLarge)
                            Text(result.session.orEmpty(), color = EduCoreColors.Slate600)
                        }
                        EduCoreStatusBadge("${result.average.formatScore()}%", EduCoreTone.Success)
                    }
                    Text("Position ${result.position ?: "—"} of ${result.classSize ?: "—"} · ${result.promotionStatus.replace('_', ' ')}")
                    result.subjects.forEach { subject ->
                        Surface(color = EduCoreColors.Page50, shape = MaterialTheme.shapes.medium) {
                            Row(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md)) {
                                Column(Modifier.weight(1f)) {
                                    Text(subject.name, fontWeight = FontWeight.SemiBold)
                                    Text("${subject.grade} · ${subject.remark}", color = EduCoreColors.Slate600)
                                }
                                Text(subject.total.formatScore(), fontWeight = FontWeight.Bold)
                            }
                        }
                    }
                    result.formTutorRemark?.let { Text("Form tutor: $it", style = MaterialTheme.typography.bodySmall) }
                    result.principalRemark?.let { Text("Principal: $it", style = MaterialTheme.typography.bodySmall) }
                }
            }
        }
    }
}

private fun Double.formatScore(): String = if (this % 1.0 == 0.0) toInt().toString() else toString()
