package online.educoreng.educore.presentation

import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle

/**
 * Keeps the existing operations router stable while Fees uses its dedicated
 * transactional API and state model.
 */
@Composable
internal fun FeesScreen(
    state: OperationsUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSection: (Int) -> Unit,
) {
    val viewModel: FeesViewModel = hiltViewModel()
    val feesState by viewModel.uiState.collectAsStateWithLifecycle()

    LaunchedEffect(state.workspace?.module?.key) {
        if (feesState.workspace == null && !feesState.isLoading) {
            viewModel.load()
        }
    }

    FeesScreen(
        state = feesState,
        onBack = onBack,
        onQuery = viewModel::setQuery,
        onSearch = viewModel::search,
        onStatus = viewModel::setStatus,
        onTab = viewModel::setTab,
        onOpenPayment = viewModel::openPayment,
        onClosePayment = viewModel::closePayment,
        onPaymentAmount = viewModel::setPaymentAmount,
        onPaidByName = viewModel::setPaidByName,
        onPaidByPhone = viewModel::setPaidByPhone,
        onGateway = viewModel::setGateway,
        onSavePayment = viewModel::savePayment,
        onOpenGeneration = viewModel::openGeneration,
        onCloseGeneration = viewModel::closeGeneration,
        onGenerationTerm = viewModel::setGenerationTerm,
        onGenerationClass = viewModel::setGenerationClass,
        onGenerate = viewModel::generateBills,
        onLoadMore = viewModel::loadMore,
        onRetry = viewModel::load,
    )
}
