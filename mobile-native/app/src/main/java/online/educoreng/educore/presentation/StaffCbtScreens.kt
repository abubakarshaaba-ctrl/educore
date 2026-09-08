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
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Assignment
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreFilterChip
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseSectionCard
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.StaffCbtExamDto

@Composable
internal fun StaffCbtListScreen(
    state: StaffCbtUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onStatus: (String?) -> Unit,
    onOpen: (Long) -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.exams.isEmpty()) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Loading CBT examinations")
        return
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize().imePadding(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader("CBT Management", "Examinations available to your role", onBack) }
        item {
            EduCoreShowcaseHero(
                eyebrow = "COMPUTER-BASED TESTING",
                title = "Prepare, publish and monitor examinations.",
                subtitle = if (state.capabilities.fullAccess) {
                    "Full CBT management access"
                } else {
                    "Only subjects and classes assigned to you are shown"
                },
                trailing = {
                    Surface(color = EduCoreColors.Gold100, shape = MaterialTheme.shapes.large) {
                        Icon(Icons.Default.Assignment, null, Modifier.padding(EduCoreSpacing.Md), tint = EduCoreColors.Navy900)
                    }
                },
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item {
            Row(
                Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                CbtMetric("All", state.counts.all, EduCoreTone.Brand)
                CbtMetric("Draft", state.counts.draft, EduCoreTone.Neutral)
                CbtMetric("Published", state.counts.published, EduCoreTone.Success)
                CbtMetric("Closed", state.counts.closed, EduCoreTone.Danger)
            }
        }
        item {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                EduCoreSearchBar(state.query, onQuery, Modifier.weight(1f), "Search exam, subject or class")
                Spacer(Modifier.width(EduCoreSpacing.Sm))
                EduCorePrimaryButton("Search", onSearch)
            }
        }
        item {
            Row(
                Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                listOf(null to "All", "draft" to "Draft", "published" to "Published", "active" to "Active", "closed" to "Closed")
                    .forEach { (value, label) ->
                        EduCoreFilterChip(label, state.status == value, { onStatus(value) })
                    }
            }
        }
        if (state.exams.isEmpty()) {
            item {
                EduCoreEmptyState(
                    "No CBT examinations",
                    "No examination matches this filter, or none has been assigned to your permitted subjects/classes.",
                )
            }
        } else {
            items(state.exams, key = StaffCbtExamDto::id) { exam ->
                StaffCbtExamCard(exam) { onOpen(exam.id) }
            }
        }
        if (state.errorMessage != null && state.exams.isEmpty()) {
            item { EduCoreSecondaryButton("Retry", onRetry, Modifier.fillMaxWidth()) }
        }
        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

@Composable
internal fun StaffCbtDetailScreen(
    state: StaffCbtUiState,
    onBack: () -> Unit,
    onPublish: () -> Unit,
    onClose: () -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.selectedExam == null) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Opening CBT examination")
        return
    }
    val exam = state.selectedExam ?: run {
        EduCoreErrorState(state.errorMessage ?: "This CBT examination is unavailable.", Modifier.fillMaxSize(), onRetry = onRetry)
        return
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize().imePadding(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader(exam.title, "CBT Management", onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreStatusBadge(exam.status.replaceFirstChar { it.uppercase() }, exam.status.cbtTone())
                exam.subject?.let { EduCoreStatusBadge(it.name, EduCoreTone.Brand) }
            }
        }
        item {
            EduCoreShowcaseSectionCard {
                Text("Exam overview", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = EduCoreColors.Navy900)
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                CbtLine("Class level", exam.classLevel?.name ?: "Not specified")
                CbtLine("Classes", exam.classes.joinToString { it.name }.ifBlank { "Not specified" })
                CbtLine("Term", listOfNotNull(exam.term?.name, exam.term?.session).joinToString(" · ").ifBlank { "Not specified" })
                CbtLine("Questions", exam.totalQuestions.toString())
                CbtLine("Total marks", exam.totalMarks.toString())
                CbtLine("Duration", "${exam.durationMinutes} minutes")
                CbtLine("Starts", exam.scheduledStart ?: "Not scheduled")
                CbtLine("Ends", exam.scheduledEnd ?: "Not scheduled")
            }
        }
        exam.attempts?.let { attempts ->
            item {
                EduCoreShowcaseSectionCard {
                    Text("Student attempts", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = EduCoreColors.Navy900)
                    Spacer(Modifier.height(EduCoreSpacing.Sm))
                    CbtLine("Started", attempts.total.toString())
                    CbtLine("Submitted", attempts.submitted.toString())
                    CbtLine("Graded", attempts.graded.toString())
                }
            }
        }
        if (exam.sections.isNotEmpty()) {
            item {
                EduCoreShowcaseSectionCard {
                    Text("Sections", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = EduCoreColors.Navy900)
                    exam.sections.sortedBy { it.displayOrder }.forEachIndexed { index, section ->
                        Text("${index + 1}. ${section.title}", style = MaterialTheme.typography.bodyLarge, color = EduCoreColors.Ink900)
                    }
                }
            }
        }
        item {
            EduCoreShowcaseSectionCard {
                Text("Permitted actions", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = EduCoreColors.Navy900)
                Spacer(Modifier.height(EduCoreSpacing.Md))
                if (exam.canPublish) {
                    EduCorePrimaryButton(
                        "Publish exam",
                        onPublish,
                        Modifier.fillMaxWidth(),
                        enabled = !state.isSaving,
                        loading = state.isSaving,
                        leadingIcon = { Icon(Icons.Default.CheckCircle, null) },
                    )
                }
                if (exam.canClose) {
                    EduCorePrimaryButton(
                        "Close exam",
                        onClose,
                        Modifier.fillMaxWidth(),
                        enabled = !state.isSaving,
                        loading = state.isSaving,
                        leadingIcon = { Icon(Icons.Default.Lock, null) },
                    )
                }
                if (exam.canReschedule) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.Schedule, null, tint = EduCoreColors.Gold700)
                        Spacer(Modifier.width(EduCoreSpacing.Sm))
                        Text(
                            "Rescheduling is permitted for this exam. The native date/time editor will be added in the next CBT pass.",
                            style = MaterialTheme.typography.bodySmall,
                            color = EduCoreColors.Slate600,
                        )
                    }
                }
                if (!exam.canPublish && !exam.canClose && !exam.canReschedule) {
                    Text("No state-changing action is currently permitted for this exam.", color = EduCoreColors.Slate600)
                }
            }
        }
        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

@Composable
private fun StaffCbtExamCard(exam: StaffCbtExamDto, onClick: () -> Unit) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
        shape = MaterialTheme.shapes.large,
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f)) {
                    Text(exam.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold, maxLines = 2, overflow = TextOverflow.Ellipsis)
                    Text(
                        listOfNotNull(exam.subject?.name, exam.classLevel?.name).joinToString(" · ").ifBlank { "CBT examination" },
                        style = MaterialTheme.typography.bodySmall,
                        color = EduCoreColors.Slate600,
                    )
                }
                EduCoreStatusBadge(exam.status.replaceFirstChar { it.uppercase() }, exam.status.cbtTone())
            }
            Text(
                "${exam.totalQuestions} questions · ${exam.durationMinutes} min · ${exam.attemptsCount} attempts",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
        }
    }
}

@Composable
private fun CbtMetric(label: String, value: Int, tone: EduCoreTone) {
    Surface(color = EduCoreColors.White, shape = MaterialTheme.shapes.large, border = BorderStroke(1.dp, EduCoreColors.Line200)) {
        Column(Modifier.width(104.dp).padding(EduCoreSpacing.Md)) {
            Text(value.toString(), style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold, color = EduCoreColors.Navy900)
            EduCoreStatusBadge(label, tone)
        }
    }
}

@Composable
private fun CbtLine(label: String, value: String) {
    Row(Modifier.fillMaxWidth().padding(vertical = EduCoreSpacing.Xs)) {
        Text(label, Modifier.weight(1f), style = MaterialTheme.typography.bodyMedium, color = EduCoreColors.Slate600)
        Text(value, Modifier.weight(1.4f), style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.SemiBold, color = EduCoreColors.Ink900)
    }
}

private fun String.cbtTone(): EduCoreTone = when (lowercase()) {
    "published", "active" -> EduCoreTone.Success
    "closed" -> EduCoreTone.Danger
    "draft" -> EduCoreTone.Neutral
    else -> EduCoreTone.Warning
}
