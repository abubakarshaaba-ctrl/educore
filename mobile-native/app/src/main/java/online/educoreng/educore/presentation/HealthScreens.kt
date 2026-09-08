package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreMetricCard
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTextField
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.HealthStudentDto

@Composable
internal fun NativeHealthScreen(
    state: HealthUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onOpen: (Long) -> Unit,
    onCloseDetail: () -> Unit,
    onField: (HealthField, String) -> Unit,
    onSave: () -> Unit,
    onLoadMore: () -> Unit,
    onRetry: () -> Unit,
) {
    val detail = state.detail
    if (detail != null) {
        HealthDetailScreen(
            state = state,
            onBack = onCloseDetail,
            onField = onField,
            onSave = onSave,
        )
        return
    }

    if (state.isLoading && state.dashboard == null) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Loading student health register")
        return
    }

    val dashboard = state.dashboard
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Health Records",
                subtitle = "Student health coverage, alerts and emergency information",
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let {
            item {
                Text(it, style = MaterialTheme.typography.bodyMedium, color = EduCoreColors.Success700)
            }
        }

        dashboard?.let { data ->
            item {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreMetricCard("Students", data.metrics.students.toString(), Modifier.weight(1f), tone = EduCoreTone.Brand)
                    EduCoreMetricCard("Records", data.metrics.records.toString(), Modifier.weight(1f), tone = EduCoreTone.Info)
                }
            }
            item {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreMetricCard("Allergy alerts", data.metrics.allergyAlerts.toString(), Modifier.weight(1f), tone = EduCoreTone.Warning)
                    EduCoreMetricCard("Medication alerts", data.metrics.medicationAlerts.toString(), Modifier.weight(1f), tone = EduCoreTone.Warning)
                }
            }
        }

        item {
            EduCoreSearchBar(
                value = state.query,
                onValueChange = onQuery,
                placeholder = "Search student or admission number",
                enabled = !state.isLoading,
            )
        }
        item {
            EduCoreSecondaryButton(
                text = "Search health register",
                onClick = onSearch,
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isLoading,
            )
        }

        if (state.students.isEmpty() && !state.isLoading) {
            item {
                EduCoreEmptyState(
                    title = if (state.query.isBlank()) "No active students" else "No matching students",
                    message = if (state.query.isBlank()) {
                        "Active students will appear here for health-record review."
                    } else {
                        "Try another student name or admission number."
                    },
                )
            }
        } else {
            items(state.students, key = HealthStudentDto::id) { student ->
                HealthStudentCard(student = student, onOpen = { onOpen(student.id) })
            }
        }

        if (state.hasMore) {
            item {
                EduCoreSecondaryButton(
                    text = if (state.isLoadingMore) "Loading…" else "Load more students",
                    onClick = onLoadMore,
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !state.isLoadingMore,
                )
            }
        }

        if (state.errorMessage != null && state.dashboard == null) {
            item {
                EduCoreSecondaryButton(
                    text = "Retry",
                    onClick = onRetry,
                    modifier = Modifier.fillMaxWidth(),
                )
            }
        }
    }
}

@Composable
private fun HealthStudentCard(student: HealthStudentDto, onOpen: () -> Unit) {
    Card(
        onClick = onOpen,
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
                Column(Modifier.weight(1f)) {
                    Text(student.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    Text(
                        listOfNotNull(student.admissionNumber, student.className).joinToString(" · ").ifBlank { "Student" },
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                EduCoreStatusBadge(
                    text = if (student.hasRecord) "Recorded" else "No record",
                    tone = if (student.hasRecord) EduCoreTone.Success else EduCoreTone.Neutral,
                )
            }
            Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                if (student.allergyAlert) EduCoreStatusBadge("Allergy alert", EduCoreTone.Warning)
                if (student.medicationAlert) EduCoreStatusBadge("Medication", EduCoreTone.Warning)
                if (!student.allergyAlert && !student.medicationAlert && student.hasRecord) {
                    EduCoreStatusBadge("No active alerts", EduCoreTone.Success)
                }
            }
        }
    }
}

@Composable
private fun HealthDetailScreen(
    state: HealthUiState,
    onBack: () -> Unit,
    onField: (HealthField, String) -> Unit,
    onSave: () -> Unit,
) {
    val detail = state.detail ?: return
    val editable = state.canManage

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = detail.student.name,
                subtitle = listOfNotNull(detail.student.admissionNumber, detail.student.className).joinToString(" · "),
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let {
            item { Text(it, style = MaterialTheme.typography.bodyMedium, color = EduCoreColors.Success700) }
        }
        if (!editable) {
            item {
                Text(
                    "This account has read-only health access.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
        }

        item { HealthSectionTitle("Clinical profile") }
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                HealthInput("Blood group", state.draft.bloodGroup, HealthField.BLOOD_GROUP, editable, onField, Modifier.weight(1f))
                HealthInput("Genotype", state.draft.genotype, HealthField.GENOTYPE, editable, onField, Modifier.weight(1f))
            }
        }
        item { HealthInput("Allergies", state.draft.allergies, HealthField.ALLERGIES, editable, onField, multiline = true) }
        item { HealthInput("Chronic conditions", state.draft.chronicConditions, HealthField.CHRONIC_CONDITIONS, editable, onField, multiline = true) }
        item { HealthInput("Current medications", state.draft.currentMedications, HealthField.CURRENT_MEDICATIONS, editable, onField, multiline = true) }
        item { HealthInput("Disability / accessibility needs", state.draft.disability, HealthField.DISABILITY, editable, onField, multiline = true) }

        item { HealthSectionTitle("Emergency contact") }
        item { HealthInput("Contact name", state.draft.emergencyContactName, HealthField.EMERGENCY_CONTACT_NAME, editable, onField) }
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                HealthInput("Phone", state.draft.emergencyContactPhone, HealthField.EMERGENCY_CONTACT_PHONE, editable, onField, Modifier.weight(1f))
                HealthInput("Relationship", state.draft.emergencyContactRelationship, HealthField.EMERGENCY_CONTACT_RELATIONSHIP, editable, onField, Modifier.weight(1f))
            }
        }

        item { HealthSectionTitle("Doctor / care provider") }
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                HealthInput("Doctor name", state.draft.doctorName, HealthField.DOCTOR_NAME, editable, onField, Modifier.weight(1f))
                HealthInput("Doctor phone", state.draft.doctorPhone, HealthField.DOCTOR_PHONE, editable, onField, Modifier.weight(1f))
            }
        }
        item { HealthInput("Notes", state.draft.notes, HealthField.NOTES, editable, onField, multiline = true) }

        if (editable) {
            item {
                EduCorePrimaryButton(
                    text = "Save health record",
                    onClick = onSave,
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !state.isSaving,
                    loading = state.isSaving,
                )
            }
        }
    }
}

@Composable
private fun HealthSectionTitle(text: String) {
    Text(text, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
}

@Composable
private fun HealthInput(
    label: String,
    value: String,
    field: HealthField,
    editable: Boolean,
    onField: (HealthField, String) -> Unit,
    modifier: Modifier = Modifier.fillMaxWidth(),
    multiline: Boolean = false,
) {
    EduCoreTextField(
        value = value,
        onValueChange = { onField(field, it) },
        label = label,
        modifier = modifier,
        enabled = editable,
        readOnly = !editable,
        singleLine = !multiline,
    )
}
