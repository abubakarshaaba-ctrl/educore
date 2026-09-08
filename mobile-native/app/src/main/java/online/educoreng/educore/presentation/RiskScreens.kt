package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Surface
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.RiskFlagDto
import online.educoreng.educore.core.network.dto.RiskKeyLabelDto
import online.educoreng.educore.core.network.dto.RiskTermOptionDto

@Composable
internal fun RiskDashboardScreen(
    state: RiskUiState,
    onBack: () -> Unit,
    onTerm: (Long?) -> Unit,
    onStatus: (String) -> Unit,
    onRiskLevel: (String) -> Unit,
    onOpen: (Long) -> Unit,
    onLoadMore: () -> Unit,
    onCompute: () -> Unit,
    onConfig: () -> Unit,
    onRetry: () -> Unit,
) {
    val workspace = state.workspace
    if (state.isLoading && workspace == null) {
        Column(Modifier.fillMaxSize().background(EduCoreColors.Page50)) {
            EduCorePageHeader("Risk Flags", "Student intervention intelligence", onBack = onBack)
            EduCoreLoadingState(Modifier.fillMaxSize(), "Loading risk intelligence")
        }
        return
    }
    if (workspace == null && state.errorMessage != null) {
        Column(Modifier.fillMaxSize().background(EduCoreColors.Page50)) {
            EduCorePageHeader("Risk Flags", "Student intervention intelligence", onBack = onBack)
            EduCoreErrorState(
                message = state.errorMessage,
                modifier = Modifier.fillMaxSize(),
                title = "Risk intelligence unavailable",
                onRetry = onRetry,
            )
        }
        return
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Risk Flags",
                subtitle = "Identify students who need early academic or welfare intervention",
                onBack = onBack,
            )
        }
        item {
            EduCoreShowcaseHero(
                eyebrow = "EARLY INTERVENTION",
                title = "Prioritise evidence, not guesswork.",
                subtitle = "Composite risk combines academic performance, attendance and—when enabled—outstanding fee signals using your school's configured thresholds.",
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let { item { RiskMessageCard(it) } }

        workspace?.let { data ->
            item {
                RiskSummaryStrip(
                    total = data.summary.total,
                    critical = data.summary.critical,
                    high = data.summary.high,
                    open = data.summary.open,
                    resolved = data.summary.resolved,
                )
            }
            item {
                RiskTermSelector(
                    terms = data.filters.terms,
                    selectedId = state.selectedTermId,
                    onSelected = onTerm,
                )
            }
            item {
                FilterStrip("Status", data.filters.statuses, state.selectedStatus, onStatus)
            }
            item {
                FilterStrip("Risk level", data.filters.riskLevels, state.selectedRiskLevel, onRiskLevel)
            }
            item {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                ) {
                    EduCorePrimaryButton(
                        text = if (state.isComputing) "Recomputing…" else "Recompute risk",
                        onClick = onCompute,
                        modifier = Modifier.weight(1f),
                        enabled = state.canCompute,
                        loading = state.isComputing,
                        leadingIcon = { Icon(Icons.Default.Refresh, contentDescription = null) },
                    )
                    if (state.canManageConfig) {
                        EduCoreSecondaryButton(
                            text = "Thresholds",
                            onClick = onConfig,
                            modifier = Modifier.weight(1f),
                        )
                    }
                }
            }
        }

        if (state.flags.isEmpty() && !state.isLoading) {
            item {
                EduCoreEmptyState(
                    title = "No matching risk flags",
                    message = "There are no student flags for the selected term, status and risk level. Recompute risk after scores, attendance or fee information changes.",
                )
            }
        } else {
            items(state.flags, key = RiskFlagDto::id) { flag ->
                RiskFlagCard(flag, onOpen)
            }
        }

        if (state.hasMore) {
            item {
                EduCoreSecondaryButton(
                    text = if (state.isLoadingMore) "Loading more…" else "Load more (${state.flags.size} of ${state.filteredTotal})",
                    onClick = onLoadMore,
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !state.isLoadingMore,
                )
            }
        }
    }
}

@Composable
internal fun RiskDetailScreen(
    state: RiskUiState,
    onBack: () -> Unit,
    onNote: (String) -> Unit,
    onAcknowledge: () -> Unit,
    onResolve: () -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isDetailLoading && state.detail == null) {
        Column(Modifier.fillMaxSize().background(EduCoreColors.Page50)) {
            EduCorePageHeader("Student Risk", "Loading intervention context", onBack = onBack)
            EduCoreLoadingState(Modifier.fillMaxSize(), "Loading student risk context")
        }
        return
    }
    val detail = state.detail
    if (detail == null) {
        Column(Modifier.fillMaxSize().background(EduCoreColors.Page50)) {
            EduCorePageHeader("Student Risk", "Intervention context", onBack = onBack)
            EduCoreErrorState(
                message = state.errorMessage ?: "This risk record could not be loaded.",
                modifier = Modifier.fillMaxSize(),
                onRetry = onRetry,
            )
        }
        return
    }

    val flag = detail.flag
    LazyColumn(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = flag.student.name,
                subtitle = listOfNotNull(flag.student.admissionNumber, flag.student.className, flag.term.name).joinToString(" · "),
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let { item { RiskMessageCard(it) } }
        item { RiskScoreCard(flag) }
        item {
            Text("Why this student is flagged", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            if (flag.flagLabels.isEmpty()) {
                Text("No component flags recorded.", color = MaterialTheme.colorScheme.onSurfaceVariant)
            } else {
                Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                    flag.flagLabels.forEach { label -> RiskReason(label) }
                }
            }
        }
        item {
            RiskContextCard(
                attendance = detail.context.attendanceRate?.let { "${it.cleanPercent()}% (${detail.context.attendancePresent}/${detail.context.attendanceTotal})" } ?: "No attendance data",
                scoreAverage = detail.context.scoreAverage?.let { "${it.cleanPercent()}% across ${detail.context.scoreRecords} records" } ?: "No score data",
                previousAverage = detail.context.previousTermAverage?.let { "${it.cleanPercent()}%" } ?: "No previous summary",
                outstanding = if (detail.context.outstandingInvoices > 0) {
                    "${detail.context.outstandingInvoices} invoice(s) · NGN ${"%,.2f".format(detail.context.outstandingBalance)}"
                } else "No outstanding invoices",
            )
        }
        item {
            Text("Intervention note", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            OutlinedTextField(
                value = state.interventionNote,
                onValueChange = onNote,
                modifier = Modifier.fillMaxWidth(),
                label = { Text("Action taken, follow-up or referral") },
                minLines = 4,
                maxLines = 8,
                enabled = flag.status != "resolved" && !state.isMutating,
                supportingText = { Text("${state.interventionNote.length}/1000 characters") },
            )
        }
        item {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                if (state.canAcknowledge) {
                    EduCoreSecondaryButton(
                        text = "Acknowledge",
                        onClick = onAcknowledge,
                        modifier = Modifier.weight(1f),
                        enabled = !state.isMutating,
                    )
                }
                if (state.canResolve) {
                    EduCorePrimaryButton(
                        text = if (state.isMutating) "Saving…" else "Resolve flag",
                        onClick = onResolve,
                        modifier = Modifier.weight(1f),
                        enabled = !state.isMutating,
                        loading = state.isMutating,
                    )
                }
            }
        }
        item {
            Text(
                text = buildString {
                    append("Status: ${flag.status.replaceFirstChar { it.uppercase() }}")
                    flag.acknowledgedBy?.let { append(" · acknowledged by $it") }
                    flag.resolvedBy?.let { append(" · resolved by $it") }
                },
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }
    }
}

@Composable
internal fun RiskConfigScreen(
    state: RiskUiState,
    onBack: () -> Unit,
    onValue: (RiskConfigField, String) -> Unit,
    onFeeRisk: (Boolean) -> Unit,
    onSave: () -> Unit,
    onReset: () -> Unit,
) {
    val draft = state.configDraft
    LazyColumn(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Risk Thresholds",
                subtitle = "Control how student risk signals are classified",
                onBack = onBack,
            )
        }
        item {
            EduCoreShowcaseHero(
                eyebrow = "RISK MODEL",
                title = "Keep the scoring model explicit and reviewable.",
                subtitle = "Thresholds determine when dimensions trigger risk; weights determine how those dimensions contribute to the composite score.",
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let { item { RiskMessageCard(it) } }
        item { ConfigNumberField("Academic threshold (%)", draft.academicThreshold) { onValue(RiskConfigField.ACADEMIC_THRESHOLD, it) } }
        item { ConfigNumberField("Attendance threshold (%)", draft.attendanceThreshold) { onValue(RiskConfigField.ATTENDANCE_THRESHOLD, it) } }
        item { ConfigNumberField("Subjects failed threshold", draft.subjectsFailedThreshold) { onValue(RiskConfigField.SUBJECTS_FAILED_THRESHOLD, it) } }
        item {
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
                        Text("Include fee risk", fontWeight = FontWeight.SemiBold)
                        Text(
                            "Use overdue/unpaid invoices as one component of the composite risk score.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                    Switch(checked = draft.includeFeeRisk, onCheckedChange = onFeeRisk)
                }
            }
        }
        item {
            Text("Composite weights", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
            Text(
                "Weights must total 100%. Current total: ${draft.weightTotal}%",
                color = if (draft.weightTotal == 100) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.error,
                style = MaterialTheme.typography.bodySmall,
            )
        }
        item { ConfigNumberField("Academic weight (%)", draft.academicWeight) { onValue(RiskConfigField.ACADEMIC_WEIGHT, it) } }
        item { ConfigNumberField("Attendance weight (%)", draft.attendanceWeight) { onValue(RiskConfigField.ATTENDANCE_WEIGHT, it) } }
        item { ConfigNumberField("Fee weight (%)", draft.feeWeight) { onValue(RiskConfigField.FEE_WEIGHT, it) } }
        item {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                EduCoreSecondaryButton("Reset", onReset, Modifier.weight(1f), enabled = !state.isSavingConfig)
                EduCorePrimaryButton(
                    text = if (state.isSavingConfig) "Saving…" else "Save thresholds",
                    onClick = onSave,
                    modifier = Modifier.weight(1f),
                    enabled = draft.weightTotal == 100 && !state.isSavingConfig,
                    loading = state.isSavingConfig,
                    leadingIcon = { Icon(Icons.Default.Settings, contentDescription = null) },
                )
            }
        }
    }
}

@Composable
private fun RiskSummaryStrip(total: Int, critical: Int, high: Int, open: Int, resolved: Int) {
    Row(
        modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
    ) {
        SummaryPill("Total", total)
        SummaryPill("Critical", critical)
        SummaryPill("High", high)
        SummaryPill("Open", open)
        SummaryPill("Resolved", resolved)
    }
}

@Composable
private fun SummaryPill(label: String, value: Int) {
    Card(
        modifier = Modifier.width(104.dp),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(Modifier.padding(EduCoreSpacing.Md)) {
            Text(value.toString(), style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Bold)
            Text(label, style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
    }
}

@Composable
private fun RiskTermSelector(terms: List<RiskTermOptionDto>, selectedId: Long?, onSelected: (Long?) -> Unit) {
    var expanded by remember { mutableStateOf(false) }
    val selected = terms.firstOrNull { it.id == selectedId }
    Column {
        Text("Academic term", style = MaterialTheme.typography.labelLarge)
        Spacer(Modifier.height(EduCoreSpacing.Xs))
        OutlinedButton(
            onClick = { expanded = true },
            modifier = Modifier.fillMaxWidth(),
            enabled = terms.isNotEmpty(),
        ) {
            Text(
                listOfNotNull(selected?.name, selected?.sessionName).joinToString(" · ").ifBlank { "Choose term" },
                modifier = Modifier.weight(1f),
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
            )
        }
        DropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
            terms.forEach { term ->
                DropdownMenuItem(
                    text = { Text(listOfNotNull(term.name, term.sessionName).joinToString(" · ")) },
                    onClick = {
                        expanded = false
                        onSelected(term.id)
                    },
                )
            }
        }
    }
}

@Composable
private fun FilterStrip(title: String, options: List<RiskKeyLabelDto>, selected: String, onSelect: (String) -> Unit) {
    Column {
        Text(title, style = MaterialTheme.typography.labelLarge)
        Spacer(Modifier.height(EduCoreSpacing.Xs))
        Row(
            modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            options.forEach { option ->
                FilterChip(
                    selected = option.key == selected,
                    onClick = { onSelect(option.key) },
                    label = { Text(option.label) },
                )
            }
        }
    }
}

@Composable
private fun RiskFlagCard(flag: RiskFlagDto, onOpen: (Long) -> Unit) {
    Card(
        onClick = { onOpen(flag.id) },
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, riskAccent(flag.riskLevel)),
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                Column(Modifier.weight(1f)) {
                    Text(flag.student.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    Text(
                        listOfNotNull(flag.student.admissionNumber, flag.student.className).joinToString(" · "),
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                RiskLevelBadge(flag.riskLevel, flag.compositeRisk)
            }
            if (flag.flagLabels.isNotEmpty()) {
                Text(
                    flag.flagLabels.take(2).joinToString(" · "),
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis,
                )
            }
            Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                MiniRisk("Academic", flag.academicRisk, Modifier.weight(1f))
                MiniRisk("Attendance", flag.attendanceRisk, Modifier.weight(1f))
                MiniRisk("Fees", flag.feeRisk, Modifier.weight(1f))
            }
            Text("Status: ${flag.status.replaceFirstChar { it.uppercase() }}", style = MaterialTheme.typography.labelMedium)
        }
    }
}

@Composable
private fun RiskScoreCard(flag: RiskFlagDto) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, riskAccent(flag.riskLevel)),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f)) {
                    Text("Composite risk", style = MaterialTheme.typography.labelLarge)
                    Text("${flag.compositeRisk}%", style = MaterialTheme.typography.headlineMedium, fontWeight = FontWeight.Bold)
                }
                RiskLevelBadge(flag.riskLevel, flag.compositeRisk)
            }
            Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                MiniRisk("Academic", flag.academicRisk, Modifier.weight(1f))
                MiniRisk("Attendance", flag.attendanceRisk, Modifier.weight(1f))
                MiniRisk("Fees", flag.feeRisk, Modifier.weight(1f))
            }
            Text("Subjects failed: ${flag.subjectsFailed}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
    }
}

@Composable
private fun MiniRisk(label: String, value: Int, modifier: Modifier = Modifier) {
    Surface(
        shape = RoundedCornerShape(10.dp),
        color = MaterialTheme.colorScheme.surfaceVariant,
        modifier = modifier,
    ) {
        Column(Modifier.padding(EduCoreSpacing.Sm), horizontalAlignment = Alignment.CenterHorizontally) {
            Text("$value%", fontWeight = FontWeight.SemiBold)
            Text(label, style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
    }
}

@Composable
private fun RiskLevelBadge(level: String, score: Int) {
    val accent = riskAccent(level)
    Surface(shape = RoundedCornerShape(50), color = accent.copy(alpha = 0.12f)) {
        Text(
            "${level.replaceFirstChar { it.uppercase() }} · $score%",
            modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
            color = accent,
            style = MaterialTheme.typography.labelMedium,
            fontWeight = FontWeight.SemiBold,
        )
    }
}

@Composable
private fun RiskReason(label: String) {
    Surface(shape = RoundedCornerShape(10.dp), color = MaterialTheme.colorScheme.surfaceVariant) {
        Text(label, modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Sm), style = MaterialTheme.typography.bodySmall)
    }
}

@Composable
private fun RiskContextCard(attendance: String, scoreAverage: String, previousAverage: String, outstanding: String) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Text("Evidence context", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
            ContextRow("Attendance", attendance)
            ContextRow("Current scores", scoreAverage)
            ContextRow("Previous average", previousAverage)
            ContextRow("Outstanding fees", outstanding)
        }
    }
}

@Composable
private fun ContextRow(label: String, value: String) {
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
        Text(label, modifier = Modifier.weight(0.38f), style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Text(value, modifier = Modifier.weight(0.62f), style = MaterialTheme.typography.bodyMedium)
    }
}

@Composable
private fun ConfigNumberField(label: String, value: String, onValue: (String) -> Unit) {
    OutlinedTextField(
        value = value,
        onValueChange = { next -> onValue(next.filter { it.isDigit() || it == '.' }.take(6)) },
        modifier = Modifier.fillMaxWidth(),
        label = { Text(label) },
        singleLine = true,
    )
}

@Composable
private fun RiskMessageCard(message: String) {
    Surface(shape = RoundedCornerShape(12.dp), color = MaterialTheme.colorScheme.primaryContainer) {
        Text(
            message,
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            style = MaterialTheme.typography.bodySmall,
            color = MaterialTheme.colorScheme.onPrimaryContainer,
        )
    }
}

@Composable
private fun riskAccent(level: String): Color = when (level.lowercase()) {
    "critical" -> MaterialTheme.colorScheme.error
    "high" -> MaterialTheme.colorScheme.tertiary
    "medium" -> MaterialTheme.colorScheme.secondary
    else -> MaterialTheme.colorScheme.primary
}

private fun Double.cleanPercent(): String = if (this % 1.0 == 0.0) toInt().toString() else "%.1f".format(this)
