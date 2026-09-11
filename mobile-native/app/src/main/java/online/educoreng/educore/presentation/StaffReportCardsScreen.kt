package online.educoreng.educore.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

/**
 * Legacy staff report-card destination retained only so older in-app route
 * constants compile while the shell is being simplified.
 *
 * Report cards/results are intentionally not available to staff, admin,
 * accountant, or student Android accounts. Parent accounts use the published
 * result workflow in the parent shell instead.
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
    @Suppress("UNUSED_VARIABLE")
    val compatibility = listOf(
        state,
        onClassSearch,
        onStudentSearch,
        onOpenClass,
        onOpenStudentResults,
        onLoadMoreStudents,
        onRetryClasses,
    )

    Column(
        modifier = Modifier.fillMaxSize().padding(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        EduCorePageHeader(title = "Results", onBack = onBack)
        Text(
            text = "Report cards and results are available in the Android app only to parent accounts.",
            style = MaterialTheme.typography.bodyMedium,
            color = EduCoreColors.Slate600,
        )
    }
}
