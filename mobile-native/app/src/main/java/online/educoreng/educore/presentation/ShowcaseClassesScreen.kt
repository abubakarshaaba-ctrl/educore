package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.GridItemSpan
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Groups
import androidx.compose.material.icons.filled.MenuBook
import androidx.compose.material.icons.filled.School
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseStat
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.component.EduCoreWarningBanner
import online.educoreng.educore.core.designsystem.layout.EduCoreWindowWidth
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.ClassSummary

/**
 * My Classes presentation based on the approved August EduCore mobile concept.
 *
 * This screen never manufactures access. The catalogue is already scoped by the
 * server and every action exposed inside a class continues to be controlled by
 * ClassCapabilities returned by the mobile API.
 */
@Composable
internal fun ShowcaseClassesListScreen(
    state: ClassesUiState,
    width: EduCoreWindowWidth,
    onSearch: (String) -> Unit,
    onOpenClass: (Long) -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoadingClasses && state.catalogue == null) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Loading your classes")
        return
    }
    val catalogueError = state.errorMessage
    if (state.catalogue == null && catalogueError != null) {
        EduCoreErrorState(catalogueError, Modifier.fillMaxSize(), onRetry = onRetry)
        return
    }
    val catalogue = state.catalogue ?: return
    val classes = remember(catalogue.classes, state.classSearch) {
        catalogue.classes.filter { item ->
            state.classSearch.isBlank() || listOf(
                item.name,
                item.formTutorName.orEmpty(),
                item.subjects.joinToString(" ") { it.name },
            ).any { it.contains(state.classSearch, ignoreCase = true) }
        }
    }
    val uniqueSubjects = remember(catalogue.classes) {
        catalogue.classes.flatMap { it.subjects }.distinctBy { it.id }.size
    }
    val totalStudents = remember(catalogue.classes) {
        catalogue.classes.sumOf { it.studentCount }
    }
    val columns = when (width) {
        EduCoreWindowWidth.Compact -> 1
        EduCoreWindowWidth.Medium -> 2
        EduCoreWindowWidth.Expanded -> 3
    }

    LazyVerticalGrid(
        columns = GridCells.Fixed(columns),
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = androidx.compose.foundation.layout.PaddingValues(eduCoreScreenPadding()),
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item(key = "classes-hero", span = { GridItemSpan(maxLineSpan) }) {
            EduCoreShowcaseHero(
                eyebrow = "MY CLASSES",
                title = "Everything you need, in one place.",
                subtitle = "Manage your assigned classes, students and teaching workspaces with ease.",
                trailing = {
                    Surface(
                        modifier = Modifier.size(48.dp),
                        shape = CircleShape,
                        color = EduCoreColors.Gold100,
                        contentColor = EduCoreColors.Navy900,
                    ) {
                        Box(contentAlignment = Alignment.Center) {
                            Icon(Icons.Default.School, contentDescription = null, modifier = Modifier.size(25.dp))
                        }
                    }
                },
            )
        }

        if (catalogue.isFromCache) {
            item(key = "classes-cache", span = { GridItemSpan(maxLineSpan) }) {
                EduCoreWarningBanner("Showing the most recent class list saved on this device.")
            }
        }

        item(key = "classes-summary-heading", span = { GridItemSpan(maxLineSpan) }) {
            EduCoreSectionHeader(
                title = "Summary",
                supportingText = "Only assignments available to your account are counted",
            )
        }

        item(key = "class-count") {
            EduCoreShowcaseStat(
                label = "Classes",
                value = catalogue.classes.size.toString(),
                icon = Icons.Default.School,
                tone = EduCoreTone.Brand,
                modifier = Modifier.fillMaxWidth(),
            )
        }
        item(key = "student-count") {
            EduCoreShowcaseStat(
                label = "Students",
                value = totalStudents.toString(),
                icon = Icons.Default.Groups,
                tone = EduCoreTone.Success,
                modifier = Modifier.fillMaxWidth(),
            )
        }
        item(key = "subject-count") {
            EduCoreShowcaseStat(
                label = "Subjects",
                value = uniqueSubjects.toString(),
                icon = Icons.Default.MenuBook,
                tone = EduCoreTone.Accent,
                modifier = Modifier.fillMaxWidth(),
            )
        }

        item(key = "class-search", span = { GridItemSpan(maxLineSpan) }) {
            Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreSectionHeader(
                    title = "Assigned Classes",
                    supportingText = "Open a class to access only the actions permitted for that assignment",
                )
                EduCoreSearchBar(
                    value = state.classSearch,
                    onValueChange = onSearch,
                    placeholder = "Search class, subject or form tutor",
                )
            }
        }

        if (classes.isEmpty()) {
            item(key = "class-empty", span = { GridItemSpan(maxLineSpan) }) {
                EduCoreEmptyState(
                    title = if (state.classSearch.isBlank()) "No assigned classes" else "No classes found",
                    message = if (state.classSearch.isBlank()) {
                        "Your school has not assigned a class workspace to this account."
                    } else {
                        "Try another class, subject or teacher name."
                    },
                )
            }
        } else {
            items(classes, key = ClassSummary::id) { classSummary ->
                ShowcaseClassCard(classSummary) { onOpenClass(classSummary.id) }
            }
        }
    }
}

@Composable
private fun ShowcaseClassCard(
    classSummary: ClassSummary,
    onClick: () -> Unit,
) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.large,
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Surface(
                    modifier = Modifier.size(44.dp),
                    shape = CircleShape,
                    color = EduCoreColors.Navy900,
                    contentColor = Color.White,
                ) {
                    Box(contentAlignment = Alignment.Center) {
                        Icon(Icons.Default.School, contentDescription = null, modifier = Modifier.size(21.dp))
                    }
                }
                Column(
                    modifier = Modifier.weight(1f).padding(start = EduCoreSpacing.Md),
                ) {
                    Text(
                        classSummary.name,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                        color = EduCoreColors.Ink900,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                    )
                    val roleLine = classSummary.roles
                        .map(::humanizeClassRole)
                        .joinToString(" · ")
                        .ifBlank { classSummary.levelName.orEmpty() }
                    if (roleLine.isNotBlank()) {
                        Text(
                            roleLine,
                            style = MaterialTheme.typography.bodySmall,
                            color = EduCoreColors.Slate600,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis,
                        )
                    }
                }
                EduCoreStatusBadge("${classSummary.studentCount} students", EduCoreTone.Info)
            }

            if (classSummary.subjects.isNotEmpty()) {
                Text(
                    classSummary.subjects.joinToString(" • ") { it.name },
                    style = MaterialTheme.typography.bodyMedium,
                    color = EduCoreColors.Slate700,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis,
                )
            }

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                if (classSummary.capabilities.markAttendance) {
                    EduCoreStatusBadge("Attendance", EduCoreTone.Success)
                }
                if (classSummary.capabilities.enterScores) {
                    EduCoreStatusBadge("Scores", EduCoreTone.Accent)
                }
                if (classSummary.capabilities.planLessons) {
                    EduCoreStatusBadge("Lessons", EduCoreTone.Brand)
                }
            }

            classSummary.formTutorName?.takeIf(String::isNotBlank)?.let {
                Text(
                    "Form tutor: $it",
                    style = MaterialTheme.typography.labelSmall,
                    color = EduCoreColors.Muted500,
                )
            }
        }
    }
}

private fun humanizeClassRole(value: String): String = value
    .replace('_', ' ')
    .trim()
    .split(Regex("\\s+"))
    .joinToString(" ") { word -> word.replaceFirstChar { it.uppercase() } }
