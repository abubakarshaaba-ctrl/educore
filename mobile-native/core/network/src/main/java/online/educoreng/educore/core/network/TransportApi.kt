package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.TransportAssignmentRequestDto
import online.educoreng.educore.core.network.dto.TransportDashboardDto
import online.educoreng.educore.core.network.dto.TransportManifestDto
import online.educoreng.educore.core.network.dto.TransportMutationResponseDto
import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

interface TransportApi {
    @GET("transport-officer/dashboard")
    suspend fun dashboard(
        @Query("search") search: String? = null,
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 40,
    ): TransportDashboardDto

    @GET("transport-officer/routes/{route}/manifest")
    suspend fun manifest(@Path("route") routeId: Long): TransportManifestDto

    @POST("transport-officer/assignments")
    suspend fun assign(@Body request: TransportAssignmentRequestDto): TransportMutationResponseDto

    @DELETE("transport-officer/assignments/{student}")
    suspend fun unassign(@Path("student") studentId: Long): TransportMutationResponseDto
}
