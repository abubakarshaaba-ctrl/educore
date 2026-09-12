package online.educoreng.educore.presentation

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material3.ExtendedFloatingActionButton
import androidx.compose.material3.Icon
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

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
        if (!payrollState.isLoading) viewModel.load()
    }

    if (payrollState.generationOpen) {
        PayrollGenerationScreen(
            state = payrollState,
            onBack = viewModel::closeGeneration,
            onTitle = viewModel::setGenerationTitle,
            onStart = viewModel::setGenerationStart,
            onEnd = viewModel::setGenerationEnd,
            onGenerate = viewModel::generate,
        )
        return
    }

    Box(Modifier.fillMaxSize()) {
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

        if (payrollState.canManage && payrollState.detail == null && !payrollState.isLoading) {
            ExtendedFloatingActionButton(
                onClick = viewModel::openGeneration,
                modifier = Modifier
                    .align(Alignment.BottomEnd)
                    .padding(EduCoreSpacing.Md),
                icon = { Icon(Icons.Default.Add, contentDescription = null) },
                text = { Text("Generate") },
            )
        }
    }
}
