package online.educoreng.educore.presentation

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

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
    tenantViewModel: PlatformTenantViewModel = hiltViewModel(),
    groupViewModel: PlatformGroupViewModel = hiltViewModel(),
) {
    val state by viewModel.uiState.collectAsStateWithLifecycle()
    val tenantState by tenantViewModel.uiState.collectAsStateWithLifecycle()
    val groupState by groupViewModel.uiState.collectAsStateWithLifecycle()
    var schoolDirectoryOpen by remember { mutableStateOf(false) }
    var selectedTenantId by remember { mutableStateOf<Long?>(null) }
    var groupDirectoryOpen by remember { mutableStateOf(false) }
    var selectedGroupId by remember { mutableStateOf<Long?>(null) }

    LaunchedEffect(Unit) {
        if (state.dashboard == null && !state.isLoading) {
            viewModel.load(PlatformSection.OVERVIEW)
        }
    }

    LaunchedEffect(selectedTenantId) {
        selectedTenantId?.let(tenantViewModel::load)
    }

    LaunchedEffect(selectedGroupId) {
        selectedGroupId?.let(groupViewModel::load)
    }

    when {
        selectedTenantId != null -> PlatformTenantScreen(
            state = tenantState,
            onBack = {
                selectedTenantId = null
                viewModel.load(PlatformSection.SCHOOLS)
            },
            onReason = tenantViewModel::setReason,
            onMonths = tenantViewModel::setExtensionMonths,
            onStatus = tenantViewModel::requestStatus,
            onExtend = tenantViewModel::requestExtension,
            onConfirm = tenantViewModel::confirm,
            onDismissConfirmation = tenantViewModel::dismissConfirmation,
            onRetry = { selectedTenantId?.let(tenantViewModel::load) },
        )

        schoolDirectoryOpen -> PlatformSchoolDirectoryScreen(
            schools = state.tenants?.tenants.orEmpty(),
            onBack = { schoolDirectoryOpen = false },
            onOpen = { selectedTenantId = it },
        )

        selectedGroupId != null -> PlatformGroupDetailScreen(
            state = groupState,
            onBack = {
                selectedGroupId = null
                viewModel.load(PlatformSection.GROUPS)
            },
            onAddMember = groupViewModel::addMember,
            onRequestLead = groupViewModel::requestLead,
            onRequestRemove = groupViewModel::requestRemove,
            onConfirm = groupViewModel::confirm,
            onDismissConfirmation = groupViewModel::dismissConfirmation,
            onRetry = { selectedGroupId?.let(groupViewModel::load) },
        )

        groupDirectoryOpen -> PlatformGroupDirectoryScreen(
            groups = state.groups?.groups.orEmpty(),
            state = groupState,
            onBack = { groupDirectoryOpen = false },
            onOpen = { selectedGroupId = it },
            onOpenCreate = groupViewModel::openCreate,
            onCloseCreate = groupViewModel::closeCreate,
            onName = groupViewModel::setNewGroupName,
            onDescription = groupViewModel::setNewGroupDescription,
            onCreate = {
                groupViewModel.createGroup { id ->
                    selectedGroupId = id
                    viewModel.load(PlatformSection.GROUPS)
                }
            },
        )

        else -> Box(Modifier.fillMaxSize()) {
            PlatformScreen(
                state = state,
                onBack = null,
                onSection = viewModel::selectSection,
                onSearchChange = viewModel::setSearch,
                onSearch = viewModel::searchSchools,
                onStatus = viewModel::setStatus,
                onEditReply = viewModel::editReply,
                onReplyDraft = viewModel::setReplyDraft,
                onSendReply = viewModel::sendReply,
                onCancelReply = viewModel::cancelReply,
                onRequestCloseTicket = viewModel::requestCloseSupport,
                onOpenBroadcastEditor = viewModel::openBroadcastEditor,
                onCloseBroadcastEditor = viewModel::closeBroadcastEditor,
                onBroadcastTitle = viewModel::setBroadcastTitle,
                onBroadcastBody = viewModel::setBroadcastBody,
                onBroadcastTarget = viewModel::setBroadcastTarget,
                onBroadcastExpiresAt = viewModel::setBroadcastExpiresAt,
                onCreateBroadcast = viewModel::createBroadcast,
                onRequestExpireBroadcast = viewModel::requestExpireBroadcast,
                onConfirmPendingAction = viewModel::confirmPendingAction,
                onDismissPendingAction = viewModel::dismissPendingAction,
                onRetry = viewModel::load,
                onLogout = onLogout,
            )

            when {
                state.section == PlatformSection.SCHOOLS && !state.isLoading && state.tenants != null -> {
                    EduCorePrimaryButton(
                        text = "Manage schools",
                        onClick = { schoolDirectoryOpen = true },
                        modifier = Modifier.align(Alignment.BottomEnd).padding(EduCoreSpacing.Lg),
                    )
                }
                state.section == PlatformSection.GROUPS && !state.isLoading && state.groups != null -> {
                    EduCorePrimaryButton(
                        text = "Manage groups",
                        onClick = { groupDirectoryOpen = true },
                        modifier = Modifier.align(Alignment.BottomEnd).padding(EduCoreSpacing.Lg),
                    )
                }
            }
        }
    }
}
