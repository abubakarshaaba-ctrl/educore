package online.educoreng.educore.presentation

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
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
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
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.SkillClassOptionDto
import online.educoreng.educore.core.network.dto.SkillDefinitionDto
import online.educoreng.educore.core.network.dto.SkillTermOptionDto

@Composable
internal fun SkillsScreen(
    state: SkillsUiState,
    onBack: () -> Unit,
    onClass: (Long?) -> Unit,
    onTerm: (Long?) -> Unit,
    onOpenSheet: () -> Unit,
    onCategory: (SkillCategory) -> Unit,
    onPreviousStudent: () -> Unit,
    onNextStudent: () -> Unit,
    onRate: (Long, Int) -> Unit,
    onSave: () -> Unit,
    onRetry: () -> Unit,
    onCancelDiscard: () -> Unit,
    onConfirmDiscard: () -> Unit,
) {
    if (state.discardPending) {
        AlertDialog(
            onDismissRequest = onCancelDiscard,
            title = { Text("Discard unsaved ratings?") },
            text = { Text("You have rating changes that have not been saved. Discard them and close this sheet?") },
            confirmButton = {
                TextButton(onClick = onConfirmDiscard) { Text("Discard") }
            },
            dismissButton = {
                TextButton(onClick = onCancelDiscard) { Text("Keep editing") }
            },
        )
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Skill Ratings",
                subtitle = if (state.inSheet) {
                    listOfNotNull(state.sheet?.classInfo?.name, state.sheet?.term?.name).joinToString(" · ")
                } else {
                    "Behavioural and psychomotor assessment"
                },
                onBack = onBack,
            )
        }

        if (!state.inSheet) {
            item {
                EduCoreShowcaseHero(
                    eyebrow = "CONTINUOUS ASSESSMENT",
                    title = "Rate the whole child, not only academic scores.",
                    subtitle = "Record behavioural and psychomotor development on the same 1–5 scale used by EduCore report cards.",
                )
            }
        }

        state.errorMessage?.let { message ->
            item {
                if (state.workspace == null && !state.inSheet) {
                    EduCoreErrorState(message = message, onRetry = onRetry)
                } else {
                    EduCoreErrorBanner(message = message)
                }
            }
        }

        if (state.isLoading && state.workspace == null) {
            item { SkillsLoading("Loading skill-rating workspace…") }
        } else if (!state.inSheet) {
            val workspace = state.workspace
            if (workspace != null) {
                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    ) {
                        Column(
                            modifier = Modifier.padding(18.dp),
                            verticalArrangement = Arrangement.spacedBy(8.dp),
                        ) {
                            Text("Workspace summary", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                            Text(
                                "${workspace.metrics.classes} class(es) · ${workspace.metrics.students} active student(s) · ${workspace.metrics.skills} skill(s)",
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                            Text(
                                "${workspace.metrics.ratedEntries} rating entries currently stored",
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                        }
                    }
                }

                if (workspace.classes.isEmpty()) {
                    item {
                        EduCoreEmptyState(
                            title = "No accessible classes",
                            message = "No class is currently available for skill rating under your account.",
                        )
                    }
                } else if (workspace.terms.isEmpty()) {
                    item {
                        EduCoreEmptyState(
                            title = "No academic term",
                            message = "Create or activate an academic term before recording skill ratings.",
                        )
                    }
                } else {
                    item {
                        SkillsSelector(
                            label = "Class",
                            selected = workspace.classes.firstOrNull { it.id == state.selectedClassId }
                                ?.let(::classLabel)
                                ?: "Choose class",
                            options = workspace.classes.map { SkillsChoice(it.id, classLabel(it)) },
                            onSelected = onClass,
                        )
                    }
                    item {
                        SkillsSelector(
                            label = "Academic term",
                            selected = workspace.terms.firstOrNull { it.id == state.selectedTermId }
                                ?.let(::termLabel)
                                ?: "Choose term",
                            options = workspace.terms.map { SkillsChoice(it.id, termLabel(it)) },
                            onSelected = onTerm,
                        )
                    }
                    item {
                        EduCorePrimaryButton(
                            text = "Open rating sheet",
                            onClick = onOpenSheet,
                            enabled = state.canOpenSheet && !state.isLoading,
                            loading = state.isLoading,
                            modifier = Modifier.fillMaxWidth(),
                        )
                    }
                }
            }
        } else {
            val sheet = state.sheet
            val student = state.currentStudent
            if (sheet != null && student != null) {
                item {
                    Row(
                        modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                    ) {
                        SkillCategory.entries.forEach { category ->
                            val count = sheet.skills.count { it.category.equals(category.key, ignoreCase = true) }
                            if (count > 0) {
                                FilterChip(
                                    selected = state.category == category,
                                    onClick = { onCategory(category) },
                                    label = { Text("${category.label} ($count)") },
                                )
                            }
                        }
                    }
                }

                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    ) {
                        Column(
                            modifier = Modifier.padding(18.dp),
                            verticalArrangement = Arrangement.spacedBy(10.dp),
                        ) {
                            Text(
                                "Student ${state.studentIndex + 1} of ${state.studentCount}",
                                style = MaterialTheme.typography.labelLarge,
                                color = MaterialTheme.colorScheme.primary,
                            )
                            Text(student.name, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                            student.admissionNumber?.takeIf(String::isNotBlank)?.let {
                                Text(it, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(10.dp),
                            ) {
                                OutlinedButton(
                                    onClick = onPreviousStudent,
                                    enabled = state.studentIndex > 0,
                                    modifier = Modifier.weight(1f),
                                ) { Text("Previous") }
                                OutlinedButton(
                                    onClick = onNextStudent,
                                    enabled = state.studentIndex < state.studentCount - 1,
                                    modifier = Modifier.weight(1f),
                                ) { Text("Next") }
                            }
                        }
                    }
                }

                if (state.categorySkills.isEmpty()) {
                    item {
                        EduCoreEmptyState(
                            title = "No active ${state.category.label.lowercase()} skills",
                            message = "There are no active skill definitions in this category.",
                        )
                    }
                } else {
                    state.categorySkills.forEach { skill ->
                        item(key = "skill-${student.id}-${skill.id}") {
                            SkillRatingCard(
                                skill = skill,
                                rating = state.draftRatings[SkillRatingKey(student.id, skill.id)],
                                scale = sheet.ratingScale.associate { it.value to it.label },
                                enabled = state.canManage && !state.isSaving,
                                onRate = { value -> onRate(skill.id, value) },
                            )
                        }
                    }
                }

                item {
                    EduCorePrimaryButton(
                        text = if (state.dirty) "Save rating changes" else "Ratings saved",
                        onClick = onSave,
                        enabled = state.canManage && state.dirty && !state.isSaving,
                        loading = state.isSaving,
                        modifier = Modifier.fillMaxWidth(),
                    )
                }

                state.message?.let { message ->
                    item {
                        Text(
                            text = message,
                            color = MaterialTheme.colorScheme.primary,
                            style = MaterialTheme.typography.bodyMedium,
                        )
                    }
                }
            } else if (state.isLoading) {
                item { SkillsLoading("Loading rating sheet…") }
            } else {
                item {
                    EduCoreEmptyState(
                        title = "No students in this class",
                        message = "The selected class does not currently contain active students to rate.",
                    )
                }
            }
        }
    }
}

@Composable
private fun SkillRatingCard(
    skill: SkillDefinitionDto,
    rating: Int?,
    scale: Map<Int, String>,
    enabled: Boolean,
    onRate: (Int) -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            Text(skill.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
            Text(
                rating?.let { scale[it] ?: "Rating $it" } ?: "Not rated",
                color = if (rating == null) MaterialTheme.colorScheme.onSurfaceVariant else MaterialTheme.colorScheme.primary,
            )
            Row(
                modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
            ) {
                (1..5).forEach { value ->
                    FilterChip(
                        selected = rating == value,
                        onClick = { onRate(value) },
                        enabled = enabled,
                        label = { Text(value.toString()) },
                    )
                }
                TextButton(
                    onClick = { onRate(0) },
                    enabled = enabled && rating != null,
                ) { Text("Clear") }
            }
        }
    }
}

private data class SkillsChoice(val id: Long?, val label: String)

@Composable
private fun SkillsSelector(
    label: String,
    selected: String,
    options: List<SkillsChoice>,
    onSelected: (Long?) -> Unit,
) {
    var expanded by remember { mutableStateOf(false) }
    Column(Modifier.fillMaxWidth()) {
        Text(label, style = MaterialTheme.typography.labelLarge)
        Spacer(Modifier.height(6.dp))
        OutlinedButton(
            onClick = { expanded = true },
            enabled = options.isNotEmpty(),
            modifier = Modifier.fillMaxWidth(),
        ) {
            Text(selected, modifier = Modifier.weight(1f))
        }
        DropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
            options.forEach { choice ->
                DropdownMenuItem(
                    text = { Text(choice.label) },
                    onClick = {
                        expanded = false
                        onSelected(choice.id)
                    },
                )
            }
        }
    }
}

@Composable
private fun SkillsLoading(label: String) {
    Column(
        modifier = Modifier.fillMaxWidth().padding(vertical = 32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        CircularProgressIndicator()
        Spacer(Modifier.height(10.dp))
        Text(label)
    }
}

private fun classLabel(option: SkillClassOptionDto): String = buildString {
    append(option.name)
    append(" · ")
    append(option.studentCount)
    append(if (option.studentCount == 1) " student" else " students")
    option.formTutor?.takeIf(String::isNotBlank)?.let {
        append(" · ")
        append(it)
    }
}

private fun termLabel(option: SkillTermOptionDto): String =
    listOfNotNull(option.name, option.session).joinToString(" · ") + if (option.current) " · Current" else ""
