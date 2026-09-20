package online.educoreng.educore.presentation

import android.content.Context
import android.database.Cursor
import android.net.Uri
import android.provider.OpenableColumns
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import dagger.hilt.android.qualifiers.ApplicationContext
import java.io.IOException
import javax.inject.Inject
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import okhttp3.MediaType
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import okio.BufferedSink
import online.educoreng.educore.core.network.AdvancedAdminApi
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.dto.AdvancedAdminConfirmationDto
import online.educoreng.educore.core.network.dto.AdvancedAdminDecisionDto
import online.educoreng.educore.core.network.dto.AdvancedAdminOverviewDto
import retrofit2.HttpException

data class AdvancedAdminSourceFile(
    val uri: Uri,
    val name: String,
    val size: Long?,
    val mimeType: String?,
)

data class AdvancedAdminUiState(
    val overview: AdvancedAdminOverviewDto? = null,
    val selectedTenantId: Long? = null,
    val sourceFiles: List<AdvancedAdminSourceFile> = emptyList(),
    val isLoading: Boolean = false,
    val isMutating: Boolean = false,
    val message: String? = null,
    val errorMessage: String? = null,
)

@HiltViewModel
class AdvancedAdministrationViewModel @Inject constructor(
    factory: ApiClientFactory,
    @ApplicationContext private val context: Context,
) : ViewModel() {
    private val api = factory.create(AdvancedAdminApi::class.java)
    private val _uiState = MutableStateFlow(AdvancedAdminUiState())
    val uiState: StateFlow<AdvancedAdminUiState> = _uiState.asStateFlow()

    init {
        load()
    }

    fun load(tenantId: Long? = _uiState.value.selectedTenantId) {
        if (_uiState.value.isLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            try {
                val overview = api.overview(tenantId)
                _uiState.update {
                    it.copy(
                        overview = overview,
                        selectedTenantId = overview.selectedTenantId ?: tenantId,
                        isLoading = false,
                    )
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update {
                    it.copy(isLoading = false, errorMessage = error.advancedAdminMessage())
                }
            }
        }
    }

    fun selectTenant(tenantId: Long?) {
        _uiState.update { it.copy(selectedTenantId = tenantId, message = null, errorMessage = null) }
        load(tenantId)
    }

    fun setSourceFiles(uris: List<Uri>) {
        val files = uris.distinct().take(20).map { uri ->
            val metadata = queryMetadata(uri)
            AdvancedAdminSourceFile(
                uri = uri,
                name = metadata.first ?: "migration-source",
                size = metadata.second,
                mimeType = context.contentResolver.getType(uri),
            )
        }
        _uiState.update { it.copy(sourceFiles = files, errorMessage = null) }
    }

    fun clearSourceFiles() = _uiState.update { it.copy(sourceFiles = emptyList()) }

    fun createMigration(
        tenantId: Long?,
        direction: String,
        migrationType: String,
        sourcePlatform: String,
        justification: String,
        dataScope: Set<String>,
    ) {
        val state = _uiState.value
        if (state.isMutating) return
        val platformScope = state.overview?.scope == "platform"
        when {
            platformScope && tenantId == null -> return failLocal("Select a school for this migration.")
            sourcePlatform.trim().isEmpty() -> return failLocal("Enter the source platform.")
            justification.trim().length < 20 -> return failLocal("Enter a business justification of at least 20 characters.")
            dataScope.isEmpty() -> return failLocal("Select at least one data scope.")
            state.sourceFiles.isEmpty() -> return failLocal("Select at least one source file.")
        }

        mutate {
            val text = "text/plain".toMediaTypeOrNull()
            val parts = state.sourceFiles.map { file ->
                MultipartBody.Part.createFormData(
                    "source_files[]",
                    file.name,
                    ContentUriRequestBody(context, file.uri, file.mimeType?.toMediaTypeOrNull()),
                )
            }
            val result = api.createMigration(
                tenantId = if (platformScope) tenantId?.toString()?.toRequestBody(text) else null,
                direction = direction.toRequestBody(text),
                migrationType = migrationType.toRequestBody(text),
                sourcePlatform = sourcePlatform.trim().toRequestBody(text),
                destinationSystem = "EduCore".toRequestBody(text),
                businessJustification = justification.trim().toRequestBody(text),
                dataScope = dataScope.sorted().map { it.toRequestBody(text) },
                sourceFiles = parts,
            )
            _uiState.update { it.copy(sourceFiles = emptyList()) }
            result.message
        }
    }

    fun ingest(migrationId: Long) = mutate { api.ingest(migrationId).message }

    fun verify(migrationId: Long) = mutate { api.verify(migrationId).message }

    fun reconstructBlueprint(migrationId: Long) = mutate {
        api.reconstructBlueprint(migrationId, AdvancedAdminConfirmationDto("RECONSTRUCT")).message
    }

    fun approve(requestId: Long, reason: String) {
        if (reason.trim().length < 10) return failLocal("Enter an approval reason of at least 10 characters.")
        mutate { api.approve(requestId, AdvancedAdminDecisionDto(reason.trim())).message }
    }

    fun reject(requestId: Long, reason: String) {
        if (reason.trim().length < 10) return failLocal("Enter a rejection reason of at least 10 characters.")
        mutate { api.reject(requestId, AdvancedAdminDecisionDto(reason.trim())).message }
    }

    fun backup() = mutate {
        api.backup(AdvancedAdminConfirmationDto("BACKUP")).message
    }

    fun consumeFeedback() = _uiState.update { it.copy(message = null, errorMessage = null) }

    private fun mutate(action: suspend () -> String) {
        if (_uiState.value.isMutating) return
        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null, message = null) }
            try {
                val message = action()
                val tenantId = _uiState.value.selectedTenantId
                val overview = api.overview(tenantId)
                _uiState.update {
                    it.copy(
                        overview = overview,
                        selectedTenantId = overview.selectedTenantId ?: tenantId,
                        isMutating = false,
                        message = message,
                    )
                }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update {
                    it.copy(isMutating = false, errorMessage = error.advancedAdminMessage())
                }
            }
        }
    }

    private fun failLocal(message: String) {
        _uiState.update { it.copy(errorMessage = message, message = null) }
    }

    private fun queryMetadata(uri: Uri): Pair<String?, Long?> {
        var cursor: Cursor? = null
        return try {
            cursor = context.contentResolver.query(
                uri,
                arrayOf(OpenableColumns.DISPLAY_NAME, OpenableColumns.SIZE),
                null,
                null,
                null,
            )
            if (cursor?.moveToFirst() == true) {
                val nameIndex = cursor.getColumnIndex(OpenableColumns.DISPLAY_NAME)
                val sizeIndex = cursor.getColumnIndex(OpenableColumns.SIZE)
                val name = if (nameIndex >= 0) cursor.getString(nameIndex) else null
                val size = if (sizeIndex >= 0 && !cursor.isNull(sizeIndex)) cursor.getLong(sizeIndex) else null
                name to size
            } else {
                null to null
            }
        } finally {
            cursor?.close()
        }
    }
}

private class ContentUriRequestBody(
    private val context: Context,
    private val uri: Uri,
    private val type: MediaType?,
) : RequestBody() {
    override fun contentType(): MediaType? = type

    override fun contentLength(): Long {
        context.contentResolver.query(uri, arrayOf(OpenableColumns.SIZE), null, null, null)?.use { cursor ->
            if (cursor.moveToFirst()) {
                val index = cursor.getColumnIndex(OpenableColumns.SIZE)
                if (index >= 0 && !cursor.isNull(index)) return cursor.getLong(index)
            }
        }
        return -1L
    }

    override fun writeTo(sink: BufferedSink) {
        val input = context.contentResolver.openInputStream(uri)
            ?: throw IOException("Unable to open selected migration source file.")
        input.use { stream ->
            val buffer = ByteArray(DEFAULT_BUFFER_SIZE)
            while (true) {
                val read = stream.read(buffer)
                if (read < 0) break
                sink.write(buffer, 0, read)
            }
        }
    }
}

private fun Throwable.advancedAdminMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "This account is not authorised for advanced administration."
        404 -> "The requested migration or administration record no longer exists."
        409 -> "The administration state changed. Refresh and try again."
        422 -> "The request was rejected by the administration workflow. Check the required fields and current migration state."
        else -> "Advanced Administration returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach Advanced Administration. Check your connection and try again."
}
