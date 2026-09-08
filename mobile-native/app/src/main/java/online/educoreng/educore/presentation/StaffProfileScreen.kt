package online.educoreng.educore.presentation

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Badge
import androidx.compose.material.icons.filled.Business
import androidx.compose.material.icons.filled.Email
import androidx.compose.material.icons.filled.Event
import androidx.compose.material.icons.filled.Person
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreProfileHeader
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.SessionSnapshot

/** Native, read-only staff identity screen for Phase C. */
@Composable
internal fun StaffProfileScreen(
    session: SessionSnapshot,
    onBack: () -> Unit,
) {
    LazyColumn(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = androidx.compose.foundation.layout.PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "My Profile",
                subtitle = "Your EduCore account and school identity",
                onBack = onBack,
            )
        }
        item {
            Card(
                modifier = Modifier.fillMaxWidth(),
                colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
            ) {
                Column {
                    EduCoreProfileHeader(
                        name = session.user.name,
                        role = session.user.roleLabel,
                        identifier = session.user.staffId ?: session.user.email,
                    )
                    Row(
                        modifier = Modifier.fillMaxWidth().padding(
                            start = EduCoreSpacing.Lg,
                            end = EduCoreSpacing.Lg,
                            bottom = EduCoreSpacing.Lg,
                        ),
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        EduCoreStatusBadge("Active", EduCoreTone.Success)
                    }
                }
            }
        }
        item {
            ProfileDetailsCard(
                rows = listOf(
                    ProfileRow(Icons.Default.Badge, "Staff ID", session.user.staffId ?: "Not assigned"),
                    ProfileRow(Icons.Default.Email, "Email", session.user.email ?: "Not provided"),
                    ProfileRow(Icons.Default.Person, "Role", session.user.roleLabel),
                    ProfileRow(Icons.Default.Business, "School", session.school.name),
                    ProfileRow(Icons.Default.Event, "Academic session", session.academicPeriod.sessionName ?: "Not set"),
                    ProfileRow(Icons.Default.Event, "Current term", session.academicPeriod.termName ?: "Not set"),
                ),
            )
        }
        session.school.motto?.takeIf(String::isNotBlank)?.let { motto ->
            item {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    colors = CardDefaults.cardColors(containerColor = EduCoreColors.Gold50),
                ) {
                    Column(Modifier.padding(EduCoreSpacing.Lg)) {
                        Text("School motto", style = MaterialTheme.typography.labelLarge, color = EduCoreColors.Navy900)
                        Text(motto, style = MaterialTheme.typography.bodyMedium, color = EduCoreColors.Slate700)
                    }
                }
            }
        }
    }
}

private data class ProfileRow(
    val icon: ImageVector,
    val label: String,
    val value: String,
)

@Composable
private fun ProfileDetailsCard(rows: List<ProfileRow>) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
    ) {
        Column {
            rows.forEachIndexed { index, row ->
                Row(
                    modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Icon(row.icon, contentDescription = null, tint = EduCoreColors.Navy900)
                    Column(Modifier.weight(1f)) {
                        Text(row.label, style = MaterialTheme.typography.labelMedium, color = EduCoreColors.Slate600)
                        Text(row.value, style = MaterialTheme.typography.bodyLarge, color = EduCoreColors.Ink900)
                    }
                }
                if (index != rows.lastIndex) HorizontalDivider(color = EduCoreColors.Line200)
            }
        }
    }
}
