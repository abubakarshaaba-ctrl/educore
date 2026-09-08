package online.educoreng.educore.core.designsystem.component

import androidx.compose.runtime.Composable

/** Convenience overload for the common title + supporting-text form. */
@Composable
fun EduCoreSectionHeader(
    title: String,
    supportingText: String,
) {
    EduCoreSectionHeader(
        title = title,
        supportingText = supportingText,
    )
}
