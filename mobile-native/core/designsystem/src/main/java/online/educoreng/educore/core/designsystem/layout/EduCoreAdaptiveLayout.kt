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

enum class EduCoreWindowHeight {
    Short,
    Regular,
    Tall,
}

data class EduCoreWindowInfo(
    val width: EduCoreWindowWidth,
    val height: EduCoreWindowHeight,
    val maxWidth: Dp,
    val maxHeight: Dp,
    val isLandscape: Boolean,
)

@Composable
fun EduCoreResponsiveLayout(
    modifier: Modifier = Modifier,
    content: @Composable (EduCoreWindowInfo) -> Unit,
) {
    BoxWithConstraints(modifier = modifier) {
        val widthClass = when {
            maxWidth < 600.dp -> EduCoreWindowWidth.Compact
            maxWidth < 840.dp -> EduCoreWindowWidth.Medium
            else -> EduCoreWindowWidth.Expanded
        }
        val heightClass = when {
            maxHeight < 600.dp -> EduCoreWindowHeight.Short
            maxHeight < 900.dp -> EduCoreWindowHeight.Regular
            else -> EduCoreWindowHeight.Tall
        }
        content(
            EduCoreWindowInfo(
                width = widthClass,
                height = heightClass,
                maxWidth = maxWidth,
                maxHeight = maxHeight,
                isLandscape = maxWidth > maxHeight,
            ),
        )
    }
}

@Composable
fun EduCoreAdaptiveLayout(
    modifier: Modifier = Modifier,
    content: @Composable (EduCoreWindowWidth) -> Unit,
) {
    EduCoreResponsiveLayout(modifier = modifier) { info ->
        content(info.width)
    }
}

fun eduCoreHorizontalPadding(widthDp: Int): Dp = when {
    widthDp < 320 -> 8.dp
    widthDp < 360 -> 12.dp
    widthDp < 600 -> 16.dp
    widthDp < 840 -> 20.dp
    else -> 24.dp
}

fun eduCoreGridMinCellWidth(width: EduCoreWindowWidth): Dp = when (width) {
    EduCoreWindowWidth.Compact -> 150.dp
    EduCoreWindowWidth.Medium -> 176.dp
    EduCoreWindowWidth.Expanded -> 208.dp
}

@Composable
fun eduCoreScreenPadding(): Dp = eduCoreHorizontalPadding(LocalConfiguration.current.screenWidthDp)
