package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Assessment
import androidx.compose.material.icons.filled.Groups
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Save
import androidx.compose.material.icons.filled.School
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
import androidx.compose.ui.focus.FocusDirection
import androidx.compose.ui.platform.LocalFocusManager
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreInfoBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseSectionCard
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseStat
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
    if (state.isLoading && state.assignments == null) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading score workspaces")
    }
    val assignments = state.assignments ?: return EduCoreErrorState(
        state.errorMessage ?: "Score workspaces are unavailable.",
        Modifier.fillMaxSize(),
        onRetry = onRetry,
    )
    val filtered = remember(assignments.assignments, state.search) {
        assignments.assignments.filter {
            state.search.isBlank() ||
                it.className.contains(state.search, true) ||
                it.subjectName.contains(state.search, true)
        }
    }
    val classCount = remember(assignments.assignments) {
        assignments.assignments.map(ScoreAssignment::classId).distinct().size
    }
    val subjectCount = remember(assignments.assignments) {
        assignments.assignments.map(ScoreAssignment::subjectId).distinct().size
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize().imePadding().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCoreShowcaseHero(
                eyebrow = "SCORE ENTRY",
                title = "Score Entry",
                subtitle = listOfNotNull(assignments.termName, assignments.sessionName)
                    .filter(String::isNotBlank)
                    .joinToString(" · ")
                    .ifBlank { "Authorised teaching assignments" },
                trailing = {
                    Surface(
                        modifier = Modifier.size(48.dp),
                        shape = CircleShape,
                        color = EduCoreColors.Gold100,
                        contentColor = EduCoreColors.Navy900,
                    ) {
                        Box(contentAlignment = Alignment.Center) {
                            Icon(Icons.Default.Assessment, contentDescription = null, modifier = Modifier.size(24.dp))
                        }
                    }
                },
            )
        }

        if (assignments.isFromCache) {
            item { EduCoreWarningBanner("Showing saved assignments while EduCore reconnects.") }
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }

        item {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                EduCoreShowcaseStat(
                    label = "Workspaces",
                    value = assignments.assignments.size.toString(),
                    icon = Icons.Default.Assessment,
                    tone = EduCoreTone.Accent,
                    modifier = Modifier.weight(1f),
                )
                EduCoreShowcaseStat(
                    label = "Classes",
                    value = classCount.toString(),
                    icon = Icons.Default.School,
                    tone = EduCoreTone.Brand,
                    modifier = Modifier.weight(1f),
                )
                EduCoreShowcaseStat(
                    label = "Subjects",
                    value = subjectCount.toString(),
                    icon = Icons.Default.Assessment,
                    tone = EduCoreTone.Info,
                    modifier = Modifier.weight(1f),
                )
            }
        }

        item {
            EduCoreSectionHeader(
                title = "Teaching assignments",
                supportingText = "Open a class and subject",
            )
        }
        item {
            EduCoreSearchBar(
                value = state.search,
                onValueChange = onSearch,
                placeholder = "Search class or subject",
            )
        }

        if (filtered.isEmpty()) {
            item {
                EduCoreShowcaseSectionCard {
                    EduCoreEmptyState(
                        "No score workspaces",
                        if (state.search.isBlank()) {
                            "No teaching assignment is available for score entry."
                        } else {
                            "No class or subject matches your search."
                        },
                    )
                }
            }
        }

        items(filtered, key = { "${it.classId}:${it.subjectId}" }) { assignment ->
            Card(
                onClick = { onOpen(assignment, assignments.termId) },
                modifier = Modifier.fillMaxWidth(),
                colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                border = BorderStroke(1.dp, EduCoreColors.Line200),
                shape = MaterialTheme.shapes.large,
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                ) {
                    Surface(
                        modifier = Modifier.size(44.dp),
                        shape = CircleShape,
                        color = EduCoreColors.Gold50,
                        contentColor = EduCoreColors.Navy900,
                    ) {
                        Box(contentAlignment = Alignment.Center) {
                            Icon(Icons.Default.Assessment, contentDescription = null, modifier = Modifier.size(21.dp))
                        }
                    }
                    Column(Modifier.weight(1f)) {
                        Text(
                            assignment.subjectName,
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.SemiBold,
                            color = EduCoreColors.Ink900,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis,
                        )
                        Text(
                            assignment.className,
                            style = MaterialTheme.typography.bodySmall,
                            color = EduCoreColors.Slate600,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis,
                        )
                    }
                    EduCoreStatusBadge("Open", EduCoreTone.Accent)
                }
            }
        }

        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
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
    if (state.isLoading && state.sheet == null) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Opening score sheet")
    }
    val sheet = state.sheet ?: return EduCoreErrorState(
        state.errorMessage ?: "The score sheet is unavailable.",
        Modifier.fillMaxSize(),
        onRetry = onRetry,
    )
    val focusManager = LocalFocusManager.current
    var studentQuery by remember { mutableStateOf("") }
    val students = remember(sheet.students, studentQuery) {
        sheet.students.filter {
            studentQuery.isBlank() ||
                it.name.contains(studentQuery, true) ||
                it.admissionNumber.contains(studentQuery, true)
        }
    }

    Column(
        modifier = Modifier.fillMaxSize().imePadding().background(EduCoreColors.Page50),
    ) {
        LazyColumn(
            modifier = Modifier.weight(1f),
            contentPadding = PaddingValues(eduCoreScreenPadding()),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            item { ScoreHeader(sheet, onBack) }

            item {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                ) {
                    EduCoreShowcaseStat(
                        label = "Students",
                        value = sheet.students.size.toString(),
                        icon = Icons.Default.Groups,
                        tone = EduCoreTone.Brand,
                        modifier = Modifier.weight(1f),
                    )
                    EduCoreShowcaseStat(
                        label = "Assessments",
                        value = sheet.assessments.size.toString(),
                        icon = Icons.Default.Assessment,
                        tone = EduCoreTone.Accent,
                        modifier = Modifier.weight(1f),
                    )
                    EduCoreShowcaseStat(
                        label = "Status",
                        value = if (sheet.locked) "Locked" else "Open",
                        icon = if (sheet.locked) Icons.Default.Lock else Icons.Default.Save,
                        tone = if (sheet.locked) EduCoreTone.Warning else EduCoreTone.Success,
                        modifier = Modifier.weight(1f),
                    )
                }
            }

            if (sheet.locked) {
                item { EduCoreWarningBanner(sheet.lockReason ?: "This score sheet is locked.") }
            }
            if (sheet.isDraftStale) {
                item { EduCoreWarningBanner("Server scores changed. Discard this draft and reload.") }
            }
            if (sheet.syncState != SyncState.NONE) {
                item {
                    val message = sheet.syncMessage ?: "Score changes are waiting to synchronize."
                    if (sheet.syncState == SyncState.QUEUED || sheet.syncState == SyncState.SYNCING) {
                        EduCoreInfoBanner(message, title = "Sync pending")
                    } else {
                        EduCoreWarningBanner(message, title = "Sync needs attention")
                    }
                }
            }
            state.errorMessage?.let { item { EduCoreErrorBanner(it) } }

            item {
                EduCoreSectionHeader(
                    title = "Students",
                    supportingText = "Enter scores. Press Next/Enter to move to the next cell.",
                )
            }
            item {
                EduCoreSearchBar(
                    value = studentQuery,
                    onValueChange = { studentQuery = it },
                    placeholder = "Search students",
                )
            }

            if (students.isEmpty()) {
                item {
                    EduCoreShowcaseSectionCard {
                        EduCoreEmptyState(
                            if (sheet.students.isEmpty()) "No active students" else "No students found",
                            if (sheet.students.isEmpty()) {
                                "No active students are available for this class."
                            } else {
                                "Try another name or admission number."
                            },
                        )
                    }
                }
            }

            items(students, key = { it.id }) { student ->
                EduCoreShowcaseSectionCard {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Column(Modifier.weight(1f)) {
                            Text(
                                student.name,
                                style = MaterialTheme.typography.titleMedium,
                                fontWeight = FontWeight.SemiBold,
                                color = EduCoreColors.Ink900,
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis,
                            )
                            Text(
                                student.admissionNumber,
                                style = MaterialTheme.typography.bodySmall,
                                color = EduCoreColors.Slate600,
                            )
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
                                    modifier = Modifier.padding(EduCoreSpacing.Sm),
                                    horizontalAlignment = Alignment.CenterHorizontally,
                                    verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs),
                                ) {
                                    Text(
                                        assessment.name,
                                        style = MaterialTheme.typography.labelSmall,
                                        fontWeight = FontWeight.Medium,
                                        maxLines = 1,
                                        overflow = TextOverflow.Ellipsis,
                                    )
                                    Text(
                                        "/${if (assessment.isSplit) assessment.theoryMaximum ?: 0.0 else assessment.maximum}"
                                            .replace(".0", ""),
                                        style = MaterialTheme.typography.labelSmall,
                                        color = EduCoreColors.Muted500,
                                    )
                                    OutlinedTextField(
                                        value = cell?.value?.formatScore().orEmpty(),
                                        onValueChange = { onValue(student.id, assessment.id, it) },
                                        enabled = !sheet.locked &&
                                            cell?.locked != true &&
                                            sheet.syncState == SyncState.NONE,
                                        modifier = Modifier.width(70.dp),
                                        singleLine = true,
                                        keyboardOptions = KeyboardOptions(
                                            keyboardType = KeyboardType.Decimal,
                                            imeAction = ImeAction.Next,
                                        ),
                                        keyboardActions = KeyboardActions(
                                            onNext = {
                                                if (!focusManager.moveFocus(FocusDirection.Next)) {
                                                    focusManager.clearFocus()
                                                }
                                            },
                                        ),
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
                    ) {
                        Text("Discard", color = EduCoreColors.Slate700)
                    }
                    EduCorePrimaryButton(
                        text = if (sheet.syncState == SyncState.FAILED) "Retry sync" else "Save scores",
                        onClick = onSubmit,
                        modifier = Modifier.weight(1f),
                        enabled = !state.isSaving &&
                            !sheet.locked &&
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
    EduCoreShowcaseHero(
        eyebrow = "SCORE ENTRY",
        title = sheet.subjectName,
        subtitle = "${sheet.className} · ${sheet.termName}",
        actions = {
            androidx.compose.material3.TextButton(onClick = onBack) {
                Text("Back", color = EduCoreColors.White)
            }
        },
    )
}

@Composable
internal fun PublishedResultsScreen(
    state: ScoresUiState,
    onBack: () -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.publishedResults == null) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading published results")
    }
    val results = state.publishedResults ?: return EduCoreErrorState(
        state.errorMessage ?: "Published results are unavailable.",
        Modifier.fillMaxSize(),
        onRetry = onRetry,
    )

    LazyColumn(
        modifier = Modifier.fillMaxSize().imePadding().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Published results",
                subtitle = listOfNotNull(results.studentName, results.admissionNumber, results.className)
                    .joinToString(" · "),
                onBack = onBack,
            )
        }
        item {
            EduCoreShowcaseHero(
                eyebrow = "ACADEMIC REPORT",
                title = results.studentName.ifBlank { "Published report card" },
                subtitle = listOfNotNull(results.className, results.admissionNumber)
                    .filter(String::isNotBlank)
                    .joinToString(" · "),
                trailing = {
                    Surface(
                        modifier = Modifier.size(48.dp),
                        shape = CircleShape,
                        color = EduCoreColors.Gold100,
                        contentColor = EduCoreColors.Navy900,
                    ) {
                        Box(contentAlignment = Alignment.Center) {
                            Icon(Icons.Default.Assessment, contentDescription = null, modifier = Modifier.size(24.dp))
                        }
                    }
                },
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }

        if (results.results.isEmpty()) {
            item {
                EduCoreShowcaseSectionCard {
                    EduCoreEmptyState(
                        "No published results",
                        "No report card has been published for this account yet.",
                    )
                }
            }
        }

        items(results.results, key = PublishedResult::id) { result ->
            Card(
                modifier = Modifier.fillMaxWidth(),
                colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                border = BorderStroke(1.dp, EduCoreColors.Line200),
                shape = MaterialTheme.shapes.large,
            ) {
                Column(
                    modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
                    verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                ) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Column(Modifier.weight(1f)) {
                            Text(
                                result.term.orEmpty().ifBlank { "Published term" },
                                style = MaterialTheme.typography.titleLarge,
                                fontWeight = FontWeight.SemiBold,
                                color = EduCoreColors.Ink900,
                            )
                            Text(result.session.orEmpty(), color = EduCoreColors.Slate600)
                        }
                        EduCoreStatusBadge("${result.average.formatScore()}%", result.average.averageTone())
                    }

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    ) {
                        EduCoreShowcaseStat(
                            label = "Average",
                            value = "${result.average.formatScore()}%",
                            icon = Icons.Default.Assessment,
                            tone = result.average.averageTone(),
                            modifier = Modifier.weight(1f),
                        )
                        EduCoreShowcaseStat(
                            label = "Position",
                            value = result.position?.toString() ?: "—",
                            icon = Icons.Default.Groups,
                            tone = EduCoreTone.Brand,
                            modifier = Modifier.weight(1f),
                        )
                        EduCoreShowcaseStat(
                            label = "Class size",
                            value = result.classSize?.toString() ?: "—",
                            icon = Icons.Default.School,
                            tone = EduCoreTone.Info,
                            modifier = Modifier.weight(1f),
                        )
                    }

                    if (result.promotionStatus.isNotBlank()) {
                        EduCoreStatusBadge(
                            result.promotionStatus.replace('_', ' ').replaceFirstChar(Char::uppercase),
                            promotionTone(result.promotionStatus),
                        )
                    }

                    EduCoreSectionHeader(
                        title = "Subjects",
                        supportingText = "Published totals, grades and remarks",
                    )
                    result.subjects.forEach { subject ->
                        Surface(
                            modifier = Modifier.fillMaxWidth(),
                            color = EduCoreColors.Page50,
                            shape = MaterialTheme.shapes.medium,
                            border = BorderStroke(1.dp, EduCoreColors.Line200),
                        ) {
                            Row(
                                modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
                                verticalAlignment = Alignment.CenterVertically,
                            ) {
                                Column(Modifier.weight(1f)) {
                                    Text(
                                        subject.name,
                                        fontWeight = FontWeight.Medium,
                                        color = EduCoreColors.Ink900,
                                    )
                                    Text(
                                        "${subject.grade} · ${subject.remark}",
                                        color = EduCoreColors.Slate600,
                                        style = MaterialTheme.typography.bodySmall,
                                    )
                                }
                                Text(
                                    subject.total.formatScore(),
                                    fontWeight = FontWeight.SemiBold,
                                    color = EduCoreColors.Navy900,
                                )
                            }
                        }
                    }

                    result.formTutorRemark?.takeIf(String::isNotBlank)?.let {
                        Text("Form tutor: $it", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate700)
                    }
                    result.principalRemark?.takeIf(String::isNotBlank)?.let {
                        Text("Principal: $it", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate700)
                    }
                }
            }
        }

        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

private fun Double.formatScore(): String = if (this % 1.0 == 0.0) toInt().toString() else toString()

private fun Double.averageTone(): EduCoreTone = when {
    this >= 50.0 -> EduCoreTone.Success
    this >= 40.0 -> EduCoreTone.Warning
    else -> EduCoreTone.Danger
}

private fun promotionTone(status: String): EduCoreTone = when (status.lowercase()) {
    "promoted", "passed", "graduated" -> EduCoreTone.Success
    "repeated", "repeat", "failed", "not_promoted" -> EduCoreTone.Danger
    else -> EduCoreTone.Warning
}
