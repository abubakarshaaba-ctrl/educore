package online.educoreng.educore.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.EventBusy
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material.icons.filled.School
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseStat
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.PortalAttendanceRecordDto

@Composable
internal fun PortalAttendanceScreen(
    state: PortalAttendanceUiState,
    onBack: () -> Unit,
    onChild: (Long?) -> Unit,
    onTerm: (Long?) -> Unit,
    onRetry: () -> Unit,
) {
    Column(Modifier.fillMaxSize()) {
        EduCorePageHeader(
            title = if (state.portal == "parent") "Child Attendance" else "My Attendance",
            subtitle = "Term attendance history and participation rate",
            onBack = onBack,
        )

        if (state.isLoading && state.workspace == null) {
            EduCoreLoadingState(message = "Loading attendance")
            return@Column
        }

        val workspace = state.workspace
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = androidx.compose.foundation.layout.PaddingValues(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            item {
                EduCoreShowcaseHero(
                    eyebrow = if (state.portal == "parent") "PARENT PORTAL" else "STUDENT PORTAL",
                    title = workspace?.student?.name ?: "Attendance",
                    subtitle = listOfNotNull(
                        workspace?.student?.admissionNumber,
                        workspace?.student?.classRoom?.name,
                        state.selectedTermName.takeUnless { it == "Select term" },
                    ).joinToString(" · ").ifBlank { "Review attendance by academic term." },
                )
            }

            state.errorMessage?.let { error -> item { EduCoreErrorBanner(error) } }

            workspace?.let { data ->
                item {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    ) {
                        EduCoreShowcaseStat(
                            label = "Rate",
                            value = "${formatAttendanceRate(data.stats.rate)}%",
                            icon = Icons.Default.School,
                            tone = when {
                                data.stats.rate >= 90 -> EduCoreTone.Success
                                data.stats.rate >= 75 -> EduCoreTone.Brand
                                else -> EduCoreTone.Warning
                            },
                            modifier = Modifier.weight(1f),
                        )
                        EduCoreShowcaseStat(
                            label = "Present",
                            value = data.stats.present.toString(),
                            icon = Icons.Default.CheckCircle,
                            tone = EduCoreTone.Success,
                            modifier = Modifier.weight(1f),
                        )
                        EduCoreShowcaseStat(
                            label = "Absent",
                            value = data.stats.absent.toString(),
                            icon = Icons.Default.EventBusy,
                            tone = if (data.stats.absent > 0) EduCoreTone.Warning else EduCoreTone.Success,
                            modifier = Modifier.weight(1f),
                        )
                    }
                }

                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                    ) {
                        Column(
                            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
                            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                        ) {
                            EduCoreSectionHeader(
                                title = "View attendance",
                                supportingText = if (state.portal == "parent") {
                                    "Choose a linked child and academic term."
                                } else {
                                    "Choose an academic term."
                                },
                            )
                            if (state.portal == "parent" && data.children.isNotEmpty()) {
                                PortalAttendanceDropdown(
                                    label = "Child",
                                    value = state.selectedChildName,
                                    options = data.children.map { it.id to it.name },
                                    onSelected = onChild,
                                )
                            }
                            PortalAttendanceDropdown(
                                label = "Term",
                                value = state.selectedTermName,
                                options = data.terms.map { term ->
                                    term.id to listOfNotNull(term.name, term.session).joinToString(" · ")
                                },
                                onSelected = onTerm,
                            )
                        }
                    }
                }

                item {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    ) {
                        AttendanceMiniMetric("Days recorded", data.stats.total.toString(), Modifier.weight(1f))
                        AttendanceMiniMetric("Late", data.stats.late.toString(), Modifier.weight(1f))
                    }
                }

                if (data.records.isEmpty() && !state.isLoading) {
                    item {
                        EduCoreEmptyState(
                            title = "No attendance records",
                            message = "No attendance has been recorded for the selected term.",
                        )
                    }
                } else {
                    item {
                        EduCoreSectionHeader(
                            title = "Attendance history",
                            supportingText = "Latest recorded school days appear first",
                        )
                    }
                    items(data.records, key = { "${it.date}:${it.status}:${it.remark.orEmpty()}" }) { record ->
                        PortalAttendanceRecordCard(record)
                    }
                }
            }

            if (workspace == null && state.errorMessage != null) {
                item {
                    EduCoreEmptyState(
                        title = "Unable to load attendance",
                        message = state.errorMessage,
                        actionLabel = "Retry",
                        onAction = onRetry,
                    )
                }
            }
        }
    }
}

@Composable
private fun PortalAttendanceRecordCard(record: PortalAttendanceRecordDto) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            Column(Modifier.weight(1f)) {
                Text(record.date, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                record.remark?.takeIf(String::isNotBlank)?.let {
                    Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                }
            }
            EduCoreStatusBadge(
                text = record.status.replace('_', ' ').replaceFirstChar(Char::uppercase),
                tone = record.status.attendanceTone(),
            )
        }
    }
}

@Composable
private fun AttendanceMiniMetric(label: String, value: String, modifier: Modifier = Modifier) {
    Card(modifier, colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md)) {
            Text(label, style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Slate600)
            Text(value, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
        }
    }
}

@Composable
private fun PortalAttendanceDropdown(
    label: String,
    value: String,
    options: List<Pair<Long, String>>,
    onSelected: (Long?) -> Unit,
) {
    var expanded by remember { mutableStateOf(false) }
    Box(Modifier.fillMaxWidth()) {
        EduCoreSecondaryButton(
            text = "$label: $value",
            onClick = { expanded = true },
            modifier = Modifier.fillMaxWidth(),
            enabled = options.isNotEmpty(),
        )
        DropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
            options.forEach { (id, text) ->
                DropdownMenuItem(
                    text = { Text(text) },
                    onClick = {
                        expanded = false
                        onSelected(id)
                    },
                )
            }
        }
    }
}

private fun String.attendanceTone(): EduCoreTone = when (lowercase()) {
    "present" -> EduCoreTone.Success
    "late" -> EduCoreTone.Warning
    "absent" -> EduCoreTone.Danger
    else -> EduCoreTone.Brand
}

private fun formatAttendanceRate(value: Double): String =
    if (value % 1.0 == 0.0) value.toInt().toString() else String.format("%.1f", value)
