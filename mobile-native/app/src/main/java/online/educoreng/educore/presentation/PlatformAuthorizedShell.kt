package online.educoreng.educore.presentation

import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle

/**
 * Dedicated native shell for Platform Super Admin accounts.
 *
 * Platform administration has a different information hierarchy from tenant
 * staff/parent/student workspaces. Keeping it outside AuthorizedShell prevents
 * platform modules from accidentally falling through tenant operations or web
 * handoff paths while the native overhaul is in progress.
 */
@Composable
internal fun PlatformAuthorizedShell(
    onLogout: () -> Unit,
    viewModel: PlatformViewModel = hiltViewModel(),
) {
    val state by viewModel.uiState.collectAsStateWithLifecycle()

    LaunchedEffect(Unit) {
        if (state.dashboard == null && !state.isLoading) {
            viewModel.load(PlatformSection.OVERVIEW)
        }
    }

    PlatformScreen(
        state = state,
        onBack = null,
        onSection = viewModel::selectSection,
        onSearchChange = viewModel::setSearch,
        onSearch = viewModel::searchSchools,
        onStatus = viewModel::setStatus,
        onRetry = viewModel::load,
        onLogout = onLogout,
    )
}
