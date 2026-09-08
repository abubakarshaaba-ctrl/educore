package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

/**
 * Directory-safe staff projection returned by the administrator API.
 * Deliberately excludes email, phone, payroll, banking and credential data.
 */
@JsonClass(generateAdapter = true)
data class StaffDirectoryMemberDto(
    val id: Long,
    val name: String,
    @Json(name = "staff_id") val staffId: String? = null,
    val role: String,
    val active: Boolean,
)

@JsonClass(generateAdapter = true)
data class StaffDirectoryResponseDto(
    val staff: List<StaffDirectoryMemberDto> = emptyList(),
)

@JsonClass(generateAdapter = true)
data class StaffActiveUpdateRequestDto(
    @Json(name = "is_active") val isActive: Boolean,
)

@JsonClass(generateAdapter = true)
data class StaffAccountMutationResponseDto(
    val message: String,
    val active: Boolean,
)
