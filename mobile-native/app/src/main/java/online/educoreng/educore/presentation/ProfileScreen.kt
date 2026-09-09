package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCoreProfileHeader
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.SessionSnapshot

@Composable
internal fun ProfileScreen(
    session: SessionSnapshot,
    onBack: () -> Unit,
) {
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "My Profile",
                subtitle = "Your EduCore identity and current school context",
                onBack = onBack,
            )
        }
        item {
            EduCoreProfileHeader(
                name = session.user.name,
                role = session.user.roleLabel,
                identifier = session.user.staffId ?: session.user.email,
                modifier = Modifier.fillMaxWidth(),
            )
        }
        item {
            ProfileSectionCard("Account") {
                ProfileRow("Email", session.user.email ?: "Not recorded")
                ProfileRow("Staff ID", session.user.staffId ?: "Not assigned")
                ProfileRow("Role", session.user.roleLabel)
                ProfileRow("Portal", session.user.portal.replaceFirstChar(Char::uppercase))
            }
        }
        item {
            ProfileSectionCard("School") {
                ProfileRow("School", session.school.name)
                ProfileRow("Academic session", session.academicPeriod.sessionName ?: "Not configured")
                ProfileRow("Current term", session.academicPeriod.termName ?: "Not configured")
            }
        }
        item {
            Card(
                modifier = Modifier.fillMaxWidth(),
                colors = CardDefaults.cardColors(containerColor = EduCoreColors.SurfaceBlue50),
                border = BorderStroke(1.dp, EduCoreColors.Info200),
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                ) {
                    Column(Modifier.weight(1f)) {
                        Text("Account status", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
                        Text(
                            "Profile identity is synchronized from the school account. Administrative identity changes remain controlled by authorized school administrators.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                    EduCoreStatusBadge("Active session", EduCoreTone.Success)
                }
            }
        }
    }
}

@Composable
private fun ProfileSectionCard(
    title: String,
    content: @Composable () -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        ) {
            Text(title, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
            content()
        }
    }
}

@Composable
private fun ProfileRow(label: String, value: String) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        Text(
            text = label,
            modifier = Modifier.weight(0.42f),
            style = MaterialTheme.typography.bodySmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
        Text(
            text = value,
            modifier = Modifier.weight(0.58f),
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = FontWeight.Medium,
        )
    }
}
