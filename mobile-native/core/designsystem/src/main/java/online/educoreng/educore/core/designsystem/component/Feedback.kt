package online.educoreng.educore.core.designsystem.component

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CloudOff
import androidx.compose.material.icons.filled.ErrorOutline
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.WarningAmber
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSizes
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

@Composable
fun EduCoreInfoBanner(
    message: String,
    modifier: Modifier = Modifier,
    title: String? = null,
) = EduCoreBanner(message, modifier, title, EduCoreTone.Info, Icons.Default.Info)

@Composable
fun EduCoreWarningBanner(
    message: String,
    modifier: Modifier = Modifier,
    title: String? = null,
) = EduCoreBanner(message, modifier, title, EduCoreTone.Warning, Icons.Default.WarningAmber)

@Composable
fun EduCoreErrorBanner(
    message: String,
    modifier: Modifier = Modifier,
    title: String? = null,
) = EduCoreBanner(message, modifier, title, EduCoreTone.Danger, Icons.Default.ErrorOutline)

@Composable
fun EduCoreOfflineBanner(
    modifier: Modifier = Modifier,
    message: String = "Offline · showing saved information",
) = EduCoreBanner(message, modifier, null, EduCoreTone.Warning, Icons.Default.CloudOff)

@Composable
private fun EduCoreBanner(
    message: String,
    modifier: Modifier,
    title: String?,
    tone: EduCoreTone,
    icon: ImageVector,
) {
    Surface(
        modifier = modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.small,
        color = tone.container(),
        contentColor = tone.foreground(),
    ) {
        Row(
            modifier = Modifier.padding(EduCoreSpacing.Md),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            verticalAlignment = Alignment.Top,
        ) {
            Icon(icon, contentDescription = null, modifier = Modifier.size(EduCoreSizes.Icon))
            Column(Modifier.weight(1f)) {
                title?.let { Text(it, style = MaterialTheme.typography.labelLarge) }
                Text(message, style = MaterialTheme.typography.bodySmall)
            }
        }
    }
}

@Composable
fun EduCoreLoadingState(
    modifier: Modifier = Modifier,
    message: String = "Loading",
) {
    Column(
        modifier = modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
    ) {
        CircularProgressIndicator(
            color = EduCoreColors.Gold600,
            strokeWidth = 2.dp,
            modifier = Modifier.size(24.dp),
        )
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        Text(message, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
    }
}

@Composable
fun EduCoreEmptyState(
    title: String,
    message: String,
    modifier: Modifier = Modifier,
    icon: ImageVector = Icons.Default.Info,
    actionLabel: String? = null,
    onAction: (() -> Unit)? = null,
) {
    Column(
        modifier = modifier.fillMaxWidth().padding(EduCoreSpacing.Xl),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
    ) {
        Surface(
            shape = RoundedCornerShape(50),
            color = EduCoreColors.Info100,
            contentColor = EduCoreColors.Navy900,
        ) {
            Icon(icon, contentDescription = null, modifier = Modifier.padding(EduCoreSpacing.Sm).size(EduCoreSizes.LargeIcon))
        }
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        Text(title, style = MaterialTheme.typography.titleMedium, textAlign = TextAlign.Center)
        Spacer(Modifier.height(EduCoreSpacing.Xs))
        Text(
            message,
            style = MaterialTheme.typography.bodySmall,
            color = EduCoreColors.Muted500,
            textAlign = TextAlign.Center,
        )
        if (actionLabel != null && onAction != null) {
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            EduCorePrimaryButton(actionLabel, onAction)
        }
    }
}

@Composable
fun EduCoreErrorState(
    message: String,
    modifier: Modifier = Modifier,
    title: String = "Unable to load",
    onRetry: (() -> Unit)? = null,
) {
    EduCoreEmptyState(
        title = title,
        message = message,
        modifier = modifier,
        icon = Icons.Default.ErrorOutline,
        actionLabel = if (onRetry == null) null else "Retry",
        onAction = onRetry,
    )
}

/**
 * Static skeletons deliberately avoid an infinite animation. Large lists can
 * render many placeholders at once; keeping them static removes continuous
 * recomposition/GPU work on lower-memory phones while still signalling loading.
 */
@Composable
fun EduCoreSkeleton(
    modifier: Modifier = Modifier,
) {
    Box(
        modifier = modifier
            .height(EduCoreSizes.TouchTarget)
            .background(EduCoreColors.Line200, MaterialTheme.shapes.small),
    )
}
