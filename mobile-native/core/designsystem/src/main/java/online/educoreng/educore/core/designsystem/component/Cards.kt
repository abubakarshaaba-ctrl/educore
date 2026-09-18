package online.educoreng.educore.core.designsystem.component

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.RowScope
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.widthIn
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.style.TextOverflow
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreElevation
import online.educoreng.educore.core.designsystem.theme.EduCoreSizes
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.designsystem.theme.EduCoreStroke

enum class EduCoreTone {
    Neutral,
    Brand,
    Accent,
    Success,
    Warning,
    Danger,
    Info,
    Purple,
}

internal fun EduCoreTone.foreground(): Color = when (this) {
    EduCoreTone.Neutral -> EduCoreColors.Slate700
    EduCoreTone.Brand -> EduCoreColors.Navy900
    EduCoreTone.Accent -> EduCoreColors.Navy900
    EduCoreTone.Success -> EduCoreColors.Success700
    EduCoreTone.Warning -> EduCoreColors.Warning700
    EduCoreTone.Danger -> EduCoreColors.Danger700
    EduCoreTone.Info -> EduCoreColors.Info700
    EduCoreTone.Purple -> EduCoreColors.Purple700
}

internal fun EduCoreTone.container(): Color = when (this) {
    EduCoreTone.Neutral -> EduCoreColors.Surface100
    EduCoreTone.Brand -> EduCoreColors.Info100
    EduCoreTone.Accent -> EduCoreColors.Info100
    EduCoreTone.Success -> EduCoreColors.Success100
    EduCoreTone.Warning -> EduCoreColors.Warning100
    EduCoreTone.Danger -> EduCoreColors.Danger100
    EduCoreTone.Info -> EduCoreColors.Info100
    EduCoreTone.Purple -> EduCoreColors.Purple100
}

@Composable
fun EduCoreDashboardCard(
    modifier: Modifier = Modifier,
    content: @Composable () -> Unit,
) {
    Card(
        modifier = modifier,
        shape = MaterialTheme.shapes.medium,
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(EduCoreStroke.Thin, EduCoreColors.Line200),
        elevation = CardDefaults.cardElevation(defaultElevation = EduCoreElevation.Resting),
    ) {
        Column(Modifier.padding(EduCoreSpacing.Md)) { content() }
    }
}

@Composable
fun EduCoreMetricCard(
    label: String,
    value: String,
    modifier: Modifier = Modifier,
    icon: ImageVector? = null,
    supportingText: String? = null,
    tone: EduCoreTone = EduCoreTone.Brand,
) {
    EduCoreDashboardCard(modifier.heightIn(min = EduCoreSizes.MetricCardHeight)) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            if (icon != null) {
                Surface(
                    shape = MaterialTheme.shapes.small,
                    color = tone.container(),
                    contentColor = tone.foreground(),
                ) {
                    Icon(
                        imageVector = icon,
                        contentDescription = null,
                        modifier = Modifier.padding(EduCoreSpacing.Sm).size(EduCoreSizes.Icon),
                    )
                }
                Spacer(Modifier.size(EduCoreSpacing.Sm))
            }
            Column {
                Text(value, style = MaterialTheme.typography.titleMedium, color = EduCoreColors.Ink900)
                Text(label, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                supportingText?.let {
                    Text(it, style = MaterialTheme.typography.labelSmall, color = tone.foreground())
                }
            }
        }
    }
}

@Composable
fun EduCoreStatCard(
    label: String,
    value: String,
    modifier: Modifier = Modifier,
    supportingText: String? = null,
    tone: EduCoreTone = EduCoreTone.Neutral,
) {
    EduCoreDashboardCard(modifier) {
        Text(value, style = MaterialTheme.typography.titleLarge, color = tone.foreground())
        Text(label, style = MaterialTheme.typography.labelLarge)
        supportingText?.let {
            Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Muted500)
        }
    }
}

@Composable
fun EduCoreCountBadge(
    count: Int,
    modifier: Modifier = Modifier,
    cap: Int = 99,
    tone: EduCoreTone = EduCoreTone.Info,
) {
    if (count <= 0) return
    val safeCap = cap.coerceAtLeast(1)
    val label = if (count > safeCap) "$safeCap+" else count.toString()
    Surface(
        modifier = modifier
            .heightIn(min = EduCoreSizes.CountBadgeMinSize)
            .widthIn(min = EduCoreSizes.CountBadgeMinSize),
        shape = MaterialTheme.shapes.extraLarge,
        color = tone.container(),
        contentColor = tone.foreground(),
        border = BorderStroke(EduCoreStroke.Thin, EduCoreColors.Line200),
    ) {
        Text(
            text = label,
            modifier = Modifier.padding(horizontal = EduCoreSpacing.Sm, vertical = EduCoreSpacing.Xs),
            style = MaterialTheme.typography.labelSmall,
            color = tone.foreground(),
            maxLines = 1,
        )
    }
}

@Composable
fun EduCoreModuleCard(
    title: String,
    icon: ImageVector,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    subtitle: String? = null,
    enabled: Boolean = true,
    badge: String? = null,
) {
    val supportingSubtitle = subtitle?.trim()?.takeIf(::isHumanReadableSubtitle)

    Card(
        onClick = onClick,
        enabled = enabled,
        modifier = modifier.heightIn(min = EduCoreSizes.ModuleCardMinHeight),
        shape = MaterialTheme.shapes.medium,
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(EduCoreStroke.Thin, EduCoreColors.Line200),
        elevation = CardDefaults.cardElevation(defaultElevation = EduCoreElevation.Resting),
    ) {
        Column(
            modifier = Modifier.padding(EduCoreSpacing.Md),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs),
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween,
            ) {
                Surface(
                    shape = MaterialTheme.shapes.small,
                    color = EduCoreColors.Info100,
                    contentColor = EduCoreColors.Navy900,
                    border = BorderStroke(EduCoreStroke.Thin, EduCoreColors.Line200),
                ) {
                    Icon(
                        imageVector = icon,
                        contentDescription = null,
                        modifier = Modifier.padding(EduCoreSpacing.Sm).size(EduCoreSizes.SmallIcon),
                    )
                }
                badge?.trim()?.takeIf(String::isNotEmpty)?.let {
                    EduCoreStatusBadge(it, EduCoreTone.Neutral)
                }
            }
            Text(
                text = title,
                style = MaterialTheme.typography.titleSmall,
                color = EduCoreColors.Ink900,
                maxLines = 2,
                overflow = TextOverflow.Ellipsis,
            )
            supportingSubtitle?.let {
                Text(
                    text = it,
                    style = MaterialTheme.typography.bodySmall,
                    color = EduCoreColors.Slate600,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis,
                )
            }
        }
    }
}

private fun isHumanReadableSubtitle(value: String): Boolean {
    if (value.isBlank()) return false
    if (value.any(Char::isWhitespace)) return true
    return value.none { it == '.' || it == '_' || it == '-' || it == '/' }
}

@Composable
fun EduCoreQuickAction(
    label: String,
    icon: ImageVector,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
) {
    Card(
        onClick = onClick,
        enabled = enabled,
        modifier = modifier.heightIn(min = EduCoreSizes.TouchTarget),
        shape = MaterialTheme.shapes.medium,
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(EduCoreStroke.Thin, EduCoreColors.Line200),
        elevation = CardDefaults.cardElevation(defaultElevation = EduCoreElevation.Resting),
    ) {
        Row(
            modifier = Modifier.padding(horizontal = EduCoreSpacing.Md, vertical = EduCoreSpacing.Sm),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            Icon(icon, contentDescription = null, tint = EduCoreColors.Navy900, modifier = Modifier.size(EduCoreSizes.Icon))
            Text(label, style = MaterialTheme.typography.labelLarge, color = EduCoreColors.Navy900)
        }
    }
}

@Composable
fun EduCoreCardActions(
    modifier: Modifier = Modifier,
    content: @Composable RowScope.() -> Unit,
) {
    Row(
        modifier = modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm, Alignment.End),
        verticalAlignment = Alignment.CenterVertically,
        content = content,
    )
}
