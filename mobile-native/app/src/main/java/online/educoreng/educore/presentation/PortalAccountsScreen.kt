package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreConfirmationDialog
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreMetricCard
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTabs
import online.educoreng.educore.core.designsystem.component.EduCoreTextField
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.PortalAccountStateDto

@Composable
internal fun PortalAccountsScreen(
    state: PortalAccountsUiState,
    onBack: () -> Unit,
    onTab: (PortalAccountsTab) -> Unit,
    onQuery: (String) -> Unit,
    onCreateStudent: (Long, String, String?) -> Unit,
    onCreateParent: (Long, String, String?) -> Unit,
    onResetPassword: (Long, String) -> Unit,
    onToggle: (Long, String, Boolean) -> Unit,
    onBulkStudents: () -> Unit,
    onEmail: (String) -> Unit,
    onPassword: (String) -> Unit,
    onSaveEditor: () -> Unit,
    onCloseEditor: () -> Unit,
    onConfirm: () -> Unit,
    onCancelConfirm: () -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.workspace == null) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading portal accounts")
    }
    val workspace = state.workspace ?: return EduCoreErrorState(
        message = state.errorMessage ?: "Portal accounts are unavailable.",
        modifier = Modifier.fillMaxSize(),
        onRetry = onRetry,
    )

    if (state.editorOpen) {
        PortalAccountEditor(
            state = state,
            onBack = onCloseEditor,
            onEmail = onEmail,
            onPassword = onPassword,
            onSave = onSaveEditor,
        )
        return
    }

    val query = state.query.trim()
    val students = workspace.students.filter {
        query.isBlank() || it.name.contains(query, true) ||
            it.admissionNumber.orEmpty().contains(query, true) ||
            it.account?.email.orEmpty().contains(query, true)
    }
    val parents = workspace.guardians.filter {
        query.isBlank() || it.name.contains(query, true) ||
            it.email.orEmpty().contains(query, true) ||
            it.phone.orEmpty().contains(query, true)
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Portal Accounts",
                subtitle = "Student and parent login identities",
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let { item { Text(it, color = EduCoreColors.Success700) } }
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreMetricCard("Student accounts", workspace.summary.studentAccounts.toString(), Modifier.weight(1f), tone = EduCoreTone.Brand)
                EduCoreMetricCard("Parent accounts", workspace.summary.guardianAccounts.toString(), Modifier.weight(1f), tone = EduCoreTone.Info)
                EduCoreMetricCard("Inactive", workspace.summary.inactiveAccounts.toString(), Modifier.weight(1f), tone = if (workspace.summary.inactiveAccounts > 0) EduCoreTone.Warning else EduCoreTone.Success)
            }
        }
        item {
            EduCoreTabs(
                labels = listOf("Students", "Parents"),
                selectedIndex = if (state.tab == PortalAccountsTab.STUDENTS) 0 else 1,
                onSelected = { onTab(if (it == 0) PortalAccountsTab.STUDENTS else PortalAccountsTab.PARENTS) },
            )
        }
        item { EduCoreSearchBar(state.query, onQuery, "Search name, ID, email or phone") }
        if (state.canManage && state.tab == PortalAccountsTab.STUDENTS) {
            item {
                EduCoreSecondaryButton(
                    text = "Create missing student accounts",
                    onClick = onBulkStudents,
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !state.isMutating,
                )
            }
        }

        if (state.tab == PortalAccountsTab.STUDENTS) {
            if (students.isEmpty()) {
                item { EduCoreEmptyState("No matching students", "No student portal records match this search.") }
            } else {
                items(students, key = { "portal-student-${it.id}" }) { row ->
                    PortalAccountCard(
                        name = row.name,
                        subtitle = listOfNotNull(row.admissionNumber, row.classLevel, row.className).joinToString(" · "),
                        account = row.account,
                        canManage = state.canManage,
                        onCreate = { onCreateStudent(row.id, row.name, row.email) },
                        onReset = { row.account?.let { onResetPassword(it.userId, row.name) } },
                        onToggle = { row.account?.let { onToggle(it.userId, row.name, it.active) } },
                    )
                }
            }
        } else {
            if (parents.isEmpty()) {
                item { EduCoreEmptyState("No matching parents", "No parent portal records match this search.") }
            } else {
                items(parents, key = { "portal-parent-${it.id}" }) { row ->
                    PortalAccountCard(
                        name = row.name,
                        subtitle = listOfNotNull(row.phone, row.children.takeIf { it.isNotEmpty() }?.joinToString(", ") { it.name }).joinToString(" · "),
                        account = row.account,
                        canManage = state.canManage,
                        onCreate = { onCreateParent(row.id, row.name, row.email) },
                        onReset = { row.account?.let { onResetPassword(it.userId, row.name) } },
                        onToggle = { row.account?.let { onToggle(it.userId, row.name, it.active) } },
                    )
                }
            }
        }
    }

    val pending = state.pendingAction
    EduCoreConfirmationDialog(
        visible = pending != null,
        title = when (pending) {
            PortalAccountPendingAction.BULK_STUDENTS -> "Create missing student accounts?"
            PortalAccountPendingAction.TOGGLE_ACCESS -> if (state.pendingCurrentlyActive) "Disable portal access?" else "Enable portal access?"
            null -> "Confirm"
        },
        message = when (pending) {
            PortalAccountPendingAction.BULK_STUDENTS -> "EduCore will create accounts only for active students with a usable, unique email address. Passwords will not be displayed; users must use Forgot Password."
            PortalAccountPendingAction.TOGGLE_ACCESS -> if (state.pendingCurrentlyActive) {
                "Disable ${state.pendingTargetName}? Existing browser, mobile and push sessions will be revoked."
            } else "Enable portal access for ${state.pendingTargetName}?"
            null -> ""
        },
        confirmLabel = "Confirm",
        onConfirm = onConfirm,
        onDismiss = onCancelConfirm,
        destructive = pending == PortalAccountPendingAction.TOGGLE_ACCESS && state.pendingCurrentlyActive,
    )
}

@Composable
private fun PortalAccountCard(
    name: String,
    subtitle: String,
    account: PortalAccountStateDto?,
    canManage: Boolean,
    onCreate: () -> Unit,
    onReset: () -> Unit,
    onToggle: () -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                Column(Modifier.weight(1f)) {
                    Text(name, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
                    if (subtitle.isNotBlank()) Text(subtitle, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    account?.email?.let { Text(it, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant) }
                }
                EduCoreStatusBadge(
                    text = when {
                        account == null -> "No account"
                        account.active -> "Active"
                        else -> "Disabled"
                    },
                    tone = when {
                        account == null -> EduCoreTone.Neutral
                        account.active -> EduCoreTone.Success
                        else -> EduCoreTone.Warning
                    },
                )
            }
            if (canManage) {
                if (account == null) {
                    EduCorePrimaryButton("Create account", onCreate, Modifier.fillMaxWidth())
                } else {
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                        EduCoreSecondaryButton("Reset password", onReset, Modifier.weight(1f))
                        EduCoreSecondaryButton(if (account.active) "Disable" else "Enable", onToggle, Modifier.weight(1f))
                    }
                }
            }
        }
    }
}

@Composable
private fun PortalAccountEditor(
    state: PortalAccountsUiState,
    onBack: () -> Unit,
    onEmail: (String) -> Unit,
    onPassword: (String) -> Unit,
    onSave: () -> Unit,
) {
    val creating = state.editorMode != PortalAccountEditorMode.RESET_PASSWORD
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = if (creating) "Create Portal Account" else "Reset Password",
                subtitle = state.editorTargetName,
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        if (creating) {
            item { EduCoreTextField(state.emailDraft, onEmail, "Email address", Modifier.fillMaxWidth(), enabled = !state.isMutating) }
        }
        item {
            EduCoreTextField(
                value = state.passwordDraft,
                onValueChange = onPassword,
                label = if (creating) "Temporary password" else "New password",
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isMutating,
                supportingText = "Minimum 8 characters. EduCore does not store or redisplay this plaintext password.",
            )
        }
        item {
            EduCorePrimaryButton(
                text = if (creating) "Create account" else "Reset password",
                onClick = onSave,
                modifier = Modifier.fillMaxWidth(),
                enabled = if (creating) state.createValid else state.resetValid,
                loading = state.isMutating,
            )
        }
        item { EduCoreSecondaryButton("Cancel", onBack, Modifier.fillMaxWidth(), enabled = !state.isMutating) }
    }
}
