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
 * Canonical compact ERP typography for the native app.
 *
 * The scale deliberately mirrors the denser web interface while retaining sp
 * units so Android accessibility/font scaling continues to work. Controls keep
 * their 48dp touch target; only visual type density is reduced.
 */
private val EduCoreTypography = Typography(
    displaySmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Bold,
        fontSize = 20.sp,
        lineHeight = 25.sp,
        letterSpacing = (-0.20).sp,
    ),
    headlineSmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Bold,
        fontSize = 18.sp,
        lineHeight = 23.sp,
        letterSpacing = (-0.10).sp,
    ),
    titleLarge = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.SemiBold,
        fontSize = 16.sp,
        lineHeight = 21.sp,
    ),
    titleMedium = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.SemiBold,
        fontSize = 14.sp,
        lineHeight = 19.sp,
    ),
    titleSmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.SemiBold,
        fontSize = 13.sp,
        lineHeight = 18.sp,
    ),
    bodyLarge = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Normal,
        fontSize = 14.sp,
        lineHeight = 20.sp,
    ),
    bodyMedium = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Normal,
        fontSize = 13.sp,
        lineHeight = 18.sp,
    ),
    bodySmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Normal,
        fontSize = 11.sp,
        lineHeight = 16.sp,
    ),
    labelLarge = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.SemiBold,
        fontSize = 12.sp,
        lineHeight = 17.sp,
    ),
    labelMedium = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Medium,
        fontSize = 11.sp,
        lineHeight = 16.sp,
    ),
    labelSmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Medium,
        fontSize = 10.sp,
        lineHeight = 14.sp,
    ),
)

/** Shared geometry: small enough for dense ERP screens, consistent everywhere. */
private val EduCoreShapes = Shapes(
    extraSmall = androidx.compose.foundation.shape.RoundedCornerShape(6.dp),
    small = androidx.compose.foundation.shape.RoundedCornerShape(8.dp),
    medium = androidx.compose.foundation.shape.RoundedCornerShape(10.dp),
    large = androidx.compose.foundation.shape.RoundedCornerShape(14.dp),
    extraLarge = androidx.compose.foundation.shape.RoundedCornerShape(18.dp),
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
