package online.educoreng.educore.presentation

import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.weight
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Checkbox
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import online.educoreng.educore.core.designsystem.component.EduCoreConfirmationDialog
import online.educoreng.educore.core.designsystem.component.EduCoreDashboardCard
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreInfoBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreTabs
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.AdvancedAdminMigrationDto
import online.educoreng.educore.core.network.dto.AdvancedAdminMigrationRequestDto

private enum class AdvancedAdminTab(val label: String) {
    OVERVIEW("Overview"),
    MIGRATIONS("Data Migration"),
    AUDIT("Audit & Security"),
    BACKUP("Backup"),
}

@Composable
internal fun AdvancedAdministrationScreen(
    onBack: () -> Unit,
    viewModel: AdvancedAdministrationViewModel = hiltViewModel(),
) {
    val state by viewModel.uiState.collectAsStateWithLifecycle()
    val overview = state.overview
    var tab by remember { mutableStateOf(AdvancedAdminTab.OVERVIEW) }
    var reconstructId by remember { mutableStateOf<Long?>(null) }
    var backupConfirm by remember { mutableStateOf(false) }
    var decisionRequest by remember { mutableStateOf<AdvancedAdminMigrationRequestDto?>(null) }
    var decisionApprove by remember { mutableStateOf(true) }
    var decisionReason by remember { mutableStateOf("") }

    EduCoreConfirmationDialog(
        visible = reconstructId != null,
        title = "Reconstruct staged blueprint?",
        message = "This analyses the staged migration and rebuilds its proposed school structure. It does not directly overwrite operational school records.",
        confirmLabel = "Reconstruct",
        onConfirm = {
            reconstructId?.let(viewModel::reconstructBlueprint)
            reconstructId = null
        },
        onDismiss = { reconstructId = null },
    )

    EduCoreConfirmationDialog(
        visible = backupConfirm,
        title = "Create database backup?",
        message = "A platform database backup will be generated on the server and older backups will follow the configured retention policy.",
        confirmLabel = "Create backup",
        onConfirm = {
            backupConfirm = false
            viewModel.backup()
        },
        onDismiss = { backupConfirm = false },
    )

    decisionRequest?.let { request ->
        AlertDialog(
            onDismissRequest = { decisionRequest = null },
            title = { Text(if (decisionApprove) "Approve migration request" else "Reject migration request") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    Text(request.batchNumber ?: "Migration request #" + request.id)
                    OutlinedTextField(
                        value = decisionReason,
                        onValueChange = { decisionReason = it.take(1000) },
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text("Decision reason") },
                        minLines = 3,
                        maxLines = 6,
                    )
                }
            },
            confirmButton = {
                TextButton(
                    onClick = {
                        if (decisionApprove) viewModel.approve(request.id, decisionReason)
                        else viewModel.reject(request.id, decisionReason)
                        decisionRequest = null
                        decisionReason = ""
                    },
                    enabled = decisionReason.trim().length >= 10 && !state.isMutating,
                ) {
                    Text(if (decisionApprove) "Approve" else "Reject")
                }
            },
            dismissButton = {
                TextButton(onClick = { decisionRequest = null }) { Text("Cancel") }
            },
        )
    }

    Column(Modifier.fillMaxSize()) {
        EduCorePageHeader(
            title = "Advanced Administration",
            subtitle = "Migration, audit, backup and staged reconstruction tools",
            onBack = onBack,
        )

        if (state.isLoading && overview == null) {
            EduCoreLoadingState(message = "Loading advanced administration")
            return@Column
        }

        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = PaddingValues(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
            state.message?.let { item { EduCoreInfoBanner(it, title = "Advanced administration") } }

            if (overview == null) {
                item {
                    EduCoreEmptyState(
                        title = "Administration workspace unavailable",
                        message = state.errorMessage ?: "Reload the administration workspace.",
                        actionLabel = "Retry",
                        onAction = { viewModel.load() },
                    )
                }
                return@LazyColumn
            }

            item {
                if (overview.scope == "platform" && overview.tenants.isNotEmpty()) {
                    AdvancedAdminTenantMenu(
                        currentId = state.selectedTenantId,
                        tenants = overview.tenants.map { it.id to it.name },
                        enabled = !state.isLoading && !state.isMutating,
                        onSelect = viewModel::selectTenant,
                    )
                    Spacer(Modifier.height(EduCoreSpacing.Md))
                }
                EduCoreTabs(
                    labels = AdvancedAdminTab.entries.map { it.label },
                    selectedIndex = tab.ordinal,
                    onSelected = { tab = AdvancedAdminTab.entries[it] },
                    modifier = Modifier.fillMaxWidth(),
                )
            }

            when (tab) {
                AdvancedAdminTab.OVERVIEW -> {
                    item {
                        EduCoreDashboardCard(Modifier.fillMaxWidth()) {
                            Text("Administration summary", style = MaterialTheme.typography.titleLarge, color = EduCoreColors.Navy900)
                            Spacer(Modifier.height(EduCoreSpacing.Sm))
                            AdvancedAdminValue("Scope", if (overview.scope == "platform") "Platform" else "School")
                            AdvancedAdminValue("Recent migrations", overview.migrations.size.toString())
                            AdvancedAdminValue("Pending migration requests", overview.migrationRequests.count { it.status.startsWith("awaiting_") }.toString())
                            AdvancedAdminValue("Audit events · 7 days", overview.auditStats.events7d.toString())
                            AdvancedAdminValue("Security signals · 7 days", overview.auditStats.securitySignals7d.toString())
                            if (overview.capabilities.runBackup) AdvancedAdminValue("Stored backups", overview.backups.size.toString())
                        }
                    }
                    item {
                        EduCoreDashboardCard(Modifier.fillMaxWidth()) {
                            Text("Safety policy", style = MaterialTheme.typography.titleMedium, color = EduCoreColors.Navy900)
                            Spacer(Modifier.height(EduCoreSpacing.Sm))
                            Text(
                                "Migration sources remain immutable. Integrity verification and blueprint reconstruction operate on staged migration data. Production database backup is restricted to Platform Super Admin.",
                                style = MaterialTheme.typography.bodyMedium,
                                color = EduCoreColors.Slate600,
                            )
                        }
                    }
                }

                AdvancedAdminTab.MIGRATIONS -> {
                    item {
                        AdvancedMigrationCreateCard(
                            state = state,
                            onFiles = viewModel::setSourceFiles,
                            onClearFiles = viewModel::clearSourceFiles,
                            onSubmit = viewModel::createMigration,
                        )
                    }

                    if (overview.migrations.isEmpty()) {
                        item { EduCoreEmptyState("No migration batches", "No data migration batch is available for this scope.") }
                    } else {
                        item { Text("Recent migration batches", style = MaterialTheme.typography.titleMedium, color = EduCoreColors.Navy900) }
                        items(overview.migrations, key = { "migration-" + it.id }) { migration ->
                            AdvancedMigrationCard(
                                migration = migration,
                                busy = state.isMutating,
                                canIngest = overview.capabilities.ingestMigration,
                                canVerify = overview.capabilities.verifyMigration,
                                canReconstruct = overview.capabilities.reconstructBlueprint,
                                onIngest = { viewModel.ingest(migration.id) },
                                onVerify = { viewModel.verify(migration.id) },
                                onReconstruct = { reconstructId = migration.id },
                            )
                        }
                    }

                    if (overview.migrationRequests.isNotEmpty()) {
                        item { Text("Approval workflow", style = MaterialTheme.typography.titleMedium, color = EduCoreColors.Navy900) }
                        items(overview.migrationRequests, key = { "migration-request-" + it.id }) { request ->
                            AdvancedMigrationRequestCard(
                                request = request,
                                busy = state.isMutating,
                                canApproveSchool = overview.capabilities.approveSchool,
                                canApprovePlatform = overview.capabilities.approvePlatform,
                                onApprove = {
                                    decisionApprove = true
                                    decisionReason = ""
                                    decisionRequest = request
                                },
                                onReject = {
                                    decisionApprove = false
                                    decisionReason = ""
                                    decisionRequest = request
                                },
                            )
                        }
                    }
                }

                AdvancedAdminTab.AUDIT -> {
                    item {
                        EduCoreDashboardCard(Modifier.fillMaxWidth()) {
                            Text("Audit & security · last 7 days", style = MaterialTheme.typography.titleLarge, color = EduCoreColors.Navy900)
                            Spacer(Modifier.height(EduCoreSpacing.Sm))
                            AdvancedAdminValue("Events", overview.auditStats.events7d.toString())
                            AdvancedAdminValue("Unique actors", overview.auditStats.uniqueActors7d.toString())
                            AdvancedAdminValue("Security signals", overview.auditStats.securitySignals7d.toString())
                        }
                    }
                    if (overview.audit.isEmpty()) {
                        item { EduCoreEmptyState("No audit events", "No audit event is available for the selected scope.") }
                    } else {
                        items(overview.audit, key = { "audit-" + it.id }) { event ->
                            EduCoreDashboardCard(Modifier.fillMaxWidth()) {
                                Text(event.action.replace('_', ' '), fontWeight = FontWeight.SemiBold, color = EduCoreColors.Ink900)
                                event.actor?.let { AdvancedAdminValue("Actor", it) }
                                event.tenant?.let { AdvancedAdminValue("School", it) }
                                event.reason?.takeIf(String::isNotBlank)?.let { AdvancedAdminValue("Reason", it) }
                                event.createdAt?.let { AdvancedAdminValue("Time", it) }
                            }
                        }
                    }
                }

                AdvancedAdminTab.BACKUP -> {
                    if (!overview.capabilities.runBackup) {
                        item {
                            EduCoreInfoBanner(
                                title = "Platform-only capability",
                                message = "Database backup is available only to the Platform Super Admin because the database contains multiple schools.",
                            )
                        }
                    } else {
                        item {
                            EduCoreDashboardCard(Modifier.fillMaxWidth()) {
                                Text("Database backup", style = MaterialTheme.typography.titleLarge, color = EduCoreColors.Navy900)
                                Spacer(Modifier.height(EduCoreSpacing.Sm))
                                Text(
                                    "Create a server-side MySQL backup. The mobile app receives only backup metadata; database files are never downloaded to the device.",
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = EduCoreColors.Slate600,
                                )
                                Spacer(Modifier.height(EduCoreSpacing.Md))
                                EduCorePrimaryButton(
                                    text = "Create database backup",
                                    onClick = { backupConfirm = true },
                                    modifier = Modifier.fillMaxWidth(),
                                    enabled = !state.isMutating,
                                    loading = state.isMutating,
                                )
                            }
                        }
                        if (overview.backups.isEmpty()) {
                            item { EduCoreEmptyState("No stored backups", "No server backup is currently listed.") }
                        } else {
                            items(overview.backups, key = { it.name }) { backup ->
                                EduCoreDashboardCard(Modifier.fillMaxWidth()) {
                                    Text(backup.name, fontWeight = FontWeight.SemiBold)
                                    AdvancedAdminValue("Size", formatBytes(backup.size))
                                    backup.createdAt?.let { AdvancedAdminValue("Created", it) }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun AdvancedMigrationCreateCard(
    state: AdvancedAdminUiState,
    onFiles: (List<android.net.Uri>) -> Unit,
    onClearFiles: () -> Unit,
    onSubmit: (Long?, String, String, String, String, Set<String>) -> Unit,
) {
    val overview = state.overview ?: return
    var tenantId by remember(state.selectedTenantId) { mutableStateOf(state.selectedTenantId) }
    var direction by remember { mutableStateOf("inbound") }
    var migrationType by remember { mutableStateOf("standard_import") }
    var sourcePlatform by remember { mutableStateOf("") }
    var justification by remember { mutableStateOf("") }
    var dataScope by remember { mutableStateOf(setOf("students", "academics")) }

    val picker = rememberLauncherForActivityResult(ActivityResultContracts.OpenMultipleDocuments()) { uris ->
        if (uris.isNotEmpty()) onFiles(uris)
    }

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text("Create migration batch", style = MaterialTheme.typography.titleLarge, color = EduCoreColors.Navy900)
        Spacer(Modifier.height(EduCoreSpacing.Sm))

        if (overview.scope == "platform") {
            AdvancedAdminTenantMenu(
                currentId = tenantId,
                tenants = overview.tenants.map { it.id to it.name },
                enabled = !state.isMutating,
                onSelect = { tenantId = it },
            )
            Spacer(Modifier.height(EduCoreSpacing.Sm))
        }

        AdvancedAdminStringMenu(
            label = "Direction",
            current = direction.replace('_', ' ').replaceFirstChar(Char::uppercase),
            options = listOf("inbound" to "Inbound", "outbound" to "Outbound"),
            enabled = !state.isMutating,
            onSelect = { direction = it },
        )
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        AdvancedAdminStringMenu(
            label = "Migration type",
            current = migrationType.replace('_', ' ').replaceFirstChar(Char::uppercase),
            options = listOf(
                "standard_import" to "Standard import",
                "full_migration" to "Full migration",
                "full_export" to "Full export",
                "selective_export" to "Selective export",
            ),
            enabled = !state.isMutating,
            onSelect = { migrationType = it },
        )
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        OutlinedTextField(
            value = sourcePlatform,
            onValueChange = { sourcePlatform = it.take(120) },
            modifier = Modifier.fillMaxWidth(),
            label = { Text("Source platform/system") },
            enabled = !state.isMutating,
            singleLine = true,
        )
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        OutlinedTextField(
            value = justification,
            onValueChange = { justification = it.take(2000) },
            modifier = Modifier.fillMaxWidth(),
            label = { Text("Business justification") },
            enabled = !state.isMutating,
            minLines = 3,
            maxLines = 6,
        )
        Spacer(Modifier.height(EduCoreSpacing.Md))
        Text("Data scope", style = MaterialTheme.typography.titleSmall)
        listOf("students", "guardians", "staff", "academics", "attendance", "finance", "configuration").forEach { scope ->
            Row(modifier = Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                Checkbox(
                    checked = scope in dataScope,
                    onCheckedChange = { checked ->
                        dataScope = if (checked) dataScope + scope else dataScope - scope
                    },
                    enabled = !state.isMutating,
                )
                Text(scope.replaceFirstChar(Char::uppercase))
            }
        }

        Spacer(Modifier.height(EduCoreSpacing.Sm))
        OutlinedButton(
            onClick = { picker.launch(arrayOf("*/*")) },
            modifier = Modifier.fillMaxWidth(),
            enabled = !state.isMutating,
        ) {
            Text(if (state.sourceFiles.isEmpty()) "Select source files" else "Replace source files")
        }
        state.sourceFiles.forEach { file ->
            Text(
                "• " + file.name + (file.size?.let { " · " + formatBytes(it) } ?: ""),
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Slate600,
            )
        }
        if (state.sourceFiles.isNotEmpty()) {
            TextButton(onClick = onClearFiles, enabled = !state.isMutating) { Text("Clear selected files") }
        }

        Spacer(Modifier.height(EduCoreSpacing.Md))
        EduCorePrimaryButton(
            text = "Create migration request",
            onClick = { onSubmit(tenantId, direction, migrationType, sourcePlatform, justification, dataScope) },
            modifier = Modifier.fillMaxWidth(),
            enabled = !state.isMutating,
            loading = state.isMutating,
        )
    }
}

@Composable
private fun AdvancedMigrationCard(
    migration: AdvancedAdminMigrationDto,
    busy: Boolean,
    canIngest: Boolean,
    canVerify: Boolean,
    canReconstruct: Boolean,
    onIngest: () -> Unit,
    onVerify: () -> Unit,
    onReconstruct: () -> Unit,
) {
    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text(migration.batchNumber, style = MaterialTheme.typography.titleMedium, color = EduCoreColors.Navy900)
        migration.tenantName?.let { AdvancedAdminValue("School", it) }
        AdvancedAdminValue("Status", migration.status.replace('_', ' '))
        AdvancedAdminValue("Type", migration.migrationType.replace('_', ' '))
        AdvancedAdminValue("Source", migration.sourceSystem ?: "Not specified")
        AdvancedAdminValue("Files", migration.filesCount.toString())
        AdvancedAdminValue("Datasets", migration.datasetsCount.toString())
        AdvancedAdminValue("Issues", migration.issuesCount.toString())
        if (migration.totalSourceRows > 0) AdvancedAdminValue("Source rows", migration.totalSourceRows.toString())
        if (migration.totalFailed > 0) AdvancedAdminValue("Failed rows", migration.totalFailed.toString())

        if (canIngest) {
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreSecondaryButton("Inspect & stage files", onIngest, Modifier.fillMaxWidth(), enabled = !busy)
        }
        if (canVerify) {
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreSecondaryButton("Verify source integrity", onVerify, Modifier.fillMaxWidth(), enabled = !busy)
        }
        if (canReconstruct) {
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreSecondaryButton("Reconstruct staged blueprint", onReconstruct, Modifier.fillMaxWidth(), enabled = !busy)
        }
    }
}

@Composable
private fun AdvancedMigrationRequestCard(
    request: AdvancedAdminMigrationRequestDto,
    busy: Boolean,
    canApproveSchool: Boolean,
    canApprovePlatform: Boolean,
    onApprove: () -> Unit,
    onReject: () -> Unit,
) {
    val canApprove = when (request.status) {
        "awaiting_school_approval" -> canApproveSchool
        "awaiting_platform_approval" -> canApprovePlatform
        else -> false
    }

    EduCoreDashboardCard(Modifier.fillMaxWidth()) {
        Text(request.batchNumber ?: "Request #" + request.id, style = MaterialTheme.typography.titleMedium, color = EduCoreColors.Navy900)
        AdvancedAdminValue("Status", request.status.replace('_', ' '))
        request.riskLevel?.let { AdvancedAdminValue("Risk", it) }
        if (request.dataScope.isNotEmpty()) AdvancedAdminValue("Scope", request.dataScope.joinToString(", "))
        request.businessJustification?.let { AdvancedAdminValue("Justification", it) }
        request.decisionReason?.let { AdvancedAdminValue("Decision", it) }

        if (canApprove) {
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCorePrimaryButton("Approve request", onApprove, Modifier.fillMaxWidth(), enabled = !busy)
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCoreSecondaryButton("Reject request", onReject, Modifier.fillMaxWidth(), enabled = !busy)
        }
    }
}

@Composable
private fun AdvancedAdminTenantMenu(
    currentId: Long?,
    tenants: List<Pair<Long, String>>,
    enabled: Boolean,
    onSelect: (Long?) -> Unit,
) {
    var expanded by remember { mutableStateOf(false) }
    val current = tenants.firstOrNull { it.first == currentId }?.second ?: "All schools"
    Box(Modifier.fillMaxWidth()) {
        OutlinedButton(
            onClick = { expanded = true },
            modifier = Modifier.fillMaxWidth(),
            enabled = enabled,
        ) {
            Text("School: " + current, modifier = Modifier.weight(1f))
        }
        DropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
            DropdownMenuItem(
                text = { Text("All schools") },
                onClick = {
                    expanded = false
                    onSelect(null)
                },
            )
            tenants.forEach { pair ->
                DropdownMenuItem(
                    text = { Text(pair.second) },
                    onClick = {
                        expanded = false
                        onSelect(pair.first)
                    },
                )
            }
        }
    }
}

@Composable
private fun AdvancedAdminStringMenu(
    label: String,
    current: String,
    options: List<Pair<String, String>>,
    enabled: Boolean,
    onSelect: (String) -> Unit,
) {
    var expanded by remember { mutableStateOf(false) }
    Column(Modifier.fillMaxWidth()) {
        Text(label, style = MaterialTheme.typography.labelMedium, color = EduCoreColors.Slate600)
        Spacer(Modifier.height(EduCoreSpacing.Xs))
        Box(Modifier.fillMaxWidth()) {
            OutlinedButton(
                onClick = { expanded = true },
                modifier = Modifier.fillMaxWidth(),
                enabled = enabled,
            ) {
                Text(current, modifier = Modifier.weight(1f))
            }
            DropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
                options.forEach { pair ->
                    DropdownMenuItem(
                        text = { Text(pair.second) },
                        onClick = {
                            expanded = false
                            onSelect(pair.first)
                        },
                    )
                }
            }
        }
    }
}

@Composable
private fun AdvancedAdminValue(label: String, value: String) {
    Row(
        modifier = Modifier.fillMaxWidth().padding(vertical = EduCoreSpacing.Xs),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.Top,
    ) {
        Text(label, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600, modifier = Modifier.weight(0.42f))
        Text(value, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Ink900, modifier = Modifier.weight(0.58f))
    }
}

private fun formatBytes(bytes: Long): String = when {
    bytes >= 1024L * 1024L * 1024L -> String.format("%.1f GB", bytes / (1024.0 * 1024.0 * 1024.0))
    bytes >= 1024L * 1024L -> String.format("%.1f MB", bytes / (1024.0 * 1024.0))
    bytes >= 1024L -> String.format("%.1f KB", bytes / 1024.0)
    else -> bytes.toString() + " B"
}
