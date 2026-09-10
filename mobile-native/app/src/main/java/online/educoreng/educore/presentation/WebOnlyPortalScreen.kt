package online.educoreng.educore.presentation

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

/**
 * Native Android is intentionally focused on staff, school-admin operational,
 * and parent workflows. Platform administration and the student portal remain
 * web-only while the operational mobile surface is hardened module-by-module.
 */
@Composable
internal fun WebOnlyPortalScreen(
    portal: String,
    accountName: String,
    onLogout: () -> Unit,
) {
    val title = when (portal.lowercase()) {
        "platform" -> "Platform administration"
        "student" -> "Student portal"
        else -> "Web-only account"
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(EduCoreColors.Page50)
            .padding(EduCoreSpacing.Xl),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Text(
            text = title,
            style = MaterialTheme.typography.titleLarge,
            color = EduCoreColors.Navy900,
            fontWeight = FontWeight.Medium,
            textAlign = TextAlign.Center,
        )
        Spacer(Modifier.height(EduCoreSpacing.Sm))
        Text(
            text = "$accountName should use EduCore Web for this account type.",
            style = MaterialTheme.typography.bodyMedium,
            color = EduCoreColors.Slate600,
            textAlign = TextAlign.Center,
        )
        Spacer(Modifier.height(EduCoreSpacing.Lg))
        EduCorePrimaryButton(
            text = "Sign out",
            onClick = onLogout,
            modifier = Modifier.fillMaxWidth(),
        )
    }
}
