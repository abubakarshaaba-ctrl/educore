package online.educoreng.educore.presentation

import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle

/** Keeps the existing operations router stable while Expenses use their dedicated API. */
@Composable
internal fun ExpensesScreen(
    state: OperationsUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
) {
    val viewModel: ExpensesViewModel = hiltViewModel()
    val expenseState by viewModel.uiState.collectAsStateWithLifecycle()

    LaunchedEffect(state.workspace?.module?.key) {
        if (expenseState.workspace == null && !expenseState.isLoading) {
            viewModel.load()
        }
    }

    ExpensesScreen(
        state = expenseState,
        onBack = onBack,
        onQuery = viewModel::setQuery,
        onSearch = viewModel::search,
        onCategory = viewModel::setCategory,
        onCreate = viewModel::create,
        onEdit = viewModel::edit,
        onCloseEditor = viewModel::closeEditor,
        onField = viewModel::updateField,
        onDraftCategory = viewModel::setDraftCategory,
        onSession = viewModel::setSession,
        onTerm = viewModel::setTerm,
        onSave = viewModel::save,
        onDelete = viewModel::delete,
        onLoadMore = viewModel::loadMore,
        onRetry = viewModel::load,
    )
}
