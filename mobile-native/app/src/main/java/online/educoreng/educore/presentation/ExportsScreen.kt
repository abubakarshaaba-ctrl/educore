package online.educoreng.educore.presentation

import androidx.compose.foundation.background
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Download
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

@Composable
internal fun ExportsScreen(
    state: ExportsUiState,
    onBack: () -> Unit,
    onType: (ExportType) -> Unit,
    onClass: (Long?) -> Unit,
    onTerm: (Long?) -> Unit,
    onSession: (Long?) -> Unit,
    onDownload: () -> Unit,
    onRetry: () -> Unit,
    onDocumentOpened: () -> Unit,
) {
    OpenDocumentEffect(state.document, onDocumentOpened)

    androidx.compose.foundation.lazy.LazyColumn(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Exports",
                subtitle = "Generate secure CSV reports without leaving EduCore",
                onBack = onBack,
            )
        }

        item {
            EduCoreShowcaseHero(
                eyebrow = "DATA EXPORTS",
                title = "Download only the records your role can access.",
                subtitle = "Every export is generated for your current school tenant and saved in EduCore's private document storage before it is opened.",
            )
        }

        state.errorMessage?.let { message ->
            item { EduCoreErrorBanner(message = message, onRetry = onRetry) }
        }

        if (state.isLoading && state.options == null) {
            item {
                Column(
                    modifier = Modifier.fillMaxWidth().padding(vertical = 32.dp),
                    horizontalAlignment = Alignment.CenterHorizontally,
                ) {
                    CircularProgressIndicator()
                    Spacer(Modifier.height(10.dp))
                    Text("Loading export options…")
                }
            }
        } else if (state.availableTypes.isEmpty()) {
            item {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                ) {
                    Column(Modifier.padding(18.dp)) {
                        Text("No export types available", fontWeight = FontWeight.SemiBold)
                        Spacer(Modifier.height(4.dp))
                        Text(
                            "Your account does not currently have access to student, broadsheet or fee exports.",
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                }
            }
        } else {
            item {
                Text("Export type", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                Spacer(Modifier.height(8.dp))
                Row(
                    modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    state.availableTypes.forEach { type ->
                        FilterChip(
                            selected = state.selectedType == type,
                            onClick = { onType(type) },
                            label = { Text(type.label) },
                        )
                    }
                }
                state.selectedType?.let { type ->
                    Spacer(Modifier.height(6.dp))
                    Text(type.description, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
            }

            when (state.selectedType) {
                ExportType.STUDENTS -> item {
                    ExportSelector(
                        label = "Class",
                        selected = state.options?.classes?.firstOrNull { it.id == state.selectedClassId }?.name ?: "All classes",
                        options = listOf(ExportChoice(null, "All classes")) + state.options.orEmptyClasses().map { ExportChoice(it.id, it.name) },
                        onSelected = onClass,
                    )
                }

                ExportType.BROADSHEET -> {
                    item {
                        ExportSelector(
                            label = "Class *",
                            selected = state.options?.classes?.firstOrNull { it.id == state.selectedClassId }?.name ?: "Choose class",
                            options = state.options.orEmptyClasses().map { ExportChoice(it.id, it.name) },
                            onSelected = onClass,
                        )
                    }
                    item {
                        ExportSelector(
                            label = "Term *",
                            selected = state.options?.terms?.firstOrNull { it.id == state.selectedTermId }
                                ?.let { listOfNotNull(it.name, it.sessionName).joinToString(" · ") }
                                ?: "Choose term",
                            options = state.options.orEmptyTerms().map {
                                ExportChoice(it.id, listOfNotNull(it.name, it.sessionName).joinToString(" · "))
                            },
                            onSelected = onTerm,
                        )
                    }
                }

                ExportType.FEES -> item {
                    ExportSelector(
                        label = "Academic session",
                        selected = state.options?.sessions?.firstOrNull { it.id == state.selectedSessionId }?.name ?: "All sessions",
                        options = listOf(ExportChoice(null, "All sessions")) + state.options.orEmptySessions().map { ExportChoice(it.id, it.name) },
                        onSelected = onSession,
                    )
                }

                null -> Unit
            }

            item {
                EduCorePrimaryButton(
                    text = if (state.isDownloading) "Generating export…" else "Generate CSV",
                    onClick = onDownload,
                    enabled = state.canDownload && !state.isDownloading,
                    modifier = Modifier.fillMaxWidth(),
                    leadingIcon = {
                        if (state.isDownloading) {
                            CircularProgressIndicator(strokeWidth = 2.dp, modifier = Modifier.height(18.dp))
                        } else {
                            androidx.compose.material3.Icon(Icons.Default.Download, contentDescription = null)
                        }
                    },
                )
            }

            state.message?.let { message ->
                item {
                    Text(
                        text = message,
                        color = MaterialTheme.colorScheme.primary,
                        style = MaterialTheme.typography.bodyMedium,
                    )
                }
            }
        }
    }
}

private data class ExportChoice(val id: Long?, val label: String)

@Composable
private fun ExportSelector(
    label: String,
    selected: String,
    options: List<ExportChoice>,
    onSelected: (Long?) -> Unit,
) {
    var expanded by remember { mutableStateOf(false) }

    Column(Modifier.fillMaxWidth()) {
        Text(label, style = MaterialTheme.typography.labelLarge)
        Spacer(Modifier.height(6.dp))
        OutlinedButton(
            onClick = { expanded = true },
            enabled = options.isNotEmpty(),
            modifier = Modifier.fillMaxWidth(),
        ) {
            Text(selected, modifier = Modifier.weight(1f))
        }
        DropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
            options.forEach { choice ->
                DropdownMenuItem(
                    text = { Text(choice.label) },
                    onClick = {
                        expanded = false
                        onSelected(choice.id)
                    },
                )
            }
        }
    }
}

private fun online.educoreng.educore.core.network.dto.ExportOptionsResponseDto?.orEmptyClasses() = this?.classes.orEmpty()
private fun online.educoreng.educore.core.network.dto.ExportOptionsResponseDto?.orEmptyTerms() = this?.terms.orEmpty()
private fun online.educoreng.educore.core.network.dto.ExportOptionsResponseDto?.orEmptySessions() = this?.sessions.orEmpty()
