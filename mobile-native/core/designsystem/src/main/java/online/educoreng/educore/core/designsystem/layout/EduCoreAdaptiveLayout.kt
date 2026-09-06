package online.educoreng.educore.core.designsystem.layout

import androidx.compose.foundation.layout.BoxWithConstraints
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp

enum class EduCoreWindowWidth {
    Compact,
    Medium,
    Expanded,
}

@Composable
fun EduCoreAdaptiveLayout(
    modifier: Modifier = Modifier,
    content: @Composable (EduCoreWindowWidth) -> Unit,
) {
    BoxWithConstraints(modifier = modifier) {
        val widthClass = when {
            maxWidth < 600.dp -> EduCoreWindowWidth.Compact
            maxWidth < 840.dp -> EduCoreWindowWidth.Medium
            else -> EduCoreWindowWidth.Expanded
        }
        content(widthClass)
    }
}

fun eduCoreHorizontalPadding(widthDp: Int): Dp = when {
    widthDp < 360 -> 12.dp
    widthDp < 600 -> 16.dp
    widthDp < 840 -> 20.dp
    else -> 24.dp
}

@Composable
fun eduCoreScreenPadding(): Dp = eduCoreHorizontalPadding(LocalConfiguration.current.screenWidthDp)
