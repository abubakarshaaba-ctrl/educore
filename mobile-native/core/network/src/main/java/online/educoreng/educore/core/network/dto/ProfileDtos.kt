package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class ProfileResponseDto(
    val profile: UserProfileDto,
)

data class ProfileMutationResponseDto(
    val message: String,
    val profile: UserProfileDto,
)

data class UserProfileDto(
    val id: Long,
    val name: String,
    val email: String? = null,
    val phone: String? = null,
    @Json(name = "date_of_birth") val dateOfBirth: String? = null,
    val gender: String? = null,
    val address: String? = null,
    @Json(name = "staff_id") val staffId: String? = null,
    @Json(name = "role_key") val roleKey: String? = null,
    val role: String? = null,
    val portal: String? = null,
    @Json(name = "has_passport") val hasPassport: Boolean = false,
    @Json(name = "passport_version") val passportVersion: String? = null,
    @Json(name = "passport_url") val passportUrl: String? = null,
)

data class UpdateProfileRequestDto(
    val name: String,
    val email: String,
    val phone: String,
    @Json(name = "date_of_birth") val dateOfBirth: String,
    val gender: String,
    val address: String,
)

data class ChangePasswordRequestDto(
    @Json(name = "current_password") val currentPassword: String,
    val password: String,
    @Json(name = "password_confirmation") val passwordConfirmation: String,
)

data class StaffIdCardDto(
    val name: String,
    @Json(name = "staff_id") val staffId: String? = null,
    val role: String? = null,
    val department: String? = null,
    @Json(name = "date_joined") val dateJoined: String? = null,
    val email: String? = null,
    val phone: String? = null,
    @Json(name = "has_photo") val hasPhoto: Boolean = false,
    @Json(name = "photo_version") val photoVersion: String? = null,
    val photo: String? = null,
    @Json(name = "qr_payload") val qrPayload: String? = null,
    val school: StaffIdCardSchoolDto,
)

data class StaffIdCardSchoolDto(
    val name: String? = null,
    val motto: String? = null,
    val address: String? = null,
    val phone: String? = null,
    val email: String? = null,
    val website: String? = null,
    @Json(name = "has_signature") val hasSignature: Boolean = false,
    @Json(name = "signature_version") val signatureVersion: String? = null,
)
