package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.StaffAccountMutationResponseDto
import online.educoreng.educore.core.network.dto.StaffActiveUpdateRequestDto
import online.educoreng.educore.core.network.dto.StaffDirectoryResponseDto
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.PATCH
import retrofit2.http.Path

/**
 * Narrow native staff-management contract.
 * Laravel tenant scoping and staff-module authorization remain authoritative.
 */
interface StaffAdminApi {
    @GET("admin/staff")
    suspend fun staff(): StaffDirectoryResponseDto

    @PATCH("admin/staff/{member}")
    suspend fun updateActiveState(
        @Path("member") memberId: Long,
        @Body request: StaffActiveUpdateRequestDto,
    ): StaffAccountMutationResponseDto
}
