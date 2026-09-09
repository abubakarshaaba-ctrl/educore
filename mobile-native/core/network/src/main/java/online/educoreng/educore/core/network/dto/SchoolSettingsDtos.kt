package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class SchoolSettingsCapabilitiesDto(val manage: Boolean = false)

data class SchoolSettingsSchoolDto(
    val id: Long,
    val name: String,
    val motto: String? = null,
    val address: String? = null,
    val phone: String? = null,
    val email: String? = null,
    val website: String? = null,
    @param:Json(name = "established_year") val establishedYear: String? = null,
    val proprietor: String? = null,
    val slogan: String? = null,
    @param:Json(name = "logo_configured") val logoConfigured: Boolean = false,
    @param:Json(name = "authorized_signature_configured") val authorizedSignatureConfigured: Boolean = false,
)

data class SchoolSettingsWorkspaceDto(
    @param:Json(name = "contract_version") val contractVersion: Int = 1,
    val capabilities: SchoolSettingsCapabilitiesDto = SchoolSettingsCapabilitiesDto(),
    val school: SchoolSettingsSchoolDto,
    val message: String? = null,
)

data class SchoolSettingsUpdateRequestDto(
    val name: String,
    val motto: String? = null,
    val address: String? = null,
    val phone: String? = null,
    val email: String? = null,
    val website: String? = null,
    @param:Json(name = "established_year") val establishedYear: Int? = null,
    val proprietor: String? = null,
    val slogan: String? = null,
)
