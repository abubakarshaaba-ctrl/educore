package online.educoreng.educore.presentation

import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle

/** Keeps the generic module route while using the dedicated library management API. */
@Composable
internal fun LibraryOperationsScreen(
    state: OperationsUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSection: (Int) -> Unit,
    onRefreshWorkspace: () -> Unit,
) {
    val viewModel: LibraryManagementViewModel = hiltViewModel()
    val management by viewModel.uiState.collectAsStateWithLifecycle()

    LaunchedEffect(management.refreshVersion) {
        if (management.refreshVersion > 0) {
            onRefreshWorkspace()
            viewModel.consumeRefresh()
        }
    }

    LibraryScreen(
        state = state,
        management = management,
        onBack = onBack,
        onQuery = onQuery,
        onSection = onSection,
        onOpenIssue = viewModel::openIssue,
        onCloseIssue = viewModel::closeIssue,
        onBook = viewModel::selectBook,
        onBorrowerType = viewModel::selectBorrowerType,
        onBorrower = viewModel::selectBorrower,
        onDueDate = viewModel::setDueDate,
        onNotes = viewModel::setNotes,
        onIssue = viewModel::issue,
        onReturn = viewModel::returnLoan,
    )
}
