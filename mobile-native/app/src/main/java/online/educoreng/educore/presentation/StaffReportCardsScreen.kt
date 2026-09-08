package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.weight
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ChevronRight
import androidx.compose.material.icons.filled.Description
import androidx.compose.material.icons.filled.Groups
import androidx.compose.material.icons.filled.School
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
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseStat
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.ClassSummary
import online.educoreng.educore.core.model.StudentSummary

/**
 * Staff-native report card workspace.
 *
 * The server remains authoritative: the class catalogue is assignment-scoped,
 * student lists are resolved inside the selected class, and the result endpoint
 * exposes published report cards only. This screen never broadens that scope.
 */
@Composable
internal fun StaffReportCardsScreen(
    state: ClassesUiState,
    onBack: () -> Unit,
    onClassSearch: (String) -> Unit,
    onStudentSearch: (String) -> Unit,
    onOpenClass: (Long) -> Unit,
    onOpenStudentResults: (Long, Long) -> Unit,
    onLoadMoreStudents: () -> Unit,
    onRetryClasses: () -> Unit,
) {
    val selectedClass = state.classStudents?.classSummary
    val catalogueClasses = state.catalogue?.classes.orEmpty()
    val totalStudents = catalogueClasses.sumOf(ClassSummary::studentCount)

    LazyColumn(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = androidx.compose.foundation.layout.PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = if (selectedClass == null) "Report Cards" else selectedClass.name,
                subtitle = if (selectedClass == null) {
                    "Published academic reports for your authorised classes"
                } else {
                    "Choose a student to open the published report"
                },
                onBack = onBack,
            )
        }

        item {
            EduCoreShowcaseHero(
                eyebrow = if (selectedClass == null) "REPORTING WORKSPACE" else "SELECTED CLASS",
                title = if (selectedClass == null) "Reports without the web detour." else selectedClass.name,
                subtitle = if (selectedClass == null) {
                    "Move from your assigned class to a student report entirely inside the EduCore app. Only published results are displayed."
                } else {
                    "${selectedClass.studentCount} students · ${selectedClass.subjects.size} subjects available in this class workspace."
                },
                trailing = {
                    Surface(
                        modifier = Modifier.size(48.dp),
                        shape = CircleShape,
                        color = EduCoreColors.Gold100,
                        contentColor = EduCoreColors.Navy900,
                    ) {
                        Box(contentAlignment = Alignment.Center) {
                            Icon(Icons.Default.Description, contentDescription = null, modifier = Modifier.size(24.dp))
                        }
                    }
                },
            )
        }

        if (selectedClass == null && catalogueClasses.isNotEmpty()) {
            item {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                ) {
                    EduCoreShowcaseStat(
                        label = "Authorised classes",
                        value = catalogueClasses.size.toString(),
                        icon = Icons.Default.School,
                        tone = EduCoreTone.Brand,
                        modifier = Modifier.weight(1f),
                    )
                    EduCoreShowcaseStat(
                        label = "Students in scope",
                        value = totalStudents.toString(),
                        icon = Icons.Default.Groups,
                        tone = EduCoreTone.Success,
                        modifier = Modifier.weight(1f),
                    )
                }
            }
        }

        if (selectedClass == null) {
            item {
                EduCoreSearchBar(
                    value = state.classSearch,
                    onValueChange = onClassSearch,
                    placeholder = "Search class or subject",
                )
            }

            when {
                state.isLoadingClasses && state.catalogue == null -> item {
                    EduCoreLoadingState(message = "Loading your classes")
                }
                state.errorMessage != null && state.catalogue == null -> item {
                    EduCoreErrorState(message = state.errorMessage, onRetry = onRetryClasses)
                }
                else -> {
                    val classes = catalogueClasses.filter { summary ->
                        state.classSearch.isBlank() ||
                            summary.name.contains(state.classSearch, ignoreCase = true) ||
                            summary.subjects.any { it.name.contains(state.classSearch, ignoreCase = true) }
                    }
                    item {
                        EduCoreSectionHeader(
                            title = "Choose a class",
                            supportingText = "Only classes granted to this account are listed",
                        )
                    }
                    if (classes.isEmpty()) {
                        item {
                            EduCoreEmptyState(
                                title = if (state.classSearch.isBlank()) "No report classes" else "No classes found",
                                message = if (state.classSearch.isBlank()) {
                                    "No class with report-card access is currently available to this account."
                                } else {
                                    "No authorised class matches your search."
                                },
                            )
                        }
                    } else {
                        items(classes, key = ClassSummary::id) { classRoom ->
                            ReportClassCard(classRoom) { onOpenClass(classRoom.id) }
                        }
                    }
                }
            }
        } else {
            item {
                EduCoreSearchBar(
                    value = state.studentSearch,
                    onValueChange = onStudentSearch,
                    placeholder = "Search student or admission number",
                )
            }

            when {
                state.isLoadingWorkspace && state.classStudents?.students.isNullOrEmpty() -> item {
                    EduCoreLoadingState(message = "Loading students")
                }
                state.errorMessage != null && state.classStudents?.students.isNullOrEmpty() -> item {
                    EduCoreErrorState(
                        message = state.errorMessage,
                        onRetry = { onOpenClass(selectedClass.id) },
                    )
                }
                else -> {
                    val students = state.classStudents?.students.orEmpty().filter { student ->
                        state.studentSearch.isBlank() ||
                            student.name.contains(state.studentSearch, ignoreCase = true) ||
                            student.admissionNumber.contains(state.studentSearch, ignoreCase = true)
                    }
                    item {
                        EduCoreSectionHeader(
                            title = "Students",
                            supportingText = "Open a student to view the published report card",
                        )
                    }
                    if (students.isEmpty()) {
                        item {
                            EduCoreEmptyState(
                                title = "No students found",
                                message = if (state.studentSearch.isBlank()) {
                                    "No active student is available in this class."
                                } else {
                                    "Try another student name or admission number."
                                },
                            )
                        }
                    } else {
                        items(students, key = StudentSummary::id) { student ->
                            ReportStudentCard(student) {
                                onOpenStudentResults(selectedClass.id, student.id)
                            }
                        }
                        if ((state.classStudents?.currentPage ?: 1) < (state.classStudents?.lastPage ?: 1)) {
                            item {
                                Card(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .clickable(enabled = !state.isLoadingMoreStudents, onClick = onLoadMoreStudents),
                                    colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                                    border = BorderStroke(1.dp, EduCoreColors.Line200),
                                ) {
                                    Text(
                                        text = if (state.isLoadingMoreStudents) "Loading more students…" else "Load more students",
                                        modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
                                        style = MaterialTheme.typography.labelLarge,
                                        fontWeight = FontWeight.SemiBold,
                                        color = EduCoreColors.Navy900,
                                    )
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun ReportClassCard(classRoom: ClassSummary, onClick: () -> Unit) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.large,
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Surface(
                modifier = Modifier.size(44.dp),
                shape = CircleShape,
                color = EduCoreColors.Navy900,
                contentColor = EduCoreColors.White,
            ) {
                Box(contentAlignment = Alignment.Center) {
                    Icon(Icons.Default.School, contentDescription = null, modifier = Modifier.size(21.dp))
                }
            }
            Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                Text(
                    classRoom.name,
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    color = EduCoreColors.Ink900,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis,
                )
                Text(
                    "${classRoom.studentCount} students · ${classRoom.subjects.size} subjects",
                    style = MaterialTheme.typography.bodySmall,
                    color = EduCoreColors.Slate600,
                )
                classRoom.subjects.takeIf { it.isNotEmpty() }?.let { subjects ->
                    Text(
                        subjects.joinToString(" • ") { it.name },
                        style = MaterialTheme.typography.bodySmall,
                        color = EduCoreColors.Muted500,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                    )
                }
            }
            Icon(Icons.Default.ChevronRight, contentDescription = "Open class", tint = EduCoreColors.Gold700)
        }
    }
}

@Composable
private fun ReportStudentCard(student: StudentSummary, onClick: () -> Unit) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.large,
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Surface(
                modifier = Modifier.size(44.dp),
                shape = CircleShape,
                color = EduCoreColors.Gold50,
                contentColor = EduCoreColors.Navy900,
            ) {
                Box(contentAlignment = Alignment.Center) {
                    Text(
                        student.initials,
                        style = MaterialTheme.typography.labelLarge,
                        fontWeight = FontWeight.Bold,
                    )
                }
            }
            Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                Text(
                    student.name,
                    style = MaterialTheme.typography.titleSmall,
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
            EduCoreStatusBadge("Published only", EduCoreTone.Success)
            Icon(Icons.Default.ChevronRight, contentDescription = "Open report card", tint = EduCoreColors.Gold700)
        }
    }
}
