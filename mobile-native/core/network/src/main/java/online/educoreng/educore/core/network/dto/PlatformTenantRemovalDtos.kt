package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class PlatformTenantRemovalRequestDto(
    val confirmation: String,
    @param:Json(name = "current_password") val currentPassword: String,
    val reason: String,
)
