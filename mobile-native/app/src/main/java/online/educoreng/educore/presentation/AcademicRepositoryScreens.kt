package online.educoreng.educore.presentation

import android.content.ClipData
import android.content.Intent
import android.net.Uri
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
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Description
import androidx.compose.material.icons.filled.Download
import androidx.compose.material.icons.filled.Folder
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.core.text.HtmlCompat
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreExpandableSection
import online.educoreng.educore.core.designsystem.component.EduCoreFilterChip
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.component.EduCoreWarningBanner
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.DownloadedDocument
import online.educoreng.educore.core.model.RepositoryResource

private enum class RepositoryMode { RESOURCES, KNOWLEDGE, KNOWLEDGE_DETAIL, GENERATED }

@Composable
internal fun AcademicRepositoryScreen(
    state: AcademicContentUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onClass: (String?) -> Unit,
    onTerm: (String?) -> Unit,
    onSubject: (String?) -> Unit,
    onOpen: (Long) -> Unit,
    onLoadMore: () -> Unit,
    onRetry: () -> Unit,
    onDocumentOpened: () -> Unit,
) {
    @Suppress("UNUSED_VARIABLE")
    val legacyResourceOpenDisabled = onOpen
    val knowledgeViewModel: AcademicKnowledgeViewModel = hiltViewModel()
    val knowledgeState by knowledgeViewModel.uiState.collectAsStateWithLifecycle()
    var mode by remember { mutableStateOf(RepositoryMode.RESOURCES) }

    when (mode) {
        RepositoryMode.KNOWLEDGE -> {
            LaunchedEffect(Unit) {
                if (knowledgeState.catalogue == null && !knowledgeState.isLoading) knowledgeViewModel.loadTopics()
            }
            AcademicKnowledgeListScreen(
                state = knowledgeState,
                onBack = { mode = RepositoryMode.RESOURCES },
                onQuery = knowledgeViewModel::setQuery,
                onSearch = knowledgeViewModel::submitSearch,
                onReadyOnly = knowledgeViewModel::setReadyOnly,
                onOpen = { id ->
                    knowledgeViewModel.openTopic(id)
                    mode = RepositoryMode.KNOWLEDGE_DETAIL
                },
                onLoadMore = knowledgeViewModel::loadMore,
                onRetry = knowledgeViewModel::loadTopics,
            )
            return
        }
        RepositoryMode.KNOWLEDGE_DETAIL -> {
            AcademicKnowledgeDetailScreen(
                state = knowledgeState,
                onBack = { mode = RepositoryMode.KNOWLEDGE },
                onGenerateLessonPlan = { id ->
                    knowledgeViewModel.generate(id, "lesson-plan")
                    mode = RepositoryMode.GENERATED
                },
                onGenerateStudentNote = { id ->
                    knowledgeViewModel.generate(id, "student-note")
                    mode = RepositoryMode.GENERATED
                },
                onSaveLessonPlan = knowledgeViewModel::saveLessonPlan,
                onSaveStudentNote = knowledgeViewModel::saveStudentNote,
                onRetry = knowledgeViewModel::openTopic,
            )
            return
        }
        RepositoryMode.GENERATED -> {
            GeneratedKnowledgeScreen(
                state = knowledgeState,
                onBack = {
                    knowledgeViewModel.clearDocument()
                    mode = RepositoryMode.KNOWLEDGE_DETAIL
                },
                onSaveLessonPlan = knowledgeViewModel::saveLessonPlan,
                onSaveStudentNote = knowledgeViewModel::saveStudentNote,
            )
            return
        }
        RepositoryMode.RESOURCES -> Unit
    }

    OpenDocumentEffect(state.downloadedDocument, onDocumentOpened)
    if (state.isLoading && state.catalogue == null) return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading academic repository")
    val hierarchy = state.hierarchy ?: return EduCoreErrorState(
        message = state.errorMessage ?: "The repository is unavailable.",
        modifier = Modifier.fillMaxSize(),
        onRetry = onRetry,
    )
    val catalogue = state.catalogue
    val classGroup = hierarchy.classes.firstOrNull { it.name == state.selectedClass }
    val termGroup = classGroup?.terms?.firstOrNull { it.name == state.selectedTerm }

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { RepositoryHeader("Academic Repository", "Canonical curriculum sources for lesson plans and student notes", onBack) }
        item {
            Card(
                colors = CardDefaults.cardColors(containerColor = EduCoreColors.Gold100),
                border = BorderStroke(1.dp, EduCoreColors.Line200),
            ) {
                Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    Text("Canonical Knowledge", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold, color = EduCoreColors.Navy900)
                    Text("EduCore extracts and indexes repository content, then uses the mapped knowledge directly to create content-rich lesson plans and student notes without depending on AI.", style = MaterialTheme.typography.bodyMedium, color = EduCoreColors.Navy900)
                    EduCorePrimaryButton("Open Canonical Knowledge", { mode = RepositoryMode.KNOWLEDGE }, modifier = Modifier.fillMaxWidth())
                }
            }
        }
        if (hierarchy.isFromCache || catalogue?.isFromCache == true) item { EduCoreWarningBanner("Showing saved repository data while EduCore reconnects.") }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item {
            Row(Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                RepositoryMetric("Resources", hierarchy.metrics.resources.toString())
                RepositoryMetric("Classes", hierarchy.metrics.classes.toString())
                RepositoryMetric("Subjects", hierarchy.metrics.subjects.toString())
                RepositoryMetric("Sections", hierarchy.metrics.sections.toString())
            }
        }
        item {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                EduCoreSearchBar(state.query, onQuery, Modifier.weight(1f), "Search canonical sources")
                Spacer(Modifier.width(EduCoreSpacing.Sm))
                EduCorePrimaryButton("Search", onSearch)
            }
        }
        item { FilterLabel("Class") }
        item {
            Row(Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreFilterChip("All", state.selectedClass == null, { onClass(null) })
                hierarchy.classes.forEach { group -> EduCoreFilterChip("${group.name} (${group.resourceCount})", state.selectedClass == group.name, { onClass(group.name) }) }
            }
        }
        if (classGroup != null) {
            item { FilterLabel("Term") }
            item {
                Row(Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreFilterChip("All", state.selectedTerm == null, { onTerm(null) })
                    classGroup.terms.forEach { term -> EduCoreFilterChip("${term.name} (${term.resourceCount})", state.selectedTerm == term.name, { onTerm(term.name) }) }
                }
            }
        }
        if (termGroup != null) {
            item { FilterLabel("Subject") }
            item {
                Row(Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreFilterChip("All", state.selectedSubject == null, { onSubject(null) })
                    termGroup.subjects.forEach { subject -> EduCoreFilterChip("${subject.name} (${subject.resourceCount})", state.selectedSubject == subject.name, { onSubject(subject.name) }) }
                }
            }
        }
        item {
            Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                Text(listOfNotNull(state.selectedClass, state.selectedTerm, state.selectedSubject).ifEmpty { listOf("All canonical sources") }.joinToString(" · "), style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                Text("These files are source material for EduCore's indexed knowledge base. They are no longer opened as a separate read-only note screen.", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
        }
        val resources = catalogue?.resources.orEmpty()
        if (resources.isEmpty()) item { EduCoreEmptyState("No canonical sources found", "Choose another class, term or subject, or clear the search.") }
        items(resources, key = RepositoryResource::id) { resource -> RepositoryResourceCard(resource) }
        if (catalogue != null && catalogue.currentPage < catalogue.lastPage) item {
            EduCoreSecondaryButton(text = if (state.isLoadingMore) "Loading…" else "Load more (${resources.size} of ${catalogue.total})", onClick = onLoadMore, enabled = !state.isLoadingMore, modifier = Modifier.fillMaxWidth())
        }
        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

@Composable
internal fun AcademicResourceDetailScreen(
    state: AcademicContentUiState,
    onBack: () -> Unit,
    onDownload: () -> Unit,
    onRetry: () -> Unit,
    onDocumentOpened: () -> Unit,
) {
    OpenDocumentEffect(state.downloadedDocument, onDocumentOpened)
    if (state.isLoading && state.resource == null) return EduCoreLoadingState(Modifier.fillMaxSize(), "Opening resource")
    val detail = state.resource ?: return EduCoreErrorState(message = state.errorMessage ?: "This resource is unavailable.", modifier = Modifier.fillMaxSize(), onRetry = onRetry)

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { RepositoryHeader(detail.resource.title, "Academic Repository", onBack) }
        if (detail.isFromCache) item { EduCoreWarningBanner("Showing saved content while EduCore reconnects.") }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item {
            Row(Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreStatusBadge(detail.resource.subject, EduCoreTone.Brand)
                EduCoreStatusBadge(detail.resource.className, EduCoreTone.Accent)
                EduCoreStatusBadge(detail.resource.term, EduCoreTone.Neutral)
            }
        }
        if (detail.fragments.isEmpty()) item { EduCoreEmptyState("No readable sections", "Open the original resource to view it.") }
        items(detail.fragments, key = { it.id }) { fragment ->
            EduCoreExpandableSection(
                title = fragment.topic,
                supportingText = listOfNotNull(fragment.theme, fragment.subtopic).filter(String::isNotBlank).joinToString(" · "),
                initiallyExpanded = detail.fragments.firstOrNull()?.id == fragment.id,
            ) {
                Column(Modifier.fillMaxWidth().padding(start = EduCoreSpacing.Lg, end = EduCoreSpacing.Lg, bottom = EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                    AcademicNoteContent(fragment.content)
                    fragment.learningExpectation?.takeIf(String::isNotBlank)?.let { expectation ->
                        Surface(color = EduCoreColors.Gold100, shape = MaterialTheme.shapes.small) {
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md)) {
                                Text("Learning expectation", style = MaterialTheme.typography.labelLarge, fontWeight = FontWeight.SemiBold, color = EduCoreColors.Navy900)
                                Text(expectation, style = MaterialTheme.typography.bodyMedium.copy(lineHeight = 21.sp), color = EduCoreColors.Navy900)
                            }
                        }
                    }
                }
            }
        }
        item {
            Card(colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
                Row(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalAlignment = Alignment.CenterVertically) {
                    Surface(color = EduCoreColors.Gold100, shape = MaterialTheme.shapes.medium) { Icon(Icons.Default.Description, null, Modifier.padding(EduCoreSpacing.Md), tint = EduCoreColors.Navy900) }
                    Spacer(Modifier.width(EduCoreSpacing.Md))
                    Column(Modifier.weight(1f)) {
                        Text(detail.resource.filename ?: detail.resource.title, style = MaterialTheme.typography.titleSmall)
                        Text("Original resource", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                    }
                    EduCorePrimaryButton(text = "Open", onClick = onDownload, loading = state.isSaving, leadingIcon = { Icon(Icons.Default.Download, null) })
                }
            }
        }
    }
}

@Composable
private fun AcademicNoteContent(content: String) {
    val blocks = remember(content) { parseAcademicNote(content) }
    if (blocks.isEmpty()) {
        Text("No readable note text is available in this section.", style = MaterialTheme.typography.bodyMedium, color = EduCoreColors.Slate600)
        return
    }

    val bodyStyle = MaterialTheme.typography.bodyLarge.copy(lineHeight = 23.sp)
    Column(Modifier.fillMaxWidth(), verticalArrangement = Arrangement.spacedBy(9.dp)) {
        blocks.forEach { block ->
            when (block) {
                is AcademicNoteBlock.Heading -> Text(block.text, Modifier.padding(top = EduCoreSpacing.Sm), style = MaterialTheme.typography.titleMedium.copy(lineHeight = 23.sp), fontWeight = FontWeight.SemiBold, color = EduCoreColors.Navy900)
                is AcademicNoteBlock.Paragraph -> Text(block.text, style = bodyStyle, color = EduCoreColors.Ink900)
                is AcademicNoteBlock.Bullet -> Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
                    Text("•", Modifier.width(22.dp), style = bodyStyle, fontWeight = FontWeight.Medium, color = EduCoreColors.Gold700)
                    Text(block.text, Modifier.weight(1f), style = bodyStyle, color = EduCoreColors.Ink900)
                }
                is AcademicNoteBlock.Numbered -> Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
                    Text(block.marker, Modifier.width(38.dp), style = bodyStyle, fontWeight = FontWeight.Medium, color = EduCoreColors.Gold700)
                    Text(block.text, Modifier.weight(1f), style = bodyStyle, color = EduCoreColors.Ink900)
                }
            }
        }
    }
}

private sealed class AcademicNoteBlock {
    data class Heading(val text: String) : AcademicNoteBlock()
    data class Paragraph(val text: String) : AcademicNoteBlock()
    data class Bullet(val text: String) : AcademicNoteBlock()
    data class Numbered(val marker: String, val text: String) : AcademicNoteBlock()
}

private val academicHtmlTag = Regex("</?[A-Za-z][^>]*>")
private val academicMarkdownHeading = Regex("^#{1,6}\\s+(.+)$")
private val academicNumberedPoint = Regex("^((?:\\d+(?:\\.\\d+)*)|[A-Za-z])[.)]\\s+(.+)$")
private val academicBulletPrefixes = listOf("• ", "● ", "◦ ", "▪ ", "‣ ", "- ", "* ", "– ", "— ")
private val academicStructuralHeading = Regex("(?<!^)(?=\\b(?:SUBTOPICS?|INTRODUCTION|MEANING OF|DEFINITION OF|DEFINITIONS OF|CAUSES OF|EFFECTS OF|TYPES OF|FEATURES OF|CHARACTERISTICS OF|IMPORTANCE OF|FUNCTIONS OF|ADVANTAGES OF|DISADVANTAGES OF|APPLICATIONS OF|SUMMARY|CONCLUSION|REVISION|EVALUATION)\\b)")
private val academicHeadingLabels = setOf(
    "subtopic", "subtopics", "introduction", "meaning", "definition", "definitions", "overview",
    "objectives", "learning objectives", "learning outcomes", "features", "characteristics", "types",
    "classification", "importance", "functions", "examples", "causes", "effects", "advantages",
    "disadvantages", "applications", "summary", "conclusion", "revision", "evaluation", "weather vs. climate",
)

private fun parseAcademicNote(raw: String): List<AcademicNoteBlock> {
    val text = normaliseAcademicNote(raw)
    if (text.isBlank()) return emptyList()
    val blocks = mutableListOf<AcademicNoteBlock>()
    val paragraph = mutableListOf<String>()
    fun flushParagraph() {
        if (paragraph.isNotEmpty()) {
            blocks += AcademicNoteBlock.Paragraph(paragraph.joinToString(" ").normaliseInlineSpacing())
            paragraph.clear()
        }
    }
    text.lineSequence().forEach { sourceLine ->
        val line = sourceLine.trim()
        if (line.isBlank()) { flushParagraph(); return@forEach }
        academicMarkdownHeading.matchEntire(line)?.let { match ->
            flushParagraph(); blocks += AcademicNoteBlock.Heading(match.groupValues[1].trim().trimEnd(':')); return@forEach
        }
        academicBulletPrefixes.firstOrNull(line::startsWith)?.let { prefix ->
            flushParagraph(); line.removePrefix(prefix).trim().takeIf(String::isNotBlank)?.let { blocks += AcademicNoteBlock.Bullet(it.normaliseInlineSpacing()) }; return@forEach
        }
        academicNumberedPoint.matchEntire(line)?.let { match ->
            flushParagraph(); blocks += AcademicNoteBlock.Numbered("${match.groupValues[1]}.", match.groupValues[2].trim().normaliseInlineSpacing()); return@forEach
        }
        if (isAcademicHeading(line)) {
            flushParagraph(); blocks += AcademicNoteBlock.Heading(line.trim().trimEnd(':').normaliseInlineSpacing()); return@forEach
        }
        paragraph += line
    }
    flushParagraph()
    return blocks
}

private fun normaliseAcademicNote(raw: String): String {
    val source = raw.replace("\r\n", "\n").replace('\r', '\n').replace('\u00A0', ' ')
    val readable = if (academicHtmlTag.containsMatchIn(source)) {
        val prepared = source
            .replace(Regex("(?i)<h[1-6][^>]*>"), "# ")
            .replace(Regex("(?i)</h[1-6]>"), "<br/>")
            .replace(Regex("(?i)<li[^>]*>"), "• ")
            .replace(Regex("(?i)</li>"), "<br/>")
        HtmlCompat.fromHtml(prepared, HtmlCompat.FROM_HTML_MODE_LEGACY).toString()
    } else source
    return readable
        .replace('\u00A0', ' ')
        .replace(Regex("[•●◦▪‣]+\\s*"), "\n• ")
        .replace(academicStructuralHeading, "\n")
        .replace(Regex("[ \\t]+\\n"), "\n")
        .replace(Regex("\\n[ \\t]+"), "\n")
        .replace(Regex("\\n{3,}"), "\n\n")
        .trim()
}

private fun isAcademicHeading(line: String): Boolean {
    val candidate = line.trim().trimEnd(':').normaliseInlineSpacing()
    if (candidate.length !in 2..90) return false
    val lower = candidate.lowercase()
    if (lower in academicHeadingLabels) return true
    val letters = candidate.filter(Char::isLetter)
    if (letters.length >= 4 && letters.all(Char::isUpperCase)) return true
    return line.endsWith(':') && candidate.split(Regex("\\s+")).size <= 8
}

private fun String.normaliseInlineSpacing(): String = trim().replace(Regex("\\s+"), " ")

@Composable
internal fun RepositoryHeader(title: String, subtitle: String, onBack: () -> Unit) {
    EduCorePageHeader(title = title, subtitle = subtitle, onBack = onBack)
}

@Composable
private fun RepositoryMetric(label: String, value: String) {
    Card(Modifier.width(118.dp), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
        Column(Modifier.padding(EduCoreSpacing.Md)) {
            Text(value, style = MaterialTheme.typography.titleLarge, color = EduCoreColors.Navy900, fontWeight = FontWeight.SemiBold)
            Text(label, color = EduCoreColors.Slate600, style = MaterialTheme.typography.bodySmall)
        }
    }
}

@Composable
private fun FilterLabel(value: String) = Text(value, style = MaterialTheme.typography.labelMedium, fontWeight = FontWeight.Medium, color = EduCoreColors.Slate600)

@Composable
private fun RepositoryResourceCard(resource: RepositoryResource) {
    Card(colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
        Row(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalAlignment = Alignment.CenterVertically) {
            Surface(color = EduCoreColors.Gold100, shape = MaterialTheme.shapes.medium) { Icon(Icons.Default.Description, null, Modifier.padding(EduCoreSpacing.Md), tint = EduCoreColors.Navy900) }
            Spacer(Modifier.width(EduCoreSpacing.Md))
            Column(Modifier.weight(1f)) {
                Text(resource.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Medium)
                Text("${resource.className} · ${resource.term} · ${resource.subject}", color = EduCoreColors.Slate600, style = MaterialTheme.typography.bodySmall)
                Text("Canonical source · ${resource.fragmentsCount} indexed sections", color = EduCoreColors.Gold700, style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.Medium)
                resource.filename?.takeIf(String::isNotBlank)?.let { filename ->
                    Text(filename, color = EduCoreColors.Muted500, style = MaterialTheme.typography.bodySmall, maxLines = 1, overflow = TextOverflow.Ellipsis)
                }
            }
            Icon(Icons.Default.Folder, null, tint = EduCoreColors.Gold600)
        }
    }
}

@Composable
internal fun OpenDocumentEffect(document: DownloadedDocument?, onOpened: () -> Unit) {
    val context = LocalContext.current
    LaunchedEffect(document) {
        document ?: return@LaunchedEffect
        runCatching {
            val uri = Uri.parse(document.uri)
            val viewIntent = Intent(Intent.ACTION_VIEW).apply {
                setDataAndType(uri, document.mimeType)
                clipData = ClipData.newUri(context.contentResolver, document.filename, uri)
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION or Intent.FLAG_ACTIVITY_NEW_TASK)
            }
            context.startActivity(Intent.createChooser(viewIntent, "Open ${document.filename}").addFlags(Intent.FLAG_ACTIVITY_NEW_TASK))
        }.recoverCatching {
            val uri = Uri.parse(document.uri)
            val sendIntent = Intent(Intent.ACTION_SEND).apply {
                type = document.mimeType
                putExtra(Intent.EXTRA_STREAM, uri)
                clipData = ClipData.newUri(context.contentResolver, document.filename, uri)
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION or Intent.FLAG_ACTIVITY_NEW_TASK)
            }
            context.startActivity(Intent.createChooser(sendIntent, "Share ${document.filename}").addFlags(Intent.FLAG_ACTIVITY_NEW_TASK))
        }
        onOpened()
    }
}
