package online.educoreng.educore.presentation

import android.content.Intent
import android.content.ClipData
import android.net.Uri
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.rememberScrollState
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
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Description
import androidx.compose.material.icons.filled.Download
import androidx.compose.material.icons.filled.Folder
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreFilterChip
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreWarningBanner
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.model.DownloadedDocument
import online.educoreng.educore.core.model.RepositoryResource

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
        Modifier.fillMaxSize().imePadding(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { RepositoryHeader("Academic Repository", "Class · term · subject", onBack) }
        if (hierarchy.isFromCache || catalogue?.isFromCache == true) item { EduCoreWarningBanner("Showing repository information saved on this device.") }
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
                EduCoreSearchBar(state.query, onQuery, Modifier.weight(1f), "Search prepared notes")
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
            Text(
                listOfNotNull(state.selectedClass, state.selectedTerm, state.selectedSubject).ifEmpty { listOf("All resources") }.joinToString(" · "),
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
            )
        }
        val resources = catalogue?.resources.orEmpty()
        if (resources.isEmpty()) item { EduCoreEmptyState("No resources found", "Choose another class, term or subject, or clear the search.") }
        items(resources, key = RepositoryResource::id) { resource -> RepositoryResourceCard(resource) { onOpen(resource.id) } }
        if (catalogue != null && catalogue.currentPage < catalogue.lastPage) {
            item {
                EduCoreSecondaryButton(
                    text = if (state.isLoadingMore) "Loading more…" else "Load more (${resources.size} of ${catalogue.total})",
                    onClick = onLoadMore,
                    enabled = !state.isLoadingMore,
                    modifier = Modifier.fillMaxWidth(),
                )
            }
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
    val detail = state.resource ?: return EduCoreErrorState(
        message = state.errorMessage ?: "This resource is unavailable.",
        modifier = Modifier.fillMaxSize(),
        onRetry = onRetry,
    )
    LazyColumn(
        Modifier.fillMaxSize().imePadding(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { RepositoryHeader(detail.resource.title, "${detail.resource.className} · ${detail.resource.term} · ${detail.resource.subject}", onBack) }
        if (detail.isFromCache) item { EduCoreWarningBanner("Showing the latest content saved on this device.") }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCorePrimaryButton("Download original", onDownload, loading = state.isSaving, leadingIcon = { Icon(Icons.Default.Download, null) })
                Text("${detail.fragments.size} sections", Modifier.align(Alignment.CenterVertically), color = EduCoreColors.Slate600)
            }
        }
        if (detail.fragments.isEmpty()) item { EduCoreEmptyState("No readable sections", "Download the original resource to view it.") }
        items(detail.fragments, key = { it.id }) { fragment ->
            Card(colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
                Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    fragment.theme?.takeIf(String::isNotBlank)?.let { Text(it.uppercase(), color = EduCoreColors.Gold600, style = MaterialTheme.typography.labelMedium) }
                    Text(fragment.topic, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                    fragment.subtopic?.takeIf(String::isNotBlank)?.let { Text(it, color = EduCoreColors.Navy900, fontWeight = FontWeight.SemiBold) }
                    Text(fragment.content, style = MaterialTheme.typography.bodyLarge)
                    fragment.learningExpectation?.takeIf(String::isNotBlank)?.let {
                        Surface(color = EduCoreColors.Info100, shape = MaterialTheme.shapes.small) {
                            Text("Learning expectation: $it", Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), style = MaterialTheme.typography.bodySmall)
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun RepositoryHeader(title: String, subtitle: String, onBack: () -> Unit) {
    EduCorePageHeader(title = title, subtitle = subtitle, onBack = onBack)
}

@Composable
private fun RepositoryMetric(label: String, value: String) {
    Card(Modifier.width(132.dp), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
        Column(Modifier.padding(EduCoreSpacing.Lg)) {
            Text(value, style = MaterialTheme.typography.headlineSmall, color = EduCoreColors.Navy900, fontWeight = FontWeight.Bold)
            Text(label, color = EduCoreColors.Slate600, style = MaterialTheme.typography.bodySmall)
        }
    }
}

@Composable private fun FilterLabel(value: String) = Text(value.uppercase(), style = MaterialTheme.typography.labelMedium, color = EduCoreColors.Slate600)

@Composable
private fun RepositoryResourceCard(resource: RepositoryResource, onOpen: () -> Unit) {
    Card(onClick = onOpen, colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
        Row(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalAlignment = Alignment.CenterVertically) {
            Surface(color = EduCoreColors.Info100, shape = MaterialTheme.shapes.medium) {
                Icon(Icons.Default.Description, null, Modifier.padding(EduCoreSpacing.Md), tint = EduCoreColors.Navy900)
            }
            Spacer(Modifier.width(EduCoreSpacing.Md))
            Column(Modifier.weight(1f)) {
                Text(resource.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                Text("${resource.className} · ${resource.term} · ${resource.subject}", color = EduCoreColors.Slate600, style = MaterialTheme.typography.bodySmall)
                Text("${resource.fragmentsCount} sections · ${resource.filename.orEmpty()}", color = EduCoreColors.Muted500, style = MaterialTheme.typography.bodySmall, maxLines = 1, overflow = TextOverflow.Ellipsis)
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
            val viewIntent = Intent(Intent.ACTION_VIEW).apply {
                setDataAndType(Uri.parse(document.uri), document.mimeType)
                clipData = ClipData.newUri(context.contentResolver, document.filename, Uri.parse(document.uri))
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION or Intent.FLAG_ACTIVITY_NEW_TASK)
            }
            context.startActivity(Intent.createChooser(viewIntent, "Open ${document.filename}").addFlags(Intent.FLAG_ACTIVITY_NEW_TASK))
        }.recoverCatching {
            val sendIntent = Intent(Intent.ACTION_SEND).apply {
                type = document.mimeType
                putExtra(Intent.EXTRA_STREAM, Uri.parse(document.uri))
                clipData = ClipData.newUri(context.contentResolver, document.filename, Uri.parse(document.uri))
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION or Intent.FLAG_ACTIVITY_NEW_TASK)
            }
            context.startActivity(Intent.createChooser(sendIntent, "Share ${document.filename}").addFlags(Intent.FLAG_ACTIVITY_NEW_TASK))
        }
        onOpened()
    }
}
