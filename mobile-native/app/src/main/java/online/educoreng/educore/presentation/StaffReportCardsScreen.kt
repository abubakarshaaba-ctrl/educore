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
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ChevronRight
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
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseStat
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.ClassSummary
import online.educoreng.educore.core.model.StudentSummary

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
                title = "Report cards",
                subtitle = selectedClass?.name,
                onBack = onBack,
            )
        }
        if (selectedClass == null && catalogueClasses.isNotEmpty()) {
            item {
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreShowcaseStat("Classes", catalogueClasses.size.toString(), Icons.Default.School, modifier = Modifier.weight(1f))
                    EduCoreShowcaseStat("Students", totalStudents.toString(), Icons.Default.Groups, tone = EduCoreTone.Success, modifier = Modifier.weight(1f))
                }
            }
        }
        if (selectedClass == null) {
            item { EduCoreSearchBar(value = state.classSearch, onValueChange = onClassSearch, placeholder = "Search class or subject") }
            when {
                state.isLoadingClasses && state.catalogue == null -> item { EduCoreLoadingState(message = "Loading classes") }
                state.errorMessage != null && state.catalogue == null -> item { EduCoreErrorState(message = state.errorMessage, onRetry = onRetryClasses) }
                else -> {
                    val classes = catalogueClasses.filter { summary ->
                        state.classSearch.isBlank() || summary.name.contains(state.classSearch, true) || summary.subjects.any { it.name.contains(state.classSearch, true) }
                    }
                    item { EduCoreSectionHeader(title = "Classes") }
                    if (classes.isEmpty()) {
                        item { EduCoreEmptyState(title = "No classes found", message = "Try another search.") }
                    } else {
                        items(classes, key = ClassSummary::id) { classRoom -> ReportClassCard(classRoom) { onOpenClass(classRoom.id) } }
                    }
                }
            }
        } else {
            item { EduCoreSearchBar(value = state.studentSearch, onValueChange = onStudentSearch, placeholder = "Search student or admission number") }
            when {
                state.isLoadingWorkspace && state.classStudents?.students.isNullOrEmpty() -> item { EduCoreLoadingState(message = "Loading students") }
                state.errorMessage != null && state.classStudents?.students.isNullOrEmpty() -> item { EduCoreErrorState(message = state.errorMessage, onRetry = { onOpenClass(selectedClass.id) }) }
                else -> {
                    val students = state.classStudents?.students.orEmpty().filter { student ->
                        state.studentSearch.isBlank() || student.name.contains(state.studentSearch, true) || student.admissionNumber.contains(state.studentSearch, true)
                    }
                    item { EduCoreSectionHeader(title = "Students") }
                    if (students.isEmpty()) {
                        item { EduCoreEmptyState(title = "No students found", message = "Try another search.") }
                    } else {
                        items(students, key = StudentSummary::id) { student ->
                            ReportStudentCard(student) { onOpenStudentResults(selectedClass.id, student.id) }
                        }
                        if ((state.classStudents?.currentPage ?: 1) < (state.classStudents?.lastPage ?: 1)) {
                            item {
                                Card(
                                    modifier = Modifier.fillMaxWidth().clickable(enabled = !state.isLoadingMoreStudents, onClick = onLoadMoreStudents),
                                    colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                                    border = BorderStroke(1.dp, EduCoreColors.Line200),
                                ) {
                                    Text(
                                        text = if (state.isLoadingMoreStudents) "Loading…" else "Load more",
                                        modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
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
}

@Composable
private fun ReportClassCard(classRoom: ClassSummary, onClick: () -> Unit) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Surface(modifier = Modifier.size(40.dp), shape = CircleShape, color = EduCoreColors.Info100, contentColor = EduCoreColors.Navy900) {
                Box(contentAlignment = Alignment.Center) { Icon(Icons.Default.School, contentDescription = null, modifier = Modifier.size(20.dp)) }
            }
            Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                Text(classRoom.name, style = MaterialTheme.typography.titleMedium, color = EduCoreColors.Ink900, maxLines = 1, overflow = TextOverflow.Ellipsis)
                Text("${classRoom.studentCount} students · ${classRoom.subjects.size} subjects", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
            Icon(Icons.Default.ChevronRight, contentDescription = "Open class", tint = EduCoreColors.Slate600)
        }
    }
}

@Composable
private fun ReportStudentCard(student: StudentSummary, onClick: () -> Unit) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Surface(modifier = Modifier.size(40.dp), shape = CircleShape, color = EduCoreColors.Info100, contentColor = EduCoreColors.Navy900) {
                Box(contentAlignment = Alignment.Center) { Text(student.initials, style = MaterialTheme.typography.labelLarge) }
            }
            Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                Text(student.name, style = MaterialTheme.typography.titleSmall, color = EduCoreColors.Ink900, maxLines = 1, overflow = TextOverflow.Ellipsis)
                Text(student.admissionNumber, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
            }
            EduCoreStatusBadge("Published", EduCoreTone.Success)
            Icon(Icons.Default.ChevronRight, contentDescription = "Open report card", tint = EduCoreColors.Slate600)
        }
    }
}