package online.educoreng.educore.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.squareup.moshi.Moshi
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorState
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.AdminDirectoryApi
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.dto.AdminStudentDto
import online.educoreng.educore.core.network.safeApiCall

@HiltViewModel
class AdminStudentDirectoryViewModel @Inject constructor(
    factory: ApiClientFactory,
    moshi: Moshi,
) : ViewModel() {
    private val api = factory.create(AdminDirectoryApi::class.java)
    private val parser = moshi
    private val _uiState = MutableStateFlow(AdminStudentDirectoryUiState())
    val uiState: StateFlow<AdminStudentDirectoryUiState> = _uiState.asStateFlow()

    fun load() {
        if (_uiState.value.isLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            when (val result = safeApiCall(parser) { api.students() }) {
                is AppResult.Success -> _uiState.update {
                    it.copy(isLoading = false, students = result.value.students, errorMessage = null)
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isLoading = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }
}

data class AdminStudentDirectoryUiState(
    val isLoading: Boolean = false,
    val students: List<AdminStudentDto> = emptyList(),
    val errorMessage: String? = null,
)

@Composable
internal fun AdminStudentDirectoryScreen(
    state: AdminStudentDirectoryUiState,
    onBack: () -> Unit,
    onRefresh: () -> Unit,
) {
    if (state.isLoading && state.students.isEmpty()) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Loading students")
        return
    }
    if (state.students.isEmpty() && state.errorMessage != null) {
        EduCoreErrorState(
            message = state.errorMessage,
            modifier = Modifier.fillMaxSize(),
            onRetry = onRefresh,
        )
        return
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                IconButton(onClick = onBack) {
                    Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back", tint = EduCoreColors.Navy900)
                }
                Column(Modifier.weight(1f)) {
                    Text("Student Directory", style = MaterialTheme.typography.titleLarge, color = EduCoreColors.Navy900)
                    Text("${state.students.size} active students", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                }
                IconButton(onClick = onRefresh, enabled = !state.isLoading) {
                    Icon(Icons.Default.Refresh, contentDescription = "Refresh", tint = EduCoreColors.Navy900)
                }
            }
        }

        state.errorMessage?.let { message ->
            item {
                Surface(color = EduCoreColors.Danger100, shape = MaterialTheme.shapes.medium) {
                    Text(
                        message,
                        modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
                        color = EduCoreColors.Danger700,
                        style = MaterialTheme.typography.bodySmall,
                    )
                }
            }
        }

        if (state.students.isEmpty()) {
            item {
                EduCoreEmptyState(
                    title = "No active students",
                    message = "No active student records were returned for this school.",
                )
            }
        } else {
            items(state.students, key = AdminStudentDto::id) { student ->
                Surface(
                    modifier = Modifier.fillMaxWidth(),
                    color = EduCoreColors.White,
                    shape = MaterialTheme.shapes.large,
                    shadowElevation = 1.dp,
                ) {
                    Row(
                        modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
                    ) {
                        Column(Modifier.weight(1f)) {
                            Text(student.name, style = MaterialTheme.typography.titleSmall, color = EduCoreColors.Ink900)
                            val identity = listOfNotNull(
                                student.admissionNumber?.takeIf(String::isNotBlank),
                                student.className?.takeIf(String::isNotBlank),
                            ).joinToString(" · ")
                            if (identity.isNotBlank()) {
                                Text(identity, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                            }
                            student.gender?.takeIf(String::isNotBlank)?.let {
                                Text(
                                    it.replaceFirstChar { char -> char.uppercase() },
                                    style = MaterialTheme.typography.labelSmall,
                                    color = EduCoreColors.Muted500,
                                )
                            }
                        }
                        EduCoreStatusBadge(
                            student.status.orEmpty().ifBlank { "active" }.replaceFirstChar { it.uppercase() },
                            if (student.status.equals("active", true)) EduCoreTone.Success else EduCoreTone.Neutral,
                        )
                    }
                }
            }
        }
    }
}
