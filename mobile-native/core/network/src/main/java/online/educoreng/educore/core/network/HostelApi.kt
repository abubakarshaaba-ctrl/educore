package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.CreateHostelRequestDto
import online.educoreng.educore.core.network.dto.CreateHostelResponseDto
import online.educoreng.educore.core.network.dto.CreateHostelRoomRequestDto
import online.educoreng.educore.core.network.dto.CreateHostelRoomResponseDto
import online.educoreng.educore.core.network.dto.HostelAllocationRequestDto
import online.educoreng.educore.core.network.dto.HostelAllocationResponseDto
import online.educoreng.educore.core.network.dto.HostelMutationResponseDto
import online.educoreng.educore.core.network.dto.HostelWorkspaceDto
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

interface HostelApi {
    @GET("hostels")
    suspend fun index(
        @Query("search") search: String? = null,
        @Query("student_search") studentSearch: String? = null,
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 40,
    ): HostelWorkspaceDto

    @POST("hostels")
    suspend fun createHostel(@Body request: CreateHostelRequestDto): CreateHostelResponseDto

    @POST("hostels/{hostel}/rooms")
    suspend fun createRoom(
        @Path("hostel") hostelId: Long,
        @Body request: CreateHostelRoomRequestDto,
    ): CreateHostelRoomResponseDto

    @POST("hostels/allocations")
    suspend fun allocate(@Body request: HostelAllocationRequestDto): HostelAllocationResponseDto

    @POST("hostels/allocations/{allocation}/vacate")
    suspend fun vacate(@Path("allocation") allocationId: Long): HostelMutationResponseDto
}
