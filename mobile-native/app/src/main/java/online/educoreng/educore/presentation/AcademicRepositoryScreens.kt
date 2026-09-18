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
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Description
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
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreFilterChip
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreWarningBanner
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.DownloadedDocument
import online.educoreng.educore.core.model.RepositoryResource

private enum class RepositoryMode { SOURCES, KNOWLEDGE, KNOWLEDGE_DETAIL, GENERATED }

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
    val retiredResourceReader = onOpen
    @Suppress("UNUSED_VARIABLE")
    val retiredDocumentOpenCallback = onDocumentOpened

    val knowledgeViewModel: AcademicKnowledgeViewModel = hiltViewModel()
    val knowledgeState by knowledgeViewModel.uiState.collectAsStateWithLifecycle()
    var mode by remember { mutableStateOf(RepositoryMode.KNOWLEDGE) }

    when (mode) {
        RepositoryMode.KNOWLEDGE -> {
            LaunchedEffect(Unit) {
                if (knowledgeState.catalogue == null && !knowledgeState.isLoading) knowledgeViewModel.loadTopics()
            }
            AcademicKnowledgeListScreen(
                state = knowledgeState,
                onBack = onBack,
                onOpenSources = {
                    if (state.hierarchy == null && !state.isLoading) onRetry()
                    mode = RepositoryMode.SOURCES
                },
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
        RepositoryMode.SOURCES -> Unit
    }

    if (state.isLoading && state.catalogue == null) return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading canonical sources")
    val hierarchy = state.hierarchy ?: return EduCoreErrorState(
        message = state.errorMessage ?: "Canonical sources are unavailable.",
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
        item { RepositoryHeader("Canonical Sources", "Evidence layer for EduCore Academic Knowledge", { mode = RepositoryMode.KNOWLEDGE }) }
        item {
            Card(
                colors = CardDefaults.cardColors(containerColor = EduCoreColors.Gold100),
                border = BorderStroke(1.dp, EduCoreColors.Line200),
            ) {
                Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    Text("Sources are evidence, not the teaching interface", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold, color = EduCoreColors.Navy900)
                    Text("EduCore extracts, cleans, fragments and indexes approved files. Teachers work from consolidated topic knowledge; original files remain available for audit and provenance.", style = MaterialTheme.typography.bodyMedium, color = EduCoreColors.Navy900)
                    EduCorePrimaryButton("Back to Academic Knowledge", { mode = RepositoryMode.KNOWLEDGE }, modifier = Modifier.fillMaxWidth())
                }
            }
        }
        if (hierarchy.isFromCache || catalogue?.isFromCache == true) item { EduCoreWarningBanner("Showing saved source metadata while EduCore reconnects.") }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item {
            Row(Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                RepositoryMetric("Sources", hierarchy.metrics.resources.toString())
                RepositoryMetric("Classes", hierarchy.metrics.classes.toString())
                RepositoryMetric("Subjects", hierarchy.metrics.subjects.toString())
                RepositoryMetric("Indexed sections", hierarchy.metrics.sections.toString())
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
            Text(listOfNotNull(state.selectedClass, state.selectedTerm, state.selectedSubject).ifEmpty { listOf("All canonical sources") }.joinToString(" · "), style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
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

/**
 * Compatibility target for stale deep links from older APKs. The old read-only
 * source reader is intentionally retired; users return to Academic Knowledge or
 * open the original evidence file when it is available.
 */
@Composable
internal fun AcademicResourceDetailScreen(
    state: AcademicContentUiState,
    onBack: () -> Unit,
    onDownload: () -> Unit,
    onRetry: () -> Unit,
    onDocumentOpened: () -> Unit,
) {
    OpenDocumentEffect(state.downloadedDocument, onDocumentOpened)
    val detail = state.resource
    if (state.isLoading && detail == null) return EduCoreLoadingState(Modifier.fillMaxSize(), "Checking canonical source")
    if (detail == null) return EduCoreErrorState(
        message = state.errorMessage ?: "This legacy source link is no longer used. Return to Academic Knowledge.",
        modifier = Modifier.fillMaxSize(),
        onRetry = onRetry,
    )

    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { RepositoryHeader("Canonical Source", detail.resource.title, onBack) }
        item {
            Card(colors = CardDefaults.cardColors(containerColor = EduCoreColors.Gold100), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
                Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    Text("Read-only note view retired", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold, color = EduCoreColors.Navy900)
                    Text("This material is indexed into Academic Knowledge and used as canonical evidence for deterministic lesson-plan and student-note generation.", style = MaterialTheme.typography.bodyMedium, color = EduCoreColors.Navy900)
                    detail.resource.filename?.takeIf(String::isNotBlank)?.let {
                        EduCoreSecondaryButton(
                            text = if (state.isSaving) "Opening…" else "Open original evidence file",
                            onClick = onDownload,
                            enabled = !state.isSaving,
                            modifier = Modifier.fillMaxWidth(),
                        )
                    }
                }
            }
        }
    }
}

@Composable
internal fun RepositoryHeader(title: String, subtitle: String, onBack: () -> Unit) {
    EduCorePageHeader(title = title, subtitle = subtitle, onBack = onBack)
}

@Composable
private fun RepositoryMetric(label: String, value: String) {
    Card(Modifier.widthIn(min = 132.dp, max = 180.dp), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
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
            Surface(color = EduCoreColors.Gold100, shape = MaterialTheme.shapes.medium) {
                Icon(Icons.Default.Description, null, Modifier.padding(EduCoreSpacing.Md), tint = EduCoreColors.Navy900)
            }
            Spacer(Modifier.width(EduCoreSpacing.Md))
            Column(Modifier.weight(1f)) {
                Text(resource.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Medium)
                Text("${resource.className} · ${resource.term} · ${resource.subject}", color = EduCoreColors.Slate600, style = MaterialTheme.typography.bodySmall)
                Text("Canonical evidence · ${resource.fragmentsCount} indexed sections", color = EduCoreColors.Gold700, style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.Medium)
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
