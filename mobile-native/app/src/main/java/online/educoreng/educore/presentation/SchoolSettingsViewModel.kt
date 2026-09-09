package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.SchoolSettingsApi
import online.educoreng.educore.core.network.dto.SchoolSettingsUpdateRequestDto
import online.educoreng.educore.core.network.dto.SchoolSettingsWorkspaceDto
import retrofit2.HttpException

internal data class SchoolSettingsUiState(
    val workspace: SchoolSettingsWorkspaceDto? = null,
    val name: String = "",
    val motto: String = "",
    val address: String = "",
    val phone: String = "",
    val email: String = "",
    val website: String = "",
    val establishedYear: String = "",
    val proprietor: String = "",
    val slogan: String = "",
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val canManage: Boolean get() = workspace?.capabilities?.manage == true
    val valid: Boolean
        get() = name.isNotBlank()
            && (email.isBlank() || email.contains('@'))
            && (website.isBlank() || website.startsWith("http://") || website.startsWith("https://"))
            && (establishedYear.isBlank() || establishedYear.toIntOrNull() != null)
}

@HiltViewModel
internal class SchoolSettingsViewModel @Inject constructor(factory: ApiClientFactory) : ViewModel() {
    private val api = factory.create(SchoolSettingsApi::class.java)
    private val _uiState = MutableStateFlow(SchoolSettingsUiState())
    val uiState: StateFlow<SchoolSettingsUiState> = _uiState.asStateFlow()
    private var loadJob: Job? = null

    fun load() {
        loadJob?.cancel()
        loadJob = viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null, message = null) }
            try {
                applyWorkspace(api.show(), isLoading = false)
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isLoading = false, errorMessage = error.schoolSettingsMessage()) }
            }
        }
    }

    fun setName(v: String) = edit { it.copy(name = v.take(150)) }
    fun setMotto(v: String) = edit { it.copy(motto = v.take(200)) }
    fun setAddress(v: String) = edit { it.copy(address = v.take(300)) }
    fun setPhone(v: String) = edit { it.copy(phone = v.take(20)) }
    fun setEmail(v: String) = edit { it.copy(email = v.take(180)) }
    fun setWebsite(v: String) = edit { it.copy(website = v.take(255)) }
    fun setEstablishedYear(v: String) = edit { it.copy(establishedYear = v.filter(Char::isDigit).take(4)) }
    fun setProprietor(v: String) = edit { it.copy(proprietor = v.take(150)) }
    fun setSlogan(v: String) = edit { it.copy(slogan = v.take(200)) }

    fun save() {
        val state = _uiState.value
        if (!state.canManage || !state.valid || state.isSaving) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            try {
                val workspace = api.update(
                    SchoolSettingsUpdateRequestDto(
                        name = state.name.trim(),
                        motto = state.motto.trim().ifBlank { null },
                        address = state.address.trim().ifBlank { null },
                        phone = state.phone.trim().ifBlank { null },
                        email = state.email.trim().ifBlank { null },
                        website = state.website.trim().ifBlank { null },
                        establishedYear = state.establishedYear.toIntOrNull(),
                        proprietor = state.proprietor.trim().ifBlank { null },
                        slogan = state.slogan.trim().ifBlank { null },
                    )
                )
                applyWorkspace(workspace, isSaving = false, message = workspace.message ?: "School settings updated.")
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isSaving = false, errorMessage = error.schoolSettingsMessage()) }
            }
        }
    }

    private fun edit(change: (SchoolSettingsUiState) -> SchoolSettingsUiState) {
        _uiState.update { if (it.isSaving) it else change(it).copy(errorMessage = null, message = null) }
    }

    private fun applyWorkspace(
        workspace: SchoolSettingsWorkspaceDto,
        isLoading: Boolean = _uiState.value.isLoading,
        isSaving: Boolean = _uiState.value.isSaving,
        message: String? = _uiState.value.message,
    ) {
        val school = workspace.school
        _uiState.update {
            it.copy(
                workspace = workspace,
                name = school.name,
                motto = school.motto.orEmpty(),
                address = school.address.orEmpty(),
                phone = school.phone.orEmpty(),
                email = school.email.orEmpty(),
                website = school.website.orEmpty(),
                establishedYear = school.establishedYear.orEmpty(),
                proprietor = school.proprietor.orEmpty(),
                slogan = school.slogan.orEmpty(),
                isLoading = isLoading,
                isSaving = isSaving,
                message = message,
            )
        }
    }
}

private fun Throwable.schoolSettingsMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "Your account does not have school-settings management permission."
        422 -> "Review the school details. Some values are invalid."
        else -> "The school-settings service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the school-settings service. Check your connection and try again."
}
