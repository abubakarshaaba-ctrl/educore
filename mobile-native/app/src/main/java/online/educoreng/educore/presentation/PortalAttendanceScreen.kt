package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
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
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Locale
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseStat
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTabs
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
    var selectedView by remember { mutableStateOf(0) }

    Column(Modifier.fillMaxSize()) {
        EduCorePageHeader(
            title = if (state.portal == "parent") "Child Attendance" else "My Attendance",
            subtitle = "Daily records, term summary and monthly calendar",
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
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                        border = BorderStroke(1.dp, EduCoreColors.Line300),
                    ) {
                        Column(
                            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
                            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                        ) {
                            EduCoreSectionHeader(
                                title = if (state.portal == "parent") "Select child and term" else "Select term",
                                supportingText = if (state.portal == "parent") {
                                    "Only children linked to this parent account are available."
                                } else {
                                    "Attendance is shown from your own student record."
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
                            tone = if (data.stats.absent > 0) EduCoreTone.Danger else EduCoreTone.Success,
                            modifier = Modifier.weight(1f),
                        )
                        EduCoreShowcaseStat(
                            label = "Late",
                            value = data.stats.late.toString(),
                            icon = Icons.Default.Schedule,
                            tone = if (data.stats.late > 0) EduCoreTone.Warning else EduCoreTone.Success,
                            modifier = Modifier.weight(1f),
                        )
                    }
                }

                item {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    ) {
                        AttendanceMiniMetric("Attendance rate", "${formatAttendanceRate(data.stats.rate)}%", Modifier.weight(1f))
                        AttendanceMiniMetric("Days recorded", data.stats.total.toString(), Modifier.weight(1f))
                    }
                }

                item {
                    EduCoreTabs(
                        labels = listOf("Attendance", "Calendar"),
                        selectedIndex = selectedView,
                        onSelected = { selectedView = it },
                        modifier = Modifier.fillMaxWidth(),
                    )
                }

                if (data.records.isEmpty() && !state.isLoading) {
                    item {
                        EduCoreEmptyState(
                            title = "No attendance records",
                            message = "No attendance has been recorded for the selected child and term.",
                        )
                    }
                } else if (selectedView == 0) {
                    item {
                        EduCoreSectionHeader(
                            title = "Attendance history",
                            supportingText = "Latest recorded school days appear first",
                        )
                    }
                    items(data.records, key = { "${it.date}:${it.status}:${it.remark.orEmpty()}" }) { record ->
                        PortalAttendanceRecordCard(record)
                    }
                } else {
                    item { AttendanceCalendar(data.records) }
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
private fun AttendanceCalendar(records: List<PortalAttendanceRecordDto>) {
    val months = remember(records) {
        records.mapNotNull { it.date.takeIf { date -> date.length >= 7 }?.substring(0, 7) }.distinct().sortedDescending()
    }
    var selectedMonth by remember(records) { mutableStateOf(months.firstOrNull().orEmpty()) }
    LaunchedEffect(months) {
        if (selectedMonth !in months) selectedMonth = months.firstOrNull().orEmpty()
    }
    val monthRecords = remember(records, selectedMonth) {
        records.filter { it.date.startsWith(selectedMonth) }.associateBy { it.date.takeLast(2).toIntOrNull() }
    }

    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line300),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
            EduCoreSectionHeader("Monthly calendar", "Green = present · amber = late · red = absent")
            if (months.isEmpty()) {
                EduCoreEmptyState("No calendar records", "Attendance dates will appear here when recorded.")
                return@Column
            }
            Row(Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                months.forEach { month ->
                    EduCoreSecondaryButton(
                        text = attendanceMonthLabel(month),
                        onClick = { selectedMonth = month },
                        enabled = month != selectedMonth,
                    )
                }
            }
            Row(Modifier.fillMaxWidth()) {
                listOf("Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun").forEach { day ->
                    Text(day, Modifier.weight(1f), textAlign = TextAlign.Center, style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Slate700)
                }
            }
            attendanceCalendarWeeks(selectedMonth).forEach { week ->
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                    week.forEach { day ->
                        if (day == null) {
                            Spacer(Modifier.weight(1f))
                        } else {
                            AttendanceCalendarDay(day, monthRecords[day], Modifier.weight(1f))
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun AttendanceCalendarDay(day: Int, record: PortalAttendanceRecordDto?, modifier: Modifier = Modifier) {
    val background = when (record?.status?.lowercase()) {
        "present" -> EduCoreColors.Success100
        "late" -> EduCoreColors.Warning100
        "absent" -> EduCoreColors.Danger100
        else -> EduCoreColors.Page50
    }
    val foreground = when (record?.status?.lowercase()) {
        "present" -> EduCoreColors.Success700
        "late" -> EduCoreColors.Warning700
        "absent" -> EduCoreColors.Danger700
        else -> EduCoreColors.Slate700
    }
    Surface(modifier = modifier, color = background, shape = MaterialTheme.shapes.small) {
        Box(Modifier.padding(vertical = EduCoreSpacing.Sm), contentAlignment = Alignment.Center) {
            Text(day.toString(), textAlign = TextAlign.Center, fontWeight = if (record == null) FontWeight.Normal else FontWeight.Bold, color = foreground)
        }
    }
}

@Composable
private fun PortalAttendanceRecordCard(record: PortalAttendanceRecordDto) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line300),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Column(Modifier.weight(1f)) {
                Text(record.date, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold, color = EduCoreColors.Ink900)
                record.remark?.takeIf(String::isNotBlank)?.let {
                    Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate700)
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
    Card(modifier, colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line300)) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md)) {
            Text(label, style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Slate700)
            Text(value, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold, color = EduCoreColors.Ink900)
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

private fun attendanceCalendarWeeks(month: String): List<List<Int?>> {
    if (!month.matches(Regex("\\d{4}-\\d{2}"))) return emptyList()
    val first = runCatching { SimpleDateFormat("yyyy-MM-dd", Locale.US).parse("$month-01") }.getOrNull() ?: return emptyList()
    val calendar = Calendar.getInstance().apply { time = first }
    val days = calendar.getActualMaximum(Calendar.DAY_OF_MONTH)
    val sundayFirst = calendar.get(Calendar.DAY_OF_WEEK)
    val mondayFirstOffset = (sundayFirst + 5) % 7
    val cells = MutableList<Int?>(mondayFirstOffset) { null }
    (1..days).forEach(cells::add)
    while (cells.size % 7 != 0) cells.add(null)
    return cells.chunked(7)
}

private fun attendanceMonthLabel(month: String): String {
    val date = runCatching { SimpleDateFormat("yyyy-MM-dd", Locale.US).parse("$month-01") }.getOrNull() ?: return month
    return SimpleDateFormat("MMMM yyyy", Locale.getDefault()).format(date)
}

private fun String.attendanceTone(): EduCoreTone = when (lowercase()) {
    "present" -> EduCoreTone.Success
    "late" -> EduCoreTone.Warning
    "absent" -> EduCoreTone.Danger
    else -> EduCoreTone.Brand
}

private fun formatAttendanceRate(value: Double): String =
    if (value % 1.0 == 0.0) value.toInt().toString() else String.format("%.1f", value)
