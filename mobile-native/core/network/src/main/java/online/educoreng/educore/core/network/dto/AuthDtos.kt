package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class LoginRequestDto(
    @param:Json(name = "login_id") val loginId: String,
    val password: String,
    val device: String,
)

data class LoginResponseDto(
    val token: String,
    val user: UserDto,
    val school: SchoolDto,
    val permissions: List<String> = emptyList(),
)

data class ForgotPasswordRequestDto(
    val email: String,
)

data class PortalSessionRequestDto(val path: String)

data class PortalSessionResponseDto(val url: String)

data class MessageDto(val message: String)

data class UserDto(
    val id: Long,
    val name: String,
    val email: String? = null,
    @param:Json(name = "staff_id") val staffId: String? = null,
    @param:Json(name = "role_key") val roleKey: String,
    val role: String,
    val roles: List<String> = emptyList(),
    val portal: String,
)

data class SchoolDto(
    val id: Long? = null,
    val name: String,
    val slug: String,
    val branding: BrandingDto = BrandingDto(),
)

data class BrandingDto(
    @param:Json(name = "primary_color") val primaryColor: String = "#071E45",
    @param:Json(name = "accent_color") val accentColor: String = "#D79A21",
    val motto: String? = null,
)
