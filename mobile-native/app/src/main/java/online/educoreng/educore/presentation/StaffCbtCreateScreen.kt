package online.educoreng.educore.presentation

import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AddCircle
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableLongStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Date
import java.util.Locale
import online.educoreng.educore.core.designsystem.component.EduCoreDatePicker
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreFilterChip
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseSectionCard
import online.educoreng.educore.core.designsystem.component.EduCoreTextField
import online.educoreng.educore.core.designsystem.component.EduCoreTimePicker
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.StaffCbtCreateRequestDto

private enum class CreateCbtScheduleTarget { START, END }

@Composable
internal fun StaffCbtCreateScreen(
    state: StaffCbtUiState,
    onBack: () -> Unit,
    onCreate: (StaffCbtCreateRequestDto) -> Unit,
    onRetry: () -> Unit,
) {
    val options = state.createOptions
    if (state.isOptionsLoading && options == null) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Preparing examination options")
        return
    }
    if (options == null) {
        Column(
            modifier = Modifier.fillMaxSize(),
            verticalArrangement = Arrangement.Center,
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            EduCoreEmptyState(
                title = "Creation options unavailable",
                message = state.errorMessage ?: "Reload the CBT creation options and try again.",
                actionLabel = "Retry",
                onAction = onRetry,
            )
        }
        return
    }

    var title by rememberSaveable { mutableStateOf("") }
    var bankId by rememberSaveable { mutableLongStateOf(0L) }
    var termId by rememberSaveable { mutableLongStateOf(0L) }
    var assessmentId by rememberSaveable { mutableLongStateOf(0L) }
    var duration by rememberSaveable { mutableStateOf(options.defaults.durationMinutes.toString()) }
    var selectedClasses by remember { mutableStateOf<Set<Long>>(emptySet()) }
    var malpracticeEnabled by rememberSaveable { mutableStateOf(options.defaults.malpracticeEnabled) }
    var requireFullscreen by rememberSaveable { mutableStateOf(options.defaults.requireFullscreen) }
    var focusLossPolicy by rememberSaveable { mutableStateOf(options.defaults.focusLossPolicy) }
    var maxFocusLosses by rememberSaveable { mutableStateOf(options.defaults.maxFocusLosses.toString()) }

    val initialStart = remember { roundedFutureMillis(60) }
    var startMillis by rememberSaveable { mutableLongStateOf(initialStart) }
    var endMillis by rememberSaveable { mutableLongStateOf(initialStart + 60 * 60 * 1000L) }
    var dateTarget by rememberSaveable { mutableStateOf<CreateCbtScheduleTarget?>(null) }
    var timeTarget by rememberSaveable { mutableStateOf<CreateCbtScheduleTarget?>(null) }

    LaunchedEffect(options.generatedAt) {
        if (bankId == 0L) bankId = options.banks.firstOrNull()?.id ?: 0L
        if (termId == 0L) termId = options.defaults.termId ?: options.terms.firstOrNull()?.id ?: 0L
    }

    LaunchedEffect(bankId) {
        val levelId = options.banks.firstOrNull { it.id == bankId }?.classLevel?.id
        selectedClasses = selectedClasses.filterTo(mutableSetOf()) { selectedId ->
            options.classes.any { it.id == selectedId && it.classLevelId == levelId }
        }
    }

    LaunchedEffect(termId) {
        if (assessmentId != 0L && options.assessmentTypes.none { it.id == assessmentId && it.termId == termId }) {
            assessmentId = 0L
        }
    }

    dateTarget?.let { target ->
        val current = if (target == CreateCbtScheduleTarget.START) startMillis else endMillis
        EduCoreDatePicker(
            visible = true,
            initialDateMillis = utcDateOnlyMillis(current),
            onDateSelected = { selected ->
                if (target == CreateCbtScheduleTarget.START) {
                    startMillis = replaceCreateDate(startMillis, selected)
                } else {
                    endMillis = replaceCreateDate(endMillis, selected)
                }
            },
            onDismiss = { dateTarget = null },
        )
    }

    timeTarget?.let { target ->
        val current = if (target == CreateCbtScheduleTarget.START) startMillis else endMillis
        val calendar = Calendar.getInstance().apply { timeInMillis = current }
        EduCoreTimePicker(
            visible = true,
            initialHour = calendar.get(Calendar.HOUR_OF_DAY),
            initialMinute = calendar.get(Calendar.MINUTE),
            onTimeSelected = { hour, minute ->
                if (target == CreateCbtScheduleTarget.START) {
                    startMillis = replaceCreateTime(startMillis, hour, minute)
                } else {
                    endMillis = replaceCreateTime(endMillis, hour, minute)
                }
            },
            onDismiss = { timeTarget = null },
        )
    }

    val selectedBank = options.banks.firstOrNull { it.id == bankId }
    val availableClasses = options.classes.filter { it.classLevelId == selectedBank?.classLevel?.id }
    val assessments = options.assessmentTypes.filter { it.termId == termId }
    val durationValue: Int? = duration.toIntOrNull()
    val maxFocusValue: Int? = maxFocusLosses.toIntOrNull()
    val durationValid = durationValue?.let { value -> value >= 5 && value <= 1440 } == true
    val focusValid = maxFocusValue?.let { value -> value >= 0 && value <= 20 } == true
    val bankValid = (selectedBank?.questionCount ?: 0) > 0
    val scheduleValid = startMillis > System.currentTimeMillis() && endMillis > startMillis
    val formValid = title.isNotBlank() && bankValid && selectedClasses.isNotEmpty() && termId > 0L && durationValid && focusValid && scheduleValid

    androidx.compose.foundation.lazy.LazyColumn(
        modifier = Modifier.fillMaxSize().imePadding(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "New Examination",
                subtitle = "CBT Management",
                onBack = onBack,
            )
        }
        item {
            EduCoreShowcaseHero(
                eyebrow = "CREATE DRAFT",
                title = "Prepare a secure examination.",
                subtitle = "Select an existing question bank, target classes and schedule. EduCore will generate the draft sections automatically.",
                trailing = { Icon(Icons.Default.AddCircle, contentDescription = null) },
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }

        item {
            EduCoreShowcaseSectionCard {
                Text("Examination", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = EduCoreColors.Navy900)
                Spacer(Modifier.height(EduCoreSpacing.Md))
                EduCoreTextField(
                    value = title,
                    onValueChange = { candidate -> if (candidate.length <= 150) title = candidate },
                    label = "Examination title",
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !state.isCreating,
                    supportingText = "Use a clear title students and staff will recognize.",
                )
                Spacer(Modifier.height(EduCoreSpacing.Md))
                Text("Question bank", style = MaterialTheme.typography.labelLarge, color = EduCoreColors.Ink900)
                Row(
                    modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                ) {
                    options.banks.forEach { bank ->
                        EduCoreFilterChip(
                            label = "${bank.name} (${bank.questionCount})",
                            selected = bank.id == bankId,
                            onClick = { bankId = bank.id },
                            enabled = !state.isCreating,
                        )
                    }
                }
                selectedBank?.let { bank ->
                    Text(
                        listOfNotNull(bank.subject?.name, bank.classLevel?.name, "${bank.questionCount} questions").joinToString(" · "),
                        style = MaterialTheme.typography.bodySmall,
                        color = if (bank.questionCount > 0) EduCoreColors.Slate600 else EduCoreColors.Danger600,
                    )
                }
            }
        }

        item {
            EduCoreShowcaseSectionCard {
                Text("Classes", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = EduCoreColors.Navy900)
                Text("Choose one or more ${selectedBank?.classLevel?.name ?: "matching"} classes.", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                if (availableClasses.isEmpty()) {
                    Text("No permitted classes match this question bank.", color = EduCoreColors.Danger600, style = MaterialTheme.typography.bodyMedium)
                } else {
                    Row(
                        modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    ) {
                        availableClasses.forEach { classOption ->
                            EduCoreFilterChip(
                                label = classOption.name,
                                selected = classOption.id in selectedClasses,
                                onClick = {
                                    selectedClasses = if (classOption.id in selectedClasses) selectedClasses - classOption.id else selectedClasses + classOption.id
                                },
                                enabled = !state.isCreating,
                            )
                        }
                    }
                }
            }
        }

        item {
            EduCoreShowcaseSectionCard {
                Text("Academic period", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = EduCoreColors.Navy900)
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                Text("Term", style = MaterialTheme.typography.labelLarge, color = EduCoreColors.Ink900)
                Row(
                    modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                ) {
                    options.terms.forEach { term ->
                        EduCoreFilterChip(
                            label = listOfNotNull(term.name, term.session).joinToString(" · "),
                            selected = term.id == termId,
                            onClick = { termId = term.id },
                            enabled = !state.isCreating,
                        )
                    }
                }
                Spacer(Modifier.height(EduCoreSpacing.Md))
                Text("Assessment link", style = MaterialTheme.typography.labelLarge, color = EduCoreColors.Ink900)
                Row(
                    modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                ) {
                    EduCoreFilterChip(
                        label = "None",
                        selected = assessmentId == 0L,
                        onClick = { assessmentId = 0L },
                        enabled = !state.isCreating,
                    )
                    assessments.forEach { assessment ->
                        EduCoreFilterChip(
                            label = assessment.name,
                            selected = assessment.id == assessmentId,
                            onClick = { assessmentId = assessment.id },
                            enabled = !state.isCreating,
                        )
                    }
                }
            }
        }

        item {
            EduCoreShowcaseSectionCard {
                Text("Schedule", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = EduCoreColors.Navy900)
                Spacer(Modifier.height(EduCoreSpacing.Md))
                CreateScheduleRow(
                    label = "Starts",
                    millis = startMillis,
                    enabled = !state.isCreating,
                    onDate = { dateTarget = CreateCbtScheduleTarget.START },
                    onTime = { timeTarget = CreateCbtScheduleTarget.START },
                )
                Spacer(Modifier.height(EduCoreSpacing.Md))
                CreateScheduleRow(
                    label = "Ends",
                    millis = endMillis,
                    enabled = !state.isCreating,
                    onDate = { dateTarget = CreateCbtScheduleTarget.END },
                    onTime = { timeTarget = CreateCbtScheduleTarget.END },
                )
                Spacer(Modifier.height(EduCoreSpacing.Md))
                EduCoreTextField(
                    value = duration,
                    onValueChange = { candidate -> if (candidate.length <= 4 && candidate.all(Char::isDigit)) duration = candidate },
                    label = "Duration (minutes)",
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !state.isCreating,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    supportingText = "Allowed range: 5–1440 minutes",
                )
                if (!scheduleValid) {
                    Text("Start must be in the future and end must be later than start.", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Danger600)
                }
            }
        }

        item {
            EduCoreShowcaseSectionCard {
                Text("Exam security", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = EduCoreColors.Navy900)
                Spacer(Modifier.height(EduCoreSpacing.Md))
                CbtSwitchRow("Malpractice monitoring", malpracticeEnabled, !state.isCreating) { malpracticeEnabled = it }
                CbtSwitchRow("Require fullscreen", requireFullscreen, !state.isCreating) { requireFullscreen = it }
                Spacer(Modifier.height(EduCoreSpacing.Md))
                Text("Focus-loss policy", style = MaterialTheme.typography.labelLarge, color = EduCoreColors.Ink900)
                Row(
                    modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                ) {
                    listOf("submit" to "Auto-submit", "warn" to "Warn", "log" to "Log only").forEach { (value, label) ->
                        EduCoreFilterChip(
                            label = label,
                            selected = focusLossPolicy == value,
                            onClick = { focusLossPolicy = value },
                            enabled = !state.isCreating,
                        )
                    }
                }
                Spacer(Modifier.height(EduCoreSpacing.Md))
                EduCoreTextField(
                    value = maxFocusLosses,
                    onValueChange = { candidate -> if (candidate.length <= 2 && candidate.all(Char::isDigit)) maxFocusLosses = candidate },
                    label = "Maximum focus losses",
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !state.isCreating,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    supportingText = "Allowed range: 0–20",
                )
            }
        }

        item {
            EduCorePrimaryButton(
                text = "Create examination draft",
                onClick = {
                    val validDuration = duration.toIntOrNull() ?: return@EduCorePrimaryButton
                    val validFocusLosses = maxFocusLosses.toIntOrNull() ?: return@EduCorePrimaryButton
                    val linkedAssessmentId: Long? = if (assessmentId > 0L) assessmentId else null
                    onCreate(
                        StaffCbtCreateRequestDto(
                            title = title.trim(),
                            questionBankId = bankId,
                            classArmIds = selectedClasses.sorted(),
                            termId = termId,
                            durationMinutes = validDuration,
                            scheduledStart = createApiDateTime(startMillis),
                            scheduledEnd = createApiDateTime(endMillis),
                            assessmentTypeId = linkedAssessmentId,
                            malpracticeEnabled = malpracticeEnabled,
                            focusLossPolicy = focusLossPolicy,
                            maxFocusLosses = validFocusLosses,
                            requireFullscreen = requireFullscreen,
                        )
                    )
                },
                modifier = Modifier.fillMaxWidth(),
                enabled = formValid && !state.isCreating,
                loading = state.isCreating,
                leadingIcon = { Icon(Icons.Default.AddCircle, contentDescription = null) },
            )
            if (selectedBank?.questionCount == 0) {
                Text("The selected bank has no questions. Add questions before creating a native examination draft.", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Danger600)
            }
        }
        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

@Composable
private fun CreateScheduleRow(
    label: String,
    millis: Long,
    enabled: Boolean,
    onDate: () -> Unit,
    onTime: () -> Unit,
) {
    Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
        Text(label, style = MaterialTheme.typography.labelLarge, color = EduCoreColors.Ink900)
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            EduCoreFilterChip(
                label = createDateLabel(millis),
                selected = false,
                onClick = onDate,
                modifier = Modifier.weight(1f),
                enabled = enabled,
            )
            EduCoreFilterChip(
                label = createTimeLabel(millis),
                selected = false,
                onClick = onTime,
                modifier = Modifier.weight(1f),
                enabled = enabled,
            )
        }
    }
}

@Composable
private fun CbtSwitchRow(
    label: String,
    checked: Boolean,
    enabled: Boolean,
    onChecked: (Boolean) -> Unit,
) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Text(label, style = MaterialTheme.typography.bodyLarge, color = EduCoreColors.Ink900, modifier = Modifier.weight(1f))
        Switch(checked = checked, onCheckedChange = onChecked, enabled = enabled)
    }
}

private fun roundedFutureMillis(minutes: Int): Long {
    val calendar = Calendar.getInstance().apply {
        timeInMillis = System.currentTimeMillis() + minutes * 60_000L
        set(Calendar.SECOND, 0)
        set(Calendar.MILLISECOND, 0)
    }
    return calendar.timeInMillis
}

private fun replaceCreateDate(original: Long, selectedUtcDate: Long): Long {
    val originalCalendar = Calendar.getInstance().apply { timeInMillis = original }
    val selected = Calendar.getInstance(java.util.TimeZone.getTimeZone("UTC")).apply { timeInMillis = selectedUtcDate }
    return Calendar.getInstance().apply {
        set(
            selected.get(Calendar.YEAR),
            selected.get(Calendar.MONTH),
            selected.get(Calendar.DAY_OF_MONTH),
            originalCalendar.get(Calendar.HOUR_OF_DAY),
            originalCalendar.get(Calendar.MINUTE),
            0,
        )
        set(Calendar.MILLISECOND, 0)
    }.timeInMillis
}

private fun replaceCreateTime(original: Long, hour: Int, minute: Int): Long = Calendar.getInstance().apply {
    timeInMillis = original
    set(Calendar.HOUR_OF_DAY, hour)
    set(Calendar.MINUTE, minute)
    set(Calendar.SECOND, 0)
    set(Calendar.MILLISECOND, 0)
}.timeInMillis

private fun utcDateOnlyMillis(value: Long): Long {
    val local = Calendar.getInstance().apply { timeInMillis = value }
    return Calendar.getInstance(java.util.TimeZone.getTimeZone("UTC")).apply {
        clear()
        set(local.get(Calendar.YEAR), local.get(Calendar.MONTH), local.get(Calendar.DAY_OF_MONTH))
    }.timeInMillis
}

private fun createDateLabel(value: Long): String = SimpleDateFormat("EEE, d MMM yyyy", Locale.getDefault()).format(Date(value))
private fun createTimeLabel(value: Long): String = SimpleDateFormat("h:mm a", Locale.getDefault()).format(Date(value))
private fun createApiDateTime(value: Long): String = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssXXX", Locale.US).format(Date(value))
