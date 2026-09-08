package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.EventAvailable
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreFilterChip
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreMetricCard
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTabs
import online.educoreng.educore.core.designsystem.component.EduCoreTextField
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.AcademicSessionAdminDto
import online.educoreng.educore.core.network.dto.AcademicTermAdminDto

@Composable
internal fun NativeAcademicCycleScreen(
    state: AcademicCycleUiState,
    onBack: () -> Unit,
    onTab: (AcademicCycleTab) -> Unit,
    onCreateSession: () -> Unit,
    onEditSession: (AcademicSessionAdminDto) -> Unit,
    onSessionName: (String) -> Unit,
    onSessionActivate: (Boolean) -> Unit,
    onSaveSession: () -> Unit,
    onCreateTerm: () -> Unit,
    onEditTerm: (AcademicTermAdminDto) -> Unit,
    onTermSession: (Long?) -> Unit,
    onTermName: (String) -> Unit,
    onTermStart: (String) -> Unit,
    onTermEnd: (String) -> Unit,
    onNextTerm: (String) -> Unit,
    onTermActivate: (Boolean) -> Unit,
    onSaveTerm: () -> Unit,
    onCloseEditor: () -> Unit,
    onActivateSession: (AcademicSessionAdminDto) -> Unit,
    onCloseSession: (AcademicSessionAdminDto) -> Unit,
    onDeleteSession: (AcademicSessionAdminDto) -> Unit,
    onActivateTerm: (AcademicTermAdminDto) -> Unit,
    onCloseTerm: (AcademicTermAdminDto) -> Unit,
    onDeleteTerm: (AcademicTermAdminDto) -> Unit,
    onCancelAction: () -> Unit,
    onConfirmAction: () -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.workspace == null) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading academic cycle")
    }
    state.workspace ?: return EduCoreErrorState(
        message = state.errorMessage ?: "Academic cycle is unavailable.",
        modifier = Modifier.fillMaxSize(),
        onRetry = onRetry,
    )

    when (state.editor) {
        AcademicCycleEditor.SESSION -> AcademicSessionEditor(
            state = state,
            onBack = onCloseEditor,
            onName = onSessionName,
            onActivate = onSessionActivate,
            onSave = onSaveSession,
        )
        AcademicCycleEditor.TERM -> AcademicTermEditor(
            state = state,
            onBack = onCloseEditor,
            onSession = onTermSession,
            onName = onTermName,
            onStart = onTermStart,
            onEnd = onTermEnd,
            onNextTerm = onNextTerm,
            onActivate = onTermActivate,
            onSave = onSaveTerm,
        )
        AcademicCycleEditor.NONE -> AcademicCycleRegister(
            state = state,
            onBack = onBack,
            onTab = onTab,
            onCreateSession = onCreateSession,
            onEditSession = onEditSession,
            onCreateTerm = onCreateTerm,
            onEditTerm = onEditTerm,
            onActivateSession = onActivateSession,
            onCloseSession = onCloseSession,
            onDeleteSession = onDeleteSession,
            onActivateTerm = onActivateTerm,
            onCloseTerm = onCloseTerm,
            onDeleteTerm = onDeleteTerm,
            onCancelAction = onCancelAction,
            onConfirmAction = onConfirmAction,
        )
    }
}

@Composable
private fun AcademicCycleRegister(
    state: AcademicCycleUiState,
    onBack: () -> Unit,
    onTab: (AcademicCycleTab) -> Unit,
    onCreateSession: () -> Unit,
    onEditSession: (AcademicSessionAdminDto) -> Unit,
    onCreateTerm: () -> Unit,
    onEditTerm: (AcademicTermAdminDto) -> Unit,
    onActivateSession: (AcademicSessionAdminDto) -> Unit,
    onCloseSession: (AcademicSessionAdminDto) -> Unit,
    onDeleteSession: (AcademicSessionAdminDto) -> Unit,
    onActivateTerm: (AcademicTermAdminDto) -> Unit,
    onCloseTerm: (AcademicTermAdminDto) -> Unit,
    onDeleteTerm: (AcademicTermAdminDto) -> Unit,
    onCancelAction: () -> Unit,
    onConfirmAction: () -> Unit,
) {
    val workspace = state.workspace ?: return
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Academic Cycle",
                subtitle = "Sessions, terms and controlled lifecycle transitions",
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let { item { Text(it, color = EduCoreColors.Success700) } }
        item { CurrentAcademicContextCard(state) }
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreMetricCard(
                    label = "Sessions",
                    value = workspace.metrics.sessions.toString(),
                    modifier = Modifier.weight(1f),
                    icon = Icons.Default.CalendarMonth,
                    tone = EduCoreTone.Brand,
                )
                EduCoreMetricCard(
                    label = "Terms",
                    value = workspace.metrics.terms.toString(),
                    modifier = Modifier.weight(1f),
                    icon = Icons.Default.EventAvailable,
                    tone = EduCoreTone.Info,
                )
            }
        }
        item {
            EduCoreTabs(
                labels = listOf("Sessions", "Terms"),
                selectedIndex = if (state.tab == AcademicCycleTab.SESSIONS) 0 else 1,
                onSelected = { onTab(if (it == 0) AcademicCycleTab.SESSIONS else AcademicCycleTab.TERMS) },
            )
        }
        if (state.canManage) {
            item {
                EduCorePrimaryButton(
                    text = if (state.tab == AcademicCycleTab.SESSIONS) "Add academic session" else "Add term",
                    onClick = if (state.tab == AcademicCycleTab.SESSIONS) onCreateSession else onCreateTerm,
                    modifier = Modifier.fillMaxWidth(),
                )
            }
        }

        if (state.tab == AcademicCycleTab.SESSIONS) {
            if (workspace.sessions.isEmpty()) {
                item { EduCoreEmptyState("No academic sessions", "Create the first academic session to establish the school cycle.") }
            } else {
                items(workspace.sessions, key = { "academic-session-${it.id}" }) { session ->
                    AcademicSessionCardNative(
                        session = session,
                        canManage = state.canManage,
                        checkingReadiness = state.isCheckingReadiness,
                        onEdit = { onEditSession(session) },
                        onActivate = { onActivateSession(session) },
                        onClose = { onCloseSession(session) },
                        onDelete = { onDeleteSession(session) },
                    )
                }
            }
        } else {
            if (workspace.terms.isEmpty()) {
                item { EduCoreEmptyState("No academic terms", "Create terms inside an academic session to continue setup.") }
            } else {
                items(workspace.terms, key = { "academic-term-${it.id}" }) { term ->
                    AcademicTermCardNative(
                        term = term,
                        canManage = state.canManage,
                        checkingReadiness = state.isCheckingReadiness,
                        onEdit = { onEditTerm(term) },
                        onActivate = { onActivateTerm(term) },
                        onClose = { onCloseTerm(term) },
                        onDelete = { onDeleteTerm(term) },
                    )
                }
            }
        }

        state.pendingAction?.let { action ->
            item {
                AcademicCycleConfirmation(
                    action = action,
                    busy = state.isSaving,
                    onCancel = onCancelAction,
                    onConfirm = onConfirmAction,
                )
            }
        }
    }
}

@Composable
private fun CurrentAcademicContextCard(state: AcademicCycleUiState) {
    val current = state.workspace?.current ?: return
    val complete = current.sessionId != null && current.termId != null
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.SurfaceBlue50),
        border = BorderStroke(1.dp, if (complete) EduCoreColors.Info200 else EduCoreColors.Gold200),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f)) {
                    Text("Current academic context", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
                    Text(
                        "${current.session ?: "No current session"} · ${current.term ?: "No current term"}",
                        style = MaterialTheme.typography.bodyMedium,
                    )
                }
                EduCoreStatusBadge(
                    text = if (complete) "Ready" else "Setup required",
                    tone = if (complete) EduCoreTone.Success else EduCoreTone.Warning,
                )
            }
            Text(
                "Exactly one current session and one current term are required for normal academic operations.",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }
    }
}

@Composable
private fun AcademicSessionCardNative(
    session: AcademicSessionAdminDto,
    canManage: Boolean,
    checkingReadiness: Boolean,
    onEdit: () -> Unit,
    onActivate: () -> Unit,
    onClose: () -> Unit,
    onDelete: () -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
                Column(Modifier.weight(1f)) {
                    Text(session.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    Text("${session.termCount} term(s)", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                EduCoreStatusBadge(if (session.current) "Current" else "Closed", if (session.current) EduCoreTone.Success else EduCoreTone.Neutral)
            }
            if (canManage) {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreSecondaryButton("Edit", onEdit, Modifier.weight(1f))
                    if (session.current) {
                        EduCoreSecondaryButton(
                            if (checkingReadiness) "Checking…" else "Close",
                            onClose,
                            Modifier.weight(1f),
                            enabled = !checkingReadiness,
                        )
                    } else {
                        EduCoreSecondaryButton("Activate", onActivate, Modifier.weight(1f))
                    }
                }
                if (!session.current && session.termCount == 0) {
                    EduCoreSecondaryButton("Delete unused session", onDelete, Modifier.fillMaxWidth())
                }
            }
        }
    }
}

@Composable
private fun AcademicTermCardNative(
    term: AcademicTermAdminDto,
    canManage: Boolean,
    checkingReadiness: Boolean,
    onEdit: () -> Unit,
    onActivate: () -> Unit,
    onClose: () -> Unit,
    onDelete: () -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
                Column(Modifier.weight(1f)) {
                    Text(term.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    Text(term.session ?: "Academic session", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                EduCoreStatusBadge(if (term.current) "Current" else "Closed", if (term.current) EduCoreTone.Success else EduCoreTone.Neutral)
            }
            Text(
                "${term.startDate ?: "Start not set"} — ${term.endDate ?: "End not set"}",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            term.nextTermBegins?.let {
                Text("Next term begins: $it", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            if (canManage) {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreSecondaryButton("Edit", onEdit, Modifier.weight(1f))
                    if (term.current) {
                        EduCoreSecondaryButton(
                            if (checkingReadiness) "Checking…" else "Close",
                            onClose,
                            Modifier.weight(1f),
                            enabled = !checkingReadiness,
                        )
                    } else {
                        EduCoreSecondaryButton("Activate", onActivate, Modifier.weight(1f))
                    }
                }
                if (!term.current) {
                    EduCoreSecondaryButton("Delete term", onDelete, Modifier.fillMaxWidth())
                }
            }
        }
    }
}

@Composable
private fun AcademicSessionEditor(
    state: AcademicCycleUiState,
    onBack: () -> Unit,
    onName: (String) -> Unit,
    onActivate: (Boolean) -> Unit,
    onSave: () -> Unit,
) {
    val draft = state.sessionDraft
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader(if (state.editingSession) "Edit Academic Session" else "Add Academic Session", "Academic session identity", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item { EduCoreTextField(draft.name, onName, "Session name", Modifier.fillMaxWidth(), enabled = !state.isSaving, supportingText = "Example: 2026/2027") }
        if (!state.editingSession) {
            item { AcademicCycleSwitch("Activate immediately", "Makes this the current session after creation.", draft.activate, !state.isSaving, onActivate) }
        }
        item { EduCorePrimaryButton(if (state.editingSession) "Save changes" else "Create session", onSave, Modifier.fillMaxWidth(), enabled = state.canManage && draft.valid, loading = state.isSaving) }
        item { EduCoreSecondaryButton("Cancel", onBack, Modifier.fillMaxWidth(), enabled = !state.isSaving) }
    }
}

@Composable
private fun AcademicTermEditor(
    state: AcademicCycleUiState,
    onBack: () -> Unit,
    onSession: (Long?) -> Unit,
    onName: (String) -> Unit,
    onStart: (String) -> Unit,
    onEnd: (String) -> Unit,
    onNextTerm: (String) -> Unit,
    onActivate: (Boolean) -> Unit,
    onSave: () -> Unit,
) {
    val workspace = state.workspace ?: return
    val draft = state.termDraft
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader(if (state.editingTerm) "Edit Academic Term" else "Add Academic Term", "Term dates and session association", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item {
            Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                Text("Academic session", style = MaterialTheme.typography.labelLarge, color = MaterialTheme.colorScheme.onSurfaceVariant)
                Row(
                    modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                ) {
                    workspace.sessions.forEach { session ->
                        EduCoreFilterChip(
                            label = session.name,
                            selected = draft.sessionId == session.id,
                            onClick = { onSession(session.id) },
                            enabled = !state.editingTerm && !state.isSaving,
                        )
                    }
                }
            }
        }
        item { EduCoreTextField(draft.name, onName, "Term name", Modifier.fillMaxWidth(), enabled = !state.isSaving, supportingText = "Example: First Term") }
        item { EduCoreTextField(draft.startDate, onStart, "Start date", Modifier.fillMaxWidth(), enabled = !state.isSaving, supportingText = "YYYY-MM-DD") }
        item { EduCoreTextField(draft.endDate, onEnd, "End date", Modifier.fillMaxWidth(), enabled = !state.isSaving, supportingText = "YYYY-MM-DD") }
        item { EduCoreTextField(draft.nextTermBegins, onNextTerm, "Next term begins", Modifier.fillMaxWidth(), enabled = !state.isSaving, supportingText = "Optional, YYYY-MM-DD") }
        if (!state.editingTerm) {
            item { AcademicCycleSwitch("Activate immediately", "Activation succeeds only when this term belongs to the current session.", draft.activate, !state.isSaving, onActivate) }
        }
        item { EduCorePrimaryButton(if (state.editingTerm) "Save changes" else "Create term", onSave, Modifier.fillMaxWidth(), enabled = state.canManage && draft.valid, loading = state.isSaving) }
        item { EduCoreSecondaryButton("Cancel", onBack, Modifier.fillMaxWidth(), enabled = !state.isSaving) }
    }
}

@Composable
private fun AcademicCycleSwitch(
    title: String,
    message: String,
    checked: Boolean,
    enabled: Boolean,
    onChecked: (Boolean) -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            Column(Modifier.weight(1f)) {
                Text(title, style = MaterialTheme.typography.titleSmall)
                Text(message, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            Switch(checked = checked, onCheckedChange = onChecked, enabled = enabled)
        }
    }
}

@Composable
private fun AcademicCycleConfirmation(
    action: AcademicCycleAction,
    busy: Boolean,
    onCancel: () -> Unit,
    onConfirm: () -> Unit,
) {
    val readiness = action.readiness
    val blocked = readiness?.allowed == false
    Card(
        colors = CardDefaults.cardColors(containerColor = if (blocked) EduCoreColors.Danger100 else EduCoreColors.Warning100),
        border = BorderStroke(1.dp, if (blocked) EduCoreColors.Danger700 else EduCoreColors.Warning700),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Text(action.title, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
            Text(action.message, style = MaterialTheme.typography.bodySmall)
            readiness?.let {
                ReadinessGroup("Blocking", it.blocking, EduCoreColors.Danger700)
                ReadinessGroup("Warnings", it.warnings, EduCoreColors.Warning700)
                ReadinessGroup("Information", it.information, EduCoreColors.Slate600)
                if (it.allowed && it.blocking.isEmpty()) {
                    Text("Lifecycle readiness check passed.", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Success700)
                }
            }
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreSecondaryButton("Cancel", onCancel, Modifier.weight(1f), enabled = !busy)
                EduCorePrimaryButton(
                    text = if (blocked) "Blocked" else "Confirm",
                    onClick = onConfirm,
                    modifier = Modifier.weight(1f),
                    enabled = !busy && !blocked,
                    loading = busy,
                )
            }
        }
    }
}

@Composable
private fun ReadinessGroup(title: String, items: List<String>, colour: androidx.compose.ui.graphics.Color) {
    if (items.isEmpty()) return
    Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
        Text(title, style = MaterialTheme.typography.labelLarge, color = colour)
        items.forEach { Text("• $it", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant) }
    }
}
