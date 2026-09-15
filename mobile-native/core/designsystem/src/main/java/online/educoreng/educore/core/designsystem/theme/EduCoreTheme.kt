package online.educoreng.educore.core.designsystem.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Shapes
import androidx.compose.material3.Typography
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

private val EduCoreColorScheme = lightColorScheme(
    primary = EduCoreColors.Navy900,
    onPrimary = EduCoreColors.White,
    primaryContainer = EduCoreColors.Info100,
    onPrimaryContainer = EduCoreColors.Navy900,
    secondary = EduCoreColors.Navy700,
    onSecondary = EduCoreColors.White,
    secondaryContainer = EduCoreColors.Gold100,
    onSecondaryContainer = EduCoreColors.Navy900,
    background = EduCoreColors.Page50,
    onBackground = EduCoreColors.Ink900,
    surface = EduCoreColors.White,
    onSurface = EduCoreColors.Ink900,
    surfaceVariant = EduCoreColors.Surface100,
    onSurfaceVariant = EduCoreColors.Slate700,
    outline = EduCoreColors.Line300,
    outlineVariant = EduCoreColors.Line200,
    error = EduCoreColors.Danger600,
    onError = Color.White,
    errorContainer = EduCoreColors.Danger100,
    onErrorContainer = EduCoreColors.Danger700,
)

/**
 * Compact enough for a data-heavy school ERP, but intentionally not micro-sized.
 * All values use sp so Android font scaling remains effective. The previous
 * 9–12sp labels/body styles were visually dense but too small for sustained use
 * on phones, especially for staff working quickly in classrooms and offices.
 */
private val EduCoreTypography = Typography(
    displaySmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Bold,
        fontSize = 24.sp,
        lineHeight = 30.sp,
        letterSpacing = (-0.25).sp,
    ),
    headlineSmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Bold,
        fontSize = 20.sp,
        lineHeight = 26.sp,
        letterSpacing = (-0.15).sp,
    ),
    titleLarge = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.SemiBold,
        fontSize = 18.sp,
        lineHeight = 24.sp,
    ),
    titleMedium = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.SemiBold,
        fontSize = 16.sp,
        lineHeight = 22.sp,
    ),
    titleSmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.SemiBold,
        fontSize = 14.sp,
        lineHeight = 20.sp,
    ),
    bodyLarge = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Normal,
        fontSize = 16.sp,
        lineHeight = 24.sp,
    ),
    bodyMedium = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Normal,
        fontSize = 14.sp,
        lineHeight = 21.sp,
    ),
    bodySmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Normal,
        fontSize = 12.sp,
        lineHeight = 18.sp,
    ),
    labelLarge = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.SemiBold,
        fontSize = 14.sp,
        lineHeight = 20.sp,
    ),
    labelMedium = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Medium,
        fontSize = 12.sp,
        lineHeight = 18.sp,
    ),
    labelSmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Medium,
        fontSize = 11.sp,
        lineHeight = 16.sp,
    ),
)

private val EduCoreShapes = Shapes(
    extraSmall = androidx.compose.foundation.shape.RoundedCornerShape(8.dp),
    small = androidx.compose.foundation.shape.RoundedCornerShape(10.dp),
    medium = androidx.compose.foundation.shape.RoundedCornerShape(14.dp),
    large = androidx.compose.foundation.shape.RoundedCornerShape(20.dp),
    extraLarge = androidx.compose.foundation.shape.RoundedCornerShape(24.dp),
)

@Composable
fun EduCoreTheme(content: @Composable () -> Unit) {
    MaterialTheme(
        colorScheme = EduCoreColorScheme,
        typography = EduCoreTypography,
        shapes = EduCoreShapes,
        content = content,
    )
}
