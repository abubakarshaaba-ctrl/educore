package online.educoreng.educore.core.designsystem.component

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.RowScope
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Badge
import androidx.compose.material3.BadgedBox
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.NavigationBarItemDefaults
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

data class EduCoreNavigationItem(
    val key: String,
    val label: String,
    val icon: ImageVector,
    val badgeCount: Int = 0,
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun EduCoreTopAppBar(
    title: String,
    modifier: Modifier = Modifier,
    subtitle: String? = null,
    navigationIcon: ImageVector? = null,
    navigationDescription: String? = null,
    onNavigationClick: (() -> Unit)? = null,
    actions: @Composable RowScope.() -> Unit = {},
) {
    TopAppBar(
        modifier = modifier,
        title = {
            Column {
                Text(
                    text = title,
                    style = MaterialTheme.typography.titleMedium,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis,
                )
                subtitle?.takeIf(String::isNotBlank)?.let {
                    Text(
                        text = it,
                        style = MaterialTheme.typography.bodySmall,
                        color = Color.White.copy(alpha = 0.72f),
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                    )
                }
            }
        },
        navigationIcon = {
            // Back arrows are intentionally removed from the native app. Root
            // navigation and Android system back remain the navigation model.
            if (navigationIcon != null && onNavigationClick != null && navigationDescription != "Back") {
                IconButton(onClick = onNavigationClick) {
                    Icon(navigationIcon, contentDescription = navigationDescription)
                }
            }
        },
        actions = actions,
        colors = TopAppBarDefaults.topAppBarColors(
            containerColor = EduCoreColors.Navy900,
            titleContentColor = Color.White,
            navigationIconContentColor = Color.White,
            actionIconContentColor = Color.White,
        ),
    )
}

@Composable
fun EduCoreBottomNavigation(
    items: List<EduCoreNavigationItem>,
    selectedKey: String,
    onSelect: (EduCoreNavigationItem) -> Unit,
    modifier: Modifier = Modifier,
) {
    NavigationBar(
        modifier = modifier,
        containerColor = Color.White,
        tonalElevation = EduCoreSpacing.Xs,
    ) {
        items.forEach { item ->
            val selected = bottomNavigationSelected(item.key, selectedKey)
            NavigationBarItem(
                selected = selected,
                onClick = { onSelect(item) },
                icon = {
                    BadgedBox(
                        badge = {
                            if (item.badgeCount > 0) {
                                Badge {
                                    Text(item.badgeCount.coerceAtMost(99).toString() + if (item.badgeCount > 99) "+" else "")
                                }
                            }
                        },
                    ) { Icon(item.icon, contentDescription = item.label) }
                },
                label = {
                    Text(
                        item.label,
                        maxLines = 1,
                        fontWeight = if (selected) FontWeight.Medium else FontWeight.Normal,
                        style = MaterialTheme.typography.labelMedium,
                    )
                },
                colors = NavigationBarItemDefaults.colors(
                    selectedIconColor = EduCoreColors.Navy900,
                    selectedTextColor = EduCoreColors.Navy900,
                    indicatorColor = EduCoreColors.Info100,
                    unselectedIconColor = EduCoreColors.Muted500,
                    unselectedTextColor = EduCoreColors.Muted500,
                ),
            )
        }
    }
}

private fun bottomNavigationSelected(itemKey: String, selectedKey: String): Boolean = when {
    selectedKey == itemKey -> true
    selectedKey.startsWith("native/communications/") -> itemKey == "inbox"
    selectedKey.startsWith("native/operations/") -> itemKey == "secondary"
    selectedKey.startsWith("native/staff-attendance") -> itemKey == "more"
    else -> false
}

@Composable
fun EduCoreTenantHeader(
    schoolName: String,
    role: String,
    modifier: Modifier = Modifier,
    session: String? = null,
    trailing: @Composable RowScope.() -> Unit = {},
) {
    Row(
        modifier = modifier
            .fillMaxWidth()
            .background(EduCoreColors.Navy900)
            .padding(horizontal = EduCoreSpacing.Lg, vertical = EduCoreSpacing.Md),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Column(Modifier.weight(1f)) {
            Text(
                text = schoolName,
                color = Color.White,
                style = MaterialTheme.typography.titleMedium,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
            )
            Text(
                text = listOfNotNull(role, session).joinToString(" · "),
                color = Color.White.copy(alpha = 0.74f),
                style = MaterialTheme.typography.bodySmall,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
            )
        }
        trailing()
    }
}

/**
 * Compact in-page heading. Back arrows and decorative descriptive copy are
 * deliberately omitted; the persistent app bar already provides context.
 */
@Suppress("UNUSED_PARAMETER")
@Composable
fun EduCorePageHeader(
    title: String,
    subtitle: String? = null,
    modifier: Modifier = Modifier,
    onBack: (() -> Unit)? = null,
    compactActions: Boolean = false,
    actions: @Composable RowScope.() -> Unit = {},
) {
    if (title in setOf("Admissions", "Inbox")) return

    Row(
        modifier = modifier
            .fillMaxWidth()
            .padding(horizontal = EduCoreSpacing.Xs, vertical = EduCoreSpacing.Xs),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Text(
            text = title,
            modifier = Modifier.weight(1f),
            style = MaterialTheme.typography.titleMedium,
            color = EduCoreColors.Ink900,
            maxLines = 1,
            overflow = TextOverflow.Ellipsis,
        )
        actions()
    }
}

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
