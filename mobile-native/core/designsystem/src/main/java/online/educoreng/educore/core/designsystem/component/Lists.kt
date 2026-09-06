package online.educoreng.educore.core.designsystem.component

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.RowScope
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ChevronRight
import androidx.compose.material.icons.filled.Description
import androidx.compose.material.icons.filled.ExpandLess
import androidx.compose.material.icons.filled.ExpandMore
import androidx.compose.material.icons.filled.Person
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreElevation
import online.educoreng.educore.core.designsystem.theme.EduCoreSizes
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

@Composable
fun EduCoreStatusBadge(
    text: String,
    tone: EduCoreTone,
    modifier: Modifier = Modifier,
) {
    Surface(
        modifier = modifier,
        shape = MaterialTheme.shapes.extraSmall,
        color = tone.container(),
        contentColor = tone.foreground(),
    ) {
        Text(
            text = text,
            modifier = Modifier.padding(horizontal = EduCoreSpacing.Sm, vertical = EduCoreSpacing.Xs),
            style = MaterialTheme.typography.labelSmall,
            maxLines = 1,
        )
    }
}

@Composable
fun EduCoreSectionHeader(
    title: String,
    modifier: Modifier = Modifier,
    supportingText: String? = null,
    actionLabel: String? = null,
    onAction: (() -> Unit)? = null,
) {
    Row(
        modifier = modifier.fillMaxWidth(),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        Column(Modifier.weight(1f)) {
            Text(title, style = MaterialTheme.typography.titleMedium)
            supportingText?.let {
                Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Muted500)
            }
        }
        if (actionLabel != null && onAction != null) {
            EduCoreTextButton(actionLabel, onAction)
        }
    }
}

@Composable
fun EduCoreListItem(
    title: String,
    modifier: Modifier = Modifier,
    subtitle: String? = null,
    icon: ImageVector? = null,
    status: String? = null,
    statusTone: EduCoreTone = EduCoreTone.Neutral,
    onClick: (() -> Unit)? = null,
    trailing: @Composable RowScope.() -> Unit = {},
) {
    val clickableModifier = if (onClick == null) modifier else modifier.clickable(onClick = onClick)
    Row(
        modifier = clickableModifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        icon?.let {
            Surface(
                shape = MaterialTheme.shapes.small,
                color = EduCoreColors.Info100,
                contentColor = EduCoreColors.Navy900,
            ) {
                Icon(it, contentDescription = null, modifier = Modifier.padding(EduCoreSpacing.Sm).size(EduCoreSizes.Icon))
            }
        }
        Column(Modifier.weight(1f)) {
            Text(title, style = MaterialTheme.typography.titleSmall, maxLines = 2, overflow = TextOverflow.Ellipsis)
            subtitle?.let {
                Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Muted500, maxLines = 2)
            }
        }
        status?.let { EduCoreStatusBadge(it, statusTone) }
        trailing()
        if (onClick != null) {
            Icon(Icons.Default.ChevronRight, contentDescription = null, tint = EduCoreColors.Muted400)
        }
    }
}

@Composable
fun EduCoreStudentRow(
    name: String,
    identifier: String,
    modifier: Modifier = Modifier,
    meta: String? = null,
    status: String? = null,
    statusTone: EduCoreTone = EduCoreTone.Neutral,
    onClick: (() -> Unit)? = null,
) = EduCorePersonRow(name, identifier, modifier, meta, status, statusTone, onClick)

@Composable
fun EduCoreTeacherRow(
    name: String,
    identifier: String,
    modifier: Modifier = Modifier,
    meta: String? = null,
    status: String? = null,
    statusTone: EduCoreTone = EduCoreTone.Neutral,
    onClick: (() -> Unit)? = null,
) = EduCorePersonRow(name, identifier, modifier, meta, status, statusTone, onClick)

@Composable
private fun EduCorePersonRow(
    name: String,
    identifier: String,
    modifier: Modifier,
    meta: String?,
    status: String?,
    statusTone: EduCoreTone,
    onClick: (() -> Unit)?,
) {
    EduCoreListItem(
        title = name,
        subtitle = listOfNotNull(identifier, meta).joinToString(" · "),
        icon = Icons.Default.Person,
        status = status,
        statusTone = statusTone,
        onClick = onClick,
        modifier = modifier,
    )
}

@Composable
fun EduCoreProfileHeader(
    name: String,
    role: String,
    modifier: Modifier = Modifier,
    identifier: String? = null,
    avatar: @Composable (() -> Unit)? = null,
) {
    Row(
        modifier = modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        if (avatar != null) {
            avatar()
        } else {
            Surface(
                modifier = Modifier.size(EduCoreSizes.LargeAvatar),
                shape = MaterialTheme.shapes.large,
                color = EduCoreColors.Gold100,
                contentColor = EduCoreColors.Navy900,
            ) {
                Box(contentAlignment = Alignment.Center) {
                    Icon(Icons.Default.Person, contentDescription = null)
                }
            }
        }
        Column(Modifier.weight(1f)) {
            Text(name, style = MaterialTheme.typography.titleLarge)
            Text(role, style = MaterialTheme.typography.bodyMedium, color = EduCoreColors.Slate600)
            identifier?.let {
                Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Muted500)
            }
        }
    }
}

@Composable
fun EduCoreFileCard(
    title: String,
    metadata: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    action: @Composable RowScope.() -> Unit = {},
) {
    Card(
        modifier = modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        border = BorderStroke(EduCoreElevation.Resting, EduCoreColors.Line200),
    ) {
        EduCoreListItem(
            title = title,
            subtitle = metadata,
            icon = Icons.Default.Description,
            onClick = onClick,
            trailing = action,
        )
    }
}

@Composable
fun EduCoreExpandableSection(
    title: String,
    modifier: Modifier = Modifier,
    initiallyExpanded: Boolean = false,
    supportingText: String? = null,
    content: @Composable () -> Unit,
) {
    var expanded by remember { mutableStateOf(initiallyExpanded) }
    Card(
        modifier = modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        border = BorderStroke(EduCoreElevation.Resting, EduCoreColors.Line200),
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .clip(MaterialTheme.shapes.medium)
                .clickable { expanded = !expanded }
                .padding(EduCoreSpacing.Lg),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Column(Modifier.weight(1f)) {
                Text(title, style = MaterialTheme.typography.titleSmall)
                supportingText?.let {
                    Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Muted500)
                }
            }
            IconButton(onClick = { expanded = !expanded }) {
                Icon(
                    if (expanded) Icons.Default.ExpandLess else Icons.Default.ExpandMore,
                    contentDescription = if (expanded) "Collapse" else "Expand",
                )
            }
        }
        AnimatedVisibility(expanded) {
            Column {
                HorizontalDivider(color = EduCoreColors.Line200)
                Column(Modifier.padding(EduCoreSpacing.Lg)) { content() }
            }
        }
    }
}

@Composable
fun EduCorePermissionGate(
    allowed: Boolean,
    content: @Composable () -> Unit,
    deniedContent: @Composable () -> Unit = {},
) {
    if (allowed) content() else deniedContent()
}
