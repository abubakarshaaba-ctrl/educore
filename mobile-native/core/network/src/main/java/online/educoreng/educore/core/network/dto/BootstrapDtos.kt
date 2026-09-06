package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class BootstrapResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val user: UserDto,
    val school: SchoolDto,
    val academic: AcademicContextDto,
    val access: TenantAccessDto,
    val permissions: List<String> = emptyList(),
    val features: List<String> = emptyList(),
    val modules: List<ModuleDto> = emptyList(),
    val token: TokenMetadataDto = TokenMetadataDto(),
    @param:Json(name = "server_time") val serverTime: String? = null,
)

data class TokenMetadataDto(
    @param:Json(name = "expires_at") val expiresAt: String? = null,
)

data class AcademicContextDto(
    val session: AcademicPeriodItemDto? = null,
    val term: AcademicPeriodItemDto? = null,
)

data class AcademicPeriodItemDto(
    val id: Long,
    val name: String,
)

data class TenantAccessDto(
    val allowed: Boolean,
    val state: String,
    val message: String,
    val severity: String? = null,
    @param:Json(name = "expires_at") val expiresAt: String? = null,
)

data class ModuleDto(
    val key: String,
    val title: String,
    val path: String,
    val icon: String,
)
