package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreFilterChip
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.GeneratedKnowledgeDocument
import online.educoreng.educore.core.model.KnowledgeTopic

@Composable
internal fun AcademicKnowledgeListScreen(
    state: AcademicKnowledgeUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onReadyOnly: (Boolean) -> Unit,
    onOpen: (Long) -> Unit,
    onLoadMore: () -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.catalogue == null) return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading curriculum knowledge")
    val catalogue = state.catalogue ?: return EduCoreErrorState(
        message = state.errorMessage ?: "Curriculum Knowledge is unavailable.",
        modifier = Modifier.fillMaxSize(),
        onRetry = onRetry,
    )

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { RepositoryHeader("Curriculum Knowledge", "Non-AI lesson plans and student notes", onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                EduCoreSearchBar(state.query, onQuery, Modifier.weight(1f), "Search topics")
                Spacer(Modifier.padding(EduCoreSpacing.Xs))
                EduCorePrimaryButton("Search", onSearch)
            }
        }
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreFilterChip("All topics", !state.readyOnly, { onReadyOnly(false) })
                EduCoreFilterChip("Generation-ready", state.readyOnly, { onReadyOnly(true) })
            }
        }
        if (catalogue.topics.isEmpty()) item {
            EduCoreEmptyState("No knowledge topics found", "Try another search or show all topics.")
        }
        items(catalogue.topics, key = KnowledgeTopic::id) { topic ->
            KnowledgeTopicCard(topic) { onOpen(topic.id) }
        }
        if (catalogue.currentPage < catalogue.lastPage) item {
            EduCoreSecondaryButton(
                text = if (state.isLoadingMore) "Loading…" else "Load more (${catalogue.topics.size} of ${catalogue.total})",
                onClick = onLoadMore,
                enabled = !state.isLoadingMore,
                modifier = Modifier.fillMaxWidth(),
            )
        }
        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

@Composable
internal fun AcademicKnowledgeDetailScreen(
    state: AcademicKnowledgeUiState,
    onBack: () -> Unit,
    onGenerateLessonPlan: (Long) -> Unit,
    onGenerateStudentNote: (Long) -> Unit,
    onSaveLessonPlan: (Long) -> Unit,
    onSaveStudentNote: (Long) -> Unit,
    onRetry: (Long) -> Unit,
) {
    if (state.isLoading && state.topic == null) return EduCoreLoadingState(Modifier.fillMaxSize(), "Opening curriculum topic")
    val topic = state.topic ?: return EduCoreErrorState(
        message = state.errorMessage ?: "This curriculum topic is unavailable.",
        modifier = Modifier.fillMaxSize(),
        onRetry = { },
    )

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { RepositoryHeader(topic.topic, listOf(topic.className, topic.subject, topic.term).joinToString(" · "), onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item { ReadinessCard(topic) }

        if (topic.readiness.critical.isNotEmpty()) item {
            InfoCard("Quality checks", topic.readiness.critical.joinToString("\n") { "• $it" })
        }
        if (topic.readiness.missing.isNotEmpty()) item {
            InfoCard("Missing content", topic.readiness.missing.joinToString("\n") { "• $it" })
        }
        topic.consolidation?.let { consolidation ->
            item {
                InfoCard(
                    "Multi-source consolidation",
                    "${consolidation.sourceCount} repository source(s) · ${consolidation.topicCount} related topic record(s) · ${consolidation.usableSourceCount} usable source(s)",
                )
            }
        }
        if (topic.sources.isNotEmpty()) item {
            Card(
                colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                border = BorderStroke(1.dp, EduCoreColors.Line200),
            ) {
                Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    Text("Ranked repository sources", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    topic.sources.forEach { source ->
                        Text(
                            listOfNotNull(source.title ?: source.filename, source.resourceType?.replace('_', ' '), source.priority?.let { "priority $it" }).joinToString(" · "),
                            style = MaterialTheme.typography.bodyMedium,
                        )
                    }
                }
            }
        }
        topic.fields?.let { fields ->
            val fieldPairs = listOf(
                "Entry Behaviour" to fields.entryBehaviour,
                "Previous / Background Knowledge" to fields.previousKnowledge,
                "Instructional Resources" to fields.instructionalResources,
                "Introduction" to fields.introduction,
                "Student Note Summary" to fields.studentNoteSummary,
                "Reference" to fields.reference,
            ).filter { !it.second.isNullOrBlank() }
            if (fieldPairs.isNotEmpty()) item {
                Card(colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
                    Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                        fieldPairs.forEach { (label, value) ->
                            Text(label, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
                            Text(value.orEmpty(), style = MaterialTheme.typography.bodyMedium)
                        }
                    }
                }
            }
        }
        if (topic.blocks.isNotEmpty()) {
            items(topic.blocks, key = { "${it.type}:${it.sequence}:${it.content.hashCode()}" }) { block ->
                InfoCard(block.title ?: block.type.replace('_', ' ').replaceFirstChar(Char::uppercase), block.content)
            }
        }
        item {
            Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCorePrimaryButton("Preview Lesson Plan", { onGenerateLessonPlan(topic.id) }, enabled = topic.readiness.ready, modifier = Modifier.fillMaxWidth())
                EduCoreSecondaryButton("Preview Student Note", { onGenerateStudentNote(topic.id) }, enabled = topic.readiness.ready, modifier = Modifier.fillMaxWidth())
                EduCorePrimaryButton("Save to Lesson Planner", { onSaveLessonPlan(topic.id) }, enabled = topic.readiness.ready && !state.isSaving, modifier = Modifier.fillMaxWidth())
                EduCoreSecondaryButton("Save Student Note", { onSaveStudentNote(topic.id) }, enabled = topic.readiness.ready && !state.isSaving, modifier = Modifier.fillMaxWidth())
            }
        }
        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

@Composable
internal fun GeneratedKnowledgeScreen(
    state: AcademicKnowledgeUiState,
    onBack: () -> Unit,
    onSaveLessonPlan: (Long) -> Unit,
    onSaveStudentNote: (Long) -> Unit,
) {
    if (state.isLoading && state.document == null) return EduCoreLoadingState(Modifier.fillMaxSize(), "Generating academic content")
    val document = state.document ?: return EduCoreErrorState(
        message = state.errorMessage ?: "Generated content is unavailable.",
        modifier = Modifier.fillMaxSize(),
        onRetry = null,
    )
    GeneratedDocumentContent(document, state, onBack, onSaveLessonPlan, onSaveStudentNote)
}

@Composable
private fun GeneratedDocumentContent(
    document: GeneratedKnowledgeDocument,
    state: AcademicKnowledgeUiState,
    onBack: () -> Unit,
    onSaveLessonPlan: (Long) -> Unit,
    onSaveStudentNote: (Long) -> Unit,
) {
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { RepositoryHeader(document.title, if (document.type == "lesson-plan") "Standard Lesson Plan" else "Student Note", onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item { ReadinessCard(document.topic) }
        items(document.sections) { (heading, content) -> InfoCard(heading, content) }
        item {
            if (document.type == "lesson-plan") {
                EduCorePrimaryButton("Save to Lesson Planner", { onSaveLessonPlan(document.topic.id) }, enabled = !state.isSaving, modifier = Modifier.fillMaxWidth())
            } else {
                EduCorePrimaryButton("Save Student Note", { onSaveStudentNote(document.topic.id) }, enabled = !state.isSaving, modifier = Modifier.fillMaxWidth())
            }
        }
        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

@Composable
private fun KnowledgeTopicCard(topic: KnowledgeTopic, onClick: () -> Unit) {
    Card(
        onClick = onClick,
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                EduCoreStatusBadge(if (topic.readiness.ready) "Ready" else "Needs content", if (topic.readiness.ready) EduCoreTone.Success else EduCoreTone.Warning)
                Text("${topic.readiness.score}%", fontWeight = FontWeight.Bold, color = EduCoreColors.Navy900)
            }
            Text(topic.topic, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
            topic.subTopic?.takeIf(String::isNotBlank)?.let { Text(it, style = MaterialTheme.typography.bodyMedium, color = EduCoreColors.Slate600) }
            Text(listOfNotNull(topic.className, topic.subject, topic.term, topic.week?.let { "Week $it" }).joinToString(" · "), style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
        }
    }
}

@Composable
private fun ReadinessCard(topic: KnowledgeTopic) {
    Surface(color = EduCoreColors.Gold100, shape = MaterialTheme.shapes.large) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
            Text("Generation readiness", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold, color = EduCoreColors.Navy900)
            Text("${topic.readiness.score}% overall · ${topic.readiness.coverageScore}% coverage${topic.readiness.qualityScore?.let { " · $it% quality" }.orEmpty()}", color = EduCoreColors.Navy900)
            Text(if (topic.readiness.ready) "Ready for deterministic generation" else "Complete the listed content and quality requirements before generation.", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Navy900)
        }
    }
}

@Composable
private fun InfoCard(title: String, body: String) {
    Card(colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Text(title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
            Text(body, style = MaterialTheme.typography.bodyMedium)
        }
    }
}
