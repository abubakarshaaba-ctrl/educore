package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.PlatformApi
import online.educoreng.educore.core.network.dto.PlatformTenantProvisionRequestDto
import retrofit2.HttpException

internal data class PlatformProvisioningUiState(
    val schoolName: String = "",
    val slug: String = "",
    val subdomain: String = "",
    val schoolEmail: String = "",
    val phone: String = "",
    val address: String = "",
    val adminName: String = "",
    val adminEmail: String = "",
    val adminPassword: String = "",
    val employmentStartDate: String = "",
    val isSubmitting: Boolean = false,
    val errorMessage: String? = null,
) {
    val valid: Boolean
        get() = schoolName.trim().length >= 2 &&
            slug.matches(Regex("^[A-Za-z0-9 _-]{2,150}$")) &&
            schoolEmail.contains('@') &&
            adminName.trim().length >= 2 &&
            adminEmail.contains('@') &&
            adminPassword.length >= 8 &&
            employmentStartDate.matches(Regex("^\\d{4}-\\d{2}-\\d{2}$"))
}

@HiltViewModel
internal class PlatformProvisioningViewModel @Inject constructor(factory: ApiClientFactory) : ViewModel() {
    private val api = factory.create(PlatformApi::class.java)
    private val _uiState = MutableStateFlow(PlatformProvisioningUiState())
    val uiState: StateFlow<PlatformProvisioningUiState> = _uiState.asStateFlow()

    fun setSchoolName(value: String) = update {
        val name = value.take(150)
        val autoSlug = if (slug.isBlank() || slug == slugify(schoolName)) slugify(name) else slug
        copy(schoolName = name, slug = autoSlug)
    }
    fun setSlug(value: String) = update { copy(slug = value.take(150)) }
    fun setSubdomain(value: String) = update { copy(subdomain = value.take(80)) }
    fun setSchoolEmail(value: String) = update { copy(schoolEmail = value.take(180)) }
    fun setPhone(value: String) = update { copy(phone = value.take(50)) }
    fun setAddress(value: String) = update { copy(address = value.take(255)) }
    fun setAdminName(value: String) = update { copy(adminName = value.take(150)) }
    fun setAdminEmail(value: String) = update { copy(adminEmail = value.take(180)) }
    fun setAdminPassword(value: String) = update { copy(adminPassword = value.take(255)) }
    fun setEmploymentStartDate(value: String) = update { copy(employmentStartDate = value.take(10)) }

    fun submit(onCreated: (Long) -> Unit) {
        val state = _uiState.value
        if (!state.valid || state.isSubmitting) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSubmitting = true, errorMessage = null) }
            try {
                val response = api.provisionTenant(
                    PlatformTenantProvisionRequestDto(
                        name = state.schoolName.trim(),
                        slug = state.slug.trim(),
                        subdomain = state.subdomain.trim().takeIf(String::isNotBlank),
                        email = state.schoolEmail.trim(),
                        phone = state.phone.trim().takeIf(String::isNotBlank),
                        address = state.address.trim().takeIf(String::isNotBlank),
                        adminName = state.adminName.trim(),
                        adminEmail = state.adminEmail.trim(),
                        adminPassword = state.adminPassword,
                        adminEmploymentStartedAt = state.employmentStartDate,
                    )
                )
                _uiState.value = PlatformProvisioningUiState()
                onCreated(response.tenant.id)
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isSubmitting = false, errorMessage = error.provisioningMessage()) }
            }
        }
    }

    fun reset() { _uiState.value = PlatformProvisioningUiState() }

    private fun update(block: PlatformProvisioningUiState.() -> PlatformProvisioningUiState) {
        _uiState.update { if (it.isSubmitting) it else it.block().copy(errorMessage = null) }
    }

    private fun slugify(value: String): String = value
        .trim()
        .lowercase()
        .replace(Regex("[^a-z0-9]+"), "-")
        .trim('-')
}

private fun Throwable.provisioningMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your platform session has expired. Sign in again."
        403 -> "Platform Super Admin access is required."
        422 -> "School provisioning was rejected. Check unique slug/subdomain, administrator email, password and employment date."
        else -> "The platform service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank) ?: "Unable to reach the school provisioning service."
}
