package online.educoreng.educore.core.designsystem.component

import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier

/**
 * Source-compatible overload for older screens that passed onBack positionally.
 *
 * EduCore's mobile shell already provides persistent bottom navigation and the
 * Android system back action, so page-level arrow buttons are intentionally not
 * rendered. The callback stays in the signature to avoid breaking callers.
 */
@Composable
fun EduCorePageHeader(
    title: String,
    subtitle: String?,
    onBack: () -> Unit,
) {
    @Suppress("UNUSED_VARIABLE")
    val retainedBackHandler = onBack
    EduCorePageHeader(
        title = title,
        subtitle = subtitle,
        modifier = Modifier,
        onBack = null,
    )
}
