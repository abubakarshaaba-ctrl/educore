package online.educoreng.educore.core.designsystem.component

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.BoxWithConstraints
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.RowScope
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
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
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

data class EduCoreNavigationItem(
    val key: String,
    val label: String,
    val icon: ImageVector,
    val badgeCount: Int = 0,
)

@Composable
fun EduCoreWordmark(
    modifier: Modifier = Modifier,
    trailingText: String? = null,
) {
    Row(modifier = modifier, verticalAlignment = Alignment.CenterVertically) {
        Text(
            text = "Edu",
            color = Color.White,
            style = MaterialTheme.typography.titleMedium,
            fontWeight = FontWeight.SemiBold,
        )
        Text(
            text = "Core",
            color = EduCoreColors.Gold400,
            style = MaterialTheme.typography.titleMedium,
            fontWeight = FontWeight.SemiBold,
        )
        trailingText?.takeIf(String::isNotBlank)?.let {
            Text(
                text = " · $it",
                color = Color.White,
                style = MaterialTheme.typography.titleMedium,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
            )
        }
    }
}

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
                EduCoreWordmark(trailingText = title)
                subtitle?.let {
                    Text(
                        text = it,
                        style = MaterialTheme.typography.bodySmall,
                        color = Color.White.copy(alpha = 0.74f),
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                    )
                }
            }
        },
        navigationIcon = {
            if (navigationIcon != null && onNavigationClick != null) {
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
            actionIconContentColor = EduCoreColors.Gold400,
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
            NavigationBarItem(
                selected = selectedKey == item.key,
                onClick = { onSelect(item) },
                icon = {
                    BadgedBox(
                        badge = {
                            if (item.badgeCount > 0) {
                                Badge { Text(item.badgeCount.coerceAtMost(99).toString() + if (item.badgeCount > 99) "+" else "") }
                            }
                        },
                    ) { Icon(item.icon, contentDescription = item.label) }
                },
                label = { Text(item.label, maxLines = 1, style = MaterialTheme.typography.labelMedium) },
                colors = NavigationBarItemDefaults.colors(
                    selectedIconColor = EduCoreColors.Navy900,
                    selectedTextColor = EduCoreColors.Navy900,
                    indicatorColor = EduCoreColors.Gold100,
                    unselectedIconColor = EduCoreColors.Muted500,
                    unselectedTextColor = EduCoreColors.Muted500,
                ),
            )
        }
    }
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
                color = Color.White.copy(alpha = 0.76f),
                style = MaterialTheme.typography.bodySmall,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
            )
        }
        trailing()
    }
}

@Composable
fun EduCorePageHeader(
    title: String,
    subtitle: String? = null,
    modifier: Modifier = Modifier,
    onBack: (() -> Unit)? = null,
    compactActions: Boolean = false,
    actions: @Composable RowScope.() -> Unit = {},
) {
    // Page-level arrow buttons are intentionally suppressed across the native
    // app. Navigation remains available through the Android system back action,
    // bottom navigation, and explicit close/cancel actions where appropriate.
    @Suppress("UNUSED_VARIABLE")
    val retainedBackHandler = onBack

    androidx.compose.material3.Surface(
        modifier = modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.medium,
        color = EduCoreColors.Info100,
        border = BorderStroke(1.dp, EduCoreColors.Info200),
    ) {
        BoxWithConstraints(Modifier.fillMaxWidth().padding(horizontal = EduCoreSpacing.Md, vertical = EduCoreSpacing.Sm)) {
            if (maxWidth < 520.dp && compactActions) {
                Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        PageHeaderIdentity(title, subtitle)
                    }
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm, Alignment.End),
                        content = actions,
                    )
                }
            } else {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    PageHeaderIdentity(title, subtitle)
                    actions()
                }
            }
        }
    }
}

@Composable
private fun RowScope.PageHeaderIdentity(title: String, subtitle: String?) {
    Column(Modifier.weight(1f)) {
        Text(title, style = MaterialTheme.typography.titleLarge, color = EduCoreColors.Ink900, maxLines = 2, overflow = TextOverflow.Ellipsis)
        subtitle?.takeIf(String::isNotBlank)?.let {
            Text(it, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600, maxLines = 2, overflow = TextOverflow.Ellipsis)
        }
    }
}
