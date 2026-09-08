package online.educoreng.educore.presentation

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ChevronRight
import androidx.compose.material.icons.filled.Description
import androidx.compose.material.icons.filled.Groups
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.ClassSummary
import online.educoreng.educore.core.model.StudentSummary

/**
 * Staff-native report card entry point.
 * Staff first selects one of the classes the server already authorises, then a
 * student in that class. Only published report cards are returned by the API.
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

    LazyColumn(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = androidx.compose.foundation.layout.PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Report Cards",
                subtitle = if (selectedClass == null) {
                    "Select one of your authorised classes"
                } else {
                    "${selectedClass.name} · Select a student"
                },
                onBack = onBack,
            )
        }

        if (selectedClass == null) {
            item {
                EduCoreSearchBar(
                    value = state.classSearch,
                    onValueChange = onClassSearch,
                    placeholder = "Search classes",
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
                    val classes = state.catalogue?.classes.orEmpty().filter { summary ->
                        state.classSearch.isBlank() ||
                            summary.name.contains(state.classSearch, ignoreCase = true) ||
                            summary.subjects.any { it.name.contains(state.classSearch, ignoreCase = true) }
                    }
                    item {
                        EduCoreSectionHeader(
                            title = "My classes",
                            supportingText = "Report cards remain scoped to classes you can access",
                        )
                    }
                    if (classes.isEmpty()) {
                        item {
                            EduCoreEmptyState(
                                title = "No classes found",
                                message = "No authorised class matches your search.",
                            )
                        }
                    } else {
                        items(classes, key = ClassSummary::id) { classRoom ->
                            ReportClassRow(classRoom) { onOpenClass(classRoom.id) }
                        }
                    }
                }
            }
        } else {
            item {
                EduCoreSearchBar(
                    value = state.studentSearch,
                    onValueChange = onStudentSearch,
                    placeholder = "Search students",
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
                    val students = state.classStudents?.students.orEmpty()
                    item {
                        EduCoreSectionHeader(
                            title = "Students",
                            supportingText = "Only published report cards are shown",
                        )
                    }
                    if (students.isEmpty()) {
                        item {
                            EduCoreEmptyState(
                                title = "No students found",
                                message = "No active student matches this search.",
                            )
                        }
                    } else {
                        items(students, key = StudentSummary::id) { student ->
                            ReportStudentRow(student) {
                                onOpenStudentResults(selectedClass.id, student.id)
                            }
                        }
                        if ((state.classStudents?.currentPage ?: 1) < (state.classStudents?.lastPage ?: 1)) {
                            item {
                                Text(
                                    text = if (state.isLoadingMoreStudents) "Loading more…" else "Load more students",
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .clickable(enabled = !state.isLoadingMoreStudents, onClick = onLoadMoreStudents)
                                        .padding(EduCoreSpacing.Lg),
                                    style = MaterialTheme.typography.labelLarge,
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

@Composable
private fun ReportClassRow(classRoom: ClassSummary, onClick: () -> Unit) {
    Card(
        modifier = Modifier.fillMaxWidth().clickable(onClick = onClick),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Icon(Icons.Default.Groups, contentDescription = null, tint = EduCoreColors.Navy900)
            Column(Modifier.weight(1f)) {
                Text(classRoom.name, style = MaterialTheme.typography.titleMedium, color = EduCoreColors.Ink900)
                Text(
                    "${classRoom.studentCount} students" + classRoom.subjects.firstOrNull()?.name?.let { " · $it" }.orEmpty(),
                    style = MaterialTheme.typography.bodySmall,
                    color = EduCoreColors.Slate600,
                )
            }
            Icon(Icons.Default.ChevronRight, contentDescription = null, tint = EduCoreColors.Gold700)
        }
    }
}

@Composable
private fun ReportStudentRow(student: StudentSummary, onClick: () -> Unit) {
    Card(
        modifier = Modifier.fillMaxWidth().clickable(onClick = onClick),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Icon(Icons.Default.Description, contentDescription = null, tint = EduCoreColors.Navy900)
            Column(Modifier.weight(1f)) {
                Text(student.name, style = MaterialTheme.typography.titleSmall, color = EduCoreColors.Ink900)
                Text(student.admissionNumber, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
            Icon(Icons.Default.ChevronRight, contentDescription = null, tint = EduCoreColors.Gold700)
        }
    }
}
