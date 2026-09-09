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

/** Dedicated native shell for Platform Super Admin accounts. */
@Composable
internal fun PlatformAuthorizedShell(
    onLogout: () -> Unit,
    viewModel: PlatformViewModel = hiltViewModel(),
    tenantViewModel: PlatformTenantViewModel = hiltViewModel(),
    groupViewModel: PlatformGroupViewModel = hiltViewModel(),
    settingsViewModel: PlatformSettingsViewModel = hiltViewModel(),
    agentViewModel: PlatformAgentViewModel = hiltViewModel(),
    provisioningViewModel: PlatformProvisioningViewModel = hiltViewModel(),
    billingViewModel: PlatformBillingViewModel = hiltViewModel(),
) {
    val state by viewModel.uiState.collectAsStateWithLifecycle()
    val tenantState by tenantViewModel.uiState.collectAsStateWithLifecycle()
    val groupState by groupViewModel.uiState.collectAsStateWithLifecycle()
    val settingsState by settingsViewModel.uiState.collectAsStateWithLifecycle()
    val agentState by agentViewModel.uiState.collectAsStateWithLifecycle()
    val provisioningState by provisioningViewModel.uiState.collectAsStateWithLifecycle()
    val billingState by billingViewModel.uiState.collectAsStateWithLifecycle()
    var schoolDirectoryOpen by remember { mutableStateOf(false) }
    var provisioningOpen by remember { mutableStateOf(false) }
    var selectedTenantId by remember { mutableStateOf<Long?>(null) }
    var groupDirectoryOpen by remember { mutableStateOf(false) }
    var selectedGroupId by remember { mutableStateOf<Long?>(null) }
    var settingsEditorOpen by remember { mutableStateOf(false) }
    var gatewayEditorOpen by remember { mutableStateOf(false) }
    var agentManagementOpen by remember { mutableStateOf(false) }
    var billingManagementOpen by remember { mutableStateOf(false) }

    LaunchedEffect(Unit) {
        if (state.dashboard == null && !state.isLoading) viewModel.load(PlatformSection.OVERVIEW)
    }
    LaunchedEffect(selectedTenantId) { selectedTenantId?.let(tenantViewModel::load) }
    LaunchedEffect(selectedGroupId) { selectedGroupId?.let(groupViewModel::load) }
    LaunchedEffect(settingsEditorOpen) { if (settingsEditorOpen) settingsViewModel.loadSettings() }
    LaunchedEffect(gatewayEditorOpen) { if (gatewayEditorOpen) settingsViewModel.loadGateways() }
    LaunchedEffect(agentManagementOpen) { if (agentManagementOpen) agentViewModel.load() }
    LaunchedEffect(billingManagementOpen) { if (billingManagementOpen) billingViewModel.load() }

    when {
        provisioningOpen -> PlatformProvisioningScreen(
            state = provisioningState,
            onBack = {
                provisioningOpen = false
                provisioningViewModel.reset()
            },
            onSchoolName = provisioningViewModel::setSchoolName,
            onSlug = provisioningViewModel::setSlug,
            onSubdomain = provisioningViewModel::setSubdomain,
            onSchoolEmail = provisioningViewModel::setSchoolEmail,
            onPhone = provisioningViewModel::setPhone,
            onAddress = provisioningViewModel::setAddress,
            onAdminName = provisioningViewModel::setAdminName,
            onAdminEmail = provisioningViewModel::setAdminEmail,
            onAdminPassword = provisioningViewModel::setAdminPassword,
            onEmploymentStartDate = provisioningViewModel::setEmploymentStartDate,
            onSubmit = {
                provisioningViewModel.submit { tenantId ->
                    provisioningOpen = false
                    schoolDirectoryOpen = false
                    selectedTenantId = tenantId
                    viewModel.load(PlatformSection.SCHOOLS)
                }
            },
        )

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
            onProvision = { provisioningOpen = true },
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

        billingManagementOpen -> PlatformBillingManagementScreen(
            state = billingState,
            onBack = {
                billingManagementOpen = false
                viewModel.load(PlatformSection.BILLING)
            },
            onStatus = billingViewModel::setStatus,
            onTenant = billingViewModel::setTenant,
            onOpenInvoice = billingViewModel::openInvoiceEditor,
            onCloseInvoice = billingViewModel::closeInvoiceEditor,
            onInvoiceTenant = billingViewModel::setInvoiceTenant,
            onCycle = billingViewModel::setCycle,
            onCapacity = billingViewModel::setCapacity,
            onDueDate = billingViewModel::setDueDate,
            onNotes = billingViewModel::setNotes,
            onCreateInvoice = billingViewModel::createInvoice,
            onRequestSettlement = billingViewModel::requestSettlement,
            onCancelSettlement = billingViewModel::cancelSettlement,
            onSettlementMethod = billingViewModel::setSettlementMethod,
            onSettlementReference = billingViewModel::setSettlementReference,
            onConfirmSettlement = billingViewModel::confirmSettlement,
            onRetry = billingViewModel::load,
        )

        settingsEditorOpen -> PlatformSettingsEditorScreen(
            state = settingsState,
            onBack = {
                settingsEditorOpen = false
                viewModel.load(PlatformSection.SETTINGS)
            },
            onValue = settingsViewModel::setValue,
            onMaintenance = settingsViewModel::setMaintenanceMode,
            onReason = settingsViewModel::setReason,
            onSave = settingsViewModel::saveSettings,
            onRetry = settingsViewModel::loadSettings,
        )

        gatewayEditorOpen -> PlatformGatewayEditorScreen(
            state = settingsState,
            onBack = {
                gatewayEditorOpen = false
                settingsViewModel.clearGateway()
                viewModel.load(PlatformSection.GATEWAYS)
            },
            onSelect = settingsViewModel::selectGateway,
            onClear = settingsViewModel::clearGateway,
            onPublic = settingsViewModel::setGatewayPublic,
            onSecret = settingsViewModel::setGatewaySecret,
            onContract = settingsViewModel::setGatewayContract,
            onLive = settingsViewModel::setGatewayLive,
            onReason = settingsViewModel::setReason,
            onSave = settingsViewModel::saveGateway,
            onRetry = settingsViewModel::loadGateways,
        )

        agentManagementOpen -> PlatformAgentManagementScreen(
            state = agentState,
            onBack = {
                agentManagementOpen = false
                viewModel.load(PlatformSection.AGENTS)
            },
            onNew = agentViewModel::newAgent,
            onEdit = agentViewModel::edit,
            onCloseEditor = agentViewModel::closeEditor,
            onName = agentViewModel::setName,
            onEmail = agentViewModel::setEmail,
            onPhone = agentViewModel::setPhone,
            onStateName = agentViewModel::setStateName,
            onCommission = agentViewModel::setCommissionRate,
            onReason = agentViewModel::setReason,
            onSave = agentViewModel::save,
            onRequestDeactivate = agentViewModel::requestDeactivate,
            onActivate = agentViewModel::activate,
            onConfirmDeactivate = agentViewModel::confirmDeactivate,
            onDismissDeactivate = agentViewModel::dismissDeactivate,
            onRetry = agentViewModel::load,
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
                state.section == PlatformSection.SCHOOLS && !state.isLoading && state.tenants != null -> EduCorePrimaryButton(
                    text = "Manage schools",
                    onClick = { schoolDirectoryOpen = true },
                    modifier = Modifier.align(Alignment.BottomEnd).padding(EduCoreSpacing.Lg),
                )
                state.section == PlatformSection.BILLING && !state.isLoading && state.billing != null -> EduCorePrimaryButton(
                    text = "Manage invoices",
                    onClick = { billingManagementOpen = true },
                    modifier = Modifier.align(Alignment.BottomEnd).padding(EduCoreSpacing.Lg),
                )
                state.section == PlatformSection.GROUPS && !state.isLoading && state.groups != null -> EduCorePrimaryButton(
                    text = "Manage groups",
                    onClick = { groupDirectoryOpen = true },
                    modifier = Modifier.align(Alignment.BottomEnd).padding(EduCoreSpacing.Lg),
                )
                state.section == PlatformSection.AGENTS && !state.isLoading && state.agents != null -> EduCorePrimaryButton(
                    text = "Manage agents",
                    onClick = { agentManagementOpen = true },
                    modifier = Modifier.align(Alignment.BottomEnd).padding(EduCoreSpacing.Lg),
                )
                state.section == PlatformSection.SETTINGS && !state.isLoading && state.settings != null -> EduCorePrimaryButton(
                    text = "Edit settings",
                    onClick = { settingsEditorOpen = true },
                    modifier = Modifier.align(Alignment.BottomEnd).padding(EduCoreSpacing.Lg),
                )
                state.section == PlatformSection.GATEWAYS && !state.isLoading && state.gateways != null -> EduCorePrimaryButton(
                    text = "Configure gateways",
                    onClick = { gatewayEditorOpen = true },
                    modifier = Modifier.align(Alignment.BottomEnd).padding(EduCoreSpacing.Lg),
                )
            }
        }
    }
}
