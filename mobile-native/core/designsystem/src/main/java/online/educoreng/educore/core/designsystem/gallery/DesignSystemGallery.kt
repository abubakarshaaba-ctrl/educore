package online.educoreng.educore.core.designsystem.gallery

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.tooling.preview.Preview
import online.educoreng.educore.core.designsystem.component.EduCoreDashboardCard
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreFilterChip
import online.educoreng.educore.core.designsystem.component.EduCoreInfoBanner
import online.educoreng.educore.core.designsystem.component.EduCoreMetricCard
import online.educoreng.educore.core.designsystem.component.EduCoreModuleCard
import online.educoreng.educore.core.designsystem.component.EduCoreOfflineBanner
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.component.EduCoreWarningBanner
import online.educoreng.educore.core.designsystem.icon.EduCoreIcons
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.designsystem.theme.EduCoreTheme

@Composable
private fun DesignSystemGalleryContent() {
    var search by remember { mutableStateOf("") }
    var selected by remember { mutableStateOf(false) }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(EduCoreColors.Page50)
            .verticalScroll(rememberScrollState())
            .padding(EduCoreSpacing.Screen),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Lg),
    ) {
        EduCoreSectionHeader(
            title = "EduCore design system",
            supportingText = "Developer preview · not a production destination",
        )
        EduCoreSearchBar(search, { search = it }, placeholder = "Search resources")
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            EduCoreMetricCard(
                label = "Metric label",
                value = "—",
                icon = EduCoreIcons.Students,
                modifier = Modifier.weight(1f),
            )
            EduCoreMetricCard(
                label = "Status",
                value = "Ready",
                icon = EduCoreIcons.Attendance,
                tone = EduCoreTone.Success,
                modifier = Modifier.weight(1f),
            )
        }
        EduCoreDashboardCard {
            EduCoreSectionHeader("Controls", supportingText = "Core interaction states")
            EduCoreFilterChip("Selected", selected, { selected = !selected })
            EduCorePrimaryButton("Primary action", onClick = {})
        }
        EduCoreModuleCard(
            title = "Academic Repository",
            subtitle = "Module card",
            icon = EduCoreIcons.Repository,
            badge = "Available",
            onClick = {},
        )
        Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            EduCoreStatusBadge("Active", EduCoreTone.Success)
            EduCoreStatusBadge("Pending", EduCoreTone.Warning)
            EduCoreStatusBadge("Blocked", EduCoreTone.Danger)
        }
        EduCoreInfoBanner("Information banner")
        EduCoreWarningBanner("Warning banner")
        EduCoreErrorBanner("Error banner")
        EduCoreOfflineBanner()
        EduCoreEmptyState("No records", "Records will appear here when available.")
    }
}

@Preview(name = "Compact", widthDp = 360, heightDp = 800, showBackground = true)
@Preview(name = "Standard", widthDp = 412, heightDp = 900, showBackground = true)
@Preview(name = "Tablet", widthDp = 840, heightDp = 1000, showBackground = true)
@Composable
private fun DesignSystemGalleryPreview() {
    EduCoreTheme { DesignSystemGalleryContent() }
}
