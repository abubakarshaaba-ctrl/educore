package online.educoreng.educore.core.designsystem.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Shapes
import androidx.compose.material3.Typography
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.unit.Density
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
    displayLarge = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Bold,
        fontSize = 24.sp,
        lineHeight = 28.sp,
        letterSpacing = (-0.30).sp,
    ),
    displayMedium = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Bold,
        fontSize = 21.sp,
        lineHeight = 25.sp,
        letterSpacing = (-0.25).sp,
    ),
    displaySmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Bold,
        fontSize = 19.sp,
        lineHeight = 23.sp,
        letterSpacing = (-0.20).sp,
    ),
    headlineLarge = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Bold,
        fontSize = 19.sp,
        lineHeight = 23.sp,
        letterSpacing = (-0.15).sp,
    ),
    headlineMedium = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Bold,
        fontSize = 18.sp,
        lineHeight = 22.sp,
        letterSpacing = (-0.12).sp,
    ),
    headlineSmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Bold,
        fontSize = 17.sp,
        lineHeight = 21.sp,
        letterSpacing = (-0.10).sp,
    ),
    titleLarge = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.SemiBold,
        fontSize = 15.sp,
        lineHeight = 19.sp,
    ),
    titleMedium = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.SemiBold,
        fontSize = 13.sp,
        lineHeight = 17.sp,
    ),
    titleSmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.SemiBold,
        fontSize = 12.sp,
        lineHeight = 16.sp,
    ),
    bodyLarge = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Normal,
        fontSize = 13.sp,
        lineHeight = 18.sp,
    ),
    bodyMedium = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Normal,
        fontSize = 12.sp,
        lineHeight = 16.sp,
    ),
    bodySmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Normal,
        fontSize = 10.sp,
        lineHeight = 14.sp,
    ),
    labelLarge = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.SemiBold,
        fontSize = 11.sp,
        lineHeight = 15.sp,
    ),
    labelMedium = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Medium,
        fontSize = 10.sp,
        lineHeight = 14.sp,
    ),
    labelSmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Medium,
        fontSize = 9.sp,
        lineHeight = 13.sp,
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

internal fun eduCoreAdaptiveFontMultiplier(widthDp: Int, heightDp: Int): Float {
    val shortestSideDp = minOf(widthDp, heightDp)

    return when {
        shortestSideDp >= 900 -> 1.32f
        shortestSideDp >= 720 -> 1.25f
        shortestSideDp >= 600 -> 1.15f
        else -> 1.00f
    }
}

@Composable
fun EduCoreTheme(content: @Composable () -> Unit) {
    val configuration = LocalConfiguration.current
    val density = LocalDensity.current
    val adaptiveFontMultiplier = eduCoreAdaptiveFontMultiplier(
        widthDp = configuration.screenWidthDp,
        heightDp = configuration.screenHeightDp,
    )

    // Keep dp geometry unchanged while enlarging every sp-based text style on
    // tablet/large-screen windows. Multiplying the existing Android fontScale
    // preserves the user's accessibility preference instead of replacing it.
    val adaptiveDensity = Density(
        density = density.density,
        fontScale = density.fontScale * adaptiveFontMultiplier,
    )

    CompositionLocalProvider(LocalDensity provides adaptiveDensity) {
        MaterialTheme(
            colorScheme = EduCoreColorScheme,
            typography = EduCoreTypography,
            shapes = EduCoreShapes,
            content = content,
        )
    }
}
