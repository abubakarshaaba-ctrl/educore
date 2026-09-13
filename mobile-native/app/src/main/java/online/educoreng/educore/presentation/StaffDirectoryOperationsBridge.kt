package online.educoreng.educore.presentation

import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle

@Composable
internal fun StaffDirectoryOperationsScreen(onBack: () -> Unit) {
    val viewModel: StaffDirectoryViewModel = hiltViewModel()
    val state by viewModel.uiState.collectAsStateWithLifecycle()

    LaunchedEffect(Unit) { viewModel.load() }

    StaffDirectoryScreen(
        state = state,
        onBack = onBack,
        onQuery = viewModel::setQuery,
        onFilter = viewModel::setFilter,
        onRefresh = viewModel::load,
        onLoadMore = viewModel::loadMore,
        onToggleActive = viewModel::setActive,
        onStartCreate = viewModel::startCreate,
        onCloseCreate = viewModel::closeCreate,
        onCreateName = viewModel::setCreateName,
        onCreateEmail = viewModel::setCreateEmail,
        onCreatePhone = viewModel::setCreatePhone,
        onCreateRole = viewModel::setCreateRole,
        onCreatePassword = viewModel::setCreatePassword,
        onCreate = viewModel::createStaff,
    )
}
