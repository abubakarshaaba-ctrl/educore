package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.NoteAdd
import androidx.compose.material.icons.filled.AutoAwesome
import androidx.compose.material.icons.filled.ChevronRight
import androidx.compose.material.icons.filled.Description
import androidx.compose.material.icons.filled.Publish
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreFilterChip
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSegmentedControl
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseSectionCard
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseStat
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTextField
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.component.EduCoreWarningBanner
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.model.LessonPlan

@Composable
internal fun LessonPlannerListScreen(
    state: AcademicContentUiState,
    onBack: () -> Unit,
    onNew: () -> Unit,
    onOpen: (Long) -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.lessonPlans.isEmpty()) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading lesson plans")
    }

    val published = state.lessonPlans.count { it.status.equals("published", ignoreCase = true) }
    val drafts = state.lessonPlans.size - published
    val notesReady = state.lessonPlans.count(LessonPlan::hasNote)

    LazyColumn(
        modifier = Modifier.fillMaxSize().imePadding().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Lesson Planner",
                subtitle = "Create, generate, review and publish from one native workspace",
                onBack = onBack,
            )
        }

        item {
            EduCoreShowcaseHero(
                eyebrow = "TEACHING WORKSPACE",
                title = "Plan lessons with structure.",
                subtitle = "Build teacher-controlled lesson plans and student notes without leaving the EduCore app.",
                trailing = {
                    Surface(
                        modifier = Modifier.size(48.dp),
                        shape = CircleShape,
                        color = EduCoreColors.Gold100,
                        contentColor = EduCoreColors.Navy900,
                    ) {
                        Box(contentAlignment = Alignment.Center) {
                            Icon(Icons.Default.AutoAwesome, contentDescription = null, modifier = Modifier.size(24.dp))
                        }
                    }
                },
            )
        }

        item {
            EduCorePrimaryButton(
                text = "Create new lesson plan",
                onClick = onNew,
                modifier = Modifier.fillMaxWidth(),
                leadingIcon = { Icon(Icons.AutoMirrored.Filled.NoteAdd, contentDescription = null) },
            )
        }

        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        if (state.options?.isFromCache == true) {
            item { EduCoreWarningBanner("Showing planning options saved on this device.") }
        }

        if (state.lessonPlans.isNotEmpty()) {
            item {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                ) {
                    EduCoreShowcaseStat(
                        label = "Drafts",
                        value = drafts.toString(),
                        icon = Icons.Default.Description,
                        tone = EduCoreTone.Info,
                        modifier = Modifier.weight(1f),
                    )
                    EduCoreShowcaseStat(
                        label = "Published",
                        value = published.toString(),
                        icon = Icons.Default.Publish,
                        tone = EduCoreTone.Success,
                        modifier = Modifier.weight(1f),
                    )
                    EduCoreShowcaseStat(
                        label = "Notes ready",
                        value = notesReady.toString(),
                        icon = Icons.Default.AutoAwesome,
                        tone = EduCoreTone.Accent,
                        modifier = Modifier.weight(1f),
                    )
                }
            }
        }

        item {
            EduCoreSectionHeader(
                title = "Lesson plans",
                supportingText = if (state.lessonPlans.isEmpty()) {
                    "Your saved lesson plans will appear here"
                } else {
                    "Open a plan to edit content, generate notes or publish"
                },
            )
        }

        if (state.lessonPlans.isEmpty() && !state.isLoading) {
            item {
                EduCoreShowcaseSectionCard {
                    EduCoreEmptyState(
                        title = "No lesson plans yet",
                        message = "Create your first plan, add the teaching details, generate content and review it before publication.",
                    )
                }
            }
        }

        items(state.lessonPlans, key = LessonPlan::id) { plan ->
            LessonPlanCard(plan) { onOpen(plan.id) }
        }

        if (state.errorMessage != null && state.lessonPlans.isEmpty()) {
            item { EduCoreSecondaryButton("Try again", onRetry, Modifier.fillMaxWidth()) }
        }

        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

@Composable
private fun LessonPlanCard(plan: LessonPlan, onOpen: () -> Unit) {
    Card(
        onClick = onOpen,
        modifier = Modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.large,
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            Surface(
                modifier = Modifier.size(44.dp),
                color = if (plan.status.equals("published", true)) EduCoreColors.Success100 else EduCoreColors.Gold50,
                contentColor = EduCoreColors.Navy900,
                shape = CircleShape,
            ) {
                Box(contentAlignment = Alignment.Center) {
                    Icon(Icons.Default.Description, contentDescription = null, modifier = Modifier.size(21.dp))
                }
            }

            Column(
                modifier = Modifier.weight(1f),
                verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs),
            ) {
                Text(
                    text = plan.topic.ifBlank { "Untitled lesson plan" },
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    color = EduCoreColors.Ink900,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis,
                )
                Text(
                    text = listOfNotNull(
                        plan.subject?.name,
                        plan.classArm?.name ?: plan.classLevel?.name,
                        plan.term?.name,
                    ).joinToString(" · "),
                    style = MaterialTheme.typography.bodySmall,
                    color = EduCoreColors.Slate600,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis,
                )
                Text(
                    text = "${plan.curriculumType.uppercase()} · ${plan.durationMinutes} min",
                    style = MaterialTheme.typography.bodySmall,
                    color = EduCoreColors.Muted500,
                )
                Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                    EduCoreStatusBadge(
                        text = plan.status.replaceFirstChar(Char::uppercase),
                        tone = if (plan.status.equals("published", true)) EduCoreTone.Success else EduCoreTone.Warning,
                    )
                    EduCoreStatusBadge(
                        text = if (plan.hasNote) "Student note ready" else "Plan only",
                        tone = if (plan.hasNote) EduCoreTone.Accent else EduCoreTone.Neutral,
                    )
                }
            }

            Icon(Icons.Default.ChevronRight, contentDescription = "Open lesson plan", tint = EduCoreColors.Gold700)
        }
    }
}

@Composable
internal fun LessonPlanEditorScreen(
    state: AcademicContentUiState,
    online: Boolean,
    onBack: () -> Unit,
    onClass: (Long) -> Unit,
    onSubject: (Long) -> Unit,
    onTerm: (Long) -> Unit,
    onCurriculum: (String) -> Unit,
    onDelivery: (String) -> Unit,
    onTopic: (String) -> Unit,
    onSubtopic: (String) -> Unit,
    onWeek: (String) -> Unit,
    onDuration: (String) -> Unit,
    onLessonNumber: (String) -> Unit,
    onLessonTime: (String) -> Unit,
    onAverageAge: (String) -> Unit,
    onSex: (String) -> Unit,
    onPlanDate: (String) -> Unit,
    onSection: (String, String) -> Unit,
    onSave: () -> Unit,
    onGenerate: () -> Unit,
    onGenerateNote: () -> Unit,
    onUpdateNote: (String, String) -> Unit,
    onPublish: () -> Unit,
    onDownloadPlan: () -> Unit,
    onDownloadNote: () -> Unit,
    onDocumentOpened: () -> Unit,
) {
    OpenDocumentEffect(state.downloadedDocument, onDocumentOpened)
    if (state.isLoading && state.draft == null) {
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Opening lesson plan")
    }
    val draft = state.draft ?: return EduCoreErrorState(
        state.errorMessage ?: "The lesson plan is unavailable.",
        Modifier.fillMaxSize(),
    )
    val plan = state.lessonPlan
    val options = state.options
    var selectedTab by remember(plan?.id) { mutableIntStateOf(0) }
    var noteText by remember(plan?.id, plan?.note?.revision) { mutableStateOf(plan?.note?.plainText.orEmpty()) }
    val assignment = options?.assignments?.firstOrNull { it.classArmId == draft.classArmId }

    LazyColumn(
        modifier = Modifier.fillMaxSize().imePadding().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = if (plan == null) "New lesson plan" else plan.topic.ifBlank { "Lesson plan" },
                subtitle = "Teacher-controlled content · Save before publishing",
                onBack = onBack,
            ) {
                plan?.let { StatusPill(it.status) }
            }
        }

        if (!online) {
            item { EduCoreWarningBanner("Offline: editing is available, but saving, AI generation and publication require a connection.") }
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }

        if (plan != null) {
            item {
                EduCoreSegmentedControl(
                    listOf("Lesson details", "Plan content", "Student note"),
                    selectedTab,
                    { selectedTab = it },
                    Modifier.fillMaxWidth(),
                )
            }
        }

        if (plan == null || selectedTab == 0) {
            item {
                EduCoreSectionHeader(
                    title = "Lesson details",
                    supportingText = "Define the class, subject, curriculum and delivery information",
                )
            }
            item { SelectorRow("Class", options?.assignments.orEmpty().map { it.classArmId to it.className }, draft.classArmId, onClass) }
            item { SelectorRow("Subject", assignment?.subjects.orEmpty().map { it.id to it.name }, draft.subjectId, onSubject) }
            item { SelectorRow("Term", options?.terms.orEmpty().map { it.id to listOfNotNull(it.name, it.session).joinToString(" · ") }, draft.termId, onTerm) }
            item { StringSelectorRow("Curriculum", options?.curriculumTypes.orEmpty().map { it.key to it.label }, draft.curriculumType, onCurriculum) }
            item { StringSelectorRow("Delivery", options?.deliveryTypes.orEmpty().map { it.key to it.label }, draft.deliveryType, onDelivery) }
            item { EduCoreTextField(draft.topic, onTopic, "Topic", Modifier.fillMaxWidth()) }
            item { EduCoreTextField(draft.subtopic, onSubtopic, "Subtopic(s)", Modifier.fillMaxWidth(), singleLine = false) }
            item {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreTextField(
                        draft.weekNumber,
                        onWeek,
                        "Week",
                        Modifier.weight(1f),
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    )
                    EduCoreTextField(
                        draft.durationMinutes,
                        onDuration,
                        "Duration (min)",
                        Modifier.weight(1f),
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    )
                }
            }
            item {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreTextField(draft.lessonNumber, onLessonNumber, "Lesson", Modifier.weight(1f))
                    EduCoreTextField(draft.lessonTime, onLessonTime, "Time", Modifier.weight(1f))
                }
            }
            item {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreTextField(draft.averageAge, onAverageAge, "Average age", Modifier.weight(1f))
                    EduCoreTextField(draft.sex, onSex, "Sex", Modifier.weight(1f))
                }
            }
            item { EduCoreTextField(draft.planDate, onPlanDate, "Date (YYYY-MM-DD)", Modifier.fillMaxWidth()) }
        }

        if (plan != null && selectedTab == 1) {
            item {
                EduCoreSectionHeader(
                    title = "Plan content",
                    supportingText = "Review and edit every generated section before publication",
                )
            }
            if (plan.sections.isEmpty()) {
                item { EduCoreEmptyState("No generated content", "Generate the plan, then edit every section before publication.") }
            }
            items(plan.sections, key = { it.key }) { section ->
                EduCoreTextField(
                    value = draft.sections[section.key].orEmpty(),
                    onValueChange = { onSection(section.key, it) },
                    label = section.label,
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = false,
                )
            }
        }

        if (plan != null && selectedTab == 2) {
            item {
                EduCoreSectionHeader(
                    title = "Student note",
                    supportingText = "The teacher controls the final note that students receive",
                )
            }
            if (plan.note == null) {
                item { EduCoreEmptyState("No student note", "Generate a rich student note from the plan and repository sources.") }
            } else {
                item {
                    EduCoreTextField(
                        noteText,
                        { noteText = it },
                        "Student note",
                        Modifier.fillMaxWidth(),
                        singleLine = false,
                        supportingText = "Teacher-controlled final content",
                    )
                }
                item {
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                        EduCoreSecondaryButton(
                            "Save draft",
                            { onUpdateNote(noteText, "draft") },
                            Modifier.weight(1f),
                            enabled = online && !state.isSaving,
                        )
                        EduCorePrimaryButton(
                            "Publish note",
                            { onUpdateNote(noteText, "published") },
                            Modifier.weight(1f),
                            enabled = online,
                            loading = state.isSaving,
                        )
                    }
                }
                item { EduCoreSecondaryButton("Download note PDF", onDownloadNote, Modifier.fillMaxWidth(), enabled = online && !state.isSaving) }
            }
        }

        item {
            EduCoreShowcaseSectionCard {
                Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreSectionHeader(
                        title = "Plan actions",
                        supportingText = if (plan == null) "Save the basic details to unlock generation" else "Generate, export or publish this lesson plan",
                    )
                    EduCorePrimaryButton(
                        if (plan == null) "Save draft" else "Save changes",
                        onSave,
                        Modifier.fillMaxWidth(),
                        enabled = online,
                        loading = state.isSaving,
                    )
                    if (plan != null) {
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                            EduCoreSecondaryButton("Generate plan", onGenerate, Modifier.weight(1f), enabled = online && !state.isSaving)
                            EduCoreSecondaryButton("Generate note", onGenerateNote, Modifier.weight(1f), enabled = online && !state.isSaving)
                        }
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                            EduCoreSecondaryButton("Plan PDF", onDownloadPlan, Modifier.weight(1f), enabled = online && !state.isSaving)
                            if (plan.status != "published") {
                                EduCorePrimaryButton("Publish plan", onPublish, Modifier.weight(1f), enabled = online, loading = state.isSaving)
                            }
                        }
                    }
                }
            }
        }

        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

@Composable
private fun <T> SelectorRow(
    label: String,
    options: List<Pair<T, String>>,
    selected: T?,
    onSelected: (T) -> Unit,
) {
    Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
        Text(label.uppercase(), style = MaterialTheme.typography.labelMedium, color = EduCoreColors.Slate600)
        Row(
            Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            options.forEach { (key, value) ->
                EduCoreFilterChip(value, key == selected, { onSelected(key) })
            }
        }
    }
}

@Composable
private fun StringSelectorRow(
    label: String,
    options: List<Pair<String, String>>,
    selected: String,
    onSelected: (String) -> Unit,
) = SelectorRow(label, options, selected, onSelected)

@Composable
private fun StatusPill(status: String) {
    Surface(
        color = if (status.equals("published", true)) EduCoreColors.Success100 else EduCoreColors.Warning100,
        shape = MaterialTheme.shapes.extraLarge,
    ) {
        Text(
            status.replaceFirstChar(Char::uppercase),
            Modifier.padding(horizontal = 10.dp, vertical = 5.dp),
            color = EduCoreColors.Navy900,
            style = MaterialTheme.typography.labelMedium,
        )
    }
}
