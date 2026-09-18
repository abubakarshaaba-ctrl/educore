package online.educoreng.educore.presentation

import android.net.Uri
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.data.repository.ProfileSelfServiceRepository
import online.educoreng.educore.core.model.DownloadedDocument
import online.educoreng.educore.core.network.dto.ChangePasswordRequestDto
import online.educoreng.educore.core.network.dto.StaffIdCardDto
import online.educoreng.educore.core.network.dto.UpdateProfileRequestDto
import online.educoreng.educore.core.network.dto.UserProfileDto

data class ProfileUiState(
    val profile: UserProfileDto? = null,
    val idCard: StaffIdCardDto? = null,
    val name: String = "",
    val email: String = "",
    val phone: String = "",
    val dateOfBirth: String = "",
    val gender: String = "",
    val address: String = "",
    val currentPassword: String = "",
    val newPassword: String = "",
    val confirmPassword: String = "",
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val isChangingPassword: Boolean = false,
    val isUploadingPassport: Boolean = false,
    val isDownloadingIdCard: Boolean = false,
    val message: String? = null,
    val errorMessage: String? = null,
    val document: DownloadedDocument? = null,
)

@HiltViewModel
class ProfileViewModel @Inject constructor(
    private val repository: ProfileSelfServiceRepository,
) : ViewModel() {
    private val _uiState = MutableStateFlow(ProfileUiState())
    val uiState: StateFlow<ProfileUiState> = _uiState.asStateFlow()

    init {
        load()
    }

    fun load() {
        if (_uiState.value.isLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            when (val result = repository.profile()) {
                is AppResult.Success -> {
                    applyProfile(result.value)
                    val portal = result.value.portal
                    if (portal != "student" && portal != "parent" && portal != "platform") {
                        when (val cardResult = repository.staffIdCard()) {
                            is AppResult.Success -> _uiState.update { it.copy(idCard = cardResult.value) }
                            // ID-card data is supplementary to profile self-service.
                            // Do not turn an unavailable card into a page-wide access
                            // error when the authenticated profile itself loaded.
                            is AppResult.Failure -> Unit
                        }
                    }
                }
                is AppResult.Failure -> _uiState.update { it.copy(errorMessage = result.error.userMessage) }
            }
            _uiState.update { it.copy(isLoading = false) }
        }
    }

    fun setName(value: String) = _uiState.update { it.copy(name = value) }
    fun setEmail(value: String) = _uiState.update { it.copy(email = value) }
    fun setPhone(value: String) = _uiState.update { it.copy(phone = value) }
    fun setDateOfBirth(value: String) = _uiState.update { it.copy(dateOfBirth = value) }
    fun setGender(value: String) = _uiState.update { it.copy(gender = value) }
    fun setAddress(value: String) = _uiState.update { it.copy(address = value) }
    fun setCurrentPassword(value: String) = _uiState.update { it.copy(currentPassword = value) }
    fun setNewPassword(value: String) = _uiState.update { it.copy(newPassword = value) }
    fun setConfirmPassword(value: String) = _uiState.update { it.copy(confirmPassword = value) }

    fun saveProfile() {
        val state = _uiState.value
        if (state.isSaving) return
        if (state.name.isBlank() || state.email.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Name and email are required.", message = null) }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            val request = UpdateProfileRequestDto(
                name = state.name.trim(),
                email = state.email.trim(),
                phone = state.phone.trim(),
                dateOfBirth = state.dateOfBirth.trim(),
                gender = state.gender.trim(),
                address = state.address.trim(),
            )
            when (val result = repository.updateProfile(request)) {
                is AppResult.Success -> {
                    applyProfile(result.value)
                    _uiState.update { it.copy(isSaving = false, message = "Profile updated successfully.") }
                }
                is AppResult.Failure -> _uiState.update { it.copy(isSaving = false, errorMessage = result.error.userMessage) }
            }
        }
    }

    fun changePassword() {
        val state = _uiState.value
        if (state.isChangingPassword) return
        when {
            state.currentPassword.isBlank() -> {
                _uiState.update { it.copy(errorMessage = "Enter your current password.", message = null) }
                return
            }
            state.newPassword.length < 8 -> {
                _uiState.update { it.copy(errorMessage = "The new password must contain at least 8 characters.", message = null) }
                return
            }
            state.newPassword != state.confirmPassword -> {
                _uiState.update { it.copy(errorMessage = "New password and confirmation do not match.", message = null) }
                return
            }
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isChangingPassword = true, errorMessage = null, message = null) }
            val request = ChangePasswordRequestDto(
                currentPassword = state.currentPassword,
                password = state.newPassword,
                passwordConfirmation = state.confirmPassword,
            )
            when (val result = repository.changePassword(request)) {
                is AppResult.Success -> _uiState.update {
                    it.copy(
                        isChangingPassword = false,
                        currentPassword = "",
                        newPassword = "",
                        confirmPassword = "",
                        message = result.value,
                    )
                }
                is AppResult.Failure -> _uiState.update { it.copy(isChangingPassword = false, errorMessage = result.error.userMessage) }
            }
        }
    }

    fun uploadPassport(uri: Uri) {
        if (_uiState.value.isUploadingPassport) return
        viewModelScope.launch {
            _uiState.update { it.copy(isUploadingPassport = true, errorMessage = null, message = null) }
            when (val result = repository.uploadPassport(uri)) {
                is AppResult.Success -> {
                    applyProfile(result.value)
                    if (result.value.portal != "student" && result.value.portal != "parent" && result.value.portal != "platform") {
                        when (val cardResult = repository.staffIdCard()) {
                            is AppResult.Success -> _uiState.update { it.copy(idCard = cardResult.value) }
                            is AppResult.Failure -> Unit
                        }
                    }
                    _uiState.update { it.copy(isUploadingPassport = false, message = "Passport photograph updated successfully.") }
                }
                is AppResult.Failure -> _uiState.update { it.copy(isUploadingPassport = false, errorMessage = result.error.userMessage) }
            }
        }
    }

    fun downloadIdCard() {
        val state = _uiState.value
        if (state.isDownloadingIdCard) return
        viewModelScope.launch {
            _uiState.update { it.copy(isDownloadingIdCard = true, errorMessage = null, message = null) }
            when (
                val result = repository.downloadStaffIdCard(
                    staffId = state.idCard?.staffId ?: state.profile?.staffId,
                    staffName = state.idCard?.name ?: state.profile?.name ?: "EduCore Staff",
                )
            ) {
                is AppResult.Success -> _uiState.update {
                    it.copy(isDownloadingIdCard = false, document = result.value, message = "Staff ID card downloaded.")
                }
                is AppResult.Failure -> _uiState.update { it.copy(isDownloadingIdCard = false, errorMessage = result.error.userMessage) }
            }
        }
    }

    fun consumeDocument() = _uiState.update { it.copy(document = null) }
    fun clearFeedback() = _uiState.update { it.copy(message = null, errorMessage = null) }

    private fun applyProfile(profile: UserProfileDto) {
        _uiState.update {
            it.copy(
                profile = profile,
                name = profile.name,
                email = profile.email.orEmpty(),
                phone = profile.phone.orEmpty(),
                dateOfBirth = profile.dateOfBirth.orEmpty(),
                gender = profile.gender.orEmpty(),
                address = profile.address.orEmpty(),
            )
        }
    }
}
