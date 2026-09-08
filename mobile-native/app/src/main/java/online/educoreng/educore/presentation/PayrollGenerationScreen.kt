package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
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
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreTextField
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

@Composable
internal fun PayrollGenerationScreen(
    state: PayrollUiState,
    onBack: () -> Unit,
    onTitle: (String) -> Unit,
    onStart: (String) -> Unit,
    onEnd: (String) -> Unit,
    onGenerate: () -> Unit,
) {
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Generate Payroll",
                subtitle = "Prepare a draft using EduCore's authoritative payroll rules",
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item {
            Card(
                modifier = Modifier.fillMaxWidth(),
                colors = CardDefaults.cardColors(containerColor = EduCoreColors.SurfaceBlue50),
                border = BorderStroke(EduCoreSpacing.Hairline, EduCoreColors.Info200),
            ) {
                Column(
                    modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
                    verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs),
                ) {
                    Text(
                        "Authoritative calculation",
                        style = MaterialTheme.typography.titleSmall,
                    )
                    Text(
                        "Generation uses configured salary settings, PAYE bands, pension rules, peculiar staff deductions and unapplied disciplinary deductions. The mobile app does not recalculate these values itself.",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
            }
        }
        item {
            EduCoreTextField(
                value = state.generationTitle,
                onValueChange = onTitle,
                label = "Payroll title",
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isSaving,
                supportingText = "Example: September 2026 Payroll",
            )
        }
        item {
            EduCoreTextField(
                value = state.generationStart,
                onValueChange = onStart,
                label = "Period start",
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isSaving,
                supportingText = "YYYY-MM-DD",
            )
        }
        item {
            EduCoreTextField(
                value = state.generationEnd,
                onValueChange = onEnd,
                label = "Period end",
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isSaving,
                supportingText = "YYYY-MM-DD",
            )
        }
        item {
            EduCorePrimaryButton(
                text = "Generate draft payroll",
                onClick = onGenerate,
                modifier = Modifier.fillMaxWidth(),
                enabled = state.canManage && !state.isSaving,
                loading = state.isSaving,
            )
        }
        item {
            EduCoreSecondaryButton(
                text = "Cancel",
                onClick = onBack,
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isSaving,
            )
        }
        item {
            Text(
                "EduCore prevents a second payroll from being generated for the same tenant and exact date range.",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }
    }
}
