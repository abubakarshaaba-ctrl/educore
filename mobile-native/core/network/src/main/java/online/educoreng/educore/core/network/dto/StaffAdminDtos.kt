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
data class StaffDirectoryCountsDto(
    val total: Int = 0,
    val active: Int = 0,
    val inactive: Int = 0,
)

@JsonClass(generateAdapter = true)
data class StaffDirectoryMetaDto(
    val page: Int = 1,
    @Json(name = "per_page") val perPage: Int = 50,
    val total: Int = 0,
    @Json(name = "last_page") val lastPage: Int = 1,
    @Json(name = "has_more") val hasMore: Boolean = false,
)

@JsonClass(generateAdapter = true)
data class StaffDirectoryResponseDto(
    val staff: List<StaffDirectoryMemberDto> = emptyList(),
    val counts: StaffDirectoryCountsDto = StaffDirectoryCountsDto(),
    val meta: StaffDirectoryMetaDto = StaffDirectoryMetaDto(),
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

@JsonClass(generateAdapter = true)
data class CreateStaffAccountRequestDto(
    val name: String,
    val email: String,
    val role: String,
    val password: String,
    val phone: String? = null,
)

@JsonClass(generateAdapter = true)
data class CreatedStaffDto(
    val id: Long,
    val name: String,
)

@JsonClass(generateAdapter = true)
data class CreateStaffAccountResponseDto(
    val message: String,
    val staff: CreatedStaffDto,
)
