package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class PlatformAgentCreateRequestDto(
    val name: String,
    val email: String,
    val phone: String? = null,
    val state: String? = null,
    @param:Json(name = "commission_rate") val commissionRate: Double,
    val reason: String,
)

data class PlatformAgentUpdateRequestDto(
    val name: String? = null,
    val phone: String? = null,
    val state: String? = null,
    @param:Json(name = "commission_rate") val commissionRate: Double? = null,
    @param:Json(name = "is_active") val isActive: Boolean? = null,
    val reason: String,
)

data class PlatformAgentMutationResponseDto(
    val message: String,
    val agent: PlatformAgentDto,
)
