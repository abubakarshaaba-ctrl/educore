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
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Description
import androidx.compose.material.icons.filled.Download
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreExpandableSection
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseSectionCard
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTabs
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.component.EduCoreWarningBanner
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.RepositoryFragment

/**
 * Academic note reader modelled on the approved August mock-up.
 *
 * The three visual tabs are views over the single repository resource already
 * authorised by the API; they do not request or expose any additional data.
 */
@Composable
internal fun ShowcaseAcademicResourceDetailScreen(
    state: AcademicContentUiState,
    onBack: () -> Unit,
    onOpenOriginal: () -> Unit,
    onRetry: () -> Unit,
    onDocumentOpened: () -> Unit,
) {
    OpenDocumentEffect(state.downloadedDocument, onDocumentOpened)
    if (state.isLoading && state.resource == null) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Opening academic note")
    }
    val detail = state.resource ?: return EduCoreErrorState(
        message = state.errorMessage ?: "This academic resource is unavailable.",
        modifier = Modifier.fillMaxSize(),
        onRetry = onRetry,
    )
    var selectedTab by remember(detail.resource.id) { mutableIntStateOf(0) }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = detail.resource.title,
                subtitle = "Academic Repository / Notes",
                onBack = onBack,
            )
        }
        if (detail.isFromCache) {
            item { EduCoreWarningBanner("Showing the latest content saved on this device.") }
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }

        item {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                EduCoreStatusBadge(detail.resource.subject, EduCoreTone.Brand)
                EduCoreStatusBadge(detail.resource.className, EduCoreTone.Accent)
                EduCoreStatusBadge(detail.resource.term, EduCoreTone.Neutral)
            }
        }

        item {
            EduCoreTabs(
                labels = listOf("Overview", "Content", "Resources"),
                selectedIndex = selectedTab,
                onSelected = { selectedTab = it },
                modifier = Modifier.fillMaxWidth(),
            )
        }

        when (selectedTab) {
            0 -> {
                item {
                    EduCoreShowcaseSectionCard {
                        Text(
                            "Summary",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold,
                            color = EduCoreColors.Navy900,
                        )
                        Spacer(Modifier.height(EduCoreSpacing.Sm))
                        Text(
                            overviewText(detail.fragments, detail.resource.subject),
                            style = MaterialTheme.typography.bodyMedium,
                            color = EduCoreColors.Slate700,
                        )
                    }
                }
                if (detail.fragments.isEmpty()) {
                    item {
                        EduCoreEmptyState(
                            title = "No readable sections",
                            message = "Open the original resource to view its source file.",
                        )
                    }
                } else {
                    items(detail.fragments.take(6), key = { "overview-${it.id}" }) { fragment ->
                        OverviewTopicCard(fragment)
                    }
                }
            }

            1 -> {
                if (detail.fragments.isEmpty()) {
                    item {
                        EduCoreEmptyState(
                            title = "No prepared content",
                            message = "This resource does not yet contain readable sections.",
                        )
                    }
                } else {
                    items(detail.fragments, key = RepositoryFragment::id) { fragment ->
                        EduCoreExpandableSection(
                            title = fragment.topic,
                            supportingText = listOfNotNull(fragment.theme, fragment.subtopic)
                                .filter(String::isNotBlank)
                                .joinToString(" · "),
                            initiallyExpanded = detail.fragments.firstOrNull()?.id == fragment.id,
                        ) {
                            Column(
                                modifier = Modifier.fillMaxWidth().padding(
                                    start = EduCoreSpacing.Lg,
                                    end = EduCoreSpacing.Lg,
                                    bottom = EduCoreSpacing.Lg,
                                ),
                                verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                            ) {
                                Text(
                                    fragment.content,
                                    style = MaterialTheme.typography.bodyLarge,
                                    color = EduCoreColors.Ink900,
                                )
                                fragment.learningExpectation?.takeIf(String::isNotBlank)?.let { expectation ->
                                    Surface(
                                        color = EduCoreColors.Gold50,
                                        shape = MaterialTheme.shapes.medium,
                                        border = BorderStroke(1.dp, EduCoreColors.Gold200),
                                    ) {
                                        Text(
                                            "Learning expectation: $expectation",
                                            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
                                            style = MaterialTheme.typography.bodySmall,
                                            color = EduCoreColors.Navy900,
                                        )
                                    }
                                }
                            }
                        }
                    }
                }
            }

            else -> {
                item {
                    EduCoreShowcaseSectionCard {
                        Text(
                            "Attachments",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold,
                            color = EduCoreColors.Navy900,
                        )
                        Spacer(Modifier.height(EduCoreSpacing.Md))
                        Card(
                            colors = CardDefaults.cardColors(containerColor = EduCoreColors.Page50),
                            border = BorderStroke(1.dp, EduCoreColors.Line200),
                        ) {
                            Row(
                                modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                            ) {
                                Surface(
                                    color = EduCoreColors.Gold50,
                                    shape = MaterialTheme.shapes.medium,
                                ) {
                                    Icon(
                                        Icons.Default.Description,
                                        contentDescription = null,
                                        modifier = Modifier.padding(EduCoreSpacing.Md),
                                        tint = EduCoreColors.Navy900,
                                    )
                                }
                                Column(Modifier.weight(1f)) {
                                    Text(
                                        detail.resource.filename ?: detail.resource.title,
                                        style = MaterialTheme.typography.titleSmall,
                                        maxLines = 2,
                                        overflow = TextOverflow.Ellipsis,
                                    )
                                    Text(
                                        resourceMetadata(
                                            detail.resource.mimeType,
                                            detail.resource.fileSize,
                                            detail.fragments.size,
                                        ),
                                        style = MaterialTheme.typography.bodySmall,
                                        color = EduCoreColors.Slate600,
                                    )
                                }
                            }
                        }
                        Spacer(Modifier.height(EduCoreSpacing.Md))
                        EduCorePrimaryButton(
                            text = "Open original resource",
                            onClick = onOpenOriginal,
                            modifier = Modifier.fillMaxWidth(),
                            loading = state.isSaving,
                            leadingIcon = { Icon(Icons.Default.Download, contentDescription = null) },
                        )
                    }
                }
            }
        }

        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

@Composable
private fun OverviewTopicCard(fragment: RepositoryFragment) {
    Card(
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
        shape = MaterialTheme.shapes.large,
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs),
        ) {
            Text(
                "${fragment.sequence}. ${fragment.topic}",
                style = MaterialTheme.typography.titleSmall,
                fontWeight = FontWeight.SemiBold,
                color = EduCoreColors.Navy900,
            )
            fragment.subtopic?.takeIf(String::isNotBlank)?.let {
                Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
        }
    }
}

private fun overviewText(fragments: List<RepositoryFragment>, subject: String): String {
    if (fragments.isEmpty()) {
        return "This $subject resource is available as an original document."
    }
    val expectation = fragments.firstNotNullOfOrNull { it.learningExpectation?.takeIf(String::isNotBlank) }
    return buildString {
        append("This resource contains ${fragments.size} prepared section")
        if (fragments.size != 1) append('s')
        append(" for $subject.")
        if (expectation != null) append(" $expectation")
    }
}

private fun resourceMetadata(mimeType: String?, fileSize: Long?, sections: Int): String {
    val parts = mutableListOf<String>()
    mimeType?.substringAfter('/')?.uppercase()?.takeIf(String::isNotBlank)?.let(parts::add)
    fileSize?.takeIf { it > 0 }?.let { bytes ->
        val kb = bytes / 1024.0
        parts += if (kb >= 1024) String.format("%.1f MB", kb / 1024.0) else String.format("%.0f KB", kb)
    }
    parts += "$sections section${if (sections == 1) "" else "s"}"
    return parts.joinToString(" · ")
}
