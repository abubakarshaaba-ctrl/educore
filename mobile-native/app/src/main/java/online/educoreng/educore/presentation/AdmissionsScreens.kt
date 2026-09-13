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
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
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
import online.educoreng.educore.core.network.dto.AdmissionClassArmDto
import online.educoreng.educore.core.network.dto.AdmissionClassLevelDto
import online.educoreng.educore.core.network.dto.AdmissionItemDto
import online.educoreng.educore.core.network.dto.AdmissionKeyLabelDto

@Composable
internal fun AdmissionsScreen(
    state: AdmissionsUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onStatusFilter: (String) -> Unit,
    onOpen: (AdmissionItemDto) -> Unit,
    onCloseDetail: () -> Unit,
    onLoadMore: () -> Unit,
    onStartCreate: () -> Unit,
    onCloseCreate: () -> Unit,
    onCreateField: (AdmissionCreateField, String) -> Unit,
    onCreateGender: (String) -> Unit,
    onCreateClassLevel: (Long?) -> Unit,
    onCreate: () -> Unit,
    onStatusDraft: (String) -> Unit,
    onClassArmDraft: (Long?) -> Unit,
    onReviewNotes: (String) -> Unit,
    onSaveStatus: () -> Unit,
    onInterviewDate: (String) -> Unit,
    onInterviewNotes: (String) -> Unit,
    onInterviewScore: (String) -> Unit,
    onScheduleInterview: () -> Unit,
    onRecordInterview: () -> Unit,
    onSendOffer: () -> Unit,
    onRetry: () -> Unit,
) {
    when {
        state.isCreateOpen -> AdmissionCreateScreen(state, onCloseCreate, onCreateField, onCreateGender, onCreateClassLevel, onCreate)
        state.selectedAdmission != null -> AdmissionDetailScreen(
            state, onCloseDetail, onStatusDraft, onClassArmDraft, onReviewNotes, onSaveStatus,
            onInterviewDate, onInterviewNotes, onInterviewScore, onScheduleInterview, onRecordInterview, onSendOffer,
        )
        else -> AdmissionsListScreen(state, onBack, onQuery, onSearch, onStatusFilter, onOpen, onLoadMore, onStartCreate, onRetry)
    }
}

@Composable
private fun AdmissionsListScreen(
    state: AdmissionsUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onStatusFilter: (String) -> Unit,
    onOpen: (AdmissionItemDto) -> Unit,
    onLoadMore: () -> Unit,
    onStartCreate: () -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.workspace == null) {
        Column(Modifier.fillMaxSize().background(EduCoreColors.Page50)) {
            EduCorePageHeader("Admissions", "Applicant review and enrolment", onBack = onBack)
            EduCoreLoadingState(Modifier.fillMaxSize(), "Loading admission applications")
        }
        return
    }
    if (state.workspace == null && state.errorMessage != null) {
        Column(Modifier.fillMaxSize().background(EduCoreColors.Page50)) {
            EduCorePageHeader("Admissions", "Applicant review and enrolment", onBack = onBack)
            EduCoreErrorState(state.errorMessage, Modifier.fillMaxSize(), title = "Admissions unavailable", onRetry = onRetry)
        }
        return
    }

    val workspace = state.workspace
    LazyColumn(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader("Admissions", "Review applicants and convert successful applications into enrolled students", onBack = onBack) }
        item {
            EduCoreShowcaseHero(
                eyebrow = "ADMISSIONS PIPELINE",
                title = "Move each applicant through a controlled decision workflow.",
                subtitle = "Search applications, schedule interviews, record results, issue offers and enrol admitted students into the correct class arm.",
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let { item { AdmissionMessageCard(it) } }
        workspace?.let { data ->
            item { AdmissionStatsStrip(data.stats.total, data.stats.pending, data.stats.shortlisted, data.stats.admitted) }
            item {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm), verticalAlignment = Alignment.CenterVertically) {
                    OutlinedTextField(
                        value = state.searchQuery, onValueChange = onQuery, modifier = Modifier.weight(1f),
                        label = { Text("Search applicant or application no.") }, singleLine = true,
                    )
                    EduCoreSecondaryButton("Search", onSearch, leadingIcon = { Icon(Icons.Default.Search, null) })
                }
            }
            item { AdmissionFilterStrip(listOf(AdmissionKeyLabelDto("all", "All")) + data.statusOptions, state.selectedStatus, onStatusFilter) }
            if (state.canCreate) item {
                EduCorePrimaryButton("New application", onStartCreate, Modifier.fillMaxWidth(), leadingIcon = { Icon(Icons.Default.Add, null) })
            }
        }
        if (state.admissions.isEmpty() && !state.isLoading) {
            item { EduCoreEmptyState("No matching applications", "No applicants match the current status and search filters.") }
        } else {
            items(state.admissions, key = AdmissionItemDto::id) { admission -> AdmissionCard(admission) { onOpen(admission) } }
        }
        if (state.hasMore) item {
            EduCoreSecondaryButton(
                if (state.isLoadingMore) "Loading more…" else "Load more (${state.admissions.size} of ${state.filteredTotal})",
                onLoadMore, Modifier.fillMaxWidth(), enabled = !state.isLoadingMore,
            )
        }
    }
}

@Composable
private fun AdmissionDetailScreen(
    state: AdmissionsUiState,
    onBack: () -> Unit,
    onStatusDraft: (String) -> Unit,
    onClassArmDraft: (Long?) -> Unit,
    onReviewNotes: (String) -> Unit,
    onSaveStatus: () -> Unit,
    onInterviewDate: (String) -> Unit,
    onInterviewNotes: (String) -> Unit,
    onInterviewScore: (String) -> Unit,
    onScheduleInterview: () -> Unit,
    onRecordInterview: () -> Unit,
    onSendOffer: () -> Unit,
) {
    val admission = state.selectedAdmission ?: return
    val workspace = state.workspace
    LazyColumn(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader(admission.name, "${admission.applicationNumber} · ${admission.classLevel ?: "Class not selected"}", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let { item { AdmissionMessageCard(it) } }
        item { AdmissionDetailCard(admission) }
        item {
            Text("Guardian", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            InfoCard(listOf(
                "Name" to admission.guardianName,
                "Relationship" to (admission.guardianRelationship ?: "Not provided"),
                "Phone" to admission.guardianPhone,
                "Email" to (admission.guardianEmail ?: "Not provided"),
                "Address" to (admission.address ?: "Not provided"),
            ))
        }
        if (state.canScheduleInterview || state.canRecordInterview) {
            item {
                Text("Interview", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                AdmissionTextField("Interview date (YYYY-MM-DD)", state.interviewDateDraft, onChange = onInterviewDate)
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                AdmissionTextField("Interview notes", state.interviewNotesDraft, minLines = 3, onChange = onInterviewNotes)
            }
            if (state.canScheduleInterview) item {
                EduCoreSecondaryButton(
                    if (state.isSaving) "Saving interview…" else "Schedule / update interview",
                    onScheduleInterview, Modifier.fillMaxWidth(), enabled = !state.isSaving && state.interviewDateDraft.isNotBlank(),
                )
            }
            if (state.canRecordInterview) item {
                AdmissionTextField("Interview score (0-100)", state.interviewScoreDraft, onChange = onInterviewScore)
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                EduCoreSecondaryButton(
                    if (state.isSaving) "Saving result…" else "Record interview result",
                    onRecordInterview, Modifier.fillMaxWidth(), enabled = !state.isSaving && state.interviewScoreDraft.isNotBlank(),
                )
            }
        }
        if (state.canChangeStatus && workspace != null) {
            item {
                Text("Decision", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                AdmissionStatusSelector(workspace.statusOptions, state.statusDraft, onStatusDraft)
            }
            if (state.statusDraft == "admitted" && admission.enrolledStudentId == null) {
                item { AdmissionClassArmSelector(workspace.classArms, state.classArmDraft, onClassArmDraft) }
            }
            item {
                OutlinedTextField(
                    value = state.reviewNotesDraft, onValueChange = onReviewNotes, modifier = Modifier.fillMaxWidth(),
                    label = { Text("Review notes") }, minLines = 3, maxLines = 7, enabled = !state.isSaving,
                )
            }
            item {
                EduCorePrimaryButton(
                    if (state.isSaving) "Saving decision…" else "Save decision", onSaveStatus,
                    Modifier.fillMaxWidth(), enabled = !state.isSaving, loading = state.isSaving,
                )
            }
        }
        if (admission.status == "admitted" && !admission.offerLetterSent) item {
            EduCoreSecondaryButton(
                if (state.isSaving) "Sending offer…" else "Send admission offer",
                onSendOffer, Modifier.fillMaxWidth(), enabled = !state.isSaving,
            )
        }
    }
}

@Composable
private fun AdmissionCreateScreen(
    state: AdmissionsUiState,
    onBack: () -> Unit,
    onField: (AdmissionCreateField, String) -> Unit,
    onGender: (String) -> Unit,
    onClassLevel: (Long?) -> Unit,
    onCreate: () -> Unit,
) {
    val draft = state.createDraft
    LazyColumn(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader("New Application", "Capture an applicant and guardian record", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item { AdmissionTextField("First name *", draft.firstName) { onField(AdmissionCreateField.FIRST_NAME, it) } }
        item { AdmissionTextField("Last name *", draft.lastName) { onField(AdmissionCreateField.LAST_NAME, it) } }
        item { AdmissionTextField("Other names", draft.otherNames) { onField(AdmissionCreateField.OTHER_NAMES, it) } }
        item { AdmissionTextField("Date of birth * (YYYY-MM-DD)", draft.dateOfBirth) { onField(AdmissionCreateField.DATE_OF_BIRTH, it) } }
        item {
            Text("Gender *", style = MaterialTheme.typography.labelLarge)
            Spacer(Modifier.height(EduCoreSpacing.Xs))
            Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                FilterChip(draft.gender == "male", { onGender("male") }, label = { Text("Male") })
                FilterChip(draft.gender == "female", { onGender("female") }, label = { Text("Female") })
            }
        }
        state.workspace?.let { workspace -> item { AdmissionClassLevelSelector(workspace.classLevels, draft.classLevelId, onClassLevel) } }
        item { AdmissionTextField("Guardian name *", draft.guardianName) { onField(AdmissionCreateField.GUARDIAN_NAME, it) } }
        item { AdmissionTextField("Guardian phone *", draft.guardianPhone) { onField(AdmissionCreateField.GUARDIAN_PHONE, it) } }
        item { AdmissionTextField("Guardian email", draft.guardianEmail) { onField(AdmissionCreateField.GUARDIAN_EMAIL, it) } }
        item { AdmissionTextField("Guardian relationship *", draft.guardianRelationship) { onField(AdmissionCreateField.GUARDIAN_RELATIONSHIP, it) } }
        item { AdmissionTextField("Address", draft.address, 2) { onField(AdmissionCreateField.ADDRESS, it) } }
        item { AdmissionTextField("Notes", draft.notes, 3) { onField(AdmissionCreateField.NOTES, it) } }
        item {
            EduCorePrimaryButton(
                if (state.isSaving) "Creating application…" else "Create application", onCreate,
                Modifier.fillMaxWidth(), enabled = draft.valid && !state.isSaving, loading = state.isSaving,
            )
        }
    }
}

@Composable
private fun AdmissionStatsStrip(total: Int, pending: Int, shortlisted: Int, admitted: Int) {
    Row(Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
        AdmissionStat("Total", total); AdmissionStat("Pending", pending); AdmissionStat("Shortlisted", shortlisted); AdmissionStat("Admitted", admitted)
    }
}

@Composable
private fun AdmissionStat(label: String, value: Int) {
    Card(Modifier.width(112.dp), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
        Column(Modifier.padding(EduCoreSpacing.Md)) {
            Text(value.toString(), style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Bold)
            Text(label, style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
    }
}

@Composable
private fun AdmissionFilterStrip(options: List<AdmissionKeyLabelDto>, selected: String, onSelect: (String) -> Unit) {
    Row(Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
        options.forEach { option -> FilterChip(option.key == selected, { onSelect(option.key) }, label = { Text(option.label) }) }
    }
}

@Composable
private fun AdmissionCard(admission: AdmissionItemDto, onOpen: () -> Unit) {
    Card(onClick = onOpen, modifier = Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                Column(Modifier.weight(1f)) {
                    Text(admission.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    Text(admission.applicationNumber, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                Text(admission.status.replaceFirstChar { it.uppercase() }, style = MaterialTheme.typography.labelLarge, color = MaterialTheme.colorScheme.primary)
            }
            Text(listOfNotNull(admission.classLevel, admission.applicationDate).joinToString(" · "), style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            Text("Guardian: ${admission.guardianName} · ${admission.guardianPhone}", style = MaterialTheme.typography.bodySmall, maxLines = 1, overflow = TextOverflow.Ellipsis)
        }
    }
}

@Composable
private fun AdmissionDetailCard(admission: AdmissionItemDto) {
    InfoCard(listOf(
        "Status" to admission.status.replaceFirstChar { it.uppercase() },
        "Date of birth" to (admission.dateOfBirth ?: "Not provided"),
        "Gender" to (admission.gender?.replaceFirstChar { it.uppercase() } ?: "Not provided"),
        "Applied class" to (admission.classLevel ?: "Not selected"),
        "Application date" to (admission.applicationDate ?: "Not recorded"),
        "Interview" to (admission.interviewDate ?: "Not scheduled"),
        "Interview score" to (admission.interviewScore?.let { "$it%" } ?: "Not recorded"),
        "Offer letter" to if (admission.offerLetterSent) "Sent" else "Not sent",
        "Decision date" to (admission.decisionDate ?: "Not recorded"),
    ))
}

@Composable
private fun InfoCard(rows: List<Pair<String, String>>) {
    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            rows.forEach { (label, value) ->
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                    Text(label, Modifier.weight(.42f), style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Text(value, Modifier.weight(.58f), style = MaterialTheme.typography.bodyMedium)
                }
            }
        }
    }
}

@Composable
private fun AdmissionStatusSelector(options: List<AdmissionKeyLabelDto>, selected: String, onSelect: (String) -> Unit) {
    var expanded by remember { mutableStateOf(false) }
    Column(Modifier.fillMaxWidth()) {
        OutlinedButton({ expanded = true }, Modifier.fillMaxWidth()) { Text(options.firstOrNull { it.key == selected }?.label ?: selected, Modifier.weight(1f)) }
        DropdownMenu(expanded, { expanded = false }) {
            options.forEach { option -> DropdownMenuItem({ Text(option.label) }, { expanded = false; onSelect(option.key) }) }
        }
    }
}

@Composable
private fun AdmissionClassArmSelector(options: List<AdmissionClassArmDto>, selectedId: Long?, onSelect: (Long?) -> Unit) {
    var expanded by remember { mutableStateOf(false) }
    Column(Modifier.fillMaxWidth()) {
        Text("Enrol into class arm *", style = MaterialTheme.typography.labelLarge)
        Spacer(Modifier.height(EduCoreSpacing.Xs))
        OutlinedButton({ expanded = true }, Modifier.fillMaxWidth(), enabled = options.isNotEmpty()) {
            Text(options.firstOrNull { it.id == selectedId }?.name ?: "Choose class arm", Modifier.weight(1f))
        }
        DropdownMenu(expanded, { expanded = false }) {
            options.forEach { option -> DropdownMenuItem({ Text(option.name) }, { expanded = false; onSelect(option.id) }) }
        }
    }
}

@Composable
private fun AdmissionClassLevelSelector(options: List<AdmissionClassLevelDto>, selectedId: Long?, onSelect: (Long?) -> Unit) {
    var expanded by remember { mutableStateOf(false) }
    Column(Modifier.fillMaxWidth()) {
        Text("Applying for class", style = MaterialTheme.typography.labelLarge)
        Spacer(Modifier.height(EduCoreSpacing.Xs))
        OutlinedButton({ expanded = true }, Modifier.fillMaxWidth(), enabled = options.isNotEmpty()) {
            Text(options.firstOrNull { it.id == selectedId }?.name ?: "Choose class level", Modifier.weight(1f))
        }
        DropdownMenu(expanded, { expanded = false }) {
            DropdownMenuItem({ Text("No class selected") }, { expanded = false; onSelect(null) })
            options.forEach { option -> DropdownMenuItem({ Text(option.name) }, { expanded = false; onSelect(option.id) }) }
        }
    }
}

@Composable
private fun AdmissionTextField(label: String, value: String, minLines: Int = 1, onChange: (String) -> Unit) {
    OutlinedTextField(
        value, onChange, Modifier.fillMaxWidth(), label = { Text(label) }, minLines = minLines,
        maxLines = if (minLines > 1) 6 else 1, singleLine = minLines == 1,
    )
}

@Composable
private fun AdmissionMessageCard(message: String) {
    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer)) {
        Text(message, Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), color = MaterialTheme.colorScheme.onPrimaryContainer)
    }
}
