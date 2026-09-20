package online.educoreng.educore.presentation

import android.app.Activity
import android.content.Context
import android.content.ContextWrapper
import android.graphics.BitmapFactory
import android.view.WindowManager
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.Image
import androidx.compose.foundation.clickable
import androidx.compose.foundation.focusable
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
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.itemsIndexed
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Flag
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Checkbox
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.focus.FocusRequester
import androidx.compose.ui.focus.focusRequester
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.input.key.Key
import androidx.compose.ui.input.key.KeyEventType
import androidx.compose.ui.input.key.key
import androidx.compose.ui.input.key.onPreviewKeyEvent
import androidx.compose.ui.input.key.type
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import androidx.lifecycle.compose.LocalLifecycleOwner
import androidx.core.view.WindowCompat
import androidx.core.view.WindowInsetsCompat
import online.educoreng.educore.core.designsystem.component.EduCoreDangerButton
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreWarningBanner
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.model.CbtPreflight
import online.educoreng.educore.core.model.CbtQuestion

@Composable
internal fun CbtExamsScreen(
    state: CbtUiState,
    onBack: () -> Unit,
    onOpen: (Long) -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading && state.exams.isEmpty()) return EduCoreLoadingState(Modifier.fillMaxSize(), "Loading examinations")
    if (state.errorMessage != null && state.exams.isEmpty()) return EduCoreErrorState(state.errorMessage, Modifier.fillMaxSize(), onRetry = onRetry)
    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { ScreenHeading("CBT examinations", "Available examinations for your assigned class", onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        if (state.exams.isEmpty()) {
            item { EduCoreEmptyState("No examination available", "Published examinations will appear here when their access window is configured.") }
        }
        items(state.exams, key = { it.exam.id }) { preflight -> ExamAccessCard(preflight, onOpen) }
    }
}

@Composable
private fun ExamAccessCard(preflight: CbtPreflight, onOpen: (Long) -> Unit) {
    Card(colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
        Column(Modifier.padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.Top) {
                Column(Modifier.weight(1f)) {
                    Text(preflight.exam.title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                    Text(preflight.exam.subject.orEmpty(), style = MaterialTheme.typography.bodyMedium, color = EduCoreColors.Slate600)
                }
                StatusPill(preflight.windowState)
            }
            Text(
                "${preflight.exam.durationMinutes} min · ${preflight.exam.totalQuestions} questions · ${preflight.exam.totalMarks.formatMark()} marks",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Muted500,
            )
            EduCorePrimaryButton(
                text = if (preflight.canResume) "Resume examination" else "Review access",
                onClick = { onOpen(preflight.exam.id) },
                modifier = Modifier.fillMaxWidth(),
            )
        }
    }
}

@Composable
internal fun CbtPreflightScreen(
    state: CbtUiState,
    online: Boolean,
    onBack: () -> Unit,
    onBegin: () -> Unit,
    onResume: () -> Unit,
    onRetry: () -> Unit,
) {
    if (state.isLoading || state.preflight == null) {
        if (state.errorMessage != null) return EduCoreErrorState(state.errorMessage, Modifier.fillMaxSize(), onRetry = onRetry)
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Checking examination access")
    }
    val preflight = requireNotNull(state.preflight)
    var acknowledged by remember(preflight.exam.id) { mutableStateOf(false) }
    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { ScreenHeading(preflight.exam.title, preflight.exam.subject ?: "CBT examination", onBack) }
        if (!online) item { EduCoreWarningBanner("An internet connection is required to begin or resume this examination.") }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item {
            Card(colors = CardDefaults.cardColors(containerColor = EduCoreColors.Navy900)) {
                Row(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), horizontalArrangement = Arrangement.SpaceBetween) {
                    ExamMetric("Time", "${preflight.exam.durationMinutes} min")
                    ExamMetric("Questions", preflight.exam.totalQuestions.toString())
                    ExamMetric("Marks", preflight.exam.totalMarks.formatMark())
                }
            }
        }
        item {
            Card(colors = CardDefaults.cardColors(containerColor = EduCoreColors.Info100), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
                Column(Modifier.padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.Lock, null, tint = EduCoreColors.Navy900)
                        Spacer(Modifier.size(EduCoreSpacing.Sm))
                        Text("Examination integrity", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
                    }
                    Text(preflight.integrityNotice.message, style = MaterialTheme.typography.bodySmall)
                    Text("• Secure-screen protection is enabled.", style = MaterialTheme.typography.bodySmall)
                    Text("• Focus-loss policy: ${preflight.integrityNotice.focusLossPolicy.replace('_', ' ')}.", style = MaterialTheme.typography.bodySmall)
                }
            }
        }
        item {
            Row(Modifier.fillMaxWidth().clickable { acknowledged = !acknowledged }, verticalAlignment = Alignment.Top) {
                Checkbox(acknowledged, { acknowledged = it })
                Text(
                    "I have read and understood the examination rules. I consent to integrity-event recording during this attempt.",
                    modifier = Modifier.padding(top = 12.dp),
                    style = MaterialTheme.typography.bodyMedium,
                )
            }
        }
        item {
            EduCorePrimaryButton(
                text = if (preflight.canResume) "Resume examination" else "Begin examination",
                onClick = if (preflight.canResume) onResume else onBegin,
                enabled = online && acknowledged && (preflight.canResume || preflight.canBegin),
                loading = state.isLoading,
                modifier = Modifier.fillMaxWidth(),
            )
        }
    }
}

@Composable
internal fun CbtAttemptScreen(
    state: CbtUiState,
    online: Boolean,
    onSection: (Int) -> Unit,
    onQuestion: (Int) -> Unit,
    onPrevious: () -> Unit,
    onNext: () -> Unit,
    onAnswer: (Long, String) -> Unit,
    onFlag: (Long) -> Unit,
    onSubmit: () -> Unit,
    onFocusLost: () -> Unit,
    onRetry: () -> Unit,
    onExit: () -> Unit,
) {
    SecureExamWindow(state.preflight?.exam?.requireFullscreen == true, onFocusLost)
    if (state.isLoading || state.attempt == null) {
        if (state.errorMessage != null) return EduCoreErrorState(state.errorMessage, Modifier.fillMaxSize(), onRetry = onRetry)
        return EduCoreLoadingState(Modifier.fillMaxSize(), "Opening secure examination")
    }
    val attempt = requireNotNull(state.attempt)
    if (state.isFinal) return CbtCompletion(state, onExit)
    val section = state.activeSection ?: return EduCoreErrorState("This attempt has no active examination section.", Modifier.fillMaxSize(), onRetry = onRetry)
    val unit = state.activeUnit ?: return EduCoreErrorState("This section has no configured questions.", Modifier.fillMaxSize(), onRetry = onRetry)
    val focusRequester = remember { FocusRequester() }
    var confirmSubmit by remember { mutableStateOf(false) }
    LaunchedEffect(unit.id) { focusRequester.requestFocus() }

    LazyColumn(
        modifier = Modifier
            .fillMaxSize()
            .focusRequester(focusRequester)
            .focusable()
            .onPreviewKeyEvent { event ->
                if (event.type != KeyEventType.KeyDown) return@onPreviewKeyEvent false
                when (event.key) {
                    Key.DirectionLeft, Key.P -> { onPrevious(); true }
                    Key.DirectionRight, Key.N -> { onNext(); true }
                    Key.A, Key.B, Key.C, Key.D -> {
                        if (section.answerMode != "online") false else {
                            val key = when (event.key) { Key.A -> "a"; Key.B -> "b"; Key.C -> "c"; else -> "d" }
                            unit.questions.firstOrNull()?.let { onAnswer(it.id, key) }
                            true
                        }
                    }
                    else -> false
                }
            },
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            Card(colors = CardDefaults.cardColors(containerColor = EduCoreColors.Navy900)) {
                Row(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                    Column {
                        Text("Attempt ${attempt.session.attemptNumber}", color = EduCoreColors.Gold400, style = MaterialTheme.typography.labelMedium)
                        Text(section.name, color = Color.White, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                    }
                    Column(horizontalAlignment = Alignment.End) {
                        Text(formatTime(state.remainingSeconds), color = Color.White, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                        Text(if (state.isSaving) "Saving…" else "Saved", color = EduCoreColors.Gold100, style = MaterialTheme.typography.labelSmall)
                    }
                }
            }
        }
        if (!online) item { EduCoreWarningBanner("Connection lost. Your current answer remains on screen, but navigation should pause until synchronization resumes.") }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item {
            Row(Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                attempt.sections.forEachIndexed { index, candidate ->
                    OutlinedButton(onClick = { onSection(index) }, border = BorderStroke(1.dp, if (index == state.selectedSection) EduCoreColors.Gold600 else EduCoreColors.Line300)) {
                        Text("${candidate.code} · ${candidate.name}")
                    }
                }
            }
        }
        item {
            if (section.answerMode == "online") ObjectiveQuestionCard(unit, state.answers, state.flagged, state.questionImages, onAnswer, onFlag)
            else TheoryQuestionCard(unit, state.questionImages)
        }
        item { QuestionNavigator(state, onQuestion) }
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreSecondaryButton("Previous", onPrevious, Modifier.weight(1f), enabled = state.selectedQuestion > 0)
                EduCoreSecondaryButton("Next", onNext, Modifier.weight(1f), enabled = state.selectedQuestion < state.units.lastIndex)
            }
        }
        item { EduCoreDangerButton("Submit examination", { confirmSubmit = true }, Modifier.fillMaxWidth(), enabled = online && !state.isSubmitting) }
        item { Text("Keyboard: ← or P previous · → or N next · A, B, C or D choose an objective answer", style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Muted500, textAlign = TextAlign.Center, modifier = Modifier.fillMaxWidth()) }
    }
    if (confirmSubmit) {
        androidx.compose.material3.AlertDialog(
            onDismissRequest = { confirmSubmit = false },
            title = { Text("Submit examination?") },
            text = { Text("Submission is final. You will not be able to change answers afterwards.") },
            confirmButton = { EduCoreDangerButton("Submit", { confirmSubmit = false; onSubmit() }) },
            dismissButton = { EduCoreSecondaryButton("Continue exam", { confirmSubmit = false }) },
        )
    }
}

@Composable
private fun ObjectiveQuestionCard(
    unit: CbtQuestionUnit,
    answers: Map<Long, String>,
    flagged: Set<Long>,
    images: Map<Long, ByteArray>,
    onAnswer: (Long, String) -> Unit,
    onFlag: (Long) -> Unit,
) {
    val question = unit.questions.first()
    Card(colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
        Column(Modifier.padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Text("Question ${question.displayPath}", color = EduCoreColors.Gold700, fontWeight = FontWeight.Bold)
                IconButton(onClick = { onFlag(question.id) }) { Icon(Icons.Default.Flag, "Flag question", tint = if (question.id in flagged) EduCoreColors.Gold600 else EduCoreColors.Muted400) }
            }
            Text(question.text, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
            QuestionImage(images[question.id])
            if (question.options.isEmpty()) {
                OutlinedTextField(
                    value = answers[question.id].orEmpty(),
                    onValueChange = { onAnswer(question.id, it) },
                    label = { Text("Your answer") },
                    modifier = Modifier.fillMaxWidth(),
                    minLines = 2,
                )
            }
            question.options.toSortedMap().forEach { (key, value) ->
                val selected = answers[question.id]?.equals(key, true) == true
                Surface(
                    modifier = Modifier.fillMaxWidth().clickable { onAnswer(question.id, key) },
                    shape = MaterialTheme.shapes.medium,
                    color = if (selected) EduCoreColors.Info100 else EduCoreColors.Surface100,
                    border = BorderStroke(1.dp, if (selected) EduCoreColors.Navy700 else EduCoreColors.Line200),
                ) {
                    Row(Modifier.padding(EduCoreSpacing.Md), verticalAlignment = Alignment.CenterVertically) {
                        Surface(shape = MaterialTheme.shapes.small, color = if (selected) EduCoreColors.Navy900 else EduCoreColors.White, border = BorderStroke(1.dp, EduCoreColors.Line300)) {
                            Text(key.uppercase(), color = if (selected) Color.White else EduCoreColors.Navy900, fontWeight = FontWeight.Bold, modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp))
                        }
                        Spacer(Modifier.size(EduCoreSpacing.Md))
                        Text(value, style = MaterialTheme.typography.bodyLarge)
                    }
                }
            }
        }
    }
}

@Composable
private fun TheoryQuestionCard(unit: CbtQuestionUnit, images: Map<Long, ByteArray>) {
    Card(colors = CardDefaults.cardColors(containerColor = EduCoreColors.White), border = BorderStroke(1.dp, EduCoreColors.Line200)) {
        Column(Modifier.padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
            Text("Theory question ${unit.label}", color = EduCoreColors.Gold700, fontWeight = FontWeight.Bold)
            unit.questions.forEach { question ->
                Column(Modifier.fillMaxWidth().padding(start = (question.level * 10).dp)) {
                    Text(question.displayPath, style = MaterialTheme.typography.labelLarge, color = EduCoreColors.Navy700)
                    Text(question.text, style = if (question.level == 0) MaterialTheme.typography.titleMedium else MaterialTheme.typography.bodyLarge, fontWeight = if (question.level == 0) FontWeight.SemiBold else FontWeight.Normal)
                    QuestionImage(images[question.id])
                    if (question.marks > 0) Text("${question.marks.formatMark()} mark(s)", style = MaterialTheme.typography.labelSmall, color = EduCoreColors.Muted500)
                }
            }
            Surface(color = EduCoreColors.Warning100, shape = MaterialTheme.shapes.medium) {
                Text("Write the complete answer in the official answer booklet. This question is marked manually; no response is entered on this screen.", modifier = Modifier.padding(EduCoreSpacing.Md), color = EduCoreColors.Warning700, style = MaterialTheme.typography.bodySmall)
            }
        }
    }
}

@Composable
private fun QuestionImage(bytes: ByteArray?) {
    if (bytes == null) return
    val bitmap = remember(bytes) { decodeQuestionBitmap(bytes)?.asImageBitmap() } ?: return
    Image(bitmap = bitmap, contentDescription = "Question diagram", modifier = Modifier.fillMaxWidth().height(220.dp))
}

internal fun decodeQuestionBitmap(bytes: ByteArray, maxDimension: Int = 1280): android.graphics.Bitmap? {
    if (bytes.isEmpty() || maxDimension <= 0) return null
    val bounds = BitmapFactory.Options().apply { inJustDecodeBounds = true }
    BitmapFactory.decodeByteArray(bytes, 0, bytes.size, bounds)
    if (bounds.outWidth <= 0 || bounds.outHeight <= 0) return null
    val options = BitmapFactory.Options().apply {
        inSampleSize = questionImageSampleSize(bounds.outWidth, bounds.outHeight, maxDimension)
        inPreferredConfig = android.graphics.Bitmap.Config.ARGB_8888
    }
    return BitmapFactory.decodeByteArray(bytes, 0, bytes.size, options)
}

internal fun questionImageSampleSize(width: Int, height: Int, maxDimension: Int): Int {
    if (width <= 0 || height <= 0 || maxDimension <= 0) return 1
    var sample = 1
    while (width / (sample * 2) >= maxDimension || height / (sample * 2) >= maxDimension) sample *= 2
    return sample
}

@Composable
private fun QuestionNavigator(state: CbtUiState, onQuestion: (Int) -> Unit) {
    val units = state.units
    val columns = if (units.size <= 10) units.size.coerceAtLeast(1) else ((units.size + 4) / 5)
    val rows = ((units.size + columns - 1) / columns).coerceAtLeast(1)
    Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
        Text("${state.activeSection?.name} question navigator", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
        LazyVerticalGrid(
            columns = GridCells.Fixed(columns),
            modifier = Modifier.fillMaxWidth().height((rows * 46).dp),
            userScrollEnabled = false,
            horizontalArrangement = Arrangement.spacedBy(4.dp),
            verticalArrangement = Arrangement.spacedBy(4.dp),
        ) {
            itemsIndexed(units, key = { _, unit -> unit.id }) { index, unit ->
                val answered = unit.questions.any { it.id in state.answers }
                Surface(
                    onClick = { onQuestion(index) },
                    color = when { index == state.selectedQuestion -> EduCoreColors.Navy900; answered -> EduCoreColors.Success100; else -> EduCoreColors.White },
                    contentColor = if (index == state.selectedQuestion) Color.White else EduCoreColors.Navy900,
                    border = BorderStroke(1.dp, if (index == state.selectedQuestion) EduCoreColors.Navy900 else EduCoreColors.Line300),
                    shape = MaterialTheme.shapes.small,
                ) { Text((index + 1).toString(), textAlign = TextAlign.Center, modifier = Modifier.padding(vertical = 10.dp), style = MaterialTheme.typography.labelMedium) }
            }
        }
    }
}

@Composable
private fun CbtCompletion(state: CbtUiState, onExit: () -> Unit) {
    val result = state.attempt?.result
    Column(Modifier.fillMaxSize(), verticalArrangement = Arrangement.Center, horizontalAlignment = Alignment.CenterHorizontally) {
        EduCoreEmptyState(
            title = "Examination submitted",
            message = if (result?.fullyScored == true) "Your score is ${result.score?.formatMark()} of ${result.maximumScore?.formatMark()}." else "Objective responses are secured. Any theory section is awaiting manual marking.",
        )
        EduCorePrimaryButton("Return to examinations", onExit)
    }
}

@Composable
private fun SecureExamWindow(fullscreenRequired: Boolean, onFocusLost: () -> Unit) {
    val activity = LocalContext.current.findActivity()
    val lifecycle = LocalLifecycleOwner.current.lifecycle
    DisposableEffect(activity, lifecycle) {
        activity?.window?.addFlags(WindowManager.LayoutParams.FLAG_SECURE)
        if (activity != null && fullscreenRequired) {
            WindowCompat.getInsetsController(activity.window, activity.window.decorView)
                .hide(WindowInsetsCompat.Type.systemBars())
        }
        val observer = LifecycleEventObserver { _, event -> if (event == Lifecycle.Event.ON_STOP) onFocusLost() }
        lifecycle.addObserver(observer)
        onDispose {
            lifecycle.removeObserver(observer)
            activity?.window?.clearFlags(WindowManager.LayoutParams.FLAG_SECURE)
            if (activity != null && fullscreenRequired) {
                WindowCompat.getInsetsController(activity.window, activity.window.decorView)
                    .show(WindowInsetsCompat.Type.systemBars())
            }
        }
    }
}

@Composable
private fun ScreenHeading(title: String, subtitle: String, onBack: () -> Unit) {
    EduCorePageHeader(title = title, subtitle = subtitle, onBack = onBack)
}

@Composable
private fun ExamMetric(label: String, value: String) {
    Column(horizontalAlignment = Alignment.CenterHorizontally) {
        Text(value, color = Color.White, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
        Text(label, color = EduCoreColors.Gold100, style = MaterialTheme.typography.labelSmall)
    }
}

@Composable
private fun StatusPill(status: String) {
    val (background, foreground) = when (status) {
        "open" -> EduCoreColors.Success100 to EduCoreColors.Success700
        "upcoming" -> EduCoreColors.Info100 to EduCoreColors.Info700
        else -> EduCoreColors.Danger100 to EduCoreColors.Danger700
    }
    Surface(color = background, contentColor = foreground, shape = MaterialTheme.shapes.extraLarge) {
        Text(status.replaceFirstChar(Char::uppercase), modifier = Modifier.padding(horizontal = 10.dp, vertical = 5.dp), style = MaterialTheme.typography.labelSmall)
    }
}

private fun Context.findActivity(): Activity? = when (this) {
    is Activity -> this
    is ContextWrapper -> baseContext.findActivity()
    else -> null
}

private fun Double.formatMark(): String = if (this % 1.0 == 0.0) toInt().toString() else "%.1f".format(this)
private fun formatTime(seconds: Int) = "%02d:%02d".format(seconds / 60, seconds % 60)
