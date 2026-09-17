package online.educoreng.educore.presentation

import android.content.Context
import android.net.Uri
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.squareup.moshi.Moshi
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
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.data.repository.saveDownloadedDocument
import online.educoreng.educore.core.model.DownloadedDocument
import online.educoreng.educore.core.network.EduCoreApi
import online.educoreng.educore.core.network.dto.ChangePasswordRequestDto
import online.educoreng.educore.core.network.dto.StaffIdCardDto
import online.educoreng.educore.core.network.dto.UpdateProfileRequestDto
import online.educoreng.educore.core.network.dto.UserProfileDto
import online.educoreng.educore.core.network.safeApiCall

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
    private val api: EduCoreApi,
    private val moshi: Moshi,
    @ApplicationContext private val context: Context,
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
            val profileResult = safeApiCall(moshi) { api.profile() }
            val cardResult = safeApiCall(moshi) { api.staffIdCard() }

            when (profileResult) {
                is AppResult.Success -> applyProfile(profileResult.value.profile)
                is AppResult.Failure -> _uiState.update { it.copy(errorMessage = profileResult.error.userMessage) }
            }

            when (cardResult) {
                is AppResult.Success -> _uiState.update { it.copy(idCard = cardResult.value) }
                is AppResult.Failure -> {
                    // A profile is valid for non-staff accounts too; the staff card is optional there.
                    if (_uiState.value.profile?.staffId != null) {
                        _uiState.update { it.copy(errorMessage = cardResult.error.userMessage) }
                    }
                }
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
            _uiState.update { it.copy(errorMessage = "Name and email are required.") }
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
            when (val result = safeApiCall(moshi) { api.updateProfile(request) }) {
                is AppResult.Success -> {
                    applyProfile(result.value.profile)
                    _uiState.update { it.copy(isSaving = false, message = result.value.message) }
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isSaving = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun changePassword() {
        val state = _uiState.value
        if (state.isChangingPassword) return
        when {
            state.currentPassword.isBlank() -> {
                _uiState.update { it.copy(errorMessage = "Enter your current password.") }
                return
            }
            state.newPassword.length < 8 -> {
                _uiState.update { it.copy(errorMessage = "The new password must contain at least 8 characters.") }
                return
            }
            state.newPassword != state.confirmPassword -> {
                _uiState.update { it.copy(errorMessage = "New password and confirmation do not match.") }
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
            when (val result = safeApiCall(moshi) { api.changePassword(request) }) {
                is AppResult.Success -> _uiState.update {
                    it.copy(
                        isChangingPassword = false,
                        currentPassword = "",
                        newPassword = "",
                        confirmPassword = "",
                        message = result.value.message,
                    )
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isChangingPassword = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun uploadPassport(uri: Uri) {
        if (_uiState.value.isUploadingPassport) return
        viewModelScope.launch {
            _uiState.update { it.copy(isUploadingPassport = true, errorMessage = null, message = null) }
            val prepared = runCatching { preparePassport(uri) }
            if (prepared.isFailure) {
                _uiState.update {
                    it.copy(
                        isUploadingPassport = false,
                        errorMessage = prepared.exceptionOrNull()?.message ?: "The selected passport could not be read.",
                    )
                }
                return@launch
            }

            when (val result = safeApiCall(moshi) { api.uploadPassport(prepared.getOrThrow()) }) {
                is AppResult.Success -> {
                    applyProfile(result.value.profile)
                    val cardResult = safeApiCall(moshi) { api.staffIdCard() }
                    if (cardResult is AppResult.Success) {
                        _uiState.update { it.copy(idCard = cardResult.value) }
                    }
                    _uiState.update { it.copy(isUploadingPassport = false, message = result.value.message) }
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isUploadingPassport = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun downloadIdCard() {
        if (_uiState.value.isDownloadingIdCard) return
        viewModelScope.launch {
            _uiState.update { it.copy(isDownloadingIdCard = true, errorMessage = null, message = null) }
            when (val result = safeApiCall(moshi) { api.staffIdCardPdf() }) {
                is AppResult.Success -> {
                    val saved = runCatching {
                        withContext(Dispatchers.IO) {
                            saveDownloadedDocument(
                                context = context,
                                body = result.value,
                                requestedName = "EduCore_Staff_ID_Card.pdf",
                                requestedMimeType = "application/pdf",
                            )
                        }
                    }
                    saved.fold(
                        onSuccess = { document ->
                            _uiState.update {
                                it.copy(isDownloadingIdCard = false, document = document, message = "Staff ID card downloaded.")
                            }
                        },
                        onFailure = { error ->
                            _uiState.update {
                                it.copy(isDownloadingIdCard = false, errorMessage = error.message ?: "The ID card could not be saved.")
                            }
                        },
                    )
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isDownloadingIdCard = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun consumeDocument() = _uiState.update { it.copy(document = null) }
    fun consumeMessage() = _uiState.update { it.copy(message = null, errorMessage = null) }

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

    private suspend fun preparePassport(uri: Uri): MultipartBody.Part = withContext(Dispatchers.IO) {
        val resolver = context.contentResolver
        val mime = resolver.getType(uri)?.takeIf { it in ALLOWED_IMAGE_TYPES } ?: "image/jpeg"
        val bytes = resolver.openInputStream(uri)?.use { input ->
            val data = input.readBytes()
            require(data.size <= MAX_PASSPORT_BYTES) { "Passport image must not exceed 4 MB." }
            require(data.isNotEmpty()) { "The selected image is empty." }
            data
        } ?: error("The selected image could not be opened.")

        val extension = when (mime) {
            "image/png" -> "png"
            "image/webp" -> "webp"
            else -> "jpg"
        }
        val requestBody = bytes.toRequestBody(mime.toMediaTypeOrNull())
        MultipartBody.Part.createFormData("passport", "passport.$extension", requestBody)
    }

    private companion object {
        const val MAX_PASSPORT_BYTES = 4 * 1024 * 1024
        val ALLOWED_IMAGE_TYPES = setOf("image/jpeg", "image/jpg", "image/png", "image/webp")
    }
}
