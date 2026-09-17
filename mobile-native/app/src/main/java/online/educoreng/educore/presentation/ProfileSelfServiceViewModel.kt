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

data class ProfileSelfServiceUiState(
    val profile: UserProfileDto? = null,
    val idCard: StaffIdCardDto? = null,
    val isLoading: Boolean = false,
    val isSavingProfile: Boolean = false,
    val isChangingPassword: Boolean = false,
    val isUploadingPassport: Boolean = false,
    val isLoadingCard: Boolean = false,
    val isDownloadingCard: Boolean = false,
    val message: String? = null,
    val errorMessage: String? = null,
    val document: DownloadedDocument? = null,
)

@HiltViewModel
class ProfileSelfServiceViewModel @Inject constructor(
    private val repository: ProfileSelfServiceRepository,
) : ViewModel() {
    private val _uiState = MutableStateFlow(ProfileSelfServiceUiState())
    val uiState: StateFlow<ProfileSelfServiceUiState> = _uiState.asStateFlow()

    init {
        loadProfile()
    }

    fun loadProfile() {
        if (_uiState.value.isLoading) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            when (val result = repository.profile()) {
                is AppResult.Success -> _uiState.update { it.copy(profile = result.value, isLoading = false) }
                is AppResult.Failure -> _uiState.update { it.copy(isLoading = false, errorMessage = result.error.userMessage) }
            }
        }
    }

    fun saveProfile(
        name: String,
        email: String,
        phone: String,
        dateOfBirth: String,
        gender: String,
        address: String,
    ) {
        if (_uiState.value.isSavingProfile) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSavingProfile = true, message = null, errorMessage = null) }
            val request = UpdateProfileRequestDto(
                name = name.trim(),
                email = email.trim(),
                phone = phone.trim(),
                dateOfBirth = dateOfBirth.trim(),
                gender = gender.trim(),
                address = address.trim(),
            )
            when (val result = repository.updateProfile(request)) {
                is AppResult.Success -> _uiState.update {
                    it.copy(profile = result.value, isSavingProfile = false, message = "Profile updated successfully.", idCard = null)
                }
                is AppResult.Failure -> _uiState.update { it.copy(isSavingProfile = false, errorMessage = result.error.userMessage) }
            }
        }
    }

    fun changePassword(currentPassword: String, newPassword: String, confirmation: String) {
        if (_uiState.value.isChangingPassword) return
        if (newPassword != confirmation) {
            _uiState.update { it.copy(errorMessage = "The new password and confirmation do not match.", message = null) }
            return
        }
        if (newPassword.length < 8) {
            _uiState.update { it.copy(errorMessage = "The new password must contain at least 8 characters.", message = null) }
            return
        }
        viewModelScope.launch {
            _uiState.update { it.copy(isChangingPassword = true, message = null, errorMessage = null) }
            when (
                val result = repository.changePassword(
                    ChangePasswordRequestDto(currentPassword, newPassword, confirmation),
                )
            ) {
                is AppResult.Success -> _uiState.update { it.copy(isChangingPassword = false, message = result.value) }
                is AppResult.Failure -> _uiState.update { it.copy(isChangingPassword = false, errorMessage = result.error.userMessage) }
            }
        }
    }

    fun uploadPassport(uri: Uri) {
        if (_uiState.value.isUploadingPassport) return
        viewModelScope.launch {
            _uiState.update { it.copy(isUploadingPassport = true, message = null, errorMessage = null) }
            when (val result = repository.uploadPassport(uri)) {
                is AppResult.Success -> _uiState.update {
                    it.copy(
                        profile = result.value,
                        idCard = null,
                        isUploadingPassport = false,
                        message = "Passport photograph updated successfully.",
                    )
                }
                is AppResult.Failure -> _uiState.update { it.copy(isUploadingPassport = false, errorMessage = result.error.userMessage) }
            }
        }
    }

    fun loadStaffIdCard(force: Boolean = false) {
        if (_uiState.value.isLoadingCard || (!force && _uiState.value.idCard != null)) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoadingCard = true, errorMessage = null) }
            when (val result = repository.staffIdCard()) {
                is AppResult.Success -> _uiState.update { it.copy(idCard = result.value, isLoadingCard = false) }
                is AppResult.Failure -> _uiState.update { it.copy(isLoadingCard = false, errorMessage = result.error.userMessage) }
            }
        }
    }

    fun downloadStaffIdCard() {
        if (_uiState.value.isDownloadingCard) return
        val card = _uiState.value.idCard
        val profile = _uiState.value.profile
        viewModelScope.launch {
            _uiState.update { it.copy(isDownloadingCard = true, errorMessage = null) }
            when (
                val result = repository.downloadStaffIdCard(
                    staffId = card?.staffId ?: profile?.staffId,
                    staffName = card?.name ?: profile?.name ?: "EduCore Staff",
                )
            ) {
                is AppResult.Success -> _uiState.update { it.copy(isDownloadingCard = false, document = result.value) }
                is AppResult.Failure -> _uiState.update { it.copy(isDownloadingCard = false, errorMessage = result.error.userMessage) }
            }
        }
    }

    fun clearFeedback() = _uiState.update { it.copy(message = null, errorMessage = null) }
    fun consumeDocument() = _uiState.update { it.copy(document = null) }
}
