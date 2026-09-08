package online.educoreng.educore.core.designsystem.component

import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier

/** Convenience overload for the common title + supporting-text form. */
@Composable
fun EduCoreSectionHeader(
    title: String,
    supportingText: String,
) {
    EduCoreSectionHeader(
        title = title,
        modifier = Modifier,
        supportingText = supportingText,
    )
}
