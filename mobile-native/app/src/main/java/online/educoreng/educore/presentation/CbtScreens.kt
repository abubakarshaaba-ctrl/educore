package online.educoreng.educore.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

/**
 * Compatibility destinations only. CBT is removed from the Android product and
 * these routes are unreachable from the filtered navigation graph.
 */
@Composable
internal fun CbtExamsScreen(
    state: CbtUiState,
    onBack: () -> Unit,
    onOpen: (Long) -> Unit,
    onRetry: () -> Unit,
) = CbtRemovedScreen(onBack, state, onOpen, onRetry)

@Composable
internal fun CbtPreflightScreen(
    state: CbtUiState,
    online: Boolean,
    onBack: () -> Unit,
    onBegin: () -> Unit,
    onResume: () -> Unit,
    onRetry: () -> Unit,
) = CbtRemovedScreen(onBack, state, online, onBegin, onResume, onRetry)

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
) = CbtRemovedScreen(
    onExit,
    state,
    online,
    onSection,
    onQuestion,
    onPrevious,
    onNext,
    onAnswer,
    onFlag,
    onSubmit,
    onFocusLost,
    onRetry,
)

@Composable
private fun CbtRemovedScreen(
    onBack: () -> Unit,
    vararg compatibility: Any?,
) {
    @Suppress("UNUSED_VARIABLE")
    val retainedForLegacySignatures = compatibility

    Column(
        modifier = Modifier.fillMaxSize().padding(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        EduCorePageHeader(title = "CBT unavailable", onBack = onBack)
        Text(
            text = "CBT is not available in the EduCore Android app.",
            style = MaterialTheme.typography.bodyMedium,
            color = EduCoreColors.Slate600,
        )
    }
}
