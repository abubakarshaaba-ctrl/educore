package online.educoreng.educore.core.designsystem.component

import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier

/** Source-compatible overload for older screens that passed onBack positionally. */
@Composable
fun EduCorePageHeader(
    title: String,
    subtitle: String?,
    onBack: () -> Unit,
) {
    EduCorePageHeader(
        title = title,
        subtitle = subtitle,
        modifier = Modifier,
        onBack = onBack,
    )
}
