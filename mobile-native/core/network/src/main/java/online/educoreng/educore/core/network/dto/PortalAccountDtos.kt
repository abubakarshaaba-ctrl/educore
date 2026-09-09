package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class PortalAccountCapabilitiesDto(val manage: Boolean = false)

data class PortalAccountSummaryDto(
    val students: Int = 0,
    @param:Json(name = "student_accounts") val studentAccounts: Int = 0,
    val guardians: Int = 0,
    @param:Json(name = "guardian_accounts") val guardianAccounts: Int = 0,
    @param:Json(name = "inactive_accounts") val inactiveAccounts: Int = 0,
)

data class PortalAccountStateDto(
    @param:Json(name = "user_id") val userId: Long,
    val email: String? = null,
    val active: Boolean = true,
    val role: String? = null,
)

data class PortalStudentAccountDto(
    val id: Long,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    val email: String? = null,
    @param:Json(name = "class") val className: String? = null,
    @param:Json(name = "class_level") val classLevel: String? = null,
    val account: PortalAccountStateDto? = null,
)

data class PortalChildDto(val id: Long, val name: String)

data class PortalGuardianAccountDto(
    val id: Long,
    val name: String,
    val email: String? = null,
    val phone: String? = null,
    val children: List<PortalChildDto> = emptyList(),
    val account: PortalAccountStateDto? = null,
)

data class PortalAccountsWorkspaceDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val capabilities: PortalAccountCapabilitiesDto = PortalAccountCapabilitiesDto(),
    val summary: PortalAccountSummaryDto = PortalAccountSummaryDto(),
    val students: List<PortalStudentAccountDto> = emptyList(),
    val guardians: List<PortalGuardianAccountDto> = emptyList(),
)

data class PortalAccountCreateRequestDto(
    val email: String,
    val password: String,
)

data class PortalPasswordResetRequestDto(val password: String)

data class PortalAccountMutationResponseDto(
    val message: String,
    val account: PortalAccountStateDto? = null,
    val created: Int? = null,
    val skipped: Int? = null,
)
