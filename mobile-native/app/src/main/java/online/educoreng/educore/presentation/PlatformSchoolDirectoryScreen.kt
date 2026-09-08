package online.educoreng.educore.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.PlatformTenantDto

@Composable
internal fun PlatformSchoolDirectoryScreen(
    schools: List<PlatformTenantDto>,
    onBack: () -> Unit,
    onOpen: (Long) -> Unit,
) {
    Column(Modifier.fillMaxSize()) {
        EduCorePageHeader(
            title = "Manage Schools",
            subtitle = "Lifecycle, administrators and subscriptions",
            onBack = onBack,
        )
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = androidx.compose.foundation.layout.PaddingValues(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            if (schools.isEmpty()) {
                item { EduCoreEmptyState("No schools available", "Return to Schools and refresh the directory.") }
            }
            items(schools, key = { it.id }) { tenant ->
                Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                    Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            Text(tenant.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, modifier = Modifier.weight(1f))
                            EduCoreStatusBadge(
                                tenant.status.replace('_', ' '),
                                if (tenant.status == "active") EduCoreTone.Success else if (tenant.status == "pending") EduCoreTone.Warning else EduCoreTone.Danger,
                            )
                        }
                        Text(tenant.slug, style = MaterialTheme.typography.bodySmall)
                        Text("${tenant.students} students · ${tenant.users} users", style = MaterialTheme.typography.bodySmall)
                        tenant.subscriptionExpiresAt?.let { Text("Expires $it", style = MaterialTheme.typography.bodySmall) }
                        EduCorePrimaryButton(
                            text = "Manage school",
                            onClick = { onOpen(tenant.id) },
                            modifier = Modifier.fillMaxWidth(),
                        )
                    }
                }
            }
        }
    }
}
