package online.educoreng.educore.presentation

import android.content.Context
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import online.educoreng.educore.core.data.repository.saveDownloadedDocument
import online.educoreng.educore.core.model.DownloadedDocument
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.ExportsApi
import online.educoreng.educore.core.network.dto.ExportOptionsResponseDto
import retrofit2.HttpException

enum class ExportType(val wireValue: String, val label: String, val description: String) {
    STUDENTS("students", "Student list", "Active students, admission numbers, classes and status"),
    BROADSHEET("broadsheet", "Broadsheet", "Class result summary for a selected term"),
    FEES("fees", "Fee report", "Invoices, collections and outstanding balances"),
}

internal data class ExportsUiState(
    val options: ExportOptionsResponseDto? = null,
    val selectedType: ExportType? = null,
    val selectedClassId: Long? = null,
    val selectedTermId: Long? = null,
    val selectedSessionId: Long? = null,
    val isLoading: Boolean = false,
    val isDownloading: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
    val document: DownloadedDocument? = null,
) {
    val availableTypes: List<ExportType>
        get() = buildList {
            val capabilities = options?.capabilities ?: return@buildList
            if (capabilities.students) add(ExportType.STUDENTS)
            if (capabilities.broadsheet) add(ExportType.BROADSHEET)
            if (capabilities.fees) add(ExportType.FEES)
        }

    val canDownload: Boolean
        get() = when (selectedType) {
            ExportType.STUDENTS -> true
            ExportType.BROADSHEET -> selectedClassId != null && selectedTermId != null
            ExportType.FEES -> true
            null -> false
        }
}

@HiltViewModel
internal class ExportsViewModel @Inject constructor(
    factory: ApiClientFactory,
    @ApplicationContext private val context: Context,
) : ViewModel() {
    private val api: ExportsApi = factory.create(ExportsApi::class.java)
    private val _uiState = MutableStateFlow(ExportsUiState())
    val uiState: StateFlow<ExportsUiState> = _uiState.asStateFlow()

    fun load() {
        if (_uiState.value.isLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            runCatching { api.options() }
                .onSuccess { options ->
                    _uiState.update { state ->
                        val available = buildList {
                            if (options.capabilities.students) add(ExportType.STUDENTS)
                            if (options.capabilities.broadsheet) add(ExportType.BROADSHEET)
                            if (options.capabilities.fees) add(ExportType.FEES)
                        }
                        state.copy(
                            options = options,
                            selectedType = state.selectedType?.takeIf { it in available } ?: available.firstOrNull(),
                            selectedTermId = state.selectedTermId
                                ?: options.terms.firstOrNull { it.current }?.id
                                ?: options.terms.firstOrNull()?.id,
                            selectedSessionId = state.selectedSessionId
                                ?: options.sessions.firstOrNull { it.current }?.id
                                ?: options.sessions.firstOrNull()?.id,
                            isLoading = false,
                        )
                    }
                }
                .onFailure { error ->
                    _uiState.update { it.copy(isLoading = false, errorMessage = error.exportMessage()) }
                }
        }
    }

    fun selectType(type: ExportType) = _uiState.update { state ->
        if (type in state.availableTypes) state.copy(selectedType = type, errorMessage = null) else state
    }

    fun selectClass(id: Long?) = _uiState.update { it.copy(selectedClassId = id, errorMessage = null) }
    fun selectTerm(id: Long?) = _uiState.update { it.copy(selectedTermId = id, errorMessage = null) }
    fun selectSession(id: Long?) = _uiState.update { it.copy(selectedSessionId = id, errorMessage = null) }

    fun download() {
        val state = _uiState.value
        val type = state.selectedType ?: return
        if (!state.canDownload || state.isDownloading) return

        viewModelScope.launch {
            _uiState.update { it.copy(isDownloading = true, errorMessage = null, message = null, document = null) }
            runCatching {
                val body = api.download(
                    type = type.wireValue,
                    classArmId = state.selectedClassId.takeIf { type != ExportType.FEES },
                    termId = state.selectedTermId.takeIf { type == ExportType.BROADSHEET },
                    sessionId = state.selectedSessionId.takeIf { type == ExportType.FEES },
                )
                withContext(Dispatchers.IO) {
                    saveDownloadedDocument(
                        context = context,
                        body = body,
                        requestedName = filename(type, state),
                        requestedMimeType = "text/csv",
                    )
                }
            }.onSuccess { document ->
                _uiState.update {
                    it.copy(
                        isDownloading = false,
                        document = document,
                        message = "${type.label} generated successfully.",
                    )
                }
            }.onFailure { error ->
                _uiState.update { it.copy(isDownloading = false, errorMessage = error.exportMessage()) }
            }
        }
    }

    fun consumeDocument() = _uiState.update { it.copy(document = null) }
    fun consumeMessage() = _uiState.update { it.copy(message = null) }

    private fun filename(type: ExportType, state: ExportsUiState): String = when (type) {
        ExportType.STUDENTS -> {
            val className = state.options?.classes?.firstOrNull { it.id == state.selectedClassId }?.name
            "EduCore_Students${className?.let { "_${safePart(it)}" }.orEmpty()}.csv"
        }
        ExportType.BROADSHEET -> {
            val className = state.options?.classes?.firstOrNull { it.id == state.selectedClassId }?.name ?: "Class"
            val termName = state.options?.terms?.firstOrNull { it.id == state.selectedTermId }?.name ?: "Term"
            "EduCore_Broadsheet_${safePart(className)}_${safePart(termName)}.csv"
        }
        ExportType.FEES -> {
            val sessionName = state.options?.sessions?.firstOrNull { it.id == state.selectedSessionId }?.name
            "EduCore_Fees${sessionName?.let { "_${safePart(it)}" }.orEmpty()}.csv"
        }
    }

    private fun safePart(value: String): String = value.replace(Regex("[^A-Za-z0-9_-]+"), "_").trim('_').take(50)
}

private fun Throwable.exportMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "Your account is not permitted to generate this export."
        404 -> "The selected export resource is no longer available."
        422 -> "Choose the required class, term or session before exporting."
        else -> "The export service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to generate the export. Check your connection and try again."
}
