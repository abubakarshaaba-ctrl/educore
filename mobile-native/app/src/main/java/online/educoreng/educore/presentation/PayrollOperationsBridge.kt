package online.educoreng.educore.presentation

import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle

/** Keeps the operations navigation stable while Payroll uses its dedicated API. */
@Composable
internal fun PayrollScreen(
    state: OperationsUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
) {
    val viewModel: PayrollViewModel = hiltViewModel()
    val payrollState by viewModel.uiState.collectAsStateWithLifecycle()

    LaunchedEffect(state.workspace?.module?.key) {
        if (payrollState.workspace == null && !payrollState.isLoading) {
            viewModel.load()
        }
    }

    PayrollScreen(
        state = payrollState,
        onBack = onBack,
        onQuery = viewModel::setQuery,
        onSearch = viewModel::search,
        onStatus = viewModel::setStatus,
        onOpen = viewModel::open,
        onCloseDetail = viewModel::closeDetail,
        onApprove = viewModel::approve,
        onMarkPaid = viewModel::markPaid,
        onLoadMore = viewModel::loadMore,
        onRetry = viewModel::load,
    )
}
