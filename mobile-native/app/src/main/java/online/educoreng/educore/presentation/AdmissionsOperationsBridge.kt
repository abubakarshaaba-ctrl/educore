package online.educoreng.educore.presentation

import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle

/** Routes the operations descriptor into the dedicated admissions API/workspace. */
@Composable
internal fun AdmissionsOperationsScreen(
    state: OperationsUiState,
    onBack: () -> Unit,
) {
    val viewModel: AdmissionsViewModel = hiltViewModel()
    val admissionsState by viewModel.uiState.collectAsStateWithLifecycle()

    LaunchedEffect(state.workspace?.module?.key) {
        if (admissionsState.workspace == null && !admissionsState.isLoading) {
            viewModel.load()
        }
    }

    AdmissionsScreen(
        state = admissionsState,
        onBack = onBack,
        onQuery = viewModel::setSearch,
        onSearch = viewModel::search,
        onStatusFilter = viewModel::selectStatus,
        onOpen = viewModel::open,
        onCloseDetail = viewModel::closeDetail,
        onLoadMore = viewModel::loadMore,
        onStartCreate = viewModel::startCreate,
        onCloseCreate = viewModel::closeCreate,
        onCreateField = viewModel::updateCreate,
        onCreateGender = viewModel::selectCreateGender,
        onCreateClassLevel = viewModel::selectCreateClassLevel,
        onCreate = viewModel::create,
        onStatusDraft = viewModel::setStatusDraft,
        onClassArmDraft = viewModel::setClassArmDraft,
        onReviewNotes = viewModel::setReviewNotes,
        onSaveStatus = viewModel::saveStatus,
        onRetry = viewModel::load,
    )
}
