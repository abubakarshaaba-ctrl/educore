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
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Assessment
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Save
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.foundation.rememberScrollState
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreInfoBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreResponsiveButtonPair
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.component.EduCoreWarningBanner
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.model.PublishedResult
import online.educoreng.educore.core.model.ScoreAssignment
import online.educoreng.educore.core.model.ScoreSheet
import online.educoreng.educore.core.model.SyncState
import online.educoreng.educore.core.model.SCORE_WORKSPACE_PARALLEL

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
    LazyColumn(Modifier.fillMaxSize().imePadding(), contentPadding = PaddingValues(eduCoreScreenPadding()), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
        item {
            Text("Score entry", style = MaterialTheme.typography.headlineSmall, color = EduCoreColors.Ink900)
            Text(listOfNotNull(assignments.termName, assignments.sessionName).joinToString(" · "), color = EduCoreColors.Slate600)
        }
        if (assignments.isFromCache) item { EduCoreWarningBanner("Showing saved assignments while EduCore reconnects.") }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item { EduCoreSearchBar(state.search, onSearch, placeholder = "Search class or subject") }
        if (filtered.isEmpty()) item { EduCoreEmptyState("No score workspaces", "No current teaching assignment is available for score entry.") }
        items(filtered, key = { "${it.workspaceType}:${it.classId}:${it.subjectId}" }) { assignment ->
            Card(
                onClick = { onOpen(assignment, assignments.termId) },
                colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                border = BorderStroke(1.dp, EduCoreColors.Line200),
            ) {
                Row(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalAlignment = Alignment.CenterVertically) {
                    Surface(shape = MaterialTheme.shapes.medium, color = EduCoreColors.Info100) {
                        Icon(Icons.Default.Assessment, null, Modifier.padding(EduCoreSpacing.Md), tint = EduCoreColors.Navy900)
                    }
                    Spacer(Modifier.width(EduCoreSpacing.Md))
                    Column(Modifier.weight(1f)) {
                        Text(assignment.subjectName, style = MaterialTheme.typography.titleMedium)
                        Text(assignment.className, color = EduCoreColors.Slate600)
                    }
                    EduCoreStatusBadge(
                        if (assignment.workspaceType == SCORE_WORKSPACE_PARALLEL) "Parallel" else "Open sheet",
                        if (assignment.workspaceType == SCORE_WORKSPACE_PARALLEL) EduCoreTone.Warning else EduCoreTone.Info,
                    )
                }
            }
        }
    }
}

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
    LazyColumn(Modifier.fillMaxSize().imePadding(), contentPadding = PaddingValues(eduCoreScreenPadding()), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
        item { ScoreHeader(sheet, onBack) }
        if (sheet.locked) item { EduCoreWarningBanner(sheet.lockReason ?: "This score sheet is locked.") }
        if (sheet.isDraftStale) item { EduCoreWarningBanner("This device draft is older than the server sheet. Discard it and reload before saving.") }
        if (sheet.syncState != SyncState.NONE) item {
            val message = sheet.syncMessage ?: "This explicit submission is waiting to synchronize."
            if (sheet.syncState == SyncState.QUEUED || sheet.syncState == SyncState.SYNCING) EduCoreInfoBanner(message, title = "Sync pending") else EduCoreWarningBanner(message, title = "Sync needs attention")
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        if (sheet.students.isEmpty()) item { EduCoreEmptyState("No active students", "This class has no active students available for score entry.") }
        items(sheet.students, key = { it.id }) { student ->
            Card(colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
                Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Column(Modifier.weight(1f)) {
                            Text(student.name, style = MaterialTheme.typography.titleMedium)
                            Text(student.admissionNumber, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                        }
                    }
                    sheet.assessments.forEach { assessment ->
                        val cell = student.scores[assessment.id]
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Column(Modifier.weight(1f)) {
                                Text(assessment.name, fontWeight = FontWeight.SemiBold)
                                Text(
                                    if (assessment.isSplit) "Theory / ${assessment.theoryMaximum ?: 0.0} · Objective ${cell?.objectiveScore ?: "—"}" else "Maximum ${assessment.maximum}",
                                    style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600,
                                )
                            }
                            OutlinedTextField(
                                value = cell?.value?.formatScore().orEmpty(),
                                onValueChange = { onValue(student.id, assessment.id, it) },
                                enabled = !sheet.locked && cell?.locked != true && sheet.syncState == SyncState.NONE,
                                modifier = Modifier.width(112.dp),
                                singleLine = true,
                                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal, imeAction = ImeAction.Done),
                                trailingIcon = if (cell?.locked == true) ({ Icon(Icons.Default.Lock, null) }) else null,
                            )
                        }
                    }
                }
            }
        }
        if (sheet.hasLocalDraft) item {
            EduCoreResponsiveButtonPair(
                primaryText = if (sheet.syncState == SyncState.FAILED) "Retry sync" else "Save scores",
                onPrimary = onSubmit,
                secondaryText = "Discard",
                onSecondary = onDiscard,
                primaryEnabled = !state.isSaving && !sheet.locked && sheet.syncState != SyncState.QUEUED && sheet.syncState != SyncState.SYNCING && sheet.syncState != SyncState.CONFLICT,
                primaryLoading = state.isSaving,
            )
        }
        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

@Composable
private fun ScoreHeader(sheet: ScoreSheet, onBack: () -> Unit) {
    EduCorePageHeader(title = sheet.subjectName, subtitle = "${sheet.className} · ${sheet.termName}", onBack = onBack)
}

@Composable
internal fun PublishedResultsScreen(
    state: ScoresUiState,
    onBack: () -> Unit,
    onRetry: () -> Unit,
    onChild: (Long) -> Unit = {},
) {
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
        if (results.children.size > 1) {
            item {
                Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    Text(
                        "Select child",
                        style = MaterialTheme.typography.labelLarge,
                        color = EduCoreColors.Slate600,
                    )
                    Row(
                        modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    ) {
                        results.children.forEach { child ->
                            FilterChip(
                                selected = child.id == results.studentId,
                                onClick = { onChild(child.id) },
                                label = {
                                    Text(
                                        listOfNotNull(child.name, child.className).joinToString(" · "),
                                        maxLines = 1,
                                    )
                                },
                            )
                        }
                    }
                }
            }
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }

        if (results.results.isEmpty() && results.parallelResults.isEmpty()) {
            item {
                EduCoreEmptyState(
                    "No published results",
                    "Your school has not published a result for this account yet.",
                )
            }
        }

        if (results.results.isNotEmpty()) {
            item {
                Text(
                    "Conventional curriculum",
                    style = MaterialTheme.typography.titleMedium,
                    color = EduCoreColors.Ink900,
                )
            }
            items(results.results, key = { "conventional:${it.id}" }) { result ->
                PublishedResultCard(result, parallel = false)
            }
        }

        if (results.parallelResults.isNotEmpty()) {
            item {
                Text(
                    "Other curriculum results",
                    style = MaterialTheme.typography.titleMedium,
                    color = EduCoreColors.Ink900,
                )
            }
            items(results.parallelResults, key = { "parallel:${it.id}" }) { result ->
                PublishedResultCard(result, parallel = true)
            }
        }
    }
}

@Composable
private fun PublishedResultCard(result: PublishedResult, parallel: Boolean) {
    Card(
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(
            Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            Row(verticalAlignment = Alignment.Top) {
                Column(Modifier.weight(1f)) {
                    Text(
                        if (parallel) result.curriculumName ?: "Parallel curriculum" else result.term.orEmpty(),
                        style = MaterialTheme.typography.titleLarge,
                    )
                    Text(
                        if (parallel) {
                            listOfNotNull(result.resultClassName, result.term, result.session).joinToString(" · ")
                        } else {
                            result.session.orEmpty()
                        },
                        color = EduCoreColors.Slate600,
                    )
                }
                EduCoreStatusBadge(
                    "${result.average.formatScore()}%",
                    if (parallel) EduCoreTone.Info else EduCoreTone.Success,
                )
            }

            val summary = buildList {
                add("Position ${result.position ?: "—"} of ${result.classSize ?: "—"}")
                add("${result.subjectsOffered} subjects")
                if (result.subjectsFailed > 0) add("${result.subjectsFailed} failed")
                if (!parallel && result.promotionStatus.isNotBlank()) {
                    add(result.promotionStatus.replace('_', ' '))
                }
            }.joinToString(" · ")
            Text(summary, color = EduCoreColors.Slate600)

            if (parallel && result.maximumTotal != null) {
                Text(
                    "Total ${result.totalScore.formatScore()} / ${result.maximumTotal.formatScore()}",
                    style = MaterialTheme.typography.bodySmall,
                    color = EduCoreColors.Slate600,
                )
            }

            result.subjects.forEach { subject ->
                Surface(color = EduCoreColors.Page50, shape = MaterialTheme.shapes.medium) {
                    Column(
                        Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
                        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs),
                    ) {
                        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                            Column(Modifier.weight(1f)) {
                                Text(subject.name, fontWeight = FontWeight.SemiBold)
                                Text("${subject.grade} · ${subject.remark}", color = EduCoreColors.Slate600)
                            }
                            Text(subject.total.formatScore(), fontWeight = FontWeight.Bold)
                        }
                        if (parallel && subject.assessments.isNotEmpty()) {
                            Text(
                                subject.assessments.joinToString(" · ") { assessment ->
                                    "${assessment.name}: ${assessment.score?.formatScore() ?: "—"}/${assessment.maximum.formatScore()}"
                                },
                                style = MaterialTheme.typography.bodySmall,
                                color = EduCoreColors.Slate600,
                            )
                        }
                    }
                }
            }

            if (!parallel) {
                result.formTutorRemark?.let { Text("Form tutor: $it", style = MaterialTheme.typography.bodySmall) }
                result.principalRemark?.let { Text("Principal: $it", style = MaterialTheme.typography.bodySmall) }
            }
        }
    }
}

private fun Double.formatScore(): String = if (this % 1.0 == 0.0) toInt().toString() else toString()
