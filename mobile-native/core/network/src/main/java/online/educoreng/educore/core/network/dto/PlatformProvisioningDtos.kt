package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class PlatformTenantProvisionRequestDto(
    val name: String,
    val slug: String,
    val subdomain: String? = null,
    val email: String,
    val phone: String? = null,
    val address: String? = null,
    @param:Json(name = "admin_name") val adminName: String,
    @param:Json(name = "admin_email") val adminEmail: String,
    @param:Json(name = "admin_password") val adminPassword: String,
    @param:Json(name = "admin_employment_started_at") val adminEmploymentStartedAt: String,
)

data class PlatformProvisionedTenantDto(
    val id: Long,
    val name: String,
    val slug: String,
    val subdomain: String? = null,
    val status: String,
)

data class PlatformProvisionedAdminDto(
    val id: Long,
    val name: String,
    val email: String,
)

data class PlatformTenantProvisionResponseDto(
    val message: String,
    val tenant: PlatformProvisionedTenantDto,
    val administrator: PlatformProvisionedAdminDto,
)
