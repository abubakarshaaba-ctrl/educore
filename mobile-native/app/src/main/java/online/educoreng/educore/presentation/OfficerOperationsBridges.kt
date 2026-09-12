package online.educoreng.educore.presentation

import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle

/**
 * Connects the generic operations route to the full native Transport Officer
 * workflow. Keeping the dispatch here means every shell that opens the
 * `transport` module receives the same tenant-scoped implementation.
 */
@Composable
internal fun TransportOperationsScreen(onBack: () -> Unit) {
    val viewModel: TransportViewModel = hiltViewModel()
    val state by viewModel.uiState.collectAsStateWithLifecycle()

    LaunchedEffect(Unit) { viewModel.load() }

    NativeTransportScreen(
        state = state,
        onBack = onBack,
        onQuery = viewModel::setQuery,
        onSearch = viewModel::search,
        onOpenManifest = viewModel::openManifest,
        onCloseManifest = viewModel::closeManifest,
        onOpenAssignment = viewModel::openAssignment,
        onCloseAssignment = viewModel::closeAssignment,
        onRoute = viewModel::selectRoute,
        onStudent = viewModel::selectStudent,
        onPickupStop = viewModel::setPickupStop,
        onDirection = viewModel::setDirection,
        onAssign = viewModel::assign,
        onUnassign = viewModel::unassign,
        onLoadMore = viewModel::loadMore,
        onRetry = viewModel::load,
    )
}

/**
 * Connects the generic operations route to the full native Health Officer
 * register/detail/edit workflow.
 */
@Composable
internal fun HealthOperationsScreen(onBack: () -> Unit) {
    val viewModel: HealthViewModel = hiltViewModel()
    val state by viewModel.uiState.collectAsStateWithLifecycle()

    LaunchedEffect(Unit) { viewModel.load() }

    NativeHealthScreen(
        state = state,
        onBack = onBack,
        onQuery = viewModel::setQuery,
        onSearch = viewModel::search,
        onOpen = viewModel::open,
        onCloseDetail = viewModel::closeDetail,
        onField = viewModel::updateField,
        onSave = viewModel::save,
        onLoadMore = viewModel::loadMore,
        onRetry = viewModel::load,
    )
}
